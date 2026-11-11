<div class="container page">
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-card__header">
        <div class="auth-card__icon" aria-hidden="true"><?= icon('leaf', 32) ?></div>
        <h1>Welcome Back</h1>
        <p>Sign in to access your Brio account and orders</p>
      </div>

      <div class="alert auth-alert" role="alert" data-auth-error hidden></div>

      <form class="auth-form" data-login-form novalidate>
        <div class="form-group-brio">
          <label for="login-email" class="form-label">Email Address</label>
          <input id="login-email" name="email" type="email" class="form-control" placeholder="you@example.com" required autocomplete="email">
        </div>

        <div class="form-group-brio">
          <div class="d-flex justify-content-between align-items-baseline">
            <label for="login-password" class="form-label">Password</label>
            <a href="#forgot" class="auth-link auth-link--sm" data-forgot>Forgot password?</a>
          </div>
          <input id="login-password" name="password" type="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
        </div>

        <div class="form-check form-check-brio">
          <input class="form-check-input" type="checkbox" id="login-remember" name="remember" checked>
          <label class="form-check-label" for="login-remember">Remember me on this device</label>
        </div>

        <button type="submit" class="btn btn-brio btn-brio--primary btn-brio--lg w-100 auth-btn">Sign In</button>
        <button type="button" class="auth-demo-btn" data-demo-fill>Use Demo Account (Amani Kariuki)</button>
      </form>

      <div class="auth-card__footer">
        <p>Don’t have an account? <a href="<?= url('register') ?>" class="auth-link">Create one now →</a></p>
      </div>
    </div>
  </div>
</div>
