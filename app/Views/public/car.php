<?php
/**
 * Car detail: big viewer with the available angles (buttons, swipe, arrow keys, "turn" transition),
 * spec sheet and related cars from the same brand.
 * @var array $car @var array $photos @var array $related
 */
use Garage\Models\CarPhoto;
use Garage\Services\PhotoStorage;

$angles = array_values(array_intersect(CarPhoto::ANGLES, array_keys($photos)));
$specs = array_filter([
    t('field.brand_id') => $car['brand_name'],
    t('field.model') => $car['model'],
    t('field.series_id') => $car['series_name'],
    t('field.real_year') => $car['real_year'],
    t('field.casting_year') => $car['casting_year'],
    t('field.collection_number') => $car['collection_number'],
    t('field.color') => $car['color'],
    t('field.rarity') => $car['rarity'] ? t('rarity.' . $car['rarity']) : null,
    t('field.item_condition') => $car['item_condition'] ? t('condition.' . $car['item_condition']) : null,
], static fn ($v): bool => $v !== null && $v !== '');
?>
<article class="car-page">
    <nav class="breadcrumb" aria-label="<?= e(t('car.breadcrumb')) ?>">
        <a href="<?= e(route('home')) ?>">← <?= e(t('nav.home')) ?></a>
    </nav>

    <div class="car-page__grid">
        <section class="viewer" data-viewer aria-label="<?= e(t('viewer.label', ['name' => $car['name']])) ?>">
            <div class="viewer__stage" data-viewer-stage tabindex="0" aria-live="polite">
                <?php foreach ($angles as $i => $angle): $p = $photos[$angle]; ?>
                    <figure class="viewer__slide<?= $i === 0 ? ' is-active' : '' ?>" id="slide-<?= e($angle) ?>" data-angle="<?= e($angle) ?>">
                        <img src="<?= e(PhotoStorage::url($p['file_key'], 'lg')) ?>"
                             srcset="<?= e(PhotoStorage::url($p['file_key'], 'md')) ?> 800w, <?= e(PhotoStorage::url($p['file_key'], 'lg')) ?> 1600w"
                             sizes="(min-width: 1000px) 58vw, 100vw" width="<?= (int) $p['width'] ?>" height="<?= (int) $p['height'] ?>"
                             alt="<?= e($car['name'] . ' — ' . t('angle.' . $angle)) ?>" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
                        <figcaption class="visually-hidden"><?= e(t('angle.' . $angle)) ?></figcaption>
                    </figure>
                <?php endforeach; ?>
                <?php if (!$angles): ?>
                    <div class="viewer__empty"><?= Garage\Core\View::partial('partials/angle-icon', ['angle' => 'left']) ?></div>
                <?php endif; ?>
            </div>
            <?php if (count($angles) > 1): ?>
                <div class="viewer__controls" role="group" aria-label="<?= e(t('viewer.angles')) ?>">
                    <?php foreach ($angles as $i => $angle): ?>
                        <button type="button" class="viewer__btn" data-go="<?= e($angle) ?>" aria-controls="slide-<?= e($angle) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                            <?= Garage\Core\View::partial('partials/angle-icon', ['angle' => $angle]) ?>
                            <span><?= e(t('angle.' . $angle)) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="viewer__hint"><?= e(t('viewer.hint')) ?></p>
            <?php endif; ?>
        </section>

        <section class="car-info">
            <div class="car-info__stickers">
                <?php if ($car['rarity'] === 'super_treasure_hunt'): ?><span class="sticker sticker--red sticker--lg">Super Treasure Hunt</span><?php endif; ?>
                <?php if ($car['rarity'] === 'treasure_hunt'): ?><span class="sticker sticker--yellow sticker--lg">Treasure Hunt</span><?php endif; ?>
                <?php if ($car['is_favorite']): ?><span class="sticker sticker--blue sticker--lg">★ <?= e(t('field.is_favorite_short')) ?></span><?php endif; ?>
            </div>
            <span class="brand-chip brand-chip--lg" data-brand="<?= e($car['brand_slug']) ?>"><?= e($car['brand_name']) ?></span>
            <h1 class="car-info__title"><?= e($car['name']) ?></h1>

            <h2 class="car-info__subtitle"><?= e(t('car.specs')) ?></h2>
            <dl class="spec-sheet">
                <?php foreach ($specs as $label => $value): ?>
                    <div class="spec-sheet__row"><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
                <?php endforeach; ?>
            </dl>
            <?php if ($car['notes']): ?>
                <h2 class="car-info__subtitle"><?= e(t('field.notes')) ?></h2>
                <p class="car-info__notes"><?= nl2br(e($car['notes'])) ?></p>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($related): ?>
        <section class="related" aria-labelledby="related-title">
            <h2 class="section-title" id="related-title"><?= e(t('car.related', ['brand' => $car['brand_name']])) ?></h2>
            <div class="car-grid car-grid--compact">
                <?= Garage\Core\View::partial('public/_cards', ['cars' => $related]) ?>
            </div>
        </section>
    <?php endif; ?>
</article>

<div class="cursor-label" aria-hidden="true" data-cursor><?= e(t('home.view_car')) ?></div>
