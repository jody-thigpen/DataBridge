<?php

namespace App\Enums;

enum ReportOrderStatus: string
{
    case AwaitingCandidate = 'awaiting_candidate';
    case PendingReview = 'pending_review';
    case Assigned = 'assigned';
    case Approved = 'approved';
    case Submitted = 'submitted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AwaitingCandidate => 'Awaiting candidate',
            self::PendingReview => 'Pending review',
            self::Assigned => 'Assigned',
            self::Approved => 'Approved',
            self::Submitted => 'Submitted',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::AwaitingCandidate => 'badge',
            self::PendingReview => 'badge-muted',
            self::Assigned => 'badge',
            self::Approved => 'badge-success',
            self::Submitted => 'badge-success',
            self::Rejected => 'badge-muted',
            self::Cancelled => 'badge-muted',
        };
    }
}
