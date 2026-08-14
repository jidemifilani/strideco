<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

$errors = [];
$old = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf() || honeypot_triggered()) {
        set_flash('error', 'Something went wrong, please try again.');
        redirect(base_url('contact.php'));
    }

    $old['name'] = trim($_POST['name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['subject'] = trim($_POST['subject'] ?? '');
    $old['message'] = trim($_POST['message'] ?? '');

    if ($old['name'] === '') { $errors[] = 'Name is required.'; }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email is required.'; }
    if ($old['message'] === '') { $errors[] = 'Message is required.'; }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$old['name'], $old['email'], $old['subject'], $old['message']]);
        set_flash('success', "Thanks for reaching out — we'll get back to you soon.");
        redirect(base_url('contact.php'));
    }
}

$contactEmail = get_setting($pdo, 'contact_email', 'hello@strideco.example');
$contactPhone = get_setting($pdo, 'contact_phone', '+234 800 000 0000');
$contactAddress = get_setting($pdo, 'contact_address', 'Lagos, Nigeria');

$pageTitle = 'Contact Us';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section" style="padding-bottom:96px;">
  <div class="container" style="max-width:640px;">
    <div class="breadcrumb"><a href="<?= base_url('index.php') ?>">Home</a> / Contact</div>
    <h1 style="margin-bottom:8px;">Get in touch</h1>
    <p class="text-muted" style="margin-bottom:28px;">Questions about an order, sizing, or anything else — we're happy to help.</p>

    <?php if ($errors): ?>
      <div class="form-errors"><?= implode(' ', array_map('htmlspecialchars', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('contact.php') ?>" class="checkout-card" style="margin-bottom:28px;">
      <?= csrf_field() ?>
      <?= honeypot_field() ?>
      <div class="form-row">
        <div class="form-group">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" required value="<?= htmlspecialchars($old['name']) ?>">
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required value="<?= htmlspecialchars($old['email']) ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="subject">Subject</label>
        <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($old['subject']) ?>" placeholder="e.g. Order SC260814ABCDE">
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" required style="min-height:140px;"><?= htmlspecialchars($old['message']) ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Send message</button>
    </form>

    <div class="checkout-card">
      <h3>Other ways to reach us</h3>
      <p style="margin-bottom:6px;">Email: <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color:var(--accent-dark);"><?= htmlspecialchars($contactEmail) ?></a></p>
      <p style="margin-bottom:6px;">Phone: <?= htmlspecialchars($contactPhone) ?></p>
      <p style="margin:0;"><?= htmlspecialchars($contactAddress) ?></p>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
