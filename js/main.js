/* GlobalWays — shared interactions */
(function () {
  'use strict';

  const base = document.documentElement.dataset.base || '';

  function tpl(html) {
    return html.replaceAll('{{BASE}}', base);
  }

  async function loadPartials() {
    // Navbar and footer are inlined in each HTML file so the site works
    // without a server (file://) and when clients open files directly.
  }

  function initNavbarScroll() {
    const navbar = document.querySelector('.gw-navbar');
    if (!navbar) return;
    const onScroll = () => {
      if (window.scrollY > 12) navbar.classList.add('scrolled');
      else navbar.classList.remove('scrolled');
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Dashboard sidebar toggle (mobile)
  const sidebar = document.querySelector('.dashboard-sidebar');
  const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  const sidebarClose = document.querySelector('[data-sidebar-close]');
  const sidebarBackdrop = document.querySelector('.sidebar-backdrop');

  if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.add('show');
      if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
      document.body.classList.add('sidebar-open');
    });
  }
  const closeSidebar = () => {
    if (sidebar) sidebar.classList.remove('show');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
    document.body.classList.remove('sidebar-open');
  };
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

  // Customer messages: mobile master/detail
  const msgLayout = document.querySelector('.cp-msg-layout');
  if (msgLayout) {
    const threads = msgLayout.querySelectorAll('.cp-thread');
    const chatBack = msgLayout.querySelector('.cp-chat-back');
    threads.forEach((thread) => {
      thread.addEventListener('click', (e) => {
        e.preventDefault();
        threads.forEach((t) => t.classList.remove('active'));
        thread.classList.add('active');
        msgLayout.classList.add('is-chat');
      });
    });
    if (chatBack) {
      chatBack.addEventListener('click', () => {
        msgLayout.classList.remove('is-chat');
      });
    }
  }

  // Counter animation
  function initCounters() {
    document.querySelectorAll('[data-counter]').forEach((el) => {
      const end = parseInt(el.dataset.counter, 10);
      const suffix = el.dataset.suffix || '';
      const duration = 1800;
      let started = false;

      const run = () => {
        if (started) return;
        started = true;
        const start = performance.now();
        const tick = (now) => {
          const p = Math.min((now - start) / duration, 1);
          const val = Math.floor(end * p);
          el.textContent = val.toLocaleString() + suffix;
          if (p < 1) requestAnimationFrame(tick);
          else el.textContent = end.toLocaleString() + suffix;
        };
        requestAnimationFrame(tick);
      };

      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
          if (entries[0].isIntersecting) {
            run();
            io.disconnect();
          }
        });
        io.observe(el);
      } else {
        run();
      }
    });
  }

  // Login role redirect
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const role = document.querySelector('input[name="role"]:checked')?.value || 'customer';
      const paths = {
        customer: 'customer/index.html',
        vendor: 'vendor/index.html',
        admin: 'admin/index.html',
      };
      window.location.href = paths[role] || paths.customer;
    });
  }

  function highlightActiveNav() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.html';
    document.querySelectorAll('.gw-navbar .nav-link, .dashboard-sidebar .nav-link').forEach((link) => {
      const href = link.getAttribute('href');
      if (!href) return;
      const linkPath = href.split('/').pop();
      if (linkPath === currentPath || (currentPath === '' && linkPath === 'index.html')) {
        link.classList.add('active');
      }
    });
  }

  function initCustomCursor() {
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!finePointer || reducedMotion) return;

    const dot = document.createElement('div');
    const ring = document.createElement('div');
    dot.className = 'gw-cursor-dot';
    ring.className = 'gw-cursor-ring';
    document.body.appendChild(dot);
    document.body.appendChild(ring);
    document.body.classList.add('has-custom-cursor');

    let mouseX = window.innerWidth / 2;
    let mouseY = window.innerHeight / 2;
    let ringX = mouseX;
    let ringY = mouseY;
    let rafId = null;

    const render = () => {
      // Soft lag on outer ring for the "follower" feel
      ringX += (mouseX - ringX) * 0.16;
      ringY += (mouseY - ringY) * 0.16;
      // Dot stays exact / snappy
      dot.style.transform = `translate(${mouseX}px, ${mouseY}px) translate(-50%, -50%)`;
      ring.style.transform = `translate(${ringX}px, ${ringY}px) translate(-50%, -50%)`;
      rafId = requestAnimationFrame(render);
    };

    const hoverSelector = 'a, button, .btn, input, textarea, select, [role="button"], .filter-pill-btn, .service-card, .blog-post-card, .vendor-list-card';
    // Sections that are typically solid blue / dark — used as a fast path
    const knownDarkSections = [
      '.page-hero',
      '.about-hero',
      '.blog-hero',
      '.contact-hero',
      '.login-hero-panel',
      '.register-hero-panel',
      '.article-hero-dark',
      '.article-bottom-cta',
      '.article-cta-card',
      '.hiw-vendor-panel',
      '.blog-newsletter-section',
      '.final-cta-black',
      '.cta-section',
      '.bg-blk',
      '.gw-footer',
    ].join(', ');

    const parseRgb = (bg) => {
      const match = bg && bg.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?/i);
      if (!match) return null;
      return {
        r: Number(match[1]),
        g: Number(match[2]),
        b: Number(match[3]),
        a: match[4] !== undefined ? Number(match[4]) : 1,
      };
    };

    const needsLightCursor = (rgb) => {
      if (!rgb || rgb.a < 0.35) return false;
      const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
      const isBlueish = rgb.b > 120 && rgb.b >= rgb.r && rgb.b >= rgb.g - 10 && luminance < 0.72;
      const isDark = luminance < 0.42;
      return isBlueish || isDark;
    };

    // Walk from the element under the pointer up to find the first opaque
    // background; switch to white cursor only when that surface is blue/dark.
    const updateCursorTone = (target) => {
      let node = target;
      let onDark = false;
      while (node && node !== document.documentElement) {
        if (node === document.body) {
          onDark = false;
          break;
        }
        try {
          const style = window.getComputedStyle(node);
          const rgb = parseRgb(style.backgroundColor);
          if (rgb && rgb.a >= 0.35) {
            onDark = needsLightCursor(rgb);
            break;
          }
          // Gradients / images often report transparent backgroundColor — use known sections
          if (
            (style.backgroundImage && style.backgroundImage !== 'none') ||
            node.matches(knownDarkSections)
          ) {
            onDark = true;
            break;
          }
        } catch (_) {
          break;
        }
        node = node.parentElement;
      }
      document.body.classList.toggle('cursor-on-blue', onDark);
    };

    const onMove = (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
      document.body.classList.add('cursor-ready');
      updateCursorTone(e.target);
    };

    document.addEventListener('mousemove', onMove, { passive: true });
    document.addEventListener('mousedown', () => document.body.classList.add('cursor-click'));
    document.addEventListener('mouseup', () => document.body.classList.remove('cursor-click'));
    document.addEventListener('mouseover', (e) => {
      if (e.target.closest(hoverSelector)) document.body.classList.add('cursor-hover');
      updateCursorTone(e.target);
    });
    document.addEventListener('mouseout', (e) => {
      if (e.target.closest(hoverSelector)) document.body.classList.remove('cursor-hover');
    });
    document.addEventListener('mouseleave', () => {
      document.body.classList.remove('cursor-ready', 'cursor-on-blue');
    });
    document.addEventListener('mouseenter', () => document.body.classList.add('cursor-ready'));

    rafId = requestAnimationFrame(render);

    window.addEventListener('beforeunload', () => {
      if (rafId) cancelAnimationFrame(rafId);
    });
  }

  function enhanceScrollReveal() {
    const candidates = document.querySelectorAll(
      'main section > .container-xl, main section > .container, .service-card, .blog-post-card, .about-stand-card, .about-journey-row, .hiw-step-row, .feature-card, .value-card'
    );
    candidates.forEach((el) => {
      if (!el.classList.contains('fade-in')) el.classList.add('fade-in');
    });

    const fadeEls = document.querySelectorAll('.fade-in');
    if (!fadeEls.length) return;
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((e) => {
            if (e.isIntersecting) {
              e.target.classList.add('visible');
              io.unobserve(e.target);
            }
          });
        },
        { threshold: 0.1, rootMargin: '0px 0px -36px 0px' }
      );
      fadeEls.forEach((el) => io.observe(el));
    } else {
      fadeEls.forEach((el) => el.classList.add('visible'));
    }
  }

  document.addEventListener('DOMContentLoaded', async () => {
    await loadPartials();
    initNavbarScroll();
    highlightActiveNav();
    // initCustomCursor();
    enhanceScrollReveal();
    initCounters();

    // Dynamically load the real-time notification listener
    const base = document.documentElement.dataset.base || '';
    let scriptPath = '/js/notifications.js';
    const currentPath = window.location.pathname;
    if (currentPath.includes('/vendor/') || currentPath.includes('/customer/')) {
        scriptPath = '../js/notifications.js';
    } else if (currentPath.includes('/admin/')) {
        scriptPath = '../../js/notifications.js';
    }
    const script = document.createElement('script');
    script.src = scriptPath;
    document.body.appendChild(script);
  });
})();
