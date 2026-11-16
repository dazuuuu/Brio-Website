<?php
$items = site_nav('header');
$q = $route === 'products' ? (string)($_GET['q'] ?? '') : '';
$flags = site_header_flags();
$customer = site_customer();
?>
<header class="site-nav">
  <!-- Row 1: search · brand · actions (mobile: menu · brand · actions, then search) -->
  <div class="container site-nav__top">
    <button type="button" class="icon-btn site-nav__burger collapsed" data-bs-toggle="collapse" data-bs-target="#primary-nav"
            aria-controls="primary-nav" aria-expanded="false" aria-label="Open menu">
      <span class="when-closed"><?= icon('menu') ?></span><span class="when-open"><?= icon('close') ?></span>
    </button>

    <?php if ($flags['showSearch']): ?>
    <form class="search-pill site-nav__search" role="search" action="<?= url('products') ?>" method="get">
      <?= icon('search', 18) ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products…" aria-label="Search products">
    </form>
    <?php endif; ?>

    <?php $brand = brand(); ?>
    <a href="<?= url() ?>" class="brand site-nav__brand" aria-label="<?= e($brand['name']) ?> home">
      <img class="brand__logo brand__logo--light<?= $brand['hasDarkLogo'] ? '' : ' brand__logo--chip' ?>" src="<?= e(url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>">
      <?php if ($brand['hasDarkLogo']): ?><img class="brand__logo brand__logo--dark" src="<?= e(url($brand['logoDark'])) ?>" alt="" aria-hidden="true"><?php endif; ?>
    </a>

    <div class="site-nav__actions tw-flex tw-items-center tw-gap-1">
      <button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-pressed="false" aria-label="Switch to dark theme" title="Dark theme">
        <span class="theme-icon theme-icon--moon"><?= icon('moon') ?></span><span class="theme-icon theme-icon--sun"><?= icon('sun') ?></span>
      </button>
      <?php if ($flags['showAccountIcon']): ?>
      <a href="<?= url($customer ? 'account' : 'login') ?>" class="icon-btn" aria-label="<?= $customer ? e('My account (' . $customer['firstName'] . ')') : 'Sign in to your account' ?>" title="<?= $customer ? e('Signed in as ' . $customer['firstName']) : 'Sign In' ?>"><?= icon('user') ?></a>
      <?php endif; ?>
      <button type="button" class="icon-btn" data-bs-toggle="offcanvas" data-bs-target="#cart-drawer" aria-controls="cart-drawer" data-cart-button aria-label="Cart, 0 items">
        <?= icon('cart') ?><span class="cart-badge" data-cart-count hidden>0</span>
      </button>
    </div>
  </div>

  <!-- Row 2: category links, centred (collapses into a menu below 900px) -->
  <div class="site-nav__bar collapse" id="primary-nav">
    <div class="container">
      <nav class="site-nav__links" aria-label="Primary">
        <?php foreach ($items as $it): ?>
          <?php
            $isLogin = trim((string)parse_url($it['href'], PHP_URL_PATH), '/') === 'login';
            $href = site_href($it['href']); $label = $it['label'];
            if ($isLogin && $customer) { $href = url('account'); if ($label === 'Sign In / Register') $label = 'My Account (' . $customer['firstName'] . ')'; }
          ?>
          <a href="<?= e($href) ?>" class="site-nav__link<?= nav_is_active($it, $route, $path) ? ' is-active' : '' ?>"<?= $it['newTab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</header>
