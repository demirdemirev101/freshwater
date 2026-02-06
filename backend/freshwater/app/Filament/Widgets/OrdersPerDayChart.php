<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrdersPerDayChart extends ChartWidget
{
    protected ?string $heading = 'Поръчки за деня';
    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $days = $this->lastDays(14);
        $counts = $this->countsByDay($days);

        return [
            'labels' => $days->map(fn (Carbon $day) => $day->format('d M'))->all(),
            'datasets' => [
                [
                    'label' => 'Orders',
                    'data' => $days->map(fn (Carbon $day) => $counts[$day->toDateString()] ?? 0)->all(),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.2)',
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

    private function countsByDay($days): array
    {
        $start = $days->first()->copy()->startOfDay();
        $end = $days->last()->copy()->endOfDay();

        return Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'day')
            ->all();
    }
}
