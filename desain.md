# BENERANJUALAN.ID — DESIGN CONTRACT V2
## IMMERSIVE 3D FLORIST STORY • SCROLL-DRIVEN CAMERA • REAL COMMERCE

**Status:** SPECIFICATION REPLACEMENT — use this file to replace the earlier `desain.md` for the storefront redesign.  
**Reference:** `video referensi.mp4` (second video, healthcare/isometric 3D scroll experience).  
**Current result:** `20261107-1544-04.5897627.mp4` (first video, current florist site).  
**Priority:** Match the **mechanics and spatial storytelling** of the second video, not its healthcare subject or exact art assets.  
**Core rule:** A conventional vertical editorial page with bouquet photos and an SVG branch is **NOT an acceptable completion**.

---

## 1. NON-NEGOTIABLE VISUAL TARGET

In the reference, the key visual effect is a single connected 3D/isometric world. The camera glides between miniature environments as the user scrolls. A continuous accent-color route/ribbon guides the eye. Each camera stop has dedicated typography, context and useful interactions. A functional multi-step booking overlay appears later in the experience.

Our website must recreate that **type of experience** as an original boutique florist world:

- A real rendered **3D environment**, not 2D cards pretending to be 3D.
- Distinctive **isometric/orthographic camera** focused on miniature florist sets.
- At least **five connected scenes**, with clearly different objects, compositions and camera positions.
- **Scroll progress controls the 3D camera position and orientation**, including transitions between scenes.
- A continuous **botanical ribbon / winding pathway** links scenes spatially. It is not a vertical SVG on top of the page.
- The editorial copy and actions update in sync with camera scene transitions.
- The active story is **pinned** over a bounded scroll range; after it, normal catalog/checkout continues.
- Product listing and checkout remain **real e-commerce functionality** powered by PHP/MySQL.
- The actual bouquet product photos supplied by the owner are used for product cards, product details, and optional scene photo frames; they are not magically converted into fabricated 3D replicas.

**Important distinction:** The reference video depicts medical spaces. Build a fully ORIGINAL miniature floral universe; never copy healthcare assets, logos, layouts, or specific scenes frame by frame.

---

## 2. DIAGNOSIS OF PREVIOUS MISMATCH

The current recorded site looks like an ordinary editorial florist page: creamy background, navbar, serif headline, arch-shaped bouquet images, light stem doodle, vertical scroll sections, CTA and footer. It is polished but NOT visually analogous to the reference.

The prior design instruction encouraged the wrong output: '3D optional', 'avoid pinned scroll', 'real photography center stage', 'simple SVG botanical path'. All of these can lead to a tasteful but largely flat landing page.

In this V2 contract: **3D world + pinned camera-driven scroll story are P0 acceptance requirements**, not decorative optional extras. Aesthetic styling alone is insufficient.

---

## 3. VISUAL CONCEPT — 'A FLOWER'S JOURNEY'

Art direction: high-end, modern boutique florist told through a stylized miniature set. Tactile matte material, handcrafted 3D look, carefully arranged botanical colors. The story follows a bouquet from the flower studio to a personal gift, with a functional path into shopping.

### Suggested brand tokens

| Token | Value | Use |
|---|---|---|
| Canvas | `#F7F5F0` | main light background |
| Paper | `#FFFEFB` | cards/dialogs |
| Ink | `#21382E` | main typography |
| Forest | `#285742` | primary action |
| Sage | `#9FB4A1` | muted 3D botanical accents |
| Clay blush | `#D78F91` | ribbon / focus color |
| Pale rose | `#F4E0DF` | section transitions |
| Sand | `#E9DFCF` | architectural sets |
| Outline | `#DADCD4` | quiet borders |

Contrast must satisfy WCAG AA for core text and controls. Use two font families maximum: elegant readable editorial serif for storytelling headings, crisp sans-serif for all product and checkout UI. Desktop body ~16–18px; mobile ~16px; no tiny navigational labels.

### Geometry and lighting

- Isometric/orthographic camera; stable horizon and cinematic but restrained shadows.
- Clay-like low-poly stylized architecture with small flower props; coherent scale across scenes.
- Soft directional lighting, subtle ambient occlusion if affordable, muted physically coherent colors.
- Zero random neon/glassmorphism/gradient-filled AI dashboards.
- Optimized props: shared geometries, instancing where appropriate, compressed textures, controlled draw calls.
- Real product photos remain truthful. Use them outside the 3D world or on explicit display panels in-world, not as fake 3D scans.

---

## 4. STORYBOARD — SIX CONNECTED 3D SCENES

**All 6 scenes live within ONE navigable world or a convincingly continuous 3D sequence; the camera moves between them as scroll progress advances.** Story content can be displayed with accessible HTML above the canvas.

