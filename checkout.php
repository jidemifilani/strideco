<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$items = get_cart_details($pdo);
if (!$items) {
    set_flash('error', 'Your cart is empty — add some shoes before checking out.');
    redirect(base_url('cart.php'));
}

$subtotal = 0;
foreach ($items as $line) { $subtotal += $line['line_total']; }

$customer = null;
if (is_customer_logged_in()) {
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
    $stmt->execute([current_customer_id()]);
    $customer = $stmt->fetch();
}

$shippingResult = resolve_shipping_fee($pdo, $customer['city'] ?? '');
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : $shippingResult['fee'];

$couponResult = null;
if (!empty($_SESSION['coupon_code'])) {
    $couponResult = validate_coupon($pdo, $_SESSION['coupon_code'], $subtotal);
    if (!$couponResult['valid']) {
        unset($_SESSION['coupon_code']);
    }
}
$discount = $couponResult['discount'] ?? 0.0;
$tax = calculate_tax($pdo, max(0, $subtotal - $discount));
$totalBeforeGiftCard = max(0, $subtotal - $discount + $shipping + $tax);
$taxRatePercent = get_tax_rate_percent($pdo);

$giftCardResult = null;
if (!empty($_SESSION['gift_card_code'])) {
    $giftCardResult = validate_gift_card($pdo, $_SESSION['gift_card_code']);
    if (!$giftCardResult['valid']) {
        unset($_SESSION['gift_card_code']);
    }
}
$giftCardAmount = $giftCardResult && $giftCardResult['valid']
    ? min((float) $giftCardResult['giftCard']['balance'], $totalBeforeGiftCard)
    : 0.0;
$total = max(0, $totalBeforeGiftCard - $giftCardAmount);

$pageTitle = 'Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / <a href="<?= base_url('cart.php') ?>">Cart</a> / Checkout</div>
    <h1 style="margin-bottom:28px;">Checkout</h1>

    <?php if (!$customer): ?>
      <div class="cta-banner" style="padding:18px 24px;margin-bottom:28px;">
        <p style="margin:0;">Have an account? <a href="<?= base_url('account/login.php?next=checkout.php') ?>" style="color:#fff;text-decoration:underline;">Log in</a> for faster checkout.</p>
      </div>
    <?php endif; ?>

    <div class="checkout-layout">
      <div class="checkout-card">
        <h3>Delivery details</h3>
        <form action="<?= base_url('place-order.php') ?>" method="post">
          <?= csrf_field() ?>
          <?= honeypot_field() ?>
          <div class="form-row">
            <div class="form-group">
              <label for="customer_name">Full name</label>
              <input type="text" id="customer_name" name="customer_name" required value="<?= htmlspecialchars($customer['name'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label for="phone">Phone number</label>
              <input type="tel" id="phone" name="phone" required placeholder="080X XXX XXXX" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="address">Delivery address</label>
            <textarea id="address" name="address" required placeholder="Street address, apartment, landmark"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label for="city">City</label>
            <input type="text" id="city" name="city" required value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
            <p class="form-hint" id="shippingEstimateNote">Delivery fee shown is an estimate for <?= htmlspecialchars($shippingResult['zone']['name'] ?? 'your area') ?> &mdash; it updates once you enter your city.</p>
          </div>
          <?php if ($total > 0): ?>
            <button type="submit" class="btn btn-primary btn-block" id="payButton">Pay <?= format_price($total) ?> with Paystack</button>
            <p class="form-hint" style="text-align:center;margin-top:12px;">You'll be redirected to Paystack to complete payment securely.</p>
          <?php else: ?>
            <button type="submit" class="btn btn-primary btn-block" id="payButton">Place order &mdash; fully covered by gift card</button>
            <p class="form-hint" style="text-align:center;margin-top:12px;">No payment needed — your gift card covers the full total.</p>
          <?php endif; ?>
        </form>
      </div>

      <div class="summary-card">
        <h3>Order Summary</h3>
        <?php foreach ($items as $line): $p = $line['product']; $v = $line['variant']; ?>
          <div class="mini-cart-item">
            <img src="<?= $v ? variant_image_url($v, $p) : product_image_url($p) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <div>
              <div class="name"><?= htmlspecialchars($p['name']) ?><?php if (!empty($p['is_preorder'])): ?> <span class="preorder-tag">Pre-order</span><?php endif; ?></div>
              <div class="meta"><?= $v ? htmlspecialchars($v['color_name']) . ' &middot; ' : '' ?>Size <?= htmlspecialchars($line['size']) ?> &times; <?= (int) $line['qty'] ?></div>
            </div>
            <div class="price"><?= format_price((float) $line['line_total']) ?></div>
          </div>
        <?php endforeach; ?>

        <?php if ($couponResult && $couponResult['valid']): ?>
          <div class="summary-row" style="align-items:center;">
            <span>Coupon <strong><?= htmlspecialchars($couponResult['coupon']['code']) ?></strong></span>
            <form action="<?= base_url('apply-coupon.php') ?>" method="post" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove">
              <button type="submit" class="remove-link" style="background:none;border:none;padding:0;">Remove</button>
            </form>
          </div>
        <?php else: ?>
          <form action="<?= base_url('apply-coupon.php') ?>" method="post" style="display:flex;gap:8px;margin:14px 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="apply">
            <input type="text" name="code" placeholder="Coupon code" style="flex:1;padding:10px 12px;">
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
          </form>
        <?php endif; ?>

        <?php if ($giftCardResult && $giftCardResult['valid']): ?>
          <div class="summary-row" style="align-items:center;">
            <span>Gift card <strong><?= htmlspecialchars($giftCardResult['giftCard']['code']) ?></strong></span>
            <form action="<?= base_url('apply-giftcard.php') ?>" method="post" style="display:inline;">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove">
              <button type="submit" class="remove-link" style="background:none;border:none;padding:0;">Remove</button>
            </form>
          </div>
        <?php else: ?>
          <form action="<?= base_url('apply-giftcard.php') ?>" method="post" style="display:flex;gap:8px;margin:14px 0;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="apply">
            <input type="text" name="gift_card_code" placeholder="Gift card code" style="flex:1;padding:10px 12px;">
            <button type="submit" class="btn btn-outline btn-sm">Apply</button>
          </form>
        <?php endif; ?>

        <div class="summary-row" style="margin-top:6px;"><span>Subtotal</span><span id="summarySubtotal"><?= format_price($subtotal) ?></span></div>
        <?php if ($discount > 0): ?>
          <div class="summary-row"><span>Discount</span><span>&minus;<?= format_price($discount) ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Shipping</span><span id="summaryShipping"><?= $shipping > 0 ? format_price($shipping) : 'Free' ?></span></div>
        <?php if ($tax > 0 || $taxRatePercent > 0): ?>
          <div class="summary-row" id="summaryTaxRow"><span>Tax (<?= rtrim(rtrim(number_format($taxRatePercent, 2), '0'), '.') ?>%)</span><span id="summaryTax"><?= format_price($tax) ?></span></div>
        <?php endif; ?>
        <?php if ($giftCardAmount > 0): ?>
          <div class="summary-row"><span>Gift card</span><span>&minus;<?= format_price($giftCardAmount) ?></span></div>
        <?php endif; ?>
        <div class="summary-row total"><span>Total</span><span id="summaryTotal"><?= format_price($total) ?></span></div>
        <?= shipping_nudge_html($subtotal) ?>

        <div id="checkoutData"
          data-subtotal="<?= $subtotal ?>"
          data-discount="<?= $discount ?>"
          style="display:none;"></div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
