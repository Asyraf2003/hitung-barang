#!/usr/bin/env bash

set -Eeuo pipefail

zip_file="${1:-}"
app_dir="${2:-hitung-barang}"
public_dir="${3:-public_html}"
site_url="${4:-}"
clear_file_name="${5:-}"
site_url="${site_url%/}"

if [[ -z "$zip_file" || ! -f "$zip_file" ]]; then
    echo "ERROR: ZIP deployment tidak ditemukan: $zip_file" >&2
    exit 1
fi
if [[ -z "$site_url" ]]; then
    echo "ERROR: SITE_URL kosong." >&2
    exit 1
fi
if [[ ! "$clear_file_name" =~ ^clear-[a-f0-9]{16}\.php$ ]]; then
    echo "ERROR: nama maintenance entry harus unik dan berbentuk clear-<16 hex>.php." >&2
    exit 1
fi

listing_file="$(mktemp "${TMPDIR:-/tmp}/hitung-barang-zip-list.XXXXXX")"
permissions_file="$(mktemp "${TMPDIR:-/tmp}/hitung-barang-zip-permissions.XXXXXX")"
index_file="$(mktemp "${TMPDIR:-/tmp}/hitung-barang-index.XXXXXX")"
clear_file="$(mktemp "${TMPDIR:-/tmp}/hitung-barang-clear.XXXXXX")"
env_file="$(mktemp "${TMPDIR:-/tmp}/hitung-barang-env.XXXXXX")"
manifest_file="$(mktemp "${TMPDIR:-/tmp}/hitung-barang-manifest.XXXXXX")"

cleanup() {
    rm -f "$listing_file" "$permissions_file" "$index_file" "$clear_file" "$env_file" "$manifest_file"
}
trap cleanup EXIT

unzip -tq "$zip_file" >/dev/null
unzip -Z1 "$zip_file" > "$listing_file"
unzip -Z -l "$zip_file" > "$permissions_file"

failures=0

fail() {
    echo "FAIL: $1" >&2
    failures=$((failures + 1))
}

require_entry() {
    local entry="$1"
    if ! grep -Fxq "$entry" "$listing_file"; then
        fail "file wajib tidak ada di ZIP: $entry"
    fi
}

require_mode() {
    local entry="$1"
    local expected_mode="$2"
    local actual_mode
    actual_mode="$(awk -v entry="$entry" '$NF == entry {print $1; exit}' "$permissions_file")"
    if [[ "$actual_mode" != "$expected_mode" ]]; then
        fail "permission ZIP salah untuk $entry: diharapkan $expected_mode, ditemukan ${actual_mode:-missing}"
    fi
}

top_levels="$(awk -F/ 'NF {print $1}' "$listing_file" | sort -u | paste -sd ' ' -)"
expected_top_levels="$(printf '%s\n%s\n' "$app_dir" "$public_dir" | sort -u | paste -sd ' ' -)"
if [[ "$top_levels" != "$expected_top_levels" ]]; then
    fail "top-level ZIP harus tepat '$expected_top_levels', ditemukan '$top_levels'"
fi

require_entry "$app_dir/artisan"
require_entry "$app_dir/vendor/autoload.php"
require_entry "$app_dir/.env"
require_entry "$public_dir/index.php"
require_entry "$public_dir/.htaccess"
require_entry "$public_dir/build/manifest.json"
require_entry "$public_dir/$clear_file_name"

require_mode "$app_dir/" "drwxr-xr-x"
require_mode "$app_dir/.env" "-rw-------"
require_mode "$public_dir/" "drwxr-xr-x"
require_mode "$public_dir/index.php" "-rw-r--r--"
require_mode "$public_dir/$clear_file_name" "-rw-r--r--"

