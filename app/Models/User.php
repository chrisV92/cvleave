<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'hire_date', 'prior_experience_years', 'is_platform_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'hire_date' => 'date',
            'prior_experience_years' => 'float',
            'is_platform_admin' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'platform') {
            return (bool) $this->is_platform_admin;
        }

        return true;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenant ? collect([$this->tenant]) : collect();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->tenant_id === $tenant->id;
    }

    public function canImpersonate(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function canBeImpersonated(): bool
    {
        return ! $this->is_platform_admin;
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function yearsOfService(?\DateTimeInterface $asOf = null): int
    {
        if (! $this->hire_date) {
            return 0;
        }

        return (int) $this->hire_date->diffInYears($asOf ?? now());
    }

    /**
     * Total career seniority for Greek annual-leave-law purposes: years at this
     * employer plus any declared prior experience at other employers.
     */
    public function totalCareerYears(?\DateTimeInterface $asOf = null): float
    {
        return $this->yearsOfService($asOf) + (float) $this->prior_experience_years;
    }
}
