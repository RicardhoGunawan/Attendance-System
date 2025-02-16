<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Office;
use App\Models\Schedule;
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

        // Get user's schedule
        $schedule = Schedule::where('user_id', $user->id)
            ->with(['office', 'shift'])
            ->first();

        if (!$schedule) {
            return view('attendance.no-schedule'); // Create this view to show "No schedule assigned" message
        }

        // Get today's attendance
        $attendance = Attendance::with('office')
            ->where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // Get monthly statistics
        $monthlyStats = $this->getMonthlyStatistics($user->id);

        // Get attendance history
        $history = Attendance::with(['user', 'office'])
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('attendance.index', compact('attendance', 'history', 'monthlyStats', 'schedule'));
    }

    public function checkIn(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $now = Carbon::now();

            // Get user's schedule
            $schedule = Schedule::where('user_id', $user->id)->first();

            if (!$schedule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No schedule assigned'
                ], 400);
            }

            // Validate location data
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric',
            ]);

            // Check if already checked in today
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have already checked in today'
                ], 400);
            }

            // Only validate distance if WFA is not active
            if (!$schedule->status) {
                if (!$this->isWithinOfficeRadius(
                    $request->latitude,
                    $request->longitude,
                    $schedule->office->latitude,
                    $schedule->office->longitude,
                    $schedule->office->radius
                )) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You are outside the office radius'
                    ], 400);
                }
            }

            // Determine check-in status based on shift time
            $status = $this->determineCheckInStatus($now, $schedule->shift);

            // Create attendance record
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'office_id' => $schedule->office_id,
                'date' => $now->toDateString(),
                'check_in' => $now->toTimeString(),
                'check_in_latitude' => $request->latitude,
                'check_in_longitude' => $request->longitude,
                'status' => $status,
                'is_wfa' => (bool) $schedule->status,  // Pastikan dikonversi ke boolean
                'notes' => $this->generateCheckInNotes($status, $now, $schedule)
            ]);

            $this->logAttendanceActivity($attendance, 'check_in');

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Check-in successful at ' . $now->format('H:i:s'),
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-in error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during check-in'
            ], 500);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();
            $now = Carbon::now();

            // Validate location data
            $request->validate([
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric'
            ]);

            $attendance = Attendance::with(['office'])
                ->where('user_id', $user->id)
                ->whereDate('date', $now->toDateString())
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->firstOrFail();

            // Only validate distance if this is not a WFA attendance
            if (!$attendance->is_wfa) {
                if (!$this->isWithinOfficeRadius(
                    $request->latitude,
                    $request->longitude,
                    $attendance->office->latitude,
                    $attendance->office->longitude,
                    $attendance->office->radius
                )) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You are outside the office radius'
                    ], 400);
                }
            }

            // Calculate work duration
            $checkInTime = Carbon::parse($attendance->check_in);
            $workDuration = $now->diffInMinutes($checkInTime);

            $notes = $this->generateCheckOutNotes($attendance, $workDuration);

            // Update attendance record
            $attendance->update([
                'check_out' => $now->toTimeString(),
                'check_out_latitude' => $request->latitude,
                'check_out_longitude' => $request->longitude,
                'notes' => $notes
            ]);

            $this->logAttendanceActivity($attendance, 'check_out');

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Check-out successful at ' . $now->format('H:i:s'),
                'data' => $attendance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Check-out error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred during check-out'
            ], 500);
        }
    }

    protected function generateCheckInNotes(string $status, Carbon $now, Schedule $schedule): string
    {
        $notes = [];
        
        if ($status === 'late') {
            $notes[] = "Late by " . $this->calculateLateMinutes($now, $schedule->shift) . " minutes";
        }
        
        if ($schedule->status) {
            $notes[] = "Working From Anywhere (WFA)";
        }
        
        return implode("\n", $notes);
    }

    protected function generateCheckOutNotes(Attendance $attendance, int $workDuration): string
    {
        $notes = [];
        
        if ($attendance->notes) {
            $notes[] = $attendance->notes;
        }
        
        $notes[] = "Work duration: " . $this->formatWorkDuration($workDuration);
        
        return implode("\n", $notes);
    }

    protected function isWithinOfficeRadius($userLat, $userLng, $officeLat, $officeLng, $radius)
    {
        // Convert coordinates to radians
        $userLat = deg2rad((float) $userLat);
        $userLng = deg2rad((float) $userLng);
        $officeLat = deg2rad((float) $officeLat);
        $officeLng = deg2rad((float) $officeLng);

        // Haversine formula
        $latDelta = $officeLat - $userLat;
        $lngDelta = $officeLng - $userLng;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos($userLat) * cos($officeLat) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Earth's radius in meters
        $distance = 6371000 * $c;

        return $distance <= (float) $radius;
    }

    protected function determineCheckInStatus(Carbon $time, Shift $shift): string
    {
        $workStart = Carbon::createFromTimeString($shift->start_time);
        $lateThreshold = $workStart->copy()->addMinutes($this->lateThreshold);
    
        if ($time->lt($lateThreshold)) {
            return 'present';
        }
        return 'late';
    }
    
    protected function calculateLateMinutes(Carbon $checkInTime, Shift $shift): int
    {
        $workStart = Carbon::createFromTimeString($shift->start_time);
        return max(0, $checkInTime->diffInMinutes($workStart));
    }

    protected function formatWorkDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        return sprintf("%d hours %d minutes", $hours, $remainingMinutes);
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
            'office_id' => $attendance->office_id,
            'date' => $attendance->date,
            'time' => $action === 'check_in' ? $attendance->check_in : $attendance->check_out,
            'status' => $attendance->status,
            'location' => [
                'latitude' => $action === 'check_in' ?
                    $attendance->check_in_latitude :
                    $attendance->check_out_latitude,
                'longitude' => $action === 'check_in' ?
                    $attendance->check_in_longitude :
                    $attendance->check_out_longitude,
            ]
        ]);
    }
}