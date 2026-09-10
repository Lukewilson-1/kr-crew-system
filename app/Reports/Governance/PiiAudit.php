<?php

namespace App\Reports\Governance;

use App\Models\AuditLog;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;

class PiiAudit extends BaseReport
{
    private const PII_MODELS = [
        'App\User' => 'User account',
        'App\CrewMember' => 'Crew member',
        'App\Models\CrewRecord' => 'Crew record',
    ];

    private const EVENT_LABELS = [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
        'restored' => 'Restored',
    ];

    public function slug(): string
    {
        return 'pii-audit';
    }

    public function title(): string
    {
        return 'Personnel Data Audit';
    }

    public function icon(): string
    {
        return '🛡️';
    }

    public function category(): string
    {
        return 'Governance & Security';
    }

    public function description(): string
    {
        return 'Who created or modified sensitive personnel records (users, crew members, crew records) and when.';
    }

    public function allows(User $user): bool
    {
        return $user->is_active && $user->isGlobalAccess();
    }

    public function filters(): array
    {
        return [
            ['key' => 'from', 'label' => 'From', 'type' => 'date', 'default' => date('Y-m-d', strtotime('-90 days'))],
            ['key' => 'to', 'label' => 'To', 'type' => 'date', 'default' => date('Y-m-d')],
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $from = $filterValues['from'] ?? date('Y-m-d', strtotime('-90 days'));
        $to = $filterValues['to'] ?? date('Y-m-d');
        if ($to < $from) {
            $to = $from;
        }

        $logs = AuditLog::query()
            ->whereIn('entity_type', array_keys(self::PII_MODELS))
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('created_at')
            ->limit(750)
            ->get(['id', 'actor_username', 'actor_ip', 'event', 'entity_type', 'entity_id', 'created_at']);

        $rows = [];
        $counts = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'restored' => 0];

        foreach ($logs as $log) {
            $counts[$log->event] = ($counts[$log->event] ?? 0) + 1;
            $rows[] = [
                'time' => $log->created_at?->format('Y-m-d H:i:s') ?? (string) $log->created_at,
                'actor' => $log->actor_username,
                'event' => self::EVENT_LABELS[$log->event] ?? $log->event,
                'model' => self::PII_MODELS[$log->entity_type] ?? str_replace('App\\', '', $log->entity_type),
                'id' => $log->entity_id,
                'ip' => $log->actor_ip,
            ];
        }

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No personnel record changes in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Personnel record changes', 'note' => 'Most recent 750 events across user accounts, crew members and crew records.', 'columns' => ['Time', 'Actor', 'Event', 'Model', 'Record', 'IP'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Changes', 'value' => number_format($logs->count())],
                ['label' => 'Created', 'value' => number_format($counts['created'])],
                ['label' => 'Updated', 'value' => number_format($counts['updated'])],
                ['label' => 'Deleted', 'value' => number_format($counts['deleted']), 'sub' => 'Restored '.number_format($counts['restored'])],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}