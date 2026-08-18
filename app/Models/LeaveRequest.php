<?php

namespace App\Models;

use App\Notifications\LeaveRequestReviewed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    const DURATION_FULL_DAY = 'full_day';

    const DURATION_HALF_DAY = 'half_day';

    const DURATION_HOURS = 'hours';

    protected $fillable = [
        'user_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'duration_type',
        'hours',
        'days_count',
        'status',
        'note',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'reviewed_at' => 'datetime',
            'hours' => 'float',
            'days_count' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected static function booted(): void
    {
        static::saving(function (self $leaveRequest) {
            if ($leaveRequest->isDirty('status') && in_array($leaveRequest->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
                $leaveRequest->reviewed_by = auth()->id();
                $leaveRequest->reviewed_at = now();
            }
        });

        static::saved(function (self $leaveRequest) {
            if ($leaveRequest->wasChanged('status') && in_array($leaveRequest->status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true)) {
                $leaveRequest->user->notify(new LeaveRequestReviewed($leaveRequest));
            }
        });
    }
}
