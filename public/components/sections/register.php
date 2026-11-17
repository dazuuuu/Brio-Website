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
          <h1>You already have an account</h1>
          <p class="auth-sub">Signed in as <b><?= e($customer['displayName']) ?></b></p>
          <div class="d-grid gap-2 mt-3">
            <a href="<?= url('account') ?>" class="btn btn-brio btn-brio--primary auth-btn">Go to my account</a>
            <button type="button" class="btn btn-brio btn-brio--secondary" data-customer-logout>Sign out</button>
          </div>
        <?php else: ?>
          <h1>Create an account</h1>
          <p class="auth-sub">Already have an account? <a href="<?= url('login' . $q) ?>" class="auth-link">Log in</a></p>
          <div class="alert auth-alert" role="alert" data-auth-error hidden></div>
          <div class="auth-cols">
            <form class="auth-form" data-register-form novalidate>
              <div class="form-group-brio"><label for="reg-email" class="form-label">Email</label>
                <input id="reg-email" name="email" type="email" class="form-control" placeholder="jane@example.com" autocomplete="email" inputmode="email"><div class="invalid-feedback" data-error-for="email"></div></div>
              <div class="form-group-brio"><label for="reg-password" class="form-label">Password <span class="auth-hint">(min 8 characters)</span></label>
                <input id="reg-password" name="password" type="password" class="form-control" placeholder="••••••••" autocomplete="new-password"><div class="invalid-feedback" data-error-for="password"></div></div>
              <div class="form-group-brio"><label for="reg-confirm" class="form-label">Confirm password</label>
                <input id="reg-confirm" name="confirm" type="password" class="form-control" placeholder="••••••••" autocomplete="new-password"><div class="invalid-feedback" data-error-for="confirm"></div></div>
              <p class="auth-fine">We’ll email you a link to confirm your address. Add your name and delivery details later, in your account or at checkout.</p>
              <button type="submit" class="btn btn-brio btn-brio--primary auth-btn">Create account</button>
            </form>
            <?php include __DIR__ . '/_auth_google.php'; ?>
          </div>
          <p class="auth-guest">No account needed to order: <a href="<?= url('products') ?>" class="auth-link">shop as a guest</a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
