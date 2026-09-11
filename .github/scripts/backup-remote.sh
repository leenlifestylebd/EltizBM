#!/usr/bin/env bash
set -euo pipefail
umask 077
backup_id="$1"
[[ "$backup_id" =~ ^[0-9]+(-[a-z]+)?$ ]] || exit 2
backup_dir="$HOME/eltiz-private-backups/$backup_id"
app="$HOME/domains/leen.com.bd/fulfillo"
cd "$backup_dir"
test ! -e backup.cms
php backup-db.php "$app"
trap 'rm -f "$backup_dir/database.cnf"' EXIT
mysqldump --defaults-extra-file="$backup_dir/database.cnf" --single-transaction --quick --skip-lock-tables --hex-blob --routines --triggers --events "$(cat database-name.txt)" > database.sql
test -s database.sql
rm -f database.cnf
(cd "$app" && git rev-parse HEAD) > deployed-commit.txt
(cd "$app" && git status --porcelain) > working-tree-status.txt
# Exclude only volatile runtime caches and logs; preserve uploads, env and git.
tar --exclude='fulfillo/storage/logs/*' --exclude='fulfillo/storage/framework/views/*' --exclude='fulfillo/storage/framework/sessions/*' --exclude='fulfillo/storage/framework/cache/*' -czf application.tar.gz -C "$HOME/domains/leen.com.bd" fulfillo
tar -czf public.tar.gz -C "$HOME/domains/leen.com.bd/public_html" app
sha256sum database.sql application.tar.gz public.tar.gz deployed-commit.txt working-tree-status.txt database-metadata.json > SHA256SUMS
tar -cf payload.tar database.sql application.tar.gz public.tar.gz deployed-commit.txt working-tree-status.txt database-metadata.json SHA256SUMS
openssl cms -encrypt -binary -aes-256-cbc -stream -outform DER -in payload.tar -out backup.cms backup-recipient.pem
sha256sum backup.cms > backup.cms.sha256
printf 'Encrypted backup captured successfully\n'
