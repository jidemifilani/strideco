<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf()) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'issue') {
        $balance = trim($_POST['initial_balance'] ?? '');
        $recipientEmail = trim($_POST['recipient_email'] ?? '');
        $expiresAt = trim($_POST['expires_at'] ?? '');
        $note = trim($_POST['note'] ?? '');

        if (!is_numeric($balance) || (float) $balance <= 0) {
            $errors[] = 'Enter a valid starting balance.';
        }
        if ($recipientEmail !== '' && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Recipient email looks invalid.';
        }

        if (!$errors) {
            do {
                $code = generate_gift_card_code();
                $check = $pdo->prepare('SELECT id FROM gift_cards WHERE code = ?');
                $check->execute([$code]);
            } while ($check->fetch());

            $pdo->prepare(
                'INSERT INTO gift_cards (code, initial_balance, balance, expires_at, recipient_email, note) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$code, $balance, $balance, $expiresAt ?: null, $recipientEmail ?: null, $note ?: null]);

            set_flash('success', "Gift card {$code} issued with a balance of " . format_price((float) $balance) . '.');
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        $pdo->prepare("UPDATE gift_cards SET status = IF(status = 'active', 'disabled', 'active') WHERE id = ?")->execute([$id]);
        set_flash('success', 'Gift card updated.');
    }

    if (!$errors) {
        redirect(base_url('admin/gift-cards.php'));
    }
}

$giftCards = $pdo->query('SELECT * FROM gift_cards ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'Gift Cards';
require_once __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
  <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="admin-card" style="margin-bottom:20px;">
  <div class="admin-card-head"><h3>Issue a gift card</h3></div>
  <div class="admin-card-body">
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="form_action" value="issue">
      <div class="form-row">
        <div class="form-group">
          <label for="initial_balance">Starting balance (&#8358;)</label>
          <input type="number" id="initial_balance" name="initial_balance" step="0.01" min="0.01" required>
        </div>
        <div class="form-group">
          <label for="recipient_email">Recipient email (optional)</label>
          <input type="email" id="recipient_email" name="recipient_email" placeholder="customer@example.com">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="expires_at">Expires on (blank = never)</label>
          <input type="date" id="expires_at" name="expires_at">
        </div>
        <div class="form-group">
          <label for="note">Internal note (optional)</label>
          <input type="text" id="note" name="note" placeholder="e.g. Goodwill credit for order SC260814ABCDE">
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Issue gift card</button>
    </form>
    <p class="form-hint" style="margin-top:12px;">The code is generated automatically and shown once it's created — copy it to send to the recipient. Not yet purchasable directly by customers as a storefront product.</p>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($giftCards) ?> gift card<?= count($giftCards) === 1 ? '' : 's' ?></h3>
  </div>
  <div class="admin-table-wrap">
    <?php if ($giftCards): ?>
    <table class="admin-table">
      <thead><tr><th>Code</th><th>Balance</th><th>Recipient</th><th>Expires</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($giftCards as $g): ?>
          <tr>
            <td><strong><?= htmlspecialchars($g['code']) ?></strong></td>
            <td><?= format_price((float) $g['balance']) ?> <span class="text-muted">/ <?= format_price((float) $g['initial_balance']) ?></span></td>
            <td class="text-muted"><?= $g['recipient_email'] ? htmlspecialchars($g['recipient_email']) : '&mdash;' ?></td>
            <td class="text-muted"><?= $g['expires_at'] ? date('d M Y', strtotime($g['expires_at'])) : 'Never' ?></td>
            <td><span class="pill pill-<?= $g['status'] === 'active' ? 'active' : 'inactive' ?>"><?= htmlspecialchars($g['status']) ?></span></td>
            <td>
              <form action="<?= base_url('admin/gift-cards.php') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="toggle">
                <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                <button type="submit" class="btn btn-outline btn-sm"><?= $g['status'] === 'active' ? 'Disable' : 'Enable' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="admin-card-body text-muted">No gift cards issued yet.</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
