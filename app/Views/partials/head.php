<?php
/** @var string $title */
use Garage\Core\Lang;

// Per-language paths of this page (controllers override them for localized slugs).
$alternates ??= ['es' => $currentPath ?? '/', 'en' => $currentPath ?? '/'];
$canonical = absolute_url($alternates[Lang::current()], Lang::current());
$metaDescription = $description ?? t('meta.description');
$shareImage = $ogImage ?? rtrim((string) config('app.url'), '/') . '/assets/img/og.png';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'GARAGE') ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="theme-color" content="#0B0B12">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="canonical" href="<?= e($canonical) ?>">
<link rel="alternate" hreflang="es" href="<?= e(absolute_url($alternates['es'], 'es')) ?>">
<link rel="alternate" hreflang="en" href="<?= e(absolute_url($alternates['en'], 'en')) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e(absolute_url($alternates['es'], 'es')) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="GARAGE — by Samuel Torres">
<meta property="og:title" content="<?= e($title ?? 'GARAGE') ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta property="og:locale" content="<?= Lang::current() === 'es' ? 'es_MX' : 'en_US' ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="preload" href="<?= e(asset('fonts/big-shoulders-display-900.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(brands_css_url()) ?>">
