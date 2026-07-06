/* GlobalWays — shared interactions */
(function () {
  'use strict';

  const base = document.documentElement.dataset.base || '';

  function tpl(html) {
    return html.replaceAll('{{BASE}}', base);
  }

  async function loadPartials() {
    const navMount = document.getElementById('site-navbar');
    const footerMount = document.getElementById('site-footer');

    const tasks = [];
    if (navMount) {
      const navClass = navMount.dataset.navClass || 'scrolled';
      tasks.push(
        fetch(base + 'partials/navbar.html')
          .then((r) => r.text())
          .then((html) => {
            navMount.outerHTML = tpl(html).replace('{{NAV_CLASS}}', navClass);
          })
      );
    }
    if (footerMount) {
      tasks.push(
        fetch(base + 'partials/footer.html')
          .then((r) => r.text())
          .then((html) => {
            footerMount.outerHTML = tpl(html);
          })
      );
    }
    if (tasks.length) await Promise.all(tasks);
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

  // Fade-in on scroll
  const fadeEls = document.querySelectorAll('.fade-in');
  if (fadeEls.length && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            io.unobserve(e.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: '-40px' }
    );
    fadeEls.forEach((el) => io.observe(el));
  } else {
    fadeEls.forEach((el) => el.classList.add('visible'));
  }

  // Counter animation
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

  // Dashboard sidebar toggle (mobile)
  const sidebar = document.querySelector('.dashboard-sidebar');
  const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
  const sidebarClose = document.querySelector('[data-sidebar-close]');
  const sidebarBackdrop = document.querySelector('.sidebar-backdrop');

  if (sidebar && sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
      sidebar.classList.add('show');
      if (sidebarBackdrop) sidebarBackdrop.classList.add('show');
    });
  }
  const closeSidebar = () => {
    if (sidebar) sidebar.classList.remove('show');
    if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
  };
  if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
  if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', closeSidebar);

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

  document.addEventListener('DOMContentLoaded', async () => {
    await loadPartials();
    initNavbarScroll();
    highlightActiveNav();
  });
})();
