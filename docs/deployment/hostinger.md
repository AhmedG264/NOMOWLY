# دليل نشر وتشغيل منصة نمولي على استضافة Hostinger المشتركة (PHP & MySQL)
# Nomowly Production Deployment Guide: Standard Hostinger PHP/MySQL Architecture

---

## 1. النظرة المعمارية العامة (Architecture Overview)

تعتمد منصة **نمولي (Nomowly)** المعمارية الرسمية المعتمدة للإنتاج على بيئة الاستضافة المشتركة القياسية لدى **Hostinger** بدون الحاجة لشراء خوادم VPS أو اشتراكات خارجية مدارة:

* **بيئة التشغيل الخلفية (Backend):** Laravel 12+ على PHP 8.3+
* **قاعدة البيانات (Database):** MySQL 8+ (محركات InnoDB لسلامة المعاملات المالية والعلاقات)
* **الواجهة الأمامية (Frontend):** Laravel Blade + Livewire + Tailwind CSS + Alpine.js
* **نظام الطوابير (Queue):** Laravel Database Queue (`QUEUE_CONNECTION=database`)
* **جدولة المهام (Scheduler):** Laravel Scheduler مدمج مع Hostinger Cron Jobs
* **دفتر الأستاذ المالي (Ledger):** نظام قيد مزدوج (Double-entry) يحافظ على توازن المدين والدائن
* **العملات الإقليمية (Multi-Currency):** دعم مستقل ومباشر لـ (EGP, SAR, AED, KWD, QAR, BHD, OMR) دون تحويل آلي
* **بيئة الإنتاج (Zero Production Node):** لا تتطلب بيئة الإنتاج خادم Node.js أو PM2 أو Docker أو Redis أو PostgreSQL. يُستخدم Node/Vite **فقط محلياً أثناء التطوير** لبناء الأصول الثابتة (`public/build`).

---

## 2. متطلبات استضافة Hostinger المسبقة

قبل البدء بالنشر، تحقق من تجهيز التالي داخل لوحة تحكم **hPanel**:

### أ. ضبط إصدار PHP والإضافات (PHP Configuration)
1. افتح **hPanel** وانتقل إلى **Advanced** -> **PHP Configuration**.
2. اختر إصدار **PHP 8.3** (أو **PHP 8.4**).
3. تأكد من تفعيل إضافات PHP التالية (مفعلة افتراضياً في Hostinger):
   - `pdo_mysql`
   - `mbstring`
   - `openssl`
   - `bcmath` (ضروري للعمليات الحسابية والمالية الدقيقة)
   - `ctype`
   - `json`
   - `tokenizer`
   - `xml`
   - `curl`
   - `fileinfo`
   - `intl`

### ب. إنشاء قاعدة بيانات MySQL ومستخدمها
1. في **hPanel**، انتقل إلى **Databases** -> **Management** -> **MySQL Databases**.
2. أنشئ قاعدة بيانات جديدة:
   - **اسم قاعدة البيانات (Database Name):** `uXXXX_nomowly`
   - **اسم المستخدم (Username):** `uXXXX_nomowly_usr`
   - **كلمة المرور (Password):** [كلمة مرور قوية ومؤمنة]
3. لاحظ عنوان خادم قاعدة البيانات (غالباً ما يكون `localhost` أو `127.0.0.1` على استضافة Hostinger المشتركة).

---

## 3. هيكل المجلدات على خادم Hostinger

في الاستضافة المشتركة، يكون مجلد الويب العام الافتراضي هو `/home/uXXXX/public_html`.  
لحماية كود التطبيق وملفات البيئة `.env` من أي وصول مباشر عبر المتصفح، يُتبع أحد الأسلوبين التاليين:

### الأسلوب الموصى به: توجيه Document Root في hPanel
1. ضع مجلد المشروع بالكامل في مسار مستقل: `/home/uXXXX/nomowly`.
2. في **hPanel** -> **Websites** -> **Domain Settings**، حدد **Document Root** ليشير إلى:
   ```
   /home/uXXXX/nomowly/public
   ```

### الأسلوب البديل: الربط الرمزي (Symlink) أو نقل محتويات public
إذا كانت خطتك لا تسمح بتغيير Document Root:
1. ضع كود التطبيق في `/home/uXXXX/nomowly`.
2. احذف المجلد القديم `public_html` أو أنشئ رابطاً رمزياً (Symlink):
   ```bash
   ln -s /home/uXXXX/nomowly/public /home/uXXXX/public_html
   ```
3. أو انسخ محتويات `nomowly/public/` إلى داخل `public_html/`، مع تعديل مسار `vendor/autoload.php` و `bootstrap/app.php` في ملف `index.php` ليشير إلى `../nomowly/`.

---

## 4. إعداد ملف البيئة الإنتاجي (`.env`)

أنشئ ملف `.env` في جذر مشروع Laravel (`/home/uXXXX/nomowly/.env`) بالقيم التالية:

```env
APP_NAME="منصة نمولي | Nomowly"
APP_ENV=production
APP_KEY=[GENERATE_VIA_ARTISAN_KEY_GENERATE]
APP_DEBUG=false
APP_URL=https://yourdomain.com

APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=Africa/Cairo

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

# إعدادات قاعدة بيانات MySQL 8 على Hostinger
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXX_nomowly
DB_USERNAME=uXXXX_nomowly_usr
DB_PASSWORD=YourStrongDatabasePassword2026!

# إدارة الجلسات والكاش على الاستضافة المشتركة (بدون Redis)
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=file

# إدارة الطوابير عبر قاعدة البيانات (بدون Redis أو BullMQ)
QUEUE_CONNECTION=database

# التخزين
FILESYSTEM_DISK=local

# إعدادات البريد الإلكتروني عبر Hostinger Webmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@yourdomain.com
MAIL_PASSWORD=YourMailPassword2026!
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="no-reply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# إعدادات العملات الإقليمية
DEFAULT_CURRENCY=EGP
SUPPORTED_CURRENCIES=EGP,SAR,AED,KWD,QAR,BHD,OMR
HOLDING_PERIOD_DAYS=14
MIN_WITHDRAWAL_AMOUNT=150

# حساب المشرف العام المعتمد (يُهيأ عبر php artisan nomowly:bootstrap-admin في المرحلة الثانية)
ADMIN_EMAIL=ahmedgamal264@outlook.com
ADMIN_INITIAL_PASSWORD=
```

