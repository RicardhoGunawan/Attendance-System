<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Semua user bisa melihat list
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin') || $attendance->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('admin');
    }
}
