<?php $featured = filter_products(featured: true); ?>
<div class="home-page">
  <?php component('sections/hero'); ?>
  <?php component('sections/deals'); ?>
  <section class="section" aria-labelledby="new-title">
    <div class="container">
      <div class="section__head-centered">
        <h2 id="new-title" class="section__title-centered">Featured Products</h2>
        <p class="section__subtitle-centered">Explore all fresh botanical formulations</p>
      </div>
      <?php component('ui/product-grid', ['products' => $featured]); ?>
      <div class="tw-flex tw-justify-center tw-mt-10"><a href="<?= url('products') ?>" class="btn btn-brio btn-brio--secondary">View all products →</a></div>
    </div>
  </section>
</div>
