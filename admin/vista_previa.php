<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/_publicar_helper.php';

$tipos = [
    'noticia'   => ['label' => 'Noticias',   'singular' => 'Noticia',   'tabla' => 'noticias',         'icon' => 'lni-pencil'],
    'reportaje' => ['label' => 'Reportajes', 'singular' => 'Reportaje', 'tabla' => 'reportajes',       'icon' => 'lni-book'],
    'boletin'   => ['label' => 'Boletines',  'singular' => 'Boletín',   'tabla' => 'boletines',        'icon' => 'lni-envelope'],
    'podcast'   => ['label' => 'Podcasts',   'singular' => 'Podcast',   'tabla' => 'podcasts',         'icon' => 'lni-microphone'],
    'video'     => ['label' => 'Videos',     'singular' => 'Video',     'tabla' => 'videos',           'icon' => 'lni-video'],
];

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function preview_url(?string $s): string
{
    $s = (string) $s;
    if (str_starts_with($s, 'uploads/')) {
        return '../' . $s;
    }
    return $s;
}

$tipo = isset($_GET['tipo']) ? (string) $_GET['tipo'] : 'noticia';
if (!isset($tipos[$tipo])) {
    $tipo = 'noticia';
}

$info = $tipos[$tipo];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$row = null;
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM `{$info['tabla']}` WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
}

if (!is_array($row)) {
    flash_set('error', 'Registro no encontrado.');
    redirect('publicar.php?tipo=' . $tipo);
}

$galeria = [];
$galeriaSql = null;
if (in_array($tipo, ['reportaje', 'boletin', 'noticia'], true)) {
    $col = match ($tipo) {
        'boletin' => 'boletin_id',
        'noticia' => 'noticia_id',
        default   => 'reportaje_id',
    };
    $galeriaSql = "SELECT url_foto AS foto, descripcion AS titulo, orden FROM fotos WHERE `$col` = :cid ORDER BY orden ASC, id ASC";
}
if ($galeriaSql !== null) {
    $galeriaStmt = db()->prepare($galeriaSql);
    $galeriaStmt->execute(['cid' => $id]);
    $galeria = $galeriaStmt->fetchAll();
}

$nombreAutor = null;
if ($tipo === 'reportaje' && !empty($row['autor_id'])) {
    $au = db()->prepare('SELECT nombres, ap_paterno, ap_materno, nickname, es_nickname FROM autores WHERE id = :id');
    $au->execute(['id' => (int) $row['autor_id']]);
    $autorRow = $au->fetch();
    if (is_array($autorRow)) {
        $nombreAutor = autor_nombre_completo($autorRow);
    }
}

$fecha = date('d/m/Y', strtotime((string) $row['fecha_publicacion']));
$titulo = '';
$cuerpo = '';
$img = '';
$embed = '';
$cta = [];

switch ($tipo) {
    case 'noticia':
        $titulo = (string) $row['titulo'];
        $img = (string) ($row['foto'] ?? '');
        $cta[] = ['href' => (string) ($row['link_externo'] ?? ''), 'label' => 'Leer la noticia completa', 'icon' => 'lni-link'];
        break;

    case 'reportaje':
        $titulo = (string) $row['titulo'];
        $cuerpo = trim((string) ($row['resumen_corto'] ?? '')) !== '' ? (string) $row['resumen_corto'] : (string) $row['desarrollo'];
        $img = (string) ($row['foto_principal'] ?? '');
        if (trim((string) ($row['pdf_adjunto'] ?? '')) !== '') {
            $cta[] = ['href' => (string) $row['pdf_adjunto'], 'label' => 'Descargar PDF del reportaje', 'icon' => 'lni-download'];
        }
        break;

    case 'boletin':
        $titulo = 'Boletín N° ' . (string) $row['numero_boletin'];
        $cuerpo = (string) ($row['resumen'] ?? '');
        $img = (string) ($row['foto_portada'] ?? '');
        $cta[] = ['href' => (string) ($row['archivo_pdf'] ?? ''), 'label' => 'Descargar PDF del boletín', 'icon' => 'lni-download'];
        break;

    case 'podcast':
    case 'video':
        $titulo = (string) $row['titulo'];
        $embed = (string) ($row['url_embed'] ?? '');
        $img = $galeria[0]['foto'] ?? '';
        break;
}

$img = trim($img);
$embed = trim($embed);
$esEmbed = $embed !== '' && filter_var($embed, FILTER_VALIDATE_URL) !== false;

$galeriaMostrar = $galeria;
if (($tipo === 'podcast' || $tipo === 'video') && isset($galeriaMostrar[0])) {
    array_shift($galeriaMostrar);
}

