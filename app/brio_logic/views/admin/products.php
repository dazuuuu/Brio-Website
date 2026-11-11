<?php
/**
 * @var string $mode products|offers|featured
 * @var array $page @var \Brio\Domain\Admin\Admin $admin @var string $csrf
 * @var array $categories @var array $tones @var int $lowThreshold
 */
$title = $page['title'];
$filters = [
    'products' => [['all', 'All Products'], ['in', 'In Stock'], ['out', 'Out of Stock'], ['low', 'Low Stock']],
    'offers'   => [['all', 'All Products'], ['on', 'On Deal'], ['off', 'Not on Deal']],
    'featured' => [['all', 'All Products'], ['on', 'Featured'], ['off', 'Not Featured']],
][$mode];
$active = $mode;
$showSearch = true;
require __DIR__ . '/_shell_top.php';
?>
    <main class="adm-content" id="main"
          data-mode="<?= h($mode) ?>" data-api="<?= h(brio_base() . '/api/admin') ?>" data-csrf="<?= h($csrf) ?>"
          data-base="<?= h(brio_base()) ?>" data-low="<?= (int)$lowThreshold ?>"
          data-categories='<?= h(json_encode($categories)) ?>'>
      <div class="adm-pagehead">
        <div>
          <h1><?= h($page['title']) ?></h1>
          <p><?= h($page['sub']) ?></p>
        </div>
        <div class="adm-pagehead__actions">
          <div class="dropdown">
            <button class="btn adm-btn adm-btn--ghost" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><?= adm_icon('sliders', 16) ?> Customize View</button>
            <div class="dropdown-menu dropdown-menu-end adm-dropdown p-2" data-columns-menu></div>
          </div>
          <button class="btn adm-btn adm-btn--ghost" data-export><?= adm_icon('export', 16) ?> Export</button>
          <button class="btn adm-btn adm-btn--primary" data-new><?= adm_icon('plus', 16) ?> Add New Product</button>
        </div>
      </div>

      <!-- Stats -->
      <section class="row g-3 adm-stats" data-stats aria-label="Statistics">
        <?php foreach ([
            ['total', 'Total Products', 'box', 'adm-stat__icon--orange'],
            ['revenue', 'Product Revenue', 'chart', 'adm-stat__icon--blue'],
            ['sold', 'Products Sold', 'cart', 'adm-stat__icon--violet'],
            ['avgSales', 'Avg. Sales / Product', 'coins', 'adm-stat__icon--amber'],
        ] as [$key, $label, $icon, $tone]): ?>
          <div class="col-6 col-xl-3">
            <article class="adm-card adm-stat">
              <div class="adm-stat__top"><span class="adm-stat__icon <?= $tone ?>"><?= adm_icon($icon, 20) ?></span><span class="adm-stat__label"><?= h($label) ?></span></div>
              <div class="adm-stat__value" data-stat="<?= $key ?>">—</div>
              <div class="adm-stat__foot"><span data-stat-note="<?= $key ?>">&nbsp;</span></div>
            </article>
          </div>
        <?php endforeach; ?>
      </section>

      <!-- Toolbar -->
      <div class="adm-toolbar">
        <div class="adm-pills" role="tablist" aria-label="Filter products">
          <?php foreach ($filters as $i => [$value, $label]): ?>
            <button type="button" class="adm-pill<?= $i === 0 ? ' is-active' : '' ?>" data-filter="<?= $value ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
              <?= h($label) ?> <span data-filter-count="<?= $value ?>"></span>
            </button>
          <?php endforeach; ?>
        </div>
        <div class="adm-toolbar__right">
          <div class="dropdown">
            <button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-bs-toggle="dropdown" aria-expanded="false"><?= adm_icon('filter', 16) ?> <span data-category-label>Filter</span></button>
            <ul class="dropdown-menu adm-dropdown">
              <li><button class="dropdown-item is-active" data-category="all">All categories</button></li>
              <?php foreach ($categories as $slug => $label): ?><li><button class="dropdown-item" data-category="<?= h($slug) ?>"><?= h($label) ?></button></li><?php endforeach; ?>
            </ul>
          </div>
          <div class="dropdown">
            <button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-bs-toggle="dropdown" aria-expanded="false"><?= adm_icon('sort', 16) ?> Sort</button>
            <ul class="dropdown-menu dropdown-menu-end adm-dropdown">
              <?php foreach ([['created', 'asc', 'Oldest first'], ['created', 'desc', 'Newest first'], ['name', 'asc', 'Name A–Z'], ['price', 'asc', 'Price: low to high'], ['price', 'desc', 'Price: high to low'], ['sales', 'desc', 'Best selling'], ['revenue', 'desc', 'Highest revenue'], ['stock', 'asc', 'Lowest stock'], ['rating', 'desc', 'Top rated']] as [$s, $d, $l]): ?>
                <li><button class="dropdown-item" data-sort="<?= $s ?>" data-dir="<?= $d ?>"><?= h($l) ?></button></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="form-check form-switch adm-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="toggle-stats" checked data-toggle-stats>
            <label class="form-check-label" for="toggle-stats">Show Statistics</label>
          </div>
        </div>
      </div>

      <!-- Bulk bar -->
      <div class="adm-bulk" data-bulk hidden>
        <span><strong data-bulk-count>0</strong> selected</span>
        <?php if ($mode === 'offers'): ?>
          <button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-bulk-action="isDeal:1">Add to deals</button>
          <button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-bulk-action="isDeal:0">Remove from deals</button>
        <?php elseif ($mode === 'featured'): ?>
          <button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-bulk-action="isFeatured:1">Feature</button>
          <button class="btn adm-btn adm-btn--ghost adm-btn--sm" data-bulk-action="isFeatured:0">Unfeature</button>
        <?php endif; ?>
        <button class="btn adm-btn adm-btn--danger adm-btn--sm" data-bulk-delete>Delete selected</button>
      </div>

      <!-- Table -->
      <div class="adm-card adm-table-card">
        <div class="table-responsive">
          <table class="table adm-table align-middle mb-0">
            <thead><tr data-thead></tr></thead>
            <tbody data-tbody><tr><td class="adm-empty" colspan="12"><span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Loading products…</td></tr></tbody>
          </table>
        </div>
      </div>

      <div class="adm-pager">
        <span data-pager-info>&nbsp;</span>
        <nav aria-label="Pagination"><ul class="adm-pager__list" data-pager></ul></nav>
      </div>
    </main>
