# OPENCode / CODEX — EXECUTION PROMPT: FIX THE WRONG BENERANJUALAN DESIGN

You are the lead creative frontend engineer, technical artist and full-stack PHP engineer for Beneranjualan.id.

## SITUATION

There are TWO videos:

- `20261107-1544-04.5897627.mp4` = CURRENT IMPLEMENTATION (WRONG visual direction): flat/editorial florist website, photos in arches, cream background, standard vertical scroll, SVG stems.
- `video referensi.mp4` = TARGET EXPERIENCE (the second video): **actual 3D/isometric miniature world, scroll-controlled camera traveling between connected environments, visible connected route, section-aware typography, interactive multistep booking**.

The customer is dissatisfied because the current site still looks like the first video. This is a genuine acceptance failure, not a request for minor polish.

**Read `desain.md` COMPLETELY. The file replaces all previous visual direction whenever instructions conflict.**

## WHAT WENT WRONG

The previous specifications described a premium editorial storefront with photos and botanical SVG lines, treating WebGL and pinned scroll as optional. Therefore the result became aesthetically polished but spatially flat. That is not what the client wants.

### NEW NON-NEGOTIABLE OUTCOME

**The homepage MUST use a genuine 3D/isometric floral mini-world and a scroll-driven camera journey.** Repainting the existing page, increasing animation durations, changing fonts, adding CSS transforms to bouquet photos, or adding drifting petals DOES NOT satisfy the request.

Do not copy the healthcare characters/rooms/logo from the reference. Translate the motion and staging pattern into an original flower boutique world.

## CURRENT CODEBASE RULES

1. Inspect the actual website files and Git status; back up before destructive work. Determine actual PHP frontend, template system, CSS/JS assets, database and current routes.
2. Preserve working product, cart, checkout, login and admin features; do not wipe production data or run destructive SQL.
3. Build the 3D scene as progressive enhancement in the existing PHP storefront using Three.js + GSAP ScrollTrigger or an equally capable stack; do NOT migrate to an unrelated framework merely for visual effects.
4. Keep real product photos for catalog/product pages. Create original procedural 3D florist geometry for storytelling or use properly licensed original 3D files if available. Do not pretend that product JPEGs are 3D assets.
5. Maintain a static/reduced-motion fallback with functional navigation and purchasing. Animated canvas must never block checkout.

## IMPLEMENTATION — DO THIS IN THIS ORDER

### Step 1 — Trace current vs target

Inspect both provided videos directly if accessible. Note these specific differences:

- Current: editorial heading + arch-shaped product photo. Target: isometric 3D boutique world.
- Current: normal scroll between separate photo sections. Target: connected mini-environments with actual camera travel controlled by scroll.
- Current: vertical floral SVG stem. Target: in-world path/ribbon connecting scenes.
- Current: static descriptions. Target: stage-aware overlays whose content changes with camera progression.
- Current: normal page CTAs only. Target: a detailed functional multi-step purchasing interaction inspired by the second video's booking modal pattern.

Write a brief analysis into `VISUAL_ACCEPTANCE.md` and continue implementation; do not stop at the analysis.

### Step 2 — Early visual proof (P0)

Before polishing the rest of the site, implement an actual spatial prototype with:

- A rendered `<canvas>`.
- Orthographic camera with directional/ambient lighting and shadows.
- 3 ORIGINAL 3D miniature florist environments (boutique exterior, florist workbench, gift wrapping station).
- A 3D curved botanical ribbon/path physically connecting spaces.
- A pinned scroll section controlling camera position/target with scrubbed progress.
- Desktop scene-aware HTML heading/CTA overlays.

Capture screenshots at scroll positions 0%, 40%, 80% when tooling permits. They MUST look like different 3D scenes. If they do not, improve the scene system before moving on. Do not replace them with still-image mockups.

### Step 3 — Complete the reference-style journey

Expand to the SIX scenes in `desain.md`: storefront → collections → craft → personal touch → packing/delivery → finale. Make all camera transitions spatially legible, with a persistent connected world identity. Fine-tune scene composition, animation pacing, lighting and camera movement. Respect prefers-reduced-motion and mobile.

### Step 4 — Connect REAL commerce

Keep DB-backed product tiles and customer journey. Build a functioning bouquet personalization and multi-step checkout, only using verified fields and payment settings. Do not invent products, prices, business claims, successful payments, customers or admin figures. Server must calculate final totals. Protect DB and credentials.

### Step 5 — Browser QA (REQUIRED)

Run the website locally, then inspect actual rendering at desktop and mobile sizes. Use browser/screenshots if supported. Test scrolling through the pinned sequence. Capture key frames at approximately 0%, 20%, 40%, 60%, 80%, 100% of story progress. Verify camera transforms actually change in response to scroll. Test no WebGL, reduced motion, and a commerce action. Verify that app still works when the 3D canvas is unavailable.

Check PHP lint, JS build, routes, database integration, mobile overflow, links, checkout as actually available. Report failures honestly.

## TESTABLE VISUAL ACCEPTANCE CHECKLIST

- [ ] Actual 3D mini-world is visible in the first viewport.
- [ ] Orthographic/isometric composition and consistent scene scale.
- [ ] Scroll moves the actual camera through connected sets.
- [ ] At least five meaningfully different 3D environments appear during the story.
- [ ] Spatial ribbon/path connects sets, not a page-level SVG line only.
- [ ] Stage labels/headings and live CTA are synchronized with world movement.
- [ ] Real product photos and DB data feed functional product/shopping pages.
- [ ] Actual shopping actions work.
- [ ] On small screens the story remains usable and shopping is fast.
- [ ] No fake testimonials/prices/payments, inaccessible motion, or blocked scroll.

IF ANY OF THE FIRST FIVE BOXES REMAIN UNCHECKED, DO NOT REPORT THE DESIGN AS 'MATCHING THE REFERENCE'. Explain what prevents completion and what genuine technical or art asset work is missing.

## DELIVERY

Make changes in the CURRENT PROJECT, not a sibling demo folder. Provide a concise result report including changed source files, live run commands, screenshots if possible, working routes and limitations. Update `VISUAL_ACCEPTANCE.md` with verified state and exact remaining problems.

**START NOW. The priority is to achieve the SECOND VIDEO's spatial 3D scroll experience, not to keep decorating the FIRST VIDEO's flat template.**
