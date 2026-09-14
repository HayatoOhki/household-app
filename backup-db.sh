#!/bin/bash

set -euo pipefail

BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

BACKUP_FILE="${BACKUP_DIR}/household-backup-${TIMESTAMP}.sql"

mkdir -p "${BACKUP_DIR}"

echo "Creating database backup..."

if ! docker compose exec -T mysql \
    mysqldump \
    -u household \
    -psecret \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --add-drop-table \
    --no-tablespaces \
    household > "${BACKUP_FILE}"
then
    rm -f "${BACKUP_FILE}"

    echo
    echo "Backup failed."
    exit 1
fi

echo
echo "Backup completed:"
echo "${BACKUP_FILE}"

echo
echo "Keeping latest 10 normal backups..."

mapfile -t NORMAL_BACKUPS < <(
    find "${BACKUP_DIR}" \
        -maxdepth 1 \
        -type f \
        -name 'household-backup-*.sql' \
        -printf '%T@ %p\n' \
        | sort -nr \
        | cut -d' ' -f2-
)

if [ "${#NORMAL_BACKUPS[@]}" -gt 10 ]; then
    for BACKUP_TO_DELETE in "${NORMAL_BACKUPS[@]:10}"; do
        rm -f "${BACKUP_TO_DELETE}"
        echo "Removed:"
        echo "${BACKUP_TO_DELETE}"
    done
fi

echo
echo "Backup cleanup completed."