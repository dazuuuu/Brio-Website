<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf */
$title = 'Pages';
$active = 'pages';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-pages-list>
      <div class="adm-pagehead">
        <div><h1>Pages</h1><p>Every page on your store. Edit or delete any of them, including the built-in ones.</p></div>
        <div class="adm-pagehead__actions">
          <a class="btn adm-btn adm-btn--primary" href="<?= admin_url('pages/new') ?>"><?= adm_icon('plus', 16) ?> New Page</a>
        </div>
      </div>

      <div class="adm-card adm-table-card">
        <div class="table-responsive">
          <table class="table adm-table align-middle mb-0">
            <thead><tr><th scope="col">Page</th><th scope="col">URL</th><th scope="col">Blocks</th><th scope="col">Updated</th><th scope="col">Published</th><th scope="col" class="text-end">Actions</th></tr></thead>
            <tbody data-pages-body><tr><td class="adm-empty" colspan="6">Loading pages…</td></tr></tbody>
          </table>
        </div>
      </div>

      <div data-removed-wrap hidden>
        <h2 class="adm-section-title mt-4">Deleted built-in pages</h2>
        <p class="small text-secondary">These pages are switched off, so visitors see “Not found”. Restore one to bring it back with its original content.</p>
        <div class="adm-card adm-table-card"><div class="table-responsive"><table class="table adm-table align-middle mb-0"><tbody data-removed-body></tbody></table></div></div>
      </div>

      <p class="small text-secondary mt-4 mb-0" data-product-pages></p>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>
<?php $scripts = ['pages.js']; require __DIR__ . '/_foot.php'; ?>
