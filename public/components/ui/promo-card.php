<?php
// Props: $p (product)
$eyebrow = $p['dealEyebrow'] ?? 'Special Offer';
$tag = $p['dealTag'] ?? '';
$tone = $p['dealTone'] ?: 'mint';
$soldOut = $p['stock'] <= 0;
?>
<div class="col">
  <article class="promo-card promo-card--<?= $tone ?> h-100" aria-label="<?= e($p['name']) ?>">
    <div class="promo-card__content">
      <span class="promo-card__eyebrow"><?= e($eyebrow) ?></span>
      <h3 class="promo-card__title"><?= e($p['name']) ?></h3>
      <div class="tw-flex tw-items-baseline tw-gap-[0.4rem] tw-mt-0.5">
        <span class="promo-card__price"><?= money($p['price']) ?></span>
        <?php if ($p['compareAtPrice']): ?><s class="promo-card__compare"><?= money($p['compareAtPrice']) ?></s><?php endif; ?>
      </div>
      <div class="tw-flex tw-flex-col tw-items-start tw-gap-[0.4rem] tw-mt-2">
        <button type="button" class="promo-card__btn" data-add-to-cart="<?= product_json($p) ?>" <?= $soldOut ? 'disabled' : '' ?> aria-label="Add <?= e($p['name']) ?> to cart">
          <?= $soldOut ? 'Sold out' : 'Add to Cart' ?>
        </button>
        <?php if ($tag !== ''): ?><span class="promo-card__tag-pill"><?= e($tag) ?></span><?php endif; ?>
      </div>
    </div>
    <div class="promo-card__media">
      <img src="<?= e(product_image($p)) ?>" alt="<?= e($p['name']) ?>" loading="lazy" class="promo-card__img">
    </div>
  </article>
</div>
