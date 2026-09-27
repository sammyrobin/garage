<?php
/** @var array $brands @var bool $isOwner */
use Garage\Models\Brand;
?>
<div class="page-head">
    <h1 class="page-title"><?= e(t('admin.brands')) ?> <span class="count-badge"><?= e(count($brands)) ?></span></h1>
</div>

<form class="panel inline-create" method="post" action="<?= e(url('/admin/brands')) ?>">
    <?= csrf_field() ?>
    <h2 class="panel__title"><?= e(t('brand.add')) ?></h2>
    <div class="inline-create__row">
        <div class="field">
            <label for="new-brand-name"><?= e(t('field.brand_name')) ?></label>
            <input id="new-brand-name" name="name" type="text" maxlength="80" required>
        </div>
        <div class="field field--color">
            <label for="new-brand-color"><?= e(t('field.accent_color')) ?></label>
            <input id="new-brand-color" name="accent_color" type="color" value="#DD0200">
        </div>
        <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => 'new-brand']) ?>
        <button type="submit" class="btn btn--red"><?= e(t('brand.add_button')) ?></button>
    </div>
</form>

<ul class="entity-list">
    <?php foreach ($brands as $brand): $sid = 'b' . $brand['id']; ?>
        <li class="entity-row">
            <span class="brand-chip" data-brand="<?= e($brand['slug']) ?>"><?= e($brand['name']) ?></span>
            <span class="muted mono"><?= e(t('admin.cars_count', ['count' => (string) $brand['cars_count']])) ?></span>
            <details class="entity-row__edit">
                <summary class="chip-btn"><?= e(t('admin.edit')) ?><span class="visually-hidden"> <?= e($brand['name']) ?></span></summary>
                <form method="post" action="<?= e(url('/admin/brands/' . $brand['id'])) ?>" class="inline-create__row">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="<?= $sid ?>-name"><?= e(t('field.brand_name')) ?></label>
                        <input id="<?= $sid ?>-name" name="name" type="text" maxlength="80" required value="<?= e($brand['name']) ?>">
                    </div>
                    <div class="field field--color">
                        <label for="<?= $sid ?>-color"><?= e(t('field.accent_color')) ?></label>
                        <input id="<?= $sid ?>-color" name="accent_color" type="color" value="<?= e($brand['accent_color']) ?>">
                    </div>
                    <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => $sid]) ?>
                    <button type="submit" class="btn btn--red btn--sm"><?= e(t('admin.save')) ?></button>
                </form>
                <form method="post" action="<?= e(url('/admin/brands/' . $brand['id'] . '/delete')) ?>" class="inline-create__row" data-confirm="<?= e(t('brand.delete_confirm', ['name' => $brand['name']])) ?>">
                    <?= csrf_field() ?>
                    <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => $sid . '-del']) ?>
                    <button type="submit" class="btn btn--ghost btn--sm btn--danger"<?= $brand['cars_count'] > 0 ? ' disabled title="' . e(t('brand.in_use', ['name' => $brand['name']])) . '"' : '' ?>><?= e(t('admin.delete')) ?></button>
                </form>
            </details>
        </li>
    <?php endforeach; ?>
</ul>
