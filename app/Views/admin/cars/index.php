<?php
/** @var string $search @var array $result @var array $photos @var bool $isOwner */
use Garage\Services\PhotoStorage;

$pageUrl = static fn (int $page): string => url('/admin/cars') . '?' . http_build_query(array_filter(['q' => $search, 'page' => $page > 1 ? $page : null]));
?>
<div class="page-head">
    <h1 class="page-title"><?= e(t('admin.cars')) ?> <span class="count-badge"><?= e($result['total']) ?></span></h1>
    <a class="btn btn--red" href="<?= e(url('/admin/cars/new')) ?>">+ <?= e(t('admin.add_car')) ?></a>
</div>

<form class="search-form" method="get" action="<?= e(url('/admin/cars')) ?>" role="search">
    <label class="visually-hidden" for="q"><?= e(t('admin.search')) ?></label>
    <input id="q" type="search" name="q" value="<?= e($search) ?>" placeholder="<?= e(t('admin.search_placeholder')) ?>">
    <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('admin.search')) ?></button>
</form>

<?php if ($result['rows'] === []): ?>
    <p class="muted"><?= e($search !== '' ? t('admin.no_results') : t('admin.empty')) ?></p>
<?php else: ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th scope="col"><span class="visually-hidden"><?= e(t('angle.front')) ?></span></th>
                    <th scope="col"><?= e(t('field.name')) ?></th>
                    <th scope="col"><?= e(t('field.brand_id')) ?></th>
                    <th scope="col"><?= e(t('field.model')) ?></th>
                    <th scope="col"><?= e(t('field.cost_mxn')) ?></th>
                    <th scope="col"><span class="visually-hidden"><?= e(t('admin.actions')) ?></span></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['rows'] as $car): $front = $photos[(int) $car['id']]['front'] ?? null; ?>
                    <tr>
                        <td class="data-table__thumb">
                            <?php if ($front): ?>
                                <img src="<?= e(PhotoStorage::url($front['file_key'], 'sm')) ?>" alt="" width="80" height="60" loading="lazy">
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($car['name']) ?></strong>
                            <?php if ($car['is_favorite']): ?><span class="sticker sticker--yellow">★ <?= e(t('field.is_favorite_short')) ?></span><?php endif; ?>
                            <?php if ($car['rarity'] === 'super_treasure_hunt'): ?><span class="sticker sticker--red">STH</span><?php endif; ?>
                        </td>
                        <td><?= e($car['brand_name']) ?></td>
                        <td><?= e($car['model']) ?></td>
                        <td class="mono"><?= e($isOwner ? ($car['cost_mxn'] !== null ? money($car['cost_mxn']) : '—') : t('admin.hidden_amount')) ?></td>
                        <td><a class="chip-btn" href="<?= e(url('/admin/cars/' . $car['id'] . '/edit')) ?>"><?= e(t('admin.edit')) ?><span class="visually-hidden"> <?= e($car['name']) ?></span></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
        <nav class="pagination" aria-label="<?= e(t('admin.pagination')) ?>">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <a href="<?= e($pageUrl($p)) ?>"<?= $p === $result['page'] ? ' aria-current="page"' : '' ?>><?= $p ?></a>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
