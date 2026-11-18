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
    <?php if ($deals):
      // Repeat the cards until one set is wider than any screen, then show two identical sets so the loop has no gap.
      $set = [];
      while (count($set) < 8) foreach ($deals as $d) $set[] = $d;
      $secs = count($set) * 6; // slow: about 6 seconds per card
    ?>
      <div class="deals-marquee" style="--deals-time: <?= $secs ?>s" role="region" aria-label="<?= e($dealsTitle) ?>">
        <div class="deals-marquee__track">
          <div class="deals-marquee__group"><?php foreach ($set as $p) component('ui/product-card', ['p' => $p, 'hidden' => false]); ?></div>
          <div class="deals-marquee__group" aria-hidden="true" inert><?php foreach ($set as $p) component('ui/product-card', ['p' => $p, 'hidden' => false]); ?></div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>
