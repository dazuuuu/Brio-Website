</main>

<footer class="site-footer">
  <div class="container">
    <div class="row gy-4 gx-5">
      <div class="col-md-6">
        <a href="<?= url() ?>" class="brand" aria-label="Brio home"><?= icon('leaf', 26, 'brand__leaf') ?><span class="brand__name">Brio</span></a>
        <p class="site-footer__blurb">Clean, leaf-fresh skin and body care. Gentle on you, kind to the planet.</p>
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
    <div class="site-footer__legal"><small>© <?= date('Y') ?> Brio. All rights reserved.</small></div>
  </div>
</footer>

<?php component('layout/cart-drawer'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
