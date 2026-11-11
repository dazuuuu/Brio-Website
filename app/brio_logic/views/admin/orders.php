<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf */
$title = 'Orders';
$active = 'orders';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-orders>
      <div class="adm-pagehead">
        <div><h1>Orders</h1><p>Orders placed from your checkout page, by form or WhatsApp.</p></div>
      </div>
      <div class="adm-toolbar">
        <div class="adm-pills" role="tablist" aria-label="Filter orders" data-order-tabs>
          <?php foreach ([['all', 'All'], ['new', 'New'], ['contacted', 'Contacted'], ['completed', 'Completed'], ['cancelled', 'Cancelled']] as $i => [$v, $l]): ?>
            <button type="button" class="adm-pill<?= $i === 0 ? ' is-active' : '' ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" data-status="<?= $v ?>"><?= $l ?> <span data-count="<?= $v ?>"></span></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="adm-card adm-table-card">
        <div class="table-responsive">
          <table class="table adm-table align-middle mb-0">
            <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Via</th><th scope="col">Status</th><th scope="col">Placed</th><th scope="col"></th></tr></thead>
            <tbody data-orders-body><tr><td class="adm-empty" colspan="8">Loading orders…</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="adm-pager"><span data-pager-info>&nbsp;</span><nav aria-label="Pagination"><ul class="adm-pager__list" data-pager></ul></nav></div>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>

<div class="modal fade" id="order-modal" tabindex="-1" aria-labelledby="order-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <div class="modal-content adm-modal">
      <div class="modal-header"><h2 class="modal-title" id="order-modal-title" data-order-title>Order</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body" data-order-body></div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn adm-btn adm-btn--ghost text-danger" data-order-delete><?= adm_icon('trash', 16) ?> Delete order</button>
        <button type="button" class="btn adm-btn adm-btn--ghost" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php $scripts = ['orders.js']; require __DIR__ . '/_foot.php'; ?>
