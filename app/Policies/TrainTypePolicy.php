<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TrainTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }

    public function view(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }

    public function update(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }

    public function delete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }

    public function forceDelete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_rosters');
    }
}