> **ملاحظة أمنية ومعمارية هامة:**  
> المشروع **لا يحتاج ولا يستخدم** أي من:
> * `DATABASE_URL`
> * `REDIS_URL`
> * `JWT_ACCESS_SECRET`
> * `JWT_REFRESH_SECRET`

---

## 5. خطوات النشر عبر SSH أو Git

### أ. سحب الكود وتثبيت حزم Composer
اتصل بحسابك عبر SSH في Hostinger:
```bash
cd /home/uXXXX/nomowly

# تثبيت الحزم للإنتاج بدون حزم التطوير
composer install --no-dev --optimize-autoloader

# توليد مفتاح التطبيق (إذا لم يكن مولداً مسبقاً)
php artisan key:generate --force
```

### ب. تطبيق هجرات قاعدة البيانات (Migrations)
```bash
# تطبيق جداول قاعدة البيانات بما فيها جداول users, sessions, cache, jobs
php artisan migrate --force
```

### ج. بناء الأصول الثابتة (Frontend Assets)
تُبنى ملفات الـ CSS والـ JS محلياً أو في مسار CI/CD ثم تُرفع جاهزة إلى مجلد `public/build`:
```bash
# محلياً على جهاز التطوير:
npm run build
# يتم رفع مجلد public/build إلى الخادم تلقائياً
```

### د. ضبط أذونات المجلدات (Permissions)
تأكد من أن مجلدات الكاش والتخزين قابلة للكتابة من قِبل سيرفر الويب:
```bash
chmod -R 775 storage bootstrap/cache
```

### هـ. تسريع واستقرار أداء Laravel على الاستضافة المشتركة
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 6. إعداد الجدولة وطوابير الانتظار عبر Hostinger Cron Jobs

نظراً لأن الاستضافات المشتركة تمنع تشغيل عمليات خلفية مستمرة (Daemons) كـ `queue:work` المستمر، توفر منصة نمولي حلاً معمارياً متكاملاً يتوافق مع **Hostinger Cron Jobs**:

### إعداد Cron Job الأساسي لمجدول مهام Laravel (Scheduler)
في **hPanel** -> **Advanced** -> **Cron Jobs**:
1. حدد التكرار: **Every Minute** (`* * * * *`).
2. أدخل الأمر التالي (مع استبدال `uXXXX` باسم حسابك):
   ```bash
   /usr/bin/php8.3 /home/uXXXX/nomowly/artisan schedule:run >> /dev/null 2>&1
   ```

### كيف تتم معالجة طوابير قاعدة البيانات (Database Queue)؟
تمت برمجة `routes/console.php` ليقوم المجدول تلقائياً بتشغيل معالجة الطابور كل دقيقة:
```bash
php artisan queue:work database --stop-when-empty --max-time=50 --tries=3
```
هذا النمط المعماري:
1. يعالج كل المهام المتراكمة في جدول `jobs`.
2. يتوقف فور فراغ الطابور أو بعد 50 ثانية كحد أقصى (قبل دورة الـ Cron التالية).
3. يمنع تضارب العمليات (`withoutOverlapping`).
4. لا يخالف سياسات استضافة Hostinger المشتركة ولا يستهلك ذاكرة الخادم بدون داعٍ.

---

## 7. فحص السلامة بعد النشر (Health Verification)

بعد اكتمال النشر، يمكنك التحقق الفوري من سلامة الخادم والتهيئة بتشغيل الأمر:
```bash
php artisan nomowly:health
```

المخرجات المتوقعة:
```
=== Nomowly Platform Health Check ===
Laravel Version: 12.x
PHP Version: 8.3.x
Environment: production
Default DB Connection: mysql
Queue Connection: database
Default Currency: EGP
Supported Currencies: EGP, SAR, AED, KWD, QAR, BHD, OMR
Hostinger Shared Hosting Compatibility: Ready
```

---

## 8. خطة النسخ الاحتياطي واستعادة البيانات (Backup & Recovery)

### أ. النسخ الاحتياطي لقاعدة البيانات
1. **النسخ التلقائي في Hostinger:** توفر Hostinger نسخاً احتياطياً يومياً تلقائياً في **hPanel** -> **Files** -> **Backups**.
2. **النسخ اليدوي / المجدول عبر Cron:**
   يمكنك جدولة أمر `mysqldump` يومي عبر Cron Job:
   ```bash
   mysqldump -u uXXXX_nomowly_usr -p'YourPassword' uXXXX_nomowly | gzip > /home/uXXXX/backups/db_$(date +\%F).sql.gz
   ```

### ب. استعادة البيانات في حالات الطوارئ
1. عبر **hPanel** -> **Databases** -> **phpMyAdmin**: استيراد أحدث ملف SQL.
2. أو عبر سطر أوامر SSH:
   ```bash
   gunzip < /home/uXXXX/backups/db_backup.sql.gz | mysql -u uXXXX_nomowly_usr -p'YourPassword' uXXXX_nomowly
   ```
3. تنظيف الكاش بعد الاستعادة:
   ```bash
   php artisan cache:clear
   php artisan config:cache
   ```