<?php require __DIR__ . '/_shell_bottom.php'; ?>

<!-- Add / edit product -->
<div class="modal fade" id="product-modal" tabindex="-1" aria-labelledby="product-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
    <form class="modal-content adm-modal" data-product-form novalidate>
      <div class="modal-header">
        <h2 class="modal-title" id="product-modal-title" data-modal-title>Add New Product</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert adm-alert adm-alert--error" data-form-error hidden></div>
        <div class="row g-4">
          <div class="col-md-4">
            <label class="form-label adm-label">Image</label>
            <label class="adm-upload" for="pf-image">
              <img data-image-preview alt="" hidden>
              <span data-image-empty><?= adm_icon('export', 22) ?><br>Click to upload<br><small>JPG, PNG or WebP · max 4 MB</small></span>
            </label>
            <input id="pf-image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="visually-hidden" data-image-input>
            <div class="invalid-feedback d-block" data-error-for="image"></div>
          </div>
          <div class="col-md-8">
            <div class="row g-3">
              <div class="col-12"><label class="form-label adm-label" for="pf-name">Product name *</label><input id="pf-name" name="name" class="form-control adm-input" required maxlength="160"><div class="invalid-feedback" data-error-for="name"></div></div>
              <div class="col-sm-6"><label class="form-label adm-label" for="pf-category">Category *</label>
                <select id="pf-category" name="category" class="form-select adm-input"><?php foreach ($categories as $slug => $label): ?><option value="<?= h($slug) ?>"><?= h($label) ?></option><?php endforeach; ?></select>
                <div class="invalid-feedback" data-error-for="category"></div></div>
              <div class="col-sm-6"><label class="form-label adm-label" for="pf-badge">Badge</label><input id="pf-badge" name="badge" class="form-control adm-input" maxlength="60" placeholder="e.g. New, 20% off"></div>
              <div class="col-sm-6"><label class="form-label adm-label" for="pf-price">Price (KSh) *</label><input id="pf-price" name="price" type="number" min="0" step="0.01" class="form-control adm-input" required><div class="invalid-feedback" data-error-for="price"></div></div>
              <div class="col-sm-6"><label class="form-label adm-label" for="pf-compare">Compare-at price</label><input id="pf-compare" name="compareAtPrice" type="number" min="0" step="0.01" class="form-control adm-input" placeholder="Original price"><div class="invalid-feedback" data-error-for="compareAtPrice"></div></div>
              <div class="col-sm-4"><label class="form-label adm-label" for="pf-stock">Stock *</label><input id="pf-stock" name="stock" type="number" min="0" step="1" class="form-control adm-input" value="0"><div class="invalid-feedback" data-error-for="stock"></div></div>
              <div class="col-sm-4"><label class="form-label adm-label" for="pf-sales">Units sold</label><input id="pf-sales" name="sales" type="number" min="0" step="1" class="form-control adm-input" value="0"><div class="invalid-feedback" data-error-for="sales"></div></div>
              <div class="col-sm-4"><label class="form-label adm-label" for="pf-rating">Rating (0–5)</label><input id="pf-rating" name="rating" type="number" min="0" max="5" step="0.1" class="form-control adm-input" value="0"><div class="invalid-feedback" data-error-for="rating"></div></div>
              <div class="col-sm-4"><label class="form-label adm-label" for="pf-reviews">Reviews</label><input id="pf-reviews" name="reviewCount" type="number" min="0" step="1" class="form-control adm-input" value="0"><div class="invalid-feedback" data-error-for="reviewCount"></div></div>
            </div>
          </div>
          <div class="col-12"><label class="form-label adm-label" for="pf-desc">Description</label><textarea id="pf-desc" name="description" rows="3" class="form-control adm-input"></textarea></div>
        </div>

        <hr class="my-4">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="adm-toggle-card">
              <div class="form-check form-switch adm-switch"><input class="form-check-input" type="checkbox" role="switch" id="pf-featured" name="isFeatured"><label class="form-check-label" for="pf-featured"><strong>Featured product</strong><small>Shown in “Featured Products” on the home page.</small></label></div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="adm-toggle-card">
              <div class="form-check form-switch adm-switch"><input class="form-check-input" type="checkbox" role="switch" id="pf-deal" name="isDeal" data-deal-toggle><label class="form-check-label" for="pf-deal"><strong>Daily deal</strong><small>Shown as an offer card in “Daily Deals”.</small></label></div>
            </div>
          </div>
        </div>
        <fieldset class="row g-3 mt-1" data-deal-fields>
          <legend class="visually-hidden">Daily deal card</legend>
          <div class="col-sm-4"><label class="form-label adm-label" for="pf-eyebrow">Deal eyebrow</label><input id="pf-eyebrow" name="dealEyebrow" class="form-control adm-input" maxlength="60" placeholder="e.g. Organic Care"></div>
          <div class="col-sm-4"><label class="form-label adm-label" for="pf-tag">Deal tag line</label><input id="pf-tag" name="dealTag" class="form-control adm-input" maxlength="60" placeholder="e.g. SPF 30 Face Care"></div>
          <div class="col-sm-4"><label class="form-label adm-label">Card colour</label>
            <div class="adm-tones" role="radiogroup" aria-label="Card colour">
              <?php foreach ($tones as $i => $tone): ?>
                <label class="adm-tone adm-tone--<?= h($tone) ?>"><input type="radio" name="dealTone" value="<?= h($tone) ?>" <?= $i === 0 ? 'checked' : '' ?>><span><?= h(ucfirst($tone)) ?></span></label>
              <?php endforeach; ?>
            </div>
          </div>
        </fieldset>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn adm-btn adm-btn--ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn adm-btn adm-btn--primary" data-save>Save product</button>
      </div>
    </form>
  </div>
</div>

<!-- Confirm delete -->
<div class="modal fade" id="confirm-modal" tabindex="-1" aria-labelledby="confirm-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content adm-modal">
      <div class="modal-body text-center p-4">
        <h2 class="adm-confirm__title" id="confirm-title">Delete product?</h2>
        <p class="adm-confirm__text" data-confirm-text>This can’t be undone.</p>
        <div class="d-flex gap-2 justify-content-center mt-3">
          <button type="button" class="btn adm-btn adm-btn--ghost" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn adm-btn adm-btn--danger" data-confirm-ok>Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= brio_asset('assets/admin/admin.js') ?>"></script>
</body>
</html>
