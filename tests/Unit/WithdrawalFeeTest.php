<?php

namespace Tests\Unit;

use App\Services\WithdrawalFeeService;
use Tests\MarketplaceTestCase;

class WithdrawalFeeTest extends MarketplaceTestCase
{
    private WithdrawalFeeService $fees;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fees = app(WithdrawalFeeService::class);
    }

    public function test_va_withdrawal_fee_is_fifteen_percent(): void
    {
        $result = $this->fees->calculateVaFee(10000);
        $this->assertSame(15.0, $result['fee_percentage']);
        $this->assertSame(1500, $result['fee_minor']);
        $this->assertSame(8500, $result['net_minor']);
    }

    public function test_client_withdrawal_fee_is_fifteen_percent(): void
    {
        $result = $this->fees->calculateClientFee(20000);
        $this->assertSame(15.0, $result['fee_percentage']);
        $this->assertSame(3000, $result['fee_minor']);
        $this->assertSame(17000, $result['net_minor']);
    }

    public function test_va_minimum_withdrawal_from_settings(): void
    {
        $this->assertSame(15000, $this->fees->vaMinimumMinor());
    }
}
