# Restaurant Setup Guide

This guide explains how to transfer and run the StockFlow POS / Inventory system on another device.

## 1. What Versions To Install

Install these versions, or newer compatible versions:

| Tool | Recommended Version | Notes |
| --- | --- | --- |
| WAMP | Latest 64-bit WampServer with PHP 8.3+ | Recommended for Windows restaurant computer |
| PHP | 8.3.14 or higher | This app requires PHP 8.3+ |
| MySQL / MariaDB | MySQL 8.x or MariaDB 10.6+ | WAMP usually includes MySQL/MariaDB |
| Composer | 2.8.12 or higher | PHP dependency installer |
| Node.js | 24.13.0 or current LTS | Used to build frontend assets |
| npm | 11.11.0 or included with Node | Comes with Node.js |
| Browser | Latest Chrome or Edge | Used by staff to open the system |

Your current development device uses:

```text
PHP 8.3.14
Composer 2.8.12
Node.js 24.13.0
npm 11.11.0
```

Use these same versions if you want the restaurant device to match your development setup closely.

## 2. Where To Install Everything

### WAMP

Install WAMP in the default location:

```text
C:\wamp64
```

After installation, the web projects folder should be:

Recommended folder:

```bash
C:\wamp64\www\pos-system\pos-app
```

### XAMPP Alternative

If using XAMPP instead of WAMP, install XAMPP in:

```bash
C:\xampp
```

Then put the project in:

```bash
C:\xampp\htdocs\pos-system\pos-app
```

### Composer

Install Composer globally using the Windows installer from:

```text
https://getcomposer.org/download/
```

During installation, select your PHP executable. For WAMP it will look like:

```text
C:\wamp64\bin\php\php8.3.14\php.exe
```

After installing, confirm:

```bash
composer --version
```

### Node.js

Install Node.js globally in its default location:

```text
C:\Program Files\nodejs
```

After installing, confirm:

```bash
node --version
npm --version
```

### Project Folder

The final Laravel project folder should be:

```text
C:\wamp64\www\pos-system\pos-app
```

Inside that folder, the Laravel public web root is:

```text
C:\wamp64\www\pos-system\pos-app\public
```

## 3. Copy The Project

Copy the whole `pos-app` folder to the new device.

Do not rely only on the browser files. A Laravel app needs the full project folder, including:

- `app`
- `bootstrap`
- `config`
- `database`
- `public`
- `resources`
- `routes`
- `storage`
- `composer.json`
- `package.json`
- `.env`

The `vendor` and `node_modules` folders can be copied, but it is cleaner to reinstall them on the new device.

## 4. Install PHP Dependencies

Open Command Prompt or PowerShell inside the new `pos-app` folder:

```bash
cd C:\wamp64\www\pos-system\pos-app
composer install
```

If Composer is not installed, install it first from:

```text
https://getcomposer.org/
```

## 5. Install Frontend Dependencies

Still inside `pos-app`, run:

```bash
npm install
npm run build
```

This prepares the frontend assets.

## 6. Create The Database

Open phpMyAdmin or MySQL and create a database:

```sql
CREATE DATABASE pos_db;
```

You may choose a different database name, but then update `.env`.

## 7. Configure `.env`

If there is no `.env` file, copy `.env.example`:

```bash
copy .env.example .env
```

Set these values:

```env
APP_NAME=StockFlow
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_db
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

Important: do not put spaces before `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, or `DB_PASSWORD`.

If MySQL has a password on the restaurant device, set it:

```env
DB_PASSWORD=your_mysql_password
```

## 8. Generate App Key

Run:

```bash
php artisan key:generate
```

If you copied your existing `.env` and it already has `APP_KEY`, you can keep it.

## 9. Run Migrations

For a fresh restaurant setup with empty data:

```bash
php artisan migrate
```

If you are moving existing data from your current device, see the next section before running this.

## 10. Transfer Existing Data

If the restaurant should receive your current products, stock movements, settings, and reports:

On your current device:

1. Open phpMyAdmin.
2. Select the current database, probably `pos_db`.
3. Click **Export**.
4. Choose SQL format.
5. Save the `.sql` file.

On the restaurant device:

1. Create the database `pos_db`.
2. Open phpMyAdmin.
3. Select `pos_db`.
4. Click **Import**.
5. Upload the `.sql` file.

After importing, do not run destructive reset commands. You can safely run:

```bash
php artisan migrate
```

This should only apply missing migrations.

## 11. Clear And Cache Configuration

Run:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

For a more production-like setup:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If you change `.env` later, run:

```bash
php artisan config:clear
```

## 12. Start The System

