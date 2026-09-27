/* GARAGE — public site behaviour. Progressive enhancement: every feature works without JS
 * (plain links, a GET form, a scroll-snap viewer); this file adds motion and live updates.
 * GSAP + ScrollTrigger + Flip are self-hosted. Nothing animates under prefers-reduced-motion.
 */
(() => {
  'use strict';

  const gsap = window.gsap;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const mobile = window.matchMedia('(max-width: 760px)').matches;
  const motion = Boolean(gsap) && !reduced;
  if (gsap && window.ScrollTrigger) gsap.registerPlugin(window.ScrollTrigger);
  if (gsap && window.Flip) gsap.registerPlugin(window.Flip);
  if (!motion) document.documentElement.classList.add('no-motion');

  /* ------------------------------------------------------------------ cursor label */
  const cursor = document.querySelector('[data-cursor]');
  if (cursor && finePointer) {
    let x = 0; let y = 0; let raf = 0;
    const move = () => { cursor.style.left = `${x}px`; cursor.style.top = `${y}px`; raf = 0; };
    document.addEventListener('pointermove', (e) => {
      x = e.clientX; y = e.clientY;
      if (!raf) raf = requestAnimationFrame(move);
      cursor.classList.toggle('is-on', Boolean(e.target.closest?.('.car-card__media')));
    }, { passive: true });
    document.addEventListener('pointerleave', () => cursor.classList.remove('is-on'));
  }

  /* ------------------------------------------------------------------ reveal on scroll */
  const revealed = new WeakSet();
  const reveal = (elements) => {
    const items = [...elements].filter((el) => !revealed.has(el) && !el.classList.contains('reveal-done'));
    items.forEach((el) => revealed.add(el));
    if (!items.length) return;
    if (!motion || !window.ScrollTrigger) { items.forEach((el) => el.classList.add('reveal-done')); return; }
    gsap.set(items, { autoAlpha: 0, y: 28 });
    window.ScrollTrigger.batch(items, {
      start: 'top 92%',
      once: true,
      onEnter: (batch) => gsap.to(batch, {
        autoAlpha: 1, y: 0, duration: 0.6, ease: 'power3.out', stagger: 0.06, overwrite: true,
        onComplete: () => batch.forEach((el) => el.classList.add('reveal-done')),
      }),
    });
  };
  const initReveal = () => {
    reveal(document.querySelectorAll('.car-card, [data-reveal]'));
    initCounters();
  };

  /* ------------------------------------------------------------------ counters + bars (stats) */
  function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    const bars = document.querySelectorAll('[data-bar]');
    if (!motion || !window.ScrollTrigger) return;
    const locale = document.documentElement.lang === 'es' ? 'es-MX' : 'en-US';
    counters.forEach((el) => {
      const target = Number(el.dataset.count);
      const plain = el.hasAttribute('data-count-plain');
      const from = plain ? Math.max(0, target - 60) : 0;
      const state = { v: from };
      el.textContent = plain ? String(from) : from.toLocaleString(locale);
      gsap.to(state, {
        v: target, duration: 1.6, ease: 'power2.out',
        scrollTrigger: { trigger: el, start: 'top 90%', once: true },
        onUpdate: () => { el.textContent = plain ? String(Math.round(state.v)) : Math.round(state.v).toLocaleString(locale); },
      });
    });
    bars.forEach((rect) => {
      const width = rect.getAttribute('width');
      gsap.fromTo(rect, { attr: { width: 0 } }, {
        attr: { width }, duration: 1.1, ease: 'power3.out',
        scrollTrigger: { trigger: rect.closest('.panel-card') || rect, start: 'top 85%', once: true },
      });
    });
  }

  /* ------------------------------------------------------------------ live filters + infinite scroll */
  const form = document.querySelector('[data-filters]');
  const grid = document.querySelector('[data-grid]');
  const loadMore = document.querySelector('[data-load-more]');
  const nextLink = loadMore?.querySelector('[data-next]');
  const count = document.querySelector('[data-results-count]');

  const filterUrl = () => {
    const data = new FormData(form);
    const params = new URLSearchParams();
    const brands = data.getAll('brand[]');
    for (const [key, value] of data.entries()) {
      if (key === 'brand[]' || value === '' || (key === 'sort' && value === 'recent')) continue;
      params.set(key, value);
    }
    if (brands.length) params.set('brand', brands.join(','));
    const qs = params.toString();
    return form.getAttribute('action') + (qs ? `?${qs.replace(/%2C/g, ',')}` : '');
  };

  let pending = null;
  const fetchCards = async (url, { append = false } = {}) => {
    pending?.abort();
    pending = new AbortController();
    const sep = url.includes('?') ? '&' : '?';
    const response = await fetch(`${url}${sep}partial=1`, { headers: { Accept: 'application/json' }, signal: pending.signal });
    if (!response.ok) throw new Error(String(response.status));
    return response.json();
  };

  const setNext = (url) => {
    if (!loadMore) return;
    loadMore.hidden = !url;
    if (url) nextLink.href = url;
  };

  const renderGrid = (html) => {
    let target = document.querySelector('[data-grid]');
    const empty = document.querySelector('[data-grid-empty]');
    if (!html.trim()) {
      window.location.reload();   // server renders the proper empty state
      return;
    }
    if (!target) {
      target = document.createElement('div');
      target.className = 'car-grid';
      target.dataset.grid = '';
      (empty || loadMore).before(target);
      empty?.remove();
    }
    target.innerHTML = html;
    reveal(target.querySelectorAll('.car-card'));
    if (window.ScrollTrigger) window.ScrollTrigger.refresh();
  };

  if (form) {
    let timer = 0;
    const apply = async () => {
      const url = filterUrl();
      try {
        const data = await fetchCards(url);
        history.pushState({ filters: true }, '', url);
        renderGrid(data.html);
        setNext(data.next);
        if (count) count.textContent = count.textContent.replace(/[\d.,]+/, Number(data.total).toLocaleString(document.documentElement.lang === 'es' ? 'es-MX' : 'en-US'));
      } catch (e) {
        if (e.name !== 'AbortError') window.location.assign(url);
      }
    };
    form.addEventListener('change', (e) => {
      if (e.target.matches('input[type="search"], input[type="text"]')) return;
      apply();
    });
    form.addEventListener('input', (e) => {
      if (!e.target.matches('input[type="search"], input[type="text"]')) return;
      clearTimeout(timer);
      timer = setTimeout(apply, 350);
    });
    form.addEventListener('submit', (e) => { e.preventDefault(); apply(); });
    window.addEventListener('popstate', () => window.location.reload());
  }

  if (loadMore && nextLink) {
    let loading = false;
    const more = async () => {
      if (loading || loadMore.hidden) return;
      loading = true;
      try {
        const data = await fetchCards(nextLink.href.replace(/[?&]partial=1/, ''), { append: true });
        const holder = document.createElement('div');
        holder.innerHTML = data.html;
        const cards = [...holder.children];
        document.querySelector('[data-grid]')?.append(...cards);
        reveal(cards);
        setNext(data.next);
        if (window.ScrollTrigger) window.ScrollTrigger.refresh();
      } catch {
        window.location.assign(nextLink.href);
      } finally {
        loading = false;
      }
    };
    nextLink.addEventListener('click', (e) => { e.preventDefault(); more(); });
    if ('IntersectionObserver' in window) {
      new IntersectionObserver((entries) => entries.some((en) => en.isIntersecting) && more(), { rootMargin: '600px 0px' }).observe(loadMore);
    }
  }

  /* ------------------------------------------------------------------ car viewer */
  const viewer = document.querySelector('[data-viewer]');
  if (viewer) {
    const stage = viewer.querySelector('[data-viewer-stage]');
    const slides = [...viewer.querySelectorAll('.viewer__slide')];
    const buttons = [...viewer.querySelectorAll('[data-go]')];
    if (slides.length > 1) {
      viewer.classList.add('is-enhanced');
      let index = 0;
      let busy = false;
      const go = (next, dir) => {
        next = (next + slides.length) % slides.length;
        if (next === index || busy) return;
        const from = slides[index];
        const to = slides[next];
        dir ??= next > index ? 1 : -1;
        buttons.forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.go === to.dataset.angle)));
        if (!motion) {
          from.classList.remove('is-active');
          to.classList.add('is-active');
          index = next;
          return;
        }
        busy = true;
        // "Turn the car": the old angle swings away on the Y axis while the new one swings in.
        gsap.timeline({ onComplete: () => { busy = false; } })
          .to(from, { rotationY: -75 * dir, xPercent: -18 * dir, autoAlpha: 0, duration: 0.38, ease: 'power2.in', transformPerspective: 1400 })
          .add(() => { from.classList.remove('is-active'); to.classList.add('is-active'); })
          .fromTo(to, { rotationY: 75 * dir, xPercent: 18 * dir, autoAlpha: 0, transformPerspective: 1400 },
            { rotationY: 0, xPercent: 0, autoAlpha: 1, duration: 0.5, ease: 'power3.out' });
        index = next;
      };
      buttons.forEach((b) => b.addEventListener('click', () => go(slides.findIndex((s) => s.dataset.angle === b.dataset.go))));
      const onKey = (e) => {
        if (e.target.closest?.('input, textarea, select')) return;
        if (e.key === 'ArrowRight') { go(index + 1, 1); e.preventDefault(); }
        if (e.key === 'ArrowLeft') { go(index - 1, -1); e.preventDefault(); }
      };
      document.addEventListener('keydown', onKey);
      let startX = null;
      stage.addEventListener('pointerdown', (e) => { startX = e.clientX; });
      stage.addEventListener('pointerup', (e) => {
        if (startX === null) return;
        const dx = e.clientX - startX;
        startX = null;
        if (Math.abs(dx) > 40) go(index + (dx < 0 ? 1 : -1), dx < 0 ? 1 : -1);
      });
    }
  }

  /* ------------------------------------------------------------------ 3D intro */
  const intro = document.querySelector('[data-intro]');
  const introWanted = intro && !document.documentElement.classList.contains('intro-seen') && motion;

  if (!introWanted) {
    intro?.remove();
    initReveal();
    return;
  }

  const markSeen = () => { try { sessionStorage.setItem('garage_intro_seen', '1'); } catch { /* private mode */ } };
  const siblings = [...document.body.children].filter((el) => el !== intro && el.tagName !== 'SCRIPT');
  document.documentElement.classList.add('intro-active');
  siblings.forEach((el) => { el.inert = true; });

  const scene = intro.querySelector('[data-intro-scene]');
  const enterBtn = intro.querySelector('[data-intro-enter]');
  const skipBtn = intro.querySelector('[data-intro-skip]');
  const allCards = [...intro.querySelectorAll('.intro-card')];
  const cards = allCards.slice(0, mobile ? 16 : 48);
  allCards.slice(cards.length).forEach((c) => c.remove());

  // Progressive image loading: the first dozen right away, the rest when the browser is idle.
  const load = (card) => { const img = card.querySelector('img'); if (img?.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; } };
  cards.slice(0, 12).forEach(load);
  const idle = window.requestIdleCallback || ((fn) => setTimeout(fn, 120));
  let cursorLoad = 12;
  const loadBatch = () => {
    cards.slice(cursorLoad, cursorLoad + 8).forEach(load);
    cursorLoad += 8;
    if (cursorLoad < cards.length) idle(loadBatch);
  };
  idle(loadBatch);

  // Cylinder layout: cards are billboards (always facing the camera) placed on 1–3 rings.
  const rows = mobile ? 1 : cards.length > 30 ? 3 : cards.length > 14 ? 2 : 1;
  const perRow = Math.ceil(cards.length / rows);
  const cardH = cards[0]?.offsetHeight || 150;
  let radius = 0;
  const layout = () => { radius = mobile ? window.innerWidth * 0.62 : Math.min(window.innerWidth * 0.44, 680); };
  layout();
  window.addEventListener('resize', layout);
  const nodes = cards.map((el, i) => {
    const row = Math.floor(i / perRow);
    return {
      el,
      base: ((i % perRow) / perRow) * Math.PI * 2 + row * (Math.PI / perRow),
      y: (row - (rows - 1) / 2) * cardH * 1.3,
      appear: 0,
    };
  });

  let rotation = 0;
  let velocity = 0;
  let autoSpeed = mobile ? 0.12 : 0.16;   // radians per second
  let tilt = 0;
  let tiltTarget = 0;
  let dragging = false;
  let lastX = 0;
  let running = true;

  const render = (time, deltaTime) => {
    if (!running) return;
    const dt = Math.min(deltaTime, 50) / 1000;
    if (!dragging) {
      rotation += (autoSpeed + velocity) * dt;
      velocity *= 0.94;
    }
    tilt += (tiltTarget - tilt) * 0.06;
    for (const n of nodes) {
      const a = n.base + rotation;
      const x = Math.sin(a) * radius;
      const zRaw = Math.cos(a) * radius;
      const y = n.y + zRaw * tilt * 0.35;
      const depth = (Math.cos(a) + 1) / 2;   // 1 = front, 0 = back
      n.el.style.transform = `translate(-50%, -50%) translate3d(${x.toFixed(1)}px, ${y.toFixed(1)}px, ${(zRaw * 0.7 - radius * 0.5).toFixed(1)}px)`;
      n.el.style.opacity = ((0.18 + 0.82 * depth) * n.appear).toFixed(3);
      n.el.style.zIndex = String(Math.round(depth * 100));
      n.el.style.filter = depth < 0.35 ? `blur(${((0.35 - depth) * 6).toFixed(1)}px)` : '';
    }
  };
  gsap.ticker.add(render);
  gsap.to(nodes, { appear: 1, duration: 1, ease: 'power2.out', stagger: { each: 0.025, from: 'random' } });
  // Scale only, never hidden: the giant title is often the LCP element. Fonts use
  // font-display: optional, so it never re-flows after the first paint.
  gsap.from(intro.querySelector('.intro__title'), { scale: 0.94, duration: 1.2, ease: 'power3.out' });
  gsap.from(intro.querySelector('.intro__ui'), { autoAlpha: 0, y: 20, duration: 0.8, delay: 0.5 });
  enterBtn.focus({ preventScroll: true });

  // Mouse steers speed and tilt; drag / finger spins the cylinder with inertia.
  if (finePointer && !mobile) {
    intro.addEventListener('pointermove', (e) => {
      const nx = e.clientX / window.innerWidth - 0.5;
      const ny = e.clientY / window.innerHeight - 0.5;
      if (!dragging) autoSpeed = 0.16 + nx * 0.5;
      tiltTarget = -ny * 0.8;
    });
  }
  scene.addEventListener('pointerdown', (e) => { dragging = true; lastX = e.clientX; velocity = 0; scene.setPointerCapture(e.pointerId); });
  scene.addEventListener('pointermove', (e) => {
    if (!dragging) return;
    const dx = e.clientX - lastX;
    lastX = e.clientX;
    rotation += dx * 0.005;
    velocity = dx * 0.3;
  });
  const endDrag = () => { dragging = false; };
  scene.addEventListener('pointerup', endDrag);
  scene.addEventListener('pointercancel', endDrag);

  let finished = false;
  const cleanup = () => {
    running = false;
    gsap.ticker.remove(render);
    window.removeEventListener('resize', layout);
    document.querySelectorAll('.intro-fly').forEach((el) => el.remove());
    intro.remove();
    document.documentElement.classList.remove('intro-active');
    siblings.forEach((el) => { el.inert = false; });
    markSeen();
    initReveal();
    document.getElementById('main')?.focus({ preventScroll: true });
  };

  const skip = () => {
    if (finished) return;
    finished = true;
    gsap.to(intro, { autoAlpha: 0, duration: 0.35, onComplete: cleanup });
  };

  // "Enter the garage": cards fly out of the cylinder and land on their grid cards (FLIP).
  const enter = () => {
    if (finished) return;
    finished = true;
    running = false;
    const gridEl = document.querySelector('[data-grid]');
    if (!gridEl || !window.Flip) { skip(); finished = true; return; }

    document.documentElement.classList.remove('intro-active');
    window.scrollTo({ top: gridEl.getBoundingClientRect().top + window.scrollY - 24, behavior: 'instant' });
    document.documentElement.classList.add('intro-active');

    const targets = new Map();
    gridEl.querySelectorAll('.car-card').forEach((card) => {
      const r = card.getBoundingClientRect();
      if (r.top < window.innerHeight && r.bottom > 0) targets.set(card.dataset.car, card);
    });

    const tl = gsap.timeline({ onComplete: cleanup });
    tl.to(intro.querySelectorAll('.intro__ui, .intro__title, .intro__skip'), { autoAlpha: 0, duration: 0.3 }, 0);
    tl.to(intro.querySelector('.intro__blobs'), { autoAlpha: 0, duration: 0.8 }, 0);
    tl.to(intro, { backgroundColor: 'rgba(11, 11, 18, 0)', duration: 0.9, ease: 'power2.inOut' }, 0.1);

    let landed = 0;
    nodes.forEach((n) => {
      const target = targets.get(n.el.dataset.slug);
      if (!target || landed >= 12) {
        tl.to(n.el, { autoAlpha: 0, scale: 0.4, duration: 0.45, ease: 'power2.in' }, 0);
        return;
      }
      // Detach from the 3D scene at its current on-screen box, then FLIP it onto the grid photo.
      const r = n.el.getBoundingClientRect();
      const media = target.querySelector('.car-card__media');
      n.el.querySelector('figcaption').remove();
      document.body.append(n.el);
      n.el.className = 'intro-fly';
      Object.assign(n.el.style, { left: `${r.left}px`, top: `${r.top}px`, width: `${r.width}px`, height: `${r.width * 0.75}px`, transform: 'none', opacity: '1', filter: '', zIndex: '101' });
      n.el.querySelector('img').style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
      target.classList.add('reveal-done');
      tl.add(window.Flip.fit(n.el, media, { scale: false, duration: 0.9, ease: 'power3.inOut', getVars: false }), 0.08 + landed * 0.035);
      tl.to(n.el, { borderRadius: 0, borderColor: '#141414', duration: 0.3 }, 0.7 + landed * 0.035);
      landed++;
    });
  };

  enterBtn.addEventListener('click', enter);
  skipBtn.addEventListener('click', skip);
  intro.addEventListener('wheel', (e) => { if (e.deltaY > 8) enter(); }, { passive: true });
  let touchY = null;
  intro.addEventListener('touchstart', (e) => { touchY = e.touches[0].clientY; }, { passive: true });
  intro.addEventListener('touchend', (e) => {
    if (touchY !== null && touchY - e.changedTouches[0].clientY > 60) enter();
    touchY = null;
  });
  document.addEventListener('keydown', (e) => {
    if (finished) return;
    if (e.key === 'Escape') skip();
    if (['ArrowDown', 'PageDown', ' '].includes(e.key) && e.target === document.body) { e.preventDefault(); enter(); }
  });
})();
