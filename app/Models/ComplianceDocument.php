<?php

namespace App\Models;

use App\Enums\ComplianceDocumentType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ComplianceDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'organization_id',
        'name',
        'document_type',
        'description',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'file_size',
        'is_active',
        'require_acknowledgment',
        'sort_order',
        'uploaded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => ComplianceDocumentType::class,
            'is_active' => 'boolean',
            'require_acknowledgment' => 'boolean',
            'file_size' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeRequiringAcknowledgment(Builder $query): Builder
    {
        return $query->active()->where('require_acknowledgment', true);
    }

    public function formattedFileSize(): string
    {
        $bytes = (int) $this->file_size;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return number_format($bytes / 1048576, 1).' MB';
    }

    public function deleteStoredFile(): void
    {
        if ($this->path !== '' && Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }
}
