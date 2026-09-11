<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case PendingPostingFee = 'pending_posting_fee';
    case PendingPayment = 'pending_payment';
    case Open = 'open';
    case Accepting = 'accepting';
    case Selecting = 'selecting';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case PendingApproval = 'pending_approval';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Disputed = 'disputed';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::PendingPostingFee, self::Cancelled],
            self::PendingPostingFee => [self::PendingPayment, self::Cancelled],
            self::PendingPayment => [self::Open, self::Cancelled],
            self::Open => [self::Accepting, self::Cancelled],
            self::Accepting => [self::Selecting, self::Open, self::Cancelled],
            self::Selecting => [self::Assigned, self::Accepting, self::Cancelled],
            self::Assigned => [self::InProgress, self::Cancelled, self::Disputed],
            self::InProgress => [self::PendingApproval, self::Cancelled, self::Disputed],
            self::PendingApproval => [self::Completed, self::InProgress, self::Disputed],
            self::Completed => [self::Disputed],
            self::Cancelled => [],
            self::Disputed => [self::InProgress, self::Completed, self::Cancelled],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
