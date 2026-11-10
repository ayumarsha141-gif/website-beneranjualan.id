# Test report

Date: 2026-11-06

## Executed

- `php -l` passed for all 65 PHP files in the project.
- `git diff --check` passed (Git displayed only expected LF/CRLF working-copy notices).
- `php scripts/setup_db.php --seed-demo` connected to the configured local MySQL database; migration `001_initial.sql` was already applied and the demo seed completed.
- Database verification reported 6 products, 4 categories, and 0 users, orders, order lines, stock movements, or order status history rows.
- PHP CLI has PDO MySQL, mbstring, and fileinfo extensions available.
- `node --check` passed for storefront and admin JavaScript.
- `php tests/run.php` passed all 31 read-only regression checks.
- Local PHP server smoke test: `/`, `/katalog.php`, `/produk.php?id=1`, `/cart.php`, `/kebijakan.php`, `/login.php`, `/register.php`, `/lupa-password.php`, and `/lacak-pesanan.php` returned HTTP 200; empty-cart `/checkout.php`, unauthenticated `/pesanan-saya.php`, and `/admin/dashboard.php` correctly redirected (HTTP 302).
- Storefront/admin CSS and JS plus placeholder and demo image assets returned HTTP 200. `/pesanan-saya.php` correctly redirected for an unauthenticated visitor.
- Guest checkout E2E created one test order with a deliberately tampered posted `total=1`; its saved order total was read back from MySQL and matched the server-calculated product total. Resubmitting the same checkout token redirected to the same order rather than creating another. The temporary test order, movements/history and stock decrement were cleaned up; database returned to 6 demo products and zero orders.
- A separate guest checkout fetched the protected-by-token receipt and confirmed it rendered the order reference; the temporary order and stock change were cleaned up.
- Customer registration → login → authenticated profile → POST logout passed; the temporary customer and test login-attempt records were removed.
- Temporary admin authentication passed, and dashboard, orders, products, product form, categories, inventory, customers, reports and settings pages each returned HTTP 200. The temporary admin account was removed; no customer accounts or test orders remain.
- Admin multipart product creation with a valid image passed; the product's public detail used `/assets/uploads/...` and the uploaded asset returned HTTP 200. The test product, upload, movement logs and temporary admin were removed afterward.
- Admin order lifecycle passed via HTTP: payment verification, cancellation, one-time stock restore, and payment refund status. Temporary order/admin data and test auth logs were removed.
- CSRF/legacy-cart checks passed: AJAX cart mutation without CSRF returned HTTP 419, and GET to the legacy add route redirected without changing the empty cart.
- Concurrent MySQL row-lock/conditional-decrement test used two independent PHP processes against one unit of temporary stock; exactly one transaction succeeded, one was rejected, remaining stock was zero, and the temporary product was removed. This validates the database primitive used by checkout, but is not a simultaneous full HTTP checkout test.
- Sitemap and robots PHP endpoints, product canonical/Open Graph metadata and demo `noindex` were smoke-checked successfully. Apache `.htaccess` rewrite behavior was not exercised by PHP's built-in server.
- Responsive WebP derivative generation completed for six bundled raster photos: 12 variants (480px/960px), about 1.05 MB total versus 4.83 MB of source photos; PHP uses WebP when present and retains original fallbacks.
- Post-optimization HTTP smoke test confirmed both WebP sizes and source JPEG return HTTP 200, storefront emits `<picture>` WebP sources with original-image fallback, and account pages emit `noindex`.
- Local backup/restore drill passed: dumped the configured DB and restored it into a newly created isolated scratch database; table counts matched (4 categories, 6 products, 0 users/orders), then the scratch database was dropped.

## Not executed / not claimed

- Browser-based visual/responsive accessibility audit and simultaneous full HTTP checkout test against final stock.
- Complete login/register/CSRF/role-guard attack test suite, image upload validation exercise, automated broken-link crawl, or visual/accessibility audit.
- Scheduled/offsite backup operations, HTTPS/deployed-host verification, Lighthouse/Core Web Vitals, or production security review.

No temporary test admin/customer or order remains, and no real business order/payment was generated. Six demo products remain in the local database.

## Follow-up verification — 2026-11-07

- MySQL was available. `php scripts/setup_db.php` connected, verified expected tables, and safely skipped `001_initial.sql` because its migration ledger entry already existed (0 new migrations).
- Regression suite rerun: **31 checks passed**.
- Archived the six explicitly marked demo products with `is_active=0`; no products were deleted. Verified 0 active demo products, 0 active real products, 0 orders, and 0 temporary QA products after cleanup.
- Full guest HTTP checkout smoke test with a temporary non-demo product passed: CSRF-protected add-to-cart, checkout receipt, server-derived total (`Rp 12.345`), one-unit stock decrement, pending-payment state, retry idempotency (one order), and rejection of archived demo product. All temporary order/product/stock rows were removed afterward.
- Local browser smoke: homepage and catalog returned HTTP 200; archived demo product detail returned 404. Homepage was reviewed at desktop/mobile widths and catalog empty state reviewed at desktop/mobile widths. This does not replace a complete accessibility or production-host audit.
- The three-chapter scroll story was rendered with original photos from Petite/Clawsha, Signature/Nasha, and Grand/Suflosha. Responsive 480/960 WebP derivatives, sticky image scene and drawn botanical path on desktop, and non-sticky chapter/photo sequence on mobile were visually checked in full-page screenshots. Customer navigation uses a floating pill, and the story now precedes the collection in the homepage sequence.
- PHP lint (47 files), JS syntax checks, and `git diff --check` passed.
- Cleanup verification: legacy static routes now return 404; `/`, `/index.php`, and `/katalog.php` still return 200. `php tests/run.php` remains at 31 passing checks. Unique source photos, backups, and database fragments were preserved.

## Design V2 visual verification — 2026-11-08

- A three-scene florist prototype was rendered in the existing PHP homepage, then expanded to six connected scenes A–F. Three.js 0.187.0 is self-hosted in `bouquet/assets/vendor/`; the renderer is orthographic and the blush tube route is Three.js world geometry.
- Playwright/Edge/WebGL/SwiftShader scrolled the pinned homepage at 0%, 20%, 40%, 60%, 80%, and 100% on 1440×900 and 390×844. All six states reached different camera X/Z values; no browser page errors occurred. 768×1024 was also tested at 0/50/100 with no horizontal overflow. See `VISUAL_ACCEPTANCE.md` and `visual-evidence/v2-six-scenes/`.
- Updated desktop renderer samples at the checkpoints: 306/69,224; 468/106,880; 419/96,758; 243/53,180; 157/34,566; 140/34,706 draw calls/triangles. These are SwiftShader stats, not FPS or device performance measurements.
- Reduced-motion and WebGL-disabled mobile tests showed the six-step static fallback, owner-photo content, and working catalogue link. Canvas is not a commerce prerequisite.
- No SQL writes, transaction fixtures, or user records were changed during the V2 3D implementation. Current active real-product count and orders remain zero; all six seeded demo products remain archived.
- No benchmark was run on a physical GPU/device. Scene modeling is procedural primitive geometry; bespoke polished GLB assets could improve art fidelity.
- Admin alignment: a temporary QA admin rendered the real PHP dashboard at 1440px and 390px. Forest/ivory palette applied, mobile menu visible, no horizontal overflow, no page errors; QA user and login rows were deleted after capture.
