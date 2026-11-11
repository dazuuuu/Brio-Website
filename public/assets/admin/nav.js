/* Admin → Navigation (header and footer menus) */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-nav]')) return;
  var $ = A.$, esc = A.esc;

  var HINTS = {
    header: 'These links appear in the bar under your logo. On phones they open from the menu button.',
    footer: 'These links appear in the “Company” column of the footer.'
  };
  var TYPES = ['home', 'shop', 'category', 'product', 'page', 'url'];
  var menus = { header: null, footer: null };
  var current = 'header';
  var listEl = $('[data-nav-list]');

  function row(item, i, n) {
    return '<div class="adm-card adm-navrow" data-i="' + i + '">' +
      '<div class="adm-navrow__grid">' +
        '<div><label class="form-label adm-label">Label</label><input class="form-control adm-input" data-label value="' + esc(item.label) + '" maxlength="60" placeholder="e.g. About us"></div>' +
        '<div><label class="form-label adm-label">Links to</label>' + A.linkField({ type: item.linkType, target: item.linkTarget }, TYPES) + '</div>' +
      '</div>' +
      '<div class="adm-navrow__tools">' +
        '<label class="form-check mb-0 small"><input class="form-check-input" type="checkbox" data-newtab' + (item.newTab ? ' checked' : '') + '> <span class="form-check-label">New tab</span></label>' +
        '<div class="form-check form-switch adm-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-active' + (item.isActive ? ' checked' : '') + ' aria-label="Show this link"><label class="form-check-label small">Show</label></div>' +
        '<span class="adm-navrow__move"><button type="button" class="adm-icon-btn" data-act="up" aria-label="Move up"' + (i === 0 ? ' disabled' : '') + '>▲</button>' +
        '<button type="button" class="adm-icon-btn" data-act="down" aria-label="Move down"' + (i === n - 1 ? ' disabled' : '') + '>▼</button>' +
        '<button type="button" class="adm-icon-btn text-danger" data-act="del" aria-label="Remove link">✕</button></span>' +
      '</div></div>';
  }

  function render() {
    var items = menus[current] || [];
    $('[data-menu-hint]').textContent = HINTS[current];
    listEl.innerHTML = items.length ? items.map(function (it, i) { return row(it, i, items.length); }).join('')
      : '<div class="adm-card adm-empty">This menu is empty. Add your first link.</div>';
  }

  function load(loc) {
    return A.request('/nav?location=' + loc).then(function (json) { menus[loc] = json.data; });
  }

  function show(loc) {
    current = loc;
    A.$all('[data-menu]').forEach(function (b) { var on = b.dataset.menu === loc; b.classList.toggle('is-active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
    $('[data-nav-error]').hidden = true;
    (menus[loc] ? Promise.resolve() : load(loc)).then(render).catch(function (e) { listEl.innerHTML = '<div class="adm-card adm-empty">' + esc(e.message) + '</div>'; });
  }

  document.addEventListener('click', function (e) {
    var tab = e.target.closest('[data-menu]');
    if (tab) { show(tab.dataset.menu); return; }
    if (e.target.closest('[data-add-item]')) {
      menus[current] = menus[current] || [];
      menus[current].push({ label: '', linkType: 'page', linkTarget: '', newTab: false, isActive: true });
      render();
      var last = listEl.lastElementChild;
      if (last) { last.scrollIntoView({ behavior: 'smooth', block: 'center' }); $('[data-label]', last).focus({ preventScroll: true }); }
      return;
    }
    var act = e.target.closest('[data-act]');
    if (!act) return;
    var items = menus[current], i = +act.closest('[data-i]').dataset.i;
    if (act.dataset.act === 'del') items.splice(i, 1);
    else {
      var j = i + (act.dataset.act === 'up' ? -1 : 1);
      if (j < 0 || j >= items.length) return;
      items.splice(j, 0, items.splice(i, 1)[0]);
    }
    render();
  });

  function onField(e) {
    var rowEl = e.target.closest('[data-i]');
    if (!rowEl) return;
    var it = menus[current][+rowEl.dataset.i];
    if (e.target.matches('[data-label]')) it.label = e.target.value;
    else if (e.target.matches('[data-newtab]')) it.newTab = e.target.checked;
    else if (e.target.matches('[data-active]')) it.isActive = e.target.checked;
    else if (e.target.closest('[data-link-field]') && e.type === 'change') {
      var l = A.readLink(e.target.closest('[data-link-field]'));
      it.linkType = l.type;
      it.linkTarget = l.target;
    }
  }
  listEl.addEventListener('input', onField);
  listEl.addEventListener('change', onField);

  $('[data-save-nav]').addEventListener('click', function () {
    var btn = this, err = $('[data-nav-error]');
    err.hidden = true;
    A.busy(btn, true);
    A.request('/nav', { method: 'POST', body: { location: current, items: menus[current] || [] } }).then(function (json) {
      menus[current] = json.data;
      render();
      A.toast('Menu saved. It’s live on your store.');
    }).catch(function (e) { err.textContent = e.message; err.hidden = false; }).then(function () { A.busy(btn, false); });
  });

  A.loadOptions().catch(function () {}).then(function () { show('header'); });
})();
