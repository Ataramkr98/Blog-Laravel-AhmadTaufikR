#!/usr/bin/env bash
set -euo pipefail

project="${1:?Project name required}"
release="${2:?Release tag required}"
case "$project" in biology-learn|warehouse-web) ;; *) exit 2 ;; esac
service=biolearn
if [[ "$project" == 'warehouse-web' ]]; then service=warehouse; fi
[[ "$release" =~ ^[a-zA-Z0-9._-]+$ ]] || exit 2

deployment="/root/${project}-deploy"
source_dir="${deployment}/releases/${release}"
umask 077
mkdir -p "$source_dir" "${deployment}/shared" "${deployment}/backups"
tar -xzf "${deployment}/${project}.tar.gz" -C "$source_dir"
tar -xzf "${deployment}/${project}-media.tar.gz" --skip-old-files -C "${deployment}/shared"
mkdir -p "${deployment}/shared/storage/app/public" \
    "${deployment}/shared/storage/framework/cache/data" \
    "${deployment}/shared/storage/framework/sessions" \
    "${deployment}/shared/storage/framework/views" \
    "${deployment}/shared/storage/logs"
chown -R 33:33 "${deployment}/shared/storage"
chmod -R u+rwX,g+rX "${deployment}/shared/storage"
if [[ -f "${deployment}/.env.production" ]]; then
    cp -p "${deployment}/.env.production" "${deployment}/backups/environment-before-${release}"
fi
mv "${deployment}/${project}.env.production" "${deployment}/.env.production"
chmod 600 "${deployment}/.env.production"
cp "${source_dir}/deploy/helipod/compose.yaml" "${deployment}/compose.yaml"
printf 'RELEASE_TAG=%s\nAPP_SOURCE=./releases/%s\nAPP_ENV_FILE=./.env.production\n' \
    "$release" "$release" > "${deployment}/.env"
cd "$deployment"
docker compose --progress plain build "$service"
docker compose run --rm --no-deps --entrypoint php "$service" /usr/local/bin/composer check-platform-reqs --no-dev
printf 'BUILD_READY %s %s\n' "$project" "$release"
