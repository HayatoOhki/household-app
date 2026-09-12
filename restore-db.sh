#!/bin/bash

set -euo pipefail

BACKUP_DIR="./backups"

if [ ! -d "${BACKUP_DIR}" ]; then
echo "Backup directory not found:"
echo "${BACKUP_DIR}"
exit 1
fi

LATEST_BACKUP=$(find "${BACKUP_DIR}" -maxdepth 1 -type f -name 'household-*.sql' -printf '%T@ %p\n' | sort -nr | head -n 1 | cut -d' ' -f2-)

if [ -z "${LATEST_BACKUP}" ]; then
echo "Backup file not found."
exit 1
fi

echo "Latest backup:"
echo "${LATEST_BACKUP}"
echo
echo "Current household database will be replaced."
echo

read -r -p "Restore? [y/N]: " CONFIRM

case "${CONFIRM}" in
y|Y)
;;
*)
echo "Restore cancelled."
exit 0
;;
esac

echo
echo "Restoring database..."

if ! docker compose exec -T mysql mysql -u household -psecret household < "${LATEST_BACKUP}"; then
echo
echo "Restore failed."
exit 1
fi

echo
echo "Restore completed:"
echo "${LATEST_BACKUP}"
