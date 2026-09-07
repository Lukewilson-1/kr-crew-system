<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function view(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function update(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function delete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_users');
    }

    public function forceDelete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_users');
    }
}
