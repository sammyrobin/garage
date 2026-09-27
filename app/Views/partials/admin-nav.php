<?php
/** @var string $section */
$items = [
    'dashboard' => ['/admin', t('admin.dashboard')],
    'new'       => ['/admin/cars/new', t('admin.add_car')],
    'cars'      => ['/admin/cars', t('admin.cars')],
    'brands'    => ['/admin/brands', t('admin.brands')],
    'series'    => ['/admin/series', t('admin.series')],
    'csv'       => ['/admin/csv', t('admin.csv')],
];
?>
<nav class="admin-nav" aria-label="<?= e(t('nav.admin')) ?>">
    <ul>
        <?php foreach ($items as $key => [$path, $label]): ?>
            <li><a href="<?= e(url($path)) ?>"<?= $section === $key ? ' aria-current="page"' : '' ?> class="<?= $key === 'new' ? 'admin-nav__cta' : '' ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
    </ul>
</nav>
