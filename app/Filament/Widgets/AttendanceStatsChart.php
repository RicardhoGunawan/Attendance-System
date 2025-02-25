<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Attendance;
use Illuminate\Support\Facades\Gate;


class AttendanceStatsChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_AttendanceStatsChart');
    }
    protected static ?string $chartId = 'attendanceStatsChart';
    protected static ?string $heading = 'Statistik Kehadiran Pegawai';

    protected function getOptions(): array
    {
        $present = Attendance::where('status', 'Present')->count();
        $late = Attendance::where('status', 'Late')->count();
        $absent = Attendance::where('status', 'Absent')->count();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
            ],
            'series' => [
                [
                    'name' => 'Jumlah Pegawai',
                    'data' => [$present, $late, $absent], // Data kehadiran
                ],
            ],
            'xaxis' => [
                'categories' => ['Hadir', 'Terlambat', 'Absen'], // Label kategori
            ],
            'colors' => ['#28a745', '#ffc107', '#dc3545'], // Warna untuk setiap kategori
            'plotOptions' => [
                'bar' => [
                    'horizontal' => false,
                    'columnWidth' => '45%',
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'legend' => [
                'position' => 'top',
            ],
        ];
    }
}
