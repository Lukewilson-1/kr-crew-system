<?php

namespace App\Filament\Pages;

use App\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Support\Carbon;
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
        'ends_at' => null,
    ];

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->is_active && $user->isGlobalAccess();
    }

    public static function getNavigationBadge(): ?string
    {
        return app()->maintenanceMode()->active() ? 'On' : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return app()->maintenanceMode()->active() ? 'danger' : null;
    }

    public static function getNavigationBadgeTooltip(): string | \Illuminate\Contracts\Support\Htmlable | null
    {
        return app()->maintenanceMode()->active() ? 'Maintenance mode is active' : null;
    }

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user !== null && $user->is_active && $user->isGlobalAccess(),
            403
        );

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Maintenance credentials')
                ->description('The hardcoded maintenance account is always reachable while the site is down. Your own HQ / superadmin credentials are also accepted.')
                ->schema([
                    TextInput::make('username')
                        ->label('Email or username')
                        ->required()
                        ->autocomplete('off'),
                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->autocomplete('new-password'),
                ])
                ->columns(2),
            Section::make('Schedule')
                ->description('Optionally set when maintenance is expected to end so staff see a live countdown. Leave blank for no schedule.')
                ->schema([
                    DateTimePicker::make('ends_at')
                        ->label('Scheduled to end')
                        ->native(false)
                        ->seconds(false)
                        ->displayFormat('d M Y H:i')
                        ->minDate(now()->addMinute())
                        ->placeholder('Leave blank for no schedule'),
                ]),
        ])->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->isMaintenanceActive()
                ? Action::make('deactivate')
                    ->label('Deactivate maintenance')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Take the site back online?')
                    ->modalDescription('Visitors will be able to use the system again immediately.')
                    ->action(fn () => $this->deactivateMaintenance())
                : Action::make('activate')
                    ->label('Activate maintenance')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Take the site offline?')
                    ->modalDescription('Visitors will see the maintenance page until you switch maintenance off again.')
                    ->action(fn () => $this->activateMaintenance()),
        ];
    }

    public function isMaintenanceActive(): bool
    {
        return app()->maintenanceMode()->active();
    }

    public function scheduledEndsAt(): ?string
    {
        if (! $this->isMaintenanceActive()) {
            return null;
        }

        try {
            $data = app()->maintenanceMode()->data();

            return filled($data['ends_at'] ?? null)
                ? Carbon::parse($data['ends_at'])->toIso8601String()
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function hasPassedScheduledEnd(): bool
    {
        $endsAt = $this->scheduledEndsAt();

        return $endsAt !== null && Carbon::parse($endsAt)->isPast();
    }

    protected function credentialsValid(): bool
    {
        $username = trim((string) ($this->data['username'] ?? ''));
        $password = (string) ($this->data['password'] ?? '');

        $identityMatches = $username === config('maintenance.username')
            || $username === config('maintenance.login');

        if ($identityMatches && hash_equals((string) config('maintenance.password'), $password)) {
            return true;
        }

        $user = User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($username) {
                $query->where('username', $username)
                    ->orWhere('email', $username);
            })
            ->first();

        return $user !== null && $user->isGlobalAccess() && $user->passwordMatches($password);
    }

    protected function maintenanceSecret(): string
    {
        return hash('sha256', (string) config('maintenance.password'));
    }

    public function activateMaintenance(): void
    {
        if (! $this->credentialsValid()) {
            Notification::make()
                ->title('Invalid credentials.')
                ->body('Use the maintenance account or your own HQ / superadmin credentials.')
                ->danger()
                ->send();

            return;
        }

        $payload = [
            'secret' => $this->maintenanceSecret(),
            'status' => 503,
        ];

        $endsAt = $this->data['ends_at'] ?? null;
        if (filled($endsAt)) {
            $payload['ends_at'] = Carbon::parse($endsAt)->toIso8601String();
        }

        app()->maintenanceMode()->activate($payload);

        Cookie::queue(MaintenanceModeBypassCookie::create($this->maintenanceSecret()));

        Notification::make()
            ->title('Maintenance mode activated.')
            ->body(filled($endsAt)
                ? 'The site is offline until '.Carbon::parse($endsAt)->format('d M Y, H:i').'.'
                : 'The site is now offline.'
            )
            ->success()
            ->send();

        $this->form->fill();
    }

    public function deactivateMaintenance(): void
    {
        if (! $this->credentialsValid()) {
            Notification::make()
                ->title('Invalid credentials.')
                ->body('Use the maintenance account or your own HQ / superadmin credentials.')
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

        $this->form->fill();
    }
}
