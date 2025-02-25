<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;


class WfaVsOfficeChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_WfaVsOfficeChart');
    }
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'wfaVsOfficeChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'WFA vs Kehadiran di Kantor';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();

        $wfaCount = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('is_wfa', 1)
                    ->whereIn('status', ['present', 'late'])
                    ->count();

        $officeCount = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('is_wfa', 0)
                    ->whereIn('status', ['present', 'late'])
                    ->count();

        return [
            'chart' => [
                'type' => 'pie',
            ],
            'series' => [$wfaCount, $officeCount],
            'labels' => ['Work From Anywhere', 'Di Kantor'],
            'colors' => ['#9966FF', '#36A2EB'],
        ];
    }
}
