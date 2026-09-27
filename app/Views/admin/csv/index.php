<?php
/** @var ?array $report @var bool $isOwner */
use Garage\Services\CsvService;
?>
<h1 class="page-title"><?= e(t('admin.csv')) ?></h1>

<?php if (!empty($report)): ?>
    <section class="panel panel--error" aria-labelledby="csv-report-title">
        <h2 class="panel__title" id="csv-report-title"><?= e(t('csv.report_title')) ?></h2>
        <ul class="csv-report">
            <?php foreach ($report as $line => $message): ?>
                <li><?= $line > 0 ? '<span class="mono">' . e(t('csv.line', ['line' => (string) $line])) . '</span> ' : '' ?><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<div class="two-col">
    <form class="panel" method="post" action="<?= e(url('/admin/csv/import')) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <h2 class="panel__title"><?= e(t('csv.import_title')) ?></h2>
        <p><?= e(t('csv.import_text')) ?></p>
        <p class="field__hint mono"><?= e(implode(', ', CsvService::COLUMNS)) ?></p>
        <p class="field__hint"><?= e(t('csv.import_hint')) ?></p>
        <div class="field">
            <label for="csv"><?= e(t('csv.file')) ?></label>
            <input id="csv" name="csv" type="file" accept=".csv,text/csv" required>
        </div>
        <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => 'csv-import']) ?>
        <button type="submit" class="btn btn--red"><?= e(t('csv.import_button')) ?></button>
    </form>

    <form class="panel" method="post" action="<?= e(url('/admin/csv/export')) ?>">
        <?= csrf_field() ?>
        <h2 class="panel__title"><?= e(t('csv.export_title')) ?></h2>
        <p><?= e(t('csv.export_text')) ?></p>
        <?= Garage\Core\View::partial('partials/owner-password', ['isOwner' => $isOwner, 'error' => null, 'idSuffix' => 'csv-export']) ?>
        <button type="submit" class="btn btn--yellow"><?= e(t('csv.export_button')) ?></button>
    </form>
</div>
