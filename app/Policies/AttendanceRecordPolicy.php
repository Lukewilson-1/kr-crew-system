<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendanceRecordPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }

    public function view(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }

    public function update(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }

    public function delete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }

    public function forceDelete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_running_rooms');
    }
}
