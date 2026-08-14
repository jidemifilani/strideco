# StrideCo — Shoe Store

A full PHP + MySQL e-commerce site: product catalog (with color variants), cart, checkout with
Paystack payment (plus gift cards and shipping/tax), customer accounts, wishlists, reviews,
coupons, real-time order tracking, refunds, transactional email, a REST API, and a full admin
panel.

## Requirements

Already installed and used to build this: **XAMPP** (Apache + PHP 8.2 + MariaDB), at `C:\xampp`,
plus **Composer** (used for PHPMailer and the test suite — already installed at
`C:\ProgramData\ComposerSetup\bin\composer.bat`).

## Running the site

1. Open the **XAMPP Control Panel** and make sure **Apache** and **MySQL** are both running
   (Apache is already running from setup; if you ever restart your PC, start them from there).
2. Visit **http://localhost/strideco/**

The database (`strideco`) has already been created and seeded with 4 categories, 19 sample
products, a sample `WELCOME10` coupon, and a default shipping zone. If you ever need to re-import
everything from scratch:

```
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\strideco\database\strideco.sql
```

This is safe to re-run — it drops nothing else, just (re)creates the `strideco` database with the
full current schema.

Dependencies (PHPMailer, PHPUnit) are managed by Composer and already installed in `vendor/` — if
that folder is ever missing, restore it with:

```
C:\ProgramData\ComposerSetup\bin\composer.bat install
```

## What's included

**Storefront**
- Product catalog with category, price-range, and rating filters, plus search
- Product pages with image galleries, **color variants** (swatches that swap the photo and
  available sizes), star ratings, stock-urgency messages, and New/Bestseller badges
- Quick View modal — preview and add to cart without leaving the grid (products with color
  variants link through to the full page instead, so stock shown is always accurate)
- Session-based cart and wishlist (wishlist syncs to your account once you log in)
- Customer accounts — register/login, saved delivery details, order history, password change
- Product reviews (customers submit, admin approves before they go live)
- Coupon codes and **gift card** redemption at checkout (an order fully covered by a gift card
  skips Paystack entirely)
- **Zone-based shipping** and configurable **tax** — the delivery fee shown updates live as the
  customer types their city
- Checkout via Paystack, with guest or logged-in checkout
- **Transactional email** — order confirmation, shipped/delivered/cancelled updates, and refund
  notices (once SMTP is configured — see below)
- Public order tracking (by order reference + email), live-updating status timeline, and a
  printable invoice
- **Abandoned-cart recovery** — a reminder email with a one-click "restore my cart" link, if a
  customer starts checkout but never finishes
- Contact form and newsletter signup
- FAQ, About, Privacy Policy, Terms of Service, Refund Policy, and custom 404 pages
- Toast notifications, honeypot spam protection, login rate-limiting/lockout
- A public **REST API** (`/api/`) for products and categories

**Admin panel** (`/admin/`)
- Dashboard with revenue chart (last 14 days), order stats, and low-stock alerts
- Products — add/edit/delete, per-size stock, main photo + photo gallery, **color variants** (each
  with its own photo and per-size stock)
- Categories — add/edit/delete
- Orders — view, update fulfilment status, **process refunds** (via Paystack if configured, or
  recorded manually), export to CSV
- Coupons — add/edit/delete discount codes
- **Gift Cards** — issue store-credit codes, view balances, enable/disable
- **Shipping & Tax** — delivery-fee zones by city/state, plus a site-wide tax rate
- **Abandoned Carts** — see who started checkout but didn't finish
- Reviews — approve/reject customer submissions
- Messages — contact form inbox + newsletter subscriber list (with CSV export)
- Theme & Content — super admins can change the site name, accent color, homepage hero copy, and
  contact/social details without touching code (see "Theme & content settings" below)
- Admin Users — super admins can add/remove other admin accounts (staff or super admin role)

## Admin panel login

URL: **http://localhost/strideco/admin/login.php**

- Username: `admin`
- Password: `Admin@Stride2026`

**Change this password before letting anyone else near the site.** Easiest way:

```
C:\xampp\php\php.exe -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"
```

