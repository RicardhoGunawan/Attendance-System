<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Office;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;


class FilterableAttendanceChart extends ChartWidget
{
    public static function canView(): bool
    {
        return Auth::user()->hasRole('admin'); // Hanya admin yang bisa melihat
    }
    protected static ?string $heading = 'Analisis Kehadiran';
    protected static string $chartType = 'bar';
    protected static ?string $maxHeight = '300px';
    
    // Filter state
    public ?string $filter = null; // NON-STATIC, sesuai dengan ChartWidget Filament
    public ?string $officeFilter = null; // Tambahkan properti ini
    public ?string $statusFilter = null; // Tambahkan properti ini
    
    // Widget ini dapat difilter
    protected function getFilters(): ?array
    {
        return [
            'this_week' => 'Minggu Ini',
            'last_week' => 'Minggu Lalu',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu',
            'this_year' => 'Tahun Ini',
        ];
    }
    
    public function getFormSchema(): array
    {
        return [
            Select::make('office')
                ->label('Kantor')
                ->options(Office::pluck('name', 'id')->toArray())
                ->placeholder('Semua Kantor')
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->officeFilter = $state;
                    $this->updateChartData();
                }),
            
            Select::make('status')
                ->label('Status')
                ->options([
                    'present' => 'Hadir',
                    'late' => 'Terlambat',
                    'absent' => 'Tidak Hadir',
                ])
                ->placeholder('Semua Status')
                ->live()
                ->afterStateUpdated(function ($state) {
                    $this->statusFilter = $state;
                    $this->updateChartData();
                }),
            
            DatePicker::make('date_range')
                ->label('Rentang Tanggal Kustom')
                ->range()
                ->live()
                ->afterStateUpdated(function ($state) {
                    if ($state) {
                        $this->filter = 'custom';
                        $this->updateChartData();
                    }
                }),
        ];
    }
    
    protected function getData(): array
    {
        $query = Attendance::query();
        
        // Terapkan filter kantor jika dipilih
        if ($this->officeFilter) {
            $query->where('office_id', $this->officeFilter);
        }
        
        // Terapkan filter status jika dipilih
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }
        
        // Terapkan filter waktu
        switch ($this->filter) {
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
                break;
            case 'custom':
                // Gunakan rentang tanggal kustom dari form
                $dateRange = $this->filterFormData['date_range'] ?? null;
                if ($dateRange && isset($dateRange['from']) && isset($dateRange['to'])) {
                    $startDate = Carbon::parse($dateRange['from']);
                    $endDate = Carbon::parse($dateRange['to']);
                } else {
                    $startDate = Carbon::now()->startOfMonth();
                    $endDate = Carbon::now()->endOfMonth();
                }
                break;
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }
        
        // Format data untuk chart
        $labels = [];
        $counts = [];
        
        // Untuk rentang waktu mingguan atau bulanan yang pendek, tampilkan per hari
        if ($endDate->diffInDays($startDate) <= 31) {
            $period = Carbon::parse($startDate)->daysUntil($endDate);
            
            foreach ($period as $date) {
                $labels[] = $date->format('d M');
                $dateString = $date->format('Y-m-d');
                
                $dailyQuery = clone $query;
                $count = $dailyQuery->whereDate('date', $dateString)->count();
                $counts[] = $count;
            }
        } 
        // Untuk rentang waktu tahunan, tampilkan per bulan
        else {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);
            
            foreach ($period as $date) {
                $labels[] = $date->format('M Y');
                
                $monthlyQuery = clone $query;
                $count = $monthlyQuery
                    ->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->count();
                $counts[] = $count;
            }
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Kehadiran',
                    'data' => $counts,
                    'backgroundColor' => '#36A2EB',
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