<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{
    protected $lateThreshold = 15; // Minutes threshold for late status

    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Get today's attendance with shift information
        $attendance = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        
        // Get monthly statistics
        $monthlyStats = $this->getMonthlyStatistics($user->id);
        
        // Get attendance history with shift information
        $history = Attendance::with(['user', 'shift'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(10);

        // Get available shifts for the user
        $shifts = Shift::all();

        return view('attendance.index', compact('attendance', 'history', 'monthlyStats', 'shifts'));
    }

    public function checkIn(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $user = auth()->user();
            $now = Carbon::now();
            
            // Get user's assigned shift for today
            $shift = $this->getUserShiftForDate($user->id, $now);
            
            if (!$shift) {
                return redirect()->back()->with('error', 'Tidak ada shift yang ditentukan untuk hari ini');
            }

            // Check if already checked in today
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($existingAttendance) {
                return redirect()->back()->with('error', 'Anda sudah melakukan check-in hari ini');
            }

            $status = $this->determineCheckInStatus($now, $shift);
            
            // Create new attendance record
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'date' => $now->toDateString(),
                'check_in' => $now->toTimeString(),
                'status' => $status,
                'notes' => $status === 'late' ? 
                    "Terlambat " . $this->calculateLateMinutes($now, $shift) . " menit" : null
            ]);

            $this->logAttendanceActivity($attendance, 'check_in');

            DB::commit();
            return redirect()->back()->with('success', 'Check-in berhasil pada ' . $now->format('H:i:s'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-in error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat check-in');
        }
    }

    public function checkOut(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $user = auth()->user();
            $now = Carbon::now();
            
            $attendance = Attendance::with('shift')
                ->where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->first();

            if (!$attendance) {
                return redirect()->back()->with('error', 'Tidak dapat melakukan check-out. Pastikan Anda sudah check-in hari ini');
            }

            // Calculate work duration
            $checkInTime = Carbon::parse($attendance->check_in);
            $workDuration = $now->diffInMinutes($checkInTime);
            
            // Check if checking out before shift end time
            $isEarlyCheckout = $this->isEarlyCheckout($now, $attendance->shift);
            $notes = $attendance->notes . "\nDurasi kerja: " . $this->formatWorkDuration($workDuration);
            
            if ($isEarlyCheckout) {
                $notes .= "\nCheck-out lebih awal dari jadwal shift";
            }

            // Update attendance record
            $attendance->update([
                'check_out' => $now->toTimeString(),
                'notes' => $notes
            ]);

            $this->logAttendanceActivity($attendance, 'check_out');

            DB::commit();
            return redirect()->back()->with('success', 'Check-out berhasil pada ' . $now->format('H:i:s'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-out error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat check-out');
        }
    }

    protected function getUserShiftForDate($userId, Carbon $date)
    {
        // This method should be implemented based on your shift assignment logic
        // For example, you might have a user_shifts table or a default shift assignment
        return Shift::first(); // Placeholder implementation
    }

    protected function determineCheckInStatus(Carbon $time, Shift $shift): string
    {
        $shiftStart = Carbon::parse($shift->start_time);
        $lateThreshold = $shiftStart->copy()->addMinutes($this->lateThreshold);

        if ($time->lt($lateThreshold)) {
            return 'present';
        }
        return 'late';
    }

    protected function calculateLateMinutes(Carbon $checkInTime, Shift $shift): int
    {
        $shiftStart = Carbon::parse($shift->start_time);
        return max(0, $checkInTime->diffInMinutes($shiftStart));
    }

    protected function isEarlyCheckout(Carbon $checkOutTime, Shift $shift): bool
    {
        $shiftEnd = Carbon::parse($shift->end_time);
        return $checkOutTime->lt($shiftEnd);
    }

    protected function formatWorkDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return sprintf("%d jam %d menit", $hours, $remainingMinutes);
    }

    protected function getMonthlyStatistics(int $userId): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $stats = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->select(
                DB::raw('COUNT(*) as total_attendance'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count'),
                DB::raw('SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent_count')
            )
            ->first();

        return [
            'total' => $stats->total_attendance,
            'present' => $stats->present_count,
            'late' => $stats->late_count,
            'absent' => $stats->absent_count,
            'attendance_rate' => $this->calculateAttendanceRate(
                $stats->total_attendance,
                Carbon::now()->daysInMonth
            )
        ];
    }

    protected function calculateAttendanceRate(int $totalAttendance, int $workingDays): float
    {
        return round(($totalAttendance / $workingDays) * 100, 2);
    }

    protected function logAttendanceActivity(Attendance $attendance, string $action): void
    {
        Log::info("Attendance {$action}", [
            'user_id' => $attendance->user_id,
            'shift_id' => $attendance->shift_id,
            'date' => $attendance->date,
            'time' => $action === 'check_in' ? $attendance->check_in : $attendance->check_out,
            'status' => $attendance->status
        ]);
    }

    public function report(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth());

        $attendances = Attendance::with('shift')
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        $statistics = $this->calculateAttendanceStatistics($attendances);

        return view('attendance.report', compact('attendances', 'statistics'));
    }

    protected function calculateAttendanceStatistics($attendances): array
    {
        $totalWorkMinutes = 0;
        $lateMinutes = 0;
        $earlyCheckouts = 0;

        foreach ($attendances as $attendance) {
            if ($attendance->check_in && $attendance->check_out && $attendance->shift) {
                $checkIn = Carbon::parse($attendance->check_in);
                $checkOut = Carbon::parse($attendance->check_out);
                $totalWorkMinutes += $checkIn->diffInMinutes($checkOut);

                // Calculate late minutes based on shift
                $scheduledStart = Carbon::parse($attendance->shift->start_time);
                if ($checkIn->gt($scheduledStart)) {
                    $lateMinutes += $checkIn->diffInMinutes($scheduledStart);
                }

                // Check for early checkouts based on shift
                $scheduledEnd = Carbon::parse($attendance->shift->end_time);
                if ($checkOut->lt($scheduledEnd)) {
                    $earlyCheckouts++;
                }
            }
        }

        return [
            'total_work_hours' => round($totalWorkMinutes / 60, 1),
            'average_work_hours' => $attendances->count() > 0 ? 
                round($totalWorkMinutes / 60 / $attendances->count(), 1) : 0,
            'total_late_minutes' => $lateMinutes,
            'early_checkouts' => $earlyCheckouts
        ];
    }
}