<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Office;
use App\Models\Attendance;
use Illuminate\Support\Facades\Gate;


class AttendanceByOfficeChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_AttendanceByOfficeChart');
    }
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'attendanceByOfficeChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'AttendanceByOfficeChart';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $offices = Office::withCount('attendances')->get();
        $labels = $offices->pluck('name')->toArray();
        $data = $offices->pluck('attendances_count')->toArray();

        return [
            'chart' => ['type' => 'pie', 'height' => 350],
            'series' => $data, // Data array langsung diberikan tanpa nama
            'labels' => $labels, // Labels untuk masing-masing kantor
            'legend' => [
                'position' => 'bottom', // Posisi legenda
            ],
        ];
    }
}
