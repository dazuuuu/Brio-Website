<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf @var array{products:int,orders:int} $counts */
$title = 'Reset store';
$active = 'reset';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-reset>
      <div class="adm-pagehead">
        <div><h1>Reset store</h1><p>Start fresh by clearing your products and orders. Your admin account stays.</p></div>
      </div>

      <div class="row g-4 align-items-start">
        <div class="col-lg-6">
          <div class="adm-card p-4 mb-3">
            <h2 class="adm-section-title text-danger">This will permanently delete</h2>
            <ul class="adm-resetlist adm-resetlist--delete">
              <li><b data-count="products"><?= (int)$counts['products'] ?></b> products, including their uploaded photos, Daily Deals and Featured selections, and the sales and revenue figures that come from them</li>
              <li><b data-count="orders"><?= (int)$counts['orders'] ?></b> orders</li>
            </ul>
            <h2 class="adm-section-title mt-4">This stays exactly as it is</h2>
            <ul class="adm-resetlist adm-resetlist--keep">
              <li>Your admin account and password</li>
              <li>Your pages, including the built-in ones</li>
              <li>Hero slides and their images</li>
              <li>Header and footer menus</li>
              <li>Settings, including your WhatsApp number</li>
            </ul>
            <p class="small text-secondary mb-0">Revenue isn’t stored anywhere on its own. It’s worked out from each product’s price and units sold, so it returns to zero when the products are cleared. Order numbers start again at BRIO-5001.</p>
          </div>
        </div>

        <div class="col-lg-6">
          <form class="adm-card p-4 adm-danger-card" data-reset-form novalidate>
            <h2 class="adm-section-title">Confirm with your password</h2>
            <div class="alert adm-alert adm-alert--error" role="alert" data-form-error hidden></div>
            <div class="mb-3">
              <label class="form-label adm-label" for="rs-password">Admin password</label>
              <input id="rs-password" name="password" type="password" class="form-control adm-input" autocomplete="current-password" required>
              <div class="invalid-feedback d-block" data-error-for="password"></div>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="rs-confirm" name="confirm">
              <label class="form-check-label" for="rs-confirm">I understand this deletes the products and orders above and can’t be undone.</label>
              <div class="invalid-feedback d-block" data-error-for="confirm"></div>
            </div>
            <button type="submit" class="btn adm-btn adm-btn--danger" data-reset-btn>Reset store</button>
          </form>

          <div class="alert adm-alert adm-alert--ok mt-3" role="status" data-reset-done hidden></div>
        </div>
      </div>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>
<?php $scripts = ['reset.js']; require __DIR__ . '/_foot.php'; ?>
