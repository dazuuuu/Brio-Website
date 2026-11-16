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
      <div class="deals" data-deals>
        <div class="deals__track" data-deals-track tabindex="0" role="group" aria-label="<?= e($dealsTitle) ?>">
          <?php foreach ($deals as $p) component('ui/promo-card', ['p' => $p]); ?>
        </div>
        <button type="button" class="deals__nav deals__nav--prev" data-deals-prev aria-label="Previous deals"><?= icon('chevron-left', 20) ?></button>
        <button type="button" class="deals__nav deals__nav--next" data-deals-next aria-label="Next deals"><?= icon('chevron-right', 20) ?></button>
        <div class="deals__dots" data-deals-dots aria-hidden="true"></div>
      </div>
    <?php endif; ?>
  </div>
</section>
