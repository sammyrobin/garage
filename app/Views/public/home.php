<?php
/**
 * @var array $filters @var array $result @var ?string $nextUrl @var array $brands @var array $seriesList
 * @var array $rarities @var array $conditions @var bool $filtered @var array $introCars
 */
?>
<?php if ($introCars): ?>
    <?= Garage\Core\View::partial('public/_intro', ['introCars' => $introCars]) ?>
<?php endif; ?>

<section class="hero" id="collection-start">
    <p class="kicker"><?= e(t('home.kicker')) ?></p>
    <h1 class="hero__title">GARAGE<span class="wordmark__dot">.</span></h1>
    <div class="hero__row">
        <p class="hero__lead"><?= e(t('home.lead')) ?></p>
        <a class="btn btn--yellow" href="<?= e(url('/admin')) ?>"><?= e(t('home.cta_admin')) ?> →</a>
    </div>
</section>

<section class="collection" aria-labelledby="collection-title">
    <h2 class="visually-hidden" id="collection-title"><?= e(t('home.collection')) ?></h2>

    <form class="filters" method="get" action="<?= e(url('/')) ?>" data-filters role="search" aria-label="<?= e(t('filters.label')) ?>">
        <div class="filters__search">
            <label class="visually-hidden" for="f-q"><?= e(t('filters.search')) ?></label>
            <input id="f-q" type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="<?= e(t('filters.search_placeholder')) ?>" autocomplete="off">
        </div>

        <?php if ($brands): ?>
            <fieldset class="chips">
                <legend class="visually-hidden"><?= e(t('field.brand_id')) ?></legend>
                <?php foreach ($brands as $brand): $on = in_array($brand['slug'], $filters['brand'], true); ?>
                    <label class="chip" data-brand="<?= e($brand['slug']) ?>">
                        <input type="checkbox" name="brand[]" value="<?= e($brand['slug']) ?>"<?= $on ? ' checked' : '' ?>>
                        <span><?= e($brand['name']) ?> <small><?= (int) $brand['cars'] ?></small></span>
                    </label>
                <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>

        <div class="filters__row">
            <div class="filters__field">
                <label for="f-series"><?= e(t('field.series_id')) ?></label>
                <select id="f-series" name="series">
                    <option value=""><?= e(t('filters.all')) ?></option>
                    <?php foreach ($seriesList as $series): ?>
                        <option value="<?= e($series['slug']) ?>"<?= $filters['series'] === $series['slug'] ? ' selected' : '' ?>><?= e($series['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters__field">
                <label for="f-rarity"><?= e(t('field.rarity')) ?></label>
                <select id="f-rarity" name="rarity">
                    <option value=""><?= e(t('filters.all')) ?></option>
                    <?php foreach ($rarities as $rarity): ?>
                        <option value="<?= e($rarity) ?>"<?= $filters['rarity'] === $rarity ? ' selected' : '' ?>><?= e(t('rarity.' . $rarity)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters__field">
                <label for="f-condition"><?= e(t('field.item_condition')) ?></label>
                <select id="f-condition" name="condition">
                    <option value=""><?= e(t('filters.all')) ?></option>
                    <?php foreach ($conditions as $condition): ?>
                        <option value="<?= e($condition) ?>"<?= $filters['condition'] === $condition ? ' selected' : '' ?>><?= e(t('condition.' . $condition)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filters__field filters__field--years">
                <span class="filters__label" id="f-years"><?= e(t('filters.years')) ?></span>
                <div class="filters__years" role="group" aria-labelledby="f-years">
                    <label class="visually-hidden" for="f-from"><?= e(t('filters.from')) ?></label>
                    <input id="f-from" name="from" type="text" inputmode="numeric" maxlength="4" placeholder="1960" value="<?= e($filters['from'] ?? '') ?>">
                    <span aria-hidden="true">–</span>
                    <label class="visually-hidden" for="f-to"><?= e(t('filters.to')) ?></label>
                    <input id="f-to" name="to" type="text" inputmode="numeric" maxlength="4" placeholder="<?= e(date('Y')) ?>" value="<?= e($filters['to'] ?? '') ?>">
                </div>
            </div>
            <div class="filters__field">
                <label for="f-sort"><?= e(t('filters.sort')) ?></label>
                <select id="f-sort" name="sort">
                    <?php foreach (['recent', 'year', 'brand', 'favorites'] as $sort): ?>
                        <option value="<?= e($sort) ?>"<?= $filters['sort'] === $sort ? ' selected' : '' ?>><?= e(t('sort.' . $sort)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filters__actions">
            <button type="submit" class="btn btn--red btn--sm" data-filters-submit><?= e(t('filters.apply')) ?></button>
            <?php if ($filtered): ?><a class="chip-link" href="<?= e(url('/')) ?>"><?= e(t('filters.clear')) ?></a><?php endif; ?>
            <p class="filters__count" aria-live="polite" data-results-count><?= e(t('filters.count', ['n' => number_format($result['total'])])) ?></p>
        </div>
    </form>

    <?php if ($result['cars']): ?>
        <div class="car-grid" data-grid>
            <?= Garage\Core\View::partial('public/_cards', ['cars' => $result['cars']]) ?>
        </div>
    <?php else: ?>
        <div class="empty-state" data-grid-empty>
            <p class="empty-state__title"><?= e($filtered ? t('home.no_results') : t('home.empty')) ?></p>
            <?php if ($filtered): ?><a class="btn btn--ghost" href="<?= e(url('/')) ?>"><?= e(t('filters.clear')) ?></a><?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="load-more" data-load-more<?= $nextUrl ? '' : ' hidden' ?>>
        <a class="btn btn--ghost" href="<?= e($nextUrl ?? '#') ?>" data-next><?= e(t('home.load_more')) ?></a>
    </div>
</section>

<div class="cursor-label" aria-hidden="true" data-cursor><?= e(t('home.view_car')) ?></div>
