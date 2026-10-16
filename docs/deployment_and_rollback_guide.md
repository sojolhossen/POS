# Staging-to-Production Deployment, Hosting & Rollback Guide
## Clothing Store Management System

### 1. Deployment Philosophy & Golden Rule
- **Target Hosting:** Linux cPanel / Shared Hosting / Apache / MySQL 8.x / PHP 8.2+.
- **Zero Direct-to-Production Rule:** No code may be deployed to Production without prior Staging execution, regression testing, backup snapshotting, and explicit **Super Admin Sign-Off**.
- **Data Preservation Guarantee:** Migrations must never drop production columns containing business transaction data. Reversals and corrections must be handled via additive ledger entries.

---

### 2. cPanel / Shared Hosting Prerequisites

| Requirement | Minimum Specification | Status Verification |
| :--- | :--- | :--- |
| **PHP Version** | PHP 8.2 or 8.3 | Verified via `php -v` |
| **PHP Extensions** | `pdo_mysql`, `bcmath`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `zip` | Active in cPanel PHP Selector |
| **MySQL Version** | MySQL 8.0+ or MariaDB 10.5+ | `SELECT VERSION()` |
| **Document Root** | Set cPanel Document Root to `/public_html/public` (or create symlink) | Essential for security |
| **File Permissions** | `storage/` and `bootstrap/cache/` writable (`775` or `755`) | Verified |
| **HTTPS Certificate** | Valid SSL (Let's Encrypt / AutoSSL) | Mandatory for POS & cookies |

---

### 3. Step-by-Step Staging Deployment Runbook

#### Step 1: Push Changes to Private GitLab
```bash
git push origin master
```

#### Step 2: Deploy to Staging Environment
On the Staging server:
```bash
cd /path/to/staging
git pull origin master
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 3: Run Full Automated Verification in Staging
```bash
php artisan test
```
Confirm all 72 tests pass (0 failures).

#### Step 4: Verify Backup & Recovery Snapshot
```bash
php artisan pos:backup
```

---

### 4. Production Manual Deployment Runbook

> Requires explicit approval from the Super Admin.

#### Step 1: Put Production into Maintenance Mode
```bash
php artisan down --message="System upgrade in progress. Normal operations will resume in 5 minutes." --retry=60
```

#### Step 2: Take Pre-Deployment Production Backup
```bash
php artisan pos:backup
```

#### Step 3: Pull Verified Release
```bash
git pull origin master
composer install --no-dev --optimize-autoloader
```

#### Step 4: Execute Database Migrations
```bash
php artisan migrate --force
```

#### Step 5: Refresh Application Caches & Build Assets
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 6: Verify Production Health Check
Access `/api/ping` via curl or browser to verify active connectivity:
```bash
curl -I https://yourdomain.com/api/ping
```
Expected: `HTTP/2 200 OK` with JSON `{"status":"online"}`.

#### Step 7: Deactivate Maintenance Mode
```bash
php artisan up
```

---

### 5. Emergency Rollback Runbook

If a critical failure, fatal error, or data corruption is discovered immediately after deployment:

1. **Activate Maintenance Mode:**
   ```bash
   php artisan down
   ```
2. **Revert Git Release to Previous Commit:**
   ```bash
   git reset --hard HEAD~1
   ```
3. **Restore Database from Pre-Deployment Snapshot:**
   ```bash
   mysql -u db_user -p db_name < storage/app/backups/backup_PRE_DEPLOY.sql
   ```
4. **Re-cache Clean Configuration:**
   ```bash
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
5. **Bring System Back Online:**
   ```bash
   php artisan up
   ```
