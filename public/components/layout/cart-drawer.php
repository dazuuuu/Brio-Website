<aside class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cart-drawer" aria-label="Shopping cart">
  <header class="cart-drawer__header">
    <h2>Your cart <span class="cart-drawer__count">(<span data-cart-count-text>0</span>)</span></h2>
    <button type="button" class="icon-btn" data-bs-dismiss="offcanvas" aria-label="Close cart"><?= icon('close') ?></button>
  </header>

  <!-- Empty -->
  <div class="cart-drawer__state" data-cart-state="empty">
    <h3>Your cart is empty</h3>
    <p>Add something fresh and it will show up here.</p>
    <a href="<?= url('products') ?>" class="btn btn-brio btn-brio--primary">Start shopping</a>
  </div>

  <!-- Items -->
  <ul class="cart-drawer__items" data-cart-state="items" data-cart-items hidden></ul>
  <footer class="cart-drawer__footer" data-cart-state="items" hidden>
    <div class="cart-drawer__subtotal"><span>Subtotal</span><strong data-cart-subtotal>KSh 0</strong></div>
    <p class="cart-drawer__note">Shipping and taxes are calculated at checkout.</p>
    <a href="<?= url('checkout') ?>" class="btn btn-brio btn-brio--primary btn-brio--lg w-100">Checkout</a>
  </footer>
</aside>
