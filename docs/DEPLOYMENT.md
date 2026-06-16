# Deployment & Rollback

## Current reality
Deployment is manual (FTP/cPanel; see `run_migrations.py`, `id_rsa_skilltricks`,
`/home/.../public_html` paths in logs). No zero-downtime, no automated rollback.
This doc defines a repeatable, reversible process until CI/CD is wired up.

Prerequisites on the server: **PHP 8.2** (matches `composer.json`), Composer,
a process manager for queues (Supervisor — see `docs/WEEK3_INFRA_SETUP.md`).

---

## Deploy (manual, repeatable)

```bash
# 1. Tag the currently-live commit BEFORE deploying (rollback target).
git tag deploy-$(date +%Y%m%d-%H%M%S) <current-live-sha>
git push --tags

# 2. Back up the database (rollback target for data/schema).
mysqldump -u <user> -p <db> | gzip > backup-$(date +%Y%m%d-%H%M%S).sql.gz

# 3. Pull the new release.
git fetch origin && git checkout <new-sha>

# 4. Install prod dependencies (no dev tools, optimized autoloader).
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Put app in maintenance during migration.
php artisan down --render="errors::503"

# 6. Run migrations.
php artisan migrate --force

# 7. Rebuild caches (config:cache only safe once admin settings views are
#    migrated off env() - see Week-2 notes; until then skip config:cache).
php artisan route:cache
php artisan view:cache
# php artisan config:cache   # enable only after settings-architecture fix

# 8. Restart queue workers so they load new code.
php artisan queue:restart      # or: supervisorctl restart skilltricks-horizon

# 9. Bring it back up.
php artisan up
```

---

## Rollback

### Code rollback
```bash
php artisan down
git checkout <previous-deploy-tag>
composer install --no-dev --optimize-autoloader
php artisan route:clear && php artisan view:clear && php artisan config:clear
php artisan queue:restart
php artisan up
```

### Database rollback
- Schema-only, reversible migration: `php artisan migrate:rollback --step=1 --force`.
- Destructive/irreversible migration: restore the dump taken in deploy step 2:
  ```bash
  gunzip < backup-<timestamp>.sql.gz | mysql -u <user> -p <db>
  ```
- Always pair a code rollback with the matching DB state. Migrations that drop
  columns/tables are NOT recoverable without the dump — never skip step 2.

### Git history note
A history rewrite was performed (removal of committed logs/phpinfo). A full
pre-rewrite backup exists as `skilltricks-backup-<timestamp>.bundle`
(`git clone <bundle>` to recover the original history if ever needed).

---

## Pre-deploy checklist
- [ ] CI green (PHPUnit + PHPStan + Pint).
- [ ] `.env` on server is correct; `APP_ENV` not `local`; `APP_DEBUG=false`.
- [ ] DB dump taken (step 2).
- [ ] Previous-live commit tagged (step 1).
- [ ] Secrets rotated if any were exposed (SSH key, payment/API keys).
- [ ] `composer audit` reviewed (currently reports advisories — triage before deploy).
