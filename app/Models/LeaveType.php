<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'requires_note',
        'auto_calculate',
        'use_greek_law_formula',
        'fixed_days_per_year',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'requires_note' => 'boolean',
            'auto_calculate' => 'boolean',
            'use_greek_law_formula' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function accrualRules(): HasMany
    {
        return $this->hasMany(LeaveAccrualRule::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
