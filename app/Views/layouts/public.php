<?php
/** @var ?array $ribbon */
use Garage\Core\Lang;

$links = [
    'portfolio' => 'https://samueltorres.dev',
    'linkedin' => 'https://www.linkedin.com/in/samuel-torres-pichardo-7b16581a1/',
    'github' => 'https://github.com/sammyrobin/garage',
];
?>
<!doctype html>
<html lang="<?= e(Lang::current()) ?>" class="no-js">
<head>
<?= Garage\Core\View::partial('partials/head', get_defined_vars()) ?>
<script src="<?= e(asset('js/intro-gate.js')) ?>"></script>
<link rel="stylesheet" href="<?= e(asset('css/site.css')) ?>">
<script src="<?= e(asset('vendor/gsap/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/gsap/ScrollTrigger.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/gsap/Flip.min.js')) ?>" defer></script>
<script src="<?= e(asset('js/site.js')) ?>" defer></script>
</head>
<body class="theme-light">
<a class="skip-link" href="#main"><?= e(t('nav.skip')) ?></a>

<?php if (!empty($ribbon) && $ribbon['cars'] > 0): ?>
    <?= Garage\Core\View::partial('partials/marquee', ['ribbon' => $ribbon]) ?>
<?php endif; ?>

<header class="site-header">
    <?= Garage\Core\View::partial('partials/wordmark') ?>
    <nav class="site-nav" aria-label="<?= e(t('nav.main')) ?>">
        <a href="<?= e(route('home')) ?>"<?= ($currentPath ?? '') === '/' ? ' aria-current="page"' : '' ?>><?= e(t('nav.home')) ?></a>
        <a href="<?= e(route('stats')) ?>"<?= in_array($currentPath ?? '', ['/estadisticas', '/stats'], true) ? ' aria-current="page"' : '' ?>><?= e(t('nav.stats')) ?></a>
        <a href="<?= e(url('/admin')) ?>" class="site-nav__panel"><?= e(t('nav.admin')) ?></a>
        <?= Garage\Core\View::partial('partials/lang-switch') ?>
    </nav>
</header>

<main id="main" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
    <div class="site-footer__top">
        <p class="site-footer__lead"><?= e(t('footer.lead')) ?></p>
        <ul class="site-footer__links">
            <li><a href="<?= e($links['portfolio']) ?>"><?= e(t('footer.portfolio')) ?> ↗</a></li>
            <li><a href="<?= e($links['linkedin']) ?>" rel="noopener">LinkedIn ↗</a></li>
            <li><a href="<?= e($links['github']) ?>" rel="noopener">GitHub ↗</a></li>
            <li><a href="<?= e(url('/admin')) ?>"><?= e(t('nav.admin')) ?></a></li>
        </ul>
    </div>
    <p class="site-footer__mark" aria-hidden="true">GARAGE<span class="wordmark__dot">.</span></p>
    <p class="site-footer__note">© <?= date('Y') ?> Samuel Torres · <?= e(t('footer.note')) ?></p>
</footer>
</body>
</html>
