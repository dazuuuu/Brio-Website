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

      <form class="adm-card p-4 adm-settings adm-settings--wide mb-3" data-brand-form novalidate>
        <h2 class="adm-section-title">Store identity</h2>
        <p class="small text-secondary">Your store name, tagline and logos. Changes show on your store straight away.</p>
        <div class="alert adm-alert adm-alert--error" data-brand-error hidden></div>
        <div class="row g-3 mb-4">
          <div class="col-sm-6"><label class="form-label adm-label" for="st-name">Store name</label><input id="st-name" name="store_name" class="form-control adm-input" maxlength="60"><div class="invalid-feedback d-block" data-error-for="store_name"></div></div>
          <div class="col-sm-6"><label class="form-label adm-label" for="st-tagline">Tagline</label><input id="st-tagline" name="store_tagline" class="form-control adm-input" maxlength="120" placeholder="e.g. Natural skin &amp; body care"><div class="invalid-feedback d-block" data-error-for="store_tagline"></div></div>
        </div>

        <div class="adm-slots">
          <?php foreach ([
              ['logo', 'Logo', 'Shown in the header and footer. A PNG with a transparent background works best.', 'light'],
              ['logo-dark', 'Logo for the dark theme', 'Optional. Without it, your logo sits on a light chip in dark mode.', 'dark'],
              ['icon', 'Site icon (favicon)', 'A square image, at least 512 × 512. Used in browser tabs and on phone home screens.', 'light'],
          ] as [$slot, $label, $hint, $bg]): ?>
            <div class="adm-slot" data-slot="<?= $slot ?>">
              <div class="adm-slot__preview adm-slot__preview--<?= $bg ?>"><img data-slot-img alt="" hidden></div>
              <div class="adm-slot__body">
                <strong><?= h($label) ?></strong>
                <small><?= h($hint) ?></small>
                <span class="adm-slot__state" data-slot-state></span>
                <div class="d-flex flex-wrap gap-2 mt-1">
                  <label class="btn adm-btn adm-btn--ghost adm-btn--sm mb-0"><?= adm_icon('upload', 14) ?> Upload<input type="file" accept="image/png,image/jpeg,image/webp" class="visually-hidden" data-slot-input></label>
                  <button type="button" class="btn adm-btn adm-btn--ghost adm-btn--sm text-danger" data-slot-clear hidden>Use default</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <h3 class="adm-section-title mt-4">Picture shown when someone shares a link</h3>
        <p class="small text-secondary">When a page is shared on WhatsApp, Facebook or X, this picture appears with it. Product pages always share that product.</p>
        <div class="adm-radios" role="radiogroup" aria-label="Share picture">
          <label class="form-check"><input class="form-check-input" type="radio" name="share_image" value="popular"> <span class="form-check-label"><b>Best-selling product</b> <small class="text-secondary">(recommended)</small></span></label>
          <label class="form-check"><input class="form-check-input" type="radio" name="share_image" value="logo"> <span class="form-check-label"><b>My logo</b></span></label>
          <label class="form-check"><input class="form-check-input" type="radio" name="share_image" value="custom" data-share-custom> <span class="form-check-label"><b>A picture I upload</b></span></label>
        </div>
        <div class="adm-slot mt-2" data-slot="share-image">
          <div class="adm-slot__preview adm-slot__preview--light"><img data-slot-img alt="" hidden></div>
          <div class="adm-slot__body">
            <small>Best at 1200 × 630 pixels.</small>
            <span class="adm-slot__state" data-slot-state></span>
            <div class="d-flex flex-wrap gap-2 mt-1">
              <label class="btn adm-btn adm-btn--ghost adm-btn--sm mb-0"><?= adm_icon('upload', 14) ?> Upload<input type="file" accept="image/png,image/jpeg,image/webp" class="visually-hidden" data-slot-input></label>
              <button type="button" class="btn adm-btn adm-btn--ghost adm-btn--sm text-danger" data-slot-clear hidden>Remove</button>
            </div>
          </div>
        </div>
        <button type="submit" class="btn adm-btn adm-btn--primary mt-4" data-save-brand>Save identity</button>
      </form>

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

      <form class="adm-card p-4 adm-settings mt-3" data-google-form novalidate>
        <h2 class="adm-section-title">Sign in with Google</h2>
        <p class="small text-secondary">Lets customers create an account or sign in with one tap. It needs a free Google “Client ID”: in <b>Google Cloud Console</b> go to APIs &amp; Services → Credentials → Create credentials → OAuth client ID → <b>Web application</b>, and add your website address under “Authorised JavaScript origins”. Then paste the Client ID below. Leave it empty to hide the Google button.</p>
        <div class="alert adm-alert adm-alert--error" data-google-error hidden></div>
        <div class="mb-2"><label class="form-label adm-label" for="st-google">Google Client ID</label>
          <input id="st-google" name="google_client_id" class="form-control adm-input" placeholder="123456789012-abc123.apps.googleusercontent.com" autocomplete="off" spellcheck="false">
          <div class="invalid-feedback d-block" data-error-for="google_client_id"></div>
          <div class="form-text">Authorised origin to add in Google: <code data-origin></code></div></div>
        <button type="submit" class="btn adm-btn adm-btn--primary" data-save-google>Save</button>
      </form>

      <form class="adm-card p-4 adm-settings mt-3" data-mail-form novalidate>
        <h2 class="adm-section-title">Email (account confirmation)</h2>
        <p class="small text-secondary">New customers get an email with a link to confirm their address. For emails to arrive reliably, create a mailbox on your hosting (e.g. <code>no-reply@yourstore.com</code>) and enter its details below; your host shows them under Emails → Configure mail client. Leave the server empty to use the host’s basic mail, which often lands in spam.</p>
        <div class="alert adm-alert adm-alert--error" data-mail-error hidden></div>
        <div class="row g-3">
          <div class="col-sm-8"><label class="form-label adm-label" for="st-smtp-host">Mail server</label>
            <input id="st-smtp-host" name="smtp_host" class="form-control adm-input" placeholder="smtp.hostinger.com" autocomplete="off" spellcheck="false"><div class="invalid-feedback d-block" data-error-for="smtp_host"></div></div>
          <div class="col-6 col-sm-2"><label class="form-label adm-label" for="st-smtp-port">Port</label>
            <input id="st-smtp-port" name="smtp_port" class="form-control adm-input" inputmode="numeric" placeholder="465"><div class="invalid-feedback d-block" data-error-for="smtp_port"></div></div>
          <div class="col-6 col-sm-2"><label class="form-label adm-label" for="st-smtp-secure">Security</label>
            <select id="st-smtp-secure" name="smtp_secure" class="form-select adm-input"><option value="ssl">SSL</option><option value="tls">TLS</option><option value="none">None</option></select></div>
          <div class="col-sm-6"><label class="form-label adm-label" for="st-smtp-user">Mailbox (username)</label>
            <input id="st-smtp-user" name="smtp_user" class="form-control adm-input" placeholder="no-reply@yourstore.com" autocomplete="off" spellcheck="false"></div>
          <div class="col-sm-6"><label class="form-label adm-label" for="st-smtp-pass">Mailbox password</label>
            <input id="st-smtp-pass" name="smtp_pass" type="password" class="form-control adm-input" autocomplete="new-password" placeholder="" data-pass><div class="form-text" data-pass-note></div></div>
          <div class="col-12"><label class="form-label adm-label" for="st-mail-from">Send from</label>
            <input id="st-mail-from" name="mail_from" class="form-control adm-input" placeholder="no-reply@yourstore.com" autocomplete="off" spellcheck="false"><div class="invalid-feedback d-block" data-error-for="mail_from"></div></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button type="submit" class="btn adm-btn adm-btn--primary" data-save-mail>Save</button>
        </div>
        <hr class="my-3">
        <label class="form-label adm-label" for="st-mail-test">Send a test email</label>
        <div class="d-flex flex-wrap gap-2">
          <input id="st-mail-test" type="email" class="form-control adm-input" style="max-width:320px" placeholder="you@example.com" data-test-to>
          <button type="button" class="btn adm-btn adm-btn--ghost" data-test-mail>Send test</button>
        </div>
        <div class="small mt-2" data-test-result role="status"></div>
      </form>

      <section class="adm-card p-4 adm-settings mt-3" data-transfer>
        <h2 class="adm-section-title">Move content between sites</h2>
        <p class="small text-secondary">Built your products, pages, menus, ads and settings on your test site? <b>Export</b> them here as one file, then open this page on your live site and <b>Import</b> it. Pictures and videos you uploaded come along. Orders, customers, admin accounts, your mail settings and your Google ID stay as they are on each site.</p>
        <div class="d-flex flex-wrap gap-2 mb-3"><button type="button" class="btn adm-btn adm-btn--primary" data-export>Download export file</button></div>
        <hr class="my-3">
        <label class="form-label adm-label" for="st-import">Import a file (replaces this site’s products, hero slides, pages, menus, ads and settings)</label>
        <div class="alert adm-alert adm-alert--error" data-transfer-error hidden></div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <input id="st-import" type="file" accept=".zip,application/zip" class="form-control adm-input" style="max-width:340px" data-import-file>
          <button type="button" class="btn adm-btn adm-btn--ghost" data-import>Import</button>
        </div>
        <div class="small mt-2" data-transfer-result role="status"></div>
      </section>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>
<?php $scripts = ['settings.js', 'brand.js']; require __DIR__ . '/_foot.php'; ?>
