<?php

namespace Tests\Unit;

use App\Enums\LedgerAccountType;
use App\Enums\LedgerEntryType;
use App\Models\User;
use App\Services\LedgerService;
use InvalidArgumentException;
use RuntimeException;
use Tests\MarketplaceTestCase;

class LedgerServiceTest extends MarketplaceTestCase
{
    private LedgerService $ledger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ledger = app(LedgerService::class);
    }

    public function test_balance_calculation_sums_entries(): void
    {
        $user = User::factory()->create();
        $account = $this->ledger->createAccount($user, LedgerAccountType::ClientWallet, 'TTD');
        $this->ledger->postEntry($account, 5000, 'TTD', LedgerEntryType::Credit, null, 'credit-1');
        $this->ledger->postEntry($account, 2500, 'TTD', LedgerEntryType::Credit, null, 'credit-2');
        $this->ledger->postEntry($account, -1000, 'TTD', LedgerEntryType::Debit, null, 'debit-1');
        $this->assertSame(6500, $this->ledger->getBalance($account));
    }

    public function test_transfer_moves_funds_between_accounts(): void
    {
        $user = User::factory()->create();
        $from = $this->ledger->createAccount($user, LedgerAccountType::ClientWallet, 'TTD');
        $to = $this->ledger->createAccount($user, LedgerAccountType::VaWallet, 'TTD');
        $this->ledger->postEntry($from, 10000, 'TTD', LedgerEntryType::Credit, null, 'seed-from');
        $result = $this->ledger->transfer($from, $to, 4000, idempotencyKey: 'transfer-1');
        $this->assertSame(6000, $this->ledger->getBalance($from));
        $this->assertSame(4000, $this->ledger->getBalance($to));
        $this->assertSame(-4000, $result['debit']->amount_minor);
        $this->assertSame(4000, $result['credit']->amount_minor);
    }

    public function test_post_entry_is_idempotent(): void
    {
        $user = User::factory()->create();
        $account = $this->ledger->createAccount($user, LedgerAccountType::ClientWallet, 'TTD');
        $first = $this->ledger->postEntry($account, 3000, 'TTD', LedgerEntryType::Credit, null, 'idem-key');
        $second = $this->ledger->postEntry($account, 3000, 'TTD', LedgerEntryType::Credit, null, 'idem-key');
        $this->assertTrue($first->is($second));
        $this->assertSame(3000, $this->ledger->getBalance($account));
    }

    public function test_transfer_rejects_negative_balances(): void
    {
        $user = User::factory()->create();
        $from = $this->ledger->createAccount($user, LedgerAccountType::ClientWallet, 'TTD');
        $to = $this->ledger->createAccount($user, LedgerAccountType::VaWallet, 'TTD');
        $this->ledger->postEntry($from, 1000, 'TTD', LedgerEntryType::Credit, null, 'seed-small');
        $this->expectException(RuntimeException::class);
        $this->ledger->transfer($from, $to, 5000, idempotencyKey: 'transfer-fail');
    }

    public function test_post_entry_rejects_zero_amount(): void
    {
        $user = User::factory()->create();
        $account = $this->ledger->createAccount($user, LedgerAccountType::ClientWallet, 'TTD');
        $this->expectException(InvalidArgumentException::class);
        $this->ledger->postEntry($account, 0, 'TTD', LedgerEntryType::Credit, null, 'zero');
    }
}
