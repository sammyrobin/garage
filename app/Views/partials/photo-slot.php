<?php
/**
 * One photo box per angle: click or drop, instant preview (browser only), replace / remove,
 * and camera capture on phones.
 * @var string $angle @var ?array $photo existing photo @var ?string $error @var bool $required
 */
use Garage\Services\ImageProcessor;
use Garage\Services\PhotoStorage;

$id = 'photo-' . $angle;
$label = t('angle.' . $angle);
?>
<div class="photo-slot<?= $photo ? ' has-photo' : '' ?><?= !empty($error) ? ' has-error' : '' ?>" data-slot="<?= e($angle) ?>">
    <label class="photo-slot__drop" for="<?= e($id) ?>">
        <span class="photo-slot__preview">
            <?php if ($photo): ?>
                <img src="<?= e(PhotoStorage::url($photo['file_key'], 'sm')) ?>" alt="<?= e($label) ?>" width="400" height="300" loading="lazy">
            <?php endif; ?>
        </span>
        <span class="photo-slot__empty">
            <?= Garage\Core\View::partial('partials/angle-icon', ['angle' => $angle]) ?>
            <span class="photo-slot__label"><?= e($label) ?><?= $required ? ' <abbr title="' . e(t('validation.required')) . '">*</abbr>' : '' ?></span>
            <span class="photo-slot__cta"><?= e(t('photo.pick')) ?></span>
        </span>
    </label>
    <input class="photo-slot__input visually-hidden" id="<?= e($id) ?>" type="file" name="photo_<?= e($angle) ?>"
           accept="image/jpeg,image/png,image/webp,image/heic,image/heif" capture="environment"
           data-max-bytes="<?= ImageProcessor::MAX_BYTES ?>"
           <?= $required && !$photo ? 'required' : '' ?>
           aria-describedby="<?= e($id) ?>-error">
    <div class="photo-slot__bar">
        <span class="photo-slot__name"><?= e($label) ?></span>
        <span class="photo-slot__actions">
            <label class="chip-btn" for="<?= e($id) ?>"><?= e(t('photo.replace')) ?></label>
            <button type="button" class="chip-btn chip-btn--danger" data-remove><?= e(t('photo.remove')) ?></button>
        </span>
    </div>
    <?php if ($photo): ?>
        <input type="checkbox" class="visually-hidden" name="remove[]" value="<?= e($angle) ?>" data-remove-flag tabindex="-1" aria-hidden="true">
    <?php endif; ?>
    <p class="field__error" id="<?= e($id) ?>-error" data-error-for="photo_<?= e($angle) ?>"<?= empty($error) ? ' hidden' : '' ?>><?= e($error ?? '') ?></p>
</div>
