/* Admin → Hero Slides */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-hero-list]')) return;

  var $ = A.$, esc = A.esc;
  var list = $('[data-hero-list]');
  var form = $('[data-slide-form]');
  var modal = new bootstrap.Modal($('#slide-modal'));
  var slides = [];
  var editing = null;
  var LINK_TYPES = ['none', 'category', 'product', 'page', 'url'];
  var DEFAULT_COLOR = '#1b2e24';

  /** Readable text colour for a button background (same rule as the server). */
  function textOn(hex) {
    var n = parseInt(String(hex).slice(1), 16);
    return (0.299 * (n >> 16) + 0.587 * ((n >> 8) & 255) + 0.114 * (n & 255)) > 150 ? '#1b2e24' : '#ffffff';
  }

  function ico(path) { return '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + path + '</svg>'; }
  var I = { up: ico('<path d="m6 15 6-6 6 6"/>'), down: ico('<path d="m6 9 6 6 6-6"/>'), edit: ico('<path d="M4 20h4L19 9l-4-4L4 16v4Z"/>'), del: ico('<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>') };

  function render() {
    if (!slides.length) {
      list.innerHTML = '<div class="adm-card adm-empty"><p class="mb-2"><b>No slides yet.</b></p><p class="mb-3">Your store is showing the built-in hero. Add a slide to replace it with your own images.</p><button class="btn adm-btn adm-btn--primary" data-new-slide>Add your first slide</button></div>';
      return;
    }
    list.innerHTML = slides.map(function (s, i) {
      var cta = s.ctaLabel ? '“' + esc(s.ctaLabel) + '” → ' + esc(A.describeLink(s.linkType, s.linkTarget)) : 'No button';
      return '<article class="adm-card adm-slide' + (s.isActive ? '' : ' is-off') + '" data-id="' + s.id + '">' +
        '<div class="adm-slide__thumb"><img src="' + esc(A.url(s.image)) + '" alt="' + esc(s.alt) + '">' +
          (s.ctaLabel ? '<span class="adm-slide__cta" style="background:' + esc(s.ctaColor) + ';color:' + textOn(s.ctaColor) + '">' + esc(s.ctaLabel) + '</span>' : '') + '</div>' +
        '<div class="adm-slide__body"><div class="d-flex align-items-center gap-2 mb-1"><strong>Slide ' + (i + 1) + '</strong>' +
          '<span class="adm-status adm-status--' + (s.isActive ? 'in' : 'out') + '">' + (s.isActive ? 'Live' : 'Hidden') + '</span>' +
          (s.mobileImage ? '<span class="adm-status adm-status--low">Phone image</span>' : '') + '</div>' +
          '<p class="mb-0 small text-secondary">' + cta + '</p></div>' +
        '<div class="adm-slide__actions">' +
          '<div class="form-check form-switch adm-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-toggle="' + s.id + '"' + (s.isActive ? ' checked' : '') + ' aria-label="Show slide ' + (i + 1) + '"></div>' +
          '<button class="adm-icon-btn" data-move="-1" data-id="' + s.id + '" aria-label="Move up"' + (i === 0 ? ' disabled' : '') + '>' + I.up + '</button>' +
          '<button class="adm-icon-btn" data-move="1" data-id="' + s.id + '" aria-label="Move down"' + (i === slides.length - 1 ? ' disabled' : '') + '>' + I.down + '</button>' +
          '<button class="adm-icon-btn" data-edit="' + s.id + '" aria-label="Edit slide ' + (i + 1) + '">' + I.edit + '</button>' +
          '<button class="adm-icon-btn text-danger" data-delete="' + s.id + '" aria-label="Delete slide ' + (i + 1) + '">' + I.del + '</button>' +
        '</div></article>';
    }).join('');
  }

  function load() {
    return A.request('/hero').then(function (json) { slides = json.data; render(); })
      .catch(function (e) { list.innerHTML = '<div class="adm-card adm-empty">' + esc(e.message) + '</div>'; });
  }

  /* ---------- Form ---------- */
  function previewCta() {
    var type = A.readLink($('[data-link-field]', form)).type;
    var show = type !== 'none';
    A.$all('[data-cta-fields]', form).forEach(function (el) { el.hidden = !show; });
    var cta = $('[data-preview-cta]');
    cta.hidden = !show || $('[data-preview-img]').hidden;
    cta.textContent = form.elements.ctaLabel.value.trim() || 'Shop now';
    var color = form.elements.ctaColor.value;
    cta.style.background = color;
    cta.style.color = textOn(color);
    A.$all('[data-swatch]', form).forEach(function (b) { b.classList.toggle('is-active', b.dataset.swatch.toLowerCase() === color.toLowerCase()); });
  }

  function setPreview(src) {
    var img = $('[data-preview-img]');
    img.hidden = !src;
    if (src) img.src = src; else img.removeAttribute('src');
    $('[data-preview-empty]').hidden = !!src;
    previewCta();
  }

  function setMobilePreview(src) {
    var img = $('[data-mobile-preview]');
    img.hidden = !src;
    if (src) img.src = src;
    $('[data-remove-mobile-wrap]').hidden = !(src && editing && editing.mobileImage);
  }

  function clearErrors() {
    $('[data-form-error]').hidden = true;
    A.$all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });
  }

  function openForm(s) {
    editing = s || null;
    form.reset();
    clearErrors();
    $('[data-modal-title]').textContent = s ? 'Edit Slide' : 'Add Slide';
    $('[data-image-name]').textContent = '';
    $('[data-link-slot]').innerHTML = '<label class="form-label adm-label">Button links to</label>' +
      A.linkField(s ? { type: s.linkType, target: s.linkTarget } : { type: 'none', target: '' }, LINK_TYPES);
    if (s) {
      form.elements.alt.value = s.alt || '';
      form.elements.ctaLabel.value = s.ctaLabel || '';
      form.elements.ctaColor.value = s.ctaColor || DEFAULT_COLOR;
      form.elements.isActive.checked = s.isActive;
    }
    setPreview(s ? A.url(s.image) : '');
    setMobilePreview(s && s.mobileImage ? A.url(s.mobileImage) : '');
    previewCta();
    modal.show();
  }

  $('[data-image-input]').addEventListener('change', function (e) {
    var f = e.target.files[0];
    if (!f) return;
    $('[data-image-name]').textContent = f.name;
    setPreview(URL.createObjectURL(f));
  });
  $('[data-mobile-input]').addEventListener('change', function (e) {
    var f = e.target.files[0];
    if (f) { setMobilePreview(URL.createObjectURL(f)); form.elements.removeMobile.checked = false; }
  });
  form.addEventListener('click', function (e) {
    var sw = e.target.closest('[data-swatch]');
    if (sw) { form.elements.ctaColor.value = sw.dataset.swatch; previewCta(); }
  });
  form.addEventListener('change', previewCta);
  form.addEventListener('input', previewCta);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var link = A.readLink($('[data-link-field]', form));
    var fd = new FormData();
    fd.append('alt', form.elements.alt.value.trim());
    fd.append('linkType', link.type);
    fd.append('linkTarget', link.target);
    fd.append('ctaLabel', form.elements.ctaLabel.value.trim());
    fd.append('ctaColor', form.elements.ctaColor.value);
    fd.append('isActive', form.elements.isActive.checked ? '1' : '0');
    if (form.elements.removeMobile.checked) fd.append('removeMobile', '1');
    if (form.elements.image.files[0]) fd.append('image', form.elements.image.files[0]);
    if (form.elements.mobileImage.files[0]) fd.append('mobileImage', form.elements.mobileImage.files[0]);

    var btn = $('[data-save]');
    A.busy(btn, true);
    A.request('/hero' + (editing ? '/' + editing.id : ''), { method: 'POST', body: fd }).then(function () {
      modal.hide();
      A.toast(editing ? 'Slide updated.' : 'Slide added.');
      return load();
    }).catch(function (err) {
      var box = $('[data-form-error]');
      box.textContent = err.message;
      box.hidden = false;
      Object.keys(err.errors || {}).forEach(function (k) { var slot = $('[data-error-for="' + k + '"]', form); if (slot) slot.textContent = err.errors[k][0]; });
    }).then(function () { A.busy(btn, false); });
  });

  /* ---------- List actions ---------- */
  document.addEventListener('click', function (e) {
    var el;
    if (e.target.closest('[data-new-slide]')) { openForm(null); return; }
    if ((el = e.target.closest('[data-edit]'))) { openForm(slides.find(function (s) { return s.id === +el.dataset.edit; })); return; }
    if ((el = e.target.closest('[data-delete]'))) {
      A.confirm('This slide and its image will be removed from your home page.', { title: 'Delete slide?' }).then(function (ok) {
        if (!ok) return;
        A.request('/hero/' + el.dataset.delete, { method: 'DELETE' }).then(function () { A.toast('Slide deleted.'); return load(); })
          .catch(function (err) { A.toast(err.message, true); });
      });
      return;
    }
    if ((el = e.target.closest('[data-move]'))) {
      var ids = slides.map(function (s) { return s.id; });
      var i = ids.indexOf(+el.dataset.id), j = i + (+el.dataset.move);
      if (j < 0 || j >= ids.length) return;
      ids.splice(j, 0, ids.splice(i, 1)[0]);
      A.request('/hero/reorder', { method: 'POST', body: { ids: ids } }).then(function (json) { slides = json.data; render(); })
        .catch(function (err) { A.toast(err.message, true); });
    }
  });

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t.matches('[data-toggle]')) return;
    t.disabled = true;
    A.request('/hero/' + t.dataset.toggle, { method: 'PATCH', body: { isActive: t.checked } })
      .then(function () { A.toast(t.checked ? 'Slide is live.' : 'Slide hidden.'); return load(); })
      .catch(function (err) { t.checked = !t.checked; t.disabled = false; A.toast(err.message, true); });
  });

  A.loadOptions().then(load).catch(load);
})();
