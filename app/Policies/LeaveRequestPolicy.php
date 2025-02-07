<?php
namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Semua user bisa melihat list
    }

    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        // Admin dapat melihat semua request, employee hanya bisa melihat request mereka sendiri
        return $user->hasRole('admin') || $leaveRequest->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        // Semua user bisa membuat leave request
        return true;
    }

    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        // Admin bisa mengedit semua leave request
        if ($user->hasRole('admin')) {
            return true;
        }

        // Employee hanya bisa update jika status masih pending
        return $leaveRequest->user_id === $user->id && $leaveRequest->status === 'pending';
    }

    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        // Hanya admin yang bisa menghapus leave request
        return $user->hasRole('admin');
    }
}
