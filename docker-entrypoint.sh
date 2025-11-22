#!/usr/bin/env bash
set -euo pipefail

DRUPAL_ROOT="/var/www/html"
CONTENT_DIR="${DRUPAL_ROOT}/Content"
FILES_DIR="${DRUPAL_ROOT}/sites/default/files"

echo "[entrypoint] Ensuring directories exist:"
echo "  - ${CONTENT_DIR}"
echo "  - ${FILES_DIR}"

mkdir -p "${CONTENT_DIR}" "${FILES_DIR}"

# Make sure Apache (www-data) can write here if needed
chown -R www-data:www-data "${CONTENT_DIR}" "${FILES_DIR}" || true

echo "[entrypoint] No changes to settings.php (managed in Git)."

# Hand off to Apache / whatever CMD was provided
exec "$@"