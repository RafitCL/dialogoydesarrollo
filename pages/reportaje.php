<?php
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

require_once dirname(__DIR__) . '/admin/config.php';

$reportaje = db()->prepare(
    "SELECT r.*, a.nombres AS a_nombres, a.ap_paterno AS a_ap, a.ap_materno AS a_am, a.nickname AS a_nick, a.es_nickname AS a_es
     FROM reportajes r
     LEFT JOIN autores a ON a.id = r.autor_id
     WHERE r.id = :id AND r.activo = 1"
);
$reportaje->execute(['id' => $id]);
$r = $reportaje->fetch();

if (!function_exists('autor_nombre')) {
    function autor_nombre(array $r): string
    {
        if (!empty($r['es_nickname']) && !empty($r['a_nick'])) {
            return $r['a_nick'];
        }
        return trim(($r['a_nombres'] ?? '') . ' ' . ($r['a_ap'] ?? '') . ' ' . ($r['a_am'] ?? ''));
    }
}

$autor = '';
$fechaPubIso = date('c');
$ogImagen = rtrim(SITE_URL, '/') . '/assets/images/reportaje-28-08-26.jpg';

if ($r !== false) {
    $titulo = $r['titulo'];
    $canonical = rtrim(SITE_URL, '/') . '/pages/reportaje.php?id=' . $id;
    $ogTipo = 'article';
    $desarrolloHtml = (string) $r['desarrollo'];

    $descripcion = trim((string) ($r['resumen_corto'] ?? ''));
    if ($descripcion === '') {
        $limpio = trim(strip_tags($desarrolloHtml));
        if ($limpio !== '') {
            $descripcion = $limpio;
        }
    }
    if ($descripcion === '') {
        $descripcion = 'Reportaje de Diálogo y Desarrollo Perú.';
    }
    if (mb_strlen($descripcion) > 160) {
        $descripcion = rtrim(mb_substr($descripcion, 0, 157), " \t\n\r\0\x0B,") . '…';
    }

    $fotoSt = db()->prepare('SELECT id FROM fotos WHERE reportaje_id = :id AND imagen IS NOT NULL AND LENGTH(imagen) > 0 ORDER BY orden ASC, id ASC LIMIT 1');
    $fotoSt->execute(['id' => $id]);
    $fotoRow = $fotoSt->fetch();
    if ($fotoRow !== false) {
        $ogImagen = rtrim(SITE_URL, '/') . '/assets/images/imagen.php?id=' . (int) $fotoRow['id'];
    }

    $autor = autor_nombre($r);
    $fechaPubIso = date('c', strtotime((string) ($r['fecha_publicacion'] ?? 'now')));
}

require __DIR__ . '/_header.php';

if ($r === false) {
    echo '<section class="breadcrumb-area py-sm-5 py-4"><div class="container"><p class="text-muted">No se encontró el reportaje.</p><a href="reportajes.php" class="btn btn-style btn-primary">Volver a Reportajes</a></div></section>';
    require __DIR__ . '/_footer.php';
    exit;
}

