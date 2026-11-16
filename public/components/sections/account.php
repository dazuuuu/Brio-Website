<?php
$customer = site_customer();
?>
<div class="container page" data-account>
<?php if (!$customer): ?>
  <div class="state-box">
    <h1>Sign in to see your account</h1>
    <p>Your contact details and order history live here.</p>
    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a href="<?= url('login?redirect=' . rawurlencode('/account')) ?>" class="btn btn-brio btn-brio--primary">Sign in</a>
      <a href="<?= url('register?redirect=' . rawurlencode('/account')) ?>" class="btn btn-brio btn-brio--secondary">Create an account</a>
    </div>
  </div>
<?php else: ?>
  <header class="page__head">
    <div><p class="eyebrow">My account</p><h1>Hi, <?= e($customer['firstName']) ?></h1></div>
    <button type="button" class="btn btn-brio btn-brio--ghost btn-brio--sm" data-customer-logout>Sign out</button>
  </header>

  <section class="account__section" aria-labelledby="profile-title">
    <h2 id="profile-title">Profile</h2>
    <div class="panel-brio profile">
      <div class="profile__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr($customer['firstName'], 0, 1) . mb_substr($customer['lastName'], 0, 1))) ?></div>
      <div>
        <h3 class="profile__name"><?= e($customer['fullName']) ?></h3>
        <dl class="profile__details">
          <div><dt>Email</dt><dd><?= e($customer['email']) ?></dd></div>
          <div><dt>Phone</dt><dd><?= e($customer['phone'] ?: '—') ?></dd></div>
          <div><dt>Member since</dt><dd><?= e(format_date($customer['createdAt'] ?? 'now')) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>

  <section class="account__section" id="details" aria-labelledby="details-title">
    <h2 id="details-title">My contact details</h2>
    <p class="small text-secondary">We use these to place your orders instantly, so you never have to fill in a form at checkout.</p>
    <form class="panel-brio" data-details-form novalidate>
      <div class="alert auth-alert" role="alert" data-form-error hidden></div>
      <div class="alert alert-success py-2" role="status" data-form-ok hidden>Saved.</div>
      <div class="row g-3 form-group-brio">
        <div class="col-sm-6"><label class="form-label" for="ad-first">First name</label><input id="ad-first" name="firstName" class="form-control" value="<?= e($customer['firstName']) ?>"><div class="invalid-feedback" data-error-for="firstName"></div></div>
        <div class="col-sm-6"><label class="form-label" for="ad-last">Last name</label><input id="ad-last" name="lastName" class="form-control" value="<?= e($customer['lastName']) ?>"></div>
        <div class="col-sm-6"><label class="form-label" for="ad-phone">Phone number</label><input id="ad-phone" name="phone" type="tel" class="form-control" value="<?= e($customer['phone']) ?>" placeholder="+254 700 000 000"><div class="invalid-feedback" data-error-for="phone"></div></div>
        <div class="col-sm-6"><label class="form-label" for="ad-wa">WhatsApp number</label><input id="ad-wa" name="whatsapp" type="tel" class="form-control" value="<?= e($customer['whatsapp']) ?>" placeholder="Same as phone if empty"><div class="invalid-feedback" data-error-for="whatsapp"></div></div>
        <div class="col-12"><label class="form-label" for="ad-address">Delivery location</label><input id="ad-address" name="address" class="form-control" value="<?= e((string)$customer['address']) ?>" placeholder="Estate, street, town"></div>
      </div>
      <button type="submit" class="btn btn-brio btn-brio--primary mt-3" data-save-details>Save details</button>
    </form>
  </section>

  <section class="account__section" aria-labelledby="orders-title">
    <h2 id="orders-title">My orders</h2>
    <div class="table-responsive orders-table-wrap">
      <table class="table orders-table mb-0">
        <caption class="visually-hidden">Your orders</caption>
        <thead><tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Items</th><th scope="col" class="text-end">Total</th><th scope="col">Status</th></tr></thead>
        <tbody data-orders-body><tr><td colspan="5" class="text-center text-secondary py-4">Loading your orders…</td></tr></tbody>
      </table>
    </div>
  </section>

  <section class="account__section" aria-labelledby="pw-title">
    <h2 id="pw-title">Change password</h2>
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
  </section>
<?php endif; ?>
</div>
