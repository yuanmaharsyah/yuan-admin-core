---
name: admin-deploy
description: "Use when deploying this admin project to a server or updating a server to a new commit."
---

# Deploy / update a server

```bash
git pull && composer install --no-dev -o && npm ci && npm run build
php artisan migrate --force && php artisan permissions:sync && php artisan optimize
php artisan storage:link   # first deploy only: public/storage for the Settings branding files
php artisan admin:doctor
sudo supervisorctl restart all
```

- The server reads the private `yuan/admin-core` repository with a read-only deploy key (`vendor/yuan/admin-core/README.md` → Access).
- Production: `APP_DEBUG=false`, a real queue driver with a supervised worker, the scheduler in cron (`php artisan schedule:run` every minute), `ADMIN_SEED_PASSWORD` set before the first seed, or no seeder and `php artisan admin:create-superadmin`.
- `php artisan admin:doctor` checks the npm dependencies, the module cache, the storage link and the environment.
- Public pages can be closed during maintenance with `php artisan admin:access maintenance --message="..."` and reopened with `php artisan admin:access open`.