while IFS= read -r entry; do
    case "$entry" in
        "$app_dir/.env") ;;
        "$app_dir/.env".*) fail "file environment tambahan ikut ZIP: $entry" ;;
    esac

    case "$entry" in
        "$app_dir/.git/"*|"$app_dir/.github/"*|"$app_dir/tests/"*|"$app_dir/docs/"*|"$app_dir/deploy/"*|"$app_dir/scripts/"*|"$app_dir/node_modules/"*)
            fail "folder development ikut ZIP: $entry"
            ;;
        "$app_dir/public/"*)
            fail "public source duplikat ikut root aplikasi: $entry"
            ;;
        "$app_dir/resources/css/"*|"$app_dir/resources/js/"*)
            fail "frontend source ikut paket runtime: $entry"
            ;;
        "$app_dir/database/"*.sqlite|"$app_dir/database/"*.sqlite-shm|"$app_dir/database/"*.sqlite-wal)
            fail "database lokal ikut ZIP: $entry"
            ;;
        "$app_dir/storage/app/public/"?*|"$app_dir/storage/app/private/"?*)
            fail "data storage lokal ikut ZIP: $entry"
            ;;
        "$app_dir/bootstrap/cache/config.php"|"$app_dir/bootstrap/cache/events.php"|"$app_dir/bootstrap/cache/routes"*.php)
            fail "cache environment lokal ikut ZIP: $entry"
            ;;
        "$public_dir/storage"|"$public_dir/storage/"*|"$public_dir/hot")
            fail "artefak lokal ikut public_html: $entry"
            ;;
    esac
done < "$listing_file"

unzip -p "$zip_file" "$public_dir/index.php" > "$index_file"
unzip -p "$zip_file" "$public_dir/$clear_file_name" > "$clear_file"
unzip -p "$zip_file" "$app_dir/.env" > "$env_file"
unzip -p "$zip_file" "$public_dir/build/manifest.json" > "$manifest_file"

if grep -Fq "__APP_DIR_NAME__" "$index_file" "$clear_file"; then
    fail "placeholder APP_DIR_NAME belum diganti"
fi
if grep -Fq "__DEPLOY_TOKEN_HASH__" "$clear_file"; then
    fail "placeholder token maintenance belum diganti"
fi
if ! grep -Fq "usePublicPath(__DIR__)" "$index_file"; then
    fail "index.php belum menetapkan public path ke public_html"
fi
if ! grep -Eq "[a-f0-9]{64}" "$clear_file"; then
    fail "hash token maintenance tidak ditemukan"
fi
if ! grep -Fq "glob(\$cacheDirectory.'/*.php')" "$clear_file"; then
    fail "maintenance entry belum membersihkan bootstrap cache sebelum bootstrap"
fi
if ! grep -Fq "opcache_reset" "$clear_file" || ! grep -Fq "phase=maintain" "$clear_file"; then
    fail "maintenance entry belum memuat kontrak two-phase OPcache reset"
fi
if ! grep -Fq "optimize:clear" "$clear_file" || ! grep -Fq "migrate" "$clear_file" || ! grep -Fq "optimize" "$clear_file"; then
    fail "maintenance entry belum memuat kontrak clear/migrate/optimize"
fi
if ! grep -Fq 'unlink(__FILE__)' "$clear_file"; then
    fail "maintenance entry belum self-delete setelah sukses"
fi
if ! grep -Fq 'resources/js/app.js' "$manifest_file" || ! grep -Fq 'resources/css/app.css' "$manifest_file"; then
    fail "Vite manifest tidak memuat entry app.js dan app.css"
fi

if ! grep -Fxq "APP_ENV=production" "$env_file"; then
    fail ".env paket bukan production"
fi
if ! grep -Fxq "APP_DEBUG=false" "$env_file"; then
    fail ".env paket masih debug"
fi
if grep -Eq '^APP_KEY=$' "$env_file" || ! grep -Eq '^APP_KEY=.+$' "$env_file"; then
    fail "APP_KEY pada .env paket kosong"
fi
if grep -Eq '^DB_DATABASE=$|^DB_USERNAME=$' "$env_file"; then
    fail "credential database production belum lengkap"
fi

packaged_url="$(sed -n 's/^APP_URL=//p' "$env_file" | tail -n 1)"
packaged_url="${packaged_url%$'\r'}"
if [[ "$packaged_url" =~ ^\".*\"$ || "$packaged_url" =~ ^\'.*\'$ ]]; then
    packaged_url="${packaged_url:1:${#packaged_url}-2}"
fi
packaged_url="${packaged_url%/}"
if [[ "$packaged_url" != "$site_url" ]]; then
    fail "APP_URL paket harus $site_url, ditemukan ${packaged_url:-empty}"
fi

if (( failures > 0 )); then
    echo "ZIP verification failed with $failures problem(s)." >&2
    exit 1
fi

echo "OK: ZIP structure, Vite assets, production .env, vendor, permissions, and maintenance entry verified."
