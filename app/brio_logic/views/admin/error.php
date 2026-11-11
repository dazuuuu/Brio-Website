<?php
/** @var string $message */
$title = 'Error';
require __DIR__ . '/_head.php';
?>
<body class="adm-auth-body">
<main class="adm-auth">
  <div class="adm-card adm-auth__card text-center">
    <h1 class="adm-auth__title"><?= h($message) ?></h1>
    <p class="mt-3"><a class="btn adm-btn adm-btn--primary" href="<?= admin_url() ?>">Go to the dashboard</a></p>
  </div>
</main>
</body>
</html>
