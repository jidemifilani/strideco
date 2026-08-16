-- StrideCo v4 schema upgrade — pre-orders.
-- Safe to re-run: uses ADD COLUMN IF NOT EXISTS throughout.

USE strideco;

-- Pre-order (opt-in per product, like color variants): a product marked
-- is_preorder=1 skips all stock gating on the storefront — every size is
-- always selectable regardless of what's in product_sizes/variant_sizes —
-- and shows a "Pre-order" badge with the expected availability date instead
-- of normal stock messaging. Existing size/stock data is left untouched and
-- simply ignored while a product is in pre-order mode, so switching it back
-- off later restores normal stock-based behavior with no data loss.
ALTER TABLE products ADD COLUMN IF NOT EXISTS is_preorder TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured;
ALTER TABLE products ADD COLUMN IF NOT EXISTS preorder_available_at DATE DEFAULT NULL AFTER is_preorder;

-- Snapshotted onto the order line at purchase time (not just read live off
-- products) so an order's receipt/timeline always reflects what the
-- customer was actually told when they bought it, even if the product's
-- pre-order status or date changes later.
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS is_preorder TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE order_items ADD COLUMN IF NOT EXISTS preorder_available_at DATE DEFAULT NULL;
