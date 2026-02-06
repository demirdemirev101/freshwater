<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CancelledReturnedPerDayChart extends ChartWidget
{
    protected ?string $heading = 'Върнати и анулирани поръчки през последните 14 дни';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $days = $this->lastDays(14);
        $counts = $this->countsByDay($days);

        return [
            'labels' => $days->map(fn (Carbon $day) => $day->format('d M'))->all(),
            'datasets' => [
                [
                    'label' => 'Cancelled',
                    'data' => $days->map(fn (Carbon $day) => $counts[$day->toDateString()]['cancelled'] ?? 0)->all(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => 'Returned',
                    'data' => $days->map(fn (Carbon $day) => $counts[$day->toDateString()]['returned'] ?? 0)->all(),
                    'borderColor' => '#6b7280',
                    'backgroundColor' => 'rgba(107, 114, 128, 0.2)',
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

        $rows = Order::query()
            ->whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['cancelled', 'returned'])
            ->selectRaw('DATE(created_at) as day, status, COUNT(*) as total')
            ->groupBy(DB::raw('DATE(created_at)'), 'status')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $day = $row->day;
            $result[$day] ??= ['cancelled' => 0, 'returned' => 0];
            $result[$day][$row->status] = (int) $row->total;
        }

        return $result;
    }
}
