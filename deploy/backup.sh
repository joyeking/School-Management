#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# Database backup for the Docker deployment.
# Run from the deploy/ directory (where docker-compose.yml lives):
#
#   bash backup.sh                 # writes backups/rosariosis-YYYYmmdd-HHMM.sql.gz
#
# Schedule daily at 02:30 with cron:
#   crontab -e
#   30 2 * * * cd /opt/ethiosis/school-management/deploy && bash backup.sh >> backup.log 2>&1
# ---------------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")"

# Read DB_NAME / DB_ROOT_PASSWORD from .env
set -a
# shellcheck disable=SC1091
. ./.env
set +a

mkdir -p backups
STAMP="$(date +%Y%m%d-%H%M)"
OUT="backups/${DB_NAME}-${STAMP}.sql.gz"

echo "==> Dumping ${DB_NAME} to ${OUT}"
docker compose exec -T db \
	sh -c "exec mysqldump -uroot -p'${DB_ROOT_PASSWORD}' --single-transaction --routines --triggers '${DB_NAME}'" \
	| gzip > "${OUT}"

echo "==> Done: ${OUT} ($(du -h "${OUT}" | cut -f1))"

# Keep the 14 most recent backups
ls -1t backups/*.sql.gz | tail -n +15 | xargs -r rm -f
