<?php

namespace App\Reports\Governance;

use App\Models\AuditLog;
use App\Reports\BaseReport;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginSecurity extends BaseReport
{
    public function slug(): string
    {
        return 'login-security';
    }

    public function title(): string
    {
        return 'Account & Login Security';
    }

    public function icon(): string
    {
        return '👤';
    }

    public function category(): string
    {
        return 'Governance & Security';
    }

    public function description(): string
    {
        return 'User accounts, their roles, last sign-in, and failed break-glass attempts — for detecting dormant accounts and suspicious activity.';
    }

    public function allows(User $user): bool
    {
        return $user->is_active && $user->isGlobalAccess();
    }

    public function filters(): array
    {
        return [
            ['key' => 'from', 'label' => 'From', 'type' => 'date', 'default' => date('Y-m-d', strtotime('-30 days'))],
            ['key' => 'to', 'label' => 'To', 'type' => 'date', 'default' => date('Y-m-d')],
        ];
    }

    public function generate(Request $request, User $user, array $filterValues): array
    {
        $from = $filterValues['from'] ?? date('Y-m-d', strtotime('-30 days'));
        $to = $filterValues['to'] ?? date('Y-m-d');
        if ($to < $from) {
            $to = $from;
        }

        $users = DB::table('users')
            ->orderByDesc('last_login_at')
            ->get([
                'username', 'name', 'role_code', 'depot_code',
                'is_active', 'is_super_admin', 'is_hq', 'last_login_at',
            ]);

        $rows = [];
        $loginCount = 0;
        $superAdmin = 0;
        $inactive = 0;

        foreach ($users as $account) {
            if ((int) $account->is_active !== 1) {
                $inactive++;
            }
            if ((int) $account->is_super_admin === 1) {
                $superAdmin++;
            }

            $lastLogin = $account->last_login_at ? date('Y-m-d H:i', strtotime($account->last_login_at)) : 'Never';
            if ($account->last_login_at
                && $account->last_login_at >= $from.' 00:00:00'
                && $account->last_login_at <= $to.' 23:59:59') {
                $loginCount++;
            }

            $rows[] = [
                'username' => $account->username,
                'name' => $account->name,
                'role' => $account->role_code,
                'depot' => $account->depot_code ?? '—',
                'flags' => trim(($account->is_super_admin == 1 ? 'Super Admin ' : '').($account->is_hq == 1 ? 'HQ' : '')),
                'last_login' => $lastLogin,
                'status' => (int) $account->is_active === 1 ? 'Active' : 'Inactive',
            ];
        }

        $failedBreakGlass = AuditLog::query()
            ->where('event', 'break_glass_login_failed')
            ->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->count();

        $sections = [];
        if ($rows === []) {
            $sections[] = ['title' => 'No data', 'note' => 'No accounts found.', 'columns' => [], 'rows' => []];
        } else {
            $sections[] = ['title' => 'Accounts', 'note' => 'Accounts with no recent last-login date are candidates for review or deactivation.', 'columns' => ['Username', 'Name', 'Role', 'Depot', 'Flags', 'Last login', 'Status'], 'rows' => $rows];
        }

        return [
            'kpis' => [
                ['label' => 'Total accounts', 'value' => number_format(count($users))],
                ['label' => 'Signed in', 'value' => number_format($loginCount), 'sub' => 'In window'],
                ['label' => 'Super admins', 'value' => number_format($superAdmin)],
                ['label' => 'Inactive accounts', 'value' => number_format($inactive), 'sub' => 'Failed break-glass '.number_format($failedBreakGlass)],
            ],
            'sections' => $sections,
            'filters_applied' => ['Period' => $from.' → '.$to],
            'as_of' => date('Y-m-d H:i'),
        ];
    }
}