Then update it in phpMyAdmin (http://localhost/phpmyadmin, `strideco` → `admins` table) or via:

```sql
UPDATE admins SET password_hash = '<paste the hash above>' WHERE username = 'admin';
```

## Setting up real payments (Paystack)

Checkout is wired to Paystack but ships with placeholder API keys, so payment won't actually go
through yet — customers who reach checkout will see a friendly "payment isn't set up" message,
and their order is still saved so nothing is lost. **This is the one thing in this README that
needs your own action** — creating a Paystack account and completing business verification isn't
something that can be done on your behalf.

To enable it:
1. Create a free account at https://dashboard.paystack.com (test mode needs no approval).
2. Go to **Settings → API Keys & Webhooks** and copy your **Test Secret Key** and **Test Public Key**.
3. Open [config/config.php](config/config.php) and replace:
   ```php
   define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
   define('PAYSTACK_PUBLIC_KEY', 'pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
   ```
   with your real test keys.
4. Test card payments work with Paystack's documented test cards (e.g. `4084 0840 8408 4081`,
   any future expiry, CVV `408`, PIN `0000`, OTP `123456`).
5. When you're ready to accept real money, switch to your **live** keys (same place in the
   Paystack dashboard) and go through their business verification.

Refunds (**Admin → Orders → Refund this order**) use the same keys automatically — see "Refunds"
below.

## Transactional email

