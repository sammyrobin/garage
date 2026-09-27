<?php
use Garage\Core\Lang;

$other = Lang::current() === 'es' ? 'en' : 'es';
?>
<a class="lang-switch" href="<?= e(url($currentPath ?? '/', $other)) ?>" hreflang="<?= e($other) ?>"
   lang="<?= e($other) ?>" aria-label="<?= e(t('lang.switch') . ': ' . t('lang.other_label')) ?>">
    <span aria-hidden="true"><?= e(Lang::current() === 'es' ? 'ES' : 'EN') ?> · </span><strong><?= e(t('lang.other')) ?></strong>
</a>
