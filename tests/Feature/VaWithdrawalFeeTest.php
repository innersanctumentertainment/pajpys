<?php

namespace Tests\Feature;

use App\Services\WithdrawalFeeService;
use App\Services\WithdrawalService;
use InvalidArgumentException;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class VaWithdrawalFeeTest extends MarketplaceTestCase
{
    public function test_scenario_seven_rejects_withdrawal_below_minimum(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $withdrawals = app(WithdrawalService::class);
        $va = $builder->createVa();
        $builder->fundVaWallet($va, 20000);

        $this->expectException(InvalidArgumentException::class);
        $withdrawals->requestVaWithdrawal($va, 10000);
    }

    public function test_scenario_seven_va_withdrawal_applies_fifteen_percent_fee(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $withdrawals = app(WithdrawalService::class);
        $fees = app(WithdrawalFeeService::class);
        $va = $builder->createVa();
        $builder->fundVaWallet($va, 20000);

        $withdrawal = $withdrawals->requestVaWithdrawal($va, 20000);
        $feeData = $fees->calculateVaFee(20000);

        $this->assertSame($feeData['fee_minor'], $withdrawal->fee_amount_minor);
        $this->assertSame($feeData['net_minor'], $withdrawal->net_amount_minor);
        $this->assertSame(3000, $withdrawal->fee_amount_minor);
        $this->assertSame(17000, $withdrawal->net_amount_minor);
    }
}
