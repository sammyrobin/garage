<?php
/**
 * Add / edit a car. Visible: name, brand + model, cost, five angle photos.
 * Everything else lives in "More details (optional)", closed by default.
 *
 * @var ?array $car @var array $values @var array $errors @var array $photos
 * @var array $brands @var array $seriesList @var bool $isOwner @var bool $costMasked @var bool $openDetails
 */
use Garage\Models\Car;
use Garage\Models\CarPhoto;

$v = static fn (string $key): string => (string) ($values[$key] ?? '');
$err = static fn (string $key): ?string => $errors[$key] ?? null;
$action = $car ? url('/admin/cars/' . $car['id']) : url('/admin/cars');
$fieldClass = static fn (string $key): string => 'field' . (isset($errors[$key]) ? ' has-error' : '');
$errorP = static function (string $key) use ($errors): string {
    return '<p class="field__error" id="err-' . e($key) . '" data-error-for="' . e($key) . '"'
        . (isset($errors[$key]) ? '' : ' hidden') . '>' . e($errors[$key] ?? '') . '</p>';
};
?>
<div class="page-head">
    <h1 class="page-title"><?= e($car ? t('car.edit_heading') : t('car.new_heading')) ?></h1>
    <?php if ($car): ?>
        <a class="btn btn--ghost" href="<?= e(url('/auto/' . $car['slug'])) ?>"><?= e(t('car.view_public')) ?></a>
    <?php endif; ?>
</div>

<?php if ($errors): ?>
    <div class="flash flash--error" role="alert"><?= e(t('validation.summary')) ?></div>
<?php endif; ?>

