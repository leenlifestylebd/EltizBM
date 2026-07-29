# Auto-deploy setup (one time)

After this, **every merge to `master` auto-deploys** to the server — no more SSH.
You just push + merge; GitHub Actions SSHes in and runs the pull + artisan steps.

## Step 1 — make an SSH key for GitHub → server (run on the SERVER)
```bash
ssh-keygen -t ed25519 -f ~/.ssh/gh_deploy -N "" -C "github-actions"
cat ~/.ssh/gh_deploy.pub >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
cat ~/.ssh/gh_deploy        # <-- copy this WHOLE private key (incl. BEGIN/END lines)
```

## Step 2 — add GitHub repo secrets
GitHub → your repo → **Settings → Secrets and variables → Actions → New repository secret**.
Add these four:

| Secret name | Value |
|---|---|
| `SSH_HOST` | `217.21.87.96` |
| `SSH_PORT` | `65002` |
| `SSH_USER` | `u343314711` |
| `SSH_KEY`  | the whole private key you copied in Step 1 |

## Step 3 — make sure the server can pull non-interactively
The Action runs `git fetch` on the server, so the server must reach GitHub without a
password prompt. Easiest: put a **read-only token** in the remote once (run on server):
```bash
cd ~/domains/leen.com.bd/fulfillo
git remote set-url origin https://<GH_USER>:<READONLY_TOKEN>@github.com/leenlifestylebd/EltizBM.git
git fetch origin        # should succeed with no prompt
```
Use a GitHub **fine-grained token** with *Contents: Read-only* on this repo.
(If `git fetch` already works without a prompt on the server, you can skip this.)

## Step 4 — commit this workflow to master (one last manual push)
```bash
git push origin fix/bonus-not-reduce-pay
gh pr create --base master --head fix/bonus-not-reduce-pay --fill
gh pr merge fix/bonus-not-reduce-pay --merge
```
That merge itself will trigger the first auto-deploy. Watch it in the repo's
**Actions** tab.

## From now on
Merge any PR to `master` → it deploys automatically. Trigger manually anytime from
**Actions → Deploy to production → Run workflow**.
