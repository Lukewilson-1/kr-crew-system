# Scheduled Tasks

## Purpose

Several operational jobs must run automatically. They are driven by the **Laravel scheduler**, which is
invoked every minute by the operating system's **cron daemon** on the production VPS:

| Command | Frequency | Purpose |
|---|---|---|
| `crew:copy-end-of-day-status` | Daily 00:01 | Copies previous day's crew status to today |
| `running-rooms:auto-checkout-rested` | Every 5 min | Auto-checkout rested crew + notify HQ and booking officers |
| `maintenance:auto-deactivate` | Every 1 min | Auto-exit maintenance mode once `ends_at` passes |
| `audit:prune --retention=365` | Daily | Enforce the audit log retention policy (≥12 months) |

## How the scheduler runs

System cron calls the Laravel scheduler once per minute; Laravel then runs whichever jobs are due:

```
* * * * * cd /var/www/kr-crew-system && php artisan schedule:run >> /dev/null 2>&1
```

Verified with:

```bash
php artisan schedule:list          # shows the 4 scheduled jobs + next due
systemctl status cron              # cron daemon is active
crontab -l                         # the schedule:run line is present (deploy user)
```

See `docs/DEPLOYMENT_GUIDE.md` §5 for the full setup and verification steps.

## The auto-checkout task (what it does)

When a crew member checks into a running room, their rest period starts. Once the rest hours expire
(10h away from the home depot, 12h at the home depot), `running-rooms:auto-checkout-rested`:

1. finds in-room attendance records (`status = 'in'`) whose crew is `Resting`;
2. checks whether `restStarted + restHours` has passed;
3. checks the crew out of the room;
4. updates the crew payload (`status=SB`, `rrCheckedOut=true`, `restStarted=null`, `awayDepot=null`);
5. creates a `SystemNotification` for HQ admin + booking officers of the crew's depot and the room's depot
   (now also emails them via the notification service — see SYSTEM_DOCUMENTATION §8.5).

`maintenance:auto-deactivate` similarly takes the site back online automatically once scheduled
maintenance (`ends_at` in the `down` file) has passed.

## Configuration

No `CRON_TOKEN`/webhook is required for system cron. Just ensure:

- The `schedule:run` crontab line exists (see DEPLOYMENT_GUIDE §5).
- PHP is reachable from cron (`which php`; use an absolute path if needed).
- Storage/logs are writable by the deploy user so job output and `laravel.log` errors are captured.

## Legacy webhook fallback (shared hosting only)

The token-protected route `GET /running-rooms/cron/auto-checkout?token=...`
(`routes/web.php`, handler `RunningRoomController::cronAutoCheckout()`) is retained **only as a fallback**
for hosts that cannot run a real cron daemon. It triggers the same auto-checkout command and is exempt
from maintenance mode. The fallback **does not** cover the daily `crew:copy-end-of-day-status` or
`audit:prune` jobs — those must be triggered another way. System cron (above) is the supported path.

> **Note:** never commit a real `CRON_TOKEN`. If you enable the fallback, generate the token with
> `php -r "echo bin2hex(random_bytes(32));"` and keep it only in `.env`.

## Local testing (Windows dev machine)

```bash
php artisan schedule:work        # ticks the scheduler in the foreground
php artisan running-rooms:auto-checkout-rested
php artisan maintenance:auto-deactivate
```

## Notes

- All schedule entries are registered in `app/Console/Kernel.php`.
- Retired design decision: an earlier version of this app triggered tasks via the external
  cron-job.org service (a webhook scheduler). This is no longer used; system cron is the standard.

## Relevant files

- `app/Console/Kernel.php` — command registration + schedule entries
- `app/Console/Commands/AutoCheckoutExpiredRest.php` — auto-checkout + notification logic
- `app/Console/Commands/AutoDeactivateMaintenance.php` — auto-end of scheduled maintenance
- `app/Console/Commands/CopyEndOfDayStatus.php` — daily status copy
- `app/Console/Commands/PruneAuditLogs.php` — audit retention
- `app/Http/Controllers/RunningRoomController.php` — `cronAutoCheckout()` fallback webhook handler
- `app/Providers/AppServiceProvider.php` — maintenance-mode exemption for the fallback webhook
- `config/cron.php` — reads `CRON_TOKEN` (fallback webhook only)
- `routes/web.php` — the `running-rooms/cron/auto-checkout` fallback route
- `app/Services/NotificationService.php` + `system_notifications` table — in-app + email notifications