Order confirmation, shipping/delivery/cancellation updates, refund notices, and abandoned-cart
reminders are all wired up in code (via [PHPMailer](https://github.com/PHPMailer/PHPMailer)), but
ship with placeholder SMTP credentials — like Paystack, emails are safely skipped (logged, not
sent) until you configure a real account, so nothing breaks in the meantime.

To enable it, open [config/config.php](config/config.php) and fill in:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'yourshop@gmail.com');
define('SMTP_PASSWORD', 'your-16-character-app-password');
define('SMTP_FROM_EMAIL', 'yourshop@gmail.com');
define('SMTP_FROM_NAME', 'StrideCo');
```
A Gmail account with an [App Password](https://myaccount.google.com/apppasswords) works well for
testing; for production sending, a transactional provider (SendGrid, Brevo, Mailtrap, Amazon SES)
is more reliable at scale and less likely to be marked as spam.

## Store settings

Edit [config/config.php](config/config.php) to change:
- `SITE_NAME` — shown throughout the site (or override live from **Admin → Theme & Content**)
- `SHIPPING_FEE` — fallback delivery fee used only if no shipping zones are configured (see below)
- `BASE_URL` — only needed if you rename the `strideco` folder
- `FORCE_HTTPS` — set to `true` once the site has a real SSL certificate in production (leave
  `false` for local XAMPP, which has no cert)
- `MYSQLDUMP_BIN` / `BACKUP_RETENTION_DAYS` — used by the backup script (see "Backups" below)

The free-shipping threshold (default ₦50,000) is the `FREE_SHIPPING_THRESHOLD` constant in
[includes/functions.php](includes/functions.php) — change it in that one place and it updates
everywhere (cart, checkout, product page, FAQ, homepage, and the "add ₦X more for free shipping"
nudge shown in the cart and checkout summaries).

## Product color variants

Products can optionally have color variants — go to **Admin → Products → (edit a product) →
Color variants** to add one. Each variant has its own name, swatch color, optional photo, and its
own per-size stock, independent of the product's base "Sizes & stock" grid.

A product with **no** variants behaves exactly as before (single color from the product's "Color"
text field, stock from the base grid) — this is fully backward-compatible and safe for all
existing products. Once a product **has** variants, the product page shows color swatches;
selecting one swaps the photo and re-checks which sizes are in stock for that color, without a
page reload.

## Shipping zones & tax

**Admin → Shipping & Tax** replaces the old flat delivery fee with per-region pricing:
- Add zones by city/state (comma-separated, case-insensitive match against the customer's City
  field at checkout) with their own fee, and mark one zone as the default (catch-all) for
  everywhere else.
- Set a site-wide **tax rate** (%), applied to (subtotal − discount). Leave it at 0% to charge no
  tax.

The delivery fee and tax shown at checkout update live as the customer types their city
(`shipping-estimate.php`, polled via JS) — the final amount is always recalculated authoritatively
server-side in `place-order.php`, never trusted from the browser.

If no shipping zones exist at all (shouldn't happen — one is seeded by default), checkout falls
back to the flat `SHIPPING_FEE` constant in `config/config.php`.

## Gift cards

**Admin → Gift Cards** lets you issue store-credit codes (e.g. for promotions or goodwill credit)
with a starting balance, optional recipient email, and optional expiry date. Customers redeem a
code at checkout the same way they apply a coupon — the balance is deducted from the order total,
and if a gift card fully covers the order, checkout skips Paystack entirely and the order is
confirmed immediately.

Gift cards aren't (yet) purchasable by customers as a storefront product — they're issued directly
by an admin. See "Ideas for later" if you want to extend this.

## Refunds

From **Admin → Orders → (view an order)**, a **paid** order can be refunded in full via the
"Refund this order" panel. If real Paystack keys are configured, this calls Paystack's refund API
(the transaction is refunded to the customer's original payment method — Paystack processes this
asynchronously on their end). If Paystack isn't configured, the refund is still recorded locally
(e.g. for a shop owner refunding by bank transfer) so nothing is silently lost. Either way, the
order's payment status becomes `refunded`, the event is logged to the order's timeline, and — once
SMTP is configured — the customer gets a refund confirmation email.

## Managing coupons

Go to **Admin → Coupons** to add discount codes — percentage or fixed amount, with an optional
minimum order value, usage limit, and expiry date. A sample code `WELCOME10` (10% off orders over
₦20,000) is included; edit or delete it from the same screen.

## Managing reviews

Customer reviews are held as **pending** until approved, so nothing appears on your product pages
without a look first. Go to **Admin → Reviews** to approve or reject them.

## Theme & content settings

**Admin → Theme & Content** (super admins only) lets you change, without editing code:
- **Branding** — site name and accent color (a color picker synced to a hex input; every shade
  used across the site — buttons, prices, hover tints — is derived from this one color)
- **Homepage hero** — the eyebrow tag, headline, headline highlight, subtext, and both button
  labels
- **Contact & social** — the email/phone/address shown in the footer and Contact page, plus
  Instagram/X/TikTok links (social icons only link out once a URL is filled in)

Changes save to the `settings` table and take effect immediately on the next page load — no
restart needed.

## Order tracking & automation

**Real-time tracking**: `track-order.php` and the customer's own order-detail page show a visual
status timeline (placed → paid → processing → shipped → delivered/refunded, or
cancelled/payment-failed) and poll `order-status.php` every 20 seconds via `fetch()` to reflect
status changes — including ones the admin makes — without the customer needing to refresh. Admins
can attach an optional note (e.g. a courier tracking number) whenever they update an order's
status from **Admin → Orders**, and it shows up in the timeline.

**Paystack webhook**: in addition to the redirect-based confirmation customers see after paying,
`paystack/webhook.php` is a server-to-server endpoint Paystack can call directly — more reliable
if a customer closes their browser mid-redirect. It verifies Paystack's HMAC-SHA512 signature
before trusting anything, and it's safe to receive alongside the redirect flow (whichever arrives
first marks the order paid; the other is a no-op). To activate it in production: in the Paystack
dashboard, go to **Settings → API Keys & Webhooks** and set the webhook URL to
`https://yourdomain.com/strideco/paystack/webhook.php`. It's inert on localhost — Paystack's
servers can't reach your machine — so nothing to configure for local testing.

**Abandoned-order cleanup**: orders left `pending` for 24+ hours (customer started checkout but
never completed payment) are automatically marked `failed` so they stop cluttering the "pending"
view and stock isn't held against them forever.

**Abandoned-cart recovery**: separately from orders, if a customer types their email at checkout
but never places the order at all, a snapshot of their cart is saved. After 2+ hours with no
activity, they get a reminder email with a "restore my cart" link that brings back exactly what
they had (re-checking stock at restore time, in case something sold out).

Both of the above run opportunistically (at most once an hour) whenever an admin loads the
dashboard, so they work with zero setup — but for a production site, schedule these to run hourly
instead:
```
C:\xampp\php\php.exe C:\xampp\htdocs\strideco\cron\cleanup-orders.php
C:\xampp\php\php.exe C:\xampp\htdocs\strideco\cron\send-abandoned-cart-emails.php
```
via Windows Task Scheduler (or cron, on Linux hosting). Both scripts are CLI-only — the `cron/`
folder blocks web access via `.htaccess`, and each script also refuses to run outside a CLI
context.

## Backups

`cron/backup-database.php` dumps the full database to `backups/` (blocked from web access via
`.htaccess`) via `mysqldump`, then deletes dumps older than `BACKUP_RETENTION_DAYS` (default 14).
Unlike the automations above, this has **no opportunistic fallback** — a backup only matters if it
runs reliably, so schedule it for real:
```
C:\xampp\php\php.exe C:\xampp\htdocs\strideco\cron\backup-database.php
```
daily via Windows Task Scheduler (e.g. 3am). If your XAMPP install lives somewhere other than
`C:\xampp`, update `MYSQLDUMP_BIN` in `config/config.php` to match.

## HTTPS in production

Leave `FORCE_HTTPS` as `false` in `config/config.php` for local development — localhost has no SSL
certificate. Once you deploy with a real certificate, set it to `true`; every request over plain
HTTP will then 301-redirect to `https://`.

## Automated tests

Two layers, both installed via Composer:
- **Unit tests** (`tests/HelperFunctionsTest.php`, via PHPUnit) — cover pure logic in
  `includes/functions.php` (pricing, slugs, cart keys, color math, etc.) with no database needed:
  ```
  C:\xampp\php\php.exe vendor\bin\phpunit
  ```
- **Integration smoke test** (`tests/smoke-test.php`) — hits the running site over HTTP and checks
  that key public pages, the REST API, and admin access-control all behave correctly. Requires
  Apache + MySQL running:
  ```
  C:\xampp\php\php.exe tests\smoke-test.php
  ```

Neither suite touches real customer data — they create and clean up their own test records where
needed (or, for the smoke test, only read existing pages/state).

## REST API

A public, read-only, unauthenticated JSON API under `/api/` — useful if you ever build a mobile
app or another client. It exposes only what's already public on the storefront (active products,
categories, stock, ratings), so it's the same trust level as the website itself; there are no
write endpoints and no customer data.

