<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessWithdrawalRequest;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogService;
use App\Services\LedgerService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly AuditLogService $auditLog,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $withdrawals = WithdrawalRequest::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->with(['user', 'wallet', 'bankDetail'])
            ->latest()
            ->paginate(30);

        return response()->json($withdrawals);
    }

    public function show(WithdrawalRequest $withdrawal): JsonResponse
    {
        return response()->json([
            'withdrawal' => $withdrawal->load(['user', 'wallet', 'bankDetail']),
        ]);
    }

    public function process(ProcessWithdrawalRequest $request, WithdrawalRequest $withdrawal): JsonResponse
    {
        $admin = $request->user();
        $action = $request->validated('action');

        if ($withdrawal->status !== WithdrawalStatus::Pending
            && ! ($action === 'mark_paid' && $withdrawal->status === WithdrawalStatus::Processing)) {
            return response()->json(['message' => 'Withdrawal is not in a processable state.'], 422);
        }

        match ($action) {
            'approve' => $this->approve($withdrawal, $admin),
            'reject' => $this->reject($withdrawal, $admin, $request->validated('rejection_reason')),
            'mark_paid' => $this->markPaid($withdrawal, $admin),
        };

        return response()->json(['withdrawal' => $withdrawal->fresh()]);
    }

    private function approve(WithdrawalRequest $withdrawal, \App\Models\User $admin): void
    {
        $withdrawal->update([
            'status' => WithdrawalStatus::Processing,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $this->auditLog->log('withdrawal.approved', $withdrawal, user: $admin);

        $this->notifications->notify(
            $withdrawal->user,
            'withdrawal.approved',
            'Withdrawal approved',
            'Your withdrawal request is being processed.',
            ['withdrawal_id' => $withdrawal->id],
        );
    }

    private function reject(WithdrawalRequest $withdrawal, \App\Models\User $admin, ?string $reason): void
    {
        $withdrawal->update([
            'status' => WithdrawalStatus::Cancelled,
            'rejection_reason' => $reason,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $this->auditLog->log('withdrawal.rejected', $withdrawal, newValues: ['reason' => $reason], user: $admin);

        $this->notifications->notify(
            $withdrawal->user,
            'withdrawal.rejected',
            'Withdrawal rejected',
            $reason ?? 'Your withdrawal request was rejected.',
            ['withdrawal_id' => $withdrawal->id],
        );
    }

    private function markPaid(WithdrawalRequest $withdrawal, \App\Models\User $admin): void
    {
        $user = $withdrawal->user;
        $accountType = $user->isVa()
            ? LedgerAccountType::VaWallet
            : LedgerAccountType::ClientWallet;

        $userAccount = $this->ledger->createAccount($user, $accountType, $withdrawal->currency);
        $platformAccount = $this->ledger->createAccount(null, LedgerAccountType::Platform, $withdrawal->currency);

        $this->ledger->transfer(
            from: $userAccount,
            to: $platformAccount,
            amountMinor: $withdrawal->amount_minor,
            debitType: LedgerEntryType::TransferOut,
            creditType: LedgerEntryType::TransferIn,
            reference: $withdrawal,
            idempotencyKey: "withdrawal:{$withdrawal->id}",
        );

        if ($withdrawal->fee_amount_minor > 0) {
            $this->ledger->postEntry(
                $platformAccount,
                $withdrawal->fee_amount_minor,
                $withdrawal->currency,
                LedgerEntryType::Fee,
                $withdrawal,
                "withdrawal:{$withdrawal->id}:fee",
                description: 'Withdrawal fee',
            );
        }

        $withdrawal->update([
            'status' => WithdrawalStatus::Completed,
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);

        $this->auditLog->log('withdrawal.paid', $withdrawal, user: $admin);

        $this->notifications->notify(
            $user,
            'withdrawal.completed',
            'Withdrawal completed',
            'Your withdrawal has been paid.',
            ['withdrawal_id' => $withdrawal->id],
        );
    }
}
