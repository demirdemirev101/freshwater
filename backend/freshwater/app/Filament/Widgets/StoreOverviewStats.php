<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Contact;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreOverviewStats extends BaseWidget
{
    protected ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $ordersThisMonth = Order::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $revenueThisMonth = (float) Order::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        $pendingReview = Order::query()
            ->where('status', OrderStatus::PENDING_REVIEW->value)
            ->count();

        $returnFlowCount = Order::query()
            ->whereIn('status', [
                OrderStatus::RETURN_REQUESTED->value,
                OrderStatus::RETURNED->value,
            ])
            ->count();

        $revenueLastYear = (float) Order::query()
            ->whereBetween('created_at', [now()->subMonths(11)->startOfMonth(), now()->endOfMonth()])
            ->sum('total');

        return [
            Stat::make('Поръчки този месец', (string) $ordersThisMonth)
                ->description('Всички нови поръчки за текущия месец')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('warning'),

            Stat::make('Приход този месец', number_format($revenueThisMonth, 2).' EUR')
                ->description('Сума по всички поръчки за текущия месец')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Чакат преглед', (string) $pendingReview)
                ->description('Поръчки в статус за потвърждение')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Връщания', (string) $returnFlowCount)
                ->description('Заявени или приключени връщания')
                ->descriptionIcon('heroicon-m-arrow-uturn-left')
                ->color('danger'),

            Stat::make('Приход за 12 месеца', number_format($revenueLastYear, 2).' EUR')
                ->description('Общ приход за последната година')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('primary'),

            Stat::make('Съобщения', (string) Contact::count())
                ->description('Всички получени контактни съобщения')
                ->descriptionIcon('heroicon-m-envelope')
                ->color('gray'),
        ];
    }
}
