<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RotateBreakGlassCredentials extends Command
{
    protected $signature = 'break-glass:rotate
        {--length=40 : Length of the generated passphrase}
        {--update-env : Also write the new passphrase into the .env file}
        {--no-audit : Skip writing a rotation audit log entry}';

    protected $description = 'Generate a new break-glass access passphrase and record the rotation.';

    public function handle(): int
    {
        $length = max(24, (int) $this->option('length'));
        $newPassword = Str::random($length);

        $username = (string) config('breakglass.username');

        if (! $this->option('no-audit')) {
            AuditLogger::record(
                event: 'break_glass_credentials_rotated',
                entityType: 'break_glass_access',
                entityId: $username ?: 'unknown',
                after: [
                    'length' => $length,
                    'updated_env' => (bool) $this->option('update-env'),
                ],
            );
        }

        if ($this->option('update-env')) {
            $this->updateEnvFile($newPassword);
            $this->info("Break-glass passphrase updated in .env ({$length} chars).");
            $this->warn('Store the passphrase in the enterprise vault and sealed envelope immediately.');

            return self::SUCCESS;
        }

        $this->info("Break-glass username : {$username}");
        $this->info("New passphrase (length {$length}):");
        $this->info('    '.$newPassword);
        $this->warn('');
        $this->warn('Set BREAK_GLASS_PASSWORD in the production .env and store this value securely.');
        $this->warn('Rotate again using: php artisan break-glass:rotate --update-env');

        return self::SUCCESS;
    }

    private function updateEnvFile(string $newPassword): void
    {
        $path = base_path('.env');
        $contents = (string) file_get_contents($path);

        $line = 'BREAK_GLASS_PASSWORD='.$newPassword;

        if (preg_match('/^BREAK_GLASS_PASSWORD=.*$/m', $contents)) {
            $contents = (string) preg_replace(
                '/^BREAK_GLASS_PASSWORD=.*$/m',
                $line,
                $contents
            );
        } else {
            $contents .= PHP_EOL.$line.PHP_EOL;
        }

        file_put_contents($path, $contents);
    }
}