<form class="car-form" method="post" action="<?= e($action) ?>" enctype="multipart/form-data" novalidate
      data-car-form data-session-url="<?= e(url('/admin/session')) ?>" data-owner="<?= $isOwner ? '1' : '0' ?>">
    <?= csrf_field() ?>

    <div class="car-form__grid">
        <div class="<?= $fieldClass('name') ?> car-form__name">
            <label for="name"><?= e(t('field.name')) ?> <abbr title="<?= e(t('validation.required')) ?>">*</abbr></label>
            <input id="name" name="name" type="text" maxlength="150" required value="<?= e($v('name')) ?>"
                   placeholder="<?= e(t('field.name_placeholder')) ?>" autocomplete="off" aria-describedby="err-name">
            <?= $errorP('name') ?>
        </div>

        <div class="<?= $fieldClass('brand_id') ?>">
            <label for="brand_id"><?= e(t('field.brand_id')) ?> <abbr title="<?= e(t('validation.required')) ?>">*</abbr></label>
            <select id="brand_id" name="brand_id" required aria-describedby="err-brand_id">
                <option value=""><?= e(t('field.choose')) ?></option>
                <?php foreach ($brands as $brand): ?>
                    <option value="<?= e($brand['id']) ?>"<?= $v('brand_id') === (string) $brand['id'] ? ' selected' : '' ?>><?= e($brand['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?= $errorP('brand_id') ?>
        </div>

        <div class="<?= $fieldClass('model') ?>">
            <label for="model"><?= e(t('field.model')) ?> <abbr title="<?= e(t('validation.required')) ?>">*</abbr></label>
            <input id="model" name="model" type="text" maxlength="150" required value="<?= e($v('model')) ?>"
                   placeholder="<?= e(t('field.model_placeholder')) ?>" autocomplete="off" aria-describedby="err-model">
            <?= $errorP('model') ?>
        </div>

        <div class="<?= $fieldClass('cost_mxn') ?>">
            <label for="cost_mxn"><?= e(t('field.cost_mxn')) ?> <span class="sticker"><?= e(t('admin.private')) ?></span></label>
            <input id="cost_mxn" name="cost_mxn" type="text" inputmode="decimal" value="<?= e($v('cost_mxn')) ?>"
                   placeholder="<?= e($costMasked ? t('field.cost_masked') : '0.00') ?>" aria-describedby="err-cost_mxn cost-hint">
            <p class="field__hint" id="cost-hint"><?= e($costMasked ? t('field.cost_keep') : t('field.cost_hint')) ?></p>
            <?= $errorP('cost_mxn') ?>
        </div>
    </div>

    <fieldset class="photo-grid">
        <legend><?= e(t('photo.legend')) ?></legend>
        <p class="field__hint"><?= e(t('photo.hint')) ?></p>
        <div class="photo-grid__slots">
            <?php foreach (CarPhoto::ANGLES as $angle): ?>
                <?= Garage\Core\View::partial('partials/photo-slot', [
                    'angle' => $angle,
                    'photo' => $photos[$angle] ?? null,
                    'error' => $errors['photo_' . $angle] ?? null,
                    'required' => $angle === 'front',
                ]) ?>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <details class="more-details"<?= $openDetails ? ' open' : '' ?>>
        <summary><?= e(t('car.more_details')) ?></summary>
        <div class="car-form__grid">
            <div class="<?= $fieldClass('series_id') ?>">
                <label for="series_id"><?= e(t('field.series_id')) ?></label>
                <select id="series_id" name="series_id">
                    <option value=""><?= e(t('field.none')) ?></option>
                    <?php foreach ($seriesList as $series): ?>
                        <option value="<?= e($series['id']) ?>"<?= $v('series_id') === (string) $series['id'] ? ' selected' : '' ?>><?= e($series['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $errorP('series_id') ?>
            </div>
            <div class="<?= $fieldClass('rarity') ?>">
                <label for="rarity"><?= e(t('field.rarity')) ?></label>
                <select id="rarity" name="rarity">
                    <option value=""><?= e(t('field.none')) ?></option>
                    <?php foreach (Car::RARITIES as $rarity): ?>
                        <option value="<?= e($rarity) ?>"<?= $v('rarity') === $rarity ? ' selected' : '' ?>><?= e(t('rarity.' . $rarity)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $errorP('rarity') ?>
            </div>
            <div class="<?= $fieldClass('item_condition') ?>">
                <label for="item_condition"><?= e(t('field.item_condition')) ?></label>
                <select id="item_condition" name="item_condition">
                    <option value=""><?= e(t('field.none')) ?></option>
                    <?php foreach (Car::CONDITIONS as $condition): ?>
                        <option value="<?= e($condition) ?>"<?= $v('item_condition') === $condition ? ' selected' : '' ?>><?= e(t('condition.' . $condition)) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $errorP('item_condition') ?>
            </div>
            <div class="<?= $fieldClass('real_year') ?>">
                <label for="real_year"><?= e(t('field.real_year')) ?></label>
                <input id="real_year" name="real_year" type="text" inputmode="numeric" maxlength="4" value="<?= e($v('real_year')) ?>" placeholder="1999">
                <?= $errorP('real_year') ?>
            </div>
            <div class="<?= $fieldClass('casting_year') ?>">
                <label for="casting_year"><?= e(t('field.casting_year')) ?></label>
                <input id="casting_year" name="casting_year" type="text" inputmode="numeric" maxlength="4" value="<?= e($v('casting_year')) ?>" placeholder="<?= e(date('Y')) ?>">
                <?= $errorP('casting_year') ?>
            </div>
            <div class="<?= $fieldClass('collection_number') ?>">
                <label for="collection_number"><?= e(t('field.collection_number')) ?></label>
                <input id="collection_number" name="collection_number" type="text" maxlength="20" value="<?= e($v('collection_number')) ?>" placeholder="123/250">
                <?= $errorP('collection_number') ?>
            </div>
            <div class="<?= $fieldClass('color') ?>">
                <label for="color"><?= e(t('field.color')) ?></label>
                <input id="color" name="color" type="text" maxlength="60" value="<?= e($v('color')) ?>">
                <?= $errorP('color') ?>
            </div>
            <div class="<?= $fieldClass('acquired_at') ?>">
                <label for="acquired_at"><?= e(t('field.acquired_at')) ?></label>
                <input id="acquired_at" name="acquired_at" type="date" value="<?= e($v('acquired_at')) ?>" max="<?= e(date('Y-m-d')) ?>">
                <?= $errorP('acquired_at') ?>
            </div>
            <div class="<?= $fieldClass('notes') ?> car-form__wide">
                <label for="notes"><?= e(t('field.notes')) ?></label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000"><?= e($v('notes')) ?></textarea>
                <?= $errorP('notes') ?>
            </div>
            <div class="field field--check car-form__wide">
                <input id="is_favorite" name="is_favorite" type="checkbox" value="1"<?= $v('is_favorite') === '1' ? ' checked' : '' ?>>
                <label for="is_favorite"><?= e(t('field.is_favorite')) ?></label>
            </div>
        </div>
    </details>

    <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => $errors['password'] ?? null, 'idSuffix' => 'car']) ?>

    <div class="progress" data-progress hidden>
        <div class="progress__track"><div class="progress__bar" data-progress-bar></div></div>
        <p class="progress__label" data-progress-label aria-live="polite"></p>
    </div>

    <div class="form-actions">
        <button type="submit" name="action" value="save" class="btn btn--red"><?= e(t('car.save')) ?></button>
        <?php if (!$car): ?>
            <button type="submit" name="action" value="save_add" class="btn btn--yellow"><?= e(t('car.save_add')) ?></button>
        <?php endif; ?>
        <a class="btn btn--ghost" href="<?= e(url('/admin/cars')) ?>"><?= e(t('car.cancel')) ?></a>
    </div>
</form>

<?php if ($car): ?>
    <form class="danger-zone" method="post" action="<?= e(url('/admin/cars/' . $car['id'] . '/delete')) ?>" data-confirm="<?= e(t('car.delete_confirm', ['name' => $car['name']])) ?>">
        <?= csrf_field() ?>
        <h2 class="panel__title"><?= e(t('car.delete_title')) ?></h2>
        <p class="muted"><?= e(t('car.delete_text')) ?></p>
        <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => 'delete']) ?>
        <button type="submit" class="btn btn--ghost btn--danger"><?= e(t('car.delete')) ?></button>
    </form>
<?php endif; ?>
