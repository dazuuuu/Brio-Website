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
          <p>Save your contact details once, then ordering takes one click</p>
        <?php endif; ?>
      </div>

      <?php if ($customer): ?>
        <div class="d-grid gap-2">
          <a href="<?= url('account') ?>" class="btn btn-brio btn-brio--primary btn-brio--lg">Go to my account</a>
          <button type="button" class="btn btn-brio btn-brio--secondary" data-customer-logout>Sign out</button>
        </div>
      <?php else: ?>
        <div class="alert auth-alert" role="alert" data-auth-error hidden></div>
        <form class="auth-form" data-register-form novalidate>
          <div class="row g-3">
            <div class="col-sm-6 form-group-brio"><label for="reg-first-name" class="form-label">First Name *</label>
              <input id="reg-first-name" name="firstName" class="form-control" placeholder="e.g. Jane" autocomplete="given-name"><div class="invalid-feedback" data-error-for="firstName"></div></div>
            <div class="col-sm-6 form-group-brio"><label for="reg-last-name" class="form-label">Last Name</label>
              <input id="reg-last-name" name="lastName" class="form-control" placeholder="e.g. Wanjiku" autocomplete="family-name"><div class="invalid-feedback" data-error-for="lastName"></div></div>
          </div>
          <div class="form-group-brio"><label for="reg-email" class="form-label">Email Address *</label>
            <input id="reg-email" name="email" type="email" class="form-control" placeholder="jane@example.com" autocomplete="email"><div class="invalid-feedback" data-error-for="email"></div></div>

          <p class="auth-section-title">Your contact details <span>so we can reach you about your orders</span></p>
          <div class="row g-3">
            <div class="col-sm-6 form-group-brio"><label for="reg-phone" class="form-label">Phone number *</label>
              <input id="reg-phone" name="phone" type="tel" class="form-control" placeholder="+254 700 000 000" autocomplete="tel"><div class="invalid-feedback" data-error-for="phone"></div></div>
            <div class="col-sm-6 form-group-brio"><label for="reg-wa" class="form-label">WhatsApp number</label>
              <input id="reg-wa" name="whatsapp" type="tel" class="form-control" placeholder="Same as phone if empty"><div class="invalid-feedback" data-error-for="whatsapp"></div></div>
          </div>
          <div class="form-group-brio"><label for="reg-address" class="form-label">Delivery location <span class="text-secondary fw-normal">(optional)</span></label>
            <input id="reg-address" name="address" class="form-control" placeholder="Estate, street, town" autocomplete="street-address"></div>

          <div class="row g-3">
            <div class="col-sm-6 form-group-brio"><label for="reg-password" class="form-label">Password (min 8 characters) *</label>
              <input id="reg-password" name="password" type="password" class="form-control" placeholder="••••••••" autocomplete="new-password"><div class="invalid-feedback" data-error-for="password"></div></div>
            <div class="col-sm-6 form-group-brio"><label for="reg-confirm" class="form-label">Confirm Password *</label>
              <input id="reg-confirm" name="confirm" type="password" class="form-control" placeholder="••••••••" autocomplete="new-password"><div class="invalid-feedback" data-error-for="confirm"></div></div>
          </div>
          <div class="form-check form-check-brio">
            <input class="form-check-input" type="checkbox" id="reg-terms" name="terms">
            <label class="form-check-label" for="reg-terms">I agree to the terms and to being contacted about my orders.</label>
          </div>
          <div class="invalid-feedback d-block" data-error-for="terms"></div>
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
