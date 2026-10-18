# NOMOWLY — HOSTINGER SHARED HOSTING DEPLOYMENT GUIDE
### Arabic Performance Marketing & Creator Economy Platform
**Target Stack:** Laravel 12 | PHP 8.2+ | MySQL 8.0+ | Blade + Tailwind CSS + Alpine.js | No Node.js / No Redis

---

## 1. PRE-DEPLOYMENT HOSTINGER AUDIT

Before uploading, log in to your **Hostinger hPanel**:

1. **PHP Version & Extensions**:
   - Go to **Advanced &rarr; PHP Configuration**.
   - Select **PHP 8.2** or **PHP 8.3**.
   - In the **PHP Extensions** tab, verify the following are enabled (standard on Hostinger):
     - `bcmath` (required for multi-currency commission math)
     - `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `json`, `mbstring`, `openssl`, `pcre`
     - `pdo_mysql` (required for MySQL 8 database connectivity)
     - `tokenizer`, `xml`, `zip`, `opcache`
2. **PHP Options**:
   - Set `memory_limit = 256M` or higher.
   - Set `max_execution_time = 120`.

---

## 2. DATABASE SETUP (MySQL 8)

1. In Hostinger hPanel, navigate to **Databases &rarr; Management**.
2. Click **Create New MySQL Database And Database User**:
   - **Database Name**: e.g., `u123456789_nomowly`
   - **Username**: e.g., `u123456789_nomowlyuser`
   - **Password**: Generate a strong password (copy this for step 5).
3. Note your MySQL host (usually `localhost` or `127.0.0.1` on Hostinger).

---

## 3. ARCHITECTURE & FOLDER STRUCTURE (SECURITY BEST PRACTICE)

Hostinger web roots default to `/home/uXXXXXX/domains/yourdomain.com/public_html`.
To prevent exposing sensitive files (`.env`, `app/`, `vendor/`), use either **Method A** (Recommended) or **Method B**.

### Method A: Out-of-Webroot Setup (Recommended)
1. In Hostinger File Manager, create a folder named `nomowly_app` parallel to `public_html`:
   ```
   /home/uXXXXXX/domains/yourdomain.com/
   ├── nomowly_app/          <-- Private Laravel application files
   │   ├── app/
   │   ├── bootstrap/
   │   ├── config/
   │   ├── database/
   │   ├── storage/
   │   ├── vendor/
   │   ├── .env
   │   └── artisan
   └── public_html/          <-- Web-accessible directory (contents of Laravel public/)
       ├── build/
       ├── .htaccess
       └── index.php
   ```
2. Upload the deployment package:
   - Extract the application files into `/home/uXXXXXX/domains/yourdomain.com/nomowly_app/`.
   - Move the contents of `nomowly_app/public/` directly into `public_html/`.
   - Edit `/home/uXXXXXX/domains/yourdomain.com/public_html/index.php` to point to the new paths:
     ```php
     // Change line 12:
     require __DIR__.'/../nomowly_app/vendor/autoload.php';

     // Change line 19:
     $app = require_once __DIR__.'/../nomowly_app/bootstrap/app.php';
     ```

### Method B: Symlink / Document Root Change
If your Hostinger plan allows changing the domain document root (or creating a symbolic link):
1. Extract the entire application into `/home/uXXXXXX/domains/yourdomain.com/`.
2. In hPanel, go to **Websites &rarr; Manage &rarr; General Settings**, and set the **Document Root** to:
   `/domains/yourdomain.com/public`

---

## 4. ENVIRONMENT CONFIGURATION (.env)

1. In the application root, copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Update the following values for production:
   ```dotenv
   APP_NAME="منصة نمولي | Nomowly"
   APP_ENV=production
   APP_KEY=                          # (Generated in Step 6)
   APP_DEBUG=false
   APP_URL=https://yourdomain.com
   APP_FORCE_HTTPS=true

   # Session & Cookie Security for Production
   SESSION_DRIVER=database
   SESSION_LIFETIME=120
   SESSION_SECURE_COOKIE=true
   SESSION_SAME_SITE=lax
   SESSION_PARTITIONED_COOKIE=false

   # Cache & Queue (Hostinger Native - No Redis required)
   CACHE_STORE=file
   QUEUE_CONNECTION=database

   # Database Configuration (MySQL 8)
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=u123456789_nomowly
   DB_USERNAME=u123456789_nomowlyuser
   DB_PASSWORD=YourSecureDatabasePasswordHere

   # Mail (Hostinger Titan Mail / SMTP)
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=no-reply@yourdomain.com
   MAIL_PASSWORD=YourEmailPasswordHere
   MAIL_ENCRYPTION=ssl
   MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