Simple local start:

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000
```

For WAMP/Apache, the safer public URL should point to Laravel's `public` folder:

```text
C:\wamp64\www\pos-system\pos-app\public
```

Do not expose the whole `pos-app` folder as the website root. The website root should be `public`.

## 13. Let Other Devices Access It On The Same Wi-Fi

Find the server computer IP address:

```bash
ipconfig
```

Look for something like:

```text
IPv4 Address . . . . . . . . . . : 192.168.1.25
```

Start Laravel using:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

On another device connected to the same Wi-Fi, open:

```text
http://192.168.1.25:8000
```

Replace `192.168.1.25` with the actual IP address.

If it does not open, allow PHP or port `8000` through Windows Firewall.

## 14. Final Restaurant Checklist

Before handing it over:

- Dashboard loads.
- Products page opens.
- Add one test product.
- Stock In increases quantity.
- Stock Out decreases quantity.
- Stock Out blocks quantity higher than available stock.
- Daily Reconciliation shows the correct stock numbers.
- Daily export downloads a CSV file.
- Settings page saves correctly.
- Remove any test product/movement data before final use.
- Set `APP_DEBUG=false` in `.env`.

## 15. Database Backup Setup

Backups are required even if the system is offline. Offline protects from internet risk, but not from power loss, disk failure, accidental deletion, or database corruption.

Recommended backup locations:

```text
D:\POS_BACKUPS
```

and one extra copy:

```text
USB drive
External hard drive
Google Drive / OneDrive, if internet is available
```

### Option A: Manual Backup With phpMyAdmin

Use this if the restaurant owner or cashier will manually back up the system.

1. Open WAMP.
2. Open phpMyAdmin.
3. Select the database:

```text
pos_db
```

4. Click **Export**.
5. Choose **Quick** export.
6. Format should be **SQL**.
7. Click **Export**.
8. Save the file in:

```text
D:\POS_BACKUPS
```

Recommended file name:

```text
pos_db_backup_YYYY-MM-DD.sql
```

Example:

```text
pos_db_backup_2026-05-17.sql
```

### Option B: Automatic Daily Backup

This is the recommended setup.

Create this folder:

```text
C:\pos-backup
```

Create this backup folder:

```text
D:\POS_BACKUPS
```

If the device has no `D:` drive, use:

```text
C:\POS_BACKUPS
```

Create this file:

```text
C:\pos-backup\backup_pos_db.bat
```

Put this inside the file:

```bat
@echo off
set MYSQLDUMP=C:\wamp64\bin\mysql\mysql8.4.0\bin\mysqldump.exe
set BACKUP_DIR=D:\POS_BACKUPS
set DB_NAME=pos_db
set DB_USER=root
set DB_PASSWORD=

if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

for /f "tokens=1-4 delims=/ " %%a in ("%date%") do (
    set YYYY=%%d
    set MM=%%a
    set DD=%%b
)

set BACKUP_FILE=%BACKUP_DIR%\%DB_NAME%_backup_%YYYY%-%MM%-%DD%.sql

if "%DB_PASSWORD%"=="" (
    "%MYSQLDUMP%" -u %DB_USER% %DB_NAME% > "%BACKUP_FILE%"
) else (
    "%MYSQLDUMP%" -u %DB_USER% -p%DB_PASSWORD% %DB_NAME% > "%BACKUP_FILE%"
)

echo Backup created: %BACKUP_FILE%
```

Important: check the real MySQL folder in WAMP. It may be different from:

```text
C:\wamp64\bin\mysql\mysql8.4.0\bin\mysqldump.exe
```

To find it:

1. Open:

```text
C:\wamp64\bin\mysql
```

2. Open the MySQL version folder.
3. Open:

```text
bin
```

4. Find:

```text
mysqldump.exe
```

5. Copy that full path into the `MYSQLDUMP` line.

If MySQL has a password, update:

```bat
set DB_PASSWORD=your_mysql_password
```

If MySQL has no password, leave it as:

```bat
set DB_PASSWORD=
```

### Test The Backup Script

Double-click:

```text
C:\pos-backup\backup_pos_db.bat
```

Then check if a `.sql` file appears in:

```text
D:\POS_BACKUPS
```

If no file appears, open Command Prompt and run:

```bash
C:\pos-backup\backup_pos_db.bat
```

Read the error message.

### Schedule Automatic Backup

Use Windows Task Scheduler:

1. Open **Start Menu**.
2. Search **Task Scheduler**.
3. Click **Create Basic Task**.
4. Name it:

```text
POS Daily Database Backup
```

5. Trigger: choose **Daily**.
6. Time: choose after restaurant closing, for example:

```text
11:30 PM
```

7. Action: choose **Start a program**.
8. Program/script:

```text
C:\pos-backup\backup_pos_db.bat
```

9. Finish.

After creating it:

1. Right-click the task.
2. Click **Run**.
3. Check `D:\POS_BACKUPS` to confirm a backup file was created.

### Backup Retention Rule

Recommended:

- Keep daily backups for 14 days.
- Keep weekly backups for 8 weeks.
- Keep monthly backups for 12 months.

At minimum, keep the latest 7 daily backups.

### Restore A Backup

Only restore if the current database is broken or lost.

1. Open phpMyAdmin.
2. Select `pos_db`.
3. Export the current database first if possible.
4. Drop all tables from `pos_db`, or create a new empty database.
5. Click **Import**.
6. Upload the backup `.sql` file.
7. Open the app and check products, stock movements, and reports.

Never test restore on the live restaurant database unless you already made a backup of the current data.

## 16. Common Problems

### Page Shows 500 Error

Run:

```bash
php artisan config:clear
php artisan cache:clear
```

Then check:

```text
storage/logs/laravel.log
```

### Database Connection Error

Check `.env`:

```env
DB_DATABASE=pos_db
DB_USERNAME=root
DB_PASSWORD=
```

Make sure MySQL is running in WAMP/XAMPP.

### Styles Look Broken

Run:

```bash
npm install
npm run build
```

### Permission Or Storage Error

Make sure these folders exist and are writable:

```text
storage
bootstrap/cache
```

Then run:

```bash
php artisan cache:clear
```

## 17. Important Notes

- This system currently does not have login/user access control.
- Use it only on a trusted restaurant computer or private local network.
- Do not expose it publicly to the internet unless authentication and security hardening are added.
- Always keep database backups before updates.
