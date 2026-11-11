<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf */
$title = 'Navigation';
$active = 'navigation';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-nav>
      <div class="adm-pagehead">
        <div><h1>Navigation</h1><p>Choose which links appear in your store’s menu bar and footer, and where they go.</p></div>
        <div class="adm-pagehead__actions"><button class="btn adm-btn adm-btn--primary" data-save-nav>Save menu</button></div>
      </div>

      <div class="adm-pills mb-3" role="tablist" aria-label="Menus">
        <button type="button" class="adm-pill is-active" role="tab" aria-selected="true" data-menu="header">Header menu</button>
        <button type="button" class="adm-pill" role="tab" aria-selected="false" data-menu="footer">Footer menu</button>
      </div>
      <p class="small text-secondary" data-menu-hint></p>

      <div class="alert adm-alert adm-alert--error" data-nav-error hidden></div>
      <div data-nav-list aria-live="polite"></div>
      <button class="btn adm-btn adm-btn--ghost mt-3" data-add-item><?= adm_icon('plus', 16) ?> Add link</button>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>
<?php $scripts = ['nav.js']; require __DIR__ . '/_foot.php'; ?>
