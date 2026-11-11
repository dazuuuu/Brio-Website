/* Brio storefront behaviour (vanilla JS + Bootstrap 5).
   Ports CartContext, AuthContext, the catalog filters and the mock API from the React UI. */
(function () {
  'use strict';

  var BASE = (window.BRIO && window.BRIO.base) || '';
  var CART_KEY = 'brio.cart.v1';
  var AUTH_KEY = 'brio.auth.user.v1';
  var ORDERS_KEY = 'brio.orders.v1';
  var DEMO_EMAIL = 'amani.kariuki@example.com';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function url(path) { return BASE + '/' + String(path || '').replace(/^\//, ''); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (m) { return '&#' + m.charCodeAt(0) + ';'; }); }
  function money(n) { return 'KSh ' + Math.round(n).toLocaleString('en-KE'); }
  function formatDate(iso) { return new Date(iso).toLocaleDateString('en-KE', { year: 'numeric', month: 'short', day: 'numeric' }); }
  function delay(ms) { return new Promise(function (r) { setTimeout(r, ms || 450); }); }

  function read(key, fallback) {
    try { var raw = localStorage.getItem(key); return raw ? JSON.parse(raw) : fallback; } catch (e) { return fallback; }
  }
  function write(key, value) {
    try { if (value === null) localStorage.removeItem(key); else localStorage.setItem(key, JSON.stringify(value)); } catch (e) { /* private mode: in-memory only */ }
  }

  var ICON = {
    minus: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg>',
    plus: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
    trash: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>'
  };

  function setLoading(btn, on) {
    if (!btn) return;
    if (on) {
      btn.dataset.label = btn.innerHTML;
      btn.disabled = true;
      btn.setAttribute('aria-busy', 'true');
      btn.innerHTML = '<span class="spinner-border" aria-hidden="true"></span>' + btn.dataset.label;
    } else {
      btn.disabled = false;
      btn.removeAttribute('aria-busy');
      if (btn.dataset.label) btn.innerHTML = btn.dataset.label;
    }
  }

  /* ------------------------------------------------------------------
     Cart
     ------------------------------------------------------------------ */
  function isCartItem(v) {
    return v && typeof v.productId === 'number' && typeof v.name === 'string' && typeof v.price === 'number' &&
      typeof v.image === 'string' && typeof v.quantity === 'number' && v.quantity > 0 && typeof v.maxQuantity === 'number';
  }
  function loadCart() { var c = read(CART_KEY, []); return Array.isArray(c) ? c.filter(isCartItem) : []; }

  var cart = loadCart();
  var drawerEl = $('#cart-drawer');
  var drawer = drawerEl && window.bootstrap ? bootstrap.Offcanvas.getOrCreateInstance(drawerEl) : null;

  function saveCart() { write(CART_KEY, cart); renderCart(); }

  function addItem(p) {
    if (p.stock <= 0) return;
    var line = cart.find(function (i) { return i.productId === p.id; });
    if (line) line.quantity = Math.min(line.quantity + 1, line.maxQuantity);
    else cart.push({ productId: p.id, name: p.name, category: p.category, price: p.price, image: p.image, description: p.description || '', quantity: 1, maxQuantity: p.stock });
    saveCart();
    if (drawer) drawer.show();
  }

  function setQuantity(id, q) {
    cart = q <= 0 ? cart.filter(function (i) { return i.productId !== id; })
                  : cart.map(function (i) { if (i.productId === id) i.quantity = Math.min(q, i.maxQuantity); return i; });
    saveCart();
  }

  function renderCart() {
    onCartRendered();
    var count = cart.reduce(function (n, i) { return n + i.quantity; }, 0);
    var subtotal = cart.reduce(function (s, i) { return s + Math.round(i.price * 100) * i.quantity; }, 0) / 100;

    $all('[data-cart-count]').forEach(function (b) { b.textContent = count; b.hidden = count === 0; });
    $all('[data-cart-count-text]').forEach(function (b) { b.textContent = count; });
    $all('[data-cart-button]').forEach(function (b) { b.setAttribute('aria-label', 'Cart, ' + count + ' ' + (count === 1 ? 'item' : 'items')); });
    if (!drawerEl) return;

    var state = cart.length ? 'items' : 'empty';
    $all('[data-cart-state]', drawerEl).forEach(function (el) { el.hidden = el.dataset.cartState !== state; });

    $('[data-cart-items]', drawerEl).innerHTML = cart.map(function (i) {
      var n = esc(i.name);
      return '<li class="cart-line">' +
        '<img src="' + esc(i.image) + '" alt="" class="cart-line__img">' +
        '<div class="cart-line__info"><p class="cart-line__name">' + n + '</p>' + (i.description ? '<p class="cart-line__desc">' + esc(i.description) + '</p>' : '') + '<p class="cart-line__price">' + money(i.price) + '</p>' +
          '<div class="qty" role="group" aria-label="Quantity for ' + n + '">' +
            '<button type="button" class="qty__btn" data-qty="' + i.productId + '" data-step="-1"' + (i.quantity <= 1 ? ' disabled' : '') + ' aria-label="Decrease quantity of ' + n + '">' + ICON.minus + '</button>' +
            '<span class="qty__value" aria-live="polite">' + i.quantity + '</span>' +
            '<button type="button" class="qty__btn" data-qty="' + i.productId + '" data-step="1"' + (i.quantity >= i.maxQuantity ? ' disabled' : '') + ' aria-label="Increase quantity of ' + n + '">' + ICON.plus + '</button>' +
          '</div></div>' +
        '<div class="cart-line__end"><button type="button" class="icon-btn" data-remove="' + i.productId + '" aria-label="Remove ' + n + ' from cart">' + ICON.trash + '</button>' +
          '<span class="cart-line__total">' + money(i.price * i.quantity) + '</span></div>' +
      '</li>';
    }).join('');
    $('[data-cart-subtotal]', drawerEl).textContent = money(subtotal);
  }

  /* checkout page hook (defined further down) */
  var onCartRendered = function () {};

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-to-cart]');
    if (add) { try { addItem(JSON.parse(add.getAttribute('data-add-to-cart'))); } catch (err) { /* bad payload */ } return; }

    var qty = e.target.closest('[data-qty]');
    if (qty) {
      var id = +qty.dataset.qty, line = cart.find(function (i) { return i.productId === id; });
      if (line) setQuantity(id, line.quantity + (+qty.dataset.step));
      return;
    }
    var rm = e.target.closest('[data-remove]');
    if (rm) { setQuantity(+rm.dataset.remove, 0); }
  });


  // Keep other tabs in sync.
  window.addEventListener('storage', function (e) { if (e.key === CART_KEY) { cart = loadCart(); renderCart(); } });

  renderCart();

  /* ------------------------------------------------------------------
     Auth (mock, mirrors mockApi.login / register)
     ------------------------------------------------------------------ */
  var DEMO_CUSTOMER = {
    firstName: 'Amani', lastName: 'Kariuki', email: DEMO_EMAIL, phone: '+254 700 123 456',
    memberSince: '2024-03-14T09:00:00Z', demo: true
  };
  function getUser() { var u = read(AUTH_KEY, null); return u && typeof u.email === 'string' && typeof u.firstName === 'string' ? u : null; }

  var user = getUser();
  $all('[data-account-link]').forEach(function (a) {
    a.href = url(user ? 'account' : 'login');
    if (a.hasAttribute('data-account-label')) a.textContent = user ? 'My Account (' + user.firstName + ')' : 'Sign In / Register';
    else {
      a.setAttribute('aria-label', user ? 'My account (' + user.firstName + ')' : 'Sign in to your account');
      a.title = user ? 'Signed in as ' + user.firstName : 'Sign In';
    }
  });

  function showAuthError(msg) {
    var el = $('[data-auth-error]');
    if (!el) return;
    el.textContent = msg || '';
    el.hidden = !msg;
  }

  var loginForm = $('[data-login-form]');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      showAuthError(null);
      var email = loginForm.email.value.trim(), password = loginForm.password.value;
      if (!email || !password) return showAuthError('Please enter both your email address and password.');
      var btn = $('[type=submit]', loginForm), fields = $all('input, button', loginForm);
      fields.forEach(function (f) { f.disabled = true; });
      setLoading(btn, true);
      delay().then(function () {
        if (password.length < 4) throw new Error('Invalid email or password.');
        var customer = DEMO_CUSTOMER;
        if (email.toLowerCase() !== DEMO_EMAIL) {
          var namePart = email.split('@')[0] || 'User';
          customer = Object.assign({}, DEMO_CUSTOMER, { firstName: namePart.charAt(0).toUpperCase() + namePart.slice(1), lastName: '', email: email, demo: false });
        }
        write(AUTH_KEY, customer);
        var redirect = new URLSearchParams(location.search).get('redirect');
        location.replace(redirect && redirect.charAt(0) === '/' && redirect.charAt(1) !== '/' ? BASE + redirect : url('account'));
      }).catch(function (err) {
        showAuthError(err.message || 'Unable to sign in. Please verify your credentials.');
        fields.forEach(function (f) { f.disabled = false; });
        setLoading(btn, false);
      });
    });
    $('[data-demo-fill]').addEventListener('click', function () {
      loginForm.email.value = DEMO_EMAIL;
      loginForm.password.value = 'secret123';
      showAuthError(null);
    });
    $('[data-forgot]').addEventListener('click', function (e) { e.preventDefault(); alert('Password reset link has been sent to your email.'); });
  }

  var regForm = $('[data-register-form]');
  if (regForm) {
    $all('a[href^="#"]', regForm).forEach(function (a) { a.addEventListener('click', function (e) { e.preventDefault(); }); });
    regForm.addEventListener('submit', function (e) {
      e.preventDefault();
      showAuthError(null);
      var f = regForm;
      var v = { firstName: f.firstName.value.trim(), lastName: f.lastName.value.trim(), email: f.email.value.trim(), phone: f.phone.value.trim(), password: f.password.value };
      if (!v.firstName || !v.lastName || !v.email || !v.password) return showAuthError('Please fill in all required fields.');
      if (v.password.length < 6) return showAuthError('Password must be at least 6 characters long.');
      if (v.password !== f.confirmPassword.value) return showAuthError('Passwords do not match. Please re-enter your password.');
      if (!f.terms.checked) return showAuthError('Please accept the terms of service to create your account.');
      var btn = $('[type=submit]', f), fields = $all('input, button', f);
      fields.forEach(function (x) { x.disabled = true; });
      setLoading(btn, true);
      delay().then(function () {
        write(AUTH_KEY, { firstName: v.firstName, lastName: v.lastName, email: v.email.toLowerCase(), phone: v.phone || null, memberSince: new Date().toISOString(), addresses: [] });
        location.replace(url('account'));
      });
    });
  }

  /* ------------------------------------------------------------------
     Account page: show the signed-in customer + locally placed orders
     ------------------------------------------------------------------ */
  var account = $('[data-account]');
  if (account) {
    if (user) {
      $('[data-account-greeting]').textContent = 'Hi, ' + user.firstName;
      $('[data-profile-name]').textContent = (user.firstName + ' ' + (user.lastName || '')).trim();
      $('[data-profile-initials]').textContent = (user.firstName.charAt(0) + (user.lastName || '').charAt(0)).toUpperCase();
      $('[data-profile-email]').textContent = user.email;
      $('[data-profile-phone]').textContent = user.phone || '—';
      $('[data-profile-since]').textContent = formatDate(user.memberSince);
      if (Array.isArray(user.addresses) && !user.addresses.length) { $('[data-addresses]').hidden = true; $('[data-addresses-empty]').hidden = false; }
    }
    var labels = { processing: 'Processing', completed: 'Completed', cancelled: 'Cancelled' };
    var local = read(ORDERS_KEY, []);
    if (Array.isArray(local) && local.length) {
      $('[data-orders-body]').insertAdjacentHTML('afterbegin', local.map(function (o) {
        return '<tr><th scope="row">' + esc(o.orderNumber) + '</th><td>' + esc(formatDate(o.createdAt)) + '</td><td><ul class="orders-table__items">' +
          o.items.map(function (i) { return '<li>' + (+i.quantity) + ' × ' + esc(i.name) + '</li>'; }).join('') +
          '</ul></td><td class="text-end">' + money(o.total) + '</td><td><span class="status status--' + esc(o.status) + '">' + esc(labels[o.status] || o.status) + '</span></td></tr>';
      }).join(''));
    }
    $('[data-sign-out]').addEventListener('click', function () { write(AUTH_KEY, null); location.href = url('login'); });
  }

  /* ------------------------------------------------------------------
     Catalog: instant category tabs + debounced live search (URL stays in sync)
     ------------------------------------------------------------------ */
  var catalog = $('[data-catalog]');
  if (catalog) {
    var LABELS = { all: 'All Products', 'skin-care': 'Skin Care', 'bath-body': 'Bath & Body' };
    var params = new URLSearchParams(location.search);
    var category = LABELS[params.get('category')] ? params.get('category') : 'all';
    var input = $('[data-catalog-search] input[type=search]', catalog);
    var timer;

    function apply(push) {
      var q = input.value.trim(), ql = q.toLowerCase(), shown = 0;
      $all('[data-product]', catalog).forEach(function (card) {
        var ok = (category === 'all' || card.dataset.category === category) && (!ql || card.dataset.name.indexOf(ql) !== -1);
        card.hidden = !ok;
        if (ok) shown++;
      });
      $('[data-product-grid]', catalog).hidden = shown === 0;
      $('[data-empty-state]', catalog).hidden = shown > 0;
      $('[data-empty-text]', catalog).textContent = q ? 'Nothing matched “' + q + '”. Try a different word or category.' : 'There are no products in this category yet.';
      $('[data-catalog-title]', catalog).textContent = LABELS[category];
      $('[data-catalog-count]', catalog).textContent = shown + ' ' + (shown === 1 ? 'product' : 'products') + (q ? ' for “' + q + '”' : '');
      $all('[data-tab]', catalog).forEach(function (t) {
        var on = t.dataset.tab === category;
        t.classList.toggle('active', on);
        t.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      $all('.site-nav__link').forEach(function (a) {
        var c = new URL(a.href, location.href);
        if (c.pathname !== location.pathname) return;
        a.classList.toggle('is-active', (c.searchParams.get('category') || 'all') === category);
      });
      var next = new URLSearchParams();
      if (category !== 'all') next.set('category', category);
      if (q) next.set('q', q);
      var target = location.pathname + (next.toString() ? '?' + next : '');
      history[push ? 'pushState' : 'replaceState'](null, '', target);
      var hidden = $('[data-catalog-search] input[name=category]', catalog);
      if (hidden) hidden.value = category;
    }

    $all('[data-tab]', catalog).forEach(function (t) {
      t.addEventListener('click', function (e) { e.preventDefault(); category = t.dataset.tab; apply(true); });
    });
    input.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(function () { apply(false); }, 300); });
    $('[data-catalog-search]', catalog).addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(timer); apply(false); });
    window.addEventListener('popstate', function () {
      var p = new URLSearchParams(location.search);
      category = LABELS[p.get('category')] ? p.get('category') : 'all';
      input.value = p.get('q') || '';
      apply(false);
    });
  }

  /* ------------------------------------------------------------------
     Checkout page: contact form → saved order, or straight to WhatsApp
     ------------------------------------------------------------------ */
  var checkoutEl = $('[data-checkout-page]');
  if (checkoutEl) {
    var cform = $('[data-checkout-form]', checkoutEl);
    var finished = false; // true once an order was placed (cart is then empty on purpose)

    onCartRendered = function () { renderCheckout(); };

    function renderCheckout() {
      var empty = cart.length === 0 && !finished;
      $('[data-checkout-empty]', checkoutEl).hidden = !empty;
      $('[data-checkout-main]', checkoutEl).hidden = empty || finished;
      $('[data-checkout-done]', checkoutEl).hidden = !finished;
      $('[data-checkout-items]', checkoutEl).innerHTML = cart.map(function (i) {
        var n = esc(i.name);
        return '<li class="checkout-item"><img src="' + esc(i.image) + '" alt="" class="checkout-item__img">' +
          '<div class="checkout-item__info"><p class="checkout-item__name">' + n + '</p>' + (i.description ? '<p class="checkout-item__desc">' + esc(i.description) + '</p>' : '') + '<p class="checkout-item__price">' + money(i.price) + '</p>' +
          '<div class="qty" role="group" aria-label="Quantity for ' + n + '">' +
            '<button type="button" class="qty__btn" data-qty="' + i.productId + '" data-step="-1"' + (i.quantity <= 1 ? ' disabled' : '') + ' aria-label="Decrease quantity of ' + n + '">' + ICON.minus + '</button>' +
            '<span class="qty__value" aria-live="polite">' + i.quantity + '</span>' +
            '<button type="button" class="qty__btn" data-qty="' + i.productId + '" data-step="1"' + (i.quantity >= i.maxQuantity ? ' disabled' : '') + ' aria-label="Increase quantity of ' + n + '">' + ICON.plus + '</button></div></div>' +
          '<div class="checkout-item__end"><button type="button" class="icon-btn" data-remove="' + i.productId + '" aria-label="Remove ' + n + '">' + ICON.trash + '</button><strong>' + money(i.price * i.quantity) + '</strong></div></li>';
      }).join('');
      $('[data-checkout-total]', checkoutEl).textContent = money(cart.reduce(function (s, i) { return s + i.price * i.quantity; }, 0));
    }

    // "Same as phone" keeps the WhatsApp field in step until the customer types their own.
    var same = $('#co-same'), phoneEl = cform.elements.phone, waEl = cform.elements.whatsapp;
    function syncWa() { if (same.checked) waEl.value = phoneEl.value; waEl.readOnly = same.checked; }
    phoneEl.addEventListener('input', syncWa);
    same.addEventListener('change', syncWa);

    function clearErrors() {
      var box = $('[data-checkout-error]', checkoutEl);
      box.hidden = true;
      $all('[data-error-for]', cform).forEach(function (el) { el.textContent = ''; });
      $all('.is-invalid', cform).forEach(function (el) { el.classList.remove('is-invalid'); });
    }

    function showFailure(err) {
      var box = $('[data-checkout-error]', checkoutEl);
      box.textContent = err.message || 'We couldn’t place your order. Please try again.';
      box.hidden = false;
      Object.keys(err.errors || {}).forEach(function (field) {
        var input = cform.elements[field];
        if (input && input.classList) input.classList.add('is-invalid');
        var slot = $('[data-error-for="' + field + '"]', cform);
        if (slot) slot.textContent = err.errors[field][0];
      });
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function send(channel, btn) {
      if (!cart.length) return;
      clearErrors();
      syncWa();
      var payload = {
        channel: channel, name: cform.elements.name.value, phone: phoneEl.value, whatsapp: waEl.value,
        address: cform.elements.address.value, notes: cform.elements.notes.value, website: cform.elements.website.value,
        items: cart.map(function (i) { return { id: i.productId, quantity: i.quantity }; })
      };
      // Open the WhatsApp tab right now, inside the click, so pop-up blockers allow it; we point it at the chat once the order is saved.
      var tab = null;
      if (channel === 'whatsapp') { try { tab = window.open('', '_blank'); } catch (e) { tab = null; } }
      setLoading(btn, true);

      fetch(BASE + '/api/orders', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(payload) })
        .then(function (res) {
          return res.json().catch(function () { return { success: false, message: 'Unexpected server response.' }; }).then(function (json) {
            if (!res.ok || !json.success) { var err = new Error(json.message || 'Request failed.'); err.errors = json.errors || {}; throw err; }
            return json.data;
          });
        })
        .then(function (order) {
          var orders = read(ORDERS_KEY, []);
          orders.unshift({ id: order.id, orderNumber: order.orderNumber, createdAt: new Date().toISOString(), status: 'processing',
            items: order.items.map(function (i) { return { name: i.name, quantity: i.quantity }; }), total: order.total });
          write(ORDERS_KEY, orders);

          finished = true;
          cart = [];
          saveCart();

          $('[data-done-number]', checkoutEl).textContent = order.orderNumber;
          var text = $('[data-done-text]', checkoutEl), waLink = $('[data-done-wa]', checkoutEl);
          waLink.hidden = !order.whatsappUrl;
          if (order.whatsappUrl) waLink.href = order.whatsappUrl;
          text.textContent = channel === 'whatsapp'
            ? (order.whatsappUrl ? 'We opened WhatsApp with your order ready to send. If it didn’t open, use the button below.' : 'We’ve saved your order and will be in touch shortly.')
            : 'We’ll contact you shortly to confirm delivery' + (order.whatsappUrl ? '. You can also send your order details to us on WhatsApp.' : '.');
          renderCheckout();
          window.scrollTo({ top: 0, behavior: 'smooth' });

          if (channel === 'whatsapp' && order.whatsappUrl) { if (tab) tab.location = order.whatsappUrl; else location.href = order.whatsappUrl; }
          else if (tab) tab.close();
        })
        .catch(function (err) { if (tab) tab.close(); showFailure(err); })
        .then(function () { setLoading(btn, false); });
    }

    cform.addEventListener('submit', function (e) { e.preventDefault(); send('form', $('[data-place]', cform)); });
    var waBtn = $('[data-whatsapp]', cform);
    if (waBtn) waBtn.addEventListener('click', function () { send('whatsapp', waBtn); });

    syncWa();
    renderCheckout();
  }
})();
