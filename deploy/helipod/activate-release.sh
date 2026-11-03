#!/usr/bin/env bash
set -euo pipefail

project="${1:?Project name required}"
release="${2:?Release tag required}"
case "$project" in biology-learn|warehouse-web) ;; *) exit 2 ;; esac
service=biolearn
if [[ "$project" == 'warehouse-web' ]]; then service=warehouse; fi
[[ "$release" =~ ^[a-zA-Z0-9._-]+$ ]] || exit 2
deployment="/root/${project}-deploy"
cd "$deployment"
umask 077

backup="${deployment}/backups/database-before-${release}.dump"
backup_env="${deployment}/backups/database-${release}.env"
trap 'rm -f "$backup_env"' EXIT
docker compose run --rm --no-deps --entrypoint php "$service" deploy/helipod/operations.php backup-env > "$backup_env"
docker run --rm --env-file "$backup_env" postgres:18-alpine pg_dump \
    --format=custom --no-owner --no-acl > "$backup"
test -s "$backup"
docker run --rm -i postgres:18-alpine pg_restore --list < "$backup" > "${backup}.contents"
echo "Database backup verified: ${backup}"

docker compose run --rm --no-deps --entrypoint php "$service" artisan migrate --force
if [[ "$project" == 'biology-learn' ]]; then
    docker compose run --rm --no-deps --entrypoint php "$service" deploy/helipod/operations.php protect-cms
    credentials="${deployment}/shared/storage/app/private/deployment-cms-credentials.json"
    if [[ -f "$credentials" ]]; then
        cp "$credentials" "${deployment}/cms-credentials.json"
        chmod 600 "${deployment}/cms-credentials.json"
        rm "$credentials"
    fi
fi
docker compose run --rm --no-deps --entrypoint php "$service" deploy/helipod/operations.php status
docker compose up -d --wait --wait-timeout 180
docker compose ps
echo "RELEASE_ACTIVE ${project} ${release}"
