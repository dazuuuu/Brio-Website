<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#00BE62">
  <script>
    /* Visitor theme: light by default; a choice lasts for this browser session only and never changes the site for anyone else. */
    try { if (sessionStorage.getItem('brio.theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); document.documentElement.setAttribute('data-bs-theme', 'dark'); } } catch (e) {}
  </script>
  <title><?= e($seo['title']) ?></title>
  <meta name="description" content="<?= e($seo['description']) ?>">
  <link rel="canonical" href="<?= e($seo['canonical']) ?>">
  <?php if ($seo['noindex']): ?><meta name="robots" content="noindex,follow"><?php else: ?><meta name="robots" content="index,follow,max-image-preview:large"><?php endif; ?>

  <!-- When a link is shared (WhatsApp, Facebook, X, LinkedIn…) these decide the picture, title and text people see -->
  <meta property="og:site_name" content="<?= e(brand()['name']) ?>">
  <meta property="og:type" content="<?= e($seo['type']) ?>">
  <meta property="og:title" content="<?= e($seo['title']) ?>">
  <meta property="og:description" content="<?= e($seo['description']) ?>">
  <meta property="og:url" content="<?= e($seo['canonical']) ?>">
  <meta property="og:image" content="<?= e($seo['image']) ?>">
  <meta property="og:image:alt" content="<?= e($seo['product']['name'] ?? brand()['name']) ?>">
  <meta property="og:locale" content="en_KE">
  <?php if ($seo['product']): ?>
    <meta property="product:price:amount" content="<?= e((string)$seo['product']['price']) ?>">
    <meta property="product:price:currency" content="<?= e(\Brio\Infrastructure\Config\Config::get('app.currency_code', 'KES')) ?>">
  <?php endif; ?>
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= e($seo['title']) ?>">
  <meta name="twitter:description" content="<?= e($seo['description']) ?>">
  <meta name="twitter:image" content="<?= e($seo['image']) ?>">
  <script type="application/ld+json"><?= seo_jsonld($seo) ?></script>

  <link rel="icon" href="<?= e(seo_icon('favicon.ico')) ?>" sizes="48x48">
  <link rel="icon" href="<?= e(seo_icon('favicon-32.png')) ?>" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= e(seo_icon('apple-touch-icon.png')) ?>">
  <link rel="manifest" href="<?= url('site.webmanifest') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500;9..144,600&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tailwind (Play CDN): "tw-" prefix and no preflight so it sits alongside Bootstrap without clashes. -->
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = {
      prefix: 'tw-',
      corePlugins: { preflight: false },
      theme: { extend: {
        colors: { leaf: '#00be62', 'leaf-dark': '#00a354', 'leaf-ink': '#00793f', mint: '#eaf8f0', 'mint-deep': '#d3eedd', ink: '#1b2e24', 'ink-soft': '#52665a', line: '#d6e9dc' },
        fontFamily: { display: ['Fraunces', 'Georgia', 'serif'], body: ['DM Sans', 'system-ui', 'sans-serif'] },
      } },
    };
  </script>
  <link href="<?= asset('css/app.css') ?>" rel="stylesheet">
  <script>window.BRIO = { base: <?= json_encode(base_path()) ?> };</script>
</head>
<body>
<a href="#main" class="skip-link">Skip to content</a>
<?php component('layout/navbar', ['route' => $route, 'path' => $path]); ?>
<main id="main">
