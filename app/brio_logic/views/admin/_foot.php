<?php /** Shared page footer for the newer admin pages. Optional: $scripts = ['pages.js', …] */ ?>
<div class="modal fade" id="adm-confirm" tabindex="-1" aria-labelledby="adm-confirm-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content adm-modal">
      <div class="modal-body text-center p-4">
        <h2 class="adm-confirm__title" id="adm-confirm-title" data-confirm-title>Are you sure?</h2>
        <p class="adm-confirm__text" data-confirm-text></p>
        <div class="d-flex gap-2 justify-content-center mt-3">
          <button type="button" class="btn adm-btn adm-btn--ghost" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn adm-btn adm-btn--danger" data-confirm-ok>Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= brio_asset('assets/admin/admin-core.js') ?>"></script>
<?php foreach (($scripts ?? []) as $js): ?><script src="<?= brio_asset('assets/admin/' . $js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
