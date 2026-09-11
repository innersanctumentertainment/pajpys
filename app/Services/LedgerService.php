<?php

namespace App\Services;

use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class LedgerService
{
    public function createAccount(
        ?User $user,
        LedgerAccountType $type,
        string $currency = 'TTD',
        array $metadata = [],
    ): LedgerAccount {
        if ($type->requiresUser() && $user === null) {
            throw new InvalidArgumentException("Account type [{$type->value}] requires a user.");
        }

        $currency = strtoupper($currency);
        $wallet = $this->resolveWallet($user, $type, $currency);
        $code = $this->accountCode($type, $user?->id, $currency);

        return LedgerAccount::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => Str::headline($type->value),
                'account_type' => $type,
                'wallet_id' => $wallet->id,
                'currency' => $currency,
                'is_active' => true,
            ],
        );
    }

    public function postEntry(
        LedgerAccount $account,
        int $amountMinor,
        string $currency,
        LedgerEntryType $type,
        ?Model $reference,
        string $idempotencyKey,
        array $metadata = [],
        ?string $description = null,
    ): LedgerEntry {
        if ($amountMinor === 0) {
            throw new InvalidArgumentException('Entry amount must be non-zero.');
        }

        $currency = strtoupper($currency);

        if ($account->currency !== $currency) {
            throw new InvalidArgumentException('Entry currency must match account currency.');
        }

        $existing = LedgerEntry::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $now = now();

        return LedgerEntry::query()->create([
            'ledger_account_id' => $account->id,
            'entry_type' => $type,
            'amount_minor' => $amountMinor,
            'currency' => $currency,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'idempotency_key' => $idempotencyKey,
            'description' => $description,
            'metadata' => $metadata,
            'recorded_at' => $now,
            'created_at' => $now,
        ]);
    }

    public function getBalance(LedgerAccount $account, ?string $currency = null): int
    {
        $currency = strtoupper($currency ?? $account->currency);

        return (int) LedgerEntry::query()
            ->where('ledger_account_id', $account->id)
            ->where('currency', $currency)
            ->sum('amount_minor');
    }

    /**
     * @return array{debit: LedgerEntry, credit: LedgerEntry}
     */
    public function transfer(
        LedgerAccount $from,
        LedgerAccount $to,
        int $amountMinor,
        LedgerEntryType $debitType = LedgerEntryType::TransferOut,
        LedgerEntryType $creditType = LedgerEntryType::TransferIn,
        ?Model $reference = null,
        string $idempotencyKey = '',
        array $metadata = [],
    ): array {
        if ($amountMinor <= 0) {
            throw new InvalidArgumentException('Transfer amount must be positive.');
        }

        if ($from->currency !== $to->currency) {
            throw new InvalidArgumentException('Transfer accounts must share the same currency.');
        }

        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Cannot transfer to the same account.');
        }

        if ($idempotencyKey === '') {
            $idempotencyKey = 'transfer:'.Str::uuid()->toString();
        }

        return DB::transaction(function () use (
            $from,
            $to,
            $amountMinor,
            $debitType,
            $creditType,
            $reference,
            $idempotencyKey,
            $metadata,
        ) {
            $lockedFrom = LedgerAccount::query()
                ->whereKey($from->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedTo = LedgerAccount::query()
                ->whereKey($to->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->getBalance($lockedFrom) < $amountMinor) {
                throw new RuntimeException('Insufficient balance for transfer.');
            }

            $debit = $this->postEntry(
                $lockedFrom,
                -$amountMinor,
                $lockedFrom->currency,
                $debitType,
                $reference,
                $idempotencyKey.':debit',
                $metadata,
            );

            $credit = $this->postEntry(
                $lockedTo,
                $amountMinor,
                $lockedTo->currency,
                $creditType,
                $reference,
                $idempotencyKey.':credit',
                $metadata,
            );

            return [
                'debit' => $debit,
                'credit' => $credit,
            ];
        });
    }

    private function resolveWallet(?User $user, LedgerAccountType $type, string $currency): Wallet
    {
        return Wallet::query()->firstOrCreate(
            [
                'user_id' => $user?->id,
                'wallet_type' => $type->value,
                'currency' => $currency,
            ],
            [
                'status' => 'active',
            ],
        );
    }

    private function accountCode(LedgerAccountType $type, ?int $userId, string $currency): string
    {
        return sprintf('%s:%s:%s', $type->value, $userId ?? 'system', $currency);
    }
}
