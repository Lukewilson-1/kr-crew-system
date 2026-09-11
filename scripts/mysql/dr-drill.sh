#!/usr/bin/env bash
# =============================================================================
# KR Crew & Running-Room Management System - DR DRILL (run on the DR replica)
# =============================================================================
# Semi-annual disaster-recovery drill that:
#   1. Verifies replication health (IO + SQL running, lag).
#   2. Takes a logical snapshot of the crew database (read-only; does NOT disturb
#      the replica stream).
#   3. Restores the snapshot into a scratch "drill" database on the same host.
#   4. Compares schema integrity (table count + approximate row volume).
#   5. Dropped the scratch database and writes a dated PASS/FAIL report.
#
# Exit code 0 = PASS, 1 = FAIL (usable from monitoring/scheduling).
# Target: Ubuntu 24.04 LTS + MySQL 8.0 on the DR server.
#
# Usage:
#   sudo bash dr-drill.sh [--db cms] [--report-dir /var/log/kr-crew-drill]
# =============================================================================
set -uo pipefail

DB="${DB:-cms}"
REPORT_DIR="${REPORT_DIR:-/var/log/kr-crew-drill}"
DRILL_DB="${DB}_drill"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --db)         DB="$2"; shift 2 ;;
    --report-dir) REPORT_DIR="$2"; shift 2 ;;
    *) echo "Unknown option: $1" >&2; exit 2 ;;
  esac
done

install -d -m 755 "$REPORT_DIR"
RUN_TS="$(date -u +%Y%m%dT%H%M%SZ)"
DUMP_FILE="$REPORT_DIR/kr-crew-drill-${DB}-${RUN_TS}.sql"
REPORT="$REPORT_DIR/kr-crew-drill-${DB}-${RUN_TS}.log"

PASS=1
fail() { PASS=0; echo "FAIL: $*" | tee -a "$REPORT"; }
cleanup() { mysql --no-defaults -uroot -e "DROP DATABASE IF EXISTS \`${DRILL_DB}\`" >/dev/null 2>&1 || true; }
trap cleanup EXIT

{
  echo "=== KR Crew System - DR Drill ==="
  echo "Run at UTC:     $RUN_TS"
  echo "Database under test: $DB (scratch: $DRILL_DB)"
  echo "--------------------------------------------------------------------------------"
} >> "$REPORT"

# ---- 1. Replication health -------------------------------------------------
REPLA="$(mysql --no-defaults -uroot -e "SHOW REPLICA STATUS\G" 2>/dev/null || true)"
IO_RUN="$(echo "$REPLA" | awk -F': ' '/Replica_IO_Running:/{gsub(/ /,"",$2); print $2}')"
SQL_RUN="$(echo "$REPLA" | awk -F': ' '/Replica_SQL_Running:/{gsub(/ /,"",$2); print $2}')"
LAG="$(echo "$REPLA" | awk -F': ' '/Seconds_Behind_Source:/{gsub(/ /,"",$2); print $2}')"

echo "Replica IO running: ${IO_RUN:-n/a}" | tee -a "$REPORT"
echo "Replica SQL running: ${SQL_RUN:-n/a}" | tee -a "$REPORT"
echo "Seconds behind source: ${LAG:-n/a}" | tee -a "$REPORT"

if [[ "${IO_RUN:-NO}" != "Yes" || "${SQL_RUN:-NO}" != "Yes" ]]; then
  fail "Replication is not healthy - drill cannot pass."
fi

# ---- 2. Logical snapshot (read-only; leaves replication untouched) ---------
if ! mysqldump --no-defaults -uroot --single-transaction --no-create-db \
     --set-gtid-purged=OFF "$DB" > "$DUMP_FILE"; then
  fail "mysqldump of '$DB' failed."
fi
DUMP_SIZE="$(stat -c%s "$DUMP_FILE" 2>/dev/null || echo 0)"
DUMP_SHA="$(sha256sum "$DUMP_FILE" | cut -d' ' -f1)"
echo "Dump: $DUMP_FILE ($DUMP_SIZE bytes, sha256 $DUMP_SHA)" | tee -a "$REPORT"

# ---- 3. Restore into scratch drill database --------------------------------
mysql --no-defaults -uroot -e "CREATE DATABASE \`${DRILL_DB}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
if ! mysql --no-defaults -uroot "$DRILL_DB" < "$DUMP_FILE"; then
  fail "Restore into '${DRILL_DB}' failed."
fi

# ---- 4. Integrity comparison ------------------------------------------------
tbl()  { mysql --no-defaults -uroot -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$1' AND TABLE_TYPE='BASE TABLE'"; }
rows() { mysql --no-defaults -uroot -N -e "SELECT COALESCE(SUM(TABLE_ROWS),0) FROM information_schema.TABLES WHERE TABLE_SCHEMA='$1' AND TABLE_TYPE='BASE TABLE'"; }

TBL_LIVE="$(tbl "$DB")";   TBL_DRILL="$(tbl "$DRILL_DB")"
SUM_LIVE="$(rows "$DB")";  SUM_DRILL="$(rows "$DRILL_DB")"

echo "Tables (live=$TBL_LIVE, drill=$TBL_DRILL) | approx rows (live=$SUM_LIVE, drill=$SUM_DRILL)" | tee -a "$REPORT"
if [[ -z "$TBL_LIVE" || "$TBL_LIVE" != "$TBL_DRILL" ]]; then
  fail "Table count mismatch between '${DB}' and '${DRILL_DB}'."
fi

# ---- 5. Report + exit ------------------------------------------------------
RESULT="PASS"
[[ "$PASS" -eq 1 ]] || RESULT="FAIL"
{
  echo "--------------------------------------------------------------------------------"
  echo "RESULT: $RESULT"
} | tee -a "$REPORT"

echo "[dr-drill] report written to $REPORT"
[[ "$PASS" -eq 1 ]]