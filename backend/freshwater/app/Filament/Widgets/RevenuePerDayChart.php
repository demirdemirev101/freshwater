<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenuePerDayChart extends ChartWidget
{
    protected ?string $heading = 'Печалби за деня';

    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 3;
    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $days = $this->lastDays(14);
        $totals = $this->totalsByDay($days);

        return [
            'labels' => $days->map(fn (Carbon $day) => $day->format('d M'))->all(),
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $days->map(fn (Carbon $day) => (float) ($totals[$day->toDateString()] ?? 0))->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return \Illuminate\Support\Collection<int, Carbon>
     */
    private function lastDays(int $count)
    {
        $start = now()->subDays($count - 1)->startOfDay();

        return collect(range(0, $count - 1))
            ->map(fn (int $offset) => $start->copy()->addDays($offset));
    }

    private function totalsByDay($days): array
    {
        $start = $days->first()->copy()->startOfDay();
        $end = $days->last()->copy()->endOfDay();

        return Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total), 0) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day')
            ->all();
    }
}
