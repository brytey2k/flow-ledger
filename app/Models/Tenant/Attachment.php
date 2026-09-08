<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\AttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'user_id',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /** @return MorphTo<Model, $this> */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function formattedSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    public function isPreviewable(): bool
    {
        return $this->isSpreadsheetPreviewable() || $this->isWordPreviewable() || $this->isNativePreviewable();
    }

    public function isSpreadsheetPreviewable(): bool
    {
        return in_array($this->previewExtension(), ['xls', 'xlsx'], true);
    }

    public function isWordPreviewable(): bool
    {
        return $this->mime_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            && $this->previewExtension() === 'docx';
    }

    private function previewExtension(): string
    {
        return Str::lower(pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    private function isNativePreviewable(): bool
    {
        $extension = $this->previewExtension();

        return match ($this->mime_type) {
            'application/pdf' => $extension === 'pdf',
            'image/jpeg' => in_array($extension, ['jpg', 'jpeg'], true),
            'image/png' => $extension === 'png',
            'image/webp' => $extension === 'webp',
            default => false,
        };
    }
}
