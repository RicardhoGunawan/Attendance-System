<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;


class CheckInTimesChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_CheckInTimesChart');
    }
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'checkInTimesChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Distribusi Waktu Check-in';

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
        
        $timeSlots = [
            '06:00-07:00', '07:00-08:00', '08:00-09:00', '09:00-10:00', 
            '10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00',
            '14:00-15:00', '15:00-16:00', '16:00-17:00', '17:00-18:00'
        ];
        
        $data = [];
        
        foreach ($timeSlots as $slot) {
            list($start, $end) = explode('-', $slot);
            
            $count = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->whereNotNull('check_in')
                        ->whereRaw("TIME(check_in) >= ?", [$start])
                        ->whereRaw("TIME(check_in) < ?", [$end])
                        ->count();
            
            $data[] = $count;
        }
        
        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
            ],
            'series' => [
                [
                    'name' => 'Jumlah Check-in',
                    'data' => $data,
                ]
            ],
            'xaxis' => [
                'categories' => $timeSlots,
            ],
            'colors' => ['#4BC0C0'],
        ];
    }
}