---

## 5. COMPOSER DEPENDENCIES

If SSH access is enabled in Hostinger hPanel (**Advanced &rarr; SSH Access**):
1. Connect via SSH to your account:
   ```bash
   ssh uXXXXXX@your-hostinger-ip -p 65002
   ```
2. Navigate to your app directory and install production dependencies:
   ```bash
   cd /home/uXXXXXX/domains/yourdomain.com/nomowly_app
   composer install --no-dev --optimize-autoloader --no-interaction
   ```
*(Note: If SSH is unavailable, upload the pre-installed `vendor/` directory from a local `composer install --no-dev` build).*

---

## 6. GENERATE APP KEY & RUN MIGRATIONS

Run the following Artisan commands via SSH:
```bash
# 1. Generate unique 256-bit encryption key
php artisan key:generate

# 2. Run all database migrations on MySQL
php artisan migrate --force

# 3. Create storage symlink
php artisan storage:link
```

---

## 7. SECURE INITIAL SUPER ADMIN CREATION

Create your initial Super Admin account securely via the built-in bootstrap command (pass your desired admin email and secure password):
```bash
php artisan nomowly:bootstrap-admin \
  --email="admin@yourdomain.com" \
  --password="YourStrongAdminPassword2026!"
```
*Note: This command is idempotent, enforces bcrypt hashing, sets mandatory audit trails, and never outputs passwords to logs.*

---

## 8. FILE PERMISSIONS

Ensure proper write permissions on runtime storage:
```bash
chmod -R 775 storage bootstrap/cache
```

---

## 9. SSL / HTTPS ACTIVATION

1. In Hostinger hPanel, go to **Security &rarr; SSL**.
2. Activate your free **Let's Encrypt SSL Certificate** for `yourdomain.com` and `www.yourdomain.com`.
3. Toggle on **Force HTTPS**.
4. In `.env`, ensure:
   ```dotenv
   APP_FORCE_HTTPS=true
   SESSION_SECURE_COOKIE=true
   ```

---

## 10. OPTIMIZE LARAVEL CACHES

Run optimization commands for production performance:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 11. CRON JOB CONFIGURATION (QUEUE & SCHEDULER)

Nomowly runs background queues (click attribution, conversion processing) and maintenance tasks via Laravel's native database queue and scheduler—**no Redis required**.

1. In Hostinger hPanel, go to **Advanced &rarr; Cron Jobs**.
2. Choose **Custom** schedule:
   - **Minute**: `*`
   - **Hour**: `*`
   - **Day of Month**: `*`
   - **Month**: `*`
   - **Day of Week**: `*`
3. Enter the command:
   ```bash
   cd /home/uXXXXXX/domains/yourdomain.com/nomowly_app && php artisan schedule:run >> /dev/null 2>&1
   ```
   *(Replace with your actual absolute path)*.
   
This automatically runs:
- `queue:work database --stop-when-empty --max-time=50` (processes queue jobs each minute safely without running as an unpermitted persistent daemon).
- `queue:prune-failed` (weekly cleanup).

---

---

## 12. FRONTEND ASSET COMPILATION (NO NODE ON SHARED HOSTING)

Hostinger shared hosting plans do **not** provide Node.js or `npm` in the SSH environment.
Therefore, frontend assets must be compiled locally or via CI/CD before deployment:

1. **Build Assets Locally**:
   ```bash
   npm install
   npm run build
   ```
2. **Verify Output**:
   Check that `public/build/manifest.json` and compiled CSS/JS exist in `public/build/assets/`.
3. **Upload to Hostinger**:
   Ensure the entire `public/build/` directory is uploaded directly into your `public_html/build/` directory.

---

## 13. PAYMENT GATEWAYS CONFIGURATION (MOYASAR & TAP)

Nomowly supports **Moyasar** (Saudi Arabia, Mada, Apple Pay, Visa/Mastercard in SAR) and **Tap Payments** (GCC & regional currencies: SAR, KWD, AED, BHD, QAR, OMR, EGP).

