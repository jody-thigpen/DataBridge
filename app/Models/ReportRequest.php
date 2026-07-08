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
                ReportRequestStatus::Submitted,
                ReportRequestStatus::Rejected,
                ReportRequestStatus::Cancelled,
            ]);
    }
}
