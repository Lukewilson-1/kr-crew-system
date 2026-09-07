<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepotPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }

    public function view(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }

    public function update(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }

    public function delete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }

    public function forceDelete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_depots');
    }
}
