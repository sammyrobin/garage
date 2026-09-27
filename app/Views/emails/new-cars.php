<?php
/**
 * New-car summary e-mail (HTML). Same visual system as the portfolio e-mails:
 * dark header, red accent line, 600px tables, inline CSS, Outlook (MSO) fallbacks.
 *
 * @var array $cars @var array $totals @var ?array $quota @var array $usage
 * @var string $adminUrl @var string $siteUrl
 */
use Garage\Services\DiskStatus;

$dark = '#14100C'; $accent = '#DD0200'; $bg = '#D9D9D9'; $text = '#1A1A1A'; $text2 = '#4A4A4A';
$muted = '#666666'; $line = '#E5E5E5'; $soft = '#F2F2F2'; $onDark = '#999999';
$font = "-apple-system, 'Segoe UI', Helvetica, Arial, sans-serif";
$barColors = ['green' => '#1E8A3C', 'yellow' => '#FFCC00', 'red' => '#DD0200', 'unknown' => '#999999'];
$count = count($cars);
$title = $count === 1 ? 'Nuevo en el Garage' : $count . ' nuevos en el Garage';
?>
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="x-apple-disable-message-reformatting">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title><?= e($title) ?></title>
<!--[if mso]>
<noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
<style>body, table, td, p, a, h1, h2, span, div { font-family: 'Segoe UI', Arial, sans-serif !important; }</style>
<![endif]-->
<style>
  body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; }
  table { border-collapse: collapse; }
  a { color: <?= $accent ?>; }
  @media (prefers-color-scheme: dark) {
    .dm-bg { background-color: #0B0907 !important; }
    .dm-card { background-color: #1C1814 !important; }
    .dm-soft { background-color: #27221D !important; }
    .dm-text { color: #F2F2F2 !important; }
    .dm-text2 { color: #C9C9C9 !important; }
    .dm-line { border-color: #3A332C !important; }
  }
  @media only screen and (max-width: 620px) {
    .px { padding-left: 24px !important; padding-right: 24px !important; }
    .stack, .stack td { display: block !important; width: 100% !important; }
    .thumb { padding: 0 0 12px 0 !important; }
  }
</style>
</head>
<body class="dm-bg" style="margin:0;padding:0;background-color:<?= $bg ?>;">
<div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">
  <?= e(implode(' · ', array_column($cars, 'name'))) ?>
</div>
<table role="presentation" class="dm-bg" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="<?= $bg ?>" style="background-color:<?= $bg ?>;">
<tr><td align="center" style="padding:32px 12px;">
<!--[if mso]><table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;margin:0 auto;">

  <tr><td class="px" bgcolor="<?= $dark ?>" style="background-color:<?= $dark ?>;padding:36px 48px 30px 48px;border-radius:12px 12px 0 0;">
    <div style="font-family:<?= $font ?>;font-size:32px;line-height:38px;font-weight:800;color:#ffffff;letter-spacing:1px;">GARAGE<span style="color:<?= $accent ?>;">.</span></div>
    <div style="font-family:<?= $font ?>;font-size:12px;line-height:18px;font-weight:500;color:<?= $onDark ?>;letter-spacing:3px;text-transform:uppercase;padding-top:8px;"><?= e($title) ?></div>
  </td></tr>
  <tr><td bgcolor="<?= $accent ?>" style="background-color:<?= $accent ?>;height:4px;font-size:0;line-height:0;mso-line-height-rule:exactly;">&nbsp;</td></tr>

  <tr><td class="px dm-card" bgcolor="#ffffff" style="background-color:#ffffff;padding:36px 48px 12px 48px;font-family:<?= $font ?>;color:<?= $text2 ?>;">
<?php foreach ($cars as $car): ?>
    <table role="presentation" class="stack" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px 0;">
      <tr>
        <td class="thumb" width="136" valign="top" style="padding:0 20px 0 0;">
          <?php if ($car['thumb_url']): ?>
            <a href="<?= e($car['detail_url']) ?>" target="_blank"><img src="<?= e($car['thumb_url']) ?>" width="120" alt="<?= e($car['name']) ?>" style="display:block;width:120px;max-width:100%;height:auto;border:2px solid #141414;border-radius:10px;"></a>
          <?php else: ?>
            <div style="width:120px;height:80px;border:2px solid #141414;border-radius:10px;background-color:<?= $soft ?>;"></div>
          <?php endif; ?>
        </td>
        <td valign="top">
          <div class="dm-text" style="font-family:<?= $font ?>;font-size:20px;line-height:26px;font-weight:700;color:<?= $text ?>;"><?= e($car['name']) ?></div>
          <div class="dm-text2" style="font-family:<?= $font ?>;font-size:14px;line-height:22px;color:<?= $text2 ?>;padding-top:2px;"><?= e($car['brand_name']) ?> · <?= e($car['model']) ?></div>
          <div class="dm-text2" style="font-family:<?= $font ?>;font-size:14px;line-height:22px;color:<?= $text2 ?>;">Costo: <strong><?= $car['cost_mxn'] !== null ? e(money($car['cost_mxn'])) : '—' ?></strong></div>
          <div style="padding-top:8px;font-family:<?= $font ?>;font-size:14px;line-height:20px;"><a href="<?= e($car['detail_url']) ?>" target="_blank" style="color:<?= $accent ?>;font-weight:700;text-decoration:none;">Ver su página &rarr;</a></div>
        </td>
      </tr>
    </table>
<?php endforeach; ?>
  </td></tr>

  <tr><td class="px dm-card" bgcolor="#ffffff" style="background-color:#ffffff;padding:0 48px 8px 48px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="dm-soft" bgcolor="<?= $soft ?>" style="background-color:<?= $soft ?>;border-radius:10px;">
      <tr>
        <?php foreach ([['Autos', (string) (int) $totals['cars']], ['Total invertido', money($totals['invested'])], ['Costo promedio', $totals['average'] !== null ? money($totals['average']) : '—']] as [$label, $value]): ?>
        <td width="33%" valign="top" style="padding:16px 14px;font-family:<?= $font ?>;">
          <div style="font-size:11px;line-height:16px;letter-spacing:1px;text-transform:uppercase;color:<?= $muted ?>;"><?= e($label) ?></div>
          <div class="dm-text" style="font-size:17px;line-height:24px;font-weight:700;color:<?= $text ?>;"><?= e($value) ?></div>
        </td>
        <?php endforeach; ?>
      </tr>
    </table>
  </td></tr>

  <tr><td class="px dm-card" bgcolor="#ffffff" style="background-color:#ffffff;padding:24px 48px 36px 48px;font-family:<?= $font ?>;">
    <div class="dm-text" style="font-size:13px;line-height:18px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:<?= $text ?>;padding-bottom:10px;">Estado del servidor</div>
    <?php if ($quota !== null && $quota['percent'] !== null):
        $pct = max(1, min(100, (int) round($quota['percent']))); ?>
      <div class="dm-text2" style="font-size:14px;line-height:22px;color:<?= $text2 ?>;padding-bottom:8px;">
        Cuenta de hosting: <strong><?= e(DiskStatus::formatBytes($quota['used'])) ?></strong> de <?= e(DiskStatus::formatBytes($quota['limit'])) ?> (<?= e(number_format($quota['percent'], 1)) ?>&nbsp;%)
      </div>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:2px solid #141414;border-radius:8px;">
        <tr>
          <td width="<?= $pct ?>%" bgcolor="<?= $barColors[$quota['level']] ?>" style="background-color:<?= $barColors[$quota['level']] ?>;height:14px;font-size:0;line-height:0;">&nbsp;</td>
          <?php if ($pct < 100): ?><td bgcolor="#ffffff" style="background-color:#ffffff;height:14px;font-size:0;line-height:0;">&nbsp;</td><?php endif; ?>
        </tr>
      </table>
    <?php elseif ($quota !== null): ?>
      <div class="dm-text2" style="font-size:14px;line-height:22px;color:<?= $text2 ?>;">Cuenta de hosting: <strong><?= e(DiskStatus::formatBytes($quota['used'])) ?></strong> usados (sin límite de cuota).</div>
    <?php else: ?>
      <div style="font-size:14px;line-height:22px;color:<?= $accent ?>;font-weight:600;">No se pudo leer la cuota de cPanel</div>
    <?php endif; ?>
    <div class="dm-text2" style="font-size:14px;line-height:22px;color:<?= $text2 ?>;padding-top:12px;">
      Fotos del Garage: <strong><?= e(DiskStatus::formatBytes($usage['bytes'])) ?></strong> en <?= e((int) $usage['photos']) ?> fotos
    </div>
  </td></tr>

  <tr><td class="px" bgcolor="<?= $dark ?>" style="background-color:<?= $dark ?>;padding:24px 48px 26px 48px;border-radius:0 0 12px 12px;">
    <div style="font-family:<?= $font ?>;font-size:14px;line-height:22px;color:#ffffff;">
      <a href="<?= e($siteUrl) ?>" target="_blank" style="color:#ffffff;text-decoration:none;font-weight:600;">Ver el garage</a>
      <span style="color:<?= $accent ?>;">&nbsp;&middot;&nbsp;</span>
      <a href="<?= e($adminUrl) ?>" target="_blank" style="color:#ffffff;text-decoration:none;font-weight:600;">Panel de control</a>
    </div>
    <div style="font-family:<?= $font ?>;font-size:12px;line-height:18px;color:<?= $onDark ?>;padding-top:10px;">Aviso automático de GARAGE · samueltorres.dev/garage</div>
  </td></tr>

</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr>
</table>
</body>
</html>
