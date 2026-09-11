<?php

namespace App\Http\Controllers;

use App\Enums\LedgerAccountType;
use App\Enums\WithdrawalStatus;
use App\Http\Requests\RequestWithdrawalRequest;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogService;
use App\Services\LedgerService;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly PlatformSettingsService $settings,
        private readonly AuditLogService $auditLog,
    ) {}

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();
        $currency = strtoupper($request->query('currency', 'TTD'));

        $accountType = $user->isVa()
            ? LedgerAccountType::VaWallet
            : LedgerAccountType::ClientWallet;

        $account = $this->ledger->createAccount($user, $accountType, $currency);
        $balanceMinor = $this->ledger->getBalance($account);

        $wallet = $user->wallets()
            ->where('wallet_type', $accountType->value)
            ->where('currency', $currency)
            ->first();

        $balance = $balanceMinor / 100;

        if ($request->wantsJson()) {
            return response()->json([
                'wallet' => $wallet,
                'account' => $account,
                'balance_minor' => $balanceMinor,
                'balance' => $balance,
                'currency' => $currency,
            ]);
        }

        return view('wallet.index', compact('wallet', 'account', 'balanceMinor', 'balance', 'currency'));
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $withdrawals = WithdrawalRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json($withdrawals);
    }

    public function requestWithdrawal(RequestWithdrawalRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $currency = strtoupper($data['currency'] ?? 'TTD');
        $amountMinor = (int) round((float) $data['amount'] * 100);

        $feePercent = $user->isVa()
            ? (float) $this->settings->get('va_withdrawal_fee', 15)
            : (float) $this->settings->get('withdrawal_fee', 15);

        $minAmount = $user->isVa()
            ? (float) $this->settings->get('va_withdrawal_minimum', 150)
            : 0.0;

        if ($data['amount'] < $minAmount) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Minimum withdrawal amount is {$minAmount} {$currency}.",
                ], 422);
            }

            return back()->with('error', "Minimum withdrawal amount is {$minAmount} {$currency}.");
        }

        $accountType = $user->isVa()
            ? LedgerAccountType::VaWallet
            : LedgerAccountType::ClientWallet;

        $account = $this->ledger->createAccount($user, $accountType, $currency);

        if ($this->ledger->getBalance($account) < $amountMinor) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Insufficient balance.'], 422);
            }

            return back()->with('error', 'Insufficient balance.');
        }

        $feeMinor = (int) round($amountMinor * ($feePercent / 100));
        $netMinor = $amountMinor - $feeMinor;

        $withdrawal = WithdrawalRequest::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $account->wallet_id,
            'bank_detail_id' => $data['bank_detail_id'] ?? null,
            'status' => WithdrawalStatus::Pending,
            'amount' => $data['amount'],
            'amount_minor' => $amountMinor,
            'fee_amount' => $feeMinor / 100,
            'fee_amount_minor' => $feeMinor,
            'net_amount' => $netMinor / 100,
            'net_amount_minor' => $netMinor,
            'currency' => $currency,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->auditLog->log(
            'wallet.withdrawal_requested',
            $withdrawal,
            newValues: [
                'amount_minor' => $amountMinor,
                'fee_minor' => $feeMinor,
                'net_minor' => $netMinor,
            ],
            user: $user,
        );

        if ($request->wantsJson()) {
            return response()->json([
                'withdrawal' => $withdrawal,
                'fee_calculation' => [
                    'fee_percent' => $feePercent,
                    'fee_amount' => $feeMinor / 100,
                    'net_amount' => $netMinor / 100,
                ],
            ], 201);
        }

        return redirect()
            ->route('wallet.index')
            ->with('success', 'Withdrawal request submitted.');
    }
}