- `GET /api/products.php` — list active products. Query params: `category=<slug>`,
  `search=<term>`, `page=<n>`, `per_page=<n, max 50>`.
- `GET /api/product.php?slug=<slug>` — full detail for one product (description, features, sizes,
  color variants, gallery, rating).
- `GET /api/categories.php` — all categories with their active product counts.

Every response is `{"ok": true, "data": ...}` (plus `"meta"` for pagination) or
`{"ok": false, "error": "..."}` with an appropriate HTTP status code. CORS is open (`GET`/`OPTIONS`
only) since this is public catalog data.

## Project structure

```
strideco/
├── index.php, shop.php, product.php       Storefront pages
├── cart.php, cart-actions.php             Session-based cart (restores from an abandoned-cart link too)
├── wishlist.php, wishlist-actions.php     Wishlist (session + account-synced)
├── checkout.php, place-order.php          Checkout → order creation (shipping/tax/gift card math)
├── shipping-estimate.php                  JSON endpoint for the live shipping/tax preview at checkout
├── apply-coupon.php, apply-giftcard.php   Coupon / gift card redemption at checkout
├── capture-abandoned-cart.php             Fire-and-forget cart snapshot, called on checkout email blur
├── review-actions.php                     Customer review submission
├── quick-view.php                         Quick View modal content (fetched via JS)
├── track-order.php, invoice.php           Public order tracking + printable invoice
├── order-status.php                       JSON endpoint polled for live order status updates
├── contact.php, faq.php, about.php        Site pages
├── privacy-policy.php, terms.php,         Legal pages (template content — see the note in each,
│   refund-policy.php                        have them reviewed before relying on them commercially)
├── newsletter-subscribe.php               Footer newsletter signup handler
├── order-success.php, order-failed.php    Post-payment result pages
├── api/                                   Public REST API (products, categories)
├── account/                               Customer accounts (register/login/profile/orders)
├── paystack/                              Paystack API integration + webhook.php (server-to-server)
├── cron/                                  CLI-only scheduled scripts (web access blocked):
│                                             cleanup-orders.php, send-abandoned-cart-emails.php,
│                                             backup-database.php
├── backups/                               mysqldump output from cron/backup-database.php (web access blocked)
├── admin/                                 Admin panel (products, orders, coupons, reviews, settings, etc.)
├── includes/                              Shared DB connection, helpers, header/footer
├── config/config.php                      Site settings, DB/Paystack/SMTP credentials
├── database/strideco.sql                  Full schema + seed data
├── database/upgrade_v2.sql,               Incremental upgrade scripts (already applied — kept for
│   upgrade_v3.sql                            reference/re-run safety, folded into strideco.sql too)
├── tests/                                 PHPUnit unit tests + the integration smoke test
├── vendor/                                Composer dependencies (PHPMailer, PHPUnit) — web access blocked
├── assets/                                CSS, JS, and uploaded product photos
├── robots.php, sitemap.php                Dynamic robots.txt / sitemap.xml (via .htaccess rewrite)
└── image.php                              Generates placeholder product art (used as a fallback
                                            when a product has no uploaded photo)
```

