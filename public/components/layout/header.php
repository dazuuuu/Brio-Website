<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= e($metaDescription ?? 'Brio — clean, leaf-fresh skin, hair and body care.') ?>">
  <meta name="theme-color" content="#8CC63F">
  <script>
    /* Visitor theme: light by default; a choice lasts for this browser session only and never changes the site for anyone else. */
    try { if (sessionStorage.getItem('brio.theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); document.documentElement.setAttribute('data-bs-theme', 'dark'); } } catch (e) {}
  </script>
  <title><?= e($title) ?></title>
  <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="48x48">
  <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="icon" href="<?= url('favicon-32.png') ?>" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= url('apple-touch-icon.png') ?>">
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
        colors: { leaf: '#8cc63f', 'leaf-dark': '#78b031', 'leaf-ink': '#4b7a17', mint: '#eaf8f0', 'mint-deep': '#d3eedd', ink: '#1b2e24', 'ink-soft': '#52665a', line: '#d6e9dc' },
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
