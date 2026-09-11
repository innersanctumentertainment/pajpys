<?php

namespace App\Services;

use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class StaffPayoutService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function processPayout(User $staff, WithdrawalRequest $withdrawal): WithdrawalRequest
    {
        if (! $staff->hasAnyRole(['master_admin', 'staff'])) {
            throw new AuthorizationException('Staff access required.');
        }

        if (! $staff->can('payments.manage')) {
            $this->auditLog->log(
                event: 'payout.unauthorized_attempt',
                auditable: $withdrawal,
                newValues: [
                    'staff_id' => $staff->id,
                    'withdrawal_id' => $withdrawal->id,
                ],
                user: $staff,
            );

            throw new AuthorizationException('You do not have permission to process payouts.');
        }

        return DB::transaction(function () use ($staff, $withdrawal) {
            $withdrawal->update([
                'status' => 'processed',
                'processed_by' => $staff->id,
                'processed_at' => now(),
            ]);

            $this->auditLog->log(
                event: 'payout.processed',
                auditable: $withdrawal->fresh(),
                newValues: ['status' => 'processed'],
                user: $staff,
            );

            return $withdrawal->fresh();
        });
    }
}
