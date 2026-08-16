<?php
// ---------------------------------------------------------------
// Expandable Collection site configuration
// ---------------------------------------------------------------

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'strideco');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site
define('SITE_NAME', 'Expandable Collection');
define('STORE_CURRENCY_SYMBOL', '₦');
define('SHIPPING_FEE', 2500.00);
// URL path prefix under the web root (no trailing slash). Change this if you
// rename the project folder inside htdocs.
define('BASE_URL', '/strideco');

// Paystack (test mode). Get your test keys from https://dashboard.paystack.com
// -> Settings -> API Keys & Webhooks, then replace the placeholders below.
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

// Outgoing email (SMTP). Ships with placeholders so the site works without email
// configured (send_email() just logs and returns false). To enable real delivery,
// fill in real SMTP credentials — e.g. a Gmail account with an "App Password"
// (https://myaccount.google.com/apppasswords), or a transactional provider like
// Mailtrap (for testing) / SendGrid / Brevo (for production).
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SMTP_PASSWORD', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('SMTP_FROM_EMAIL', 'orders@expandablecollection.example');
define('SMTP_FROM_NAME', SITE_NAME);

// Force HTTPS in production. Leave false for local XAMPP development (no SSL
// cert configured on localhost); set to true once the site has a real
// certificate and is served over https://.
define('FORCE_HTTPS', false);

// Path to mysqldump.exe, used by cron/backup-database.php. Default matches a
// standard XAMPP install; change it if yours lives elsewhere.
define('MYSQLDUMP_BIN', 'C:\\xampp\\mysql\\bin\\mysqldump.exe');
define('BACKUP_RETENTION_DAYS', 14);

date_default_timezone_set('Africa/Lagos');
