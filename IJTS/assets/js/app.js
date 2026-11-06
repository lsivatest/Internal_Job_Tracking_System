(function () {
  var btn = document.getElementById('menuBtn');
  if (btn) {
    btn.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });
    document.addEventListener('click', function (ev) {
      if (document.body.classList.contains('nav-open') && !ev.target.closest('#sidebar') && !ev.target.closest('#menuBtn')) {
        document.body.classList.remove('nav-open');
      }
    });
  }
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (ev) {
      if (!window.confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); }
    });
  });
})();
