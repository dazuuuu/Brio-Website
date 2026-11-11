<?php /** @var string $title */ ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= h($title) ?> · Brio Admin</title>
  <link rel="icon" href="<?= brio_asset('assets/img/favicon.svg') ?>" type="image/svg+xml">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script>
    tailwind.config = { prefix: 'tw-', corePlugins: { preflight: false },
      theme: { extend: { colors: { brand: '#f25c1f', 'brand-soft': '#fff1ea', ink: '#1f1f1f', muted: '#7a7a7a', line: '#ececec', canvas: '#f7f6f4' } } } };
  </script>
  <link href="<?= brio_asset('assets/admin/admin.css') ?>" rel="stylesheet">
</head>
