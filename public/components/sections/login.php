<?php
$customer = site_customer();
$redirect = site_redirect_param();
$q = $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : '';
?>
<div class="container page">
  <div class="auth-wrap">
    <div class="auth-card auth-split">
      <?php include __DIR__ . '/_auth_side.php'; ?>
      <div class="auth-main">
        <?php if ($customer): ?>
          <h1>You’re signed in</h1>
          <p class="auth-sub">Signed in as <b><?= e($customer['displayName']) ?></b></p>
          <div class="d-grid gap-2 mt-3">
            <a href="<?= url($redirect !== '' ? ltrim($redirect, '/') : 'account') ?>" class="btn btn-brio btn-brio--primary auth-btn"><?= $redirect !== '' ? 'Continue' : 'Go to my account' ?></a>
            <button type="button" class="btn btn-brio btn-brio--secondary" data-customer-logout>Sign out</button>
          </div>
        <?php else: ?>
          <h1>Welcome back</h1>
          <p class="auth-sub">New here? <a href="<?= url('register' . $q) ?>" class="auth-link">Create an account</a></p>
          <div class="alert auth-alert" role="alert" data-auth-error hidden></div>
          <div class="auth-cols">
            <form class="auth-form" data-login-form novalidate>
              <div class="form-group-brio"><label for="login-email" class="form-label">Email</label>
                <input id="login-email" name="email" type="email" class="form-control" placeholder="you@example.com" required autocomplete="email" inputmode="email"></div>
              <div class="form-group-brio"><label for="login-password" class="form-label">Password</label>
                <input id="login-password" name="password" type="password" class="form-control" placeholder="••••••••" required autocomplete="current-password"></div>
              <button type="submit" class="btn btn-brio btn-brio--primary auth-btn">Sign in</button>
            </form>
            <?php include __DIR__ . '/_auth_google.php'; ?>
          </div>
          <p class="auth-guest">No account needed to order: <a href="<?= url('products') ?>" class="auth-link">shop as a guest</a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
