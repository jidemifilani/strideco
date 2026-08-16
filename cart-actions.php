<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
start_session_if_needed();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    redirect(base_url('cart.php'));
}

$action = $_POST['action'] ?? '';
$redirectTo = $_POST['redirect_to'] ?? base_url('cart.php');
// Only allow redirecting back into this site.
if (strpos($redirectTo, base_url()) !== 0) {
    $redirectTo = base_url('cart.php');
}

if ($action === 'add') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $size = trim($_POST['size'] ?? '');
    $qty = max(1, min(10, (int) ($_POST['qty'] ?? 1)));
    $variantId = (int) ($_POST['variant_id'] ?? 0) ?: null;

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if ($variantId) {
        // Variant must actually belong to this product — otherwise a crafted
        // request could add a variant of a different (or inactive) product.
        $vCheck = $pdo->prepare('SELECT id FROM product_variants WHERE id = ? AND product_id = ?');
        $vCheck->execute([$variantId, $productId]);
        if (!$vCheck->fetch()) {
            $variantId = null;
            $product = false;
        }
    }

    // Pre-order products skip all stock gating — there's no real inventory
    // to check yet by design (see includes/header.php... no, see
    // database/upgrade_v4.sql for the reasoning).
    $isPreorderProduct = $product && !empty($product['is_preorder']);

    if ($isPreorderProduct) {
        $sizeRow = ['stock' => PHP_INT_MAX];
    } elseif ($variantId) {
        $sizeStmt = $pdo->prepare('SELECT stock FROM variant_sizes WHERE variant_id = ? AND size = ?');
        $sizeStmt->execute([$variantId, $size]);
        $sizeRow = $sizeStmt->fetch();
    } else {
        $sizeStmt = $pdo->prepare('SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?');
        $sizeStmt->execute([$productId, $size]);
        $sizeRow = $sizeStmt->fetch();
    }

    if (!$product || !$sizeRow || $size === '') {
        set_flash('error', 'That item could not be added to your cart.');
    } elseif ($sizeRow['stock'] <= 0) {
        set_flash('error', 'That size is out of stock.');
    } else {
        $existingQty = 0;
        $raw = get_cart_raw();
        $key = cart_key($productId, $size, $variantId);
        if (isset($raw[$key])) {
            $existingQty = $raw[$key]['qty'];
        }
        $allowed = max(0, $sizeRow['stock'] - $existingQty);
        if ($allowed <= 0) {
            set_flash('error', 'You already have all available stock of that size in your cart.');
        } else {
            $toAdd = min($qty, $allowed);
            add_to_cart($productId, $size, $toAdd, $variantId);
            if (!$isPreorderProduct && $toAdd < $qty) {
                set_flash('success', "Only {$toAdd} in stock — added what's available to your cart.");
            } elseif ($isPreorderProduct) {
                set_flash('success', htmlspecialchars($product['name']) . ' added to your cart as a pre-order.');
            } else {
                set_flash('success', htmlspecialchars($product['name']) . ' added to your cart.');
            }
        }
    }
} elseif ($action === 'update') {
    $key = $_POST['key'] ?? '';
    $qty = max(0, min(10, (int) ($_POST['qty'] ?? 1)));

    $raw = get_cart_raw();
    if (isset($raw[$key]) && $qty > 0) {
        $productCheck = $pdo->prepare('SELECT is_preorder FROM products WHERE id = ?');
        $productCheck->execute([$raw[$key]['product_id']]);
        $isPreorderItem = (bool) $productCheck->fetchColumn();

        if (!$isPreorderItem) {
            if (!empty($raw[$key]['variant_id'])) {
                $sizeStmt = $pdo->prepare('SELECT stock FROM variant_sizes WHERE variant_id = ? AND size = ?');
                $sizeStmt->execute([$raw[$key]['variant_id'], $raw[$key]['size']]);
            } else {
                $sizeStmt = $pdo->prepare('SELECT stock FROM product_sizes WHERE product_id = ? AND size = ?');
                $sizeStmt->execute([$raw[$key]['product_id'], $raw[$key]['size']]);
            }
            $stock = (int) ($sizeStmt->fetchColumn() ?: 0);
            $qty = min($qty, max(1, $stock));
        }
    }
    update_cart_item($key, $qty);
} elseif ($action === 'remove') {
    remove_from_cart($_POST['key'] ?? '');
    set_flash('success', 'Item removed from cart.');
} elseif ($action === 'clear') {
    clear_cart();
}

redirect($redirectTo);
