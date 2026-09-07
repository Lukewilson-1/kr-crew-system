<?php

namespace App\Filament\Pages\Auth;

use App\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $data = $this->form->getState();

        $login = trim((string) ($data['username'] ?? ''));

        $password = (string) ($data['password'] ?? '');

        // Find user using either email OR username
        $user = User::query()
            ->where(function ($query) use ($login) {
                $query
                    ->where('email', $login)
                    ->orWhere('username', $login);
            })
            ->first();

        // Validate user and password
        if (
            ! $user instanceof User ||
            ! $user->passwordMatches($password)
        ) {
            $this->throwFailureValidationException();
        }

        // Log in using Filament's authentication guard
        Filament::auth()->login(
            $user,
            (bool) ($data['remember'] ?? false)
        );

        // Verify authenticated user
        $authenticatedUser = Filament::auth()->user();

        if (! $authenticatedUser) {
            $this->throwFailureValidationException();
        }

        // Check Filament panel access
        if (
            $authenticatedUser instanceof FilamentUser &&
            ! $authenticatedUser->canAccessPanel(
                Filament::getCurrentPanel()
            )
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        // Regenerate session after successful authentication
        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('username')
            ->label('Email or Username')
            ->placeholder('Enter your email or username')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes([
                'tabindex' => 1,
            ]);
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.username' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }
}