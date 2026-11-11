<?php
/** @var ?string $error @var string $email @var ?string $notice */
$title = 'Sign in';
require __DIR__ . '/_head.php';
?>
<body class="adm-auth-body">
<main class="adm-auth">
  <div class="adm-auth__brand"><span class="adm-logo"><?= adm_icon('bag', 20) ?></span><div><strong>Brio</strong><small>Sell smart, grow bigger.</small></div></div>
  <div class="adm-card adm-auth__card">
    <h1 class="adm-auth__title">Welcome back</h1>
    <p class="adm-auth__sub">Sign in to manage your store.</p>
    <?php if ($notice): ?><div class="alert adm-alert adm-alert--ok" role="status"><?= h($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert adm-alert adm-alert--error" role="alert"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="<?= admin_url('login') ?>">
      <input type="hidden" name="_token" value="<?= h(\Brio\Http\Session::csrfToken()) ?>">
      <div class="mb-3">
        <label class="form-label adm-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="form-control adm-input" value="<?= h($email) ?>" required autocomplete="email" autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label adm-label" for="password">Password</label>
        <input id="password" name="password" type="password" class="form-control adm-input" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn adm-btn adm-btn--primary w-100 mt-2">Sign in</button>
    </form>
    <p class="adm-auth__foot"><a href="<?= brio_asset('') ?>">← Back to store</a></p>
  </div>
</main>
</body>
</html>
