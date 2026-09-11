#!/usr/bin/env bash
# =============================================================================
# KR Crew & Running-Room Management System - MySQL Replication: SOURCE (primary)
# =============================================================================
# Configures the PRODUCTION source MySQL 8.0 server for row-based, GTID-based
# replication and provisions the replication user (SSL required).
# Target: Ubuntu 24.04 LTS + MySQL 8.0 (on the Production server).
#
# Idempotent: safe to re-run. The replication user password is prompted and is
# NEVER written to disk or stored in the repository.
#
# Usage:
#   sudo bash enable-mysql-source.sh [--server-id 1] [--repl-user repl]
#                                    [--repl-host '<DR host ip>']
#
#   --server-id  integer  unique server id (source=1, replica=2)   [default 1]
#   --repl-user  string   replication user name                     [default repl]
#   --repl-host  string   allowed source-host for the user '%'|LAN IP [default %]
# =============================================================================
set -euo pipefail

SERVER_ID="${SERVER_ID:-1}"
REPL_USER="${REPL_USER:-repl}"
REPL_HOST="${REPL_HOST:-%}"

while [[ $# -gt 0 ]]; do
  case "$1" in
    --server-id) SERVER_ID="$2"; shift 2 ;;
    --repl-user) REPL_USER="$2"; shift 2 ;;
    --repl-host) REPL_HOST="$2"; shift 2 ;;
    *) echo "Unknown option: $1" >&2; exit 2 ;;
  esac
done

CONF_DIR="/etc/mysql/mysql.conf.d"
CONF_FILE="$CONF_DIR/zz-kr-crew-replication.cnf"

log() { echo "[source] $*"; }

# Sanity: we must be root to write /etc and restart mysql.
[[ "$(id -u)" -eq 0 ]] || { echo "Run with sudo / as root." >&2; exit 1; }

# ---- 1. Server configuration snippet (idempotent) -------------------------
if [[ ! -f "$CONF_FILE" ]]; then
  log "Writing replication config: $CONF_FILE"
  install -d -m 755 "$CONF_DIR"
  cat > "$CONF_FILE" <<EOF
[mysqld]
server-id = $SERVER_ID
log_bin = /var/log/mysql/mysql-bin
binlog_format = ROW
binlog_expire_logs_seconds = 604800
gtid_mode = ON
enforce_gtid_consistency = ON
EOF
  log "Restarting MySQL to apply binary-log / GTID settings..."
  systemctl restart mysql
  for _ in $(seq 1 30); do
    mysqladmin --no-defaults ping >/dev/null 2>&1 && break
    sleep 2
  done
  mysqladmin --no-defaults ping >/dev/null
else
  log "Config already present ($CONF_FILE); skipping restart."
fi

# ---- 2. Replication user ---------------------------------------------------
if [[ -z "${REPL_PASSWORD:-}" ]]; then
  read -rsp "Replication password for '${REPL_USER}'@'${REPL_HOST}': " REPL_PASSWORD
  echo
fi
[[ -n "$REPL_PASSWORD" ]] || { echo "Empty password - aborting." >&2; exit 1; }

mysql --no-defaults -uroot <<SQL
CREATE USER IF NOT EXISTS '${REPL_USER}'@'${REPL_HOST}' IDENTIFIED BY '${REPL_PASSWORD}';
ALTER USER '${REPL_USER}'@'${REPL_HOST}' IDENTIFIED BY '${REPL_PASSWORD}' REQUIRE SSL;
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO '${REPL_USER}'@'${REPL_HOST}';
FLUSH PRIVILEGES;
SQL
log "Replication user '${REPL_USER}'@'${REPL_HOST}' ready (SSL required)."

# ---- 3. Confirmation -------------------------------------------------------
log "SOURCE configuration complete."
log "Binary log coordinates:"
mysql --no-defaults -uroot -e "SHOW BINARY LOG STATUS\G" | head -20