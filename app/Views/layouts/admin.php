<?php
/** @var bool $isOwner */
/** @var ?array $flash */
/** @var string $section */
use Garage\Core\Lang;
?>
<!doctype html>
<html lang="<?= e(Lang::current()) ?>">
<head>
<?= Garage\Core\View::partial('partials/head', get_defined_vars()) ?>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body class="theme-light admin">
<a class="skip-link" href="#main"><?= e(t('nav.skip')) ?></a>

<div class="exhibition-banner" role="note">
    <?php if ($isOwner): ?>
        <p><strong class="sticker sticker--yellow"><?= e(t('admin.owner_active')) ?></strong></p>
        <form method="post" action="<?= e(url('/admin/logout')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= e($currentPath ?? '/admin') ?>">
            <button type="submit" class="btn btn--ghost btn--sm"><?= e(t('admin.logout')) ?></button>
        </form>
    <?php else: ?>
        <p><?= e(t('admin.exhibition')) ?></p>
        <form method="post" action="<?= e(url('/admin/session')) ?>" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="back" value="<?= e($currentPath ?? '/admin') ?>">
            <label class="visually-hidden" for="owner-password"><?= e(t('admin.password')) ?></label>
            <input id="owner-password" type="password" name="password" autocomplete="current-password"
                   placeholder="<?= e(t('admin.password')) ?>" required>
            <button type="submit" class="btn btn--red btn--sm"><?= e(t('admin.unlock')) ?></button>
        </form>
    <?php endif; ?>
</div>

<header class="site-header">
    <?= Garage\Core\View::partial('partials/wordmark') ?>
    <nav class="site-nav" aria-label="<?= e(t('nav.site')) ?>">
        <a href="<?= e(url('/')) ?>"><?= e(t('admin.back_to_site')) ?></a>
        <?= Garage\Core\View::partial('partials/lang-switch') ?>
    </nav>
</header>

<?= Garage\Core\View::partial('partials/admin-nav', ['section' => $section]) ?>

<main id="main" tabindex="-1" class="admin-main">
    <?php if (!empty($flash)): ?>
        <div class="flash flash--<?= e($flash['type']) ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= e($flash['message']) ?>
        </div>
    <?php endif; ?>
<?= $content ?>
</main>
<script type="application/json" id="admin-i18n"><?= json_encode([
    'checking' => t('js.checking'),
    'optimizing' => t('js.optimizing'),
    'uploading' => t('js.uploading'),
    'saving' => t('js.saving'),
    'network' => t('js.network'),
    'tooBig' => t('js.too_big'),
    'frontRequired' => t('photo.front_required'),
    'summary' => t('validation.summary'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
</body>
</html>
