#!/bin/bash

set -euo pipefail

BACKUP_DIR="./backups"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

mkdir -p "${BACKUP_DIR}"

if [ ! -d "${BACKUP_DIR}" ]; then
    echo "Backup directory not found:"
    echo "${BACKUP_DIR}"
    exit 1
fi

mapfile -t BACKUPS < <(
    find "${BACKUP_DIR}" \
        -maxdepth 1 \
        -type f \
        \( \
            -name 'household-backup-*.sql' \
            -o \
            -name 'household-pre-restore-*.sql' \
        \) \
        -printf '%T@ %p\n' \
        | sort -nr \
        | cut -d' ' -f2-
)

if [ "${#BACKUPS[@]}" -eq 0 ]; then
    echo "Backup file not found."
    exit 1
fi

echo "Available backups:"
echo

for INDEX in "${!BACKUPS[@]}"; do
    NUMBER=$((INDEX + 1))

    case "${BACKUPS[$INDEX]}" in
        *household-pre-restore-*)
            TYPE="pre-restore"
            ;;
        *)
            TYPE="backup"
            ;;
    esac

    echo "${NUMBER}) [${TYPE}] ${BACKUPS[$INDEX]}"
done

echo
read -r -p "Select backup number to restore: " SELECTION

if ! [[ "${SELECTION}" =~ ^[0-9]+$ ]]; then
    echo "Invalid selection."
    exit 1
fi

SELECTED_INDEX=$((SELECTION - 1))

if [ "${SELECTED_INDEX}" -lt 0 ] \
    || [ "${SELECTED_INDEX}" -ge "${#BACKUPS[@]}" ]
then
    echo "Invalid selection."
    exit 1
fi

RESTORE_FILE="${BACKUPS[$SELECTED_INDEX]}"

echo
echo "Selected backup:"
echo "${RESTORE_FILE}"

echo
echo "Current household database will be replaced."
echo "Before restore, current database will be saved automatically."

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

PRE_RESTORE_FILE="${BACKUP_DIR}/household-pre-restore-${TIMESTAMP}.sql"

echo
echo "Creating pre-restore backup..."

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
    household > "${PRE_RESTORE_FILE}"
then
    rm -f "${PRE_RESTORE_FILE}"

    echo
    echo "Pre-restore backup failed."
    echo "Restore was cancelled."
    exit 1
fi

echo
echo "Pre-restore backup completed:"
echo "${PRE_RESTORE_FILE}"

echo
echo "Keeping latest 5 pre-restore backups..."

mapfile -t PRE_RESTORE_BACKUPS < <(
    find "${BACKUP_DIR}" \
        -maxdepth 1 \
        -type f \
        -name 'household-pre-restore-*.sql' \
        -printf '%T@ %p\n' \
        | sort -nr \
        | cut -d' ' -f2-
)

if [ "${#PRE_RESTORE_BACKUPS[@]}" -gt 5 ]; then
    for BACKUP_TO_DELETE in "${PRE_RESTORE_BACKUPS[@]:5}"; do
        rm -f "${BACKUP_TO_DELETE}"

        echo "Removed:"
        echo "${BACKUP_TO_DELETE}"
    done
fi

echo
echo "Restoring database..."

if ! docker compose exec -T mysql \
    mysql \
    -u household \
    -psecret \
    household < "${RESTORE_FILE}"
then
    echo
    echo "Restore failed."
    echo
    echo "Current database backup remains available at:"
    echo "${PRE_RESTORE_FILE}"
    exit 1
fi

echo
echo "Restore completed:"
echo "${RESTORE_FILE}"

echo
echo "Pre-restore backup:"
echo "${PRE_RESTORE_FILE}"