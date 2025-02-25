<?php

namespace App\Filament\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use App\Models\Attendance;
use App\Models\Office;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;


class FilterableAttendanceChart extends ApexChartWidget
{
    public static function canView(): bool
    {
        return Gate::allows('widget_FilterableAttendanceChart');
    }
    protected static ?string $chartId = 'filterableAttendanceChart';
    protected static ?string $heading = 'Analisis Kehadiran';
    protected static string $type = 'bar';

    public ?string $filter = 'this_month';
    public ?int $officeFilter = null;
    public ?string $statusFilter = null;

    public function getFilters(): ?array
    {
        return [
            'this_week' => 'Minggu Ini',
            'last_week' => 'Minggu Lalu',
            'this_month' => 'Bulan Ini',
            'last_month' => 'Bulan Lalu',
            'this_year' => 'Tahun Ini',
        ];
    }

    protected function getOptions(): array
    {
        $query = Attendance::query();
        
        if ($this->officeFilter) {
            $query->where('office_id', $this->officeFilter);
        }
        
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

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
            default:
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
        }

        $labels = [];
        $counts = [];

        if ($endDate->diffInDays($startDate) <= 31) {
            $period = Carbon::parse($startDate)->daysUntil($endDate);
            foreach ($period as $date) {
                $labels[] = $date->format('d M');
                $counts[] = $query->whereDate('date', $date->format('Y-m-d'))->count();
            }
        } else {
            $period = Carbon::parse($startDate)->monthsUntil($endDate);
            foreach ($period as $date) {
                $labels[] = $date->format('M Y');
                $counts[] = $query->whereYear('date', $date->year)
                    ->whereMonth('date', $date->month)
                    ->count();
            }
        }

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 350,
            ],
            'series' => [
                [
                    'name' => 'Jumlah Kehadiran',
                    'data' => $counts,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
            ],
            'colors' => ['#36A2EB'],
        ];
    }
}