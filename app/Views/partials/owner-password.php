<?php
/**
 * Last field of every write form (exhibition mode). Hidden while the owner session is active.
 * @var bool $isOwner  @var ?string $error  @var string $idSuffix
 */
if ($isOwner) {
    return;
}
$id = 'password-' . ($idSuffix ?? 'form');
?>
<div class="field field--password<?= !empty($error) ? ' has-error' : '' ?>">
    <label for="<?= e($id) ?>"><?= e(t('admin.password')) ?></label>
    <input id="<?= e($id) ?>" type="password" name="password" autocomplete="current-password" required
           aria-describedby="<?= e($id) ?>-hint<?= !empty($error) ? ' ' . e($id) . '-error' : '' ?>">
    <p class="field__hint" id="<?= e($id) ?>-hint"><?= e(t('admin.password_hint')) ?></p>
    <p class="field__error" id="<?= e($id) ?>-error" data-error-for="password"<?= empty($error) ? ' hidden' : '' ?>><?= e($error ?? '') ?></p>
</div>
