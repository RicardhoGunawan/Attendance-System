<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class AttendanceTrendsChart extends ChartWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    protected static ?string $heading = 'Tren Kehadiran Bulanan';
    protected static string $chartType = 'line';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 2;
    
    protected function getData(): array
    {
        // Ambil data 6 bulan terakhir
        $months = 6;
        $labels = [];
        $presentData = [];
        $lateData = [];
        $wfaData = [];
        
        for ($i = 0; $i < $months; $i++) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');
            
            // Hitung kehadiran berdasarkan status
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
        
        // Balik array agar urutan dari kiri ke kanan adalah dari yang lama ke yang baru
        $labels = array_reverse($labels);
        $presentData = array_reverse($presentData);
        $lateData = array_reverse($lateData);
        $wfaData = array_reverse($wfaData);
        
        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $presentData,
                    'fill' => false,
                    'borderColor' => '#36A2EB',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $lateData,
                    'fill' => false,
                    'borderColor' => '#FFCE56',
                    'tension' => 0.1,
                ],
                [
                    'label' => 'WFA',
                    'data' => $wfaData,
                    'fill' => false,
                    'borderColor' => '#4BC0C0',
                    'tension' => 0.1,
                ],
            ],
            'labels' => $labels,
        ];
    }
    protected function getType(): string
    {
        return static::$chartType;
    }
}