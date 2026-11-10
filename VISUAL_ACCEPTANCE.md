# Visual acceptance — florist 3D story V2

Updated: 2026-11-08. This records what was actually rendered and tested locally; it is not a production GPU/performance certification.

## Current prototype state

- A real `<canvas>` renderer is installed on the existing PHP homepage. It uses a locally vendored Three.js **0.187.0** ES module (`bouquet/assets/vendor/`) under the Three.js MIT license.
- Camera type: `OrthographicCamera`. The pinned story uses a `600svh` desktop track (500 viewport-heights of scroll travel) and a `560svh` mobile track. Page scrolling remains native; no wheel hijack.
- Scroll progress interpolates camera position and look-at target between six anchors. The camera moves through one world coordinate system, not six image backgrounds.
- A continuous Three.js `TubeGeometry` ribbon connects the scene islands; scene groups are kept near the camera for a bounded draw list.
- Three-dimensional scene props are original procedural geometry. Real product photos are not turned into fake 3D models. Scene 02 can show a real DB product card; when there are no verified active products it shows an explicit empty-state message.
- Canvas failure/WebGL-disabled and `prefers-reduced-motion` modes display a sequential static fallback with direct catalogue navigation.

## Implemented scenes (A–F)

| Scene | Set | Camera/world content |
|---|---|---|
| A | Floral studio | Shop façade, awning, door/display, crates, potted blooms |
| B | Collection | Miniature bouquet display shelves, linked to real active catalogue products when present |
| C | Workbench | Florist bench, paper roll, scissors, flower stems and shelf |
| D | Personal touch | Gift/card wrapping table, bouquet and arch |
| E | Packing/handover | Prepared parcels and handover counter; no courier or delivery promise is implied |
| F | Finale | Bouquet pedestal, arch, planters and direct catalogue CTA |

The label, active step control, and real CTA change with the camera chapter. Product/checkout actions remain ordinary PHP routes.

## V2 acceptance checks

| Check | Result | Evidence/location |
|---|---|---|
| First viewport is a genuine 3D florist exterior | PASS | `visual-evidence/v2-six-scenes/desktop-000.jpg` |
| Orthographic camera and six distinct connected environments | PASS | `OrthographicCamera` in `florist-world.js`; six Playwright camera checkpoints above |
| Scroll moves the camera, not just HTML opacity | PASS | `updateScrollProgress()` and `applyCamera()` in `florist-world.js`; X/Z checkpoints above |
| One spatial ribbon connects the world | PASS | `TubeGeometry` at `florist-world.js:412`; shown across scene captures |
| Scene copy, nav state, and current CTA track progress | PASS | `updateCopy()` in `florist-world.js`; active scene 1–6 recorded in the browser probe |
| Real products and shop actions | PASS with empty-state | DB-backed product card appears only when an active non-demo product exists; current DB correctly says no active products. Catalog/checkout remain PHP routes; guest checkout E2E passed previously. |
| Mobile, reduced-motion, and WebGL fallback | PASS | Six 390×844 scene captures; reduced-motion and WebGL-disabled fallback checks showed six static chapters and catalog link |
| Live product, price, reviews, or payments fabricated | PASS | No active real products; demo products archived; no fabricated scene-product pricing |

## Browser evidence

Headless Microsoft Edge with WebGL/SwiftShader was run at **1440×900**, **768×1024**, and **390×844**. At desktop/mobile scroll progress 0%, 20%, 40%, 60%, 80%, and 100%, all six scenes reached distinct camera positions; the tablet viewport was also scrubbed at 0%, 50%, and 100% with no horizontal overflow. Browser page errors: **0**.

| Progress | Active scene | Camera `(x,z)` | Desktop draw calls / triangles |
|---:|---:|---:|---:|
| 0% | A | `(11,16)` | 306 / 69,224 |
| 20% | B | `(2,3)` | 468 / 106,880 |
| 40% | C | `(37,15)` | 419 / 96,758 |
| 60% | D | `(27,2)` | 243 / 53,180 |
| 80% | E | `(64,15)` | 157 / 34,566 |
| 100% | F | `(55,3)` | 140 / 34,706 |

Captured JPEGs are in `visual-evidence/v2-six-scenes/` (`desktop-000`…`desktop-100` and `mobile-000`…`mobile-100`). The camera values above were read from the rendered canvas after browser scrolling; they demonstrate that scroll changed the actual camera.

Additional fallback checks: reduced-motion mode and `--disable-webgl` both show the static story and a working “Jelajahi koleksi” link; no page errors observed. PHP regression suite: 31 passed. Guest checkout E2E (CSRF, server total, pending payment, stock decrement, idempotent retry and cleanup) passed in the prior follow-up run; no order/product fixture remains. The 3D work did not run SQL writes or alter transactions.

## Honest limitations / remaining work

- Scene geometry is procedurally built from primitive meshes. It is genuinely 3D and the camera/ribbon mechanics work, but the art is still a **stylized prototype**, not bespoke polished GLB dioramas at the reference video's art-direction fidelity. Custom, optimized florist-scene models/material work would improve the visual finish.
- No real-device frame-rate, GPU memory, Lighthouse, or 4G performance benchmark was measured. SwiftShader confirms mechanics/rendering, not desktop/mobile GPU performance.
- The highest observed desktop snapshot is about 468 draw calls / 106,880 triangles; the highest mobile snapshot is about 166 draw calls / 27,200 triangles. These renderer counters are scene samples, not FPS measurements.
- Current database has zero active verified products. The collection overlay therefore correctly shows no live offers; populate products/prices/stocks in Admin before sales.
- The brief refers to `references/20261107-1544-04.5897627.mp4`; that exact file was not present. `references/current-recording-2026-11-07-235934.mp4` (6.83 seconds) was used as the available current-site capture; `references/video-referensi.mp4` is the target.
