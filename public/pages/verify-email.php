<?php
// Opened from the link in the confirmation email: /verify-email?token=...
$state = 'bad';
$who = site_customer();
try {
    brio('customers')->verifyEmail((string)($_GET['token'] ?? ''));
    $state = 'ok';
} catch (Throwable $e) {
    // Already used, expired or mistyped. If they are signed in and already confirmed, that is fine too.
    if ($who && $who['emailVerified']) $state = 'ok';
}
?>
<div class="container page">
  <div class="state-box">
    <?php if ($state === 'ok'): ?>
      <p class="eyebrow">All set</p>
      <h1>Your email is confirmed</h1>
      <p>Thank you! Your account is verified. You can now track your orders and check out faster.</p>
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="<?= url('account') ?>" class="btn btn-brio btn-brio--primary">Go to my account</a>
        <a href="<?= url('products') ?>" class="btn btn-brio btn-brio--secondary">Keep shopping</a>
      </div>
    <?php else: ?>
      <p class="eyebrow">Link problem</p>
      <h1>This link doesn’t work</h1>
      <p>It may have expired (links last 48 hours) or already been used. Sign in and press “Resend email” on your account page to get a fresh one.</p>
      <div class="d-flex flex-wrap justify-content-center gap-2">
        <a href="<?= url('account') ?>" class="btn btn-brio btn-brio--primary">Go to my account</a>
        <a href="<?= url() ?>" class="btn btn-brio btn-brio--secondary">Back to home</a>
      </div>
    <?php endif; ?>
  </div>
</div>
