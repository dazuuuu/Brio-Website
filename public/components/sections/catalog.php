<?php
$category = parse_category($_GET['category'] ?? null);
$q = trim((string)($_GET['q'] ?? ''));
$all = get_products();
$matches = filter_products($category, $q);
$count = count($matches);
$tabs = ['all' => 'All'] + categories();
?>
<div class="container page" data-catalog>
  <header class="page__head">
    <div>
      <p class="eyebrow">Shop</p>
      <h1 data-catalog-title><?= e($category === 'all' ? 'All Products' : categories()[$category]) ?></h1>
      <p class="page__sub" aria-live="polite" data-catalog-count>
        <?= $count ?> <?= $count === 1 ? 'product' : 'products' ?><?= $q !== '' ? ' for “' . e($q) . '”' : '' ?>
      </p>
    </div>
    <form class="search-pill search-pill--wide" role="search" action="<?= url('products') ?>" method="get" data-catalog-search>
      <?= icon('search', 18) ?>
      <?php if ($category !== 'all'): ?><input type="hidden" name="category" value="<?= e($category) ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search the catalog…" aria-label="Search the catalog">
    </form>
  </header>

  <nav class="nav nav-pills catalog-tabs" aria-label="Product categories">
    <?php foreach ($tabs as $value => $label): ?>
      <?php $href = url('products' . ($value === 'all' ? '' : '?category=' . $value) . ($q !== '' ? ($value === 'all' ? '?' : '&') . 'q=' . urlencode($q) : '')); ?>
      <a href="<?= e($href) ?>" class="nav-link catalog-tab<?= $category === $value ? ' active' : '' ?>" data-tab="<?= e($value) ?>"
         aria-pressed="<?= $category === $value ? 'true' : 'false' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>

  <?php component('ui/product-grid', [
      'products' => $all,
      'visible' => array_column($matches, 'id'),
      'emptyText' => $q !== '' ? "Nothing matched “{$q}”. Try a different word or category." : 'There are no products in this category yet.',
  ]); ?>
</div>
