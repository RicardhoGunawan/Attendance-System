<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Office;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class AttendanceByOfficeChart extends ChartWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    
    protected static ?string $heading = 'Kehadiran per Kantor';
    protected static string $chartType = 'pie';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 2;
    
    protected function getData(): array
    {
        // Ambil semua kantor
        $offices = Office::all();
        $labels = [];
        $data = [];
        $colors = ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#C9CBCF'];
        
        // Ambil data bulan ini
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();
        
        foreach ($offices as $index => $office) {
            $labels[] = $office->name;
            
            // Hitung total kehadiran untuk kantor ini
            $count = Attendance::where('office_id', $office->id)
                        ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->where('status', 'present')
                        ->count();
                        
            $data[] = $count;
        }
        
        // Pastikan ada warna yang cukup untuk semua kantor
        while (count($colors) < count($offices)) {
            $colors = array_merge($colors, $colors);
        }
        
        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($offices)),
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