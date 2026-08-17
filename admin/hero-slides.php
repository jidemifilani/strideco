<?php
require_once __DIR__ . '/includes/auth.php';

$slides = $pdo->query('SELECT * FROM hero_slides ORDER BY sort_order ASC, id ASC')->fetchAll();

$pageTitle = 'Homepage Slider';
require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-card">
  <div class="admin-card-head">
    <h3><?= count($slides) ?> slides</h3>
    <a href="<?= base_url('admin/hero-slide-form.php') ?>" class="btn btn-primary btn-sm">Add slide</a>
  </div>
  <p class="text-muted" style="padding:0 20px 16px;">
    These images run in the homepage hero slider, in order. If no slides are added here, the
    homepage falls back to showing your products marked "Featured" instead.
  </p>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead><tr><th>Image</th><th>Headline</th><th>Links to</th><th>Order</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php if (!$slides): ?>
          <tr><td colspan="6" class="text-muted" style="padding:20px;">No custom slides yet — add one, or leave this empty to keep using Featured products.</td></tr>
        <?php endif; ?>
        <?php foreach ($slides as $slide): ?>
          <tr>
            <td><img src="<?= htmlspecialchars(base_url('assets/uploads/' . $slide['image'])) ?>" alt="" style="width:70px;height:44px;object-fit:cover;border-radius:6px;"></td>
            <td><?= htmlspecialchars($slide['headline'] ?: '—') ?></td>
            <td class="text-muted"><?= htmlspecialchars($slide['link_url'] ?: '—') ?></td>
            <td><?= (int) $slide['sort_order'] ?></td>
            <td><span class="pill <?= $slide['status'] === 'active' ? 'pill-active' : '' ?>"><?= htmlspecialchars(ucfirst($slide['status'])) ?></span></td>
            <td>
              <div class="admin-actions">
                <a href="<?= base_url('admin/hero-slide-form.php?id=' . (int) $slide['id']) ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/delete-hero-slide.php') ?>" method="post" onsubmit="return confirm('Delete this slide?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
