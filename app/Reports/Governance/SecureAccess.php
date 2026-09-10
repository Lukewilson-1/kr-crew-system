<?php

namespace App\Reports\Governance;

use App\Models\AuditLog;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;

class SecureAccess extends BaseReport
{
    private const EVENT_LABELS = [
        'break_glass_login' => 'Break-glass login',
        'break_glass_login_failed' => 'Break-glass login failed',
        'break_glass_logout' => 'Break-glass logout',
        'break_glass_expired' => 'Break-glass session expired',
        'break_glass_credentials_rotated' => 'Break-glass credentials rotated',
    ];

    public function slug(): string
    {
        return 'secure-access';
    }

    public function title(): string
    {
        return 'Secure Access (Break-glass)';
    }

    public function icon(): string
    {
        return '🔐';
    }

    public function category(): string
    {
        return 'Governance & Security';
    }

    public function description(): string
    {
        return 'Every elevated break-glass session: who accessed an account, from where, why, and how the session ended.';
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
            ->where('entity_type', 'break_glass_access')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('created_at')
            ->limit(500)
            ->get(['id', 'actor_username', 'actor_ip', 'actor_user_agent', 'event', 'entity_id', 'metadata', 'created_at']);

        $rows = [];
        foreach ($logs as $log) {
            $target = null;
            if (isset($log->metadata['target_user']) && is_string($log->metadata['target_user'])) {
                $target = $log->metadata['target_user'];
            } else {
                $target = $log->entity_id;
            }

            $rows[] = [
                'time' => $log->created_at?->format('Y-m-d H:i:s') ?? (string) $log->created_at,
                'actor' => $log->actor_username,
                'event' => self::EVENT_LABELS[$log->event] ?? $log->event,
                'target' => $target,
                'ip' => $log->actor_ip,
                'detail' => $this->sessionDetail($log),
            ];
        }

        $failed = $logs->where('event', 'break_glass_login_failed')->count();
        $granular = [
            'expired' => $logs->where('event', 'break_glass_expired')->count(),
            'rotated' => $logs->where('event', 'break_glass_credentials_rotated')->count(),
        ];
        $distinctActors = $logs->pluck('actor_username')->unique()->count();

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No break-glass activity in the selected window.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Break-glass activity', 'note' => 'Most recent 500 events. Sessions give a user temporary elevated access to another account.', 'columns' => ['Time', 'Actor', 'Event', 'Target', 'IP', 'Detail'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Events', 'value' => number_format($logs->count())],
                ['label' => 'Distinct actors', 'value' => number_format($distinctActors)],
                ['label' => 'Failed logins', 'value' => number_format($failed)],
                ['label' => 'Expired / rotated', 'value' => number_format($granular['expired'] + $granular['rotated']), 'sub' => 'Expired '.$granular['expired'].' · Rotated '.$granular['rotated']],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to],
            'as_of' => date('Y-m-d H:i'),
        ];
    }

    private function sessionDetail(AuditLog $log): string
    {
        $parts = [];
        if (isset($log->metadata['justification']) && is_string($log->metadata['justification'])) {
            $parts[] = 'Why: '.$log->metadata['justification'];
        }
        if (isset($log->metadata['session_expires_at'])) {
            $parts[] = 'Expires: '.date('Y-m-d H:i', (int) $log->metadata['session_expires_at']);
        }

        return implode(' · ', $parts);
    }
}