<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveBalanceService
{
    /**
     * The number of days a user is entitled to for a leave type in a given year.
     * Manual override (from leave_balances) always wins if set; otherwise falls
     * back to the accrual rule matching the user's years of service, or the
     * leave type's fixed_days_per_year.
     */
    public function entitledDays(User $user, LeaveType $leaveType, int $year): float
    {
        $override = $user->leaveBalances()
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->value('manual_override_days');

        if ($override !== null) {
            return (float) $override;
        }

        if ($leaveType->auto_calculate && $leaveType->use_greek_law_formula) {
            return $this->greekLawEntitledDays($user, now()->setYear($year)->endOfYear());
        }

        if ($leaveType->auto_calculate) {
            $years = $user->yearsOfService(now()->setYear($year)->endOfYear());

            $rule = $leaveType->accrualRules()
                ->where('min_years_service', '<=', $years)
                ->where(function ($query) use ($years) {
                    $query->whereNull('max_years_service')
                        ->orWhere('max_years_service', '>=', $years);
                })
                ->orderByDesc('min_years_service')
                ->first();

            if ($rule) {
                return (float) $rule->days_per_year;
            }
        }

        return (float) ($leaveType->fixed_days_per_year ?? 0);
    }

    /**
     * Annual leave entitlement per Greek labour law (Α.Ν. 539/1945 as amended),
     * for a 5-day work week:
     *  - Year 1 at this employer: 2 days per completed month, capped at 20.
     *  - Year 2 at this employer: 21 days.
     *  - Year 3+ at this employer, under 12 years TOTAL career seniority
     *    (any employer) and under 10 years at this employer: 22 days.
     *  - 12+ years total career seniority, OR 10+ years at this employer: 25 days.
     *  - 25+ years total career seniority: 26 days.
     * "Total career seniority" includes declared prior experience at other
     * employers (users.prior_experience_years) — not just this employer.
     */
    public function greekLawEntitledDays(User $user, \DateTimeInterface $asOf): float
    {
        if (! $user->hire_date) {
            return 0;
        }

        $asOf = Carbon::parse($asOf);
        $hireDate = $user->hire_date;

        if ($asOf->lessThan($hireDate)) {
            return 0;
        }

        $monthsAtEmployer = (int) $hireDate->diffInMonths($asOf);
        $yearsAtEmployer = (int) $hireDate->diffInYears($asOf);
        $totalCareerYears = $yearsAtEmployer + (float) $user->prior_experience_years;

        if ($yearsAtEmployer < 1) {
            return min($monthsAtEmployer * 2, 20);
        }

        if ($yearsAtEmployer < 2) {
            return 21;
        }

        if ($totalCareerYears >= 25) {
            return 26;
        }

        if ($totalCareerYears >= 12 || $yearsAtEmployer >= 10) {
            return 25;
        }

        return 22;
    }

    /**
     * Days already used (approved requests) for a user/leave type in a given year.
     */
    public function usedDays(User $user, LeaveType $leaveType, int $year): float
    {
        return (float) $user->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->sum('days_count');
    }

    public function remainingDays(User $user, LeaveType $leaveType, int $year): float
    {
        return $this->entitledDays($user, $leaveType, $year) - $this->usedDays($user, $leaveType, $year);
    }

    /**
     * Entitled/used/remaining days for every active leave type for a user, in a
     * constant number of queries (not one pair of queries per leave type) —
     * used by the dashboard "My Leave Balances" widget.
     *
     * @return Collection<int, object{leaveType: LeaveType, entitled: float, used: float, remaining: float}>
     */
    public function summaryForUser(User $user, int $year): Collection
    {
        $leaveTypes = LeaveType::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->with('accrualRules')
            ->get();

        $overrides = $user->leaveBalances()
            ->where('year', $year)
            ->pluck('manual_override_days', 'leave_type_id');

        $usedByType = $user->leaveRequests()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, SUM(days_count) as total')
            ->groupBy('leave_type_id')
            ->pluck('total', 'leave_type_id');

        $asOf = now()->setYear($year)->endOfYear();
        $yearsOfService = $user->yearsOfService($asOf);

        return $leaveTypes->map(function (LeaveType $leaveType) use ($user, $asOf, $yearsOfService, $overrides, $usedByType) {
            $override = $overrides->get($leaveType->id);

            if ($override !== null) {
                $entitled = (float) $override;
            } elseif ($leaveType->auto_calculate && $leaveType->use_greek_law_formula) {
                $entitled = $this->greekLawEntitledDays($user, $asOf);
            } elseif ($leaveType->auto_calculate) {
                $rule = $leaveType->accrualRules
                    ->filter(fn ($rule) => $rule->min_years_service <= $yearsOfService
                        && ($rule->max_years_service === null || $rule->max_years_service >= $yearsOfService))
                    ->sortByDesc('min_years_service')
                    ->first();

                $entitled = $rule ? (float) $rule->days_per_year : (float) ($leaveType->fixed_days_per_year ?? 0);
            } else {
                $entitled = (float) ($leaveType->fixed_days_per_year ?? 0);
            }

            $used = (float) $usedByType->get($leaveType->id, 0);

            return (object) [
                'leaveType' => $leaveType,
                'entitled' => $entitled,
                'used' => $used,
                'remaining' => $entitled - $used,
            ];
        });
    }

    /**
     * Whether the given date range overlaps an existing pending/approved leave
     * request for the same user (inclusive — a leave ending on day X blocks a
     * new leave from starting on that same day X).
     */
    public function hasOverlap(User $user, \DateTimeInterface|string $start, \DateTimeInterface|string $end, ?int $excludeLeaveRequestId = null): bool
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        return $user->leaveRequests()
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->when($excludeLeaveRequestId, fn ($query) => $query->whereKeyNot($excludeLeaveRequestId))
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }

    /**
     * Business-day count between two dates (inclusive), excluding weekends.
     */
    public function countBusinessDays(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $period = new \DatePeriod(
            \DateTime::createFromInterface($start),
            new \DateInterval('P1D'),
            (\DateTime::createFromInterface($end))->modify('+1 day')
        );

        $count = 0;
        foreach ($period as $date) {
            if ((int) $date->format('N') < 6) {
                $count++;
            }
        }

        return $count;
    }
}
