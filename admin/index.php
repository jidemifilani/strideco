<?php
require_once __DIR__ . '/includes/auth.php';

maybe_run_scheduled_cleanup($pdo);
maybe_run_abandoned_cart_reminders($pdo);

$totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$revenue = (float) $pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();
$pendingPayments = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();
$lowStock = (int) $pdo->query('SELECT COUNT(*) FROM product_sizes WHERE stock > 0 AND stock <= 3')->fetchColumn();

$recentOrders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 8')->fetchAll();

// ---- 14-day sales chart ----
function nice_ceil_value(float $value): float {
    if ($value <= 0) { return 100.0; }
    $magnitude = 10 ** floor(log10($value));
    $normalized = $value / $magnitude;
    $niceNormalized = $normalized <= 1 ? 1 : ($normalized <= 2 ? 2 : ($normalized <= 5 ? 5 : 10));
    return $niceNormalized * $magnitude;
}

$salesByDay = [];
$since = date('Y-m-d 00:00:00', strtotime('-13 days'));
$stmt = $pdo->prepare(
    "SELECT DATE(created_at) AS day, SUM(total) AS revenue
     FROM orders WHERE payment_status = 'paid' AND created_at >= ?
     GROUP BY DATE(created_at)"
);
$stmt->execute([$since]);
foreach ($stmt->fetchAll() as $row) {
    $salesByDay[$row['day']] = (float) $row['revenue'];
}

$chartDays = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartDays[] = ['date' => $date, 'label' => date('j M', strtotime($date)), 'value' => $salesByDay[$date] ?? 0.0];
}
$chartTotal = array_sum(array_column($chartDays, 'value'));
$chartMax = max(array_column($chartDays, 'value'));
$yMax = nice_ceil_value($chartMax);

// Plot geometry
$plotLeft = 64; $plotRight = 700; $plotTop = 14; $plotBottom = 190;
$plotW = $plotRight - $plotLeft; $plotH = $plotBottom - $plotTop;
$bandW = $plotW / count($chartDays);
$barW = 20;
$barsSvg = '';
foreach ($chartDays as $i => $d) {
    $bandX = $plotLeft + $i * $bandW;
    $barX = $bandX + ($bandW - $barW) / 2;
    $h = $yMax > 0 ? ($d['value'] / $yMax) * $plotH : 0;
    $y = $plotBottom - $h;
    $title = htmlspecialchars($d['label'] . ': ' . format_price($d['value']));
    if ($h > 0.5) {
        $r = min(4, $h);
        $barsSvg .= '<path d="M' . round($barX, 1) . ',' . round($y + $r, 1)
            . ' Q' . round($barX, 1) . ',' . round($y, 1) . ' ' . round($barX + $r, 1) . ',' . round($y, 1)
            . ' L' . round($barX + $barW - $r, 1) . ',' . round($y, 1)
            . ' Q' . round($barX + $barW, 1) . ',' . round($y, 1) . ' ' . round($barX + $barW, 1) . ',' . round($y + $r, 1)
            . ' L' . round($barX + $barW, 1) . ',' . round($plotBottom, 1)
            . ' L' . round($barX, 1) . ',' . round($plotBottom, 1) . ' Z" fill="#FF5A1F"><title>' . $title . '</title></path>';
    } else {
        $barsSvg .= '<rect x="' . round($barX, 1) . '" y="' . ($plotBottom - 2) . '" width="' . $barW . '" height="2" fill="#E8E4DE"><title>' . $title . '</title></rect>';
    }
    if ($i % 2 === 1) {
        $barsSvg .= '<text x="' . round($bandX + $bandW / 2, 1) . '" y="' . ($plotBottom + 18) . '" text-anchor="middle" font-size="10" fill="#837C74" font-family="Inter,sans-serif">' . htmlspecialchars($d['label']) . '</text>';
    }
}
$gridSvg = '';
foreach ([0, 0.5, 1] as $frac) {
    $gy = $plotBottom - $frac * $plotH;
    $label = format_price($yMax * $frac);
    $gridSvg .= '<line x1="' . $plotLeft . '" y1="' . round($gy, 1) . '" x2="' . $plotRight . '" y2="' . round($gy, 1) . '" stroke="#E8E4DE" stroke-width="1"/>';
    $gridSvg .= '<text x="' . ($plotLeft - 10) . '" y="' . round($gy + 3, 1) . '" text-anchor="end" font-size="10" fill="#837C74" font-family="Inter,sans-serif">' . htmlspecialchars($label) . '</text>';
}

$lowStockRows = $pdo->query(
    "SELECT p.name, p.slug, ps.size, ps.stock
     FROM product_sizes ps JOIN products p ON p.id = ps.product_id
     WHERE ps.stock <= 3
     ORDER BY ps.stock ASC, p.name ASC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="stat-grid">
  <div class="stat-card">
    <div class="label">Total Orders</div>
    <div class="value"><?= $totalOrders ?></div>
  </div>
  <div class="stat-card">
    <div class="label">Revenue (Paid)</div>
    <div class="value"><?= format_price($revenue) ?></div>
  </div>
  <div class="stat-card">
    <div class="label">Pending Payments</div>
    <div class="value"><?= $pendingPayments ?></div>
  </div>
  <div class="stat-card">
    <div class="label">Low Stock Alerts</div>
    <div class="value"><?= $lowStock ?></div>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>Sales, last 14 days</h3>
    <span class="text-muted" style="font-size:0.85rem;"><?= format_price($chartTotal) ?> total</span>
  </div>
  <div class="admin-card-body">
    <?php if ($chartTotal <= 0): ?>
      <p class="text-muted" style="margin:0;">No paid orders in the last 14 days yet — your first sale will show up here.</p>
    <?php else: ?>
      <svg viewBox="0 0 720 220" role="img" aria-label="Daily revenue for the last 14 days, total <?= htmlspecialchars(format_price($chartTotal)) ?>" style="width:100%;height:auto;max-height:240px;">
        <?= $gridSvg ?>
        <?= $barsSvg ?>
      </svg>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h3>Recent Orders</h3>
    <a href="<?= base_url('admin/orders.php') ?>" class="btn btn-outline btn-sm">View all</a>
  </div>
  <div class="admin-table-wrap">
    <?php if ($recentOrders): ?>
    <table class="admin-table">
      <thead><tr><th>Reference</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $order): ?>
          <tr>
            <td><?= htmlspecialchars($order['order_ref']) ?></td>
            <td><?= htmlspecialchars($order['customer_name']) ?></td>
            <td><?= format_price((float) $order['total']) ?></td>
            <td><span class="pill pill-<?= htmlspecialchars($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></td>
            <td><span class="pill pill-<?= htmlspecialchars($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
            <td class="text-muted"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
            <td><a href="<?= base_url('admin/order-view.php?id=' . (int) $order['id']) ?>" class="btn btn-outline btn-sm">View</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No orders yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php if ($lowStockRows): ?>
<div class="admin-card">
  <div class="admin-card-head"><h3>Low Stock</h3></div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Size</th><th>Stock left</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($lowStockRows as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['size']) ?></td>
            <td><?= (int) $row['stock'] ?></td>
            <td><a href="<?= base_url('admin/products.php') ?>" class="btn btn-outline btn-sm">Manage</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
