<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;


class CheckInTimesChart extends ChartWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    protected static ?string $heading = 'Distribusi Waktu Check-in';
    protected static string $chartType = 'bar';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 2;
    
    protected function getData(): array
    {
        // Ambil data dari bulan ini
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now();
        
        // Buat interval untuk waktu check-in (per jam)
        $timeSlots = [
            '06:00-07:00', '07:00-08:00', '08:00-09:00', '09:00-10:00', 
            '10:00-11:00', '11:00-12:00', '12:00-13:00', '13:00-14:00',
            '14:00-15:00', '15:00-16:00', '16:00-17:00', '17:00-18:00'
        ];
        
        $data = [];
        
        // Query untuk menghitung distribusi waktu check-in
        foreach ($timeSlots as $index => $slot) {
            list($start, $end) = explode('-', $slot);
            
            $count = Attendance::whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->whereNotNull('check_in')
                        ->whereRaw("TIME(check_in) >= ?", [$start])
                        ->whereRaw("TIME(check_in) < ?", [$end])
                        ->count();
            
            $data[] = $count;
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Check-in',
                    'data' => $data,
                    'backgroundColor' => '#4BC0C0',
                ],
            ],
            'labels' => $timeSlots,
        ];
    }
    protected function getType(): string
    {
        return static::$chartType;
    }
}