/* Admin → Settings */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-settings]')) return;
  var $ = A.$;
  var form = $('[data-settings-form]');
  var hform = $('[data-header-form]');
  var aform = $('[data-accent-form]');
  var DEFAULT_ACCENT = '#f25c1f';
  var savedAccent = DEFAULT_ACCENT;

  /** Same maths as the server (adm_theme_vars), so the preview matches what gets saved. */
  function applyAccent(hex) {
    var n = parseInt(hex.slice(1), 16), r = n >> 16, g = (n >> 8) & 255, b = n & 255;
    function mix(to, t) { return '#' + [r, g, b].map(function (c) { return ('0' + Math.round(c + (to - c) * t).toString(16)).slice(-2); }).join(''); }
    var root = document.documentElement.style;
    root.setProperty('--adm-brand', hex);
    root.setProperty('--adm-brand-dark', mix(0, 0.14));
    root.setProperty('--adm-brand-soft', mix(255, 0.9));
    root.setProperty('--adm-brand-row', mix(255, 0.86));
    root.setProperty('--adm-brand-rgb', r + ', ' + g + ', ' + b);
    root.setProperty('--adm-on-brand', (0.299 * r + 0.587 * g + 0.114 * b) > 150 ? '#1f1f1f' : '#ffffff');
    aform.elements.admin_accent.value = hex;
    $('[data-accent-hex]').textContent = hex;
    A.$all('[data-accent-swatch]', aform).forEach(function (s) { s.classList.toggle('is-active', s.dataset.accentSwatch === hex); });
  }
  aform.addEventListener('click', function (e) { var s = e.target.closest('[data-accent-swatch]'); if (s) applyAccent(s.dataset.accentSwatch); });
  aform.elements.admin_accent.addEventListener('input', function (e) { applyAccent(e.target.value.toLowerCase()); });
  $('[data-reset-accent]').addEventListener('click', function () { applyAccent(DEFAULT_ACCENT); });
  aform.addEventListener('submit', function (e) {
    e.preventDefault();
    $('[data-accent-error]').hidden = true;
    var btn = $('[data-save-accent]');
    A.busy(btn, true);
    A.request('/settings', { method: 'POST', body: { admin_accent: aform.elements.admin_accent.value } })
      .then(function (json) { savedAccent = json.data.admin_accent; applyAccent(savedAccent); A.toast('Admin colour saved.'); })
      .catch(function (err) { var box = $('[data-accent-error]'); box.textContent = err.message; box.hidden = false; applyAccent(savedAccent); })
      .then(function () { A.busy(btn, false); });
  });

  function fill(s) {
    form.elements.whatsapp_number.value = s.whatsapp_number ? '+' + s.whatsapp_number : '';
    form.elements.whatsapp_greeting.value = s.whatsapp_greeting || '';
    savedAccent = s.admin_accent || DEFAULT_ACCENT;
    applyAccent(savedAccent);
    hform.elements.show_search.checked = s.show_search !== '0';
    hform.elements.show_account_icon.checked = s.show_account_icon !== '0';
    var link = $('[data-test-link]');
    link.hidden = !s.whatsapp_number;
    if (s.whatsapp_number) link.href = 'https://wa.me/' + s.whatsapp_number + '?text=' + encodeURIComponent((s.whatsapp_greeting || '') + '\n\n(Test message from Brio admin)');
  }

  A.request('/settings').then(function (json) { fill(json.data); }).catch(function (e) { A.toast(e.message, true); });

  hform.addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = $('[data-save-header]');
    A.busy(btn, true);
    A.request('/settings', { method: 'POST', body: { show_search: hform.elements.show_search.checked, show_account_icon: hform.elements.show_account_icon.checked } })
      .then(function (json) { fill(json.data); A.toast('Header saved.'); })
      .catch(function (err) { A.toast(err.message, true); }).then(function () { A.busy(btn, false); });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    $('[data-form-error]').hidden = true;
    A.$all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });
    var btn = $('[data-save]');
    A.busy(btn, true);
    A.request('/settings', { method: 'POST', body: { whatsapp_number: form.elements.whatsapp_number.value, whatsapp_greeting: form.elements.whatsapp_greeting.value } })
      .then(function (json) { fill(json.data); A.toast('Settings saved.'); })
      .catch(function (err) {
        var box = $('[data-form-error]');
        box.textContent = err.message;
        box.hidden = false;
        Object.keys(err.errors || {}).forEach(function (k) { var slot = $('[data-error-for="' + k + '"]', form); if (slot) slot.textContent = err.errors[k][0]; });
      }).then(function () { A.busy(btn, false); });
  });
})();
