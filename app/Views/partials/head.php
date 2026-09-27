<?php
/** @var string $title */
use Garage\Core\Lang;

$otherLang = Lang::current() === 'es' ? 'en' : 'es';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'GARAGE') ?></title>
<meta name="description" content="<?= e(t('meta.description')) ?>">
<meta name="theme-color" content="#0B0B12">
<link rel="alternate" hreflang="es" href="<?= e(absolute_url($currentPath ?? '/', 'es')) ?>">
<link rel="alternate" hreflang="en" href="<?= e(absolute_url($currentPath ?? '/', 'en')) ?>">
<link rel="alternate" hreflang="x-default" href="<?= e(absolute_url($currentPath ?? '/', 'es')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
