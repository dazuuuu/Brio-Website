/* Brio storefront behaviour (vanilla JS + Bootstrap 5).
   Ports CartContext, AuthContext, the catalog filters and the mock API from the React UI. */
(function () {
  'use strict';

  var BASE = (window.BRIO && window.BRIO.base) || '';
  var CART_KEY = 'brio.cart.v1';

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function url(path) { return BASE + '/' + String(path || '').replace(/^\//, ''); }
  function esc(s) { return String(s).replace(/[&<>"']/g, function (m) { return '&#' + m.charCodeAt(0) + ';'; }); }
  function money(n) { return 'KSh ' + Math.round(n).toLocaleString('en-KE'); }
  function formatDate(iso) { return new Date(iso).toLocaleDateString('en-KE', { year: 'numeric', month: 'short', day: 'numeric' }); }

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
     Customer accounts (real: kept on the server)
     ------------------------------------------------------------------ */
  function customerApi(path, method, body) {
    return fetch(BASE + '/api/customer/' + path, {
      method: method || 'GET', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: body ? JSON.stringify(body) : undefined
    }).then(function (res) {
      return res.json().catch(function () { return { success: false, message: 'Unexpected server response.' }; }).then(function (json) {
        if (!res.ok || !json.success) { var err = new Error(json.message || 'Something went wrong. Please try again.'); err.errors = json.errors || {}; err.status = res.status; throw err; }
        return json.data;
      });
    });
  }
  function safeRedirect(v) { return typeof v === 'string' && /^\/(?!\/)[^\\\s]*$/.test(v) ? v : null; }
  function authError(msg) { var el = $('[data-auth-error]'); if (el) { el.textContent = msg || ''; el.hidden = !msg; } }
  function showFieldErrors(form, err) {
    Object.keys(err.errors || {}).forEach(function (f) {
      var input = form.elements[f];
      if (input && input.classList) input.classList.add('is-invalid');
      var slot = $('[data-error-for="' + f + '"]', form);
      if (slot) slot.textContent = err.errors[f][0];
    });
  }
  function clearFieldErrors(form) {
    $all('[data-error-for]', form).forEach(function (el) { el.textContent = ''; });
    $all('.is-invalid', form).forEach(function (el) { el.classList.remove('is-invalid'); });
  }
  function afterSignIn(customer) {
    var r = safeRedirect(new URLSearchParams(location.search).get('redirect'));
    if (r) location.replace(BASE + r);
    else if (customer && customer.hasContactDetails === false) location.replace(url('account') + '#details'); // e.g. a new Google user
    else location.replace(url('account'));
  }

  // "Continue with Google": loaded only when the store has a Google Client ID
  var googleBox = $('[data-google-signin]');
  if (googleBox) {
    var gNote = $('[data-google-note]', googleBox);
    var gSay = function (msg) { gNote.textContent = msg; gNote.hidden = false; };
    var gScript = document.createElement('script');
    gScript.src = 'https://accounts.google.com/gsi/client';
    gScript.async = true;
    gScript.onerror = function () { gSay('Google sign-in is unavailable right now. You can use your email instead.'); };
    gScript.onload = function () {
      try {
        google.accounts.id.initialize({
          client_id: googleBox.dataset.clientId,
          callback: function (resp) {
            authError(null);
            customerApi('google', 'POST', { credential: resp.credential }).then(afterSignIn).catch(function (err) { authError(err.message); });
          }
        });
        google.accounts.id.renderButton($('[data-google-button]', googleBox), {
          type: 'standard', theme: document.documentElement.getAttribute('data-theme') === 'dark' ? 'filled_black' : 'outline',
          size: 'large', text: 'continue_with', shape: 'pill', width: Math.max(200, Math.min(300, googleBox.clientWidth || 220))
        });
      } catch (e) { gSay('Google sign-in is unavailable right now. You can use your email instead.'); }
    };
    document.head.appendChild(gScript);
  }

  var loginForm = $('[data-login-form]');
  if (loginForm) {
    loginForm.addEventListener('submit', function (e) {
      e.preventDefault();
      authError(null);
      var email = loginForm.email.value.trim(), password = loginForm.password.value;
      if (!email || !password) return authError('Please enter your email address and password.');
      var btn = $('[type=submit]', loginForm);
      setLoading(btn, true);
      customerApi('login', 'POST', { email: email, password: password }).then(afterSignIn)
        .catch(function (err) { authError(err.message); setLoading(btn, false); });
    });
  }

  var regForm = $('[data-register-form]');
  if (regForm) {
    regForm.addEventListener('submit', function (e) {
      e.preventDefault();
      authError(null);
      clearFieldErrors(regForm);
      var f = regForm.elements;
      var btn = $('[type=submit]', regForm);
      setLoading(btn, true);
      customerApi('register', 'POST', {
        email: f.email.value, password: f.password.value, confirm: f.confirm.value
      }).then(function (customer) {
        var r = safeRedirect(new URLSearchParams(location.search).get('redirect'));
        location.replace(r ? BASE + r : url('account') + '?verify=sent'); // the account page shows the "check your email" notice
      }).catch(function (err) {
        authError(err.message);
        showFieldErrors(regForm, err);
        setLoading(btn, false);
      });
    });
  }

  // "Resend email" on the account page
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-resend-verify]');
    if (!btn) return;
    var msg = $('[data-verify-msg]');
    setLoading(btn, true);
    customerApi('resend-verification', 'POST').then(function (r) {
      msg.textContent = r.alreadyVerified ? ' Your email is already confirmed.' : (r.sent ? ' Sent! Check your inbox.' : ' We couldn’t send the email right now. Please try again later.');
    }).catch(function (err) { msg.textContent = ' ' + err.message; }).then(function () { setLoading(btn, false); });
  });

  // Sign out (login/register "you're signed in" cards and the account page)
  document.addEventListener('click', function (e) {
    var out = e.target.closest('[data-customer-logout]');
    if (!out) return;
    out.disabled = true;
    customerApi('logout', 'POST').catch(function () {}).then(function () { location.href = url('login'); });
  });

  // Account page: contact details, password, order history
  var accountEl = $('[data-account]');
  if (accountEl) {
    var STEPS = ['Received', 'Being processed', 'Completed'];
    var STEP_OF = { 'new': 0, contacted: 1, completed: 2 };
    var BADGE = { 'new': 'processing', contacted: 'processing', completed: 'completed', cancelled: 'cancelled' };
    var ordersBox = $('[data-orders]', accountEl);

    function tracker(status) {
      if (status === 'cancelled') return '<p class="track-cancelled">This order was cancelled. Contact us if that’s a mistake.</p>';
      var cur = STEP_OF[status] != null ? STEP_OF[status] : 0;
      return '<ol class="track" aria-label="Order progress">' + STEPS.map(function (label, i) {
        var cls = (i < cur || status === 'completed') ? 'is-done' : (i === cur ? 'is-current' : '');
        return '<li class="' + cls + '"' + (i === cur ? ' aria-current="step"' : '') + '><span class="track__dot" aria-hidden="true"></span><span class="track__label">' + label + '</span></li>';
      }).join('') + '</ol>';
    }

    if (ordersBox) {
      customerApi('orders').then(function (orders) {
        ordersBox.innerHTML = orders.length ? orders.map(function (o) {
          var label = o.status === 'cancelled' ? 'Cancelled' : (STEPS[STEP_OF[o.status]] || 'Received');
          return '<article class="order-card"><header class="order-card__head"><div><strong>' + esc(o.orderNumber) + '</strong><small>' + esc(formatDate(o.createdAt.replace(' ', 'T'))) + '</small></div>' +
            '<div class="text-end"><strong>' + money(o.total) + '</strong><br><span class="status status--' + (BADGE[o.status] || 'processing') + '">' + esc(label) + '</span></div></header>' +
            tracker(o.status) +
            '<ul class="order-items">' + o.items.map(function (i) {
              return '<li>' + (i.image ? '<img src="' + esc(url(i.image)) + '" alt="" loading="lazy">' : '<span class="order-items__ph"></span>') + '<span>' + (+i.quantity) + ' × ' + esc(i.name) + '</span><b>' + money(i.price * i.quantity) + '</b></li>';
            }).join('') + '</ul></article>';
        }).join('') : '<div class="panel-brio text-center"><p class="mb-2"><b>No orders yet.</b></p><a class="btn btn-brio btn-brio--primary btn-brio--sm" href="' + url('products') + '">Start shopping</a></div>';
      }).catch(function () { ordersBox.innerHTML = '<div class="panel-brio text-center text-secondary">We couldn’t load your orders. Please refresh the page.</div>'; });
    }

    function bindAccountForm(form, run, okText) {
      if (!form) return;
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearFieldErrors(form);
        var err = $('[data-form-error]', form), ok = $('[data-form-ok]', form), btn = $('[type=submit]', form);
        err.hidden = true; ok.hidden = true;
        setLoading(btn, true);
        run(form.elements).then(function () { ok.textContent = okText; ok.hidden = false; })
          .catch(function (er) { err.textContent = er.message; err.hidden = false; showFieldErrors(form, er); })
          .then(function () { setLoading(btn, false); });
      });
    }
    bindAccountForm($('[data-details-form]', accountEl), function (f) {
      return customerApi('me', 'PATCH', { firstName: f.firstName.value, lastName: f.lastName.value, phone: f.phone.value, whatsapp: f.whatsapp.value, address: f.address.value });
    }, 'Saved. Checkout will use these details.');
    var pwForm = $('[data-password-form]', accountEl);
    bindAccountForm(pwForm, function (f) {
      return customerApi('password', 'POST', { current: f.current.value, 'new': f['new'].value, confirm: f.confirm.value }).then(function () { pwForm.reset(); });
    }, 'Password changed.');
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
     Light / dark theme: per visitor, for this browser session only
     ------------------------------------------------------------------ */
  (function () {
    var html = document.documentElement, meta = $('meta[name="theme-color"]');
    function paint() {
      var dark = html.getAttribute('data-theme') === 'dark';
      $all('[data-theme-toggle]').forEach(function (b) {
        b.setAttribute('aria-pressed', dark ? 'true' : 'false');
        b.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
        b.title = dark ? 'Light theme' : 'Dark theme';
      });
      if (meta) meta.setAttribute('content', dark ? '#0e1712' : '#00BE62');
    }
    document.addEventListener('click', function (e) {
      if (!e.target.closest('[data-theme-toggle]')) return;
      var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      html.setAttribute('data-bs-theme', next);
      try { sessionStorage.setItem('brio.theme', next); } catch (err) { /* storage blocked: still works until reload */ }
      paint();
    });
    paint();
  })();

  /* ------------------------------------------------------------------
     Pop-up ads: full-screen, closes with one tap, never on checkout/login/account
     ------------------------------------------------------------------ */
  (function () {
    var holder = $('[data-ads]');
    if (!holder) return;
    var ads;
    try { ads = JSON.parse(holder.dataset.ads); } catch (e) { return; }
    var now = Date.now();

    function key(ad) { return 'brio.ad.' + ad.id; }
    function seen(ad) {
      if (ad.force || ad.frequency === 'always') return false;
      try {
        if (ad.frequency === 'session') return sessionStorage.getItem(key(ad)) === '1';
        return now - (+localStorage.getItem(key(ad)) || 0) < 864e5; // once a day
      } catch (e) { return false; }
    }
    function remember(ad) {
      try { if (ad.frequency === 'session') sessionStorage.setItem(key(ad), '1'); else if (ad.frequency === 'day') localStorage.setItem(key(ad), String(now)); } catch (e) { /* storage blocked */ }
    }
    function track(ad, type) {
      if (ad.force) return; // previews from the admin aren't counted
      try { fetch(BASE + '/api/ads/event', { method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: ad.id, type: type }) }); } catch (e) { /* ignore */ }
    }

    var ad = ads.filter(function (a) { return !seen(a); })[0];
    if (!ad) return;

    function markup(ad) {
      var p = ad.product, m = ad.media;
      var layout = p ? 'split' : (m && (ad.headline || ad.body) ? 'split' : (m ? 'media' : 'text'));
      var pct = p && p.compareAt && p.compareAt > p.price ? Math.round((1 - p.price / p.compareAt) * 100) : 0;
      var badge = ad.offerText || (pct > 0 ? '-' + pct + '%' : '');
      var title = ad.headline || (p ? p.name : '');
      var media = '';
      if (p && p.image) media = '<img src="' + esc(p.image) + '" alt="' + esc(p.name) + '">';
      else if (m && m.type === 'video') media = '<video src="' + esc(m.src) + '" autoplay muted loop playsinline controls></video>';
      else if (m) media = '<img src="' + esc(m.src) + '" alt="' + esc(title) + '">';
      var cta = ad.cta ? '<a class="ad-cta" href="' + esc(ad.cta.href) + '" data-ad-cta>' + esc(ad.cta.label) + '</a>' : '';
      var close = '<button type="button" class="ad-close" data-ad-close aria-label="Close ad"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg></button>';

      var body = '<div class="ad-body">' +
        (p ? '<p class="ad-eyebrow">Special offer</p>' : '') +
        (title ? '<h2 class="ad-title" id="ad-title">' + esc(title) + '</h2>' : '') +
        (p && ad.headline ? '<p class="ad-text">' + esc(p.name) + '</p>' : '') +
        (ad.body ? '<p class="ad-text">' + esc(ad.body) + '</p>' : '') +
        (p ? '<div class="ad-price"><strong>' + money(p.price) + '</strong>' + (p.compareAt ? '<s>' + money(p.compareAt) + '</s>' : '') + '</div>' : '') +
        cta + '<button type="button" class="ad-skip" data-ad-close>No thanks</button></div>';

      if (layout === 'media') {
        return '<div class="ad-card ad-card--media">' + close + '<div class="ad-media">' + media + '</div>' + (cta ? '<div class="ad-cta-wrap">' + cta + '</div>' : '') + '</div>';
      }
      if (layout === 'text') return '<div class="ad-card ad-card--text">' + close + body + '</div>';
      return '<div class="ad-card ad-card--split">' + close + '<div class="ad-media">' + (badge ? '<span class="ad-badge">' + esc(badge) + '</span>' : '') + media + '</div>' + body + '</div>';
    }

    function open() {
      var before = document.activeElement;
      var overlay = document.createElement('div');
      overlay.className = 'ad-overlay';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.setAttribute('aria-label', ad.headline || (ad.product ? ad.product.name : 'Announcement'));
      overlay.innerHTML = markup(ad);
      document.body.appendChild(overlay);
      document.documentElement.classList.add('ad-lock');
      requestAnimationFrame(function () { overlay.classList.add('is-open'); var c = $('[data-ad-close]', overlay); if (c) c.focus(); });
      remember(ad);
      track(ad, 'view');

      function close() {
        document.removeEventListener('keydown', onKey);
        overlay.classList.remove('is-open');
        document.documentElement.classList.remove('ad-lock');
        var v = $('video', overlay); if (v) v.pause();
        setTimeout(function () { overlay.remove(); }, 260);
        if (before && before.focus) try { before.focus(); } catch (e) { /* element gone */ }
      }
      function onKey(e) {
        if (e.key === 'Escape') { close(); return; }
        if (e.key !== 'Tab') return;
        var f = $all('[data-ad-close], [data-ad-cta], video[controls]', overlay).filter(function (x) { return x.offsetParent !== null; });
        if (!f.length) return;
        var first = f[0], last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
      document.addEventListener('keydown', onKey);
      overlay.addEventListener('click', function (e) {
        if (e.target === overlay || e.target.closest('[data-ad-close]')) { close(); return; }
        if (e.target.closest('[data-ad-cta]')) track(ad, 'click');
      });
    }

    // Wait for the picture so the ad never pops up half-empty.
    var first = ad.product ? ad.product.image : (ad.media && ad.media.type === 'image' ? ad.media.src : '');
    var go = function () { setTimeout(open, Math.max(0, (ad.delay || 0) * 1000)); };
    if (first) { var img = new Image(); var done = false; var fire = function () { if (!done) { done = true; go(); } }; img.onload = fire; img.onerror = fire; img.src = first; setTimeout(fire, 2500); } else { go(); }
  })();

  /* ------------------------------------------------------------------
     Daily Deals carousel (tablet and phone): swipe, arrows, dots
     ------------------------------------------------------------------ */
  $all('[data-deals]').forEach(function (root) {
    var track = $('[data-deals-track]', root), dotsEl = $('[data-deals-dots]', root);
    var items = $all('.deals__item', track), prev = $('[data-deals-prev]', root), next = $('[data-deals-next]', root);
    if (!items.length) return;
    var ticking = false;

    function stepWidth() {
      var gap = parseFloat(getComputedStyle(track).columnGap);
      return items[0].getBoundingClientRect().width + (isNaN(gap) ? 16 : gap);
    }
    function current() { return Math.max(0, Math.min(items.length - 1, Math.round(track.scrollLeft / stepWidth()))); }
    function update() {
      var scrollable = track.scrollWidth > track.clientWidth + 2;
      root.classList.toggle('is-scrollable', scrollable);
      var i = current(), atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 2;
      $all('.deals__dot', dotsEl).forEach(function (d, n) { d.classList.toggle('is-active', n === i); });
      prev.disabled = track.scrollLeft <= 2;
      next.disabled = atEnd;
    }
    dotsEl.innerHTML = items.map(function (_, n) { return '<button type="button" class="deals__dot" tabindex="-1" data-i="' + n + '"></button>'; }).join('');
    dotsEl.addEventListener('click', function (e) {
      var d = e.target.closest('[data-i]');
      if (d) track.scrollTo({ left: +d.dataset.i * stepWidth(), behavior: 'smooth' });
    });
    prev.addEventListener('click', function () { track.scrollBy({ left: -stepWidth(), behavior: 'smooth' }); });
    next.addEventListener('click', function () { track.scrollBy({ left: stepWidth(), behavior: 'smooth' }); });
    track.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(function () { ticking = false; update(); }); } }, { passive: true });
    track.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') { e.preventDefault(); next.click(); } else if (e.key === 'ArrowLeft') { e.preventDefault(); prev.click(); }
    });
    window.addEventListener('resize', update);
    update();
  });

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
    var same = $('#co-same'), phoneEl = cform.elements.phone, waEl = cform.elements.whatsapp; // absent when a signed-in shopper orders directly
    var signedIn = cform.dataset.signedIn === '1';
    function syncWa() { if (!same || !phoneEl || !waEl) return; if (same.checked) waEl.value = phoneEl.value; waEl.readOnly = same.checked; }
    if (phoneEl) phoneEl.addEventListener('input', syncWa);
    if (same) same.addEventListener('change', syncWa);
    function val(name) { return cform.elements[name] ? cform.elements[name].value : ''; }

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
        channel: channel, name: val('name'), phone: val('phone'), whatsapp: val('whatsapp'),
        address: val('address'), notes: val('notes'), website: val('website'),
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
          finished = true;
          cart = [];
          saveCart();

          $('[data-done-number]', checkoutEl).textContent = order.orderNumber;
          var text = $('[data-done-text]', checkoutEl), waLink = $('[data-done-wa]', checkoutEl);
          waLink.hidden = !order.whatsappUrl;
          $('[data-done-account]', checkoutEl).hidden = !signedIn;
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
