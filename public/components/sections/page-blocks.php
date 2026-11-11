<?php
/** Renders page-builder blocks. @var array $blocks (from PageService::render, links resolved, product lists loaded) */
$align = static fn(array $b): string => ' cms-align-' . ($b['align'] ?? 'left');
$sections = ['hero', 'deals', 'catalog', 'login', 'register', 'account', 'checkout'];
$open = false;
?>
<?php foreach ($blocks as $b): ?>
  <?php
    if ($b['type'] === 'section') {
        if ($open) { echo '</div>'; $open = false; }
        if (in_array($b['name'] ?? '', $sections, true)) component('sections/' . $b['name'], ['block' => $b]);
        continue;
    }
    if (!$open) { echo '<div class="container cms">'; $open = true; }
  ?>
  <?php switch ($b['type']):
    case 'heading': ?>
      <div class="cms-block cms-prose<?= $align($b) ?>"><<?= $b['level'] ?> class="cms-heading"><?= e($b['text']) ?></<?= $b['level'] ?>></div>
    <?php break; case 'text': ?>
      <div class="cms-block cms-prose cms-text<?= $align($b) ?>"><?= format_text($b['text']) ?></div>
    <?php break; case 'image': ?>
      <figure class="cms-block cms-image cms-image--<?= e($b['width']) ?>">
        <img src="<?= e(url($b['src'])) ?>" alt="<?= e($b['alt']) ?>" loading="lazy">
        <?php if ($b['caption'] !== ''): ?><figcaption><?= e($b['caption']) ?></figcaption><?php endif; ?>
      </figure>
    <?php break; case 'image_text': ?>
      <section class="cms-block cms-split cms-split--<?= e($b['side']) ?>">
        <div class="cms-split__media"><img src="<?= e(url($b['src'])) ?>" alt="<?= e($b['alt']) ?>" loading="lazy"></div>
        <div class="cms-split__body">
          <?php if ($b['title'] !== ''): ?><h2 class="cms-heading"><?= e($b['title']) ?></h2><?php endif; ?>
          <?php if ($b['text'] !== ''): ?><div class="cms-text"><?= format_text($b['text']) ?></div><?php endif; ?>
          <?php if ($b['ctaLabel'] !== '' && ($b['href'] ?? null) !== null): ?>
            <a class="btn btn-brio btn-brio--primary mt-2" href="<?= e(site_href($b['href'])) ?>"><?= e($b['ctaLabel']) ?></a>
          <?php endif; ?>
        </div>
      </section>
    <?php break; case 'button': if (($b['href'] ?? null) === null) break; ?>
      <div class="cms-block cms-button<?= $align($b) ?>">
        <a class="btn btn-brio btn-brio--<?= $b['style'] === 'outline' ? 'secondary' : 'primary' ?> btn-brio--lg" href="<?= e(site_href($b['href'])) ?>"><?= e($b['label']) ?></a>
      </div>
    <?php break; case 'products': ?>
      <section class="cms-block">
        <?php if ($b['title'] !== '' || $b['subtitle'] !== ''): ?>
          <div class="section__head-centered">
            <?php if ($b['title'] !== ''): ?><h2 class="section__title-centered"><?= e($b['title']) ?></h2><?php endif; ?>
            <?php if ($b['subtitle'] !== ''): ?><p class="section__subtitle-centered"><?= e($b['subtitle']) ?></p><?php endif; ?>
          </div>
        <?php endif; ?>
        <?php component('ui/product-grid', ['products' => $b['items'] ?? [], 'emptyTitle' => 'Nothing here yet', 'emptyText' => 'Products will appear here once they’re added.']); ?>
      </section>
    <?php break; case 'categories': ?>
      <section class="cms-block">
        <?php if ($b['title'] !== ''): ?><div class="section__head-centered"><h2 class="section__title-centered"><?= e($b['title']) ?></h2></div><?php endif; ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
          <?php foreach (categories() as $slug => $label): ?>
            <div class="col"><a class="cms-cat" href="<?= e(url('products?category=' . $slug)) ?>"><span><?= e($label) ?></span><small>Shop now →</small></a></div>
          <?php endforeach; ?>
        </div>
      </section>
    <?php break; default: ?>
      <hr class="cms-block cms-divider">
  <?php endswitch; ?>
<?php endforeach; ?>
<?php if ($open) echo '</div>'; ?>
