<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf */
$title = 'Pop-up Ads';
$active = 'ads';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>" data-ads>
      <div class="adm-pagehead">
        <div><h1>Pop-up Ads</h1><p>Full-screen pop-ups that appear when a shopper visits. They close with one tap.</p></div>
        <div class="adm-pagehead__actions"><button class="btn adm-btn adm-btn--primary" data-new-ad><?= adm_icon('plus', 16) ?> Create Ad</button></div>
      </div>

      <div class="adm-note">
        <strong>Two kinds of ad</strong>
        A <b>product ad</b> shows one of your products with its photo, name and offer price, and opens that product when tapped.
        A <b>custom ad</b> shows a picture or a short video (or just a message) and can link anywhere. Ads never appear on checkout, login or account pages.
      </div>

      <div class="adm-slides" data-ads-list aria-live="polite"><div class="adm-empty adm-card">Loading ads…</div></div>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>

<div class="modal fade" id="ad-modal" tabindex="-1" aria-labelledby="ad-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <form class="modal-content adm-modal" data-ad-form novalidate>
      <div class="modal-header">
        <h2 class="modal-title" id="ad-modal-title" data-modal-title>Create Ad</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert adm-alert adm-alert--error" data-form-error hidden></div>

        <label class="form-label adm-label" for="ad-name">Ad name <span class="text-secondary fw-normal">(only you see this)</span></label>
        <input id="ad-name" name="name" class="form-control adm-input mb-1" maxlength="120" placeholder="e.g. December serum offer">
        <div class="invalid-feedback d-block mb-2" data-error-for="name"></div>

        <p class="adm-label mt-3 mb-2">What kind of ad?</p>
        <div class="adm-kinds" role="radiogroup" aria-label="Kind of ad">
          <label class="adm-kind"><input type="radio" name="kind" value="product" checked><span><b>Product ad</b><small>Show one of your products with its offer price</small></span></label>
          <label class="adm-kind"><input type="radio" name="kind" value="custom"><span><b>Custom ad</b><small>A picture, video or message that can link anywhere</small></span></label>
        </div>

        <!-- Product ad -->
        <div data-kind-section="product" class="mt-3">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label adm-label" for="ad-product">Product</label>
              <select id="ad-product" name="productId" class="form-select adm-input"></select>
              <div class="invalid-feedback d-block" data-error-for="productId"></div>
              <div class="adm-adproduct" data-product-preview hidden></div>
            </div>
            <div class="col-sm-6"><label class="form-label adm-label" for="ad-offer-text">Offer badge <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-offer-text" name="offerText" class="form-control adm-input" maxlength="60" placeholder="e.g. 20% OFF or Today only"></div>
            <div class="col-sm-6"><label class="form-label adm-label" for="ad-offer-price">Offer price, KSh <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-offer-price" name="offerPrice" type="number" min="0" step="0.01" class="form-control adm-input" placeholder="Leave empty to use the normal price"><div class="invalid-feedback d-block" data-error-for="offerPrice"></div></div>
            <div class="col-sm-6"><label class="form-label adm-label" for="ad-headline-p">Headline <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-headline-p" name="headline" class="form-control adm-input" maxlength="140" placeholder="Defaults to the product name" data-headline-product></div>
            <div class="col-sm-6"><label class="form-label adm-label" for="ad-cta-p">Button text</label><input id="ad-cta-p" name="ctaLabel" class="form-control adm-input" maxlength="60" placeholder="Shop now" data-cta-product></div>
          </div>
        </div>

        <!-- Custom ad -->
        <div data-kind-section="custom" class="mt-3" hidden>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label adm-label">Picture or video</label>
              <div class="adm-admedia" data-media-preview><span data-media-empty><?= adm_icon('image', 26) ?><br>Choose a picture or video</span></div>
              <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                <label class="btn adm-btn adm-btn--ghost adm-btn--sm mb-0" for="ad-media"><?= adm_icon('upload', 14) ?> Upload</label>
                <input id="ad-media" name="media" type="file" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" class="visually-hidden" data-media-input>
                <span class="small text-secondary" data-media-name>JPG, PNG or WebP up to 4 MB, or an MP4/WebM video up to 25 MB.</span>
                <label class="form-check mb-0 small" data-remove-media-wrap hidden><input class="form-check-input" type="checkbox" name="removeMedia" value="1"> <span class="form-check-label">Remove it</span></label>
              </div>
              <div class="invalid-feedback d-block" data-error-for="media"></div>
            </div>
            <div class="col-12"><label class="form-label adm-label" for="ad-headline-c">Headline <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-headline-c" name="headline" class="form-control adm-input" maxlength="140" placeholder="e.g. Holiday gift sets are here" data-headline-custom><div class="invalid-feedback d-block" data-error-for="headline"></div></div>
            <div class="col-12"><label class="form-label adm-label" for="ad-body">Message <span class="text-secondary fw-normal">(optional)</span></label><textarea id="ad-body" name="body" rows="3" maxlength="500" class="form-control adm-input"></textarea></div>
            <div class="col-md-6" data-link-slot></div>
            <div class="col-md-6" data-cta-custom><label class="form-label adm-label" for="ad-cta-c">Button text</label><input id="ad-cta-c" name="ctaLabel" class="form-control adm-input" maxlength="60" placeholder="Learn more"></div>
            <div class="invalid-feedback d-block" data-error-for="linkTarget"></div>
          </div>
        </div>

        <hr class="my-4">
        <h3 class="adm-section-title">When to show it</h3>
        <div class="row g-3">
          <div class="col-sm-6"><label class="form-label adm-label" for="ad-frequency">How often</label>
            <select id="ad-frequency" name="frequency" class="form-select adm-input"><option value="session">Once per visit</option><option value="day">Once a day per shopper</option><option value="always">Every time a page loads</option></select></div>
          <div class="col-sm-6"><label class="form-label adm-label" for="ad-pages">Where</label>
            <select id="ad-pages" name="pages" class="form-select adm-input"><option value="home">Home page only</option><option value="all">Every page (except checkout and account)</option></select></div>
          <div class="col-sm-4"><label class="form-label adm-label" for="ad-delay">Wait (seconds)</label><input id="ad-delay" name="delaySeconds" type="number" min="0" max="30" value="2" class="form-control adm-input"><div class="invalid-feedback d-block" data-error-for="delaySeconds"></div></div>
          <div class="col-sm-4"><label class="form-label adm-label" for="ad-starts">Starts <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-starts" name="startsAt" type="datetime-local" class="form-control adm-input"><div class="invalid-feedback d-block" data-error-for="startsAt"></div></div>
          <div class="col-sm-4"><label class="form-label adm-label" for="ad-ends">Ends <span class="text-secondary fw-normal">(optional)</span></label><input id="ad-ends" name="endsAt" type="datetime-local" class="form-control adm-input"><div class="invalid-feedback d-block" data-error-for="endsAt"></div></div>
        </div>
        <div class="form-check form-switch adm-switch mt-4">
          <input class="form-check-input" type="checkbox" role="switch" id="ad-active" name="isActive" checked>
          <label class="form-check-label" for="ad-active">Show this ad on the store</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn adm-btn adm-btn--ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn adm-btn adm-btn--primary" data-save>Save ad</button>
      </div>
    </form>
  </div>
</div>
<?php $scripts = ['ads.js']; require __DIR__ . '/_foot.php'; ?>
