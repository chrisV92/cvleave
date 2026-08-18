<?php

namespace App\Services;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    public function greekLawEntitledDays(User $user, DateTimeInterface $asOf): float
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
     *
     * $excludeLeaveRequestId leaves one request out of the total — used when
     * re-evaluating that request itself (e.g. approving it, or editing an
     * already-approved one), so it isn't counted against its own allowance.
     */
    public function usedDays(User $user, LeaveType $leaveType, int $year, ?int $excludeLeaveRequestId = null): float
    {
        // Days taken during $year that came out of $year's own entitlement...
        $ownYear = (float) $this->approvedRequests($user, $leaveType, $excludeLeaveRequestId)
            ->whereYear('start_date', $year)
            ->sum(DB::raw('days_count - days_from_carryover'));

        // ...plus days taken early in the following year that were drawn back
        // from what was left of $year.
        $carriedForward = (float) $this->approvedRequests($user, $leaveType, $excludeLeaveRequestId)
            ->whereYear('start_date', $year + 1)
            ->sum('days_from_carryover');

        return $ownYear + $carriedForward;
    }

    public function remainingDays(User $user, LeaveType $leaveType, int $year, ?int $excludeLeaveRequestId = null): float
    {
        return $this->entitledDays($user, $leaveType, $year)
            - $this->usedDays($user, $leaveType, $year, $excludeLeaveRequestId);
    }

    /**
     * Leftover days from the previous year that can still be used on $asOf,
     * i.e. only while the company's carry-over deadline has not passed and the
     * leave type is one that carries over at all.
     */
    public function carryoverAvailable(User $user, LeaveType $leaveType, DateTimeInterface|string $asOf, ?int $excludeLeaveRequestId = null): float
    {
        if (! $leaveType->allows_carryover) {
            return 0;
        }

        $asOf = Carbon::parse($asOf);
        $deadline = $user->tenant?->carryoverDeadlineFor($asOf->year);

        if (! $deadline || $asOf->greaterThan($deadline)) {
            return 0;
        }

        return max(0, $this->remainingDays($user, $leaveType, $asOf->year - 1, $excludeLeaveRequestId));
    }

    /**
     * The whole pool a request starting on $startDate may draw from: whatever
     * is left of the previous year (while still in time) plus this year's own
     * remaining entitlement.
     */
    public function availableFor(User $user, LeaveType $leaveType, DateTimeInterface|string $startDate, ?int $excludeLeaveRequestId = null): float
    {
        $startDate = Carbon::parse($startDate);

        return $this->carryoverAvailable($user, $leaveType, $startDate, $excludeLeaveRequestId)
            + max(0, $this->remainingDays($user, $leaveType, $startDate->year, $excludeLeaveRequestId));
    }

    /**
     * How much of a $days request should be charged to the previous year.
     * Carried-over days are spent first because they are the ones that expire.
     */
    public function allocateFromCarryover(User $user, LeaveType $leaveType, DateTimeInterface|string $startDate, float $days, ?int $excludeLeaveRequestId = null): float
    {
        $carryover = $this->carryoverAvailable($user, $leaveType, $startDate, $excludeLeaveRequestId);

        return round(min($days, $carryover), 3);
    }

    /**
     * Whether approving this request would push the employee past what they
     * have available. Submitting a request only checks against already-approved
     * leave, so two requests that each fit on their own can still overdraw the
     * balance once both are approved — this is the guard for that moment.
     */
    public function approvalWouldExceedBalance(LeaveRequest $leaveRequest): bool
    {
        $available = $this->availableFor(
            $leaveRequest->user,
            $leaveRequest->leaveType,
            $leaveRequest->start_date,
            excludeLeaveRequestId: $leaveRequest->getKey(),
        );

        return (float) $leaveRequest->days_count > $available;
    }

    /**
     * @return HasMany<LeaveRequest, User>
     */
    private function approvedRequests(User $user, LeaveType $leaveType, ?int $excludeLeaveRequestId)
    {
        return $user->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->when($excludeLeaveRequestId, fn ($query) => $query->whereKeyNot($excludeLeaveRequestId));
    }

    /**
     * Entitled/used/remaining days for every active leave type for a user, in a
     * constant number of queries (not one pair of queries per leave type) —
     * used by the dashboard "My Leave Balances" widget.
     *
     * Carried-over days from the previous year are reported separately rather
     * than folded into the total, so an employee can see which days are about
     * to expire.
     *
     * @return Collection<int, object{leaveType: LeaveType, entitled: float, used: float, remaining: float, carryoverRemaining: float, carryoverEntitled: float, carryoverExpiresAt: ?Carbon}>
     */
    public function summaryForUser(User $user, int $year): Collection
    {
        $leaveTypes = LeaveType::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->with('accrualRules')
            ->get();

        $overrides = [];
        foreach ($user->leaveBalances()->whereIn('year', [$year - 1, $year])->get() as $balance) {
            $overrides[$balance->year][$balance->leave_type_id] = $balance->manual_override_days;
        }

        // One grouped query covering the previous, current and next year, so
        // the carry-over figures cost no extra queries per leave type.
        $totals = [];
        $rows = $user->leaveRequests()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereBetween('start_date', [
                Carbon::create($year - 1, 1, 1)->startOfDay(),
                Carbon::create($year + 1, 12, 31)->endOfDay(),
            ])
            ->selectRaw('leave_type_id, YEAR(start_date) as year_bucket, SUM(days_count - days_from_carryover) as own_days, SUM(days_from_carryover) as carried_days')
            ->groupBy('leave_type_id', 'year_bucket')
            ->get();

        foreach ($rows as $row) {
            $totals[$row->leave_type_id][(int) $row->year_bucket] = [
                'own' => (float) $row->own_days,
                'carried' => (float) $row->carried_days,
            ];
        }

        $usedIn = fn (int $leaveTypeId, int $bucketYear): float => ($totals[$leaveTypeId][$bucketYear]['own'] ?? 0)
            + ($totals[$leaveTypeId][$bucketYear + 1]['carried'] ?? 0);

        $deadline = $user->tenant?->carryoverDeadlineFor($year);
        $carryoverStillOpen = $deadline && now()->lessThanOrEqualTo($deadline);

        return $leaveTypes->map(function (LeaveType $leaveType) use ($user, $year, $overrides, $usedIn, $deadline, $carryoverStillOpen) {
            $entitled = $this->entitlementFor($user, $leaveType, $year, $overrides[$year][$leaveType->id] ?? null);
            $used = $usedIn($leaveType->id, $year);

            $carryoverEntitled = 0.0;
            $carryoverRemaining = 0.0;

            if ($carryoverStillOpen && $leaveType->allows_carryover) {
                $carryoverEntitled = $this->entitlementFor($user, $leaveType, $year - 1, $overrides[$year - 1][$leaveType->id] ?? null);
                $carryoverRemaining = max(0, $carryoverEntitled - $usedIn($leaveType->id, $year - 1));
            }

            return (object) [
                'leaveType' => $leaveType,
                'entitled' => $entitled,
                'used' => $used,
                'remaining' => $entitled - $used,
                'carryoverEntitled' => $carryoverEntitled,
                'carryoverRemaining' => $carryoverRemaining,
                'carryoverExpiresAt' => $carryoverRemaining > 0 ? $deadline : null,
            ];
        });
    }

    /**
     * Entitlement for one leave type/year from already-loaded data — shared by
     * the current-year and previous-year (carry-over) figures so both follow
     * exactly the same rules.
     */
    private function entitlementFor(User $user, LeaveType $leaveType, int $year, float|int|string|null $override): float
    {
        if ($override !== null) {
            return (float) $override;
        }

        $asOf = now()->setYear($year)->endOfYear();

        if ($leaveType->auto_calculate && $leaveType->use_greek_law_formula) {
            return $this->greekLawEntitledDays($user, $asOf);
        }

        if ($leaveType->auto_calculate) {
            $yearsOfService = $user->yearsOfService($asOf);

            $rule = $leaveType->accrualRules
                ->filter(fn ($rule) => $rule->min_years_service <= $yearsOfService
                    && ($rule->max_years_service === null || $rule->max_years_service >= $yearsOfService))
                ->sortByDesc('min_years_service')
                ->first();

            return $rule ? (float) $rule->days_per_year : (float) ($leaveType->fixed_days_per_year ?? 0);
        }

        return (float) ($leaveType->fixed_days_per_year ?? 0);
    }

    /**
     * Whether the given date range overlaps an existing pending/approved leave
     * request for the same user (inclusive — a leave ending on day X blocks a
     * new leave from starting on that same day X).
     */
    public function hasOverlap(User $user, DateTimeInterface|string $start, DateTimeInterface|string $end, ?int $excludeLeaveRequestId = null): bool
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
    public function countBusinessDays(DateTimeInterface $start, DateTimeInterface $end): int
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