### Scene A · 0–16% — 'Bunga untuk momen yang berharga'

- Camera opens on a miniature floral storefront set: facade, awning, display window, flower crates, a few original stylized flowers, pedestrian/cobbled base.
- A blush botanical ribbon/path leads from foreground into the next space.
- Short typographic hero: distinctive 2-line headline and straightforward `Jelajahi koleksi` CTA.
- Entrance motion: camera pushes in gently, elements reveal sequentially.
- Frame should unmistakably look like a **3D set**, not a photo card.

### Scene B · 16–32% — 'Temukan yang cocok untuk momennya'

- Camera travels along the ribbon into the boutique interior: shelves, vase displays, small arrangements, wrapping display.
- Original 3D bouquets serve as ambience, not product-exact depictions.
- DOM product cards can float alongside the scene and are fed by the real DB (selected featured products only).
- Action: `Lihat koleksi` navigates to the full real catalog.

### Scene C · 32–49% — 'Dirangkai dengan perhatian'

- Camera orbits/pans to a florist workbench: scissors, rolls of paper, ribbon spools, flowers in progress.
- Small timeline of verified preparation steps if actual store process has been confirmed.
- Visual motion: paper unwrap, leaves subtly sway, flowers arranged via simple keyframed transforms.
- No invented owner/team facts or guaranteed turnaround.

### Scene D · 49–66% — 'Beri sentuhan personal'

- Camera moves to a packaging/personalization set: folded card, ribbon, gift tag, a bouquet on a workbench.
- Real interactive CTA opens a customization / product detail entry point; provide card-message editing only if backend supports it.
- A small HTML card-preview may be overlaid and updates from real typed text (not hardcoded mock UI).

### Scene E · 66–83% — 'Dari toko hingga penerima'

- Camera follows ribbon through miniature packing / delivery scene: prepared package, pickup counter, delivery van or handover vignette.
- Only show actual service choices supported by checkout config (delivery or pickup); no fabricated free shipping / same-day promises.
- Scrolled camera path visibly connects this to previous scene.

### Scene F · 83–100% — 'Siap mengirim cerita?'

- Camera reaches final set: bouquet pedestal / gift under an arch, boutique signage, flower arrangement around.
- Strong CTA `Pilih buket` and secondary `Lihat cara pesan`.
- After story ends, scroll naturally to a real commerce collection and footer. Make content discoverable to users skipping motion.

**Desktop layout:** pinned canvas occupying prominent right/center ~55–70% width, stage-aware editorial text/actions on left or positioned deliberately; avoid overlap.  
**Mobile:** responsive lighter version using same story and scene progression (lower geometry, fewer particles), or well-designed 2.5D scene snapshots only when WebGL is unavailable. Preserve all content and buying actions. Do not make mobile a blank static version.

---

## 5. MOTION ENGINEERING — REQUIRED

### Motion architecture

Preferred: **Three.js** (WebGL) + **GSAP ScrollTrigger** (scrub and pin), within the existing PHP application. Use a JavaScript build tool only if the existing project needs it; do not rebuild the commerce backend as an unrelated app.

- A dedicated `<canvas>` for the spatial environment; accessible DOM headings/buttons layered separately.
- Create 6 scene anchors/camera targets with explicit camera position, look-at target, and visual focus.
- Use scroll progress 0..1 to interpolate camera position/orientation and scene-specific object transforms. Use easing carefully; no snap that unexpectedly steals scrolling.
- `ScrollTrigger` pin for **bounded** storytelling (e.g. ~450–550vh additional scroll travel on desktop). Outside that sequence, normal browser scrolling.
- A physically connected ribbon/tube/path in world coordinates guides the camera; do not mistake a plain fixed SVG line for this effect.
- Synchronize copy changes/active step indicator with camera stops.
- Implement hover/tap only on actually functional actions.
- `prefers-reduced-motion`: no forced camera fly-through, show concise sequential static scene snapshots and direct CTAs.
- Pause/reduce expensive rendering when offscreen; use device pixel ratio cap and adjust mobile scene complexity.
- Handle context loss / WebGL unsupported with a deliberate fallback, not a blank canvas.

### Target motion quality

- Each transition clearly changes perspective and object arrangement, not just opacity.
- The user should see **different miniature environments** around 0%, 20%, 40%, 60%, 80%, and 100% of pinned scene progress.
- No full-screen generic flower particles; no constant looping that competes with products; no excessive parallax on text.
- Do not block cart, checkout, or accessibility behind WebGL rendering.

### 3D asset pipeline

