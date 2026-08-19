<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaskAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // The row is the only record of where the file is, so it goes with it.
        static::deleted(function (self $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return Str::startsWith((string) $this->mime_type, 'image/');
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / 1024 / 1024, 1).' MB';
    }
}
