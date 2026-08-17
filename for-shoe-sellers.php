<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sell Shoes Online — A Complete Store Platform for Shoe Sellers</title>
<meta name="description" content="A complete, ready-to-launch e-commerce platform built specifically for shoe retailers — catalog, secure checkout, order tracking, and a full admin dashboard.">
<link rel="icon" href="<?= base_url('image.php?icon=1') ?>" type="image/svg+xml">
<style>
  @font-face {
    font-family: 'Big Shoulders Display';
    font-weight: 900;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/big-shoulders-display-900.woff2') ?>') format('woff2');
  }
  @font-face {
    font-family: 'IBM Plex Sans';
    font-weight: 400;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/plex-sans-400.woff2') ?>') format('woff2');
  }
  @font-face {
    font-family: 'IBM Plex Sans';
    font-weight: 600;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/plex-sans-600.woff2') ?>') format('woff2');
  }
  @font-face {
    font-family: 'IBM Plex Sans';
    font-weight: 700;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/plex-sans-700.woff2') ?>') format('woff2');
  }
  @font-face {
    font-family: 'IBM Plex Mono';
    font-weight: 400;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/plex-mono-400.woff2') ?>') format('woff2');
  }
  @font-face {
    font-family: 'IBM Plex Mono';
    font-weight: 500;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/plex-mono-500.woff2') ?>') format('woff2');
  }
  @font-face {
    font-family: 'IBM Plex Mono';
    font-weight: 600;
    font-style: normal;
    font-display: swap;
    src: url('<?= base_url('assets/fonts/plex-mono-600.woff2') ?>') format('woff2');
  }

  :root {
    --paper: #EDE9DF;
    --paper-raised: #F8F5EC;
    --ink: #1C1916;
    --ink-soft: #5C564C;
    --ink-faint: #8C8577;
    --accent: #FF5A1F;
    --accent-deep: #B93D0C;
    --line: #D8D0BF;
    --line-strong: #C3BAA5;
    --rubber: #201F1B;
    --rubber-ink: #EDE9DF;
    --rubber-line: #3A372F;
    --focus: #1C1916;

    --font-display: 'Big Shoulders Display', 'Arial Narrow', sans-serif;
    --font-body: 'IBM Plex Sans', -apple-system, sans-serif;
    --font-mono: 'IBM Plex Mono', ui-monospace, monospace;
  }

  @media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]) {
      --paper: #171410;
      --paper-raised: #201C16;
      --ink: #EDE9DF;
      --ink-soft: #B5AC9B;
      --ink-faint: #857F70;
      --accent: #FF7440;
      --accent-deep: #FFB088;
      --line: #35302A;
      --line-strong: #45403790;
      --rubber: #0E0D0B;
      --rubber-ink: #EDE9DF;
      --rubber-line: #322E27;
      --focus: #EDE9DF;
    }
  }
  :root[data-theme="dark"] {
    --paper: #171410;
    --paper-raised: #201C16;
    --ink: #EDE9DF;
    --ink-soft: #B5AC9B;
    --ink-faint: #857F70;
    --accent: #FF7440;
    --accent-deep: #FFB088;
    --line: #35302A;
    --line-strong: #45403790;
    --rubber: #0E0D0B;
    --rubber-ink: #EDE9DF;
    --rubber-line: #322E27;
    --focus: #EDE9DF;
  }

  * { box-sizing: border-box; }
  html { -webkit-text-size-adjust: 100%; }
  body {
    margin: 0;
    background: var(--paper);
    color: var(--ink);
    font-family: var(--font-body);
    font-size: 16px;
    line-height: 1.55;
    -webkit-font-smoothing: antialiased;
  }
  ::selection { background: var(--accent); color: #fff; }
  a { color: inherit; }
  :focus-visible { outline: 2px solid var(--focus); outline-offset: 3px; }
  img { max-width: 100%; display: block; }

  .wrap { max-width: 980px; margin: 0 auto; padding: 0 28px; }

  h1, h2, h3 {
    font-family: var(--font-display);
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.005em;
    text-wrap: balance;
    margin: 0;
    color: var(--ink);
  }

  .mono-label {
    font-family: var(--font-mono);
    font-size: 0.72rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--ink-faint);
  }

  .top-strip {
    border-bottom: 1px solid var(--line);
    padding: 14px 0;
  }
  .top-strip .wrap {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
  }
  .top-strip .mono-label span { color: var(--ink-soft); }
  .top-strip a.back {
    font-family: var(--font-mono);
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ink-soft);
    text-decoration: none;
  }
  .top-strip a.back:hover { color: var(--accent); }

  .hero { padding: 76px 0 64px; }
  .hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 26px;
  }
  .hero-eyebrow .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); }
  .hero h1 {
    font-size: clamp(2.6rem, 6.4vw, 4.6rem);
    line-height: 0.96;
    max-width: 15ch;
  }
  .hero h1 em {
    font-style: normal;
    color: var(--accent);
  }
  .hero-sub {
    max-width: 52ch;
    font-size: 1.14rem;
    color: var(--ink-soft);
    margin-top: 24px;
  }

  .spec-plate {
    margin-top: 44px;
    border: 1px solid var(--line-strong);
    background: var(--paper-raised);
    border-radius: 4px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
  }
  .spec-plate .cell {
    padding: 16px 18px;
    border-right: 1px solid var(--line);
  }
  .spec-plate .cell:last-child { border-right: none; }
  .spec-plate .cell .k {
    font-family: var(--font-mono);
    font-size: 0.66rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--ink-faint);
    margin-bottom: 6px;
  }
  .spec-plate .cell .v {
    font-family: var(--font-mono);
    font-size: 0.88rem;
    font-weight: 500;
    color: var(--ink);
    font-variant-numeric: tabular-nums;
  }

  .perf {
    height: 1px;
    background-image: repeating-linear-gradient(to right, var(--line-strong) 0 6px, transparent 6px 13px);
    margin: 0;
  }

  section.block { padding: 68px 0; }
  .block-head { margin-bottom: 40px; max-width: 62ch; }
  .block-head h2 { font-size: clamp(1.9rem, 3.6vw, 2.6rem); line-height: 1.02; margin-top: 10px; }
  .block-head p { color: var(--ink-soft); margin-top: 14px; font-size: 1.02rem; max-width: 56ch; }

  .pain-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1px;
    background: var(--line);
    border: 1px solid var(--line);
  }
  .pain-item {
    background: var(--paper);
    padding: 24px 26px;
  }
  .pain-item .num {
    font-family: var(--font-mono);
    font-size: 0.78rem;
    color: var(--accent-deep);
    font-variant-numeric: tabular-nums;
  }
  .pain-item p {
    margin: 10px 0 0;
    font-size: 1rem;
    color: var(--ink);
  }
  .pain-item p.reframe { color: var(--ink-soft); font-size: 0.94rem; margin-top: 6px; }

  .module {
    border-top: 1px solid var(--line-strong);
    padding: 34px 0;
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 32px;
  }
  .module:last-child { border-bottom: 1px solid var(--line-strong); }
  .module-code {
    font-family: var(--font-mono);
    font-size: 0.74rem;
    letter-spacing: 0.1em;
    color: var(--accent-deep);
    font-variant-numeric: tabular-nums;
  }
  .module-title {
    font-family: var(--font-display);
    font-weight: 900;
    text-transform: uppercase;
    font-size: 1.5rem;
    line-height: 1.05;
    margin-top: 8px;
  }
  .module ul {
    list-style: none;
    margin: 0 0 16px;
    padding: 0;
    display: grid;
    gap: 9px;
  }
  .module li {
    position: relative;
    padding-left: 18px;
    font-size: 0.97rem;
  }
  .module li::before {
    content: '';
    position: absolute;
    left: 0; top: 0.55em;
    width: 6px; height: 6px;
    background: var(--accent);
  }
  .module .benefit {
    font-family: var(--font-mono);
    font-size: 0.84rem;
    color: var(--ink-soft);
    border-left: 2px solid var(--accent);
    padding-left: 12px;
  }

  .size-band {
    background: var(--rubber);
    color: var(--rubber-ink);
    padding: 60px 0;
  }
  .size-band .block-head p, .size-band .block-head h2 { color: var(--rubber-ink); }
  .size-band .mono-label { color: var(--accent); }
  .size-run {
    display: flex;
    gap: 10px;
    margin: 30px 0 34px;
    flex-wrap: wrap;
  }
  .size-run span {
    font-family: var(--font-mono);
    font-variant-numeric: tabular-nums;
    width: 46px; height: 46px;
    border: 1px solid var(--rubber-line);
    display: flex; align-items: center; justify-content: center;
    font-size: 0.92rem;
    border-radius: 3px;
    color: var(--rubber-ink);
  }
  .chip-wall {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .chip {
    font-family: var(--font-mono);
    font-size: 0.82rem;
    padding: 9px 14px;
    border: 1px solid var(--rubber-line);
    border-radius: 3px;
    color: var(--rubber-ink);
  }

  .compare {
    display: grid;
    grid-template-columns: 1fr 1fr;
    border: 1px solid var(--line-strong);
    border-radius: 4px;
    overflow: hidden;
  }
  .compare > div { padding: 28px 30px; }
  .compare .own { background: var(--paper-raised); }
  .compare .rent { background: transparent; }
  .compare h3 {
    font-size: 1.05rem;
    margin-bottom: 16px;
  }
  .compare .own h3 { color: var(--accent-deep); }
  .compare .rent h3 { color: var(--ink-faint); }
  .compare ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
  .compare li { font-size: 0.94rem; padding-left: 20px; position: relative; }
  .compare .own li::before { content: '✓'; position: absolute; left: 0; color: var(--accent); font-weight: 700; }
  .compare .rent li::before { content: '×'; position: absolute; left: 0; color: var(--ink-faint); }
  .compare .rent li { color: var(--ink-faint); }
  @media (max-width: 640px) {
    .compare { grid-template-columns: 1fr; }
    .compare .own { border-bottom: 1px solid var(--line-strong); }
  }

  .proof {
    border: 1px dashed var(--line-strong);
    padding: 28px 30px;
    border-radius: 4px;
    display: flex;
    gap: 20px;
    align-items: flex-start;
  }
  .proof .mark {
    font-family: var(--font-display);
    font-weight: 900;
    font-size: 2.2rem;
    color: var(--accent);
    line-height: 1;
  }
  .proof p { margin: 0; color: var(--ink-soft); font-size: 0.98rem; max-width: 60ch; }
  .proof strong { color: var(--ink); }

  .closing { padding: 80px 0 100px; display: flex; justify-content: center; }
  .hangtag {
    position: relative;
    background: var(--paper-raised);
    border: 1px solid var(--line-strong);
    border-radius: 10px 40px 10px 10px;
    padding: 40px 46px 34px 54px;
    max-width: 460px;
    text-align: left;
  }
  .hangtag::before {
    content: '';
    position: absolute;
    top: 28px; right: 22px;
    width: 14px; height: 14px;
    border: 2px solid var(--ink-faint);
    border-radius: 50%;
    background: var(--paper);
  }
  .hangtag h2 {
    font-size: 1.9rem;
    max-width: 16ch;
  }
  .hangtag p {
    color: var(--ink-soft);
    font-size: 0.98rem;
    margin: 14px 0 20px;
  }
  .hangtag .plate {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-family: var(--font-mono);
    font-size: 0.78rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 12px 18px;
    background: var(--rubber);
    color: var(--rubber-ink);
    border-radius: 3px;
    text-decoration: none;
    border: 1px solid var(--rubber);
    transition: background 0.15s ease, color 0.15s ease;
  }
  .hangtag .plate:hover,
  .hangtag .plate:focus-visible {
    background: var(--accent);
    border-color: var(--accent);
    color: #fff;
  }
  .hangtag .plate svg { flex-shrink: 0; }
  .hangtag .plate-note {
    display: block;
    margin-top: 12px;
    font-family: var(--font-mono);
    font-size: 0.72rem;
    color: var(--ink-faint);
    letter-spacing: 0.04em;
  }

  footer {
    border-top: 1px solid var(--line);
    padding: 22px 0 40px;
  }
  footer .mono-label { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }

  @media (max-width: 720px) {
    .spec-plate { grid-template-columns: repeat(2, 1fr); }
    .spec-plate .cell:nth-child(2) { border-right: none; }
    .pain-grid { grid-template-columns: 1fr; }
    .module { grid-template-columns: 1fr; gap: 14px; }
  }

  @media (prefers-reduced-motion: reduce) {
    * { scroll-behavior: auto !important; }
  }
</style>
</head>
<body>

<div class="top-strip">
  <div class="wrap">
    <a href="<?= base_url('index.php') ?>" class="back">&larr; Back to the store</a>
    <span class="mono-label">REV. 2026.08</span>
  </div>
</div>

<header class="hero">
  <div class="wrap">
    <div class="hero-eyebrow mono-label"><span class="dot"></span> BUILT FOR INDEPENDENT SHOE SELLERS</div>
    <h1>Stop selling shoes from your <em>DMs.</em></h1>
    <p class="hero-sub">A complete online store — catalog, cart, secure checkout, live order tracking, and an admin dashboard you actually control. Built specifically for footwear retail, not stretched from a generic template.</p>

    <div class="spec-plate">
      <div class="cell">
        <div class="k">Style</div>
        <div class="v">Full E-Commerce Platform</div>
      </div>
      <div class="cell">
        <div class="k">Fit</div>
        <div class="v">Any Shoe Retailer</div>
      </div>
      <div class="cell">
        <div class="k">Size Run</div>
        <div class="v">38 — 45, Every Category</div>
      </div>
      <div class="cell">
        <div class="k">Status</div>
        <div class="v">Built. Tested. Live.</div>
      </div>
    </div>
  </div>
</header>

<div class="perf"></div>

<section class="block">
  <div class="wrap">
    <div class="block-head">
      <span class="mono-label">THE PROBLEM</span>
      <h2>Right now, you're leaving money in your DMs.</h2>
      <p>Selling shoes through WhatsApp status and Instagram posts works — until it doesn't. This is what it's quietly costing you.</p>
    </div>

    <div class="pain-grid">
      <div class="pain-item">
        <span class="num">01</span>
        <p>Customers can't see your full stock in one place.</p>
        <p class="reframe">They scroll old posts instead of shopping — and give up before they find what they want.</p>
      </div>
      <div class="pain-item">
        <span class="num">02</span>
        <p>No real checkout means no real payment trail.</p>
        <p class="reframe">You're manually confirming bank transfers and chasing "sent o" screenshots.</p>
      </div>
      <div class="pain-item">
        <span class="num">03</span>
        <p>Nobody can track their own order.</p>
        <p class="reframe">So they message you daily asking "is it out for delivery" — and you answer the same question fifty times.</p>
      </div>
      <div class="pain-item">
        <span class="num">04</span>
        <p>You look like a reseller, not a retailer.</p>
        <p class="reframe">And shoppers pay more, more willingly, at stores that look like they'll still be there next month.</p>
      </div>
    </div>
  </div>
</section>

<div class="perf"></div>

<section class="block">
  <div class="wrap">
    <div class="block-head">
      <span class="mono-label">THE PLATFORM</span>
      <h2>Seven modules. One store.</h2>
      <p>Everything below is already built and working — not a roadmap, not a "coming soon."</p>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 01</div>
        <div class="module-title">Storefront &amp; Catalog</div>
      </div>
      <div>
        <ul>
          <li>Full product catalog organized by category — Sneakers, Formal, Sport, Casual, or your own</li>
          <li>Multi-photo galleries with zoom, plus video support for a real walk-around view</li>
          <li>Color variants — customers pick the exact colorway, each with its own photo and its own stock</li>
          <li>Pre-order mode for stock that's arriving, so "out of stock" never has to mean "lost sale"</li>
        </ul>
        <div class="benefit">BENEFIT — Every shoe you carry, shown properly. Not buried in a camera roll.</div>
      </div>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 02</div>
        <div class="module-title">Conversion</div>
      </div>
      <div>
        <ul>
          <li>Mobile-first design with a sticky add-to-cart bar, quick-view popups, and a wishlist</li>
          <li>Live shipping estimate as a customer types their delivery city</li>
          <li>Coupon codes and gift cards to run real promotions</li>
          <li>Trust badges — secure payment, return policy — right where buyers make the decision</li>
        </ul>
        <div class="benefit">BENEFIT — The store is built to turn a visit into a completed order, not just a browse.</div>
      </div>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 03</div>
        <div class="module-title">Payments &amp; Checkout</div>
      </div>
      <div>
        <ul>
          <li>Secure checkout via Paystack — the payment processor shoppers already trust</li>
          <li>Instant, automatic payment confirmation — no manual "did they actually pay" checking</li>
          <li>Guest checkout, or full customer accounts with order history</li>
        </ul>
        <div class="benefit">BENEFIT — Money lands in your account the moment a customer pays. You don't lift a finger.</div>
      </div>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 04</div>
        <div class="module-title">Order Tracking &amp; Automation</div>
      </div>
      <div>
        <ul>
          <li>Customers track their own order live: placed, paid, processing, shipped, delivered</li>
          <li>Printable invoices, automatic email receipts, and shipping-status updates</li>
          <li>Abandoned-cart recovery — customers who didn't finish checkout get followed up automatically</li>
        </ul>
        <div class="benefit">BENEFIT — Fewer "where's my order" messages. Lost sales get a second chance, automatically.</div>
      </div>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 05</div>
        <div class="module-title">Admin Control Center</div>
      </div>
      <div>
        <ul>
          <li>Add and edit products, prices, and photos yourself — no developer required</li>
          <li>A sales dashboard, full order management, and CSV export</li>
          <li>Review moderation, coupon and gift-card management, shipping-zone and tax rules</li>
          <li>A homepage image slider and shop-by-category photos, uploaded and controlled by you</li>
          <li>Multiple staff logins, each with their own access level</li>
        </ul>
        <div class="benefit">BENEFIT — You run the whole business from one dashboard, from your phone or laptop.</div>
      </div>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 06</div>
        <div class="module-title">Trust &amp; Reach</div>
      </div>
      <div>
        <ul>
          <li>Fully branded to your business — name, colors, logo, homepage message, all editable</li>
          <li>Built-in legal pages: privacy policy, terms, and a clear returns policy</li>
          <li>One-tap sharing to WhatsApp, Facebook, X, Pinterest, and Telegram on every product</li>
          <li>A native share button that reaches TikTok and Instagram directly on mobile</li>
        </ul>
        <div class="benefit">BENEFIT — Customers do your marketing for you, one shared link at a time.</div>
      </div>
    </div>

    <div class="module">
      <div>
        <div class="module-code">MODULE 07</div>
        <div class="module-title">Security &amp; Reliability</div>
      </div>
      <div>
        <ul>
          <li>Encrypted checkout and a protected admin login that locks out repeated failed attempts</li>
          <li>Automatic database backups</li>
          <li>A maintenance mode with your own message and reopening date — instead of a broken-looking store while you restock</li>
        </ul>
        <div class="benefit">BENEFIT — Peace of mind. Your store, your customers' data, and your sales history are protected.</div>
      </div>
    </div>
  </div>
</section>

<section class="size-band">
  <div class="wrap">
    <div class="block-head">
      <span class="mono-label">COVERAGE</span>
      <h2>Nothing half-built.</h2>
      <p>The way a real size run has no gaps between 38 and 45, this platform has no gaps between "browse" and "delivered."</p>
    </div>
    <div class="size-run">
      <span>38</span><span>39</span><span>40</span><span>41</span><span>42</span><span>43</span><span>44</span><span>45</span>
    </div>
    <div class="chip-wall">
      <span class="chip">Cart</span>
      <span class="chip">Wishlist</span>
      <span class="chip">Coupons</span>
      <span class="chip">Gift Cards</span>
      <span class="chip">Reviews</span>
      <span class="chip">Shipping Zones</span>
      <span class="chip">Tax Rules</span>
      <span class="chip">Refunds</span>
      <span class="chip">Order Tracking</span>
      <span class="chip">Abandoned-Cart Recovery</span>
      <span class="chip">Email Notifications</span>
      <span class="chip">Sales Dashboard</span>
      <span class="chip">Multi-Staff Logins</span>
      <span class="chip">SEO Sitemap</span>
      <span class="chip">Mobile-First Design</span>
      <span class="chip">Automated Backups</span>
    </div>
  </div>
</section>

<section class="block">
  <div class="wrap">
    <div class="block-head">
      <span class="mono-label">OWN VS. RENT</span>
      <h2>Buy it once. Own it outright.</h2>
    </div>
    <div class="compare">
      <div class="own">
        <h3>This platform</h3>
        <ul>
          <li>No monthly platform fee eating into every sale</li>
          <li>Full source code and full database — it's yours</li>
          <li>Built specifically around shoe retail: sizes, colorways, stock per size</li>
          <li>Runs on affordable, standard hosting</li>
        </ul>
      </div>
      <div class="rent">
        <h3>Typical subscription store-builder</h3>
        <ul>
          <li>Monthly fee for as long as you sell</li>
          <li>You're renting — cancel, and the store is gone</li>
          <li>Generic template stretched to fit footwear</li>
          <li>Locked to their infrastructure and pricing</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<div class="perf"></div>

<section class="block">
  <div class="wrap">
    <div class="block-head">
      <span class="mono-label">PROOF</span>
      <h2>Not a concept. A working store.</h2>
    </div>
    <div class="proof">
      <div class="mark">✓</div>
      <p><strong>This isn't a mockup.</strong> It's a live, tested platform — real checkout flow, real order tracking, a real admin dashboard, deployed and running in production right now (the store you're browsing from is it).</p>
    </div>
  </div>
</section>

<section class="closing">
  <div class="hangtag">
    <span class="mono-label">READY WHEN YOU ARE</span>
    <h2>Open your store.</h2>
    <p>Everything on this sheet, built around your brand, your products, and your customers.</p>
    <a class="plate" href="https://wa.me/2348168580614?text=Hi%2C%20I%27d%20like%20to%20get%20a%20shoe%20store%20like%20this%20built" target="_blank" rel="noopener">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.1-1.7-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.4 0-.5C10 9 9.4 7.6 9.2 7c-.2-.5-.4-.5-.6-.5h-.5c-.2 0-.5.1-.7.3-.3.3-1 1-1 2.4s1 2.8 1.2 3c.1.2 2 3.1 4.9 4.3.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.6-.1 1.7-.7 1.9-1.3.2-.7.2-1.2.2-1.3-.1-.2-.3-.3-.6-.4z"/><path d="M12 2C6.5 2 2 6.5 2 12c0 1.9.5 3.7 1.5 5.3L2 22l4.8-1.5c1.5.8 3.3 1.3 5.2 1.3 5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18.2c-1.7 0-3.3-.5-4.7-1.3l-.3-.2-3.1 1 1-3-.2-.3C4 15 3.5 13.5 3.5 12c0-4.7 3.8-8.5 8.5-8.5s8.5 3.8 8.5 8.5-3.8 8.5-8.5 8.5z"/></svg>
      WhatsApp: 0816 858 0614
    </a>
    <span class="plate-note">Tap to chat — usually replies same day</span>
  </div>
</section>

<footer>
  <div class="wrap mono-label">
    <span>SHOE COMMERCE PLATFORM</span>
    <span>SPEC SHEET / END</span>
  </div>
</footer>

</body>
</html>
