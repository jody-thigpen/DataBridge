<?php

namespace App\Models;

use App\Enums\ReportRequestStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'screening_package_id',
        'requested_by_user_id',
        'assigned_to_user_id',
        'reviewed_by_user_id',
        'subject_name',
        'candidate_email',
        'candidate_phone',
        'invite_token',
        'invite_sent_at',
        'candidate_opened_at',
        'candidate_completed_at',
        'authorization_accepted_at',
        'authorization_ip',
        'authorization_user_agent',
        'candidate_answers',
        'acknowledged_document_ids',
        'notes',
        'price',
        'status',
        'requires_review',
        'assigned_at',
        'reviewed_at',
        'submitted_at',
        'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'requires_review' => 'boolean',
            'status' => ReportRequestStatus::class,
            'assigned_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'invite_sent_at' => 'datetime',
            'candidate_opened_at' => 'datetime',
            'candidate_completed_at' => 'datetime',
            'authorization_accepted_at' => 'datetime',
            'candidate_answers' => 'array',
            'acknowledged_document_ids' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function screeningPackage(): BelongsTo
    {
        return $this->belongsTo(ScreeningPackage::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function formattedPrice(): string
    {
        return '$'.number_format((float) $this->price, 2);
    }

    public function isAwaitingCandidate(): bool
    {
        return $this->status === ReportRequestStatus::AwaitingCandidate;
    }

    public function candidateHasCompleted(): bool
    {
        return $this->candidate_completed_at !== null;
    }

    public function inviteExpiresAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->invite_sent_at === null) {
            return null;
        }

        return $this->invite_sent_at->copy()->addDays(config('candidate_intake.invite_ttl_days', 3));
    }

    public function isInviteExpired(): bool
    {
        $expiresAt = $this->inviteExpiresAt();

        return $expiresAt !== null && $expiresAt->isPast();
    }

    public function isInviteActive(): bool
    {
        return $this->isAwaitingCandidate()
            && filled($this->invite_token)
            && ! $this->isInviteExpired();
    }

    public function scopeForOrganization(Builder $query, Organization $organization): Builder
    {
        return $query->where('organization_id', $organization->id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        if (! empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['assigned_to_user_id'])) {
            if ($filters['assigned_to_user_id'] === 'unassigned') {
                $query->whereNull('assigned_to_user_id');
            } else {
                $query->where('assigned_to_user_id', $filters['assigned_to_user_id']);
            }
        }

        if (isset($filters['requires_review']) && $filters['requires_review'] !== '') {
            $query->where('requires_review', filter_var($filters['requires_review'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function (Builder $search) use ($term): void {
                $search->where('subject_name', 'like', $term)
                    ->orWhere('candidate_email', 'like', $term)
                    ->orWhere('notes', 'like', $term)
                    ->orWhereHas('organization', fn (Builder $org) => $org->where('name', 'like', $term))
                    ->orWhereHas('screeningPackage', fn (Builder $package) => $package->where('name', 'like', $term))
                    ->orWhereHas('requestedBy', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('assignedTo', fn (Builder $user) => $user->where('name', 'like', $term));
            });
        }

        return $query;
    }

    public function scopeAwaitingReviewBy(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query
            ->where('requires_review', true)
            ->where('assigned_to_user_id', $userId)
            ->whereNotIn('status', [
                ReportRequestStatus::AwaitingCandidate,
                ReportRequestStatus::Submitted,
                ReportRequestStatus::Rejected,
                ReportRequestStatus::Cancelled,
            ]);
    }
}
