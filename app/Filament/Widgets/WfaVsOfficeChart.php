<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class WfaVsOfficeChart extends ChartWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    protected static ?string $heading = 'WFA vs Kehadiran di Kantor';
    protected static string $chartType = 'pie';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 2;
    
    protected function getData(): array
    {
        // Ambil data dari bulan ini
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();
        
        // Hitung kehadiran WFA
        $wfaCount = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('is_wfa', 1)
                    ->whereIn('status', ['present', 'late'])
                    ->count();
        
        // Hitung kehadiran di kantor
        $officeCount = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('is_wfa', 0)
                    ->whereIn('status', ['present', 'late'])
                    ->count();
        
        return [
            'datasets' => [
                [
                    'data' => [$wfaCount, $officeCount],
                    'backgroundColor' => ['#9966FF', '#36A2EB'],
                ],
            ],
            'labels' => ['Work From Anywhere', 'Di Kantor'],
        ];
    }
    protected function getType(): string
    {
        return static::$chartType;
    }
}