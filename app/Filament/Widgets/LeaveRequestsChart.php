<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;


class LeaveRequestsChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_LeaveRequestsChart');
    }
    protected static ?string $chartId = 'leaveRequestsChart';
    protected static ?string $heading = 'Statistik Permintaan Cuti';

    protected function getOptions(): array
    {
        // Ambil data status cuti bulan ini
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $pending = LeaveRequest::whereBetween('start_date', [$startDate, $endDate])
                    ->where('status', 'pending')
                    ->count();

        $approved = LeaveRequest::whereBetween('start_date', [$startDate, $endDate])
                    ->where('status', 'approved')
                    ->count();

        $rejected = LeaveRequest::whereBetween('start_date', [$startDate, $endDate])
                    ->where('status', 'rejected')
                    ->count();

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 350,
            ],
            'series' => [$pending, $approved, $rejected],
            'labels' => ['Pending', 'Disetujui', 'Ditolak'],
            'colors' => ['#FFCE56', '#36A2EB', '#FF6384'],
            'legend' => [
                'position' => 'bottom',
            ],
        ];
    }
}
