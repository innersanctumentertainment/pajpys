<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Enums\PaymentStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\MarketplaceJob;
use App\Models\Payment;
use App\Models\Report;
use App\Models\User;
use App\Models\VerificationRecord;
use App\Models\WithdrawalRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse|View
    {
        $stats = $this->stats();

        if ($request->wantsJson()) {
            return response()->json($stats);
        }

        return view('admin.dashboard', ['stats' => $stats]);
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(): array
    {
        return [
            'users' => [
                'total' => User::query()->count(),
                'clients' => User::role('client')->count(),
                'vas' => User::role('va')->count(),
            ],
            'jobs' => [
                'total' => MarketplaceJob::query()->count(),
                'open' => MarketplaceJob::query()->where('status', JobStatus::Open)->count(),
                'in_progress' => MarketplaceJob::query()->where('status', JobStatus::InProgress)->count(),
                'completed' => MarketplaceJob::query()->where('status', JobStatus::Completed)->count(),
                'disputed' => MarketplaceJob::query()->where('status', JobStatus::Disputed)->count(),
            ],
            'payments' => [
                'total' => Payment::query()->count(),
                'volume_minor' => (int) Payment::query()->where('status', PaymentStatus::Succeeded)->sum('amount_minor'),
            ],
            'pending' => [
                'verifications' => VerificationRecord::query()->where('status', 'pending')->count(),
                'withdrawals' => WithdrawalRequest::query()->where('status', WithdrawalStatus::Pending)->count(),
                'disputes' => Dispute::query()->where('status', 'open')->count(),
                'reports' => Report::query()->where('status', 'pending')->count(),
            ],
        ];
    }
}
