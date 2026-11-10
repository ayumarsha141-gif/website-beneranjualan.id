# Database setup and backup

The project requires MySQL 8+ or a compatible MariaDB release with InnoDB and `utf8mb4`. The schema and migrations are in this directory; installation is only available through the CLI script.

## Install

For local development, create the database and an application user with access only to that database. Example for a local MySQL administrator (choose a new private password):

```sql
CREATE DATABASE beneranjualan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'beneranjualan_app'@'localhost' IDENTIFIED BY 'replace-with-a-private-secret';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES ON beneranjualan.* TO 'beneranjualan_app'@'localhost';
```

Set matching `.env` values, then run `php scripts/setup_db.php`. The initial migration is versioned and rerunnable. `php scripts/setup_db.php --seed-demo` optionally inserts clearly marked sample catalog products; do not seed these on a live store.

Create the first administrator separately with `php scripts/create_admin.php`. This script is CLI-only and does not install a default password.

## Backup and restore

Back up before schema changes and store backup files outside the public web root. Example:

```powershell
mysqldump --host=127.0.0.1 --user=beneranjualan_app --password --single-transaction --routines --triggers beneranjualan > backup.sql
mysql --host=127.0.0.1 --user=beneranjualan_app --password beneranjualan < backup.sql
```

Use a protected password prompt/secret manager rather than putting credentials in shell history. Test restores against a separate database. Migrations are not a substitute for backups. The old `archive/legacy-database-fragments/` files (formerly `bouquet_db/`) are not a recoverable SQL backup and are not imported.
