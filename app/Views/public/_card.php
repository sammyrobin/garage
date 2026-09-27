<?php
/**
 * Gallery card: front photo; on hover it swaps to the left (or right) angle.
 * @var array $car (with 'photos' keyed by angle)
 */
use Garage\Services\PhotoStorage;

$front = $car['photos']['front'] ?? null;
$alt = $car['photos']['left'] ?? $car['photos']['right'] ?? null;
$srcset = static fn (array $p): string => PhotoStorage::url($p['file_key'], 'sm') . ' 400w, ' . PhotoStorage::url($p['file_key'], 'md') . ' 800w';
$sizes = '(min-width: 1200px) 25vw, (min-width: 760px) 33vw, 50vw';
$meta = array_filter([$car['model'], $car['real_year'] ?? null]);
?>
<article class="car-card<?= $alt ? ' car-card--swap' : '' ?>" data-car="<?= e($car['slug']) ?>">
    <a class="car-card__link" href="<?= e(route('car', ['slug' => $car['slug']])) ?>">
        <span class="car-card__media">
            <?php if ($front): ?>
                <img class="car-card__img" src="<?= e(PhotoStorage::url($front['file_key'], 'md')) ?>" srcset="<?= e($srcset($front)) ?>" sizes="<?= e($sizes) ?>"
                     width="<?= (int) $front['width'] ?>" height="<?= (int) $front['height'] ?>" loading="lazy" decoding="async"
                     alt="<?= e($car['name'] . ' — ' . t('angle.front')) ?>">
                <?php if ($alt): ?>
                    <img class="car-card__img car-card__img--alt" src="<?= e(PhotoStorage::url($alt['file_key'], 'md')) ?>" srcset="<?= e($srcset($alt)) ?>" sizes="<?= e($sizes) ?>"
                         width="<?= (int) $alt['width'] ?>" height="<?= (int) $alt['height'] ?>" loading="lazy" decoding="async" alt="">
                <?php endif; ?>
            <?php else: ?>
                <span class="car-card__placeholder"><?= Garage\Core\View::partial('partials/angle-icon', ['angle' => 'left']) ?></span>
            <?php endif; ?>
            <span class="car-card__stickers">
                <?php if ($car['rarity'] === 'super_treasure_hunt'): ?><span class="sticker sticker--red">Super Treasure Hunt</span><?php endif; ?>
                <?php if ($car['rarity'] === 'treasure_hunt'): ?><span class="sticker sticker--yellow">Treasure Hunt</span><?php endif; ?>
                <?php if ($car['is_favorite']): ?><span class="sticker sticker--blue">★ <?= e(t('field.is_favorite_short')) ?></span><?php endif; ?>
            </span>
            <span class="car-card__plus" aria-hidden="true">+</span>
        </span>
        <span class="car-card__body">
            <span class="brand-chip" data-brand="<?= e($car['brand_slug']) ?>"><?= e($car['brand_name']) ?></span>
            <span class="car-card__name"><?= e($car['name']) ?></span>
            <?php if ($meta): ?><span class="car-card__meta"><?= e(implode(' · ', $meta)) ?></span><?php endif; ?>
        </span>
    </a>
</article>
