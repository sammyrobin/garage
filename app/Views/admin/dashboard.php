<?php
/** @var array $totals @var bool $isOwner @var array $recent @var array $photos @var ?array $quota @var ?array $usage */
use Garage\Services\PhotoStorage;

// Private amounts: masked without an owner session (the controller also nulls them).
$private = static fn ($value): string => $isOwner ? money($value ?? 0) : t('admin.hidden_amount');
?>
<div class="page-head">
    <h1 class="page-title"><?= e(t('admin.dashboard')) ?></h1>
    <a class="btn btn--red" href="<?= e(url('/admin/cars/new')) ?>">+ <?= e(t('admin.add_car')) ?></a>
</div>

<section class="stat-grid" aria-label="<?= e(t('admin.totals')) ?>">
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

<?php if ($isOwner && $usage !== null): ?>
    <?= Garage\Core\View::partial('partials/disk-status', ['quota' => $quota, 'usage' => $usage]) ?>
<?php endif; ?>

<section class="panel" aria-labelledby="recent-title">
    <div class="panel__head">
        <h2 class="panel__title" id="recent-title"><?= e(t('admin.recent')) ?></h2>
        <a href="<?= e(url('/admin/cars')) ?>"><?= e(t('admin.see_all')) ?></a>
    </div>
    <?php if ($recent === []): ?>
        <p class="muted"><?= e(t('admin.empty')) ?></p>
    <?php else: ?>
        <ul class="mini-grid">
            <?php foreach ($recent as $car): $front = $photos[(int) $car['id']]['front'] ?? null; ?>
                <li>
                    <a class="mini-card" href="<?= e(url('/admin/cars/' . $car['id'] . '/edit')) ?>">
                        <?php if ($front): ?>
                            <img src="<?= e(PhotoStorage::url($front['file_key'], 'sm')) ?>" alt="" width="400" height="300" loading="lazy">
                        <?php else: ?>
                            <span class="mini-card__placeholder" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="mini-card__name"><?= e($car['name']) ?></span>
                        <span class="mini-card__meta"><?= e($car['brand_name']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
