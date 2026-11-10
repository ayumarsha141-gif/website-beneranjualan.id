(() => {
    'use strict';
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const csrf = $('meta[name="csrf-token"]')?.content || '';
    const toastRegion = $('#toastRegion');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function toast(message, type = 'success') {
        if (!toastRegion) return;
        const node = document.createElement('div');
        node.className = `toast toast-${type}`;
        node.textContent = message;
        toastRegion.append(node);
        requestAnimationFrame(() => node.classList.add('is-visible'));
        window.setTimeout(() => {
            node.classList.remove('is-visible');
            window.setTimeout(() => node.remove(), 220);
        }, 3200);
    }

    const drawer = $('#mobileDrawer');
    const menuButton = $('#menuToggle');
    const setDrawer = (open) => {
        if (!drawer || !menuButton) return;
        drawer.classList.toggle('is-open', open);
        drawer.setAttribute('aria-hidden', String(!open));
        menuButton.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('menu-open', open);
        if (open) $('[data-close-menu]', drawer)?.focus();
        else menuButton.focus();
    };
    menuButton?.addEventListener('click', () => setDrawer(menuButton.getAttribute('aria-expanded') !== 'true'));
    $$('[data-close-menu]').forEach((el) => el.addEventListener('click', () => setDrawer(false)));
    document.addEventListener('keydown', (event) => {
        if (!drawer?.classList.contains('is-open')) return;
        if (event.key === 'Escape') { setDrawer(false); return; }
        if (event.key === 'Tab') {
            const focusable = $$('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])', drawer)
                .filter((item) => item.offsetParent !== null);
            if (!focusable.length) return;
            const first = focusable[0], last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    const header = $('#siteHeader');
    const backTop = $('#backToTop');
    const onScroll = () => {
        header?.classList.toggle('is-scrolled', window.scrollY > 16);
        backTop?.classList.toggle('is-visible', window.scrollY > 500);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    backTop?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));

    // Reveal content only when motion is allowed; content remains visible without JS.
    const revealItems = $$('.reveal, .hero-reveal');
    if (!reduceMotion && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, io) => entries.forEach((entry) => {
            if (entry.isIntersecting) { entry.target.classList.add('is-revealed'); io.unobserve(entry.target); }
        }), { threshold: 0.12 });
        revealItems.forEach((item) => observer.observe(item));
    } else revealItems.forEach((item) => item.classList.add('is-revealed'));

    // Scroll story: the real shop photos change with each chapter while one
    // botanical route draws through the sequence. No pinning or scroll lock.
    const botanicalStory = $('[data-botanical-story]');
    if (botanicalStory) {
        const chapters = $$('[data-story-chapter]', botanicalStory);
        const stageImages = $$('[data-story-image-item]', botanicalStory);
        const progressPath = $('[data-story-progress]', botanicalStory);
        const captionKicker = $('[data-story-caption-kicker]', botanicalStory);
        const captionTitle = $('[data-story-caption-title]', botanicalStory);
        const stageIndex = $('[data-story-stage-index]', botanicalStory);
        let activeChapter = null;

        const activateChapter = (chapter) => {
            if (!chapter || chapter === activeChapter) return;
            activeChapter = chapter;
            const index = Number(chapter.dataset.storyIndex || 0);
            chapters.forEach((item) => item.classList.toggle('is-active', item === chapter));
            stageImages.forEach((image, imageIndex) => {
                const active = imageIndex === index;
                image.classList.toggle('is-active', active);
                image.setAttribute('aria-hidden', String(!active));
            });
            if (captionKicker) captionKicker.textContent = chapter.dataset.storyKicker || '';
            if (captionTitle) captionTitle.textContent = chapter.dataset.storyTitle || '';
            if (stageIndex) stageIndex.textContent = `${String(index + 1).padStart(2, '0')} / ${String(chapters.length).padStart(2, '0')}`;
        };

        const pathLength = progressPath && !reduceMotion ? progressPath.getTotalLength() : 0;
        if (progressPath && pathLength) progressPath.style.strokeDasharray = String(pathLength);
        let storyFrame = 0;
        const updateStoryScene = () => {
            storyFrame = 0;
            const sectionRect = botanicalStory.getBoundingClientRect();
            if (sectionRect.bottom >= 0 && sectionRect.top <= window.innerHeight && chapters.length) {
                const center = window.innerHeight / 2;
                let nearest = null;
                let nearestDistance = Infinity;
                chapters.forEach((chapter) => {
                    const rect = chapter.getBoundingClientRect();
                    if (rect.bottom < 0 || rect.top > window.innerHeight) return;
                    const distance = Math.abs((rect.top + rect.bottom) / 2 - center);
                    if (distance < nearestDistance) { nearest = chapter; nearestDistance = distance; }
                });
                if (nearest) activateChapter(nearest);
            }
            if (progressPath && pathLength) {
                const progress = Math.max(0, Math.min(1,
                    (window.innerHeight - sectionRect.top) / (sectionRect.height + window.innerHeight)
                ));
                progressPath.style.strokeDashoffset = String(pathLength * (1 - progress));
            }
        };
        const requestStoryScene = () => {
            if (!storyFrame) storyFrame = requestAnimationFrame(updateStoryScene);
        };
        window.addEventListener('scroll', requestStoryScene, { passive: true });
        window.addEventListener('resize', requestStoryScene, { passive: true });
        requestStoryScene();
    }

    // Category menu works with pointer and keyboard via native button semantics.
    $$('.nav-drop-toggle').forEach((button) => button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        button.closest('.nav-moments')?.classList.toggle('is-open', !expanded);
    }));

    $$('[data-qty]').forEach((button) => button.addEventListener('click', () => {
        const input = button.closest('.qty-stepper')?.querySelector('input[type="number"]');
        if (!input) return;
        const min = Number(input.min || 1), max = Number(input.max || 99), amount = Number(button.dataset.qty || 0);
        input.value = String(Math.min(max, Math.max(min, Number(input.value || min) + amount)));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }));

    $$('.gallery-thumbs [data-gallery-src]').forEach((button) => button.addEventListener('click', () => {
        const image = $('#galleryMain');
        if (!image) return;
        image.src = button.dataset.gallerySrc;
        const webp = $('#galleryWebp');
        if (webp && button.dataset.gallerySrcset) webp.srcset = button.dataset.gallerySrcset;
        else if (webp) webp.removeAttribute('srcset');
        image.alt = button.dataset.galleryAlt || '';
        $$('.gallery-thumbs button').forEach((item) => item.classList.toggle('is-active', item === button));
    }));

    // AJAX is an enhancement only: requests are POST + CSRF, and normal forms still work.
    $$('[data-add-form]').forEach((form) => form.addEventListener('submit', async (event) => {
        if (!window.fetch || !csrf) return;
        event.preventDefault();
        const button = $('[data-add-button]', form);
        const original = button?.innerHTML;
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); }
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            });
            const data = await response.json();
            if (!response.ok || !data.success) throw new Error(data.message || 'Produk gagal ditambahkan.');
            const badge = $('#cartBadge');
            if (badge) { badge.textContent = String(data.cart_count); badge.classList.toggle('is-empty', !data.cart_count); }
            toast(data.message || 'Produk ditambahkan ke keranjang.');
        } catch (error) {
            toast(error.message || 'Tidak dapat menghubungi server. Silakan coba lagi.', 'error');
        } finally {
            if (button) { button.disabled = false; button.removeAttribute('aria-busy'); if (original) button.innerHTML = original; }
        }
    }));

    $$('[data-debounce]').forEach((input) => {
        let timer;
        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => input.form?.requestSubmit(), 450);
        });
    });

    $$('form[data-checkout-form]').forEach((form) => form.addEventListener('submit', () => {
        const button = $('[data-submit-checkout]', form);
        if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); button.dataset.label = button.textContent.trim(); button.textContent = 'Memproses pesanan…'; }
    }));

    // Progressive, three-stage checkout. Without JavaScript every form section
    // remains visible and the server-side checkout remains fully usable.
    $$('form[data-checkout-form]').forEach((form) => {
        const steps = $$('[data-checkout-step]', form);
        const progressItems = $$('[data-checkout-progress] li', form);
        const submitButton = $('[data-submit-checkout]', form);
        const summaryDetails = $('[data-checkout-summary]', form);
        if (steps.length < 2) return;
        let activeStep = 0;
        const mobileSummary = window.matchMedia('(max-width: 760px)');

        const showStep = (next, moveFocus = true) => {
            activeStep = Math.max(0, Math.min(steps.length - 1, next));
            steps.forEach((step, index) => {
                step.hidden = index !== activeStep;
                step.setAttribute('aria-hidden', String(index !== activeStep));
            });
            progressItems.forEach((item, index) => {
                item.classList.toggle('is-current', index === activeStep);
                item.classList.toggle('is-complete', index < activeStep);
                if (index === activeStep) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
            if (submitButton) submitButton.disabled = activeStep !== steps.length - 1;
            if (summaryDetails && mobileSummary.matches) summaryDetails.open = activeStep === steps.length - 1;
            if (moveFocus) {
                const heading = $('h2', steps[activeStep]);
                heading?.setAttribute('tabindex', '-1');
                heading?.focus({ preventScroll: true });
                steps[activeStep].scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
            }
        };

        $$('[data-checkout-next]', form).forEach((button) => button.addEventListener('click', () => {
            const fields = $$('input, select, textarea', steps[activeStep])
                .filter((field) => field.willValidate && !field.disabled);
            const invalid = fields.find((field) => !field.checkValidity());
            if (invalid) { invalid.reportValidity(); invalid.focus(); return; }
            showStep(activeStep + 1);
        }));
        $$('[data-checkout-back]', form).forEach((button) => button.addEventListener('click', () => showStep(activeStep - 1)));
        mobileSummary.addEventListener?.('change', () => {
            if (summaryDetails) summaryDetails.open = !mobileSummary.matches || activeStep === steps.length - 1;
        });
        showStep(0, false);
    });

    $$('form[data-confirm]').forEach((form) => form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) event.preventDefault();
    }));

    // Light, decorative hero petal drift stops when hidden or reduced-motion is requested.
    if (!reduceMotion && !window.matchMedia('(max-width: 640px)').matches && $('.hero .petal')) {
        const petals = $$('.hero .petal');
        let frame = 0;
        const animate = () => {
            if (document.hidden) { frame = 0; return; }
            const t = performance.now() / 1000;
            petals.forEach((petal, index) => petal.style.setProperty('--drift', `${Math.sin(t * .35 + index) * 7}px`));
            frame = requestAnimationFrame(animate);
        };
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && frame) cancelAnimationFrame(frame);
            else if (!document.hidden && !frame) frame = requestAnimationFrame(animate);
        });
        frame = requestAnimationFrame(animate);
    }
})();
