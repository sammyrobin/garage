<?php use Garage\Core\Lang; ?>
<!doctype html>
<html lang="<?= e(Lang::current()) ?>">
<head>
<?= Garage\Core\View::partial('partials/head', get_defined_vars()) ?>
</head>
<body class="theme-light">
<a class="skip-link" href="#main"><?= e(t('nav.skip')) ?></a>

<header class="site-header">
    <?= Garage\Core\View::partial('partials/wordmark') ?>
    <nav class="site-nav" aria-label="<?= e(t('nav.home')) ?>">
        <a href="<?= e(url('/admin')) ?>"><?= e(t('nav.admin')) ?></a>
        <?= Garage\Core\View::partial('partials/lang-switch') ?>
    </nav>
</header>

<main id="main" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
    <p class="site-footer__mark" aria-hidden="true">GARAGE.</p>
    <p class="site-footer__note"><?= e(t('footer.note')) ?></p>
</footer>
</body>
</html>
