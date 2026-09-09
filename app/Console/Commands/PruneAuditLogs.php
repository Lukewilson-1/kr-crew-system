<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune
        {--retention=365 : Minimum retention period in days (default 365 / 12 months)}';

    protected $description = 'Delete audit log entries older than the retention period.';

    public function handle(): int
    {
        $retention = max(1, (int) $this->option('retention'));
        $cutoff = now()->subDays($retention);

        $deleted = DB::table('audit_logs')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Audit logs older than {$retention} days pruned. Removed {$deleted} row(s).");

        return self::SUCCESS;
    }
}