<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedReportRequestFilter extends Model
{
    /**
     * @var list<string>
     */
    public const FILTER_KEYS = [
        'organization_id',
        'status',
        'assigned_to_user_id',
        'requires_review',
        'date_from',
        'date_to',
        'q',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public static function normalizeFilters(array $input): array
    {
        $filters = [];

        foreach (self::FILTER_KEYS as $key) {
            if (! array_key_exists($key, $input)) {
                continue;
            }

            $value = $input[$key];

            if ($value === null || $value === '') {
                continue;
            }

            $filters[$key] = (string) $value;
        }

        return $filters;
    }
}
