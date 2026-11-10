# Audit report

## Scope and implementation

The existing `bouquet/` PHP application was retained. The storefront, catalog, product detail, guest checkout, customer account/order views and operational admin pages use PDO/MySQL. The project includes a versioned schema and demo seed, CLI database/admin setup, environment-based configuration, CSRF-protected POST cart/admin actions, transactional stock-aware checkout, manual payment verification, editable policy text, canonical/Open Graph/sitemap/robots metadata, responsive WebP derivatives, and a progressive Three.js orthographic scroll-story with six connected florist scenes. The PHP commerce backend and route structure remain in place.

Legacy database fragments do not constitute a complete SQL backup; no historical customers, orders, passwords, or revenue were recovered. The configured MySQL database already had migration `001_initial.sql` applied. Its six clearly marked demo products are now archived and excluded from customer sales routes; there are currently no verified active products or orders. Owner catalog data still needs to be entered before launch.

## Key safeguards

- Server computes checkout price, shipping settings and total; posted totals are not authoritative.
- Checkout uses a database transaction, product row locks, conditional stock decrement, movement/status history, and an idempotency token.
- Customer/admin mutations are POST + CSRF; admin routes enforce the role server-side.
- Uploaded product image types and size are checked; uploads are kept in a non-executable directory rule.
- Admin-uploaded images resolve correctly on storefront pages; replacement/removal handles filesystem cleanup only after database changes succeed.
- Empty real-world contact/bank settings are not invented or publicly shown.

## Still requiring owner/hosting configuration

Real product/inventory, business contact and legal policy review, bank details, delivery zones/fees, HTTPS, production secrets, email delivery for password reset, and a real deployment/restore/performance/security review. See `REMAINING_RISKS.md`.
