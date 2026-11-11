/* Admin → Orders */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A || !A.$('[data-orders]')) return;
  var $ = A.$, esc = A.esc;

  var STATUSES = [['new', 'New'], ['contacted', 'Contacted'], ['completed', 'Completed'], ['cancelled', 'Cancelled']];
  var state = { status: 'all', page: 1 };
  var rows = [];
  var openId = null;
  var modal = new bootstrap.Modal($('#order-modal'));

  function statusSelect(o) {
    return '<select class="form-select form-select-sm adm-status-select adm-status-select--' + o.status + '" data-set-status="' + o.id + '" aria-label="Status of ' + esc(o.orderNumber) + '">' +
      STATUSES.map(function (s) { return '<option value="' + s[0] + '"' + (s[0] === o.status ? ' selected' : '') + '>' + s[1] + '</option>'; }).join('') + '</select>';
  }

  function render(meta, counts) {
    $('[data-orders-body]').innerHTML = rows.length ? rows.map(function (o) {
      var qty = o.items.reduce(function (n, i) { return n + i.quantity; }, 0);
      return '<tr data-row="' + o.id + '"><th scope="row">' + esc(o.orderNumber) + '</th>' +
        '<td>' + (o.name ? esc(o.name) + '<span class="adm-prod__cat">' + esc(o.phone || o.whatsapp) + '</span>' : '<span class="text-secondary">Not given</span>') + '</td>' +
        '<td>' + qty + ' item' + (qty === 1 ? '' : 's') + '</td><td>' + A.money(o.total) + '</td>' +
        '<td><span class="adm-status adm-status--' + (o.channel === 'whatsapp' ? 'in' : 'low') + '">' + (o.channel === 'whatsapp' ? 'WhatsApp' : 'Form') + '</span></td>' +
        '<td>' + statusSelect(o) + '</td><td class="text-secondary">' + esc(A.date(o.createdAt)) + '</td>' +
        '<td class="text-end"><button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-view="' + o.id + '">View</button></td></tr>';
    }).join('') : '<tr><td class="adm-empty" colspan="8">No orders here yet.</td></tr>';

    Object.keys(counts).forEach(function (k) { var el = $('[data-count="' + k + '"]'); if (el) el.textContent = '(' + counts[k] + ')'; });
    var from = meta.total ? (meta.page - 1) * meta.perPage + 1 : 0, to = Math.min(meta.total, meta.page * meta.perPage);
    $('[data-pager-info]').textContent = 'Showing ' + from + '-' + to + ' of ' + meta.total;
    var pages = '';
    for (var p = 1; p <= meta.pages; p++) pages += '<li><button class="adm-pager__btn' + (p === meta.page ? ' is-active' : '') + '" data-page="' + p + '">' + p + '</button></li>';
    $('[data-pager]').innerHTML = meta.pages > 1 ? pages : '';
  }

  function load() {
    var q = '?page=' + state.page + (state.status !== 'all' ? '&status=' + state.status : '');
    return A.request('/orders' + q).then(function (json) { rows = json.data; render(json.meta, json.counts); })
      .catch(function (e) { $('[data-orders-body]').innerHTML = '<tr><td class="adm-empty" colspan="8">' + esc(e.message) + '</td></tr>'; });
  }

  function detail(o) {
    var digits = function (v) { return String(v).replace(/\D/g, ''); };
    var contact = function (label, v, href) { return v ? '<div><dt>' + label + '</dt><dd>' + (href ? '<a href="' + esc(href) + '" target="_blank" rel="noopener">' + esc(v) + '</a>' : esc(v)) + '</dd></div>' : ''; };
    $('[data-order-title]').textContent = o.orderNumber + ' · ' + A.date(o.createdAt);
    $('[data-order-body]').innerHTML =
      '<div class="d-flex flex-wrap gap-3 align-items-center mb-3"><div>' + statusSelect(o) + '</div><span class="adm-status adm-status--' + (o.channel === 'whatsapp' ? 'in' : 'low') + '">Placed via ' + (o.channel === 'whatsapp' ? 'WhatsApp' : 'the form') + '</span></div>' +
      '<dl class="adm-dl">' + contact('Name', o.name) + contact('Phone', o.phone, o.phone ? 'tel:' + digits(o.phone) : '') +
        contact('WhatsApp', o.whatsapp, o.whatsapp ? 'https://wa.me/' + digits(o.whatsapp) : '') + contact('Address', o.address) + contact('Notes', o.notes) + '</dl>' +
      (!o.name && !o.phone && !o.whatsapp ? '<p class="small text-secondary">This customer went straight to WhatsApp, so no details were collected. They’ll message you with order ' + esc(o.orderNumber) + '.</p>' : '') +
      '<table class="table adm-table mt-3 mb-0"><thead><tr><th>Item</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">Subtotal</th></tr></thead><tbody>' +
      o.items.map(function (i) {
        var thumb = i.image ? '<img class="adm-prod__img" src="' + esc(A.url(i.image)) + '" alt="">' : '<span class="adm-prod__img"></span>';
        return '<tr><td><div class="adm-prod">' + thumb + '<span class="adm-prod__name">' + esc(i.name) + (i.description ? '<span class="adm-prod__cat adm-wrap">' + esc(i.description) + '</span>' : '') + '</span></div></td><td class="text-end">' + A.money(i.price) + '</td><td class="text-end">' + i.quantity + '</td><td class="text-end">' + A.money(i.price * i.quantity) + '</td></tr>'; }).join('') +
      '<tr><th colspan="3" class="text-end">Total</th><th class="text-end">' + A.money(o.total) + '</th></tr></tbody></table>';
  }

  document.addEventListener('click', function (e) {
    var el;
    if ((el = e.target.closest('[data-status]'))) {
      A.$all('[data-status]').forEach(function (b) { var on = b === el; b.classList.toggle('is-active', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
      state.status = el.dataset.status; state.page = 1; load(); return;
    }
    if ((el = e.target.closest('[data-page]'))) { state.page = +el.dataset.page; load(); return; }
    if ((el = e.target.closest('[data-view]'))) {
      var o = rows.find(function (r) { return r.id === +el.dataset.view; });
      if (o) { openId = o.id; detail(o); modal.show(); }
      return;
    }
    if (e.target.closest('[data-order-delete]')) {
      var order = rows.find(function (r) { return r.id === openId; });
      modal.hide();
      A.confirm('Order ' + (order ? order.orderNumber : '') + ' will be permanently deleted.', { title: 'Delete order?' }).then(function (ok) {
        if (!ok) return;
        A.request('/orders/' + openId, { method: 'DELETE' }).then(function () { A.toast('Order deleted.'); load(); }).catch(function (err) { A.toast(err.message, true); });
      });
    }
  });

  document.addEventListener('change', function (e) {
    var t = e.target;
    if (!t.matches('[data-set-status]')) return;
    A.request('/orders/' + t.dataset.setStatus, { method: 'PATCH', body: { status: t.value } }).then(function () {
      A.toast('Status updated.');
      load();
    }).catch(function (err) { A.toast(err.message, true); load(); });
  });

  load();
})();
