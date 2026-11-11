<?php
/** @var array $product from site_product() */
[$stockText, $tone] = stock_status((int)$product['stock']);
$soldOut = $product['stock'] <= 0;
$related = site_related($product);
?>
<div class="container page">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?= url() ?>">Home</a> / <a href="<?= url('products') ?>">Shop</a> /
    <a href="<?= e(url('products?category=' . $product['category'])) ?>"><?= e($product['categoryLabel']) ?></a> / <span aria-current="page"><?= e($product['name']) ?></span>
  </nav>

  <div class="row g-4 g-lg-5 align-items-start">
    <div class="col-md-6"><div class="pdp__media"><img src="<?= e(product_image($product)) ?>" alt="<?= e($product['name']) ?>"></div></div>
    <div class="col-md-6">
      <p class="eyebrow"><?= e($product['categoryLabel']) ?><?= $product['badge'] ? ' · ' . e($product['badge']) : '' ?></p>
      <h1 class="pdp__title"><?= e($product['name']) ?></h1>
      <div class="my-2"><?= star_rating((float)$product['rating'], (int)$product['reviewCount']) ?></div>
      <div class="pdp__price">
        <span><?= money($product['price']) ?></span>
        <?php if ($product['compareAtPrice']): ?><s><?= money($product['compareAtPrice']) ?></s><?php endif; ?>
      </div>
      <p class="stock stock--<?= $tone ?> mt-2"><span class="stock__dot" aria-hidden="true"></span><?= e($stockText) ?></p>
      <?php if (trim($product['description']) !== ''): ?><div class="cms-text pdp__desc"><?= format_text($product['description']) ?></div><?php endif; ?>
      <button type="button" class="btn btn-brio btn-brio--primary btn-brio--lg pdp__add" data-add-to-cart="<?= product_json($product) ?>" <?= $soldOut ? 'disabled' : '' ?>>
        <?= $soldOut ? 'Sold out' : 'Add to Cart' ?>
      </button>
    </div>
  </div>

  <?php if ($related): ?>
    <section class="mt-5" aria-labelledby="related-title">
      <div class="section__head-centered"><h2 id="related-title" class="section__title-centered">You may also like</h2></div>
      <?php component('ui/product-grid', ['products' => $related]); ?>
    </section>
  <?php endif; ?>
</div>
