<?php
$c = demo_customer();
$orders = demo_orders();
$statusLabels = ['processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
?>
<div class="container page" data-account>
  <header class="page__head">
    <div>
      <p class="eyebrow">My account</p>
      <h1 data-account-greeting>Hi, <?= e($c['firstName']) ?></h1>
    </div>
    <button type="button" class="btn btn-brio btn-brio--ghost btn-brio--sm" data-sign-out>Sign out</button>
  </header>

  <section class="account__section" aria-labelledby="profile-title">
    <h2 id="profile-title">Profile</h2>
    <div class="panel-brio profile">
      <div class="profile__avatar" aria-hidden="true" data-profile-initials><?= e(strtoupper($c['firstName'][0] . $c['lastName'][0])) ?></div>
      <div>
        <h3 class="profile__name" data-profile-name><?= e($c['firstName'] . ' ' . $c['lastName']) ?></h3>
        <dl class="profile__details">
          <div><dt>Email</dt><dd data-profile-email><?= e($c['email']) ?></dd></div>
          <div><dt>Phone</dt><dd data-profile-phone><?= e($c['phone'] ?? '—') ?></dd></div>
          <div><dt>Member since</dt><dd data-profile-since><?= e(format_date($c['memberSince'])) ?></dd></div>
        </dl>
      </div>
    </div>
  </section>

  <section class="account__section" aria-labelledby="addresses-title">
    <h2 id="addresses-title">Saved addresses</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3" data-addresses>
      <?php foreach ($c['addresses'] as $a): ?>
        <div class="col">
          <address class="panel-brio address h-100">
            <div class="address__top"><strong><?= e($a['label']) ?></strong><?php if ($a['isDefault']): ?><span class="pill">Default</span><?php endif; ?></div>
            <p><?= e($a['recipient']) ?></p>
            <p><?= e($a['line1']) ?><?= $a['line2'] ? ', ' . e($a['line2']) : '' ?></p>
            <p><?= e("{$a['city']}, {$a['region']} {$a['postalCode']}") ?></p>
            <p><?= e($a['country']) ?></p>
          </address>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="state-box" data-addresses-empty hidden><p>You haven’t saved any addresses yet.</p></div>
  </section>

  <section class="account__section" aria-labelledby="orders-title">
    <h2 id="orders-title">Order history</h2>
    <div class="table-responsive orders-table-wrap">
      <table class="table orders-table mb-0">
        <caption class="visually-hidden">Your orders</caption>
        <thead>
          <tr><th scope="col">Order</th><th scope="col">Date</th><th scope="col">Items</th><th scope="col" class="text-end">Total</th><th scope="col">Status</th></tr>
        </thead>
        <tbody data-orders-body>
          <?php foreach ($orders as $o): ?>
            <tr>
              <th scope="row"><?= e($o['orderNumber']) ?></th>
              <td><?= e(format_date($o['createdAt'])) ?></td>
              <td><ul class="orders-table__items"><?php foreach ($o['items'] as $i): ?><li><?= (int)$i['quantity'] ?> × <?= e($i['name']) ?></li><?php endforeach; ?></ul></td>
              <td class="text-end"><?= money($o['total']) ?></td>
              <td><span class="status status--<?= e($o['status']) ?>"><?= e($statusLabels[$o['status']]) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
