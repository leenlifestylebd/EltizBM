#!/usr/bin/env bash
set -euo pipefail
umask 077
[[ "$BACKUP_ID" =~ ^[0-9]+$ ]] || exit 2
keydir=$(mktemp -d)
trap 'rm -rf "$keydir"' EXIT
printf '%s\n' "$SSH_KEY" > "$keydir/id"
unset SSH_KEY
ssh-keyscan -p "$SSH_PORT" "$SSH_HOST" > "$keydir/known_hosts" 2>/dev/null
opts=(-i "$keydir/id" -o BatchMode=yes -o ConnectTimeout=45 -o UserKnownHostsFile="$keydir/known_hosts" -o StrictHostKeyChecking=yes)
ssh "${opts[@]}" -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "umask 077; mkdir -p ~/eltiz-private-backups/$BACKUP_ID"
scp "${opts[@]}" -P "$SSH_PORT" .github/scripts/backup-{db.php,remote.sh,recipient.pem} .github/scripts/deploy-remote.sh .github/scripts/verify-payroll.php .github/scripts/verify-order-stock.php "$SSH_USER@$SSH_HOST:eltiz-private-backups/$BACKUP_ID/"
if [[ "${DEPLOY_SHA:-}" != "" ]]; then
  [[ "$DEPLOY_SHA" =~ ^[0-9a-f]{40}$ ]] || exit 2
  # Build a code-only Git bundle on the runner. GitHub credentials stay here.
  [[ "$(git rev-parse HEAD)" == "$DEPLOY_SHA" ]] || exit 2
  [[ "$(git ls-remote origin refs/heads/master | cut -f1)" == "$DEPLOY_SHA" ]] || { echo 'Master moved; deploy stopped'; exit 1; }
  git bundle create "$keydir/release.bundle" HEAD
  scp "${opts[@]}" -P "$SSH_PORT" "$keydir/release.bundle" "$SSH_USER@$SSH_HOST:eltiz-private-backups/$BACKUP_ID/release.bundle"
  ssh "${opts[@]}" -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "bash ~/eltiz-private-backups/$BACKUP_ID/deploy-remote.sh $BACKUP_ID $DEPLOY_SHA"
else
  ssh "${opts[@]}" -p "$SSH_PORT" "$SSH_USER@$SSH_HOST" "bash ~/eltiz-private-backups/$BACKUP_ID/backup-remote.sh $BACKUP_ID"
fi
mkdir -p encrypted-backup
scp "${opts[@]}" -P "$SSH_PORT" "$SSH_USER@$SSH_HOST:eltiz-private-backups/$BACKUP_ID/backup.cms" "$SSH_USER@$SSH_HOST:eltiz-private-backups/$BACKUP_ID/backup.cms.sha256" encrypted-backup/
(cd encrypted-backup && sha256sum -c backup.cms.sha256)