$pdfUrl = img_publica($r['pdf_adjunto'] ?? '', '');
?>
<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
    'headline' => mb_substr((string) $r['titulo'], 0, 110),
    'description' => $descripcion,
    'image' => [$ogImagen],
    'datePublished' => $fechaPubIso,
    'dateModified' => $fechaPubIso,
    'author' => ['@type' => 'Person', 'name' => $autor !== '' ? $autor : 'Diálogo y Desarrollo Perú'],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'Diálogo y Desarrollo Perú',
        'logo' => ['@type' => 'ImageObject', 'url' => rtrim(SITE_URL, '/') . '/assets/images/logo.png'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>
</script>
<section class="breadcrumb-area py-sm-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="breadcrumb-contents">
          <h2 class="title-big">Reportajes</h2>
          <div class="breadcrumb">
            <ul>
              <li><a href="../index.php">Inicio</a></li>
              <li class="active"><a href="reportajes.php">Reportajes</a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="w3l-blog mt-lg-5">
  <div class="text-element-9 py-5 mt-lg-5">
    <div class="container py-lg-3">
      <div class="row grid-text-9">
        <div class="col-lg-8">
          <div class="blog-single-post">
            <div class="post-content">
              <h2 class="title-single mb-3"><?= e($r['titulo']) ?></h2>
            </div>
            <div class="blo-singl mb-4">
              <ul class="blog-single-author-date d-flex align-items-center">
                <li>Por <a href="reportajes.php"><?= e($autor) ?></a></li>
                <li><?= e(fecha_comun($r['fecha_publicacion'] ?? null)) ?></li>
              </ul>
            </div>
            <div class="single-post-image mb-4 text-center">
              <?php if ($pdfUrl !== ''): ?>
                <a target="_blank" href="<?= e($pdfUrl) ?>">
                  <img src="<?= e(img_foto('reportaje', (int) $r['id'], '../assets/images/reportaje-28-08-26.jpg')) ?>" class="img-fluid w-100 radius-image" alt="<?= e($r['titulo']) ?>" />
                  <br />Clic en la imagen para ver la infografía completa
                </a>
              <?php else: ?>
                <img src="<?= e(img_foto('reportaje', (int) $r['id'], '../assets/images/reportaje-28-08-26.jpg')) ?>" class="img-fluid w-100 radius-image" alt="<?= e($r['titulo']) ?>" />
              <?php endif; ?>
            </div>
            <div class="single-post-content">
              <?php if (!empty(trim((string) $r['resumen_corto']))): ?>
                <blockquote class="blockquote my-5">
                  <q class="mb-3 d-block"><?= e($r['resumen_corto']) ?></q>
                </blockquote>
              <?php endif; ?>
              <div class="articulo-cuerpo" style="text-align: justify;">
                <?= render_desarrollo($r['desarrollo']) ?>
              </div>
              <?php if ($pdfUrl !== ''): ?>
                <p class="mb-4">
                  <a target="_blank" href="<?= e($pdfUrl) ?>" class="btn btn-style btn-primary">
                    <span class="fa fa-download mr-2" style="color: #dc3545;"></span>Descargar PDF
                  </a>
                </p>
              <?php endif; ?>
            </div>
            <nav class="post-navigation row mb-5 py-4">
              <div class="post-prev col-md-6 pr-sm-5">
                <span class="nav-title"><span class="fa fa-arrow-left mr-2"></span> <a href="reportajes.php">Reportajes</a></span>
              </div>
            </nav>
          </div>
        </div>
        <div class="col-lg-4 left-text-9 mt-lg-0 mt-5 pl-lg-4">
          <div class="left-top-9 mt-5 pt-sm-3">
            <h6 class="heading-small-text-9 mb-3">Últimas noticias</h6>
            <?php
            $ultimos = db()->prepare('SELECT id, titulo, fecha_publicacion FROM reportajes WHERE id != :id AND activo = 1 ORDER BY fecha_publicacion DESC LIMIT 3');
            $ultimos->execute(['id' => $id]);
            foreach ($ultimos->fetchAll() as $u): ?>
              <a href="reportaje.php?id=<?= (int) $u['id'] ?>" class="p-post d-block py-2">
                <h6 class="text-left-inner-9"><?= e($u['titulo']) ?></h6>
                <span class="sub-inner-text-9"><?= e(fecha_comun($u['fecha_publicacion'] ?? null)) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
          <div class="categories mt-5 pt-sm-3">
            <h6 class="heading-small-text-9">Archivos</h6>
            <?php $archivos = db()->query("SELECT DISTINCT DATE_FORMAT(fecha_publicacion, '%Y-%m') AS mes FROM reportajes WHERE activo = 1 ORDER BY mes DESC")->fetchAll(); ?>
            <?php if (empty($archivos)): ?>
              <p class="text-muted">Sin archivos por el momento.</p>
            <?php else: ?>
              <ul>
                <?php foreach ($archivos as $a): ?>
                  <li><a href="reportajes.php?mes=<?= e($a['mes']) ?>"><?= e(mes_anio($a['mes'])) ?></a></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>