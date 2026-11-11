<?php
/** Form field partial. Expects: $name, $label, $type, $errors, $old, optional $placeholder, $value, $hint, $autocomplete */
$err = $errors[$name][0] ?? null;
$val = $value ?? ($old[$name] ?? '');
?>
<div class="mb-3">
  <label class="form-label adm-label" for="f-<?= h($name) ?>"><?= h($label) ?></label>
  <input id="f-<?= h($name) ?>" name="<?= h($name) ?>" type="<?= h($type ?? 'text') ?>" value="<?= ($type ?? "text") === "password" ? "" : h((string)$val) ?>"
         class="form-control adm-input<?= $err ? ' is-invalid' : '' ?>" placeholder="<?= h($placeholder ?? '') ?>"
         <?= !empty($autocomplete) ? 'autocomplete="' . h($autocomplete) . '"' : '' ?> <?= !empty($required) ? 'required' : '' ?>>
  <?php if ($err): ?><div class="invalid-feedback"><?= h($err) ?></div><?php elseif (!empty($hint)): ?><div class="form-text"><?= h($hint) ?></div><?php endif; ?>
</div>
