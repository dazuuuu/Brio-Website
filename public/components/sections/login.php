<?php
$customer = site_customer();
$redirect = site_redirect_param();
$q = $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : '';
?>
<div class="container page">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-card__header">
        <div class="auth-card__icon" aria-hidden="true"><?= icon('leaf', 32) ?></div>
        <?php if ($customer): ?>
          <h1>You’re signed in</h1>
          <p>Signed in as <b><?= e($customer['fullName']) ?></b></p>
        <?php else: ?>
          <h1>Welcome Back</h1>
          <p>Sign in to order faster and see your orders</p>
        <?php endif; ?>
      </div>

      <?php if ($customer): ?>
        <div class="d-grid gap-2">
          <a href="<?= url($redirect !== '' ? ltrim($redirect, '/') : 'account') ?>" class="btn btn-brio btn-brio--primary btn-brio--lg"><?= $redirect !== '' ? 'Continue' : 'Go to my account' ?></a>
          <button type="button" class="btn btn-brio btn-brio--secondary" data-customer-logout>Sign out</button>
        </div>
      <?php else: ?>
        <div class="alert auth-alert" role="alert" data-auth-error hidden></div>
        <form class="auth-form" data-login-form novalidate>
          <div class="form-group-brio"><label for="login-email" class="form-label">Email Address</label>
            <input id="login-email" name="email" type="email" class="form-control" placeholder="you@example.com" required autocomplete="email"></div>
          <div class="form-group-brio"><label for="login-password" class="form-label">Password</label>
            <input id="login-password" name="password" type="password" class="form-control" placeholder="••••••••" required autocomplete="current-password"></div>
          <button type="submit" class="btn btn-brio btn-brio--primary btn-brio--lg w-100 auth-btn">Sign In</button>
        </form>
        <div class="auth-card__footer">
          <p>New here? <a href="<?= url('register' . $q) ?>" class="auth-link">Create an account →</a></p>
          <p class="mb-0 mt-2 small">You can also <a href="<?= url('products') ?>" class="auth-link">shop without an account</a>.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
