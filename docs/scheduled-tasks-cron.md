# Scheduled Tasks (Auto-Checkout Cron)

## Purpose

When a crew member checks into a running room, their rest period starts. Once the rest hours expire
(10h away from home depot, 12h at home depot), the system should automatically check them out of the
room and notify the HQ admin plus the booking officers of the crew's depot and the room's depot.

Because the production host is a **Microsoft shared server** (no real cron / shell access), this is
done with a token-protected **webhook** that a free scheduler service (cron-job.org) calls every
5 minutes.

## How it works

1. cron-job.org hits `GET /running-rooms/cron/auto-checkout?token=...` every 5 minutes.
2. `RunningRoomController::cronAutoCheckout()` verifies the token (`hash_equals`, rejects `change-me`).
3. It runs the `running-rooms:auto-checkout-rested` command, which:
   - finds in-room attendance records (`status = 'in'`) whose crew is `Resting`
   - checks whether `restStarted + restHours` has passed
   - checks the crew out of the room
   - updates the crew payload (`status=SB`, `rrCheckedOut=true`, `restStarted=null`, `awayDepot=null`)
   - creates a `SystemNotification` for HQ admin + booking officers of the crew's depot and the room's depot
4. It then runs `maintenance:auto-deactivate`, which takes the site back online automatically once
   scheduled maintenance (`ends_at` in the `down` file) has passed. This makes the webhook double as
   the scheduler for maintenance mode on hosts without real cron.
5. The webhook is **exempt from maintenance mode**, so it keeps working while the site is offline.

## Cron job (already configured)

Job ID **8273192** on cron-job.org — **enabled**:

- URL: `https://cms.krc.co.ke/running-rooms/cron/auto-checkout?token=fa93dc60b98a6c1570cc48f997640430bf766f760f0a12dfec2d249e6e0f1a62`
- Schedule: every 5 minutes (`minutes: 0,5,10,15,20,25,30,35,40,45,50,55`)
- Timezone: `Africa/Nairobi`
- Method: GET, timeout 60s, notifications on failure enabled

## Required setup (server `.env`)

The token baked into the cron URL **must** match the server's `.env` or the webhook returns 401:

```env
CRON_TOKEN=fa93dc60b98a6c1570cc48f997640430bf766f760f0a12dfec2d249e6e0f1a62
```

After editing, clear the config cache:

```bash
php artisan config:clear
```

The token is deliberately **URL-safe** (hex only). Do not use a token containing `+`, `/`, or `=`
(they break query-string parsing). Generate a new one with:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## DNS (pending — blocker)

`krc.co.ke` resolves to `74.220.219.97`, but **`cms.krc.co.ke` has no DNS record yet**. Until the
subdomain resolves, cron-job.org fails with "could not connect to host" (job status 3).

Action required in the domain DNS manager (registrar or hosting panel):

- Add an **A record** for `cms` pointing to the Microsoft shared server's IP, or
- Add a **CNAME** record for `cms` to the host-provided hostname if the hosting panel uses one.

## Verify after DNS + deploy

1. Confirm the webhook responds with the token:
   ```bash
   curl -i "https://cms.krc.co.ke/running-rooms/cron/auto-checkout?token=fa93dc60b98a6c1570cc48f997640430bf766f760f0a12dfec2d249e6e0f1a62"
   ```
   Expected: `HTTP 200` with body `{"ok":true,"output":"Auto-checked out N crew member(s)..."}`.

2. Confirm it rejects bad tokens:
   ```bash
   curl -i "https://cms.krc.co.ke/running-rooms/cron/auto-checkout"
   ```
   Expected: `HTTP 401`.

3. Watch job history on cron-job.org (Console → the job → History) for status `OK` (1) and `httpStatus 200`.

## Local testing (Windows dev machine)

No cron exists locally either. To tick the scheduler manually in a terminal:

```bash
php artisan schedule:work
```

Or run the command directly:

```bash
php artisan running-rooms:auto-checkout-rested
php artisan maintenance:auto-deactivate
```

## Notes

- The Laravel scheduler entries (`running-rooms:auto-checkout-rested` every 5 min and
  `maintenance:auto-deactivate` every 1 min) are still registered in `app/Console/Kernel.php`. They are
  harmless and will work automatically if the host ever provides real cron (`* * * * * php artisan schedule:run`).
  If that happens, the webhook becomes redundant and can be removed.
- Changing `CRON_TOKEN` requires updating both the server `.env` and the cron-job.org job URL.
- Never commit real secrets; keep `CRON_TOKEN=change-me` in `.env.example`.

## Relevant files

- `app/Http/Controllers/RunningRoomController.php` — `cronAutoCheckout()` webhook handler
- `app/Console/Commands/AutoCheckoutExpiredRest.php` — the command the webhook runs
- `app/Console/Commands/AutoDeactivateMaintenance.php` — auto-end of scheduled maintenance
- `app/Console/Kernel.php` — command registration + schedule entries
- `app/Providers/AppServiceProvider.php` — maintenance-mode exemption for the webhook
- `config/cron.php` — reads `CRON_TOKEN` from the environment
- `routes/web.php` — the `running-rooms/cron/auto-checkout` route
- `app/Models/SystemNotification.php` + `system_notifications` table — notification storage
