<?php
/** "Daily Deals" section. Optional $block: title, subtitle. */
$deals = filter_products(deal: true);
$dealsTitle = trim((string)($block['title'] ?? '')) !== '' ? $block['title'] : 'Daily Deals';
$dealsSubtitle = (string)($block['subtitle'] ?? 'Special limited-time offers on our signature organic essentials');
?>
<section class="section" aria-labelledby="deals-title">
  <div class="container">
    <div class="section__head-centered">
      <h2 id="deals-title" class="section__title-centered"><?= e($dealsTitle) ?></h2>
      <?php if ($dealsSubtitle !== ''): ?><p class="section__subtitle-centered"><?= e($dealsSubtitle) ?></p><?php endif; ?>
    </div>
    <?php if ($deals): ?>
      <?php component('ui/product-grid', ['products' => $deals]); ?>
    <?php endif; ?>
  </div>
</section>
