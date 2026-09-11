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
# This release changes application code only. Schema/dependency upgrades need a separate reviewed path.
test -z "$(git diff --name-only "$previous" "$target" -- database/migrations composer.json composer.lock)" || { echo 'Schema or dependency change needs separate staging'; exit 1; }
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
changed=1
git reset --hard "$target"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan view:cache
php "$backup_dir/verify-payroll.php" "$app"
php artisan up
curl --fail --silent --show-error --max-time 30 https://app.leen.com.bd/login -o "$backup_dir/login-smoke.html"
grep -q 'Sign In' "$backup_dir/login-smoke.html"
printf 'Verified deployed commit: %s\n' "$(git rev-parse HEAD)"
