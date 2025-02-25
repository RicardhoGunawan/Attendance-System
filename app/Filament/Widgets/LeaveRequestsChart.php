<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class LeaveRequestsChart extends ChartWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    protected static ?string $heading = 'Statistik Permintaan Cuti';
    protected static string $chartType = 'doughnut';
    protected static ?string $maxHeight = '300px';
    protected int|string|array $columnSpan = 2;

    protected function getData(): array
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
            'datasets' => [
                [
                    'data' => [$pending, $approved, $rejected],
                    'backgroundColor' => ['#FFCE56', '#36A2EB', '#FF6384'],
                ],
            ],
            'labels' => ['Pending', 'Disetujui', 'Ditolak'],
        ];
    }
    protected function getType(): string
    {
        return static::$chartType;
    }
}