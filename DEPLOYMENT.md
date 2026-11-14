# InfinityFree deployment

This guide documents the repository's GitHub Actions FTP deployment.

## 1. Target directory layout

The workflow deploys two repository folders independently:

| Repository folder | Suggested FTP destination | Purpose |
|---|---|---|
| `public/` | `htdocs/` | Public website document root |
| `app/` | `htdocs/app/` | PHP backend and admin application |

For the account layout described as `~/personalhub.my.id/htdoc/...`, verify the exact directory name in your FTP client. InfinityFree commonly uses `htdocs` (with an **s**) for the domain document root. The workflow values must be relative to the FTP account root, not local filesystem paths.

Set these GitHub repository **Variables**:

- `FTP_PUBLIC_DIR` = `htdocs/`
- `FTP_APP_DIR` = `htdocs/app/`

If your FTP account exposes a domain-specific parent folder, include that exact relative path, for example `personalhub.my.id/htdocs/` and `personalhub.my.id/htdocs/app/`. Do not guess the path; confirm it in the FTP client.

## 2. Configure GitHub Actions secrets

In GitHub, open **Settings → Secrets and variables → Actions**.

Add repository **Secrets**:

- `FTP_SERVER` — FTP hostname from InfinityFree.
- `FTP_USERNAME` — FTP username.
- `FTP_PASSWORD` — FTP password.

Add repository **Variables**:

- `FTP_PUBLIC_DIR` — destination for `public/`.
- `FTP_APP_DIR` — destination for `app/`.

Use the FTP host and account credentials shown in the hosting control panel. Do not put credentials in workflow YAML, README, or committed files.

## 3. Prepare the server once

Before the first automated deployment:

1. Create the MySQL database and import `app/database/schema.sql` and `app/database/seed.sql`.
2. Upload or create `app/config/.env` on the server and fill it with production values.
3. Confirm `app/config/.htaccess` exists and denies direct access to the configuration directory.
4. Ensure the `app/assets/uploads/` directory exists and is writable by PHP if uploads are enabled.
5. Confirm the public and app destinations are separate and point to the intended domain.

The workflow intentionally excludes `config/.env`, all `.env` files, and uploaded media subdirectories from the app deployment. This is designed to preserve server-specific configuration and runtime uploads when code is updated.

## 4. Deploy updates

The workflow runs on pushes to `main` when files under `public/`, `app/`, `DEPLOYMENT.md`, or the workflow itself change. It can also be started manually from **Actions → Deploy to InfinityFree → Run workflow**.

Recommended process:

```bash
git add .
git status --short
git commit -m "chore: update portfolio"
git push origin main
```

Check the Actions run log after pushing. A successful GitHub job indicates the FTP action completed; still open the website and test the changed feature.

## 5. Important synchronization behavior

FTP deployment is synchronization, not a database migration system.

- The workflow has separate state files for the public site and app.
- `.env` files are excluded from deployment.
- Upload folders are excluded so user-uploaded media is not replaced by an empty local directory.
- SQL files may be deployed as application files, but they are **not automatically executed** against the production database.
- Database schema changes must be reviewed, backed up, and applied manually through phpMyAdmin or a controlled migration process.

Review the FTP action's deletion/synchronization behavior before changing destination paths. Keep independent backups of the database, environment file, and uploads.

## 6. Local environment safety

Keep your local file at:

```text
app/config/.env
```

The repository ignores `.env` files. The safe template is:

```text
app/config/.env.example
```

Check that the real environment file is ignored:

```bash
git check-ignore -v app/config/.env
```

If `app/config/.env` was committed previously, remove it from Git tracking (without deleting your local copy):

```bash
git rm --cached app/config/.env
git commit -m "chore: stop tracking environment secrets"
```

Then rotate any credentials that were exposed in repository history.
