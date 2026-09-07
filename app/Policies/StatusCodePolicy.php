<?php

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatusCodePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }

    public function view(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }

    public function update(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }

    public function delete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }

    public function forceDelete(User $user, $record): bool
    {
        return $user->hasPermissionTo('manage_crew');
    }
}
