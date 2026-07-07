/* GlobalWays — shared interactions */
(function () {
  'use strict';

  function initNavbarScroll() {
    const navbar = document.querySelector('.gw-header'); // Updated class to match header
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


  function highlightActiveNav() {
    const currentPath = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.navbar-nav .nav-link, .dashboard-sidebar .nav-link').forEach((link) => {
      const href = link.getAttribute('href');
      if (!href) return;
      const linkPath = href.split('/').pop();
      if (linkPath === currentPath || (currentPath === '' && linkPath === 'index.php')) {
        link.classList.add('active');
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initNavbarScroll();
    highlightActiveNav();
  });
})();
