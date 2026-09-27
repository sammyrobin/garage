<?php
/** @var array $seriesList @var bool $isOwner */
?>
<div class="page-head">
    <h1 class="page-title"><?= e(t('admin.series')) ?> <span class="count-badge"><?= e(count($seriesList)) ?></span></h1>
</div>

<form class="panel inline-create" method="post" action="<?= e(url('/admin/series')) ?>">
    <?= csrf_field() ?>
    <h2 class="panel__title"><?= e(t('series.add')) ?></h2>
    <div class="inline-create__row">
        <div class="field">
            <label for="new-series-name"><?= e(t('field.series_name')) ?></label>
            <input id="new-series-name" name="name" type="text" maxlength="80" required>
        </div>
        <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => 'new-series']) ?>
        <button type="submit" class="btn btn--red"><?= e(t('series.add_button')) ?></button>
    </div>
</form>

<ul class="entity-list">
    <?php foreach ($seriesList as $series): $sid = 's' . $series['id']; ?>
        <li class="entity-row">
            <strong><?= e($series['name']) ?></strong>
            <span class="muted mono"><?= e(t('admin.cars_count', ['count' => (string) $series['cars_count']])) ?></span>
            <details class="entity-row__edit">
                <summary class="chip-btn"><?= e(t('admin.edit')) ?><span class="visually-hidden"> <?= e($series['name']) ?></span></summary>
                <form method="post" action="<?= e(url('/admin/series/' . $series['id'])) ?>" class="inline-create__row">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="<?= $sid ?>-name"><?= e(t('field.series_name')) ?></label>
                        <input id="<?= $sid ?>-name" name="name" type="text" maxlength="80" required value="<?= e($series['name']) ?>">
                    </div>
                    <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => $sid]) ?>
                    <button type="submit" class="btn btn--red btn--sm"><?= e(t('admin.save')) ?></button>
                </form>
                <form method="post" action="<?= e(url('/admin/series/' . $series['id'] . '/delete')) ?>" class="inline-create__row" data-confirm="<?= e(t('series.delete_confirm', ['name' => $series['name']])) ?>">
                    <?= csrf_field() ?>
                    <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => $sid . '-del']) ?>
                    <button type="submit" class="btn btn--ghost btn--sm btn--danger"><?= e(t('admin.delete')) ?></button>
                </form>
            </details>
        </li>
    <?php endforeach; ?>
</ul>
