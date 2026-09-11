<?php

namespace App\Services;

use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class WithdrawalService
{
    public function __construct(
        private readonly LedgerService $ledger,
        private readonly WithdrawalFeeService $fees,
        private readonly AuditLogService $auditLog,
    ) {}

    public function requestVaWithdrawal(User $user, int $amountMinor, string $currency = 'TTD'): WithdrawalRequest
    {
        if (! $user->isVa()) {
            throw new InvalidArgumentException('Only virtual assistants can request VA withdrawals.');
        }

        if ($amountMinor < $this->fees->vaMinimumMinor()) {
            throw new InvalidArgumentException(
                sprintf('Minimum VA withdrawal is %d minor units.', $this->fees->vaMinimumMinor()),
            );
        }

        return $this->requestWithdrawal($user, $amountMinor, $currency, 'va');
    }

    public function requestClientWithdrawal(User $user, int $amountMinor, string $currency = 'TTD'): WithdrawalRequest
    {
        if (! $user->isClient()) {
            throw new InvalidArgumentException('Only clients can request client wallet withdrawals.');
        }

        return $this->requestWithdrawal($user, $amountMinor, $currency, 'client');
    }

    private function requestWithdrawal(User $user, int $amountMinor, string $currency, string $actorType): WithdrawalRequest
    {
        $currency = strtoupper($currency);
        $feeData = $actorType === 'va'
            ? $this->fees->calculateVaFee($amountMinor)
            : $this->fees->calculateClientFee($amountMinor);

        $accountType = $actorType === 'va'
            ? LedgerAccountType::VaWallet
            : LedgerAccountType::ClientWallet;

        return DB::transaction(function () use ($user, $amountMinor, $currency, $feeData, $accountType) {
            $wallet = Wallet::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'wallet_type' => $accountType->value,
                    'currency' => $currency,
                ],
                ['status' => 'active'],
            );

            $account = $this->ledger->createAccount($user, $accountType, $currency);

            if ($this->ledger->getBalance($account) < $amountMinor) {
                throw new RuntimeException('Insufficient wallet balance for withdrawal.');
            }

            $this->ledger->postEntry(
                $account,
                -$amountMinor,
                $currency,
                LedgerEntryType::Debit,
                null,
                sprintf('withdrawal:%s:%s', $user->id, uniqid('', true)),
                ['purpose' => 'withdrawal_request'],
            );

            $withdrawal = WithdrawalRequest::query()->create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'status' => 'pending',
                'amount' => $amountMinor / 100,
                'amount_minor' => $amountMinor,
                'fee_amount' => $feeData['fee_minor'] / 100,
                'fee_amount_minor' => $feeData['fee_minor'],
                'net_amount' => $feeData['net_minor'] / 100,
                'net_amount_minor' => $feeData['net_minor'],
                'currency' => $currency,
            ]);

            $this->auditLog->log(
                event: 'withdrawal.requested',
                auditable: $withdrawal,
                newValues: [
                    'amount_minor' => $amountMinor,
                    'fee_minor' => $feeData['fee_minor'],
                    'net_minor' => $feeData['net_minor'],
                ],
                user: $user,
            );

            return $withdrawal;
        });
    }
}
