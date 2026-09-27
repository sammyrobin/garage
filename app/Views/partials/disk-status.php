<?php
/** Owner-only disk panel. @var ?array $quota @var array $usage */
use Garage\Services\DiskStatus;
?>
<section class="panel disk" aria-labelledby="disk-title">
    <h2 class="panel__title" id="disk-title"><?= e(t('disk.title')) ?></h2>
    <?php if ($quota !== null && $quota['percent'] !== null): ?>
        <p class="disk__line">
            <?= e(t('disk.account')) ?>:
            <strong><?= e(DiskStatus::formatBytes($quota['used'])) ?></strong> / <?= e(DiskStatus::formatBytes($quota['limit'])) ?>
            <span class="mono">(<?= e(number_format($quota['percent'], 1)) ?> %)</span>
        </p>
        <?php // SVG attribute (not inline style) so the strict CSP still applies. ?>
        <svg class="meter meter--<?= e($quota['level']) ?>" role="meter" aria-valuemin="0" aria-valuemax="100"
             aria-valuenow="<?= e(round($quota['percent'])) ?>" aria-label="<?= e(t('disk.account')) ?>"
             width="100%" height="18" preserveAspectRatio="none">
            <rect class="meter__fill" x="0" y="0" height="100%" width="<?= e(min(100, max(1, round($quota['percent'], 1)))) ?>%"/>
        </svg>
    <?php elseif ($quota !== null): ?>
        <p class="disk__line"><?= e(t('disk.account')) ?>: <strong><?= e(DiskStatus::formatBytes($quota['used'])) ?></strong> (<?= e(t('disk.unlimited')) ?>)</p>
    <?php else: ?>
        <p class="disk__line disk__line--warn"><?= e(t('disk.quota_failed')) ?></p>
    <?php endif; ?>
    <p class="disk__line">
        <?= e(t('disk.garage')) ?>: <strong><?= e(DiskStatus::formatBytes($usage['bytes'])) ?></strong>
        · <?= e(t('disk.photos', ['count' => (string) $usage['photos']])) ?>
    </p>
</section>