### Status Summary:
- **Architecture**: Complete and decoupled via `PaymentGatewayInterface` and `PaymentGatewayManager`.
- **Sandbox / Test Suite**: Uses `FakePaymentGateway` for automated feature and unit tests. In production (`APP_ENV=production`), `FakePaymentGateway` is strictly disabled and rejected.
- **Production Gateways**: Ready to receive live traffic once credentials and webhook secrets are entered into `.env`.

### Step-by-Step Gateway Activation:

#### A. Moyasar (Mada, Apple Pay, Credit Card - SAR / KSA)
1. Register and complete merchant verification at [moyasar.com](https://moyasar.com).
2. Retrieve your keys from Moyasar Dashboard:
   - For Testing: `pk_test_...` and `sk_test_...`
   - For Production: `pk_live_...` and `sk_live_...`
3. Configure `.env`:
   ```dotenv
   MOYASAR_API_KEY=sk_live_...
   MOYASAR_PUBLISHABLE_KEY=pk_live_...
   MOYASAR_WEBHOOK_SECRET=your_moyasar_webhook_secret
   ```
4. Set Webhook in Moyasar Dashboard:
   - **URL**: `https://yourdomain.com/api/v1/payments/webhooks/moyasar`
   - **Events**: `payment.paid`, `payment.failed`, `payment.refunded`

#### B. Tap Payments (KNET, Benefit, Mada, Fawry, Apple Pay - GCC & Egypt)
1. Register and complete merchant KYC at [tap.company](https://tap.company).
2. Retrieve keys from Tap Dashboard &rarr; Credentials:
   - For Testing: `sk_test_...` and `pk_test_...`
   - For Production: `sk_live_...` and `pk_live_...`
3. Configure `.env`:
   ```dotenv
   TAP_SECRET_KEY=sk_live_...
   TAP_PUBLIC_KEY=pk_live_...
   TAP_WEBHOOK_SECRET=your_tap_webhook_secret
   ```
4. Set Webhook in Tap Dashboard:
   - **URL**: `https://yourdomain.com/api/v1/payments/webhooks/tap`
   - **Events**: `charge.captured`, `charge.failed`, `refund.created`

#### Pre-Live Checklist for Payments:
- [ ] Test in Sandbox first using test cards before switching to live keys.
- [ ] Confirm Webhook URLs are reachable over HTTPS and return HTTP 200 to test pings.
- [ ] Verify that `FAKE_PAYMENT_GATEWAY_ENABLED=false` is set in production.
- [ ] Never commit live secret keys to Git.

---

## 14. DATABASE BACKUP & RESTORATION

1. **Automated Hostinger Backups**:
   - Hostinger takes automated daily/weekly backups available under **Files &rarr; Backups** in hPanel.
2. **Manual Pre-Update Export via CLI (SSH)**:
   ```bash
   mysqldump -u u123456789_nomowlyuser -p u123456789_nomowly > backup_$(date +%F).sql
   ```
3. **Manual Export via phpMyAdmin**:
   - Go to **Databases &rarr; phpMyAdmin &rarr; Export** &rarr; Quick &rarr; SQL format.

---

## 15. VERIFICATION & LAUNCH CHECKLIST

Open your browser and verify:
1. `https://yourdomain.com/` &rarr; Homepage loads in Arabic RTL with compiled Tailwind assets.
2. `https://yourdomain.com/login` &rarr; Login form displays with valid HTTPS action.
3. Login with your Super Admin account created in Step 7 &rarr; Admin dashboard loads (`/admin/dashboard`).
4. `https://yourdomain.com/marketplace` &rarr; Public campaign marketplace loads.
5. Create a test Brand account &rarr; Post a campaign &rarr; Confirm database persistence.

---

## 13. SAFE ROLLBACK PROCEDURE

If any step fails during launch:
1. **Maintenance Mode**:
   ```bash
   php artisan down --secret="emergency-access-token"
   ```
2. **Database Rollback**:
   ```bash
   php artisan migrate:rollback
   ```
3. **Clear Caches**:
   ```bash
   php artisan optimize:clear
   ```
4. **Restore Database Backup**:
   Use Hostinger's **Databases &rarr; phpMyAdmin &rarr; Import** to restore the previous database snapshot.
