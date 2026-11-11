<?php $wa = site_whatsapp_enabled(); ?>
<div class="container page" data-checkout-page>
  <header class="page__head">
    <div><p class="eyebrow">Checkout</p><h1>Complete your order</h1>
      <p class="page__sub">Tell us where to reach you<?= $wa ? ', or send your order straight to WhatsApp' : '' ?>. We’ll confirm delivery with you.</p></div>
  </header>

  <div class="state-box" data-checkout-empty hidden>
    <h3>Your cart is empty</h3><p>Add something fresh and come back to check out.</p>
    <a href="<?= url('products') ?>" class="btn btn-brio btn-brio--primary">Start shopping</a>
  </div>

  <div class="row g-4" data-checkout-main hidden>
    <div class="col-lg-7">
      <form class="checkout-card" data-checkout-form novalidate>
        <h2 class="checkout-card__title">Your details</h2>
        <div class="alert auth-alert" role="alert" data-checkout-error hidden></div>
        <div class="row g-3">
          <div class="col-12 form-group-brio"><label class="form-label" for="co-name">Full name *</label>
            <input id="co-name" name="name" class="form-control" autocomplete="name" placeholder="e.g. Jane Wanjiku"><div class="invalid-feedback" data-error-for="name"></div></div>
          <div class="col-sm-6 form-group-brio"><label class="form-label" for="co-phone">Phone number *</label>
            <input id="co-phone" name="phone" type="tel" class="form-control" autocomplete="tel" placeholder="+254 700 000 000"><div class="invalid-feedback" data-error-for="phone"></div></div>
          <div class="col-sm-6 form-group-brio"><label class="form-label" for="co-wa">WhatsApp number *</label>
            <input id="co-wa" name="whatsapp" type="tel" class="form-control" placeholder="+254 700 000 000"><div class="invalid-feedback" data-error-for="whatsapp"></div>
            <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="co-same" checked><label class="form-check-label small" for="co-same">Same as my phone number</label></div></div>
          <div class="col-12 form-group-brio"><label class="form-label" for="co-address">Delivery location / address <span class="text-secondary fw-normal">(optional)</span></label>
            <input id="co-address" name="address" class="form-control" autocomplete="street-address" placeholder="Estate, street, town"></div>
          <div class="col-12 form-group-brio"><label class="form-label" for="co-notes">Order notes <span class="text-secondary fw-normal">(optional)</span></label>
            <textarea id="co-notes" name="notes" rows="2" class="form-control" maxlength="500"></textarea></div>
        </div>
        <div class="visually-hidden" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="checkout-actions">
          <button type="submit" class="btn btn-brio btn-brio--primary btn-brio--lg w-100" data-place>Place order</button>
          <?php if ($wa): ?>
            <div class="checkout-or"><span>or</span></div>
            <button type="button" class="btn btn-brio btn-brio--whatsapp btn-brio--lg w-100" data-whatsapp>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.200Zm4.500-6.100c-.2-.1-1.500-.7-1.700-.8-.2-.1-.4-.1-.6.100l-.8 1c-.1.200-.3.200-.5.100a6.700 6.700 0 0 1-3.300-2.900c-.2-.4.200-.4.700-1.300.1-.2 0-.3 0-.5l-.8-1.800c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.300 3 3 0 0 0-.9 2.200 5.200 5.200 0 0 0 1.100 2.800 11.900 11.900 0 0 0 4.600 4c1.700.7 2.400.8 3.300.6.500-.1 1.500-.6 1.700-1.200.2-.6.200-1.100.1-1.200-.1-.2-.2-.2-.5-.4Z"/></svg>
              Order via WhatsApp
            </button>
            <p class="checkout-note">No form needed: your cart opens in WhatsApp as a ready-to-send message.</p>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <aside class="col-lg-5">
      <div class="checkout-card checkout-summary">
        <h2 class="checkout-card__title">Order summary</h2>
        <ul class="checkout-items" data-checkout-items></ul>
        <div class="checkout-total"><span>Total</span><strong data-checkout-total>KSh 0</strong></div>
        <p class="checkout-note mb-0">Delivery fees, if any, are confirmed when we contact you.</p>
      </div>
    </aside>
  </div>

  <div class="checkout-done" data-checkout-done hidden>
    <span class="checkout-done__icon"><?= icon('check', 30) ?></span>
    <h2>Thank you!</h2>
    <p>Your order <strong data-done-number></strong> has been received.</p>
    <p class="text-secondary" data-done-text></p>
    <div class="d-flex flex-wrap justify-content-center gap-2">
      <a class="btn btn-brio btn-brio--whatsapp" data-done-wa href="#" target="_blank" rel="noopener" hidden>Send it on WhatsApp</a>
      <a class="btn btn-brio btn-brio--primary" href="<?= url('products') ?>">Continue shopping</a>
    </div>
  </div>
</div>
