<?php
/**
 * Shared admin layout (head + sidebar + top bar). Opens <div class="adm-shell"><div class="adm-main">.
 * Expects: $title, $active, $admin, $csrf; optional $showSearch (product search box in the top bar).
 */
$showSearch ??= false;
$groups = [
    ['Catalog', [['products', 'Product', 'box'], ['offers', 'Daily Deals', 'tag'], ['featured', 'Featured', 'star']]],
    ['Storefront', [['hero', 'Hero Slides', 'image'], ['pages', 'Pages', 'file'], ['navigation', 'Navigation', 'list']]],
    ['Sales', [['orders', 'Orders', 'cart']]],
    ['Tools', [['settings', 'Settings', 'cog'], ['reset', 'Reset store', 'trash']]],
];
$initials = strtoupper(implode('', array_map(fn($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', trim($admin->name)) ?: ['A'], 0, 2))));
require __DIR__ . '/_head.php';
?>
<body class="adm-body">
<div class="adm-shell">
  <aside class="adm-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adm-sidebar" aria-label="Admin navigation">
    <div class="adm-sidebar__inner">
      <a class="adm-brand" href="<?= admin_url('products') ?>">
        <span class="adm-logo"><?= adm_icon('bag', 20) ?></span>
        <span><strong>Brio</strong><small>Sell Smart, Grow Bigger.</small></span>
      </a>

      <label class="adm-menu-search">
        <?= adm_icon('search', 16) ?>
        <input type="search" placeholder="Search menu..." aria-label="Search menu" data-menu-search>
        <kbd>Ctrl K</kbd>
      </label>

      <?php foreach ($groups as $gi => [$heading, $links]): ?>
        <p class="adm-nav__heading<?= $gi === 0 ? ' visually-hidden' : '' ?>"><?= h($heading) ?></p>
        <nav class="adm-nav" aria-label="<?= h($heading) ?>">
          <?php foreach ($links as [$key, $label, $icon]): ?>
            <a href="<?= admin_url($key) ?>" class="adm-nav__link<?= $active === $key ? ' is-active' : '' ?>" data-menu-item <?= $active === $key ? 'aria-current="page"' : '' ?>>
              <?= adm_icon($icon) ?><span><?= h($label) ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      <?php endforeach; ?>

      <p class="adm-nav__heading">Store</p>
      <nav class="adm-nav" aria-label="Store">
        <a href="<?= brio_asset('') ?>" target="_blank" rel="noopener" class="adm-nav__link" data-menu-item><?= adm_icon('store') ?><span>View storefront</span></a>
      </nav>

      <div class="adm-promo">
        <strong>Changes go live instantly</strong>
        <p>Slides, pages, menus and products update on the storefront as soon as you save.</p>
        <a href="<?= brio_asset('') ?>" target="_blank" rel="noopener" class="btn adm-btn adm-btn--primary adm-btn--sm">Open Store <span aria-hidden="true">⟶</span></a>
      </div>

      <div class="adm-user">
        <span class="adm-user__avatar" aria-hidden="true"><?= h($initials) ?></span>
        <span class="adm-user__meta"><strong><?= h($admin->name) ?></strong><small>Admin</small></span>
        <form method="post" action="<?= admin_url('logout') ?>" class="ms-auto">
          <input type="hidden" name="_token" value="<?= h($csrf) ?>">
          <button type="submit" class="adm-icon-btn" title="Sign out" aria-label="Sign out"><?= adm_icon('logout') ?></button>
        </form>
      </div>
    </div>
  </aside>

  <div class="adm-main">
    <header class="adm-topbar">
      <button class="adm-icon-btn d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adm-sidebar" aria-controls="adm-sidebar" aria-label="Open menu"><?= adm_icon('menu', 20) ?></button>
      <?php if ($showSearch): ?>
        <label class="adm-topsearch">
          <?= adm_icon('search', 16) ?>
          <input type="search" placeholder="Search products, categories, or ..." aria-label="Search products" data-search>
        </label>
      <?php endif; ?>
      <div class="adm-today">
        <span class="adm-today__icon"><?= adm_icon('calendar') ?></span>
        <span><strong><?= date('D, j M Y') ?></strong><small>Have a productive day!</small></span>
      </div>
    </header>
