<?php
/** Plain-text version of the new-car summary. No HTML escaping: this is text/plain. */
use Garage\Services\DiskStatus;

$count = count($cars);
echo $count === 1 ? "NUEVO EN EL GARAGE\n" : "{$count} NUEVOS EN EL GARAGE\n";
echo str_repeat('=', 40) . "\n\n";

foreach ($cars as $car) {
    echo $car['name'] . "\n";
    echo '  ' . $car['brand_name'] . ' · ' . $car['model'] . "\n";
    echo '  Costo: ' . ($car['cost_mxn'] !== null ? money($car['cost_mxn']) : '—') . "\n";
    echo '  ' . $car['detail_url'] . "\n\n";
}

echo "COLECCIÓN\n";
echo '  Autos: ' . (int) $totals['cars'] . "\n";
echo '  Total invertido: ' . money($totals['invested']) . "\n";
echo '  Costo promedio: ' . ($totals['average'] !== null ? money($totals['average']) : '—') . "\n\n";

echo "ESTADO DEL SERVIDOR\n";
if ($quota !== null && $quota['percent'] !== null) {
    $filled = max(0, min(20, (int) round($quota['percent'] / 5)));
    echo '  Hosting: ' . DiskStatus::formatBytes($quota['used']) . ' de ' . DiskStatus::formatBytes($quota['limit'])
        . ' (' . number_format($quota['percent'], 1) . " %)\n";
    echo '  [' . str_repeat('#', $filled) . str_repeat('.', 20 - $filled) . "]\n";
} elseif ($quota !== null) {
    echo '  Hosting: ' . DiskStatus::formatBytes($quota['used']) . " usados (sin límite de cuota)\n";
} else {
    echo "  No se pudo leer la cuota de cPanel\n";
}
echo '  Fotos del Garage: ' . DiskStatus::formatBytes($usage['bytes']) . ' en ' . (int) $usage['photos'] . " fotos\n\n";

echo 'Panel de control: ' . $adminUrl . "\n";
