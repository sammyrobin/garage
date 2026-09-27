<?php
/** @var array $stats @var bool $isOwner */
$maxBrand = max(1, ...array_map(static fn (array $b): int => (int) $b['n'], $stats['top_brands'] ?: [['n' => 1]]));
$maxRarity = max(1, ...array_values($stats['rarities']));
$counters = [
    ['stats.cars', $stats['cars']],
    ['stats.brands', $stats['brands']],
    ['stats.series', $stats['series']],
    ['stats.sths', $stats['sths']],
    ['stats.ths', $stats['ths']],
    ['stats.favorites', $stats['favorites']],
];
?>
<section class="hero hero--compact">
    <p class="kicker"><?= e(t('stats.kicker')) ?></p>
    <h1 class="hero__title"><?= e(t('stats.title')) ?></h1>
</section>

<section class="stats" aria-label="<?= e(t('stats.title')) ?>">
    <div class="counter-grid">
        <?php foreach ($counters as $i => [$key, $value]): ?>
            <div class="counter counter--<?= $i % 3 ?>" data-reveal>
                <p class="counter__value" data-count="<?= (int) $value ?>"><?= e(number_format($value)) ?></p>
                <p class="counter__label"><?= e(t($key)) ?></p>
            </div>
        <?php endforeach; ?>
        <?php if ($stats['oldest_year']): ?>
            <div class="counter counter--2" data-reveal>
                <p class="counter__value" data-count="<?= (int) $stats['oldest_year'] ?>" data-count-plain><?= (int) $stats['oldest_year'] ?></p>
                <p class="counter__label"><?= e(t('stats.oldest_year')) ?></p>
            </div>
        <?php endif; ?>
        <?php if ($isOwner && isset($stats['invested'])): ?>
            <div class="counter counter--private" data-reveal>
                <p class="counter__value counter__value--money"><?= e(money($stats['invested'])) ?></p>
                <p class="counter__label"><?= e(t('admin.stat.invested')) ?> <span class="sticker"><?= e(t('admin.private')) ?></span></p>
            </div>
            <div class="counter counter--private" data-reveal>
                <p class="counter__value counter__value--money"><?= e($stats['average'] !== null ? money($stats['average']) : '—') ?></p>
                <p class="counter__label"><?= e(t('admin.stat.average')) ?> <span class="sticker"><?= e(t('admin.private')) ?></span></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="stats__cols">
        <section class="panel-card" aria-labelledby="top-brands" data-reveal>
            <h2 class="section-title" id="top-brands"><?= e(t('stats.top_brands')) ?></h2>
            <?php if ($stats['top_brands']): ?>
                <ol class="bar-chart">
                    <?php foreach ($stats['top_brands'] as $brand): $pct = round($brand['n'] / $maxBrand * 100, 1); ?>
                        <li class="bar-chart__row" data-brand="<?= e($brand['slug']) ?>">
                            <span class="bar-chart__label"><?= e($brand['name']) ?></span>
                            <?php // Width via SVG attribute: the strict CSP forbids inline styles. ?>
                            <svg class="bar-chart__bar" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true"><rect x="0" y="0" height="10" width="<?= e($pct) ?>" data-bar/></svg>
                            <span class="bar-chart__value"><?= (int) $brand['n'] ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="muted"><?= e(t('home.empty')) ?></p>
            <?php endif; ?>
        </section>

        <section class="panel-card" aria-labelledby="rarities" data-reveal>
            <h2 class="section-title" id="rarities"><?= e(t('stats.rarities')) ?></h2>
            <ul class="bar-chart bar-chart--rarity">
                <?php foreach ($stats['rarities'] as $rarity => $n): $pct = round($n / $maxRarity * 100, 1); ?>
                    <li class="bar-chart__row bar-chart__row--<?= e($rarity) ?>">
                        <span class="bar-chart__label"><?= e(t('rarity.' . $rarity)) ?></span>
                        <svg class="bar-chart__bar" viewBox="0 0 100 10" preserveAspectRatio="none" aria-hidden="true"><rect x="0" y="0" height="10" width="<?= e($pct) ?>" data-bar/></svg>
                        <span class="bar-chart__value"><?= (int) $n ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>

    <?php if ($stats['oldest']): $o = $stats['oldest']; ?>
        <section class="oldest" aria-labelledby="oldest-title" data-reveal>
            <h2 class="section-title" id="oldest-title"><?= e(t('stats.oldest')) ?></h2>
            <div class="oldest__card">
                <span class="oldest__year"><?= (int) $o['real_year'] ?></span>
                <div class="car-grid car-grid--single">
                    <?= Garage\Core\View::partial('public/_card', ['car' => $o + ['rarity' => null, 'is_favorite' => 0]]) ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($stats['sth_cars']): ?>
        <section class="related" aria-labelledby="sth-title">
            <h2 class="section-title" id="sth-title"><?= e(t('stats.sth_list')) ?></h2>
            <div class="car-grid car-grid--compact">
                <?= Garage\Core\View::partial('public/_cards', ['cars' => $stats['sth_cars']]) ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<div class="cursor-label" aria-hidden="true" data-cursor><?= e(t('home.view_car')) ?></div>
