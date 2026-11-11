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
      <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 g-md-4">
        <?php foreach ($deals as $p) component('ui/promo-card', ['p' => $p]); ?>
      </div>
    <?php endif; ?>
  </div>
</section>
