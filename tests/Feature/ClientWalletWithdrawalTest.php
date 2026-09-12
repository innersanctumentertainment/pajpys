<?php

namespace Tests\Feature;

use App\Enums\LedgerAccountType;
use App\Services\WithdrawalService;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class ClientWalletWithdrawalTest extends MarketplaceTestCase
{
    public function test_scenario_six_client_wallet_withdrawal_with_fifteen_percent_fee(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $withdrawals = app(WithdrawalService::class);
        $client = $builder->createClient();
        $builder->fundClientWallet($client, 50000);

        $withdrawal = $withdrawals->requestClientWithdrawal($client, 20000);

        $this->assertSame('pending', $withdrawal->status);
        $this->assertSame(3000, $withdrawal->fee_amount_minor);
        $this->assertSame(17000, $withdrawal->net_amount_minor);

        $account = $builder->ledger()->createAccount($client, LedgerAccountType::ClientWallet, 'TTD');
        $this->assertSame(30000, $builder->ledger()->getBalance($account));
    }
}
