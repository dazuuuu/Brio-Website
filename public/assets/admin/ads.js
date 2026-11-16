/* Admin → Pop-up Ads */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-ads-list]')) return;
  var $ = A.$, esc = A.esc;
  var list = $('[data-ads-list]'), form = $('[data-ad-form]'), modal = new bootstrap.Modal($('#ad-modal'));
  var ads = [], editing = null;
  var LINK_TYPES = ['none', 'category', 'product', 'page', 'url'];
  var FREQ = { session: 'Once per visit', day: 'Once a day', always: 'Every page load' };
  var STATUS = { live: ['Live', 'in'], scheduled: ['Scheduled', 'low'], ended: ['Ended', 'out'], paused: ['Paused', 'out'] };

  function bi(n) { return '<i class="bi bi-' + n + '" style="font-size:16px" aria-hidden="true"></i>'; }
  function productById(id) { return ((A.options && A.options.products) || []).find(function (p) { return String(p.id) === String(id); }); }

  function thumb(a) {
    var src = '';
    if (a.kind === 'product' && a.productImage) return '<img src="' + esc(A.url(a.productImage)) + '" alt="">';
    if (a.mediaType === 'image') return '<img src="' + esc(A.url(a.mediaPath)) + '" alt="">';
    if (a.mediaType === 'video') return '<video src="' + esc(A.url(a.mediaPath)) + '#t=0.1" muted preload="metadata" playsinline></video>';
    return '<span class="adm-adthumb__text">' + esc((a.headline || a.name || 'Ad').slice(0, 40)) + '</span>';
  }

  function render() {
    if (!ads.length) {
      list.innerHTML = '<div class="adm-card adm-empty"><p class="mb-2"><b>No ads yet.</b></p><p class="mb-3">Create a pop-up to promote a product or share news with shoppers.</p><button class="btn adm-btn adm-btn--primary" data-new-ad>Create your first ad</button></div>';
      return;
    }
    list.innerHTML = ads.map(function (a, i) {
      var st = STATUS[a.status] || STATUS.paused;
      var what = a.kind === 'product' ? 'Product: ' + (a.productName || 'removed') + (a.offerPrice != null ? ' · offer ' + A.money(a.offerPrice) : '') : (a.mediaType === 'video' ? 'Video ad' : a.mediaType === 'image' ? 'Picture ad' : 'Message ad');
      var when = (FREQ[a.frequency] || '') + ' · ' + (a.pages === 'home' ? 'Home page' : 'All pages') + (a.startsAt ? ' · from ' + a.startsAt.replace('T', ' ') : '') + (a.endsAt ? ' · until ' + a.endsAt.replace('T', ' ') : '');
      var ctr = a.views ? Math.round(a.clicks * 1000 / a.views) / 10 + '%' : '—';
      return '<article class="adm-card adm-slide adm-ad' + (a.status === 'live' ? '' : ' is-off') + '" data-id="' + a.id + '">' +
        '<div class="adm-adthumb">' + thumb(a) + '</div>' +
        '<div class="adm-slide__body"><div class="d-flex flex-wrap align-items-center gap-2 mb-1"><strong>' + esc(a.name) + '</strong><span class="adm-status adm-status--' + st[1] + '">' + st[0] + '</span></div>' +
          '<p class="mb-0 small text-secondary">' + esc(what) + '</p><p class="mb-0 small text-secondary">' + esc(when) + '</p>' +
          '<p class="mb-0 small mt-1"><b>' + A.number(a.views) + '</b> views · <b>' + A.number(a.clicks) + '</b> clicks · ' + ctr + ' click rate</p></div>' +
        '<div class="adm-slide__actions">' +
          '<div class="form-check form-switch adm-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-toggle="' + a.id + '"' + (a.isActive ? ' checked' : '') + ' aria-label="Show ' + esc(a.name) + '"></div>' +
          '<a class="adm-icon-btn" href="' + esc(A.url('') + '?ad_preview=' + a.id) + '" target="_blank" rel="noopener" title="Preview on the store" aria-label="Preview ' + esc(a.name) + '">' + bi('eye') + '</a>' +
          '<button class="adm-icon-btn" data-move="-1" data-id="' + a.id + '" aria-label="Move up"' + (i === 0 ? ' disabled' : '') + '>' + bi('chevron-up') + '</button>' +
          '<button class="adm-icon-btn" data-move="1" data-id="' + a.id + '" aria-label="Move down"' + (i === ads.length - 1 ? ' disabled' : '') + '>' + bi('chevron-down') + '</button>' +
          '<button class="adm-icon-btn" data-edit="' + a.id + '" aria-label="Edit ' + esc(a.name) + '">' + bi('pencil-square') + '</button>' +
          '<button class="adm-icon-btn text-danger" data-delete="' + a.id + '" aria-label="Delete ' + esc(a.name) + '">' + bi('trash3') + '</button>' +
        '</div></article>';
    }).join('');
  }

  function load() {
    return A.request('/ads').then(function (json) { ads = json.data; render(); })
      .catch(function (e) { list.innerHTML = '<div class="adm-card adm-empty">' + esc(e.message) + '</div>'; });
  }

  /* ---------- form ---------- */
  function kind() { return form.querySelector('[name=kind]:checked').value; }
  function showKind() {
    var k = kind();
    A.$all('[data-kind-section]', form).forEach(function (s) {
      var on = s.dataset.kindSection === k;
      s.hidden = !on;
      A.$all('input, select, textarea', s).forEach(function (i) { i.disabled = !on; }); // so the hidden kind's fields aren't sent
    });
  }
  function showProduct() {
    var p = productById(form.elements.productId.value), box = $('[data-product-preview]');
    box.hidden = !p;
    if (p) box.innerHTML = (p.image ? '<img src="' + esc(A.url(p.image)) + '" alt="">' : '') + '<span><b>' + esc(p.name) + '</b><small>Current price ' + A.money(p.price) + '</small></span>';
  }
  function setMedia(src, type) {
    var box = $('[data-media-preview]');
    box.innerHTML = src ? (type === 'video' ? '<video src="' + esc(src) + '" muted controls playsinline></video>' : '<img src="' + esc(src) + '" alt="">') : '<span data-media-empty>' + bi('image') + '<br>Choose a picture or video</span>';
  }
  function clearErrors() {
    $('[data-form-error]').hidden = true;
    A.$all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });
  }

  function openForm(a) {
    editing = a || null;
    form.reset();
    clearErrors();
    $('[data-modal-title]').textContent = a ? 'Edit Ad' : 'Create Ad';
    var products = (A.options && A.options.products) || [];
    form.elements.productId.innerHTML = '<option value="">Choose a product…</option>' + products.map(function (p) { return '<option value="' + p.id + '">' + esc(p.name) + '</option>'; }).join('');
    $('[data-link-slot]').innerHTML = '<label class="form-label adm-label">Button links to <span class="text-secondary fw-normal">(optional)</span></label>' + A.linkField(a ? { type: a.linkType, target: a.linkTarget } : { type: 'none', target: '' }, LINK_TYPES);
    $('[data-media-name]').textContent = 'JPG, PNG or WebP up to 4 MB, or an MP4/WebM video up to 25 MB.';
    setMedia('', '');
    $('[data-remove-media-wrap]').hidden = true;

    form.querySelector('[name=kind][value="' + (a ? a.kind : 'product') + '"]').checked = true;
    if (a) {
      form.elements.name.value = a.name || '';
      form.elements.productId.value = a.productId || '';
      A.$all('[name=headline]', form).forEach(function (i) { i.value = a.headline || ''; });
      A.$all('[name=ctaLabel]', form).forEach(function (i) { i.value = a.ctaLabel || ''; });
      form.elements.offerText.value = a.offerText || '';
      form.elements.offerPrice.value = a.offerPrice != null ? a.offerPrice : '';
      form.elements.body.value = a.body || '';
      form.elements.frequency.value = a.frequency;
      form.elements.pages.value = a.pages;
      form.elements.delaySeconds.value = a.delaySeconds;
      form.elements.startsAt.value = a.startsAt || '';
      form.elements.endsAt.value = a.endsAt || '';
      form.elements.isActive.checked = a.isActive;
      if (a.mediaPath) { setMedia(A.url(a.mediaPath), a.mediaType); $('[data-remove-media-wrap]').hidden = false; }
    }
    showKind();
    showProduct();
    modal.show();
  }

  form.addEventListener('change', function (e) {
    if (e.target.name === 'kind') showKind();
    if (e.target.name === 'productId') showProduct();
    var input = e.target.closest('[data-media-input]');
    if (input && input.files[0]) {
      var f = input.files[0];
      $('[data-media-name]').textContent = f.name;
      setMedia(URL.createObjectURL(f), f.type.indexOf('video') === 0 ? 'video' : 'image');
      form.elements.removeMedia.checked = false;
    }
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var k = kind(), fd = new FormData();
    function val(n) { var el = A.$all('[name=' + n + ']', form).filter(function (i) { return !i.disabled; })[0]; return el ? el.value : ''; }
    fd.append('name', form.elements.name.value.trim());
    fd.append('kind', k);
    if (k === 'product') {
      ['productId', 'offerText', 'offerPrice', 'headline', 'ctaLabel'].forEach(function (n) { fd.append(n, val(n)); });
    } else {
      ['headline', 'body', 'ctaLabel'].forEach(function (n) { fd.append(n, val(n)); });
      var link = A.readLink($('[data-link-field]', form));
      fd.append('linkType', link.type);
      fd.append('linkTarget', link.target);
      if (form.elements.removeMedia.checked) fd.append('removeMedia', '1');
      if (form.elements.media.files[0]) fd.append('media', form.elements.media.files[0]);
    }
    ['frequency', 'pages', 'delaySeconds', 'startsAt', 'endsAt'].forEach(function (n) { fd.append(n, form.elements[n].value); });
    fd.append('isActive', form.elements.isActive.checked ? '1' : '0');

    var btn = $('[data-save]');
    A.busy(btn, true);
    A.request('/ads' + (editing ? '/' + editing.id : ''), { method: 'POST', body: fd }).then(function () {
      modal.hide();
      A.toast(editing ? 'Ad updated.' : 'Ad created.');
      return load();
    }).catch(function (err) {
      var box = $('[data-form-error]');
      box.textContent = err.message;
      box.hidden = false;
      Object.keys(err.errors || {}).forEach(function (k2) { var slot = $('[data-error-for="' + k2 + '"]', form); if (slot) slot.textContent = err.errors[k2][0]; });
    }).then(function () { A.busy(btn, false); });
  });

  /* ---------- list actions ---------- */
  document.addEventListener('click', function (e) {
    var el;
    if (e.target.closest('[data-new-ad]')) { openForm(null); return; }
    if ((el = e.target.closest('[data-edit]'))) { openForm(ads.find(function (a) { return a.id === +el.dataset.edit; })); return; }
    if ((el = e.target.closest('[data-delete]'))) {
      var ad = ads.find(function (a) { return a.id === +el.dataset.delete; });
      A.confirm('“' + (ad ? ad.name : 'This ad') + '” and its picture or video will be deleted.', { title: 'Delete ad?' }).then(function (ok) {
        if (!ok) return;
        A.request('/ads/' + el.dataset.delete, { method: 'DELETE' }).then(function () { A.toast('Ad deleted.'); return load(); }).catch(function (err) { A.toast(err.message, true); });
      });
      return;
    }
    if ((el = e.target.closest('[data-move]'))) {
      var ids = ads.map(function (a) { return a.id; });
      var i = ids.indexOf(+el.dataset.id), j = i + (+el.dataset.move);
      if (j < 0 || j >= ids.length) return;
      ids.splice(j, 0, ids.splice(i, 1)[0]);
      A.request('/ads/reorder', { method: 'POST', body: { ids: ids } }).then(function () { return load(); }).catch(function (err) { A.toast(err.message, true); });
    }
  });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t.matches('[data-toggle]')) return;
    t.disabled = true;
    A.request('/ads/' + t.dataset.toggle, { method: 'PATCH', body: { isActive: t.checked } })
      .then(function () { A.toast(t.checked ? 'Ad is on.' : 'Ad paused.'); return load(); })
      .catch(function (err) { t.checked = !t.checked; t.disabled = false; A.toast(err.message, true); });
  });

  A.loadOptions().catch(function () {}).then(load);
})();
