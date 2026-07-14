<?php

namespace Tests\Unit;

use App\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPasswordHandlingTest extends TestCase
{
    public function test_plaintext_password_is_hashed_when_set(): void
    {
        $user = new User();

        $user->pw = 'superadmin123';

        $this->assertNotEmpty($user->pw);
        $this->assertTrue(Hash::check('superadmin123', $user->pw));
        $this->assertNotSame('superadmin123', $user->pw);
    }

    public function test_hashed_password_is_preserved_when_set(): void
    {
        $user = new User();
        $hashed = Hash::make('superadmin123');

        $user->pw = $hashed;

        $this->assertSame($hashed, $user->pw);
    }
}