1. Audit available `.glb/.gltf/.blend` or equivalent original licensed 3D assets.
2. If none, build ORIGINAL stylized florist dioramas with procedural Three.js geometry (flower stems/petals/leaves, display shelves, countertop, shop façade, boxes and van). This may be simplified but must be genuinely dimensional and art-directed.
3. If an asset cannot reach professional reference-level finish programmatically, report this as a **real visual limitation** and list exact custom assets needed; do not mark the site as a match based only on hero typography.
4. Store scene assets in an organized `assets/3d` or `assets/scene` structure; optimize/lazy-load as appropriate.

---

## 6. COMMERCE LAYER — DO NOT BREAK SALES

The actual site was a PHP + PDO/MySQL storefront. Inspect the current code; the current project may have evolved. Keep working shop workflows intact:

- Database-driven catalog with real product photo, truthful product title/price/status and real filtering.
- Product detail (multiple real photos where supplied), available variants/add-ons only, card message when supported.
- Cart and server-side price calculation (not trusting client totals).
- Checkout with customer, recipient, delivery/pickup, message, actual payment method and accurate order status.
- Admin management of products, categories, stocks, photos, orders, statuses, fulfillment options and settings.
- Responsive commerce and accessible multi-step checkout inspired by the **interaction pattern** shown in reference at ~42–65s; not an exact medical booking copy.
- No fake sales, testimonial, prices, products, tracking, card payments or QRIS.
- Preserve any migrations/data; do not destructively recreate the database.

**Commerce must remain usable if the 3D story fails to load.** Add a visible `Skip intro / Langsung belanja` entry.

---

## 7. QUALITY GATES AND ACCEPTANCE TESTS

### MUST PASS: Visual parity in interaction mechanics

1. Hero screenshot shows a miniature **3D florist exterior/interior**, not only a photo arch.
2. Scroll 20% into story: camera has traveled to a **different** connected area.
3. Scroll 40%: flower arrangement/workbench set visible with changed 3D camera view.
4. Scroll 60%: gift personalization/wrapping scene visible.
5. Scroll 80%: shipping/pickup micro-set visible.
6. Scroll 100%: ending 3D composition + clear shop CTA.
7. A continuous curved ribbon/path is visible in multiple scenes and appears spatially connected.
8. Scrolling drives camera/world movement, not just HTML fade/translate.
9. Functional product card and checkout button lead to working routes.
10. The mobile fallback displays the brand story and gives fast access to purchase.

If 1–8 fail, **THIS IS NOT THE REQUESTED DESIGN** even if typography/colors are attractive.

### MUST PASS: Engineering/business

- Real products/prices/media from DB or explicit missing state; no invented inventory.
- PHP lint and JS build pass; no runtime errors in tested routes.
- Cart/checkout/admin smoke tests pass or unresolved blockers documented.
- Test 390×844, 768×1024 and 1440×900 viewports.
- Check 3D frame behavior on reduced-motion and low-performance/mobile modes.
- Visual captures at story key points; compare to *user reference mechanics*, not merely a generic florist moodboard.

---

## 8. IMPLEMENTATION ORDER

**Pass 0 — Audit:** inspect current repo, legacy design, real assets, actual DB state and working transactions. Backup before large edits.

**Pass 1 — Visual proof of concept (MANDATORY EARLY):** implement the home page 3D spatial world with at least 3 distinct miniature connected scenes and working scroll-scrub camera, and capture evidence. If still a flat editorial page, STOP styling and correct the rendering architecture.

**Pass 2 — Full motion story:** complete six environments, world ribbon, transitions, synced text, reduced-motion and mobile fallback.

**Pass 3 — Commerce integration:** connect actual product and checkout flows, polish cart/admin, fix discovered transaction risks; preserve data.

**Pass 4 — QA:** run app, inspect screenshots, test interaction mechanics and functionality, document actual results.

**NOTE:** The earlier prompt placed business feature work before any visual proof, and allowed 3D to be skipped. That produced the previous unwanted outcome. V2 requires an **early motion proof** while still forbidding breaking the existing business code.

---

## 9. REQUIRED DELIVERY RECORD

Alongside code edits, create `VISUAL_ACCEPTANCE.md` documenting:

- Which scenes were actually implemented (A–F) and which assets power them.
- Whether a real 3D canvas exists and which renderer is used.
- Whether scroll progress moves the actual camera (show code location).
- Evidence screenshots at the 6 key progress points, when screenshot/browser tooling is available.
- Browser/mobile performance observations, clearly distinguishing tested facts from estimates.
- Commerce routes smoke-tested and any missing DB connectivity.
- Any honest limitations, especially if a custom 3D art pipeline is still required.

**Do not say 'completed' if the result still resembles the first recorded video more than the second in spatial motion mechanics.**
