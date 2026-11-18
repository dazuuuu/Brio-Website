<?php
// Props: $p (product)
[$stockText, $tone] = stock_status($p['stock']);
$soldOut = $p['stock'] <= 0;
$off = discount_percent($p['price'], $p['compareAtPrice']);
?>
<div class="col" data-product data-name="<?= e(mb_strtolower($p['name'] . ' ' . $p['description'])) ?>" data-category="<?= e($p['category']) ?>"<?= !empty($hidden) ? ' hidden' : '' ?>>
  <article class="product-card h-100">
    <a class="product-card__media" href="<?= e(url('products/' . $p['slug'])) ?>" aria-label="View <?= e($p['name']) ?>">
      <img src="<?= e(product_image($p)) ?>" alt="<?= e($p['name']) ?>" loading="lazy" class="product-card__img">
      <?php if ($off): ?><span class="product-card__off">-<?= $off ?>%</span><?php endif; ?>
      <?php if ($p['badge']): ?><span class="pill product-card__badge"><?= e($p['badge']) ?></span><?php endif; ?>
    </a>
    <div class="product-card__body">
      <p class="product-card__category"><?= e(categories()[$p['category']]) ?></p>
      <h3 class="product-card__name"><a href="<?= e(url('products/' . $p['slug'])) ?>"><?= e($p['name']) ?></a></h3>
      <div class="tw-flex tw-justify-center tw-mb-0.5"><?= star_rating($p['rating'], $p['reviewCount']) ?></div>
      <div class="tw-flex tw-items-baseline tw-justify-center tw-gap-[0.45rem]">
        <span class="product-card__price"><?= money($p['price']) ?></span>
        <?php if ($p['compareAtPrice']): ?><s class="product-card__compare"><?= money($p['compareAtPrice']) ?></s><?php endif; ?>
        <?php if ($off): ?><span class="product-card__save"><?= $off ?>% off</span><?php endif; ?>
      </div>
      <p class="stock stock--<?= $tone ?>"><span class="stock__dot" aria-hidden="true"></span><?= e($stockText) ?></p>
      <div class="product-card__actions">
      <button type="button" class="btn btn-brio btn-brio--primary product-card__action" data-add-to-cart="<?= product_json($p) ?>"
              <?= $soldOut ? 'disabled' : '' ?> aria-label="<?= e($soldOut ? $p['name'] . ' is out of stock' : 'Add ' . $p['name'] . ' to cart') ?>">
        <?= $soldOut ? 'Sold out' : 'Add to Cart' ?>
      </button>
      <a class="btn btn-brio btn-brio--secondary product-card__view" href="<?= e(url('products/' . $p['slug'])) ?>">View product</a>
      </div>
    </div>
  </article>
</div>
