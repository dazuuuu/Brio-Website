<?php
/** @var string $step 'database'|'admin' @var array $errors @var array $old @var ?string $notice */
$title = 'Setup';
$token = \Brio\Http\Session::csrfToken();
$field = static function (array $f) use ($errors, $old): void { extract($f); require __DIR__ . '/_field.php'; };
require __DIR__ . '/_head.php';
?>
<body class="adm-auth-body">
<main class="adm-auth">
  <div class="adm-auth__brand"><span class="adm-logo"><?= adm_icon('bag', 20) ?></span><div><strong>Brio</strong><small>Admin setup</small></div></div>

  <div class="adm-card adm-auth__card">
    <ol class="adm-steps" aria-label="Setup progress">
      <li class="<?= $step === 'database' ? 'is-current' : 'is-done' ?>"><span>1</span> Database</li>
      <li class="<?= $step === 'admin' ? 'is-current' : '' ?>"><span>2</span> Admin account</li>
      <li><span>3</span> Sign in</li>
    </ol>

    <?php if ($notice): ?><div class="alert adm-alert adm-alert--ok" role="status"><?= h($notice) ?></div><?php endif; ?>

    <?php if ($step === 'database'): ?>
      <?php $failed = array_filter($checks ?? [], static fn($c) => !$c['ok']); ?>
      <?php if ($checks ?? []): ?>
        <details class="adm-checks"<?= $failed ? ' open' : '' ?>>
          <summary><?= $failed ? '⚠ ' . count($failed) . ' thing' . (count($failed) === 1 ? '' : 's') . ' to fix on this server' : '✓ This server is ready' ?></summary>
          <ul>
            <?php foreach ($checks as $c): ?>
              <li class="<?= $c['ok'] ? 'is-ok' : 'is-bad' ?>"><?= $c['ok'] ? '✓' : '✕' ?> <?= h($c['label']) ?><?php if (!$c['ok']): ?><small><?= h($c['hint']) ?></small><?php endif; ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      <?php endif; ?>
      <h1 class="adm-auth__title">Connect your database</h1>
      <p class="adm-auth__sub">Enter your MySQL details. We’ll create the database if it doesn’t exist, run the migrations and add the starter products.</p>
      <?php if (!empty($errors['connection'])): ?><div class="alert adm-alert adm-alert--error" role="alert"><?= h($errors['connection'][0]) ?></div><?php endif; ?>
      <form method="post" action="<?= setup_url('database') ?>" novalidate>
        <input type="hidden" name="_token" value="<?= h($token) ?>">
        <div class="row g-3">
          <div class="col-sm-8"><?php $field(['name' => 'host', 'label' => 'Host', 'value' => $old['host'] ?? '127.0.0.1', 'placeholder' => '127.0.0.1', 'hint' => 'On most web hosts this is localhost.']); ?></div>
          <div class="col-sm-4"><?php $field(['name' => 'port', 'label' => 'Port', 'type' => 'number', 'value' => $old['port'] ?? '3306']); ?></div>
        </div>
        <?php $field(['name' => 'database', 'label' => 'Database name', 'placeholder' => 'brio_store', 'required' => true, 'hint' => 'On shared hosting, create the database in your control panel first and enter its full name (it often starts with your account name).']); ?>
        <?php $field(['name' => 'username', 'label' => 'Database username', 'placeholder' => 'root', 'required' => true, 'autocomplete' => 'off']); ?>
        <?php $field(['name' => 'password', 'label' => 'Database password', 'type' => 'password', 'placeholder' => 'Leave empty if none', 'autocomplete' => 'new-password']); ?>
        <button type="submit" class="btn adm-btn adm-btn--primary w-100 mt-2" data-loading-text="Running migrations…">Run migrations &amp; continue</button>
      </form>
    <?php else: ?>
      <h1 class="adm-auth__title">Create your admin account</h1>
      <p class="adm-auth__sub">This is the account you’ll use to manage products, daily deals and featured items.</p>
      <form method="post" action="<?= setup_url('admin') ?>" novalidate>
        <input type="hidden" name="_token" value="<?= h($token) ?>">
        <?php $field(['name' => 'name', 'label' => 'Your name', 'placeholder' => 'e.g. Amani Kariuki', 'required' => true, 'autocomplete' => 'name']); ?>
        <?php $field(['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'you@example.com', 'required' => true, 'autocomplete' => 'email']); ?>
        <?php $field(['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true, 'hint' => 'At least 8 characters.', 'autocomplete' => 'new-password']); ?>
        <?php $field(['name' => 'confirm', 'label' => 'Confirm password', 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password']); ?>
        <button type="submit" class="btn adm-btn adm-btn--primary w-100 mt-2">Create admin account</button>
      </form>
    <?php endif; ?>
  </div>
</main>
<script>
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('[type=submit]');
      b.disabled = true;
      b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>' + (b.dataset.loadingText || 'Please wait…');
    });
  });
</script>
</body>
</html>
