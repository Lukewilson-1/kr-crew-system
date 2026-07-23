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

    public function test_plaintext_password_is_accepted_and_rehashed_when_checked(): void
    {
        $user = new User();
        $user->setRawAttributes([
            'username' => 'regression-user-' . uniqid('', true),
            'name' => 'Super Admin',
            'pw' => 'superadmin123',
        ]);

        $this->assertTrue($user->passwordMatches('superadmin123'));
        $this->assertTrue(Hash::check('superadmin123', $user->getAttribute('pw')));
        $this->assertNotSame('superadmin123', $user->getAttribute('pw'));
    }

    public function test_model_password_matches_accepts_plaintext_passwords(): void
    {
        $user = new User();
        $user->setRawAttributes([
            'username' => 'provider-regression-' . uniqid('', true),
            'name' => 'Super Admin',
            'pw' => 'superadmin123',
        ]);

        $this->assertTrue($user->passwordMatches('superadmin123'));
    }
}
