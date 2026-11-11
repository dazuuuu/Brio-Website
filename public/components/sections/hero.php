<?php
$legacySlides = [
    ['tag' => 'Natural Botanicals', 'title' => 'SKIN CARE', 'subtitle' => 'Healthy & Natural · Fresh & Radiant', 'cta' => 'Shop Skin Care',
     'link' => 'products?category=skin-care', 'main' => 'face_cream.jpeg', 'second' => 'serum.jpeg', 'bg' => 'linear-gradient(135deg, #F4FAF5 0%, #E8F5ED 100%)'],
    ['tag' => 'Pure Botanical Care', 'title' => 'BATH & BODY', 'subtitle' => 'Cleanse & Refresh · Pure Organic', 'cta' => 'Shop Bath & Body',
     'link' => 'products?category=bath-body', 'main' => 'showergel.jpeg', 'second' => 'body_lotion.jpeg', 'bg' => 'linear-gradient(135deg, #FDF9F3 0%, #EEF7F1 100%)'],
];
$slides = site_hero();
$multi = count($slides) > 1;
?>
<?php if ($slides): ?>
<section class="hero-section" aria-label="Featured promotions">
  <div class="container">
    <div id="hero-carousel" class="carousel slide hero-banner hero-banner--image"<?= $multi ? ' data-bs-ride="carousel" data-bs-interval="6000"' : '' ?>>
      <div class="carousel-inner">
        <?php foreach ($slides as $i => $s): ?>
          <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>">
            <div class="hero-slide" style="--ar-m: <?= $s['mobileImage'] ? '1 / 1' : '4 / 3' ?>">
              <picture>
                <?php if ($s['mobileImage']): ?><source media="(max-width: 767.98px)" srcset="<?= e(url($s['mobileImage'])) ?>"><?php endif; ?>
                <img src="<?= e(url($s['image'])) ?>" alt="<?= e($s['alt']) ?>" class="hero-slide__img"<?= $i > 0 ? ' loading="lazy"' : '' ?>>
              </picture>
              <?php if ($s['ctaLabel'] !== null && $s['href'] !== null): ?>
                <a class="hero-slide__cta" style="background:<?= e($s['ctaColor']) ?>;color:<?= e($s['ctaTextColor']) ?>" href="<?= e(site_href($s['href'])) ?>"><?= e($s['ctaLabel']) ?></a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($multi): ?>
        <button type="button" class="hero-banner__nav-btn hero-banner__nav-btn--prev" data-bs-target="#hero-carousel" data-bs-slide="prev" aria-label="Previous slide"><?= icon('chevron-left', 20) ?></button>
        <button type="button" class="hero-banner__nav-btn hero-banner__nav-btn--next" data-bs-target="#hero-carousel" data-bs-slide="next" aria-label="Next slide"><?= icon('chevron-right', 20) ?></button>
        <div class="carousel-indicators hero-banner__dots" aria-label="Slide dots">
          <?php foreach ($slides as $i => $s): ?>
            <button type="button" data-bs-target="#hero-carousel" data-bs-slide-to="<?= $i ?>" class="hero-banner__dot<?= $i === 0 ? ' active' : '' ?>" <?= $i === 0 ? 'aria-current="true"' : '' ?> aria-label="Go to slide <?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php else: /* No slides uploaded yet: built-in hero */ ?>
<section class="hero-section" aria-label="Featured Promotions">
  <div class="container">
    <div id="hero-carousel" class="carousel slide carousel-fade hero-banner" data-bs-ride="carousel" data-bs-interval="6000">
      <!-- Subtle botanical line-art watermarks -->
      <svg class="hero-banner__watermark hero-banner__watermark--tl" viewBox="0 0 160 160" fill="none" aria-hidden="true">
        <path d="M10 150C20 90 60 40 150 10M10 150C50 140 100 110 150 50M10 150C80 120 130 90 150 10" stroke="#8CC63F" stroke-width="1.5" stroke-opacity="0.22"/>
        <circle cx="90" cy="50" r="3" fill="#8CC63F" fill-opacity="0.25"/><circle cx="120" cy="30" r="2" fill="#8CC63F" fill-opacity="0.25"/>
      </svg>
      <svg class="hero-banner__watermark hero-banner__watermark--br" viewBox="0 0 160 160" fill="none" aria-hidden="true">
        <path d="M150 10C140 70 100 120 10 150M150 10C110 20 60 50 10 110M150 10C80 40 30 70 10 150" stroke="#8CC63F" stroke-width="1.5" stroke-opacity="0.22"/>
      </svg>

      <div class="carousel-inner">
        <?php foreach ($legacySlides as $i => $s): ?>
          <div class="carousel-item<?= $i === 0 ? ' active' : '' ?>" style="background: <?= e($s['bg']) ?>">
            <div class="hero-banner__grid">
              <div class="hero-banner__media">
                <div class="hero-banner__image-stage">
                  <div class="hero-banner__prod-secondary"><img src="<?= asset('img/products/' . $s['second']) ?>" alt="" class="hero-banner__img hero-banner__img--second"></div>
                  <div class="hero-banner__prod-main"><img src="<?= asset('img/products/' . $s['main']) ?>" alt="<?= e($s['title']) ?>" class="hero-banner__img hero-banner__img--main"></div>
                </div>
              </div>
              <div class="hero-banner__content">
                <span class="hero-banner__tag"><?= e($s['tag']) ?></span>
                <h1 class="hero-banner__title"><?= e($s['title']) ?></h1>
                <p class="hero-banner__subtitle"><?= e($s['subtitle']) ?></p>
                <a href="<?= url($s['link']) ?>" class="hero-banner__cta"><?= e($s['cta']) ?></a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <button type="button" class="hero-banner__nav-btn hero-banner__nav-btn--prev" data-bs-target="#hero-carousel" data-bs-slide="prev" aria-label="Previous slide"><?= icon('chevron-left', 20) ?></button>
      <button type="button" class="hero-banner__nav-btn hero-banner__nav-btn--next" data-bs-target="#hero-carousel" data-bs-slide="next" aria-label="Next slide"><?= icon('chevron-right', 20) ?></button>

      <div class="carousel-indicators hero-banner__dots" aria-label="Slide dots">
        <?php foreach ($legacySlides as $i => $s): ?>
          <button type="button" data-bs-target="#hero-carousel" data-bs-slide-to="<?= $i ?>" class="hero-banner__dot<?= $i === 0 ? ' active' : '' ?>"
                  <?= $i === 0 ? 'aria-current="true"' : '' ?> aria-label="Go to slide <?= $i + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
