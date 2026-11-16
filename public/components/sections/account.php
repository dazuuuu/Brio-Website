<?php
$customer = site_customer();
?>
<div class="container page" data-account>
<?php if (!$customer): ?>
  <div class="state-box">
    <h1>Sign in to track your orders</h1>
    <p>See where your orders are, and keep your contact details for faster checkout.</p>
    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a href="<?= url('login?redirect=' . rawurlencode('/account')) ?>" class="btn btn-brio btn-brio--primary">Sign in</a>
      <a href="<?= url('register?redirect=' . rawurlencode('/account')) ?>" class="btn btn-brio btn-brio--secondary">Create an account</a>
    </div>
  </div>
<?php else: ?>
  <header class="page__head">
    <div><p class="eyebrow">My account</p><h1>Hi, <?= e($customer['displayName']) ?></h1>
      <p class="page__sub"><?= e($customer['email']) ?></p></div>
    <button type="button" class="btn btn-brio btn-brio--ghost btn-brio--sm" data-customer-logout>Sign out</button>
  </header>

  <?php if (!$customer['emailVerified']): ?>
    <div class="verify-banner" role="status" data-verify-banner>
      <div><b><?= ($_GET['verify'] ?? '') === 'sent' ? 'Account created! Check your email.' : 'Please confirm your email address.' ?></b>
        <span>We sent a confirmation link to <?= e($customer['email']) ?>. Open it to verify your account (check spam too).</span>
        <span class="verify-banner__msg" data-verify-msg role="status"></span></div>
      <button type="button" class="btn btn-brio btn-brio--secondary btn-brio--sm" data-resend-verify>Resend email</button>
    </div>
  <?php endif; ?>

  <section class="account__section" aria-labelledby="orders-title">
    <h2 id="orders-title">Track my orders</h2>
    <div data-orders><div class="panel-brio text-center text-secondary">Loading your orders…</div></div>
  </section>

  <section class="account__section" id="details" aria-labelledby="details-title">
    <h2 id="details-title">My contact details</h2>
    <?php if (!$customer['hasContactDetails']): ?>
      <p class="checkout-account-note">Add your name and phone number once and we’ll remember them, so you never fill in a form at checkout. You can also skip this and enter them when you order. They get saved then.</p>
    <?php else: ?>
      <p class="small text-secondary">We use these to place your orders instantly, so you never fill in a form at checkout.</p>
    <?php endif; ?>
    <form class="panel-brio" data-details-form novalidate>
      <div class="alert auth-alert" role="alert" data-form-error hidden></div>
      <div class="alert alert-success py-2" role="status" data-form-ok hidden>Saved.</div>
      <div class="row g-3 form-group-brio">
        <div class="col-sm-6"><label class="form-label" for="ad-first">First name</label><input id="ad-first" name="firstName" class="form-control" value="<?= e($customer['firstName']) ?>" autocomplete="given-name"><div class="invalid-feedback" data-error-for="firstName"></div></div>
        <div class="col-sm-6"><label class="form-label" for="ad-last">Last name</label><input id="ad-last" name="lastName" class="form-control" value="<?= e($customer['lastName']) ?>" autocomplete="family-name"></div>
        <div class="col-sm-6"><label class="form-label" for="ad-phone">Phone number</label><input id="ad-phone" name="phone" type="tel" class="form-control" value="<?= e($customer['phone']) ?>" placeholder="+254 700 000 000" autocomplete="tel"><div class="invalid-feedback" data-error-for="phone"></div></div>
        <div class="col-sm-6"><label class="form-label" for="ad-wa">WhatsApp number</label><input id="ad-wa" name="whatsapp" type="tel" class="form-control" value="<?= e($customer['whatsapp']) ?>" placeholder="Same as phone if empty"><div class="invalid-feedback" data-error-for="whatsapp"></div></div>
        <div class="col-12"><label class="form-label" for="ad-address">Delivery location <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-address" name="address" class="form-control" value="<?= e((string)$customer['address']) ?>" placeholder="Estate, street, town" autocomplete="street-address"></div>
      </div>
      <button type="submit" class="btn btn-brio btn-brio--primary mt-3">Save details</button>
    </form>
  </section>

  <section class="account__section" aria-labelledby="pw-title">
    <h2 id="pw-title">Password</h2>
    <?php if ($customer['hasPassword']): ?>
      <form class="panel-brio" data-password-form novalidate style="max-width:520px">
        <div class="alert auth-alert" role="alert" data-form-error hidden></div>
        <div class="alert alert-success py-2" role="status" data-form-ok hidden>Password changed.</div>
        <div class="form-group-brio mb-3"><label class="form-label" for="pw-current">Current password</label><input id="pw-current" name="current" type="password" class="form-control" autocomplete="current-password"><div class="invalid-feedback" data-error-for="current"></div></div>
        <div class="row g-3 form-group-brio">
          <div class="col-sm-6"><label class="form-label" for="pw-new">New password</label><input id="pw-new" name="new" type="password" class="form-control" autocomplete="new-password"><div class="invalid-feedback" data-error-for="new"></div></div>
          <div class="col-sm-6"><label class="form-label" for="pw-confirm">Confirm new password</label><input id="pw-confirm" name="confirm" type="password" class="form-control" autocomplete="new-password"><div class="invalid-feedback" data-error-for="confirm"></div></div>
        </div>
        <button type="submit" class="btn btn-brio btn-brio--secondary mt-3">Change password</button>
      </form>
    <?php else: ?>
      <div class="panel-brio small text-secondary">You sign in with Google, so there is no password to manage here.</div>
    <?php endif; ?>
  </section>
<?php endif; ?>
</div>
