<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf @var int $pageId 0 = new page */
$title = $pageId ? 'Edit page' : 'New page';
$active = 'pages';
require __DIR__ . '/_shell_top.php';
$blockTypes = [['heading', 'Heading', 'edit'], ['text', 'Text', 'list'], ['image', 'Image', 'image'], ['image_text', 'Image + text', 'file'],
               ['button', 'Button', 'plus'], ['products', 'Products', 'cart'], ['categories', 'Categories', 'tag'], ['divider', 'Divider', 'sort']];
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-page-editor data-page-id="<?= (int)$pageId ?>">
      <div class="adm-pagehead">
        <div>
          <a class="adm-back" href="<?= admin_url('pages') ?>"><?= adm_icon('chev-l', 14) ?> All pages</a>
          <h1 data-editor-heading><?= $pageId ? 'Edit page' : 'New page' ?></h1>
        </div>
        <div class="adm-pagehead__actions">
          <a class="btn adm-btn adm-btn--ghost" data-preview-link target="_blank" rel="noopener" hidden><?= adm_icon('eye', 16) ?> View page</a>
          <button class="btn adm-btn adm-btn--ghost text-danger" data-delete-page hidden><?= adm_icon('trash', 16) ?> Delete</button>
          <button class="btn adm-btn adm-btn--primary" data-save-page>Save page</button>
        </div>
      </div>

      <div class="alert adm-alert adm-alert--error" data-page-error hidden></div>
      <div class="adm-note" data-system-note hidden><strong>Built-in page</strong>This page is part of your store. You can change what’s on it, reorder or remove its sections, and unpublish or delete it. Its web address stays the same. If you delete it, you can restore it from the Pages list.</div>

      <div class="row g-4 align-items-start">
        <div class="col-lg-8">
          <div class="adm-card p-3 mb-3">
            <label class="form-label adm-label" for="pg-title">Page title *</label>
            <input id="pg-title" class="form-control adm-input adm-input--lg" maxlength="160" placeholder="e.g. About us" data-title>
            <div class="invalid-feedback" data-error-for="title"></div>
          </div>

          <div data-blocks aria-live="polite"></div>

          <div class="adm-card p-3 mt-3">
            <p class="adm-label mb-2">Add a block</p>
            <div class="adm-palette">
              <?php foreach ($blockTypes as [$type, $label, $icon]): ?>
                <button type="button" class="adm-palette__btn" data-add-block="<?= $type ?>"><?= adm_icon($icon, 18) ?><span><?= h($label) ?></span></button>
              <?php endforeach; ?>
            </div>
            <p class="small text-secondary mt-2 mb-0"><b>Products</b> and <b>Categories</b> are dynamic: they always show your latest catalogue.</p>
            <p class="adm-label mt-3 mb-2">Store sections</p>
            <div class="adm-palette">
              <?php foreach ([['hero', 'Hero slider', 'image'], ['deals', 'Daily deals', 'tag'], ['catalog', 'Shop & filters', 'cart'], ['checkout', 'Checkout form', 'bag'],
                              ['login', 'Sign-in form', 'edit'], ['register', 'Register form', 'plus'], ['account', 'My account', 'file']] as [$name, $label, $icon]): ?>
                <button type="button" class="adm-palette__btn" data-add-block="section" data-section="<?= $name ?>"><?= adm_icon($icon, 18) ?><span><?= h($label) ?></span></button>
              <?php endforeach; ?>
            </div>
            <p class="small text-secondary mt-2 mb-0">Parts of the store that work on their own. Each can be on a page once.</p>
          </div>
        </div>

        <aside class="col-lg-4">
          <div class="adm-card p-3 adm-sticky">
            <h2 class="adm-section-title">Page settings</h2>
            <div class="mb-3">
              <label class="form-label adm-label" for="pg-slug">Page URL</label>
              <div class="adm-slug"><span data-slug-prefix></span><input id="pg-slug" class="form-control adm-input" placeholder="about-us" data-slug maxlength="120"></div>
              <div class="invalid-feedback d-block" data-error-for="slug"></div>
              <div class="form-text">Letters, numbers and dashes. Use <code>/</code> for sub-pages, e.g. <code>help/delivery</code>.</div>
            </div>
            <div class="form-check form-switch adm-switch mb-3">
              <input class="form-check-input" type="checkbox" role="switch" id="pg-published" data-published checked>
              <label class="form-check-label" for="pg-published">Published <small class="d-block text-secondary">Drafts are hidden from visitors.</small></label>
            </div>
            <div>
              <label class="form-label adm-label" for="pg-meta">Search description</label>
              <textarea id="pg-meta" rows="3" class="form-control adm-input" maxlength="300" placeholder="A short summary for Google" data-meta></textarea>
              <div class="invalid-feedback d-block" data-error-for="metaDescription"></div>
            </div>
          </div>
        </aside>
      </div>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>
<?php $scripts = ['pages.js']; require __DIR__ . '/_foot.php'; ?>
