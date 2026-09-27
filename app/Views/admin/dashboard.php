<?php
/** @var array $totals */
/** @var bool $isOwner */
// Private amounts: masked without an owner session (the controller also nulls them).
$private = static fn ($value): string => $isOwner ? money($value ?? 0) : t('admin.hidden_amount');
?>
<h1 class="page-title"><?= e(t('admin.dashboard')) ?></h1>

<section class="stat-grid" aria-label="<?= e(t('admin.dashboard')) ?>">
    <article class="stat-card">
        <p class="stat-card__label"><?= e(t('admin.stat.cars')) ?></p>
        <p class="stat-card__value"><?= e((int) $totals['cars']) ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label"><?= e(t('admin.stat.brands')) ?></p>
        <p class="stat-card__value"><?= e((int) $totals['brands']) ?></p>
    </article>
    <article class="stat-card">
        <p class="stat-card__label"><?= e(t('admin.stat.sth')) ?></p>
        <p class="stat-card__value"><?= e((int) $totals['sths']) ?></p>
    </article>
    <article class="stat-card stat-card--private">
        <p class="stat-card__label"><?= e(t('admin.stat.invested')) ?> <span class="sticker"><?= e(t('admin.private')) ?></span></p>
        <p class="stat-card__value stat-card__value--mono"><?= e($private($totals['invested'])) ?></p>
    </article>
    <article class="stat-card stat-card--private">
        <p class="stat-card__label"><?= e(t('admin.stat.average')) ?> <span class="sticker"><?= e(t('admin.private')) ?></span></p>
        <p class="stat-card__value stat-card__value--mono"><?= e($private($totals['average'])) ?></p>
    </article>
</section>

<p class="muted"><?= e(t('admin.phase_note')) ?></p>
