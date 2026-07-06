// public/assets/js/users.js
document.addEventListener('DOMContentLoaded', function () {
  var forms = document.querySelectorAll('form[data-confirm]');
  forms.forEach(function (f) {
    f.addEventListener('submit', function (e) {
      var msg = f.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });
});
