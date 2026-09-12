<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\StaffPayoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Tests\MarketplaceTestCase;
use Tests\Support\MarketplaceScenarioBuilder;

class StaffPermissionTest extends MarketplaceTestCase
{
    public function test_scenario_nine_unauthorized_payout_denied_and_audited(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $payouts = app(StaffPayoutService::class);
        $va = $builder->createVa();
        $builder->fundVaWallet($va, 20000);

        $wallet = Wallet::query()->where('user_id', $va->id)->first();
        $withdrawal = WithdrawalRequest::query()->create([
            'user_id' => $va->id,
            'wallet_id' => $wallet->id,
            'status' => 'pending',
            'amount' => 200,
            'amount_minor' => 20000,
            'fee_amount' => 30,
            'fee_amount_minor' => 3000,
            'net_amount' => 170,
            'net_amount_minor' => 17000,
            'currency' => 'TTD',
        ]);

        $unauthorizedStaff = User::factory()->create([
            'email' => 'staff-limited@example.com',
            'password' => 'Password123!',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
        $unauthorizedStaff->assignRole('staff');

        $this->expectException(AuthorizationException::class);

        try {
            $payouts->processPayout($unauthorizedStaff, $withdrawal);
        } finally {
            $this->assertDatabaseHas('audit_logs', [
                'event' => 'payout.unauthorized_attempt',
                'user_id' => $unauthorizedStaff->id,
            ]);
        }
    }

    public function test_authorized_staff_can_process_payout(): void
    {
        $builder = app(MarketplaceScenarioBuilder::class);
        $payouts = app(StaffPayoutService::class);
        $va = $builder->createVa();
        $builder->fundVaWallet($va, 20000);

        $wallet = Wallet::query()->where('user_id', $va->id)->first();
        $withdrawal = WithdrawalRequest::query()->create([
            'user_id' => $va->id,
            'wallet_id' => $wallet->id,
            'status' => 'pending',
            'amount' => 200,
            'amount_minor' => 20000,
            'fee_amount' => 30,
            'fee_amount_minor' => 3000,
            'net_amount' => 170,
            'net_amount_minor' => 17000,
            'currency' => 'TTD',
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'email_verified' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('master_admin');

        $processed = $payouts->processPayout($admin, $withdrawal);

        $this->assertSame('processed', $processed->status);
        $this->assertTrue(AuditLog::query()->where('event', 'payout.processed')->exists());
    }
}
