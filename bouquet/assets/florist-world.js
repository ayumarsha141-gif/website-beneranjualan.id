(() => {
    'use strict';

    const root = document.querySelector('[data-florist-world]');
    if (!root) return;

    const canvas = root.querySelector('[data-world-canvas]');
    const track = root.querySelector('[data-world-track]');
    const copies = [...root.querySelectorAll('[data-world-copy]')];
    const sceneButtons = [...root.querySelectorAll('[data-world-jump]')];
    const navProgress = root.querySelector('[data-world-nav-progress]');
    const fallbackNote = root.querySelector('[data-world-fallback-note]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const showFallback = (reason = '') => {
        root.dataset.worldState = 'fallback';
        root.classList.add('is-fallback');
        if (fallbackNote && reason) fallbackNote.textContent = reason;
    };

    if (reducedMotion) {
        root.dataset.worldState = 'reduced-motion';
        root.classList.add('is-reduced');
        return;
    }

    async function startFloristWorld() {
        try {
            const THREE = await import('./vendor/three.module.js');
            if (!canvas || !track) throw new Error('Elemen canvas cerita tidak ditemukan.');

            const isMobile = window.matchMedia('(max-width: 760px)').matches;
            const pixelRatio = Math.min(window.devicePixelRatio || 1, isMobile ? 1 : 1.5);
            const scene = new THREE.Scene();
            const worldColor = new THREE.Color('#F7F5F0');
            scene.background = worldColor;
            scene.fog = new THREE.Fog(worldColor, 57, 100);

            const renderer = new THREE.WebGLRenderer({
                canvas,
                antialias: !isMobile,
                alpha: false,
                powerPreference: 'low-power',
            });
            renderer.setPixelRatio(pixelRatio);
            renderer.outputColorSpace = THREE.SRGBColorSpace;
            renderer.toneMapping = THREE.ACESFilmicToneMapping;
            renderer.toneMappingExposure = 0.96;
            renderer.shadowMap.enabled = !isMobile;
            renderer.shadowMap.type = THREE.PCFSoftShadowMap;
            renderer.setClearColor('#F7F5F0', 1);

            const camera = new THREE.OrthographicCamera(-10, 10, 7, -7, 0.1, 180);
            const hemisphere = new THREE.HemisphereLight('#fffdf7', '#899b87', 1.35);
            scene.add(hemisphere);
            const keyLight = new THREE.DirectionalLight('#fff4df', 2.2);
            keyLight.position.set(-12, 20, 18);
            keyLight.castShadow = !isMobile;
            keyLight.shadow.mapSize.set(1024, 1024);
            keyLight.shadow.camera.left = -40;
            keyLight.shadow.camera.right = 70;
            keyLight.shadow.camera.top = 35;
            keyLight.shadow.camera.bottom = -35;
            keyLight.shadow.bias = -0.00025;
            scene.add(keyLight);

            const material = (color, roughness = 0.86, extra = {}) => new THREE.MeshStandardMaterial({
                color, roughness, ...extra,
            });
            const mats = {
                canvas: material('#F7F5F0'),
                sand: material('#DCCDB5'),
                sandLight: material('#ECE1CF'),
                paper: material('#FFFEFB'),
                wood: material('#916646'),
                woodLight: material('#B8875D'),
                forest: material('#285742'),
                sage: material('#9FB4A1'),
                sageDark: material('#718C73'),
                clay: material('#C97C81'),
                clayLight: material('#E8B4AF'),
                rose: material('#F1D8D5'),
                glass: material('#B7CFCC', 0.38, { transparent: true, opacity: 0.72 }),
                soil: material('#80654D'),
                white: material('#FFFDF8'),
                yellow: material('#D9B66E'),
                red: material('#B95F59'),
                blue: material('#7897A1'),
            };

            const geo = {
                box: new THREE.BoxGeometry(1, 1, 1),
                sphere: new THREE.SphereGeometry(1, 12, 8),
                smallSphere: new THREE.SphereGeometry(1, 8, 6),
                cylinder: new THREE.CylinderGeometry(0.5, 0.5, 1, 10),
                pot: new THREE.CylinderGeometry(0.4, 0.28, 0.7, 9),
                cone: new THREE.CylinderGeometry(0.82, 0.24, 1.35, 7, 1, true),
                petal: new THREE.SphereGeometry(1, 10, 6),
                torus: new THREE.TorusGeometry(0.25, 0.055, 6, 16),
            };

            function addBox(parent, size, position, mat, rotation = [0, 0, 0], castsShadow = true) {
                const mesh = new THREE.Mesh(geo.box, mat);
                mesh.scale.set(size[0], size[1], size[2]);
                mesh.position.set(position[0], position[1], position[2]);
                mesh.rotation.set(rotation[0], rotation[1], rotation[2]);
                mesh.castShadow = castsShadow && !isMobile;
                mesh.receiveShadow = true;
                parent.add(mesh);
                return mesh;
            }

            function addCylinder(parent, radiusTop, radiusBottom, height, position, mat, rotation = [0, 0, 0], radial = 10) {
                const mesh = new THREE.Mesh(new THREE.CylinderGeometry(radiusTop, radiusBottom, height, radial), mat);
                mesh.position.set(position[0], position[1], position[2]);
                mesh.rotation.set(rotation[0], rotation[1], rotation[2]);
                mesh.castShadow = !isMobile;
                mesh.receiveShadow = true;
                parent.add(mesh);
                return mesh;
            }

            function addFlower(parent, position, petalMaterial, scale = 1, sway = false) {
                const flower = new THREE.Group();
                flower.position.set(position[0], position[1], position[2]);
                flower.scale.setScalar(scale);
                flower.userData.sway = sway;
                parent.add(flower);

                const stem = new THREE.Mesh(geo.cylinder, mats.sageDark);
                stem.scale.set(0.055, 1.12, 0.055);
                stem.position.y = 0.48;
                stem.castShadow = !isMobile;
                flower.add(stem);

                [-1, 1].forEach((side, index) => {
                    const leaf = new THREE.Mesh(geo.sphere, index ? mats.sage : mats.forest);
                    leaf.scale.set(0.25, 0.075, 0.14);
                    leaf.position.set(side * 0.17, 0.42 + index * 0.14, 0.02);
                    leaf.rotation.z = side * -0.42;
                    flower.add(leaf);
                });

                const head = new THREE.Group();
                head.position.y = 1.12;
                flower.add(head);
                const petals = isMobile ? 5 : 7;
                const petalInstances = new THREE.InstancedMesh(geo.petal, petalMaterial, petals);
                const petalTransform = new THREE.Object3D();
                for (let i = 0; i < petals; i += 1) {
                    const angle = (i / petals) * Math.PI * 2;
                    petalTransform.scale.set(0.20, 0.095, 0.32);
                    petalTransform.position.set(Math.cos(angle) * 0.28, 0, Math.sin(angle) * 0.28);
                    petalTransform.rotation.set(0, -angle, 0);
                    petalTransform.updateMatrix();
                    petalInstances.setMatrixAt(i, petalTransform.matrix);
                }
                petalInstances.instanceMatrix.needsUpdate = true;
                petalInstances.castShadow = !isMobile;
                head.add(petalInstances);
                const center = new THREE.Mesh(geo.smallSphere, mats.yellow);
                center.scale.set(0.15, 0.11, 0.15);
                center.position.y = 0.04;
                head.add(center);
                return flower;
            }

            function addBouquet(parent, position, size = 1, palette = [mats.clay, mats.white, mats.rose]) {
                const group = new THREE.Group();
                group.position.set(position[0], position[1], position[2]);
                group.scale.setScalar(size);
                parent.add(group);
                const wrap = new THREE.Mesh(geo.cone, mats.rose);
                wrap.position.y = 0.82;
                wrap.castShadow = !isMobile;
                group.add(wrap);
                addBox(group, [0.48, 0.09, 0.42], [0, 0.2, 0], mats.clay, [0, 0.15, 0]);
                const positions = [[-0.38, 1.38, 0], [-0.16, 1.58, 0.02], [0.1, 1.62, -0.02], [0.36, 1.39, 0.02], [0, 1.82, 0]];
                positions.slice(0, isMobile ? 3 : positions.length).forEach((p, i) => {
                    addFlower(group, p, palette[i % palette.length], 0.75 + (i % 2) * 0.06, true);
                });
                return group;
            }

            function addPlanter(parent, position, flowers = 3) {
                const pot = new THREE.Mesh(geo.pot, mats.clayLight);
                pot.position.set(position[0], position[1] + 0.36, position[2]);
                pot.castShadow = !isMobile;
                parent.add(pot);
                for (let i = 0; i < flowers; i += 1) {
                    const angle = (i / flowers) * Math.PI * 2;
                    const colors = [mats.clay, mats.white, mats.rose, mats.yellow];
                    addFlower(parent, [position[0] + Math.cos(angle) * 0.22, position[1] + 0.55, position[2] + Math.sin(angle) * 0.22], colors[i % colors.length], 0.48, true);
                }
            }

            function makeIsland(center, width = 8.5, depth = 7.4) {
                const group = new THREE.Group();
                group.position.set(center[0], 0, center[2]);
                scene.add(group);
                addBox(group, [width, 0.5, depth], [0, -0.12, 0], mats.sand, [0, 0, 0]);
                addBox(group, [width - 0.18, 0.08, depth - 0.18], [0, 0.16, 0], mats.sandLight, [0, 0, 0], false);
                return group;
            }

            function addShopSign(parent, position) {
                const signCanvas = document.createElement('canvas');
                signCanvas.width = 640;
                signCanvas.height = 150;
                const ctx = signCanvas.getContext('2d');
                ctx.fillStyle = '#FFFEFB';
                ctx.fillRect(0, 0, signCanvas.width, signCanvas.height);
                ctx.fillStyle = '#285742';
                ctx.font = '600 48px Georgia, serif';
                ctx.textAlign = 'center';
                ctx.fillText('BENERANJUALAN', 320, 69);
                ctx.fillStyle = '#8A7968';
                ctx.font = '22px Arial, sans-serif';
                ctx.fillText('HANDMADE BOUQUETS', 320, 112);
                const texture = new THREE.CanvasTexture(signCanvas);
                texture.colorSpace = THREE.SRGBColorSpace;
                const sign = new THREE.Mesh(new THREE.PlaneGeometry(3.15, 0.74), new THREE.MeshBasicMaterial({ map: texture }));
                sign.position.set(position[0], position[1], position[2]);
                parent.add(sign);
            }

            const centers = [[0, 0, 0], [13, 0, -9], [26, 0, 0], [39, 0, -9], [52, 0, 0], [65, 0, -9]];
            const sceneGroups = [];

            function buildBoutiqueExterior(center) {
                const g = makeIsland(center);
                sceneGroups.push(g);
                addBox(g, [6.4, 3.5, 0.42], [0, 2.0, -2.05], mats.sand);
                addBox(g, [6.8, 0.28, 4.3], [0, 3.9, -0.8], mats.clay, [0.04, 0, 0]);
                addBox(g, [6.95, 0.14, 0.3], [0, 3.66, 1.34], mats.woodLight);
                addBox(g, [3.0, 0.16, 1.38], [-1.55, 3.12, -0.62], mats.rose, [-0.16, 0, 0]);
                addBox(g, [3.0, 0.16, 1.38], [1.55, 3.12, -0.62], mats.white, [-0.16, 0, 0]);
                [-2.45, -0.82, 0.82, 2.45].forEach((x, i) => addBox(g, [0.77, 0.12, 1.28], [x, 3.14, -0.61], i % 2 ? mats.white : mats.clay, [-0.16, 0, 0]));

                [-1.85, -0.95].forEach((x) => {
                    addBox(g, [1.56, 1.7, 0.11], [x, 2.25, -1.79], mats.wood);
                    addBox(g, [1.35, 1.48, 0.08], [x, 2.25, -1.70], mats.glass, [0, 0, 0], false);
                    addBox(g, [1.3, 0.08, 0.12], [x, 1.95, -1.61], mats.white, [0, 0, 0], false);
                });
                addBox(g, [0.92, 2.12, 0.15], [1.76, 1.75, -1.75], mats.wood);
                addBox(g, [0.68, 1.8, 0.08], [1.76, 1.75, -1.64], mats.forest, [0, 0, 0], false);
                addBox(g, [0.1, 0.1, 0.18], [2.02, 1.6, -1.52], mats.yellow, [0, 0, 0], false);
                addShopSign(g, [0, 3.35, -0.1]);
                addBox(g, [7.2, 0.24, 0.5], [0, 0.54, 1.5], mats.woodLight);
                addBox(g, [1.35, 0.66, 0.8], [-2.2, 0.95, 1.4], mats.wood);
                addBox(g, [1.14, 0.1, 0.68], [-2.2, 1.32, 1.4], mats.woodLight);
                addBouquet(g, [-2.2, 1.36, 1.4], 0.48, [mats.clay, mats.white, mats.rose]);
                addPlanter(g, [-3.0, 0.48, 2.25], 3);
                addPlanter(g, [3.0, 0.48, 2.25], 3);
                addBox(g, [1.3, 0.12, 1.0], [2.55, 0.63, 0.45], mats.clayLight);
                addBouquet(g, [2.55, 0.72, 0.45], 0.44, [mats.white, mats.yellow, mats.clay]);
                [-2.4, -1.2, 0, 1.2, 2.4].forEach((x, i) => addBox(g, [0.9, 0.06, 0.58], [x, 0.49, 2.85], i % 2 ? mats.sand : mats.sandLight, [0, i % 2 ? 0.025 : -0.025, 0], false));
                const passerby = new THREE.Group();
                passerby.position.set(3.0, 0.48, 1.25);
                g.add(passerby);
                addBox(passerby, [0.43, 0.78, 0.3], [0, 0.95, 0], mats.forest, [0, 0, -0.05]);
                const passerbyHead = new THREE.Mesh(geo.sphere, mats.woodLight);
                passerbyHead.scale.set(0.22, 0.25, 0.22);
                passerbyHead.position.set(0, 1.48, 0);
                passerby.add(passerbyHead);
                addBox(passerby, [0.17, 0.58, 0.18], [-0.13, 0.31, 0], mats.wood);
                addBox(passerby, [0.17, 0.58, 0.18], [0.13, 0.31, 0], mats.wood);
                return g;
            }

            function buildCollectionDisplay(center) {
                const g = makeIsland(center, 9, 7.8);
                sceneGroups.push(g);
                addBox(g, [6.8, 3.5, 0.28], [0, 1.92, -2.35], mats.sand);
                addBox(g, [5.9, 0.18, 0.38], [0, 0.85, -2.08], mats.wood);
                addBox(g, [5.9, 0.18, 0.38], [0, 1.8, -2.08], mats.woodLight);
                addBox(g, [5.9, 0.18, 0.38], [0, 2.74, -2.08], mats.wood);
                [-2.65, 0, 2.65].forEach(x => addBox(g, [0.14, 2.5, 0.25], [x, 1.88, -2.05], mats.forest));
                const palettes = [
                    [mats.clay, mats.white, mats.rose],
                    [mats.white, mats.yellow, mats.sage],
                    [mats.rose, mats.clay, mats.white],
                ];
                [-1.8, 0, 1.8].forEach((x, i) => {
                    addBox(g, [1.38, 0.12, 1.08], [x, 1.0, -0.9], i === 1 ? mats.rose : mats.sandLight);
                    addBouquet(g, [x, 1.08, -0.9], 0.56, palettes[i]);
                    addBox(g, [1.48, 0.12, 1.1], [x, 1.0, 1.0], mats.woodLight);
                    addBouquet(g, [x, 1.08, 1.0], 0.48, palettes[(i + 1) % palettes.length]);
                });
                addPlanter(g, [-3.2, 0.42, 2.3], 3);
                addPlanter(g, [3.2, 0.42, 2.3], 3);
                return g;
            }

            function buildWorkbench(center) {
                const g = makeIsland(center, 9, 7.8);
                sceneGroups.push(g);
                addBox(g, [5.3, 0.24, 2.6], [0, 1.4, 0.1], mats.woodLight);
                [[-2.25, 0.7, -0.92], [2.25, 0.7, -0.92], [-2.25, 0.7, 1.12], [2.25, 0.7, 1.12]].forEach(p => addBox(g, [0.18, 1.35, 0.18], p, mats.wood));
                addBox(g, [4.8, 0.16, 0.32], [0, 1.95, -2.25], mats.wood);
                addBox(g, [0.17, 2.0, 0.18], [-2.25, 1.0, -2.25], mats.wood);
                addBox(g, [0.17, 2.0, 0.18], [2.25, 1.0, -2.25], mats.wood);
                addBox(g, [4.5, 0.14, 0.28], [0, 2.65, -2.25], mats.woodLight);
                [-1.55, 0, 1.55].forEach((x, i) => {
                    addBox(g, [0.82, 0.62, 0.65], [x, 2.28, -2.08], i === 1 ? mats.rose : mats.sandLight);
                    addBouquet(g, [x, 2.55, -2.08], 0.34, [mats.clay, mats.white, mats.yellow]);
                });
                addBouquet(g, [-1.05, 1.52, -0.1], 0.62, [mats.clay, mats.white, mats.rose]);
                addBox(g, [1.2, 0.1, 0.78], [1.2, 1.56, 0.05], mats.paper);
                addBox(g, [0.78, 0.09, 0.22], [1.18, 1.68, 0.05], mats.rose);
                const roll = addCylinder(g, 0.28, 0.28, 1.4, [2.0, 1.77, 0.55], mats.clayLight, [0, 0, Math.PI / 2], 12);
                roll.castShadow = !isMobile;
                addBox(g, [0.14, 0.18, 0.9], [0.8, 1.58, 0.95], mats.forest, [0, 0, 0.1]);
                addBox(g, [0.14, 0.18, 0.9], [1.18, 1.58, 0.95], mats.woodLight, [0, 0, -0.1]);
                const scissorRingA = new THREE.Mesh(geo.torus, mats.forest);
                scissorRingA.position.set(0.72, 1.59, -0.6);
                scissorRingA.scale.set(1, 1, 0.4);
                g.add(scissorRingA);
                const scissorRingB = new THREE.Mesh(geo.torus, mats.forest);
                scissorRingB.position.set(1.12, 1.59, -0.6);
                scissorRingB.scale.set(1, 1, 0.4);
                g.add(scissorRingB);
                addBox(g, [0.08, 0.07, 0.8], [0.91, 1.59, -0.55], mats.silver || mats.white, [0, 0, 0.08], false);
                addPlanter(g, [-3.2, 0.42, 1.8], 4);
                return g;
            }

            function buildPersonalization(center) {
                const g = makeIsland(center, 8.8, 7.6);
                sceneGroups.push(g);
                addBox(g, [5.5, 0.25, 3.0], [0, 1.35, 0], mats.woodLight);
                [[-2.3, 0.68, -1.1], [2.3, 0.68, -1.1], [-2.3, 0.68, 1.1], [2.3, 0.68, 1.1]].forEach(p => addBox(g, [0.18, 1.3, 0.18], p, mats.wood));
                addCylinder(g, 0.075, 0.075, 1.6, [-2.25, 2.15, -1.65], mats.forest, [0, 0, 0], 10);
                addCylinder(g, 0.075, 0.075, 1.6, [2.25, 2.15, -1.65], mats.forest, [0, 0, 0], 10);
                const arch = new THREE.Mesh(new THREE.TorusGeometry(2.25, 0.11, 8, 32, Math.PI), mats.clay);
                arch.position.set(0, 3.0, -1.65);
                arch.castShadow = !isMobile;
                g.add(arch);

                const gift = addBox(g, [1.35, 1.25, 1.3], [-1.1, 2.08, 0.1], mats.rose);
                gift.rotation.y = -0.16;
                addBox(g, [1.42, 0.15, 1.38], [-1.1, 2.78, 0.1], mats.clayLight, [0, -0.16, 0]);
                addBox(g, [0.2, 1.4, 1.4], [-1.1, 2.06, 0.1], mats.clay, [0, -0.16, 0]);
                addBox(g, [1.0, 0.08, 0.58], [0.85, 1.54, -0.35], mats.paper, [0, 0.1, 0]);
                addBox(g, [0.7, 0.055, 0.08], [0.85, 1.62, -0.35], mats.forest, [0, 0.1, 0]);
                addBox(g, [0.5, 0.06, 0.5], [1.2, 1.55, 0.8], mats.yellow);
                addBouquet(g, [1.2, 1.62, 0.8], 0.52, [mats.clay, mats.white, mats.yellow]);
                addPlanter(g, [-3.1, 0.42, 1.8], 4);
                return g;
            }

            function buildPackingHandover(center) {
                const g = makeIsland(center, 9.2, 7.8);
                sceneGroups.push(g);
                addBox(g, [5.8, 0.24, 2.7], [0, 1.15, 0.2], mats.woodLight);
                [[-2.45, 0.58, -0.8], [2.45, 0.58, -0.8], [-2.45, 0.58, 1.2], [2.45, 0.58, 1.2]].forEach(p => addBox(g, [0.18, 1.12, 0.18], p, mats.wood));
                addBox(g, [3.4, 2.5, 0.22], [0, 1.65, -2.05], mats.sandLight);
                addBox(g, [2.4, 0.55, 0.12], [0, 2.15, -1.91], mats.forest);
                addBox(g, [1.15, 0.8, 1.0], [-1.35, 1.67, 0.15], mats.rose);
                addBox(g, [1.2, 0.12, 1.06], [-1.35, 2.14, 0.15], mats.clayLight);
                addBox(g, [0.18, 1.02, 1.03], [-1.35, 1.67, 0.15], mats.clay);
                addBox(g, [1.2, 0.8, 1.0], [0.45, 1.67, 0.25], mats.paper);
                addBox(g, [1.2, 0.12, 1.06], [0.45, 2.14, 0.25], mats.sand);
                addBox(g, [0.16, 1.02, 1.03], [0.45, 1.67, 0.25], mats.rose);
                addCylinder(g, 0.18, 0.18, 0.1, [2.15, 1.48, 0.62], mats.forest, [Math.PI / 2, 0, 0], 12);
                addBox(g, [0.12, 0.46, 0.1], [2.15, 1.76, 0.62], mats.woodLight);
                addPlanter(g, [-3.4, 0.42, 1.7], 3);
                return g;
            }

            function buildFinale(center) {
                const g = makeIsland(center, 9, 8);
                sceneGroups.push(g);
                addBox(g, [0.3, 3.6, 0.3], [-2.7, 1.96, -1.5], mats.forest);
                addBox(g, [0.3, 3.6, 0.3], [2.7, 1.96, -1.5], mats.forest);
                const finalArch = new THREE.Mesh(new THREE.TorusGeometry(2.7, 0.15, 10, 40, Math.PI), mats.clay);
                finalArch.position.set(0, 3.75, -1.5);
                finalArch.castShadow = !isMobile;
                g.add(finalArch);
                addCylinder(g, 1.0, 1.18, 0.65, [0, 0.62, 0.2], mats.sandLight, [0, 0, 0], 8);
                addCylinder(g, 0.72, 0.82, 0.35, [0, 1.12, 0.2], mats.woodLight, [0, 0, 0], 8);
                addBouquet(g, [0, 1.28, 0.2], 0.88, [mats.clay, mats.white, mats.rose, mats.yellow]);
                [-3.2, 3.2].forEach((x, i) => addPlanter(g, [x, 0.42, 1.8], i ? 4 : 3));
                addBox(g, [1.5, 0.12, 0.75], [0, 0.52, 2.6], mats.rose);
                return g;
            }

            const ground = new THREE.Mesh(new THREE.PlaneGeometry(160, 110), mats.canvas);
            ground.rotation.x = -Math.PI / 2;
            ground.position.set(32, -0.42, -4);
            ground.receiveShadow = true;
            scene.add(ground);

            buildBoutiqueExterior(centers[0]);
            buildCollectionDisplay(centers[1]);
            buildWorkbench(centers[2]);
            buildPersonalization(centers[3]);
            buildPackingHandover(centers[4]);
            buildFinale(centers[5]);

            const routePoints = [
                new THREE.Vector3(-4.5, 0.12, 7.2), new THREE.Vector3(-1.2, 0.12, 5.0),
                new THREE.Vector3(3.3, 0.12, 1.9), new THREE.Vector3(7.4, 0.12, -4.5),
                new THREE.Vector3(11.0, 0.12, -7.2), new THREE.Vector3(15.5, 0.12, -5.5),
                new THREE.Vector3(20.3, 0.12, 0.4), new THREE.Vector3(25.5, 0.12, 4.2),
                new THREE.Vector3(30.0, 0.12, -3.6), new THREE.Vector3(36.5, 0.12, -7.0),
                new THREE.Vector3(42.4, 0.12, -2.5), new THREE.Vector3(48.1, 0.12, 3.2),
                new THREE.Vector3(54.0, 0.12, -2.5), new THREE.Vector3(60.3, 0.12, -7.2),
                new THREE.Vector3(66.0, 0.12, -5.0), new THREE.Vector3(70.0, 0.12, -1.2),
            ];
            const routeCurve = new THREE.CatmullRomCurve3(routePoints);
            const ribbon = new THREE.Mesh(new THREE.TubeGeometry(routeCurve, 220, 0.13, 8, false), mats.clay);
            ribbon.castShadow = !isMobile;
            scene.add(ribbon);
            [routePoints[1], routePoints[4], routePoints[7], routePoints[10], routePoints[13], routePoints[15]].forEach((point, i) => {
                const marker = new THREE.Mesh(geo.sphere, i === 1 ? mats.sageDark : mats.rose);
                marker.scale.set(0.22, 0.1, 0.22);
                marker.position.copy(point).setY(0.28);
                scene.add(marker);
            });

            const centers3 = centers.map(c => new THREE.Vector3(c[0], 0, c[2]));
            const cameraOffsets = [
                new THREE.Vector3(11, 13, 16),
                new THREE.Vector3(-11, 14, 12),
                new THREE.Vector3(11, 13, 15),
                new THREE.Vector3(-12, 14, 11),
                new THREE.Vector3(12, 13, 15),
                new THREE.Vector3(-10, 14, 12),
            ];
            const cameraAnchors = centers3.map((center, i) => ({
                position: center.clone().add(cameraOffsets[i]),
                target: center.clone().add(new THREE.Vector3(1.4, 1.15, 0)),
            }));

            const storyCopies = [...root.querySelectorAll('[data-world-copy]')];
            const navButtons = [...root.querySelectorAll('[data-world-jump]')];
            const progressFill = root.querySelector('[data-world-nav-progress]');
            const cameraPosition = new THREE.Vector3();
            const cameraTarget = new THREE.Vector3();
            const nextCameraPosition = new THREE.Vector3();
            const nextCameraTarget = new THREE.Vector3();
            let scrollProgress = 0;
            let easedProgress = 0;
            let lastSceneIndex = -1;
            let resizeObserver = null;
            let intersectionObserver = null;
            let raf = 0;
            let animationFramesLeft = 0;
            let visible = true;
            let lastTime = 0;
            let resizeFrame = 0;

            function updateSize() {
                resizeFrame = 0;
                const rect = canvas.getBoundingClientRect();
                const width = Math.max(1, Math.round(rect.width));
                const height = Math.max(1, Math.round(rect.height));
                const ratio = Math.min(window.devicePixelRatio || 1, isMobile ? 1 : 1.5);
                renderer.setPixelRatio(ratio);
                renderer.setSize(width, height, false);
                const frustumHeight = isMobile ? 15.5 : 14.5;
                const aspect = width / height;
                camera.left = -frustumHeight * aspect / 2;
                camera.right = frustumHeight * aspect / 2;
                camera.top = frustumHeight / 2;
                camera.bottom = -frustumHeight / 2;
                camera.updateProjectionMatrix();
                requestFrame();
            }

            function updateScrollProgress() {
                const rect = track.getBoundingClientRect();
                const scrollRange = Math.max(1, track.offsetHeight - window.innerHeight);
                scrollProgress = THREE.MathUtils.clamp(-rect.top / scrollRange, 0, 1);
                root.dataset.worldProgress = scrollProgress.toFixed(3);
                if (progressFill) {
                    const percent = `${(scrollProgress * 100).toFixed(1)}%`;
                    if (window.matchMedia('(max-width: 760px)').matches) progressFill.style.width = percent;
                    else progressFill.style.height = percent;
                }
                requestFrame();
            }

            function updateCopy(progress) {
                const sceneIndex = Math.round(progress * (storyCopies.length - 1));
                if (sceneIndex === lastSceneIndex) return;
                lastSceneIndex = sceneIndex;
                storyCopies.forEach((copy, i) => {
                    const active = i === sceneIndex;
                    copy.classList.toggle('is-active', active);
                    copy.setAttribute('aria-hidden', String(!active));
                });
                navButtons.forEach((button, i) => {
                    const active = i === sceneIndex;
                    button.classList.toggle('is-active', active);
                    if (active) button.setAttribute('aria-current', 'step');
                    else button.removeAttribute('aria-current');
                });
                root.dataset.activeScene = String(sceneIndex + 1);
            }

            function applyCamera(progress) {
                const segmentPosition = progress * (cameraAnchors.length - 1);
                const segment = Math.min(cameraAnchors.length - 2, Math.floor(segmentPosition));
                const local = segmentPosition >= cameraAnchors.length - 1 ? 1 : segmentPosition - segment;
                const eased = THREE.MathUtils.smoothstep(local, 0, 1);
                const from = cameraAnchors[segment];
                const to = cameraAnchors[segment + 1];
                nextCameraPosition.lerpVectors(from.position, to.position, eased);
                nextCameraTarget.lerpVectors(from.target, to.target, eased);
                camera.position.copy(nextCameraPosition);
                camera.lookAt(nextCameraTarget);
                canvas.dataset.cameraX = camera.position.x.toFixed(2);
                canvas.dataset.cameraZ = camera.position.z.toFixed(2);
            }

            function draw(now) {
                raf = 0;
                if (!visible || document.hidden) return;
                const delta = lastTime ? Math.min((now - lastTime) / 1000, 0.05) : 1 / 60;
                lastTime = now;
                const damp = 1 - Math.exp(-delta * 7.5);
                easedProgress += (scrollProgress - easedProgress) * damp;
                if (Math.abs(scrollProgress - easedProgress) < 0.0005) easedProgress = scrollProgress;
                applyCamera(easedProgress);
                updateCopy(scrollProgress);
                const worldScenePosition = easedProgress * (sceneGroups.length - 1);
                sceneGroups.forEach((group, groupIndex) => {
                    group.visible = Math.abs(groupIndex - worldScenePosition) <= 1.25;
                    group.traverse(object => {
                        if (object.userData.sway) object.rotation.z = Math.sin(now * 0.0007 + groupIndex + object.position.x) * 0.018;
                    });
                });
                renderer.render(scene, camera);
                canvas.dataset.renderCalls = String(renderer.info.render.calls);
                canvas.dataset.renderTriangles = String(renderer.info.render.triangles);
                animationFramesLeft -= 1;
                if (Math.abs(scrollProgress - easedProgress) > 0.0005 && animationFramesLeft > 0) {
                    raf = window.requestAnimationFrame(draw);
                }
            }

            function requestFrame() {
                if (visible && !document.hidden) {
                    animationFramesLeft = 42;
                    if (!raf) raf = window.requestAnimationFrame(draw);
                }
            }

            function jumpToScene(index) {
                const range = Math.max(0, track.offsetHeight - window.innerHeight);
                const sceneProgress = index / Math.max(1, storyCopies.length - 1);
                const top = window.scrollY + track.getBoundingClientRect().top + range * sceneProgress;
                window.scrollTo({ top, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            }

            navButtons.forEach(button => button.addEventListener('click', () => jumpToScene(Number(button.dataset.worldJump || 0))));
            window.addEventListener('scroll', updateScrollProgress, { passive: true });
            window.addEventListener('resize', () => {
                if (!resizeFrame) resizeFrame = window.requestAnimationFrame(updateSize);
                updateScrollProgress();
            }, { passive: true });
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) requestFrame();
                else { lastTime = 0; if (raf) window.cancelAnimationFrame(raf); raf = 0; }
            });

            if ('ResizeObserver' in window) {
                resizeObserver = new ResizeObserver(updateSize);
                resizeObserver.observe(root);
            } else window.addEventListener('resize', updateSize, { passive: true });

            if ('IntersectionObserver' in window) {
                intersectionObserver = new IntersectionObserver(entries => {
                    visible = entries.some(entry => entry.isIntersecting);
                    if (visible) { updateScrollProgress(); requestFrame(); }
                    else { lastTime = 0; if (raf) window.cancelAnimationFrame(raf); raf = 0; }
                }, { rootMargin: '120px 0px', threshold: 0 });
                intersectionObserver.observe(root);
            } else visible = true;

            root.addEventListener('webglcontextlost', event => {
                event.preventDefault();
                if (raf) window.cancelAnimationFrame(raf);
                renderer.dispose();
                showFallback('WebGL berhenti. Cerita tetap bisa dibaca dan katalog tetap bisa dibuka.');
            });

            updateSize();
            updateScrollProgress();
            root.dataset.worldState = 'ready';
            root.classList.remove('is-fallback');
            root.classList.add('is-ready');

            // The scroll track grows from auto height to 600svh during init.
            // A link like index.php#cara-pesan is followed before that growth,
            // so re-align to the requested section once the layout is final.
            const alignInitialHash = () => {
                const hash = window.location.hash;
                if (hash.length < 2) return;
                let hashTarget = null;
                try {
                    hashTarget = document.getElementById(decodeURIComponent(hash.slice(1)));
                } catch (error) {
                    hashTarget = null;
                }
                if (!hashTarget || root.contains(hashTarget)) return;
                const html = document.documentElement;
                const previousBehavior = html.style.scrollBehavior;
                html.style.scrollBehavior = 'auto';
                hashTarget.scrollIntoView({ block: 'start', behavior: 'auto' });
                html.style.scrollBehavior = previousBehavior;
            };
            const scheduleHashAlignment = () => {
                window.setTimeout(alignInitialHash, 150);
                window.setTimeout(alignInitialHash, 750);
            };
            if (document.readyState === 'complete') {
                scheduleHashAlignment();
            } else {
                window.addEventListener('load', scheduleHashAlignment, { once: true });
            }
        } catch (error) {
            console.error('Florist 3D story unavailable:', error);
            showFallback('Tampilan 3D tidak tersedia pada browser ini. Cerita foto dan katalog tetap dapat digunakan.');
        }
    }

    startFloristWorld();
})();
