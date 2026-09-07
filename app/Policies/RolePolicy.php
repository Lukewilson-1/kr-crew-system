<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }

    public function view(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }

    public function update(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }

    public function delete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }

    public function forceDelete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_roles');
    }
}
