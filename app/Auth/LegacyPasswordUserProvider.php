<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class LegacyPasswordUserProvider extends EloquentUserProvider
{
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';

        if ($user instanceof \App\User) {
            return $user->passwordMatches((string) $plain);
        }

        return parent::validateCredentials($user, $credentials);
    }
}
