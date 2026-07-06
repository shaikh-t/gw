// public/assets/js/app.js - small helpers
document.addEventListener('DOMContentLoaded', function () {
  // simple client-side enhancement: focus first input on forms with .auto-focus
  var el = document.querySelector('.auto-focus input, .auto-focus textarea');
  if (el) el.focus();
});
// Toggle sidebar on small screens
document.addEventListener('DOMContentLoaded', function () {
  var toggleBtn = document.querySelector('[data-toggle-sidebar]');
  var sidebar = document.getElementById('appSidebar');
  if (!toggleBtn || !sidebar) return;
  toggleBtn.addEventListener('click', function (e) {
    e.preventDefault();
    sidebar.classList.toggle('open');
  });
  // close when clicking outside on small screens
  document.addEventListener('click', function (e) {
    if (window.innerWidth > 991.98) return;
    if (!sidebar.classList.contains('open')) return;
    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
      sidebar.classList.remove('open');
    }
  });
});
document.addEventListener('DOMContentLoaded', function() {
  var box = document.getElementById('flashErrors');
  if (box) {
    setTimeout(function() {
      box.classList.add('fade-out');
      setTimeout(function() {
        box.style.display = 'none';
      }, 500); // wait for fade-out transition
    }, 4000); // hide after 4 seconds
  }
});
