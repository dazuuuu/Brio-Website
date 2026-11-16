</main>

<footer class="site-footer">
  <div class="container">
    <div class="row gy-4 gx-md-5">
      <div class="col-md-6">
        <?php $brand = brand(); ?>
        <a href="<?= url() ?>" class="brand" aria-label="<?= e($brand['name']) ?> home">
          <img class="brand__logo brand__logo--light<?= $brand['hasDarkLogo'] ? '' : ' brand__logo--chip' ?>" src="<?= e(url($brand['logo'])) ?>" alt="<?= e($brand['name']) ?>">
          <?php if ($brand['hasDarkLogo']): ?><img class="brand__logo brand__logo--dark" src="<?= e(url($brand['logoDark'])) ?>" alt="" aria-hidden="true"><?php endif; ?>
        </a>
        <p class="site-footer__blurb"><?= e($brand['tagline'] !== '' ? $brand['tagline'] . '. ' : '') ?>Organic face, hand and body care, delivered to your door.</p>
      </div>
      <nav class="col-md-3 site-footer__col" aria-label="Shop">
        <h4>Shop</h4>
        <a href="<?= url('products') ?>">All Products</a>
        <?php foreach (categories() as $slug => $label): ?>
          <a href="<?= url('products?category=' . $slug) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <?php if ($footerLinks = site_nav('footer')): ?>
      <nav class="col-md-3 site-footer__col" aria-label="Company">
        <h4>Company</h4>
        <?php foreach ($footerLinks as $it): ?>
          <a href="<?= e(site_href($it['href'])) ?>"<?= $it['newTab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($it['label']) ?></a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </div>
    <div class="site-footer__legal"><small>© <?= date('Y') ?> <?= e(brand()['name']) ?>. All rights reserved.</small></div>
  </div>
</footer>

<?php component('layout/cart-drawer'); ?>

<?php if ($ads = site_ads($route ?? '')): ?>
  <div hidden data-ads="<?= e(json_encode($ads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG)) ?>"></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
