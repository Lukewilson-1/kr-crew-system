#!/usr/bin/env bash
# =============================================================================
# KR Crew & Running-Room Management System - MySQL Replication: REPLICA (DR)
# =============================================================================
# On a FRESH MySQL 8.0 instance on the DR host: applies replica configuration,
# clones the full SOURCE cluster via a GTID logical dump, and starts replication.
# Target: Ubuntu 24.04 LTS + MySQL 8.0 (on the DR server).
#
# Prerequisites:
#   - MySQL 8.0 freshly installed on the DR host (no application data yet).
#   - SOURCE prepared with scripts/mysql/enable-mysql-source.sh (GTID + user).
#   - Root (socket) access on the DR host and SOURCE admin access.
#   - $DR_HOST and $PROD_HOST reachable over port 3306 (see SID Appendix B).
#
# The replication password is prompted interactively and NEVER stored.
#
# Usage:
#   sudo bash setup-mysql-replica.sh --source-host <PROD_IP> [--source-port 3306]
#                                    [--repl-user repl] [--server-id 2]
#                                    [--source-admin root]
# =============================================================================
set -euo pipefail

SOURCE_HOST=""; SOURCE_PORT=3306; REPL_USER="repl"; SERVER_ID=2; SOURCE_ADMIN="root"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --source-host)   SOURCE_HOST="$2"; shift 2 ;;
    --source-port)   SOURCE_PORT="$2"; shift 2 ;;
    --repl-user)     REPL_USER="$2";   shift 2 ;;
    --server-id)     SERVER_ID="$2";   shift 2 ;;
    --source-admin)  SOURCE_ADMIN="$2"; shift 2 ;;
    *) echo "Unknown option: $1" >&2; exit 2 ;;
  esac
done
[[ -n "$SOURCE_HOST" ]] || { echo "Missing --source-host" >&2; exit 2; }

CONF_DIR="/etc/mysql/mysql.conf.d"
CONF_FILE="$CONF_DIR/zz-kr-crew-replication.cnf"
DUMP_FILE="/tmp/kr-crew-source-full.sql"

log() { echo "[replica] $*"; }
[[ "$(id -u)" -eq 0 ]] || { echo "Run with sudo / as root." >&2; exit 1; }

# ---- 1. Replica server configuration (idempotent) --------------------------
if [[ ! -f "$CONF_FILE" ]]; then
  log "Writing replica config: $CONF_FILE"
  install -d -m 755 "$CONF_DIR"
  cat > "$CONF_FILE" <<EOF
[mysqld]
server-id = $SERVER_ID
log_bin = /var/log/mysql/mysql-bin
binlog_format = ROW
relay_log = /var/log/mysql/mysql-relay-bin
read_only = ON
gtid_mode = ON
enforce_gtid_consistency = ON
EOF
  log "Restarting MySQL to apply replica settings..."
  systemctl restart mysql
  for _ in $(seq 1 30); do
    mysqladmin --no-defaults ping >/dev/null 2>&1 && break
    sleep 2
  done
  mysqladmin --no-defaults ping >/dev/null
else
  log "Config already present ($CONF_FILE); skipping restart."
fi

# ---- 2. Replication user password (prompted) -------------------------------
if [[ -z "${REPL_PASSWORD:-}" ]]; then
  read -rsp "Replication password for '${REPL_USER}' (as created on the source): " REPL_PASSWORD
  echo
fi
[[ -n "$REPL_PASSWORD" ]] || { echo "Empty password - aborting." >&2; exit 1; }

# ---- 3. Initial clone from source (GTID, logical) --------------------------
log "Cloning all databases from ${SOURCE_HOST}:${SOURCE_PORT} ..."
mysqldump --no-defaults \
  -h"$SOURCE_HOST" -P"$SOURCE_PORT" -u"$SOURCE_ADMIN" \
  --all-databases --single-transaction --routines --events --triggers \
  --set-gtid-purged=ON --default-character-set=utf8mb4 \
  > "$DUMP_FILE"
log "Dump written: $DUMP_FILE ($(stat -c%s "$DUMP_FILE") bytes)"

log "Applying clone to local MySQL..."
mysql --no-defaults -uroot < "$DUMP_FILE"

# ---- 4. Configure and start the replication channel ------------------------
log "Configuring replication channel..."
mysql --no-defaults -uroot <<SQL
STOP REPLICA;
RESET REPLICA ALL;
CHANGE REPLICATION SOURCE TO
  SOURCE_HOST = '${SOURCE_HOST}',
  SOURCE_PORT = ${SOURCE_PORT},
  SOURCE_USER = '${REPL_USER}',
  SOURCE_PASSWORD = '${REPL_PASSWORD}',
  SOURCE_AUTO_POSITION = 1,
  SOURCE_SSL = 1,
  SOURCE_SSL_VERIFY_SERVER_CERT = 0;
START REPLICA;
SQL

# ---- 5. Verify -------------------------------------------------------------
sleep 5
REPLA="$(mysql --no-defaults -uroot -e "SHOW REPLICA STATUS\G")"
IO_RUN="$(echo "$REPLA" | awk -F': ' '/Replica_IO_Running:/{gsub(/ /,"",$2); print $2}')"
SQL_RUN="$(echo "$REPLA" | awk -F': ' '/Replica_SQL_Running:/{gsub(/ /,"",$2); print $2}')"
LAG="$(echo "$REPLA" | awk -F': ' '/Seconds_Behind_Source:/{gsub(/ /,"",$2); print $2}')"

log "Replica_IO_Running: ${IO_RUN:-unknown}"
log "Replica_SQL_Running: ${SQL_RUN:-unknown}"
log "Seconds_Behind_Source: ${LAG:-NULL}"

if [[ "${IO_RUN:-NO}" == "Yes" && "${SQL_RUN:-NO}" == "Yes" ]]; then
  log "Replication is RUNNING. Schedule a first DR drill to validate restore-ability."
  exit 0
else
  echo "Replication did not start cleanly - investigate the replica status." >&2
  exit 1
fi