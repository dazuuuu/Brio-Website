/* Shared helpers for the newer admin pages (hero, pages, navigation, orders, settings). */
(function () {
  'use strict';

  var root = document.querySelector('[data-admin-root]');
  var cfg = root ? root.dataset : {};
  var A = window.BrioAdmin = { root: root, apiBase: cfg.api || '', csrf: cfg.csrf || '', base: cfg.base || '', options: null };

  A.$ = function (s, r) { return (r || document).querySelector(s); };
  A.$all = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  A.esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) { return '&#' + m.charCodeAt(0) + ';'; }); };
  A.url = function (path) { return A.base + '/' + String(path || '').replace(/^\//, ''); };
  A.money = function (n) { return 'KSh ' + Number(n || 0).toLocaleString('en-KE', { maximumFractionDigits: 2 }); };
  A.debounce = function (fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms || 300); }; };
  A.date = function (s) { var d = new Date(String(s).replace(' ', 'T')); return isNaN(d) ? s : d.toLocaleDateString('en-KE', { day: 'numeric', month: 'short', year: 'numeric' }) + ', ' + d.toLocaleTimeString('en-KE', { hour: '2-digit', minute: '2-digit' }); };

  /** JSON API call. Body may be FormData or a plain object. Rejects with Error(message) + .errors. */
  A.request = function (path, opts) {
    opts = opts || {};
    var headers = { 'Accept': 'application/json', 'X-CSRF-Token': A.csrf };
    var body = opts.body;
    if (body && !(body instanceof FormData)) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(body); }
    return fetch(A.apiBase + path, { method: opts.method || 'GET', headers: headers, body: body, credentials: 'same-origin' }).then(function (res) {
      return res.json().catch(function () { return { success: false, message: 'Unexpected server response.' }; }).then(function (json) {
        if (res.status === 401) location.href = A.url('admin/login');
        if (!res.ok || !json.success) { var err = new Error(json.message || 'Request failed.'); err.errors = json.errors || {}; throw err; }
        return json;
      });
    });
  };

  A.toast = function (msg, isError) {
    var host = A.$('[data-toasts]');
    if (!host) return;
    var el = document.createElement('div');
    el.className = 'toast adm-toast align-items-center' + (isError ? ' adm-toast--error' : '');
    el.setAttribute('role', isError ? 'alert' : 'status');
    el.innerHTML = '<div class="d-flex"><div class="toast-body">' + A.esc(msg) + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    host.appendChild(el);
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
    new bootstrap.Toast(el, { delay: isError ? 6000 : 2800 }).show();
  };

  A.confirm = function (text, opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      var el = A.$('#adm-confirm');
      var modal = bootstrap.Modal.getOrCreateInstance(el);
      A.$('[data-confirm-title]', el).textContent = opts.title || 'Are you sure?';
      A.$('[data-confirm-text]', el).textContent = text;
      var ok = A.$('[data-confirm-ok]', el);
      ok.textContent = opts.ok || 'Delete';
      var confirmed = false;
      function onOk() { confirmed = true; modal.hide(); resolve(true); }
      function onHide() { ok.removeEventListener('click', onOk); el.removeEventListener('hidden.bs.modal', onHide); if (!confirmed) resolve(false); }
      ok.addEventListener('click', onOk);
      el.addEventListener('hidden.bs.modal', onHide);
      modal.show();
    });
  };

  A.busy = function (btn, on, label) {
    if (!btn) return;
    if (on) { btn.dataset.label = btn.innerHTML; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' + (label || 'Saving…'); }
    else { btn.disabled = false; if (btn.dataset.label) btn.innerHTML = btn.dataset.label; }
  };

  A.upload = function (file, folder) {
    var fd = new FormData();
    fd.append('image', file);
    fd.append('folder', folder || 'pages');
    return A.request('/uploads', { method: 'POST', body: fd }).then(function (json) { return json.data.path; });
  };

  /* ---------------- Link picker (hero CTA, menus, page buttons) ---------------- */
  var TYPE_LABELS = { none: 'No link', home: 'Home page', shop: 'All products', category: 'A category', product: 'A specific product', page: 'One of my pages', url: 'Custom URL' };

  A.loadOptions = function () {
    if (A.options) return Promise.resolve(A.options);
    return A.request('/link-options').then(function (json) { A.options = json.data; return A.options; });
  };

  function targetSelect(rows, current, placeholder) {
    return '<select class="form-select adm-input" data-link-value aria-label="' + placeholder + '"><option value="">' + placeholder + '…</option>' +
      rows.map(function (r) { return '<option value="' + A.esc(r[0]) + '"' + (String(r[0]) === String(current) ? ' selected' : '') + '>' + A.esc(r[1]) + '</option>'; }).join('') + '</select>';
  }

  function targetHtml(type, target) {
    var o = A.options || { categories: [], products: [], pages: [] };
    if (type === 'category') return targetSelect(o.categories.map(function (c) { return [c.slug, c.label]; }), target, 'Choose a category');
    if (type === 'product') return targetSelect(o.products.map(function (p) { return [p.id, p.name]; }), target, 'Choose a product');
    if (type === 'page') return targetSelect(o.pages.map(function (p) { return [p.id, p.title + (p.isPublished ? '' : ' (draft)')]; }), target, 'Choose a page');
    if (type === 'url') return '<input class="form-control adm-input" data-link-value placeholder="https://… or /path" value="' + A.esc(target || '') + '" aria-label="Link URL">';
    return '';
  }

  A.linkField = function (value, types) {
    value = value || { type: types[0], target: '' };
    return '<div class="adm-link" data-link-field><select class="form-select adm-input" data-link-type aria-label="Link to">' +
      types.map(function (t) { return '<option value="' + t + '"' + (t === value.type ? ' selected' : '') + '>' + TYPE_LABELS[t] + '</option>'; }).join('') +
      '</select><div class="adm-link__target" data-link-target>' + targetHtml(value.type, value.target) + '</div></div>';
  };

  A.readLink = function (field) {
    var v = A.$('[data-link-value]', field);
    return { type: A.$('[data-link-type]', field).value, target: v ? v.value.trim() : '' };
  };

  A.describeLink = function (type, target) {
    var o = A.options || { categories: [], products: [], pages: [] };
    if (type === 'category') { var c = o.categories.find(function (x) { return x.slug === target; }); return 'Category: ' + (c ? c.label : target); }
    if (type === 'product') { var p = o.products.find(function (x) { return String(x.id) === String(target); }); return 'Product: ' + (p ? p.name : 'removed'); }
    if (type === 'page') { var g = o.pages.find(function (x) { return String(x.id) === String(target); }); return 'Page: ' + (g ? g.title : 'removed'); }
    if (type === 'url') return 'Link: ' + target;
    return TYPE_LABELS[type] || '';
  };

  // Capture phase so the target control is rebuilt before any page-level change handler reads the link.
  document.addEventListener('change', function (e) {
    var sel = e.target.closest && e.target.closest('[data-link-type]');
    if (!sel) return;
    A.$('[data-link-target]', sel.closest('[data-link-field]')).innerHTML = targetHtml(sel.value, '');
  }, true);

  // Sidebar menu search + Ctrl/Cmd K
  var menuSearch = A.$('[data-menu-search]');
  if (menuSearch) {
    menuSearch.addEventListener('input', function () {
      var q = menuSearch.value.trim().toLowerCase();
      A.$all('[data-menu-item]').forEach(function (a) { a.hidden = q && a.textContent.toLowerCase().indexOf(q) === -1; });
    });
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); menuSearch.focus(); }
    });
  }
})();
