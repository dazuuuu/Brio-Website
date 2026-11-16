<?php
$customer = site_customer();
$redirect = site_redirect_param();
$q = $redirect !== '' ? '?redirect=' . rawurlencode($redirect) : '';
?>
<div class="container page">
  <div class="auth-wrap">
    <div class="auth-card auth-card--wide">
      <div class="auth-card__header">
        <div class="auth-card__icon" aria-hidden="true"><?= icon('leaf', 32) ?></div>
        <?php if ($customer): ?>
          <h1>You already have an account</h1>
          <p>Signed in as <b><?= e($customer['fullName']) ?></b></p>
        <?php else: ?>
          <h1>Create an Account</h1>
          <p>Just an email and a password. Track your orders and order faster next time</p>
        <?php endif; ?>
      </div>

      <?php if ($customer): ?>
        <div class="d-grid gap-2">
          <a href="<?= url('account') ?>" class="btn btn-brio btn-brio--primary btn-brio--lg">Go to my account</a>
          <button type="button" class="btn btn-brio btn-brio--secondary" data-customer-logout>Sign out</button>
        </div>
      <?php else: ?>
        <?php if ($gid = brand()['googleClientId']): ?>
          <div class="auth-google" data-google-signin data-client-id="<?= e($gid) ?>"><div data-google-button></div><p class="auth-google__note" data-google-note hidden></p></div>
          <div class="auth-or"><span>or use your email</span></div>
        <?php elseif (isset($_COOKIE['brio_admin'])): /* only you (signed in to the admin) see this reminder */ ?>
          <div class="auth-google auth-google--off">
            <button type="button" class="btn-google-preview" disabled><svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.5 5.8c4.4-4.1 7.1-10.1 7.1-17.5z"/><path fill="#FBBC05" d="M10.5 28.7a14.5 14.5 0 0 1 0-9.4l-7.9-6.1a24 24 0 0 0 0 21.6l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.5-5.8c-2.1 1.4-4.9 2.3-8.4 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg> Continue with Google</button>
            <p class="auth-google__note">Admin preview: customers can’t see this button yet. Add your Google Client ID in Admin → Settings → Sign in with Google to turn it on.</p>
          </div>
          <div class="auth-or"><span>or use your email</span></div>
        <?php endif; ?>
        <div class="alert auth-alert" role="alert" data-auth-error hidden></div>
        <form class="auth-form" data-register-form novalidate>
          <div class="form-group-brio"><label for="reg-email" class="form-label">Email Address</label>
            <input id="reg-email" name="email" type="email" class="form-control" placeholder="jane@example.com" autocomplete="email" inputmode="email"><div class="invalid-feedback" data-error-for="email"></div></div>
          <div class="row g-3">
            <div class="col-sm-6 form-group-brio"><label for="reg-password" class="form-label">Password (min 8 characters)</label>
              <input id="reg-password" name="password" type="password" class="form-control" placeholder="••••••••" autocomplete="new-password"><div class="invalid-feedback" data-error-for="password"></div></div>
            <div class="col-sm-6 form-group-brio"><label for="reg-confirm" class="form-label">Confirm Password</label>
              <input id="reg-confirm" name="confirm" type="password" class="form-control" placeholder="••••••••" autocomplete="new-password"><div class="invalid-feedback" data-error-for="confirm"></div></div>
          </div>
          <p class="auth-fine">We’ll email you a link to confirm your address. Add your name and delivery details later, in your account or at checkout.</p>
          <button type="submit" class="btn btn-brio btn-brio--primary btn-brio--lg w-100 auth-btn">Create Account</button>
        </form>
        <div class="auth-card__footer">
          <p>Already have an account? <a href="<?= url('login' . $q) ?>" class="auth-link">Sign in here →</a></p>
          <p class="mb-0 mt-2 small">No account needed to order: <a href="<?= url('products') ?>" class="auth-link">keep shopping as a guest</a>.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
