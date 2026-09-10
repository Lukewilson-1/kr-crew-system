<?php

namespace App\Reports;

use App\Reports\Contracts\SystemReport;
use App\User;

class ReportRegistry
{
    private const REPORTS = [
        \App\Reports\Operational\DepotScorecard::class,
        \App\Reports\Operational\RoomOccupancy::class,
        \App\Reports\Operational\RestCompliance::class,
        \App\Reports\Operational\DutyTurnaround::class,
        \App\Reports\Workforce\DutyCoverage::class,
        \App\Reports\Workforce\RestReadiness::class,
        \App\Reports\Workforce\AbsenceHeatmap::class,
        \App\Reports\Matters\MatterAging::class,
        \App\Reports\Matters\RecurringIssues::class,
        \App\Reports\Matters\ResolutionSla::class,
        \App\Reports\Governance\SecureAccess::class,
        \App\Reports\Governance\PiiAudit::class,
        \App\Reports\Governance\LoginSecurity::class,
    ];

    /** @return SystemReport[] */
    public static function all(): array
    {
        return array_map(fn (string $class) => app($class), self::REPORTS);
    }

    /** @return SystemReport[] */
    public static function for(User $user): array
    {
        return array_values(array_filter(
            self::all(),
            fn (SystemReport $report) => $report->allows($user)
        ));
    }

    public static function find(string $slug): ?SystemReport
    {
        foreach (self::all() as $report) {
            if ($report->slug() === $slug) {
                return $report;
            }
        }

        return null;
    }
}