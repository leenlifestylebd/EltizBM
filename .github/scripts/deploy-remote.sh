#!/usr/bin/env bash
set -euo pipefail
umask 077
backup_id="$1"; target="$2"
[[ "$backup_id" =~ ^[0-9]+$ && "$target" =~ ^[0-9a-f]{40}$ ]] || exit 2
app="$HOME/domains/leen.com.bd/fulfillo"
backup_dir="$HOME/eltiz-private-backups/$backup_id"
cd "$app"
# Read repository objects from the runner's code-only bundle, without GitHub credentials.
git bundle verify "$backup_dir/release.bundle"
[[ "$(git bundle list-heads "$backup_dir/release.bundle" HEAD | cut -d' ' -f1)" == "$target" ]] || exit 2
git fetch "$backup_dir/release.bundle" HEAD:refs/remotes/origin/master
[[ "$(git rev-parse origin/master)" == "$target" ]] || { echo 'Master moved; deploy stopped'; exit 1; }
test -z "$(git status --porcelain --untracked-files=no)" || { echo 'Tracked production edits need review'; exit 1; }
previous=$(git rev-parse HEAD)
git merge-base --is-ancestor "$previous" "$target"
# Only additive, restore-tested migrations are permitted here. Each one is
# reviewed against a restored production database before being listed.
allowed_migrations=(
  database/migrations/2026_09_20_000001_add_warehouse_id_to_orders.php
  database/migrations/2026_10_05_000001_keep_single_main_warehouse.php
)
test -z "$(git diff --name-only "$previous" "$target" -- composer.json composer.lock)" || { echo 'Dependency change needs separate staging'; exit 1; }
schema_changes=$(git diff --name-only "$previous" "$target" -- database/migrations)
pending_migrations=()
if [[ -n "$schema_changes" ]]; then
  while IFS= read -r change; do
    [[ -n "$change" ]] || continue
    permitted=0
    for allowed in "${allowed_migrations[@]}"; do
      if [[ "$change" == "$allowed" ]]; then permitted=1; break; fi
    done
    [[ "$permitted" == 1 ]] || { echo 'Unreviewed schema change'; exit 1; }
    pending_migrations+=("$change")
  done <<< "$schema_changes"
fi
test ! -f storage/framework/down || { echo 'Application already in maintenance'; exit 1; }
if pgrep -u "$(id -u)" -f '[a]rtisan (queue:work|queue:listen|horizon|schedule:work)' >/dev/null; then
  echo 'Background workers require a coordinated pause'; exit 1
fi
changed=0
recover() {
  result=$?
  trap - EXIT
  cd "$app"
  if [[ "$result" != 0 && "$changed" == 1 ]]; then
    git reset --hard "$previous"
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    echo 'Previous application code restored; business database retained'
  fi
  php artisan up
  exit "$result"
}
trap recover EXIT
php artisan down --retry=60
sleep 5
bash "$backup_dir/backup-remote.sh" "$backup_id"
(cd "$backup_dir" && sha256sum -c SHA256SUMS && sha256sum -c backup.cms.sha256)
php "$backup_dir/verify-order-stock.php" "$app" before "$backup_dir/order-stock-before.json"
changed=1
git reset --hard "$target"
php artisan config:clear
php artisan route:clear
php artisan view:clear
if [[ ${#pending_migrations[@]} -gt 0 ]]; then
  # Chronological order matters when more than one migration is pending.
  # Use a here-string, not process substitution: this host has no /dev/fd.
  sorted_migrations=$(printf '%s\n' "${pending_migrations[@]}" | sort)
  while IFS= read -r migration; do
    [[ -n "$migration" ]] || continue
    php artisan migrate --path="$migration" --force
  done <<< "$sorted_migrations"
fi
php "$backup_dir/verify-order-stock.php" "$app" after "$backup_dir/order-stock-before.json"
php artisan view:cache
php "$backup_dir/verify-payroll.php" "$app"
php artisan up
curl --fail --silent --show-error --max-time 30 https://app.leen.com.bd/login -o "$backup_dir/login-smoke.html"
grep -q 'Sign In' "$backup_dir/login-smoke.html"
printf 'Verified deployed commit: %s\n' "$(git rev-parse HEAD)"
