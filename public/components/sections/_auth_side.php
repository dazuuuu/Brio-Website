<?php
// Left panel of the sign-in / create-account card: brand logo, a product photo and a short line.
$brand = brand();
$sideImg = 'assets/img/products/serum.jpeg';
try { if (\Brio\Infrastructure\Config\Config::hasDatabase() && ($pop = brio('products')->popular())) $sideImg = $pop['image']; } catch (Throwable) {}
?>
<aside class="auth-side" aria-hidden="true">
  <img class="auth-side__logo" src="<?= e(url($brand['logo'])) ?>" alt="">
  <div class="auth-side__photo"><img src="<?= e(url($sideImg)) ?>" alt="" loading="lazy"></div>
  <p class="auth-side__line"><?= e($brand['tagline'] !== '' ? $brand['tagline'] : 'Organic face, hand and body care') ?></p>
</aside>
