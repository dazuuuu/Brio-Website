/* Admin → Reset store */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-reset]')) return;
  var $ = A.$;
  var form = $('[data-reset-form]');

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    $('[data-form-error]').hidden = true;
    A.$all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });

    var btn = $('[data-reset-btn]');
    A.busy(btn, true, 'Resetting…');
    A.request('/reset', { method: 'POST', body: { password: form.elements.password.value, confirm: form.elements.confirm.checked } })
      .then(function (json) {
        var r = json.data;
        form.reset();
        $('[data-count="products"]').textContent = '0';
        $('[data-count="orders"]').textContent = '0';
        var done = $('[data-reset-done]');
        done.textContent = 'Store reset. Removed ' + r.products + ' product' + (r.products === 1 ? '' : 's') + ', ' + r.orders + ' order' + (r.orders === 1 ? '' : 's') + ' and ' + r.images + ' photo' + (r.images === 1 ? '' : 's') + '. Your account, pages, slides, menus and settings were not changed.';
        done.hidden = false;
        A.toast('Store reset.');
      })
      .catch(function (err) {
        var box = $('[data-form-error]');
        box.textContent = err.message;
        box.hidden = false;
        Object.keys(err.errors || {}).forEach(function (k) { var slot = $('[data-error-for="' + k + '"]', form); if (slot) slot.textContent = err.errors[k][0]; });
        form.elements.password.value = '';
      })
      .then(function () { A.busy(btn, false); });
  });
})();
