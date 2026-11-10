# Changelog

## 2026-11-06 — Storefront and integration continuation

- Added an honest store policy/terms information route and wired it into existing navigation.
- Replaced legacy cart aliases that performed GET mutations with POST/CSRF-compatible endpoints.
- Replaced outdated storefront JS with progressive enhancement for the current POST forms, gallery, navigation drawer, quantity controls, reveal motion, toast feedback and reduced-motion behavior.
- Rebuilt storefront CSS to match the botanical editorial design across mobile, tablet and desktop; added admin CSS/JS for operational pages and responsive navigation.
- Added canonical/Open Graph metadata, a sitemap of active non-demo catalog pages, and `robots.php` with Apache rewrite instructions.
- Connected admin-managed terms/privacy text to the public policy page and added keyboard focus trapping to the mobile navigation drawer.
- Fixed public image resolution for admin-uploaded files and made image replacement/deletion safer around DB commits; added 30 read-only PHP regression checks.
- Generated responsive WebP variants for the bundled bouquet photos and added fallback-aware `srcset` support plus a reproducible Pillow-based asset script.
- Reworked the full admin visual system to a cream/dusty-rose palette, restored the desktop sidebar, fixed settings checkbox/status-list layout, and added restrained flower/petal motion with reduced-motion support.
- Hid the SVG symbol sprite from normal page flow to eliminate the large blank space above storefront/admin headers; bumped asset versions to bypass stale browser cache.
- Updated the entire customer storefront to the requested soft blush/cream palette too, including CTA, forms, catalog, account pages, WhatsApp action treatment and footer; kept success/error cues readable and motion restrained.
- Expanded HTTP verification to customer auth/logout, uploaded product images, and admin payment/cancel/refund/stock-restoration flow.
- Verified guest receipt rendering by fetching the receipt URL after checkout; marked account, cart, checkout and tracking pages `noindex` by default.
- Added project and database setup documentation and audit/test/risk reports.
- Ran PHP syntax checks and local MySQL schema/seed verification; see `TEST_REPORT.md`.

## Earlier implementation

See Git changes for the ongoing PHP/MySQL rebuild: environment config, schema/migration, demo catalog, checkout, account/order routes and admin operations.
