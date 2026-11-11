<?php
// Props: $products, optional $visible (ids shown initially), $emptyTitle / $emptyText
$visible ??= array_column($products, 'id');
$emptyTitle ??= 'No products found';
$emptyText ??= 'Try a different search or category.';
?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 product-grid" data-product-grid<?= $visible ? '' : ' hidden' ?>>
  <?php foreach ($products as $p) component('ui/product-card', ['p' => $p, 'hidden' => !in_array($p['id'], $visible, true)]); ?>
</div>
<div class="state-box" data-empty-state<?= $visible ? ' hidden' : '' ?>>
  <h3><?= e($emptyTitle) ?></h3>
  <p data-empty-text><?= e($emptyText) ?></p>
</div>
