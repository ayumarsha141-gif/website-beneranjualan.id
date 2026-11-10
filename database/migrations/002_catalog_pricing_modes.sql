-- Additive only: preserve existing products, orders, and transaction history.
-- `from` means the published amount is a starting price; `quote` means the
-- owner must confirm a quote (normally in WhatsApp); `request` availability
-- routes inquiries to the store instead of pretending stock is known.
ALTER TABLE produk
    ADD COLUMN pricing_mode VARCHAR(16) NOT NULL DEFAULT 'fixed' AFTER harga,
    ADD COLUMN availability_mode VARCHAR(16) NOT NULL DEFAULT 'stock' AFTER pricing_mode,
    ADD COLUMN pricing_note VARCHAR(500) NULL AFTER availability_mode;