## Notes on the product photos

The 19 sample products ship with real stock photography (sourced from Unsplash, free for
commercial use) so the store looks finished out of the box. **Caveat**: real product photography
inevitably shows real brand logos (Nike, Adidas, Puma, Converse, TOMS appear across these photos)
— fine for a demo/placeholder catalog, but swap in your own photography (or licensed unbranded
photos) before any real commercial launch, to avoid trademark/brand-representation concerns. This
is the other thing in this README that needs your own action — there's no way to generate better
placeholder photos than real stock photography without an actual photoshoot or an artist-made
asset. `image.php` still generates a placeholder badge as a fallback for any product without an
uploaded photo. When you add or edit a product in the admin panel, you can upload a real
JPG/PNG/WEBP main photo, plus additional gallery photos or short videos (MP4/WEBM/MOV, up to
20MB) shown as thumbnails on the product page.

## Security notes

- All database queries use prepared statements.
- All forms that write data are CSRF-protected; public forms (register, contact, newsletter,
  reviews) also carry an invisible honeypot field against basic spam bots.
- Passwords are hashed with bcrypt; both admin and customer logins lock out for 15 minutes after
  5 failed attempts.
- Product review submissions require admin approval before they're shown publicly.
- `vendor/`, `tests/`, `cron/`, and `backups/` are all blocked from direct web access via
  `.htaccess` — none of them are meant to be browsed.
- The `strideco` MySQL user is `root` with no password, which is the standard local XAMPP
  default — fine for local development, but if you ever deploy this to a public server, create a
  dedicated MySQL user with a real password and update `config/config.php`.

## Ideas for later

This now covers the vast majority of what a small shoe store needs to run — including everything
that used to be listed here (transactional email, product variants, shipping zones/tax, refunds,
gift cards, abandoned-cart recovery, and a REST API). What's genuinely still missing, roughly in
order of likely usefulness:
- Gift cards purchasable by customers as a storefront product (currently admin-issued only)
- Partial refunds (currently full-refund only)
- True product variants for size *and* color combined per-variant (currently: color variants each
  have their own size/stock grid, which covers the common case, but a variant's photo can't differ
  by size)
- Multi-currency — deliberately skipped; StrideCo is a single-currency (₦) Nigerian store with no
  real exchange-rate data source, so this would be unused complexity until that changes
- Shipping zones matched by delivery address structured fields (state/LGA) rather than a free-text
  city match
- A `refund.processed` Paystack webhook listener, to confirm a refund actually completed (not just
  that Paystack accepted the request) — mirrors how payment confirmation is double-checked
