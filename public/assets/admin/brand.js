/* Admin → Settings: store identity (name, logos, icon, share picture) and Google sign-in */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-brand-form]')) return;
  var $ = A.$;
  var form = $('[data-brand-form]'), gform = $('[data-google-form]');
  var settings = {};

  /** Where each upload slot's current value lives in the settings, and what it falls back to. */
  var SLOTS = {
    'logo': { key: 'logo', fallback: 'assets/img/logo.png', none: 'Using the bundled logo.' },
    'logo-dark': { key: 'logo_dark', fallback: 'assets/img/logo-dark.png', none: 'Not set. Your logo is shown on a light chip in dark mode.', onlyDefaultWhenNoCustom: true },
    'icon': { key: 'site_icon', none: 'Using the bundled icon.' },
    'share-image': { key: 'share_image_path', none: 'No picture uploaded yet.' }
  };

  function render() {
    $('[name=store_name]', form).value = settings.store_name || '';
    $('[name=store_tagline]', form).value = settings.store_tagline || '';
    A.$all('[name=share_image]', form).forEach(function (r) { r.checked = r.value === settings.share_image; });
    $('[data-share-custom]', form).disabled = !settings.share_image_path;
    $('[name=google_client_id]', gform).value = settings.google_client_id || '';

    A.$all('[data-slot]', form).forEach(function (el) {
      var slot = el.dataset.slot, def = SLOTS[slot], value = settings[def.key] || '';
      var img = $('[data-slot-img]', el), state = $('[data-slot-state]', el), clear = $('[data-slot-clear]', el);
      var src = '';
      if (slot === 'icon') src = value ? A.url('uploads/brand/icons/favicon-32.png') + '?v=' + encodeURIComponent(value) : A.url('favicon-32.png');
      else if (value) src = A.url(value);
      else if (def.fallback && !(def.onlyDefaultWhenNoCustom && settings.logo)) src = A.url(def.fallback);
      img.hidden = !src;
      if (src) img.src = src;
      state.textContent = value ? 'Custom image in use.' : def.none;
      clear.hidden = !value;
    });
  }

  function load() {
    return A.request('/settings').then(function (json) { settings = json.data; render(); }).catch(function (e) { A.toast(e.message, true); });
  }

  function showErrors(box, root, err) {
    box.textContent = err.message;
    box.hidden = false;
    Object.keys(err.errors || {}).forEach(function (k) { var slot = $('[data-error-for="' + k + '"]', root); if (slot) slot.textContent = err.errors[k][0]; });
  }

  /* upload / remove an image */
  form.addEventListener('change', function (e) {
    var input = e.target.closest('[data-slot-input]');
    if (!input || !input.files[0]) return;
    var slot = input.closest('[data-slot]').dataset.slot, fd = new FormData();
    fd.append('image', input.files[0]);
    var state = $('[data-slot-state]', input.closest('[data-slot]'));
    state.textContent = 'Uploading…';
    A.request('/brand/' + slot, { method: 'POST', body: fd })
      .then(function (json) { settings = json.data; render(); A.toast(slot === 'icon' ? 'Site icon updated.' : 'Image updated.'); })
      .catch(function (err) { A.toast(err.message, true); render(); });
    input.value = '';
  });
  form.addEventListener('click', function (e) {
    var clear = e.target.closest('[data-slot-clear]');
    if (!clear) return;
    var slot = clear.closest('[data-slot]').dataset.slot;
    A.request('/brand/' + slot, { method: 'DELETE' })
      .then(function (json) { settings = json.data; render(); A.toast('Back to the default.'); })
      .catch(function (err) { A.toast(err.message, true); });
  });

  /* name, tagline, share picture */
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    $('[data-brand-error]').hidden = true;
    A.$all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });
    var btn = $('[data-save-brand]'), mode = $('[name=share_image]:checked', form);
    A.busy(btn, true);
    A.request('/settings', { method: 'POST', body: { store_name: form.elements.store_name.value, store_tagline: form.elements.store_tagline.value, share_image: mode ? mode.value : 'popular' } })
      .then(function (json) { settings = json.data; render(); A.toast('Store identity saved.'); })
      .catch(function (err) { showErrors($('[data-brand-error]'), form, err); })
      .then(function () { A.busy(btn, false); });
  });

  /* Google */
  $('[data-origin]', gform).textContent = location.origin;
  gform.addEventListener('submit', function (e) {
    e.preventDefault();
    $('[data-google-error]').hidden = true;
    A.$all('[data-error-for]', gform).forEach(function (el) { el.textContent = ''; });
    var btn = $('[data-save-google]');
    A.busy(btn, true);
    A.request('/settings', { method: 'POST', body: { google_client_id: gform.elements.google_client_id.value } })
      .then(function (json) { settings = json.data; render(); A.toast(settings.google_client_id ? 'Google sign-in is on.' : 'Google sign-in is off.'); })
      .catch(function (err) { showErrors($('[data-google-error]'), gform, err); })
      .then(function () { A.busy(btn, false); });
  });

  /* Email */
  var mform = $('[data-mail-form]');
  function renderMail() {
    ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'mail_from'].forEach(function (k) { mform.elements[k].value = settings[k] || ''; });
    mform.elements.smtp_pass.value = '';
    $('[data-pass-note]', mform).textContent = settings.smtp_pass_set ? 'A password is saved. Leave blank to keep it.' : 'No password saved yet.';
  }
  var baseRender = render;
  render = function () { baseRender(); renderMail(); };
  mform.addEventListener('submit', function (e) {
    e.preventDefault();
    $('[data-mail-error]').hidden = true;
    A.$all('[data-error-for]', mform).forEach(function (el) { el.textContent = ''; });
    var btn = $('[data-save-mail]'), body = {};
    ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_pass', 'mail_from'].forEach(function (k) { body[k] = mform.elements[k].value; });
    A.busy(btn, true);
    A.request('/settings', { method: 'POST', body: body })
      .then(function (json) { settings = json.data; render(); A.toast('Email settings saved.'); })
      .catch(function (err) { showErrors($('[data-mail-error]'), mform, err); })
      .then(function () { A.busy(btn, false); });
  });
  $('[data-test-mail]', mform).addEventListener('click', function () {
    var out = $('[data-test-result]', mform), btn = this;
    out.className = 'small mt-2'; out.textContent = 'Sending…';
    A.busy(btn, true, 'Sending…');
    A.request('/settings/test-email', { method: 'POST', body: { to: $('[data-test-to]', mform).value } })
      .then(function () { out.className = 'small mt-2 text-success'; out.textContent = 'Sent. Check the inbox (and spam folder).'; })
      .catch(function (err) { out.className = 'small mt-2 text-danger'; out.textContent = (err.errors && err.errors.to ? err.errors.to[0] : err.message); })
      .then(function () { A.busy(btn, false); });
  });

  /* Move content between sites */
  var tbox = $('[data-transfer]');
  $('[data-export]', tbox).addEventListener('click', function () {
    var btn = this;
    A.busy(btn, true, 'Preparing…');
    fetch(A.apiBase + '/transfer/export', { headers: { 'X-CSRF-Token': A.csrf }, credentials: 'same-origin' })
      .then(function (res) { if (!res.ok) throw new Error('Could not create the export.'); return res.blob(); })
      .then(function (blob) {
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'store-content-' + new Date().toISOString().slice(0, 10) + '.zip';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
        A.toast('Export downloaded.');
      })
      .catch(function (err) { A.toast(err.message, true); })
      .then(function () { A.busy(btn, false); });
  });
  $('[data-import]', tbox).addEventListener('click', function () {
    var btn = this, file = $('[data-import-file]', tbox).files[0], err = $('[data-transfer-error]', tbox), out = $('[data-transfer-result]', tbox);
    err.hidden = true; out.textContent = '';
    if (!file) { err.textContent = 'Choose the .zip file you exported.'; err.hidden = false; return; }
    A.confirm('This replaces the products, hero slides, pages, menus, ads and settings on THIS site with the ones in the file. It cannot be undone.', { title: 'Import content?', ok: 'Replace and import' }).then(function (yes) {
      if (!yes) return;
      var fd = new FormData();
      fd.append('package', file); fd.append('confirm', 'yes');
      A.busy(btn, true, 'Importing…');
      A.request('/transfer/import', { method: 'POST', body: fd })
        .then(function (json) {
          var c = json.data;
          out.className = 'small mt-2 text-success';
          out.textContent = 'Done: ' + (c.products || 0) + ' products, ' + (c.hero_slides || 0) + ' hero slides, ' + (c.pages || 0) + ' pages, ' + (c.nav_items || 0) + ' menu links, ' + (c.ads || 0) + ' ads, ' + (c.files || 0) + ' pictures/videos.';
          load();
        })
        .catch(function (e) { err.textContent = (e.errors && e.errors.package ? e.errors.package[0] : e.message); err.hidden = false; })
        .then(function () { A.busy(btn, false); });
    });
  });

  load();
})();
