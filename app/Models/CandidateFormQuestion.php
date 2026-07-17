<?php

namespace App\Models;

use App\Enums\CandidateFormQuestionType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CandidateFormQuestion extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'label',
        'help_text',
        'field_key',
        'field_type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'field_type' => CandidateFormQuestionType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CandidateFormQuestion $question): void {
            if (blank($question->field_key)) {
                $question->field_key = Str::slug($question->label, '_');
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return list<string>
     */
    public function optionList(): array
    {
        $options = $this->options ?? [];

        return array_values(array_filter(array_map(
            fn ($option) => is_string($option) ? trim($option) : '',
            $options,
        )));
    }
}
