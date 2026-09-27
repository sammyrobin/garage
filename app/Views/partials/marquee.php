<?php
/** Ribbon with live collection facts. @var array $ribbon */
$facts = array_filter([
    t('ribbon.cars', ['n' => number_format($ribbon['cars'])]),
    t('ribbon.brands', ['n' => (string) $ribbon['brands']]),
    $ribbon['sths'] ? t('ribbon.sths', ['n' => (string) $ribbon['sths']]) : null,
    $ribbon['ths'] ? t('ribbon.ths', ['n' => (string) $ribbon['ths']]) : null,
    $ribbon['favorites'] ? t('ribbon.favorites', ['n' => (string) $ribbon['favorites']]) : null,
    $ribbon['oldest'] ? t('ribbon.oldest', ['year' => (string) $ribbon['oldest']]) : null,
]);
$line = implode(' · ', $facts) . ' · ';
?>
<div class="marquee" role="region" aria-label="<?= e(t('ribbon.label')) ?>">
    <p class="visually-hidden"><?= e(implode(', ', $facts)) ?></p>
    <div class="marquee__track" aria-hidden="true">
        <?php for ($i = 0; $i < 4; $i++): ?>
            <span class="marquee__item"><?= e($line) ?></span>
        <?php endfor; ?>
    </div>
</div>
