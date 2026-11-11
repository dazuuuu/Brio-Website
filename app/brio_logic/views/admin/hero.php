<?php
/** @var \Brio\Domain\Admin\Admin $admin @var string $csrf */
$title = 'Hero Slides';
$active = 'hero';
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main" data-admin-root data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>" data-base="<?= h(brio_base()) ?>">
      <div class="adm-pagehead">
        <div><h1>Hero Slides</h1><p>Upload full-slide images for the top of your home page. The button is optional.</p></div>
        <div class="adm-pagehead__actions">
          <button class="btn adm-btn adm-btn--primary" data-new-slide><?= adm_icon('plus', 16) ?> Add Slide</button>
        </div>
      </div>

      <div class="adm-note">
        <strong>Image tips</strong>
        Use a landscape image about <b>1920 × 704 px</b> (the same shape as your current hero) (JPG, PNG or WebP, up to 4 MB). The image is the whole slide, so design it like your current hero: product on the left, text on the right. The button always sits under the subtitle on the right, so leave that spot clear.
        Optionally add a square <b>800 × 800 px</b> version for phones. Slides play in the order below.
      </div>

      <div class="adm-slides" data-hero-list aria-live="polite"><div class="adm-empty adm-card">Loading slides…</div></div>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>

<div class="modal fade" id="slide-modal" tabindex="-1" aria-labelledby="slide-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <form class="modal-content adm-modal" data-slide-form novalidate>
      <div class="modal-header">
        <h2 class="modal-title" id="slide-modal-title" data-modal-title>Add Slide</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert adm-alert adm-alert--error" data-form-error hidden></div>

        <label class="form-label adm-label">Slide image *</label>
        <div class="adm-slide-preview" data-preview>
          <img data-preview-img alt="" hidden>
          <span class="adm-slide-preview__empty" data-preview-empty><?= adm_icon('image', 28) ?><br>Choose an image</span>
          <span class="adm-slide-preview__cta" data-preview-cta hidden></span>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-2 align-items-center">
          <label class="btn adm-btn adm-btn--ghost adm-btn--sm mb-0" for="sf-image"><?= adm_icon('export', 14) ?> Upload image</label>
          <input id="sf-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-image-input>
          <span class="small text-secondary" data-image-name></span>
        </div>
        <div class="invalid-feedback d-block" data-error-for="image"></div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label adm-label" for="sf-alt">Image description <span class="text-secondary fw-normal">(for screen readers)</span></label>
            <input id="sf-alt" name="alt" class="form-control adm-input" maxlength="160" placeholder="e.g. Skin care sale">
          </div>
          <div class="col-md-6">
            <label class="form-label adm-label">Phone image <span class="text-secondary fw-normal">(optional)</span></label>
            <div class="d-flex align-items-center gap-2">
              <img class="adm-slide-mini" data-mobile-preview alt="" hidden>
              <label class="btn adm-btn adm-btn--ghost adm-btn--sm mb-0" for="sf-mobile">Upload</label>
              <input id="sf-mobile" name="mobileImage" type="file" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-mobile-input>
              <label class="form-check mb-0 small" data-remove-mobile-wrap hidden><input class="form-check-input" type="checkbox" name="removeMobile" value="1"> <span class="form-check-label">Remove</span></label>
            </div>
            <div class="invalid-feedback d-block" data-error-for="mobileImage"></div>
          </div>
        </div>

        <hr class="my-4">
        <h3 class="adm-section-title">Button <span class="text-secondary fw-normal">(optional)</span></h3>
        <p class="small text-secondary">Add a button on top of the image that opens a product, a category, one of your pages or any link. Choose “No link” for a plain image slide.</p>
        <div class="row g-3">
          <div class="col-md-6" data-link-slot></div>
          <div class="col-md-6" data-cta-fields><label class="form-label adm-label" for="sf-label">Button text</label><input id="sf-label" name="ctaLabel" class="form-control adm-input" maxlength="60" placeholder="Shop now"></div>
          <div class="col-12" data-cta-fields>
            <label class="form-label adm-label">Button colour</label>
            <div class="adm-swatches" role="group" aria-label="Button colour">
              <?php foreach ([['#1b2e24', 'Dark green'], ['#8cc63f', 'Leaf green'], ['#ffffff', 'White'], ['#c8664a', 'Clay'], ['#111111', 'Black']] as [$hex, $name]): ?>
                <button type="button" class="adm-swatch" data-swatch="<?= $hex ?>" style="--c:<?= $hex ?>" aria-label="<?= $name ?>" title="<?= $name ?>"></button>
              <?php endforeach; ?>
              <label class="adm-swatch adm-swatch--custom" title="Pick any colour"><input type="color" name="ctaColor" value="#1b2e24" data-color-input aria-label="Custom button colour"></label>
            </div>
            <div class="form-text">The button stays in the same place on every slide. Only its text and colour change.</div>
          </div>
        </div>
        <div class="invalid-feedback d-block" data-error-for="linkTarget"></div>

        <div class="form-check form-switch adm-switch mt-4">
          <input class="form-check-input" type="checkbox" role="switch" id="sf-active" name="isActive" checked>
          <label class="form-check-label" for="sf-active">Show this slide on the home page</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn adm-btn adm-btn--ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn adm-btn adm-btn--primary" data-save>Save slide</button>
      </div>
    </form>
  </div>
</div>
<?php $scripts = ['hero.js']; require __DIR__ . '/_foot.php'; ?>
