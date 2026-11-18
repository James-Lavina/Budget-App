<script>
  if (!window.__bwAuthToggleBound) {
    window.__bwAuthToggleBound = true;
    document.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-bw-toggle]');
      if (!btn) return;
      var input = btn.parentElement.querySelector('input');
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('is-visible', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  }
</script>