<?php
/** Simple line icons for the five photo angles. @var string $angle */
$paths = [
    'front' => '<rect x="4" y="9" width="16" height="8" rx="2"/><path d="M6 9l2-4h8l2 4"/><circle cx="7.5" cy="13" r="1.3"/><circle cx="16.5" cy="13" r="1.3"/><path d="M6 17v2M18 17v2"/>',
    'back'  => '<rect x="4" y="9" width="16" height="8" rx="2"/><path d="M6 9l2-4h8l2 4"/><path d="M6 12.5h3M15 12.5h3"/><path d="M10 15h4"/><path d="M6 17v2M18 17v2"/>',
    'left'  => '<path d="M2.5 15v-3l3-1 3-4h7l4 4 2 1v3z"/><circle cx="7" cy="16" r="2"/><circle cx="17" cy="16" r="2"/><path d="M9 11h8"/>',
    'right' => '<path d="M21.5 15v-3l-3-1-3-4h-7l-4 4-2 1v3z"/><circle cx="17" cy="16" r="2"/><circle cx="7" cy="16" r="2"/><path d="M15 11H7"/>',
    'top'   => '<rect x="7" y="2.5" width="10" height="19" rx="4"/><path d="M8.5 8h7l-1 3h-5z"/><path d="M9 16h6"/>',
];
?>
<svg class="angle-icon" viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><?= $paths[$angle] ?? '' ?></svg>
