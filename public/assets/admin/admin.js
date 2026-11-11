/* Brio admin dashboard — talks to /api/admin/* (JSON, session + CSRF). */
(function () {
  'use strict';

  var root = document.querySelector('[data-mode]');
  if (!root) return;

  var MODE = root.dataset.mode;               // products | offers | featured
  var API = root.dataset.api;
  var CSRF = root.dataset.csrf;
  var BASE = root.dataset.base;
  var CATEGORIES = JSON.parse(root.dataset.categories || '{}');
  var STORE_KEY = 'brio.admin.' + MODE;

  function $(s, r) { return (r || document).querySelector(s); }
  function $all(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) { return '&#' + m.charCodeAt(0) + ';'; }); }
  function money(n) { return 'KSh ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 }); }
  function num(n) { return Number(n || 0).toLocaleString('en-KE'); }
  function imgUrl(p) { return p ? BASE + '/' + String(p).replace(/^\//, '') : ''; }
  function pref(key, fallback) { try { var v = JSON.parse(localStorage.getItem(STORE_KEY + '.' + key)); return v == null ? fallback : v; } catch (e) { return fallback; } }
  function savePref(key, v) { try { localStorage.setItem(STORE_KEY + '.' + key, JSON.stringify(v)); } catch (e) { /* ignore */ } }

  var ICON = {
    sort: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m7 9 5-5 5 5M7 15l5 5 5-5"/></svg>',
    more: '<svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="19" cy="12" r="1.6"/></svg>',
    star: '<svg width="14" height="14" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9Z"/></svg>',
    left: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
    right: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>'
  };

  /* ---------------- API ---------------- */
  function api(path, opts) {
    opts = opts || {};
    var headers = { 'Accept': 'application/json', 'X-CSRF-Token': CSRF };
    var body = opts.body;
    if (body && !(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
    return fetch(API + path, { method: opts.method || 'GET', headers: headers, body: body, credentials: 'same-origin' })
      .then(function (res) {
        return res.json().catch(function () { return { success: false, message: 'Unexpected server response.' }; }).then(function (json) {
          if (res.status === 401) { location.href = BASE + '/admin/login'; }
          if (!res.ok || !json.success) { var err = new Error(json.message || 'Request failed.'); err.errors = json.errors || {}; throw err; }
          return json;
        });
      });
  }

  function toast(msg, isError) {
    var el = document.createElement('div');
    el.className = 'toast adm-toast align-items-center' + (isError ? ' adm-toast--error' : '');
    el.setAttribute('role', isError ? 'alert' : 'status');
    el.innerHTML = '<div class="d-flex"><div class="toast-body">' + esc(msg) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    $('[data-toasts]').appendChild(el);
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    new bootstrap.Toast(el, { delay: isError ? 6000 : 2800 }).show();
  }

  /* ---------------- Columns ---------------- */
  var sortable = { price: 'price', sales: 'sales', revenue: 'revenue', stock: 'stock', rating: 'rating', product: 'name' };
  var COLUMNS = {
    product: { label: 'Product', cell: function (p) {
      return '<div class="adm-prod">' + (p.image ? '<img class="adm-prod__img" src="' + esc(imgUrl(p.image)) + '" alt="" loading="lazy">' : '<span class="adm-prod__img"></span>') +
        '<span class="adm-prod__name">' + esc(p.name) + '<span class="adm-prod__cat">' + esc(p.categoryLabel) + (p.badge ? ' · ' + esc(p.badge) : '') + '</span></span></div>';
    } },
    price: { label: 'Price', cell: function (p) { return money(p.price); } },
    compare: { label: 'Compare at', cell: function (p) { return p.compareAtPrice ? '<s class="text-secondary">' + money(p.compareAtPrice) + '</s>' : '<span class="text-secondary">—</span>'; } },
    sales: { label: 'Sales', cell: function (p) { return num(p.sales) + ' pcs'; } },
    revenue: { label: 'Revenue', cell: function (p) { return money(p.revenue); } },
    stock: { label: 'Stock', cell: function (p) { return num(p.stock) + ' pcs'; } },
    status: { label: 'Status', cell: function (p) {
      var map = { 'in': ['in', 'In Stock'], out: ['out', 'Out of Stock'], low: ['low', 'Low Stock'] }[p.stockStatus];
      return '<span class="adm-status adm-status--' + map[0] + '">' + map[1] + '</span>';
    } },
    rating: { label: 'Rating', cell: function (p) { return '<span class="adm-rating">' + ICON.star + Number(p.rating).toFixed(1) + '</span>'; } },
    deal: { label: 'On deal', cell: function (p) { return toggleCell(p, 'isDeal', 'Daily deal'); } },
    eyebrow: { label: 'Eyebrow', cell: function (p) { return p.dealEyebrow ? esc(p.dealEyebrow) : '<span class="text-secondary">—</span>'; } },
    tag: { label: 'Tag line', cell: function (p) { return p.dealTag ? esc(p.dealTag) : '<span class="text-secondary">—</span>'; } },
    tone: { label: 'Card', cell: function (p) { return '<span class="adm-tone-dot adm-tone-dot--' + esc(p.dealTone) + '"></span>' + esc(p.dealTone.charAt(0).toUpperCase() + p.dealTone.slice(1)); } },
    featured: { label: 'Featured', cell: function (p) { return toggleCell(p, 'isFeatured', 'Featured'); } },
    badge: { label: 'Badge', cell: function (p) { return p.badge ? esc(p.badge) : '<span class="text-secondary">—</span>'; } }
  };
  var DEFAULT_COLS = {
    products: ['product', 'price', 'sales', 'revenue', 'stock', 'status', 'rating'],
    offers: ['product', 'price', 'compare', 'deal', 'eyebrow', 'tag', 'tone', 'stock'],
    featured: ['product', 'price', 'featured', 'badge', 'sales', 'status', 'rating']
  }[MODE];
  var hiddenCols = pref('hiddenCols', []);
  function visibleCols() { return DEFAULT_COLS.filter(function (c) { return c === 'product' || hiddenCols.indexOf(c) === -1; }); }

  function toggleCell(p, field, label) {
    return '<div class="form-check form-switch adm-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-toggle-field="' + field + '" data-id="' + p.id + '"' +
      (p[field] ? ' checked' : '') + ' aria-label="' + esc(label + ': ' + p.name) + '"></div>';
  }

  /* ---------------- State ---------------- */
  var state = { filter: 'all', q: '', category: 'all', sort: 'created', dir: 'asc', page: 1 };
  var rows = [];
  var selected = {};
  var reqId = 0;

  function queryString(extra) {
    var p = new URLSearchParams();
    if (state.q) p.set('q', state.q);
    if (state.category !== 'all') p.set('category', state.category);
    if (MODE === 'products' && state.filter !== 'all') p.set('stock', state.filter);
    if (MODE === 'offers' && state.filter !== 'all') p.set('deal', state.filter === 'on' ? '1' : '0');
    if (MODE === 'featured' && state.filter !== 'all') p.set('featured', state.filter === 'on' ? '1' : '0');
    p.set('sort', state.sort); p.set('dir', state.dir); p.set('page', state.page);
    Object.keys(extra || {}).forEach(function (k) { p.set(k, extra[k]); });
    return p.toString();
  }

  function load() {
    var id = ++reqId;
    $('[data-tbody]').style.opacity = '.55';
    return api('/products?' + queryString()).then(function (json) {
      if (id !== reqId) return;
      rows = json.data;
      renderTable();
      renderPager(json.meta);
    }).catch(function (err) {
      if (id !== reqId) return;
      $('[data-tbody]').innerHTML = '<tr><td class="adm-empty" colspan="12">' + esc(err.message) + ' <button class="btn adm-btn adm-btn--ghost adm-btn--sm ms-2" data-retry>Try again</button></td></tr>';
    }).then(function () { $('[data-tbody]').style.opacity = ''; });
  }

  function loadStats() {
    return api('/stats').then(function (json) {
      var s = json.data;
      $('[data-stat="total"]').textContent = num(s.total);
      $('[data-stat="revenue"]').textContent = money(s.revenue);
      $('[data-stat="sold"]').textContent = num(s.sold);
      $('[data-stat="avgSales"]').textContent = num(s.avgSales);
      $('[data-stat-note="total"]').innerHTML = '<span class="adm-chip">' + num(s.featured) + ' featured</span> <span class="adm-chip">' + num(s.deals) + ' on deal</span>';
      $('[data-stat-note="revenue"]').textContent = 'Price × units sold';
      $('[data-stat-note="sold"]').textContent = 'Units across all products';
      $('[data-stat-note="avgSales"]').textContent = 'Units sold per product';
      var counts = MODE === 'products' ? { all: s.total, 'in': s['in'], out: s.out, low: s.low }
        : MODE === 'offers' ? { all: s.total, on: s.deals, off: s.total - s.deals }
        : { all: s.total, on: s.featured, off: s.total - s.featured };
      Object.keys(counts).forEach(function (k) { var el = $('[data-filter-count="' + k + '"]'); if (el) el.textContent = '(' + num(counts[k]) + ')'; });
    }).catch(function () { /* stats are non-critical */ });
  }

  function refresh() { loadStats(); return load(); }

  /* ---------------- Render ---------------- */
  function renderHead() {
    var cols = visibleCols();
    $('[data-thead]').innerHTML = '<th scope="col" style="width:44px"><input class="form-check-input adm-check" type="checkbox" data-check-all aria-label="Select all on this page"></th>' +
      cols.map(function (c) {
        var key = sortable[c];
        if (!key) return '<th scope="col">' + esc(COLUMNS[c].label) + '</th>';
        var on = state.sort === key;
        return '<th scope="col" aria-sort="' + (on ? (state.dir === 'asc' ? 'ascending' : 'descending') : 'none') + '"><button type="button" class="adm-th-sort' + (on ? ' is-sorted' : '') + '" data-th-sort="' + key + '">' + esc(COLUMNS[c].label) + ICON.sort + '</button></th>';
      }).join('') + '<th scope="col" class="text-center">Action</th>';
  }

  function renderTable() {
    renderHead();
    var cols = visibleCols();
    if (!rows.length) {
      $('[data-tbody]').innerHTML = '<tr><td class="adm-empty" colspan="' + (cols.length + 2) + '">No products match these filters.' +
        (MODE === 'products' ? ' <button class="btn adm-btn adm-btn--primary adm-btn--sm ms-2" data-new>Add a product</button>' : '') + '</td></tr>';
    } else {
      $('[data-tbody]').innerHTML = rows.map(function (p) {
        var sel = !!selected[p.id];
        return '<tr class="' + (sel ? 'is-selected' : '') + '" data-row="' + p.id + '">' +
          '<td><input class="form-check-input adm-check" type="checkbox" data-check="' + p.id + '"' + (sel ? ' checked' : '') + ' aria-label="Select ' + esc(p.name) + '"></td>' +
          cols.map(function (c) { return '<td>' + COLUMNS[c].cell(p) + '</td>'; }).join('') +
          '<td class="text-center"><div class="dropdown"><button class="adm-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for ' + esc(p.name) + '">' + ICON.more + '</button>' +
            '<ul class="dropdown-menu dropdown-menu-end adm-dropdown">' +
              '<li><button class="dropdown-item" data-edit="' + p.id + '">Edit product</button></li>' +
              '<li><button class="dropdown-item" data-quick="' + p.id + '" data-field="isFeatured" data-value="' + (p.isFeatured ? 0 : 1) + '">' + (p.isFeatured ? 'Remove from featured' : 'Add to featured') + '</button></li>' +
              '<li><button class="dropdown-item" data-quick="' + p.id + '" data-field="isDeal" data-value="' + (p.isDeal ? 0 : 1) + '">' + (p.isDeal ? 'Remove from daily deals' : 'Add to daily deals') + '</button></li>' +
              '<li><hr class="dropdown-divider"></li>' +
              '<li><button class="dropdown-item text-danger" data-delete="' + p.id + '">Delete</button></li>' +
            '</ul></div></td>' +
        '</tr>';
      }).join('');
    }
    syncSelection();
  }

  function renderPager(meta) {
    var from = meta.total ? (meta.page - 1) * meta.perPage + 1 : 0;
    var to = Math.min(meta.total, meta.page * meta.perPage);
    $('[data-pager-info]').textContent = 'Showing ' + from + '-' + to + ' of ' + num(meta.total);
    var pages = [], P = meta.pages, c = meta.page;
    for (var i = 1; i <= P; i++) {
      if (i === 1 || i === P || Math.abs(i - c) <= 1) pages.push(i);
      else if (pages[pages.length - 1] !== '…') pages.push('…');
    }
    $('[data-pager]').innerHTML =
      '<li><button class="adm-pager__btn" data-page="' + (c - 1) + '"' + (c <= 1 ? ' disabled' : '') + ' aria-label="Previous page">' + ICON.left + '</button></li>' +
      pages.map(function (p) {
        return p === '…' ? '<li><span class="adm-pager__btn" aria-hidden="true">…</span></li>'
          : '<li><button class="adm-pager__btn' + (p === c ? ' is-active' : '') + '" data-page="' + p + '"' + (p === c ? ' aria-current="page"' : '') + '>' + p + '</button></li>';
      }).join('') +
      '<li><button class="adm-pager__btn" data-page="' + (c + 1) + '"' + (c >= P ? ' disabled' : '') + ' aria-label="Next page">' + ICON.right + '</button></li>';
  }

  function renderColumnsMenu() {
    $('[data-columns-menu]').innerHTML = '<p class="px-2 pt-1 pb-2 mb-0 small text-secondary">Visible columns</p>' + DEFAULT_COLS.filter(function (c) { return c !== 'product'; }).map(function (c) {
      return '<div class="form-check"><input class="form-check-input" type="checkbox" id="col-' + c + '" data-col="' + c + '"' + (hiddenCols.indexOf(c) === -1 ? ' checked' : '') + '><label class="form-check-label" for="col-' + c + '">' + esc(COLUMNS[c].label) + '</label></div>';
    }).join('');
  }

  function syncSelection() {
    var ids = Object.keys(selected);
    $('[data-bulk]').hidden = ids.length === 0;
    $('[data-bulk-count]').textContent = ids.length;
    var all = $('[data-check-all]');
    if (all) {
      var onPage = rows.filter(function (p) { return selected[p.id]; }).length;
      all.checked = rows.length > 0 && onPage === rows.length;
      all.indeterminate = onPage > 0 && onPage < rows.length;
    }
  }

  /* ---------------- Product modal ---------------- */
  var modalEl = $('#product-modal');
  var modal = new bootstrap.Modal(modalEl);
  var form = $('[data-product-form]');
  var editingId = null;

  function setDealFields() { $('[data-deal-fields]').disabled = !$('[data-deal-toggle]').checked; }

  function clearErrors() {
    $('[data-form-error]').hidden = true;
    $all('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
    $all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });
  }

  function openForm(p) {
    editingId = p ? p.id : null;
    form.reset();
    clearErrors();
    $('[data-modal-title]').textContent = p ? 'Edit product' : 'Add New Product';
    var preview = $('[data-image-preview]');
    preview.hidden = true; preview.removeAttribute('src');
    $('[data-image-empty]').hidden = false;
    if (p) {
      ['name', 'category', 'badge', 'price', 'stock', 'sales', 'rating', 'reviewCount', 'description', 'dealEyebrow', 'dealTag'].forEach(function (k) {
        if (form.elements[k]) form.elements[k].value = p[k] == null ? '' : p[k];
      });
      form.elements.compareAtPrice.value = p.compareAtPrice == null ? '' : p.compareAtPrice;
      form.elements.isFeatured.checked = !!p.isFeatured;
      form.elements.isDeal.checked = !!p.isDeal;
      var tone = form.querySelector('[name=dealTone][value="' + p.dealTone + '"]');
      if (tone) tone.checked = true;
      if (p.image) { preview.src = imgUrl(p.image); preview.hidden = false; $('[data-image-empty]').hidden = true; }
    } else {
      if (MODE === 'offers') form.elements.isDeal.checked = true;
      if (MODE === 'featured') form.elements.isFeatured.checked = true;
    }
    setDealFields();
    modal.show();
  }

  $('[data-image-input]').addEventListener('change', function (e) {
    var file = e.target.files[0];
    if (!file) return;
    var preview = $('[data-image-preview]');
    preview.src = URL.createObjectURL(file);
    preview.hidden = false;
    $('[data-image-empty]').hidden = true;
  });
  $('[data-deal-toggle]').addEventListener('change', setDealFields);

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    clearErrors();
    var fd = new FormData(form);
    fd.set('isFeatured', form.elements.isFeatured.checked ? '1' : '0');
    fd.set('isDeal', form.elements.isDeal.checked ? '1' : '0');
    if (!form.elements.image.files.length) fd.delete('image');
    // disabled fieldset fields are omitted by FormData; keep their current values
    ['dealEyebrow', 'dealTag'].forEach(function (k) { if (!fd.has(k)) fd.set(k, form.elements[k].value); });
    if (!fd.has('dealTone')) { var t = form.querySelector('[name=dealTone]:checked'); fd.set('dealTone', t ? t.value : 'mint'); }

    var btn = $('[data-save]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Saving…';
    api('/products' + (editingId ? '/' + editingId : ''), { method: 'POST', body: fd }).then(function () {
      modal.hide();
      toast(editingId ? 'Product updated.' : 'Product added.');
      refresh();
    }).catch(function (err) {
      var box = $('[data-form-error]');
      box.textContent = err.message;
      box.hidden = false;
      Object.keys(err.errors || {}).forEach(function (field) {
        var input = form.elements[field];
        if (input && input.classList) input.classList.add('is-invalid');
        var slot = form.querySelector('[data-error-for="' + field + '"]');
        if (slot) slot.textContent = err.errors[field][0];
      });
    }).then(function () { btn.disabled = false; btn.textContent = 'Save product'; });
  });

  /* ---------------- Confirm ---------------- */
  var confirmModal = new bootstrap.Modal($('#confirm-modal'));
  var onConfirm = null;
  function confirmDelete(text, fn) { $('[data-confirm-text]').textContent = text; onConfirm = fn; confirmModal.show(); }
  $('[data-confirm-ok]').addEventListener('click', function () { confirmModal.hide(); if (onConfirm) onConfirm(); onConfirm = null; });

  function patch(id, data) { return api('/products/' + id, { method: 'PATCH', body: data }); }

  /* ---------------- Events ---------------- */
  document.addEventListener('click', function (e) {
    var t = e.target;
    var el;
    if ((el = t.closest('[data-new]'))) { openForm(null); return; }
    if ((el = t.closest('[data-retry]'))) { refresh(); return; }
    if ((el = t.closest('[data-filter]'))) {
      $all('[data-filter]').forEach(function (b) { var on = b === el; b.classList.toggle('is-active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
      state.filter = el.dataset.filter; state.page = 1; load(); return;
    }
    if ((el = t.closest('[data-category]'))) {
      $all('[data-category]').forEach(function (b) { b.classList.toggle('is-active', b === el); });
      state.category = el.dataset.category; state.page = 1;
      $('[data-category-label]').textContent = state.category === 'all' ? 'Filter' : CATEGORIES[state.category];
      load(); return;
    }
    if ((el = t.closest('[data-sort]'))) { state.sort = el.dataset.sort; state.dir = el.dataset.dir; state.page = 1; load(); return; }
    if ((el = t.closest('[data-th-sort]'))) {
      var key = el.dataset.thSort;
      state.dir = state.sort === key && state.dir === 'asc' ? 'desc' : 'asc';
      state.sort = key; state.page = 1; load(); return;
    }
    if ((el = t.closest('[data-page]')) && !el.disabled) { state.page = +el.dataset.page; load(); return; }
    if ((el = t.closest('[data-edit]'))) {
      var p = rows.find(function (r) { return r.id === +el.dataset.edit; });
      if (p) openForm(p);
      return;
    }
    if ((el = t.closest('[data-quick]'))) {
      var data = {}; data[el.dataset.field] = el.dataset.value === '1';
      patch(el.dataset.quick, data).then(function () { toast('Saved.'); refresh(); }).catch(function (err) { toast(err.message, true); });
      return;
    }
    if ((el = t.closest('[data-delete]'))) {
      var row = rows.find(function (r) { return r.id === +el.dataset.delete; });
      confirmDelete('“' + (row ? row.name : 'This product') + '” will be removed from the store. This can’t be undone.', function () {
        api('/products/' + el.dataset.delete, { method: 'DELETE' }).then(function () {
          delete selected[el.dataset.delete]; toast('Product deleted.'); refresh();
        }).catch(function (err) { toast(err.message, true); });
      });
      return;
    }
    if ((el = t.closest('[data-bulk-delete]'))) {
      var ids = Object.keys(selected);
      confirmDelete(ids.length + ' product(s) will be removed from the store. This can’t be undone.', function () {
        Promise.all(ids.map(function (id) { return api('/products/' + id, { method: 'DELETE' }); }))
          .then(function () { toast(ids.length + ' product(s) deleted.'); })
          .catch(function (err) { toast(err.message, true); })
          .then(function () { selected = {}; refresh(); });
      });
      return;
    }
    if ((el = t.closest('[data-bulk-action]'))) {
      var parts = el.dataset.bulkAction.split(':'), body = {};
      body[parts[0]] = parts[1] === '1';
      var list = Object.keys(selected);
      Promise.all(list.map(function (id) { return patch(id, body); }))
        .then(function () { toast(list.length + ' product(s) updated.'); })
        .catch(function (err) { toast(err.message, true); })
        .then(function () { selected = {}; refresh(); });
      return;
    }
    if ((el = t.closest('[data-export]'))) { exportCsv(el); }
  });

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches('[data-toggle-field]')) {
      var data = {}; data[t.dataset.toggleField] = t.checked;
      t.disabled = true;
      patch(t.dataset.id, data).then(function () { toast('Saved.'); loadStats(); })
        .catch(function (err) { t.checked = !t.checked; toast(err.message, true); })
        .then(function () { t.disabled = false; });
    } else if (t.matches('[data-check]')) {
      if (t.checked) selected[t.dataset.check] = true; else delete selected[t.dataset.check];
      t.closest('tr').classList.toggle('is-selected', t.checked);
      syncSelection();
    } else if (t.matches('[data-check-all]')) {
      rows.forEach(function (p) { if (t.checked) selected[p.id] = true; else delete selected[p.id]; });
      renderTable();
    } else if (t.matches('[data-col]')) {
      var c = t.dataset.col;
      hiddenCols = hiddenCols.filter(function (x) { return x !== c; });
      if (!t.checked) hiddenCols.push(c);
      savePref('hiddenCols', hiddenCols);
      renderTable();
    } else if (t.matches('[data-toggle-stats]')) {
      $('[data-stats]').hidden = !t.checked;
      savePref('stats', t.checked);
    }
  });

  // Search (debounced)
  var timer;
  $('[data-search]').addEventListener('input', function (e) {
    clearTimeout(timer);
    timer = setTimeout(function () { state.q = e.target.value.trim(); state.page = 1; load(); }, 300);
  });

  // Sidebar menu search + Ctrl/Cmd K
  var menuSearch = $('[data-menu-search]');
  menuSearch.addEventListener('input', function () {
    var q = menuSearch.value.trim().toLowerCase();
    $all('[data-menu-item]').forEach(function (a) { a.hidden = q && a.textContent.toLowerCase().indexOf(q) === -1; });
  });
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); $('[data-search]').focus(); }
  });

  /* ---------------- Export (CSV of the current filters, all pages) ---------------- */
  function exportCsv(btn) {
    btn.disabled = true;
    api('/products?' + queryString({ page: 1, per_page: 1000 })).then(function (json) {
      var head = ['ID', 'Name', 'Category', 'Price', 'Compare at', 'Stock', 'Sales', 'Revenue', 'Rating', 'Reviews', 'Featured', 'Deal', 'Badge'];
      var lines = [head].concat(json.data.map(function (p) {
        return [p.id, p.name, p.categoryLabel, p.price, p.compareAtPrice == null ? '' : p.compareAtPrice, p.stock, p.sales, p.revenue, p.rating, p.reviewCount, p.isFeatured ? 'yes' : 'no', p.isDeal ? 'yes' : 'no', p.badge || ''];
      })).map(function (r) { return r.map(function (v) { var s = String(v); if (/^[=+\-@]/.test(s)) s = "'" + s; return '"' + s.replace(/"/g, '""') + '"'; }).join(','); });
      var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8' });
      var a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'brio-' + MODE + '-' + new Date().toISOString().slice(0, 10) + '.csv';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
    }).catch(function (err) { toast(err.message, true); }).then(function () { btn.disabled = false; });
  }

  /* ---------------- Init ---------------- */
  var showStats = pref('stats', true);
  $('[data-toggle-stats]').checked = showStats;
  $('[data-stats]').hidden = !showStats;
  renderColumnsMenu();
  refresh();
})();
