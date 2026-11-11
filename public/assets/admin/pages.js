/* Admin → Pages (list) and the block-based page builder (editor) */
(function () {
  'use strict';
  var A = window.BrioAdmin;
  if (!A) return;
  var $ = A.$, esc = A.esc;

  /* =============================================================
     Page list
     ============================================================= */
  if ($('[data-pages-list]')) {
    var body = $('[data-pages-body]');
    var pages = [];

    var render = function () {
      if (!pages.length) {
        body.innerHTML = '<tr><td class="adm-empty" colspan="6"><p class="mb-2"><b>No pages.</b></p><a class="btn adm-btn adm-btn--primary" href="' + A.url('admin/pages/new') + '">Create a page</a></td></tr>';
        return;
      }
      body.innerHTML = pages.map(function (p) {
        var view = A.url(p.slug) + (p.isPublished ? '' : '?preview=1');
        var edit = A.url('admin/pages/' + p.id);
        return '<tr>' +
          '<td><a class="fw-semibold" href="' + edit + '">' + esc(p.title) + '</a>' + (p.systemKey ? ' <span class="adm-status adm-status--low">Built-in</span>' : '') + '</td>' +
          '<td><a class="text-secondary" href="' + esc(view) + '" target="_blank" rel="noopener" title="Open the live page">/' + esc(p.slug) + '</a></td>' +
          '<td>' + p.blockCount + '</td>' +
          '<td class="text-secondary">' + esc(A.date(p.updatedAt)) + '</td>' +
          '<td><div class="form-check form-switch adm-switch mb-0"><input class="form-check-input" type="checkbox" role="switch" data-publish="' + p.id + '"' + (p.isPublished ? ' checked' : '') + ' aria-label="Published: ' + esc(p.title) + '"></div></td>' +
          '<td class="text-end text-nowrap"><a class="btn adm-btn adm-btn--ghost adm-btn--sm" href="' + edit + '">Edit</a> ' +
          '<button type="button" class="btn adm-btn adm-btn--ghost adm-btn--sm text-danger" data-delete="' + p.id + '">Delete</button></td></tr>';
      }).join('');
    };

    var renderRemoved = function (removed) {
      $('[data-removed-wrap]').hidden = !removed.length;
      $('[data-removed-body]').innerHTML = removed.map(function (r) {
        return '<tr><td><span class="fw-semibold">' + esc(r.title) + '</span> <span class="adm-status adm-status--out">Deleted</span><span class="adm-prod__cat">' + esc(r.description) + '</span></td>' +
          '<td class="text-secondary">' + esc(r.path) + '</td><td class="text-end"><button type="button" class="btn adm-btn adm-btn--primary adm-btn--sm" data-restore="' + esc(r.key) + '">Restore</button></td></tr>';
      }).join('');
    };

    var load = function () {
      return A.request('/pages').then(function (json) {
        pages = json.data;
        render();
        renderRemoved(json.removed || []);
        var pp = json.productPages;
        $('[data-product-pages]').innerHTML = pp ? '<b>' + esc(pp.title) + '</b> (' + esc(pp.path) + '): ' + esc(pp.description) + ' <a href="' + A.url('admin/products') + '">Go to Products</a>' : '';
      }).catch(function (e) { body.innerHTML = '<tr><td class="adm-empty" colspan="6">' + esc(e.message) + '</td></tr>'; });
    };

    document.addEventListener('click', function (e) {
      var del = e.target.closest('[data-delete]');
      if (del) {
        var page = pages.find(function (p) { return p.id === +del.dataset.delete; });
        var text = page && page.systemKey
          ? '“' + page.title + '” is a built-in page. Deleting it removes it from your store, so visitors will see “Not found”. You can restore it from this list at any time.'
          : '“' + (page ? page.title : 'This page') + '” will be deleted and its URL will stop working.';
        A.confirm(text, { title: 'Delete page?' }).then(function (ok) {
          if (!ok) return;
          A.request('/pages/' + del.dataset.delete, { method: 'DELETE' }).then(function () { A.toast('Page deleted.'); return load(); })
            .catch(function (err) { A.toast(err.message, true); });
        });
        return;
      }
      var res = e.target.closest('[data-restore]');
      if (res) {
        res.disabled = true;
        A.request('/pages/restore', { method: 'POST', body: { key: res.dataset.restore } }).then(function () { A.toast('Page restored.'); return load(); })
          .catch(function (err) { res.disabled = false; A.toast(err.message, true); });
      }
    });
    document.addEventListener('change', function (e) {
      var t = e.target;
      if (!t.matches('[data-publish]')) return;
      t.disabled = true;
      A.request('/pages/' + t.dataset.publish, { method: 'PATCH', body: { isPublished: t.checked } })
        .then(function () { A.toast(t.checked ? 'Page published.' : 'Page is now a draft.'); return load(); })
        .catch(function (err) { t.checked = !t.checked; t.disabled = false; A.toast(err.message, true); });
    });
    load();
    return;
  }

  /* =============================================================
     Page editor
     ============================================================= */
  var editor = $('[data-page-editor]');
  if (!editor) return;

  var pageId = +editor.dataset.pageId || 0;
  var page = { id: pageId || null, title: '', slug: '', metaDescription: '', isPublished: true, blocks: [] };
  var slugTouched = !!pageId;
  var blocksEl = $('[data-blocks]');

  var LABELS = { heading: 'Heading', text: 'Text', image: 'Image', image_text: 'Image + text', button: 'Button', products: 'Products (dynamic)', categories: 'Categories (dynamic)', divider: 'Divider', section: 'Store section' };
  var SECTIONS = {
    hero: ['Hero slider', 'Your hero slides. Manage the slides in Hero Slides.'],
    deals: ['Daily deals', 'Your daily-deal offers. Choose which products are deals in Daily Deals.'],
    catalog: ['Shop & filters', 'The product list with search and category filters.'],
    checkout: ['Checkout form', 'Customer details, Place order and WhatsApp ordering.'],
    login: ['Sign-in form', 'The customer sign-in form.'],
    register: ['Register form', 'The customer sign-up form.'],
    account: ['My account', 'Customer profile, addresses and order history.']
  };
  var DEFAULTS = {
    heading: { text: '', level: 'h2', align: 'left' },
    text: { text: '', align: 'left' },
    image: { src: '', alt: '', caption: '', width: 'wide' },
    image_text: { src: '', alt: '', title: '', text: '', side: 'left', ctaLabel: '', link: { type: 'none', target: '' } },
    button: { label: '', link: { type: 'shop', target: '' }, align: 'left', style: 'primary' },
    products: { title: 'Featured products', subtitle: '', source: 'featured', category: '', ids: [], limit: 8 },
    categories: { title: 'Shop by category' },
    divider: {},
    section: { name: 'hero' }
  };

  function uid() { return Math.random().toString(36).slice(2, 10); }
  function slugify(s) {
    return String(s).toLowerCase().trim().replace(/[^a-z0-9/]+/g, '-').replace(/\/+/g, '/').split('/')
      .map(function (x) { return x.replace(/^-+|-+$/g, ''); }).filter(Boolean).join('/');
  }

  /* ---------- Field builders ---------- */
  function field(label, html, cls, hint) {
    return '<div class="' + (cls || 'col-12') + '"><label class="form-label adm-label">' + label + '</label>' + html + (hint ? '<div class="form-text">' + hint + '</div>' : '') + '</div>';
  }
  function input(f, v, attrs) { return '<input class="form-control adm-input" data-f="' + f + '" value="' + esc(v) + '" ' + (attrs || '') + '>'; }
  function area(f, v, rows) { return '<textarea class="form-control adm-input" data-f="' + f + '" rows="' + (rows || 4) + '">' + esc(v) + '</textarea>'; }
  function select(f, v, opts) {
    return '<select class="form-select adm-input" data-f="' + f + '">' + opts.map(function (o) { return '<option value="' + o[0] + '"' + (o[0] === v ? ' selected' : '') + '>' + o[1] + '</option>'; }).join('') + '</select>';
  }
  var ALIGN = [['left', 'Left'], ['center', 'Center'], ['right', 'Right']];

  function imagePicker(b) {
    return '<div class="adm-imgpick"><img data-preview alt=""' + (b.src ? ' src="' + esc(A.url(b.src)) + '"' : ' hidden') + '>' +
      '<div><label class="btn adm-btn adm-btn--ghost adm-btn--sm mb-0">' + (b.src ? 'Replace image' : 'Upload image') +
      '<input type="file" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-upload></label>' +
      '<div class="small text-secondary mt-1" data-upload-note>JPG, PNG or WebP · max 4 MB</div></div></div>';
  }

  function blockBody(b) {
    switch (b.type) {
      case 'heading':
        return field('Heading text', input('text', b.text, 'maxlength="200" placeholder="Our story"')) +
          field('Size', select('level', b.level, [['h2', 'Large'], ['h3', 'Smaller']]), 'col-sm-6') + field('Alignment', select('align', b.align, ALIGN), 'col-sm-6');
      case 'text':
        return field('Text', area('text', b.text, 7), 'col-12', 'Leave a blank line between paragraphs. <code>**bold**</code> · <code>*italic*</code> · <code>[link text](https://…)</code> · start lines with <code>- </code> for a bullet list.') +
          field('Alignment', select('align', b.align, ALIGN), 'col-sm-4');
      case 'image':
        return field('Image', imagePicker(b)) + field('Image description', input('alt', b.alt, 'maxlength="160"'), 'col-sm-6', 'Helps screen readers and Google.') +
          field('Caption (optional)', input('caption', b.caption, 'maxlength="200"'), 'col-sm-6') +
          field('Width', select('width', b.width, [['narrow', 'Narrow'], ['wide', 'Wide'], ['full', 'Full width']]), 'col-sm-4');
      case 'image_text':
        return field('Image', imagePicker(b)) + field('Heading', input('title', b.title, 'maxlength="200"'), 'col-sm-8') +
          field('Image on', select('side', b.side, [['left', 'Left'], ['right', 'Right']]), 'col-sm-4') +
          field('Text', area('text', b.text, 5)) + field('Image description', input('alt', b.alt, 'maxlength="160"'), 'col-12') +
          field('Optional button links to', A.linkField(b.link, ['none', 'shop', 'category', 'product', 'page', 'url']), 'col-md-7') +
          '<div class="col-md-5" data-cta-label' + (b.link.type === 'none' ? ' hidden' : '') + '><label class="form-label adm-label">Button text</label>' + input('ctaLabel', b.ctaLabel, 'maxlength="60" placeholder="Learn more"') + '</div>';
      case 'button':
        return field('Button text', input('label', b.label, 'maxlength="60" placeholder="Shop now"'), 'col-sm-6') +
          field('Links to', A.linkField(b.link, ['shop', 'home', 'category', 'product', 'page', 'url']), 'col-sm-6') +
          field('Style', select('style', b.style, [['primary', 'Filled'], ['outline', 'Outline']]), 'col-sm-6') + field('Alignment', select('align', b.align, ALIGN), 'col-sm-6');
      case 'products': {
        var o = A.options || { categories: [], products: [] };
        var out = field('Section title', input('title', b.title, 'maxlength="100"'), 'col-sm-6') + field('Subtitle (optional)', input('subtitle', b.subtitle, 'maxlength="200"'), 'col-sm-6') +
          field('Show', select('source', b.source, [['featured', 'Featured products'], ['deals', 'Daily deals'], ['latest', 'Latest products'], ['category', 'Products from a category'], ['selected', 'Products I pick']]), 'col-sm-6') +
          field('How many', input('limit', b.limit, 'type="number" min="1" max="24"'), 'col-sm-6');
        if (b.source === 'category') {
          out += field('Category', select('category', b.category, [['', 'Choose a category…']].concat(o.categories.map(function (c) { return [c.slug, c.label]; }))));
        }
        if (b.source === 'selected') {
          out += field('Choose products', '<div class="adm-checklist">' + (o.products.length ? o.products.map(function (p) {
            return '<label class="form-check"><input class="form-check-input" type="checkbox" data-id-cb value="' + p.id + '"' + (b.ids.indexOf(p.id) !== -1 ? ' checked' : '') + '> <span class="form-check-label">' + esc(p.name) + '</span></label>';
          }).join('') : '<span class="text-secondary">No products yet.</span>') + '</div>');
        }
        return out + '<div class="col-12"><p class="small text-secondary mb-0">Always shows your current catalogue, so it updates by itself when products change.</p></div>';
      }
      case 'section': {
        var meta = SECTIONS[b.name] || ['Section', ''];
        var out2 = '<div class="col-12"><p class="mb-0"><b>' + esc(meta[0]) + '</b><br><span class="small text-secondary">' + esc(meta[1]) + ' It works on its own, so there is nothing to set up here.</span></p></div>';
        if (b.name === 'deals') out2 = field('Section title', input('title', b.title || '', 'maxlength="100" placeholder="Daily Deals"'), 'col-sm-6') + field('Subtitle', input('subtitle', b.subtitle || '', 'maxlength="200"'), 'col-sm-6') + out2;
        return out2;
      }
      case 'categories':
        return field('Section title', input('title', b.title, 'maxlength="100"')) + '<div class="col-12"><p class="small text-secondary mb-0">Shows a tile for every product category, linking to that category.</p></div>';
      default:
        return '<div class="col-12"><p class="small text-secondary mb-0">A thin line that separates sections.</p></div>';
    }
  }

  function renderBlocks() {
    if (!page.blocks.length) {
      blocksEl.innerHTML = '<div class="adm-card adm-empty">This page is empty. Add your first block below.</div>';
      return;
    }
    blocksEl.innerHTML = page.blocks.map(function (b, i) {
      return '<section class="adm-card adm-block" data-i="' + i + '"><header class="adm-block__head"><span class="adm-block__type">' + esc(b.type === 'section' ? (SECTIONS[b.name] || [LABELS.section])[0] : LABELS[b.type]) + '</span>' +
        '<div class="adm-block__tools">' +
        '<button type="button" class="adm-icon-btn" data-act="up" aria-label="Move block up"' + (i === 0 ? ' disabled' : '') + '>▲</button>' +
        '<button type="button" class="adm-icon-btn" data-act="down" aria-label="Move block down"' + (i === page.blocks.length - 1 ? ' disabled' : '') + '>▼</button>' +
        '<button type="button" class="adm-icon-btn text-danger" data-act="del" aria-label="Remove block">✕</button></div></header>' +
        '<div class="adm-block__body row g-3">' + blockBody(b) + '</div></section>';
    }).join('');
  }

  /* ---------- Editing ---------- */
  blocksEl.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-act]');
    if (!btn) return;
    var i = +btn.closest('[data-i]').dataset.i;
    if (btn.dataset.act === 'del') {
      A.confirm('This block will be removed from the page.', { title: 'Remove block?', ok: 'Remove' }).then(function (ok) {
        if (ok) { page.blocks.splice(i, 1); renderBlocks(); }
      });
      return;
    }
    var j = i + (btn.dataset.act === 'up' ? -1 : 1);
    if (j < 0 || j >= page.blocks.length) return;
    page.blocks.splice(j, 0, page.blocks.splice(i, 1)[0]);
    renderBlocks();
  });

  function onField(e) {
    var el = e.target, card = el.closest('[data-i]');
    if (!card) return;
    var b = page.blocks[+card.dataset.i];

    if (el.matches('[data-upload]')) {
      var file = el.files[0];
      if (!file || e.type !== 'change') return;
      var note = $('[data-upload-note]', card);
      note.textContent = 'Uploading…';
      A.upload(file, 'pages').then(function (path) {
        b.src = path;
        var img = $('[data-preview]', card);
        img.src = A.url(path);
        img.hidden = false;
        note.textContent = file.name;
      }).catch(function (err) { note.textContent = err.message; A.toast(err.message, true); });
    } else if (el.matches('[data-id-cb]')) {
      b.ids = A.$all('[data-id-cb]:checked', card).map(function (c) { return +c.value; });
    } else if (el.closest('[data-link-field]')) {
      if (e.type !== 'change') return;
      b.link = A.readLink(el.closest('[data-link-field]'));
      var wrap = $('[data-cta-label]', card);
      if (wrap) wrap.hidden = b.link.type === 'none';
    } else if (el.matches('[data-f]')) {
      var f = el.dataset.f;
      b[f] = f === 'limit' ? (parseInt(el.value, 10) || 1) : el.value;
      if (f === 'source' && e.type === 'change') renderBlocks();
    }
  }
  blocksEl.addEventListener('input', onField);
  blocksEl.addEventListener('change', onField);

  document.addEventListener('click', function (e) {
    var add = e.target.closest('[data-add-block]');
    if (!add) return;
    var block = JSON.parse(JSON.stringify(DEFAULTS[add.dataset.addBlock]));
    block.id = uid();
    block.type = add.dataset.addBlock;
    if (block.type === 'section') {
      block.name = add.dataset.section;
      if (page.blocks.some(function (x) { return x.type === 'section' && x.name === block.name; })) { A.toast('That section is already on this page.', true); return; }
      if (block.name === 'deals') { block.title = 'Daily Deals'; block.subtitle = 'Special limited-time offers on our signature organic essentials'; }
    }
    page.blocks.push(block);
    renderBlocks();
    var last = blocksEl.lastElementChild;
    if (last) { last.scrollIntoView({ behavior: 'smooth', block: 'center' }); var first = $('input:not([type=file]), textarea, select', last); if (first) first.focus({ preventScroll: true }); }
  });

  /* ---------- Page settings ---------- */
  var titleEl = $('[data-title]'), slugEl = $('[data-slug]'), pubEl = $('[data-published]'), metaEl = $('[data-meta]');
  $('[data-slug-prefix]').textContent = A.base + '/';

  titleEl.addEventListener('input', function () {
    if (!slugTouched) slugEl.value = slugify(titleEl.value);
  });
  slugEl.addEventListener('input', function () { slugTouched = true; });
  slugEl.addEventListener('blur', function () { slugEl.value = slugify(slugEl.value); });

  function fill() {
    titleEl.value = page.title;
    slugEl.value = page.slug === '' ? '(home page)' : page.slug;
    pubEl.checked = page.isPublished;
    metaEl.value = page.metaDescription || '';
    $('[data-editor-heading]').textContent = page.id ? page.title || 'Edit page' : 'New page';
    var link = $('[data-preview-link]');
    link.hidden = !page.id;
    if (page.id) link.href = A.url(page.slug) + (page.isPublished ? '' : '?preview=1');
    $('[data-delete-page]').hidden = !page.id;
    slugEl.disabled = !!page.systemKey;
    $('[data-system-note]').hidden = !page.systemKey;
    $('[data-editor-heading]').textContent = (page.id ? page.title || 'Edit page' : 'New page') + (page.systemKey ? ' (built-in)' : '');
    renderBlocks();
  }

  function clearErrors() {
    $('[data-page-error]').hidden = true;
    A.$all('[data-error-for]').forEach(function (el) { el.textContent = ''; });
    A.$all('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
  }

  $('[data-save-page]').addEventListener('click', function () {
    clearErrors();
    var btn = this;
    var body = { title: titleEl.value.trim(), slug: page.systemKey ? page.slug : slugify(slugEl.value), metaDescription: metaEl.value.trim(), isPublished: pubEl.checked, blocks: page.blocks };
    A.busy(btn, true);
    A.request(page.id ? '/pages/' + page.id : '/pages', { method: page.id ? 'PATCH' : 'POST', body: body }).then(function (json) {
      if (!page.id) { location.href = A.url('admin/pages/' + json.data.id) + '?saved=1'; return; }
      page = json.data;
      fill();
      A.toast('Page saved.');
    }).catch(function (err) {
      var box = $('[data-page-error]');
      box.textContent = err.message;
      box.hidden = false;
      Object.keys(err.errors || {}).forEach(function (k) {
        var slot = $('[data-error-for="' + k + '"]');
        if (slot) slot.textContent = err.errors[k][0];
      });
      box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }).then(function () { A.busy(btn, false); });
  });

  $('[data-delete-page]').addEventListener('click', function () {
    A.confirm(page.systemKey ? '“' + page.title + '” is a built-in page. Deleting it removes it from your store. You can restore it later from the Pages list.' : '“' + page.title + '” will be deleted and its URL will stop working.', { title: 'Delete page?' }).then(function (ok) {
      if (!ok) return;
      A.request('/pages/' + page.id, { method: 'DELETE' }).then(function () { location.href = A.url('admin/pages'); })
        .catch(function (err) { A.toast(err.message, true); });
    });
  });

  /* ---------- Boot ---------- */
  A.loadOptions().catch(function () {}).then(function () {
    if (!pageId) { fill(); return; }
    return A.request('/pages/' + pageId).then(function (json) {
      page = json.data;
      fill();
      if (/[?&]saved=1/.test(location.search)) { A.toast('Page created.'); history.replaceState(null, '', location.pathname); }
    }).catch(function (err) { blocksEl.innerHTML = '<div class="adm-card adm-empty">' + esc(err.message) + '</div>'; });
  });
})();
