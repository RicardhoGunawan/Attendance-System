<?php
namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class AttendanceStatsChart extends ChartWidget
{
    protected static ?string $heading = 'Statistik Kehadiran Harian';
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    protected static string $chartType = 'bar';
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        // Ambil data 10 hari terakhir
        $days = 10;
        $labels = [];
        $presentCount = [];
        $lateCount = [];
        $absentCount = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M');
            
            // Hitung jumlah kehadiran berdasarkan status
            $present = Attendance::whereDate('date', $date->format('Y-m-d'))
                        ->where('status', 'present')
                        ->count();
            
            $late = Attendance::whereDate('date', $date->format('Y-m-d'))
                        ->where('status', 'late')
                        ->count();
            
            $absent = Attendance::whereDate('date', $date->format('Y-m-d'))
                        ->where('status', 'absent')
                        ->count();
            
            $presentCount[] = $present;
            $lateCount[] = $late;
            $absentCount[] = $absent;
        }
        
        // Balik array agar urutan dari kiri ke kanan adalah dari yang lama ke yang baru
        $labels = array_reverse($labels);
        $presentCount = array_reverse($presentCount);
        $lateCount = array_reverse($lateCount);
        $absentCount = array_reverse($absentCount);
        
        return [
            'datasets' => [
                [
                    'label' => 'Hadir',
                    'data' => $presentCount,
                    'backgroundColor' => '#36A2EB',
                ],
                [
                    'label' => 'Terlambat',
                    'data' => $lateCount,
                    'backgroundColor' => '#FFCE56',
                ],
                [
                    'label' => 'Tidak Hadir',
                    'data' => $absentCount,
                    'backgroundColor' => '#FF6384',
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
