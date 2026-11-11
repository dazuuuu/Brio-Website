/* Admin → Settings */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-settings]')) return;
  var $ = A.$;
  var form = $('[data-settings-form]');
  var hform = $('[data-header-form]');

  function fill(s) {
    form.elements.whatsapp_number.value = s.whatsapp_number ? '+' + s.whatsapp_number : '';
    form.elements.whatsapp_greeting.value = s.whatsapp_greeting || '';
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
