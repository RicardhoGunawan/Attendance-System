<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();
        
        $history = Attendance::where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('attendance.index', compact('attendance', 'history'));
    }

    public function checkIn(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();
        
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $now->toDateString(),
            ],
            [
                'check_in' => $now->toTimeString(),
                'status' => 'present',
            ]
        );

        return redirect()->back()->with('success', 'Check-in berhasil');
    }

    public function checkOut(Request $request)
    {
        $user = auth()->user();
        $now = Carbon::now();
        
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->first();

        if ($attendance) {
            $attendance->update([
                'check_out' => $now->toTimeString(),
            ]);
        }

        return redirect()->back()->with('success', 'Check-out berhasil');
    }
}
