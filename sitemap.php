<?php
declare(strict_types=1);

require __DIR__ . '/admin/config.php';

function sitemap_entry(string $loc, ?string $lastmod = null, string $freq = 'monthly', string $prio = '0.7'): string
{
    $s = "  <url>\n";
    $s .= '    <loc>' . htmlspecialchars($loc, ENT_XML1, 'UTF-8') . "</loc>\n";
    if ($lastmod !== null && $lastmod !== '') {
        $ts = strtotime($lastmod);
        if ($ts !== false) {
            $s .= '    <lastmod>' . date('Y-m-d', $ts) . "</lastmod>\n";
        }
    }
    $s .= '    <changefreq>' . $freq . "</changefreq>\n";
    $s .= '    <priority>' . $prio . "</priority>\n";
    $s .= "  </url>\n";
    return $s;
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

$base = rtrim(SITE_URL, '/');

$ultimo = db()->query("SELECT MAX(fecha_publicacion) AS f FROM reportajes WHERE activo = 1")->fetch();
echo sitemap_entry($base . '/', (string) ($ultimo['f'] ?? ''), 'daily', '1.0');

foreach (['reportajes.php', 'podcast.php', 'video.php', 'boletines.php', 'alianzas.php', 'nosotros.php', 'contacto.php'] as $pg) {
    echo sitemap_entry($base . '/pages/' . $pg, null, 'monthly', '0.6');
}

$st = db()->query('SELECT id, fecha_publicacion FROM reportajes WHERE activo = 1');
foreach ($st as $r) {
    echo sitemap_entry($base . '/pages/reportaje.php?id=' . (int) $r['id'], (string) $r['fecha_publicacion'], 'monthly', '0.8');
}

echo '</urlset>' . "\n";