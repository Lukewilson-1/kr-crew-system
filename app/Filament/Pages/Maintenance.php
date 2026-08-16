<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Support\Facades\Cookie;
use UnitEnum;
use BackedEnum;

class Maintenance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static BackedEnum | string | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Maintenance';

    protected static UnitEnum | string | null $navigationGroup = 'Site Settings';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.maintenance';

    public array $data = [
        'username' => '',
        'password' => '',
    ];

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->is_active && (
            $user->is_super_admin ||
            $user->is_hq ||
            $user->role_code === 'hq_admin' ||
            $user->depot_code === 'HQ'
        );
    }

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user &&
            $user->is_active &&
            (
                $user->is_super_admin ||
                $user->is_hq ||
                $user->role_code === 'hq_admin' ||
                $user->depot_code === 'HQ'
            ),
            403
        );

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('username')
                ->label('Email or username')
                ->placeholder('Maintenance email or username')
                ->required()
                ->autocomplete('off'),
            TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->revealable()
                ->autocomplete('new-password'),
        ])->statePath('data');
    }

    public function isMaintenanceActive(): bool
    {
        return app()->maintenanceMode()->active();
    }

    protected function credentialsValid(): bool
    {
        $username = trim((string) ($this->data['username'] ?? ''));
        $password = (string) ($this->data['password'] ?? '');

        $identityMatches = $username === config('maintenance.username') || $username === config('maintenance.login');

        return $identityMatches && hash_equals((string) config('maintenance.password'), $password);
    }

    protected function maintenanceSecret(): string
    {
        return hash('sha256', (string) config('maintenance.password'));
    }

    public function activateMaintenance(): void
    {
        if (! $this->credentialsValid()) {
            Notification::make()
                ->title('Invalid maintenance credentials.')
                ->body('The username and password must match the hardcoded maintenance account.')
                ->danger()
                ->send();

            return;
        }

        app()->maintenanceMode()->activate([
            'secret' => $this->maintenanceSecret(),
            'status' => 503,
        ]);

        Cookie::queue(MaintenanceModeBypassCookie::create($this->maintenanceSecret()));

        Notification::make()
            ->title('Maintenance mode activated.')
            ->body('The site is now offline. Use the maintenance login to access it while it is down.')
            ->success()
            ->send();
    }

    public function deactivateMaintenance(): void
    {
        if (! $this->credentialsValid()) {
            Notification::make()
                ->title('Invalid maintenance credentials.')
                ->body('The username and password must match the hardcoded maintenance account.')
                ->danger()
                ->send();

            return;
        }

        app()->maintenanceMode()->deactivate();

        Notification::make()
            ->title('Maintenance mode deactivated.')
            ->body('The site is back online.')
            ->success()
            ->send();
    }
}