$usuarioNombre = htmlspecialchars((string) ($_SESSION['user_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
$usuarioRol    = htmlspecialchars((string) ($_SESSION['user_rol'] ?? ''), ENT_QUOTES, 'UTF-8');
$usuarioEmail  = htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <title>Vista previa <?= $info['singular'] ?> | Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
      .preview-frame { border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, .10); }
      .preview-chrome { display: flex; align-items: center; gap: 8px; background: #1f2937; padding: 10px 14px; }
      .preview-dot { width: 11px; height: 11px; border-radius: 50%; }
      .preview-url { flex: 1; background: #374151; color: #d1d5db; font-size: 12px; border-radius: 5px; padding: 5px 10px; margin-left: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
      .preview-nav { display: flex; align-items: center; justify-content: space-between; padding: 14px 34px; background: #0f172a; color: #fff; }
      .preview-nav strong { font-size: 18px; letter-spacing: .5px; }
      .preview-nav .preview-navlinks { font-size: 13px; color: #cbd5e1; }
      .preview-nav .preview-navlinks span { margin-left: 18px; }
      .preview-body { background: #fff; padding: 34px; font-family: Georgia, Cambria, "Times New Roman", serif; }
      @media (max-width: 576px) { .preview-body { padding: 20px; } }
      .preview-kicker { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: inline-block; font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase; color: #92400e; background: #fef3c7; padding: 4px 10px; border-radius: 4px; }
      .preview-title { font-size: 34px; line-height: 1.2; font-weight: 700; color: #111827; margin: 14px 0 10px; }
      .preview-meta { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 13px; color: #6b7280; margin-bottom: 20px; }
      .preview-figure { margin: 0 0 22px; }
      .preview-figure img { width: 100%; border-radius: 8px; max-height: 440px; object-fit: cover; }
      .preview-text { font-size: 17px; line-height: 1.75; color: #374151; }
      .preview-embed { margin: 18px 0; }
      .preview-embed iframe { border: 0; border-radius: 8px; min-height: 340px; }
      .preview-placeholder { border: 2px dashed #d1d5db; border-radius: 8px; padding: 34px 20px; text-align: center; color: #6b7280; }
      .preview-placeholder i { font-size: 34px; color: #9ca3af; }
      .preview-placeholder p { margin: 10px 0 0; font-size: 13px; }
      .preview-galeria { margin-top: 26px; }
      .preview-galeria img { width: 100%; border-radius: 8px; aspect-ratio: 4 / 3; object-fit: cover; }
      .preview-galeria figcaption { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 12px; color: #6b7280; margin-top: 6px; }
      .preview-cta { margin-top: 26px; display: flex; gap: 10px; flex-wrap: wrap; }
      .preview-btn { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 600; }
      .preview-btn.on { background: #2563eb; color: #fff; }
      .preview-btn.on:hover { background: #1d4ed8; color: #fff; }
      .preview-btn.off { background: #e5e7eb; color: #6b7280; cursor: not-allowed; }
    </style>
  </head>
  <body>
    <div id="preloader"><div class="spinner"></div></div>

    <!-- sidebar -->
    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="index.php"><img src="assets/images/logo/logo.svg" alt="logo" /></a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item">
            <a href="index.php"><span class="text">Dashboard</span></a>
          </li>
          <li class="nav-item">
            <a href="publicar.php"><span class="text">Publicar</span></a>
          </li>
        </ul>
      </nav>
    </aside>
    <div class="overlay"></div>

    <!-- main -->
    <main class="main-wrapper">
      <header class="header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-5 col-md-5 col-6">
              <div class="header-left d-flex align-items-center">
                <div class="menu-toggle-btn mr-15">
                  <button id="menu-toggle" class="main-btn primary-btn btn-hover"><i class="lni lni-chevron-left me-2"></i> Menu</button>
                </div>
              </div>
            </div>
            <div class="col-lg-7 col-md-7 col-6">
              <div class="header-right">
                <div class="profile-box ml-15">
                  <button class="dropdown-toggle bg-transparent border-0" type="button" id="profile" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-info">
                      <div class="info">
                        <div class="image">
                          <i class="lni lni-user" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #6b7280;"></i>
                        </div>
                        <div>
                          <h6 class="fw-500"><?= $usuarioNombre; ?></h6>
                          <p><?= $usuarioRol !== '' ? $usuarioRol : 'Usuario'; ?></p>
                        </div>
                      </div>
                    </div>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profile">
                    <li>
                      <div class="author-info flex items-center !p-1">
                        <div class="image">
                          <i class="lni lni-user" style="font-size: 16px; color: #6b7280; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px;"></i>
                        </div>
                        <div class="content">
                          <h4 class="text-sm"><?= $usuarioNombre; ?></h4>
                          <a class="text-black/40 dark:text-white/40 hover:text-black dark:hover:text-white text-xs" href="#0"><?= $usuarioEmail; ?></a>
                        </div>
                      </div>
                    </li>
                    <li class="divider"></li>
                    <li><a href="logout.php"> <i class="lni lni-exit"></i> Salir </a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </header>

      <section class="section">
        <div class="container-fluid">
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title"><h2>Vista previa — <?= $info['label'] ?></h2></div>
              </div>
              <div class="col-md-6">
                <div class="breadcrumb-wrapper">
                  <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                      <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                      <li class="breadcrumb-item"><a href="publicar.php">Publicar</a></li>
                      <li class="breadcrumb-item"><a href="publicar.php?tipo=<?= $tipo ?>"><?= $info['label'] ?></a></li>
                      <li class="breadcrumb-item active" aria-current="page">Vista previa</li>
                    </ol>
                  </nav>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-10 mx-auto">
              <div class="card-style mb-30">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                  <a href="publicar.php?tipo=<?= $tipo ?>" class="main-btn btn-light btn-hover">
                    <i class="lni lni-arrow-left me-2"></i> Volver al listado
                  </a>
                  <div>
                    <span class="status-btn info-btn"><i class="lni <?= $info['icon'] ?> me-1"></i><?= $info['singular'] ?></span>
                    <span class="status-btn dark-btn ms-1">ID #<?= (int) $row['id'] ?></span>
                  </div>
                </div>

                <div class="preview-frame">
                  <div class="preview-chrome">
                    <span class="preview-dot" style="background:#f87171;"></span>
                    <span class="preview-dot" style="background:#fbbf24;"></span>
                    <span class="preview-dot" style="background:#34d399;"></span>
                    <span class="preview-url">https://www.peruportal.com/<?= $tipo ?>/<?= (int) $row['id'] ?></span>
                  </div>

                  <div class="preview-nav">
                    <strong>Perú Portal</strong>
                    <div class="preview-navlinks">
                      <span>Inicio</span><span>Noticias</span><span>Reportajes</span><span>Boletines</span><span>Podcasts</span><span>Videos</span>
                    </div>
                  </div>

                  <div class="preview-body">
                    <span class="preview-kicker"><?= h($info['singular']) ?></span>
                    <h1 class="preview-title"><?= h($titulo) ?></h1>
                    <div class="preview-meta"><?= h($fecha) ?> <?= $nombreAutor !== null ? '· Por ' . h($nombreAutor) : '' ?> · Publicado por <?= h($usuarioNombre) ?></div>

                    <?php if ($img !== ''): ?>
                      <figure class="preview-figure">
                        <img src="<?= h(preview_url($img)) ?>" alt="<?= h($titulo) ?>" onerror="this.closest('figure').style.display='none';" />
                      </figure>
                    <?php endif; ?>

                    <?php if ($cuerpo !== ''): ?>
                      <p class="preview-text"><?= h($cuerpo) ?></p>
                    <?php endif; ?>

                    <?php if ($esEmbed): ?>
                      <div class="preview-embed ratio ratio-16x9">
                        <iframe src="<?= h(preview_url($embed)) ?>" title="<?= h($titulo) ?>" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                      </div>
                    <?php elseif ($tipo === 'podcast' || $tipo === 'video'): ?>
                      <div class="preview-embed preview-placeholder">
                        <i class="lni <?= $tipo === 'video' ? 'lni-video' : 'lni-microphone' ?>"></i>
                        <p>Aquí se incrustará el reproductor del <?= h(mb_strtolower($info['singular'])) ?> a partir del enlace <code>url_embed</code>.</p>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($galeriaMostrar)): ?>
                      <div class="preview-galeria">
                        <div class="row g-3">
                          <?php foreach ($galeriaMostrar as $foto): ?>
                            <div class="col-sm-6 col-lg-4">
                              <figure class="mb-0">
                                <img src="<?= h(preview_url($foto['foto'])) ?>" alt="<?= h($foto['titulo']) ?>" onerror="this.closest('.col-sm-6').style.display='none';" />
                                <?php if (trim((string) $foto['titulo']) !== ''): ?>
                                  <figcaption><?= h($foto['titulo']) ?></figcaption>
                                <?php endif; ?>
                              </figure>
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>

                    <?php if (!empty($cta)): ?>
                      <div class="preview-cta">
                        <?php foreach ($cta as $boton): ?>
                          <?php if ($boton['href'] !== '' && $boton['href'] !== '#'): ?>
                            <a class="preview-btn on" href="<?= h(preview_url($boton['href'])) ?>" target="_blank" rel="noopener">
                              <i class="lni <?= h($boton['icon']) ?>"></i><?= h($boton['label']) ?>
                            </a>
                          <?php else: ?>
                            <span class="preview-btn off" title="Enlace pendiente por completar">
                              <i class="lni <?= h($boton['icon']) ?>"></i><?= h($boton['label']) ?>
                            </span>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-6 order-last order-md-first">
              <div class="copyright text-center text-md-start">
                <p class="text-sm">Designed and Developed by <a href="https://plainadmin.com" rel="nofollow" target="_blank">PlainAdmin</a></p>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </main>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
  </body>
</html>