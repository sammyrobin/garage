<?php
/**
 * 3D intro: my cars floating on a slowly spinning cylinder over a moving heat-map of color.
 * Hidden without JS, for reduced motion, and after the first view in the session (see intro-gate.js).
 * Images use data-src and are loaded progressively by site.js.
 * @var array $introCars
 */
use Garage\Services\PhotoStorage;
?>
<section class="intro" data-intro aria-label="<?= e(t('intro.label')) ?>">
    <div class="intro__blobs" aria-hidden="true">
        <span class="blob blob--red"></span>
        <span class="blob blob--yellow"></span>
        <span class="blob blob--blue"></span>
    </div>
    <p class="intro__title" aria-hidden="true">GARAGE</p>
    <div class="intro__scene" aria-hidden="true" data-intro-scene>
        <?php foreach ($introCars as $i => $car): ?>
            <figure class="intro-card" data-slug="<?= e($car['slug']) ?>">
                <img data-src="<?= e(PhotoStorage::url($car['file_key'], 'sm')) ?>" alt="" width="400" height="300" decoding="async">
                <figcaption><?= e($car['name']) ?></figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
    <button type="button" class="intro__skip" data-intro-skip><?= e(t('intro.skip')) ?></button>
    <div class="intro__ui">
        <p class="intro__by">by Samuel Torres · <?= e(t('intro.subtitle')) ?></p>
        <button type="button" class="btn btn--red intro__enter" data-intro-enter><?= e(t('intro.enter')) ?> ↓</button>
        <p class="intro__hint" aria-hidden="true"><?= e(t('intro.hint')) ?></p>
    </div>
</section>
