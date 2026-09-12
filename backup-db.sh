#!/bin/bash

set -euo pipefail

BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/household-${TIMESTAMP}.sql"

mkdir -p "${BACKUP_DIR}"

echo "Creating database backup..."

if ! docker compose exec -T mysql mysqldump -u household -psecret --single-transaction --routines --triggers --events --add-drop-table --no-tablespaces household > "${BACKUP_FILE}"; then
rm -f "${BACKUP_FILE}"

```
echo
echo "Backup failed."
exit 1
```

fi

echo
echo "Backup completed:"
echo "${BACKUP_FILE}"

echo
echo "Removing backups older than 7 days..."

find "${BACKUP_DIR}" -maxdepth 1 -type f -name 'household-*.sql' -mmin +10080 -delete

echo "Old backup cleanup completed."
