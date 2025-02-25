<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;


class AttendanceTrendsChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_AttendanceTrendsChart');
    }
    protected static ?string $chartId = 'attendanceTrendsChart';
    protected static ?string $heading = 'Tren Kehadiran Bulanan';

    protected function getOptions(): array
    {
        // Ambil data 6 bulan terakhir
        $months = 6;
        $labels = [];
        $presentData = [];
        $lateData = [];
        $wfaData = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');

            // Hitung jumlah kehadiran berdasarkan status
            $present = Attendance::whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->where('status', 'present')
                        ->count();

            $late = Attendance::whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->where('status', 'late')
                        ->count();

            $wfa = Attendance::whereYear('date', $date->year)
                        ->whereMonth('date', $date->month)
                        ->where('is_wfa', 1)
                        ->count();

            $presentData[] = $present;
            $lateData[] = $late;
            $wfaData[] = $wfa;
        }

        return [
            'chart' => [
                'type' => 'line',
                'height' => 350,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Hadir',
                    'data' => $presentData,
                    'color' => '#36A2EB',
                ],
                [
                    'name' => 'Terlambat',
                    'data' => $lateData,
                    'color' => '#FFCE56',
                ],
                [
                    'name' => 'WFA',
                    'data' => $wfaData,
                    'color' => '#4BC0C0',
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
                'labels' => ['rotate' => -45],
            ],
            'stroke' => [
                'curve' => 'smooth',
            ],
            'markers' => [
                'size' => 5,
            ],
            'tooltip' => [
                'enabled' => true,
            ],
        ];
    }
}
