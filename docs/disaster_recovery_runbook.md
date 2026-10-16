# Disaster Recovery, Backup & Restore Runbook
## Clothing Store Management System

### 1. Objective and Policy Baseline

- **Automated Backup Schedule:** Daily at midnight (minimum) + recommended hourly cron snapshot.
- **Storage Target:** Local server directory (`storage/app/backups`) + synchronized offsite storage (S3 / Cloud Storage / SFTP).
- **Retention Period:** 365 days retention with automated file purging.
- **Access Control:** Restore permissions restricted exclusively to the **Super Admin**.

---

### 2. Critical RTO & RPO Analysis

#### Business Targets
- **Recovery Time Objective (RTO):** 1 Hour.
- **Recovery Point Objective (RPO):** Maximum 1 Hour of data loss.

#### Shared Hosting / cPanel Realities & Recommendations
1. **The Daily Backup Fallback:**
   A single daily backup scheduled at midnight provides an RPO of **24 hours**, which does **not** meet the required 1-hour RPO.
2. **How to Achieve 1-Hour RPO on Shared Hosting:**
   - Configure a recurring cPanel Cron Job running every 60 minutes:
     ```bash
     0 * * * * cd /home/username/public_html && php artisan pos:backup >> /dev/null 2>&1
     ```
   - Each hourly snapshot takes under 3 seconds and produces a compressed SQL file with verification JSON metadata.
3. **How to Achieve Sub-15-Minute RPO (Enterprise Strategy):**
   - If migrating from shared hosting to a managed VPS or dedicated cloud instance (AWS RDS / DigitalOcean / Cloudways), enable MySQL Binary Logging (`log_bin = ON`).
   - Point-in-time recovery (PITR) using `mysqlbinlog` allows recovery to the exact second preceding a corruption or failure.
4. **RTO Feasibility (1 Hour):**
   - Database restore for typical retail database size (< 500 MB) takes 3 to 7 minutes via MySQL CLI or cPanel phpMyAdmin import.
   - Application file restoration via tarball takes under 10 minutes.
   - Total recovery time: ~15 to 20 minutes, well within the 1-hour RTO.

---

### 3. Backup Execution Procedures

#### Manual On-Demand Backup via CLI:
```bash
php artisan pos:backup
```

#### Scheduled Cron Configuration (cPanel / Linux Crontab):
```bash
# Automated Hourly Snapshot (Meets 1-Hour RPO)
0 * * * * cd /home/username/public_html && /usr/local/bin/php artisan pos:backup >> /dev/null 2>&1

# Automated Daily Retention Purge (Older than 365 Days)
30 2 * * * cd /home/username/public_html && /usr/local/bin/php artisan pos:backup --purge >> /dev/null 2>&1
```

---

### 4. Step-by-Step Restoration Procedure

> [!CAUTION]
> NEVER execute a database restore on a live production environment without first:
> 1. Activating maintenance mode (`php artisan down`).
> 2. Taking an emergency snapshot of the current state.
> 3. Testing the restoration on an isolated Staging database.

#### Step 1: Put Application into Maintenance Mode
```bash
php artisan down --secret="superadmin-emergency-bypass"
```

#### Step 2: Identify and Verify Backup Snapshot
Inspect `storage/app/backups/backup_YYYY-MM-DD_HHMMSS.json`:
- Confirm timestamp, file size, and table record counts.

#### Step 3: Restore Database via MySQL CLI
```bash
mysql -h 127.0.0.1 -u db_user -p db_name < storage/app/backups/backup_YYYY-MM-DD_HHMMSS.sql
```
*cPanel Alternative:* Log into cPanel -> phpMyAdmin -> Select Database -> Import -> Choose SQL file -> Go.

#### Step 4: Verify Post-Restore Data Integrity
Run table record verification against the backup JSON metadata:
```bash
php artisan tinker --execute="print_r(app(\App\Services\BackupService::class)->captureTableRecordCounts());"
```
Compare with counts recorded in the snapshot JSON.

#### Step 5: Run Post-Restore Smoke Tests
```bash
php artisan test --filter=EndToEndRetailLifecycleTest
```

#### Step 6: Bring System Back Online
```bash
php artisan up
```
