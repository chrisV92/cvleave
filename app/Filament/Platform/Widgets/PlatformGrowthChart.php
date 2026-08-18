<?php

namespace App\Filament\Platform\Widgets;

use App\Models\Tenant;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PlatformGrowthChart extends ChartWidget
{
    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    // Without this the widget stretches to fill the column and a handful of
    // bars end up floating in ~700px of empty space.
    protected ?string $maxHeight = '260px';

    /**
     * One series only: companies signed up per month. Users deliberately are not
     * plotted alongside — they live on a different scale, and forcing two scales
     * onto one axis (or adding a second axis) would misrepresent both.
     */
    private const SERIES_COLOR = '#2a78d6';

    public function getHeading(): string
    {
        return __('Νέες εταιρείες ανά μήνα');
    }

    public function getDescription(): string
    {
        return __('Τελευταίοι 12 μήνες');
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect(range(11, 0))
            ->map(fn (int $back) => now()->startOfMonth()->subMonths($back));

        $counts = Tenant::query()
            ->where('created_at', '>=', $months->first())
            ->get(['created_at'])
            ->countBy(fn (Tenant $tenant) => $tenant->created_at->format('Y-m'));

        return [
            'datasets' => [
                [
                    'label' => __('Νέες εταιρείες'),
                    'data' => $months->map(fn (Carbon $month) => $counts->get($month->format('Y-m'), 0))->all(),
                    'backgroundColor' => self::SERIES_COLOR,
                    'borderColor' => self::SERIES_COLOR,
                    // Rounded data-ends, anchored to the baseline.
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                ],
            ],
            'labels' => $months->map(fn (Carbon $month) => $month->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                // A single series names itself in the heading — a legend box
                // would only repeat it.
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    // Whole companies only; no 0.5 gridlines on small numbers.
                    'ticks' => ['precision' => 0],
                    'grid' => ['drawBorder' => false],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
            // Leaves a surface gap between adjacent bars.
            'datasets' => ['bar' => ['categoryPercentage' => 0.7, 'barPercentage' => 0.85]],
        ];
    }
}
