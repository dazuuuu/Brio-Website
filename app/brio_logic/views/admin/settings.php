<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf */
$title = 'Settings';
$active = 'settings';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-settings>
      <div class="adm-pagehead">
        <div><h1>Settings</h1><p>Store-wide options.</p></div>
      </div>

      <form class="adm-card p-4 adm-settings" data-settings-form novalidate>
        <h2 class="adm-section-title">WhatsApp orders</h2>
        <p class="small text-secondary">Customers can finish their order on WhatsApp. This is the number they’ll message. Leave it empty to hide the WhatsApp button at checkout.</p>
        <div class="alert adm-alert adm-alert--error" data-form-error hidden></div>
        <div class="mb-3">
          <label class="form-label adm-label" for="st-number">WhatsApp number</label>
          <input id="st-number" name="whatsapp_number" class="form-control adm-input" inputmode="tel" placeholder="+254 700 123 456" autocomplete="off">
          <div class="invalid-feedback d-block" data-error-for="whatsapp_number"></div>
          <div class="form-text">Include the country code. Spaces and + are fine.</div>
        </div>
        <div class="mb-3">
          <label class="form-label adm-label" for="st-greeting">First line of the WhatsApp message</label>
          <textarea id="st-greeting" name="whatsapp_greeting" rows="2" class="form-control adm-input" maxlength="300"></textarea>
          <div class="invalid-feedback d-block" data-error-for="whatsapp_greeting"></div>
          <div class="form-text">The order number, items, total and the customer’s details are added automatically underneath.</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button type="submit" class="btn adm-btn adm-btn--primary" data-save>Save settings</button>
          <a class="btn adm-btn adm-btn--ghost" data-test-link target="_blank" rel="noopener" hidden>Test the WhatsApp link</a>
        </div>
      </form>

      <form class="adm-card p-4 adm-settings mt-3" data-header-form novalidate>
        <h2 class="adm-section-title">Header</h2>
        <p class="small text-secondary">Choose what shows in the top bar. The links under your logo are managed in Navigation.</p>
        <div class="form-check form-switch adm-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="st-search" name="show_search"><label class="form-check-label" for="st-search">Search box</label></div>
        <div class="form-check form-switch adm-switch mb-3"><input class="form-check-input" type="checkbox" role="switch" id="st-account" name="show_account_icon"><label class="form-check-label" for="st-account">Account icon <small class="d-block text-secondary">The person icon that opens sign in / My account.</small></label></div>
        <button type="submit" class="btn adm-btn adm-btn--primary" data-save-header>Save header</button>
      </form>

      <form class="adm-card p-4 adm-settings mt-3" data-accent-form novalidate>
        <h2 class="adm-section-title">Admin panel colour</h2>
        <p class="small text-secondary">Pick any colour for buttons, highlights and the sidebar menu. The preview updates as you choose. This only changes the admin, not your store.</p>
        <div class="adm-swatches mb-3" role="group" aria-label="Admin colour">
          <?php foreach ([['#f25c1f', 'Orange (default)'], ['#3466e0', 'Blue'], ['#1e8e4e', 'Green'], ['#7a4ce0', 'Purple'], ['#0f9d9a', 'Teal'], ['#d6336c', 'Pink'], ['#334155', 'Slate'], ['#111111', 'Black']] as [$hex, $name]): ?>
            <button type="button" class="adm-swatch" data-accent-swatch="<?= $hex ?>" style="--c:<?= $hex ?>" aria-label="<?= $name ?>" title="<?= $name ?>"></button>
          <?php endforeach; ?>
          <label class="adm-swatch adm-swatch--custom" title="Pick any colour"><input type="color" name="admin_accent" value="#f25c1f" data-accent-input aria-label="Custom admin colour"></label>
          <code class="ms-2" data-accent-hex>#f25c1f</code>
        </div>
        <div class="alert adm-alert adm-alert--error" data-accent-error hidden></div>
        <div class="d-flex flex-wrap gap-2">
          <button type="submit" class="btn adm-btn adm-btn--primary" data-save-accent>Save colour</button>
          <button type="button" class="btn adm-btn adm-btn--ghost" data-reset-accent>Back to default</button>
        </div>
      </form>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>
<?php $scripts = ['settings.js']; require __DIR__ . '/_foot.php'; ?>
