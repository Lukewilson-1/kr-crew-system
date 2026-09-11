# KR Crew System — Disaster Recovery (DR) Runbook

> **Owner:** Kenya Railways Corporation — ICT Division + Rail Operations (Developer/ICT Assistant)
> **Applies to:** Production MySQL 8 → DR replica topology (Ubuntu 24.04 LTS)
> **Status:** Implemented as scripts in `scripts/mysql/`; execution steps below.

---

## 1. Objective & targets

Provide an up-to-second replica of the Production database on the DR host and prove,
twice a year, that Production data can be restored from it.

| Metric | Target | Notes |
|---|---|---|
| **RPO** (recovery point objective) | ≤ 5 min (near-zero by default) | Governed by replication lag; alert if lag > 60 s |
| **RTO** (recovery time objective) | ≤ 1 h | Failover to DR + app re-point + smoke test |
| **Drill frequency** | Semi-annual (every 6 months) | KR Monitoring & Maintenance schedule |
| **Backup validation** | Monthly | Separate from replication (see §5) |

## 2. Architecture

```
Production (LAN)                     DR site (LAN)
   [MySQL SOURCE]  --GTID replication (SSL)-->   [MySQL REPLICA]   (read_only=1)
   server-id=1, log_bin=ROW                   server-id=2, relay_log, gtid_mode=ON
```

- **Source:** Production VM, MySQL 8, binary logging (ROW) + GTID enabled.
- **Replica:** DR VM, MySQL 8, `read_only=ON`, replays the source stream.
- Application data (`cms`) is replicated; the app itself and `storage/matter-photos`
  are separately backed up (monthly validation + daily off-site in Production).

## 3. Initial setup (one-time)

1. **On the Production (source) server:**
   ```bash
   sudo bash scripts/mysql/enable-mysql-source.sh --server-id 1 --repl-host <DR_IP>
   ```
   Writes `/etc/mysql/mysql.conf.d/zz-kr-crew-replication.cnf`, restarts MySQL, and
   creates the `repl` user (SSL required). Re-run safely if needed.

2. **On the DR server (fresh MySQL 8 install):**
   ```bash
   sudo bash scripts/mysql/setup-mysql-replica.sh \
       --source-host <PROD_IP> --source-port 3306 --repl-user repl --server-id 2
   ```
   Configures the replica, clones the source (GTID logical dump), starts replication,
   and verifies `Replica_IO_Running=Yes` / `Replica_SQL_Running=Yes`.

3. **Confirm:**
   ```bash
   mysql -uroot -e "SHOW REPLICA STATUS\G" | grep -E "Running|Seconds_Behind_Source"
   ```

> ⚠️ The first run of `setup-mysql-replica.sh` must be against an **empty** MySQL
> instance (fresh install) so GTID-published transactions can be applied cleanly.

## 4. Ongoing monitoring (KR Monitoring tools)

- Check every 5–10 min: `Replica_IO_Running`, `Replica_SQL_Running`, all channels.
- Alert if `Seconds_Behind_Source > 60` or any state ≠ `Yes`.
- Log review weekly (KR ICT Security); replication errors go to MySQL error log
  `/var/log/mysql/error.log` and are surfaced via KR monitoring.

## 5. Semi-annual DR drill (the step this runbook exists for)

Run on the **DR replica** host, twice a year (aligned to the KR Monitoring &
Maintenance schedule), under a Helpdesk change record approved by ICTM:

```bash
sudo bash scripts/mysql/dr-drill.sh --db cms
# exit 0 = PASS, exit 1 = FAIL
```

The drill:
1. Verifies replication is healthy (IO + SQL running, lag captured).
2. Dumps the `cms` database (read-only, does not disturb the replication stream).
3. Restores the dump into a scratch `cms_drill` database on the DR host.
4. Compares table count (+ approximate row volume) live vs drill replica.
5. Drops the scratch database and writes a dated report to
   `/var/log/kr-crew-drill/kr-crew-drill-cms-<timestamp>.log` (dump checksum included).

**After the drill:** attach the log to the Helpdesk change record, record the result
in `docs/sdlc/14-System-Implementation-Design-SID.md` Appendix D/E, and close the
change after ICT validation.

## 6. Failover to DR (declared disaster)

Follow KR ICT change management. The application code and `matter-photos` are assumed
restorable from the latest Production backup onto the DR VM; the database below is
already live on the replica.

1. **On the DR (replica) host — promote:**
   ```bash
   mysql -uroot -e "STOP REPLICA; RESET REPLICA ALL; SET GLOBAL read_only = OFF;"
   mysql -uroot -e "SHOW REPLICA STATUS\G"   # confirm channel stopped
   ```
2. **Point the application at DR:** update `APP_URL`/`DB_HOST`/`DB_PORT` etc. in
   `.env` on the DR app VM (or re-point a reverse proxy / DNS record), then
   `php artisan config:cache` (and `route:cache`) and restart PHP-FPM/Nginx.
3. **Smoke tests:** login, one depot, one running room, one matter, one report.
4. **Announce** failover per the Commissioning communication plan.

## 7. Fallback (back to restored original site)

- If the original site recovered: configure the recovered MySQL as **SOURCE** again,
  re-point the app, and rebuild the replica with `setup-mysql-replica.sh` when the
  replica-side data is no longer needed.
- Otherwise run until a new DR replica is provisioned via §3.

## 8. Backup & restore baseline (independent of replication)

- Daily off-site backups (mysqldump + storage + matter-photos) in Production; monthly
  restore validation with KR ICT.
- Restoration drill doubles as the last-resort DR path if replication is unavailable.

## 9. Roles

- **Developer / ICT Assistant:** scripts, drill execution, failover support.
- **KR ICT (Network & Security):** monitoring, PAM, quarterly vulnerability scans,
  approving changes and drills, disaster declaration.
- **Coordinator (ICT):** George Muia — ensures full ICT support on requests.

## 10. Related documents

- `docs/sdlc/12-Server-Environment-Requirements.md` — DR server specs (§4.1 row).
- `docs/sdlc/14-System-Implementation-Design-SID.md` — SID §4.3, Appendix B/C.
- `docs/DEPLOYMENT_GUIDE.md` — deployment & fallback procedures.
- `scripts/mysql/` — the three scripts referenced above.