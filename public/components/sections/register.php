<div class="container page">
  <div class="auth-wrap">
    <div class="auth-card auth-card--wide">
      <div class="auth-card__header">
        <div class="auth-card__icon" aria-hidden="true"><?= icon('leaf', 32) ?></div>
        <h1>Create an Account</h1>
        <p>Join Brio for botanical wellness, order tracking and rewards</p>
      </div>

      <div class="alert auth-alert" role="alert" data-auth-error hidden></div>

      <form class="auth-form" data-register-form novalidate>
        <div class="row g-3">
          <div class="col-sm-6 form-group-brio">
            <label for="reg-first-name" class="form-label">First Name *</label>
            <input id="reg-first-name" name="firstName" type="text" class="form-control" placeholder="e.g. Jane" required autocomplete="given-name">
          </div>
          <div class="col-sm-6 form-group-brio">
            <label for="reg-last-name" class="form-label">Last Name *</label>
            <input id="reg-last-name" name="lastName" type="text" class="form-control" placeholder="e.g. Wanjiku" required autocomplete="family-name">
          </div>
        </div>

        <div class="form-group-brio">
          <label for="reg-email" class="form-label">Email Address *</label>
          <input id="reg-email" name="email" type="email" class="form-control" placeholder="jane.wanjiku@example.com" required autocomplete="email">
        </div>

        <div class="form-group-brio">
          <label for="reg-phone" class="form-label">Phone Number (Optional)</label>
          <input id="reg-phone" name="phone" type="tel" class="form-control" placeholder="+254 700 000 000" autocomplete="tel">
        </div>

        <div class="row g-3">
          <div class="col-sm-6 form-group-brio">
            <label for="reg-password" class="form-label">Password (min 6 characters) *</label>
            <input id="reg-password" name="password" type="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
          </div>
          <div class="col-sm-6 form-group-brio">
            <label for="reg-confirm-password" class="form-label">Confirm Password *</label>
            <input id="reg-confirm-password" name="confirmPassword" type="password" class="form-control" placeholder="••••••••" required autocomplete="new-password">
          </div>
        </div>

        <div class="form-check form-check-brio">
          <input class="form-check-input" type="checkbox" id="reg-terms" name="terms">
          <label class="form-check-label" for="reg-terms">
            I agree to Brio’s <a href="#terms" class="auth-link">Terms of Service</a> and <a href="#privacy" class="auth-link">Privacy Policy</a>
          </label>
        </div>

        <button type="submit" class="btn btn-brio btn-brio--primary btn-brio--lg w-100 auth-btn">Create Account</button>
      </form>

      <div class="auth-card__footer">
        <p>Already have an account? <a href="<?= url('login') ?>" class="auth-link">Sign in here →</a></p>
      </div>
    </div>
  </div>
</div>
