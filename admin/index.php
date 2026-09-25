<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/_publicar_helper.php';

if (!es_admin()) {
    redirect('publicar.php?tipo=noticia');
}

$cnt = static function (string $table): int {
    return (int) db()->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
};

$cReportajes = $cnt('reportajes');
$cNoticias   = $cnt('noticias');
$cBoletines  = $cnt('boletines');
$cPodcasts   = $cnt('podcasts');
$cVideos     = $cnt('videos');
$cAutores    = $cnt('autores');
$totalPublicaciones = $cReportajes + $cNoticias + $cBoletines + $cPodcasts + $cVideos;
$totalUsuarios = $cnt('usuarios');

$roles = db()->query('SELECT rol, COUNT(*) AS c FROM usuarios GROUP BY rol ORDER BY c DESC, rol')->fetchAll();

$topAutores = db()->query("SELECT a.id, a.nombres, a.ap_paterno, a.ap_materno, a.nickname, a.es_nickname, COUNT(r.id) AS c FROM autores a LEFT JOIN reportajes r ON r.autor_id = a.id GROUP BY a.id ORDER BY c DESC, a.ap_paterno ASC, a.nombres ASC LIMIT 3")->fetchAll();

$fotos = (int) db()->query("SELECT COUNT(*) FROM fotos WHERE url_foto IS NOT NULL AND url_foto <> ''")->fetchColumn();

$pdfs = (int) db()->query("SELECT
    (SELECT COUNT(*) FROM reportajes WHERE pdf_adjunto IS NOT NULL AND pdf_adjunto <> '' AND pdf_adjunto <> '#') +
    (SELECT COUNT(*) FROM boletines WHERE archivo_pdf IS NOT NULL AND archivo_pdf <> '' AND archivo_pdf <> '#')
")->fetchColumn();

$promFotos = $totalPublicaciones > 0 ? number_format($fotos / $totalPublicaciones, 2, ',', '.') : '0,00';
$promPdfs  = $totalPublicaciones > 0 ? number_format($pdfs / $totalPublicaciones, 2, ',', '.') : '0,00';

$ultFotos = db()->query("SELECT f.id, f.descripcion,
    CASE
        WHEN f.reportaje_id IS NOT NULL THEN 'Reportaje'
        WHEN f.noticia_id IS NOT NULL THEN 'Noticia'
        WHEN f.boletin_id IS NOT NULL THEN 'Boletin'
        ELSE 'Sin asignar'
    END AS tipo,
    COALESCE(
        (SELECT r.titulo FROM reportajes r WHERE r.id = f.reportaje_id),
        (SELECT n.titulo FROM noticias n WHERE n.id = f.noticia_id),
        (SELECT b.numero_boletin FROM boletines b WHERE b.id = f.boletin_id),
        '—'
    ) AS titulo,
    (f.imagen IS NOT NULL AND LENGTH(f.imagen) > 0) AS tiene_img
    FROM fotos f
    ORDER BY f.id DESC
    LIMIT 12")->fetchAll();

$ultPdfs = db()->query("SELECT 'Reportaje' AS tipo, titulo, pdf_adjunto AS archivo, fecha_publicacion AS fecha
    FROM reportajes WHERE pdf_adjunto IS NOT NULL AND pdf_adjunto <> '' AND pdf_adjunto <> '#'
    UNION ALL
    SELECT 'Boletin', numero_boletin, archivo_pdf, fecha_publicacion
    FROM boletines WHERE archivo_pdf IS NOT NULL AND archivo_pdf <> '' AND archivo_pdf <> '#'
    ORDER BY fecha DESC
    LIMIT 12")->fetchAll();

$pdfHref = static function (?string $ruta): string {
    if (is_string($ruta) && $ruta !== '') {
        $archivo = dirname(__DIR__) . '/' . $ruta;
        if (is_file($archivo) && filesize($archivo) > 0) {
            return '../' . $ruta;
        }
    }
    return '';
};

$meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
$chartLabels = [];
$chartData = [];
for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("-$i months", strtotime(date('Y-m-01')));
    $chartLabels[] = $meses[(int) date('n', $ts)];
    $chartData[date('Y-m', $ts)] = 0;
}

$sqlMes = "SELECT mes, SUM(c) AS total FROM (
    SELECT DATE_FORMAT(fecha_publicacion, '%Y-%m') AS mes, COUNT(*) AS c FROM reportajes GROUP BY mes
    UNION ALL SELECT DATE_FORMAT(fecha_publicacion, '%Y-%m'), COUNT(*) FROM noticias GROUP BY 1
    UNION ALL SELECT DATE_FORMAT(fecha_publicacion, '%Y-%m'), COUNT(*) FROM boletines GROUP BY 1
    UNION ALL SELECT DATE_FORMAT(fecha_publicacion, '%Y-%m'), COUNT(*) FROM podcasts GROUP BY 1
    UNION ALL SELECT DATE_FORMAT(fecha_publicacion, '%Y-%m'), COUNT(*) FROM videos GROUP BY 1
) x GROUP BY mes";

foreach (db()->query($sqlMes) as $row) {
    if (isset($chartData[$row['mes']])) {
        $chartData[$row['mes']] = (int) $row['total'];
    }
}
$chartData = array_values($chartData);

$chartLabelsJson = json_encode($chartLabels, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
$chartDataJson   = json_encode($chartData);

$actualizaciones = db()->query("SELECT tipo, titulo, fecha FROM (
    SELECT 'Reportaje' AS tipo, titulo, COALESCE(created_at, updated_at, fecha_publicacion) AS fecha FROM reportajes
    UNION ALL SELECT 'Noticia', titulo, COALESCE(created_at, updated_at, fecha_publicacion) FROM noticias
    UNION ALL SELECT 'Boletin', numero_boletin, COALESCE(created_at, updated_at, fecha_publicacion) FROM boletines
    UNION ALL SELECT 'Podcast', titulo, COALESCE(created_at, updated_at, fecha_publicacion) FROM podcasts
    UNION ALL SELECT 'Video', titulo, COALESCE(created_at, updated_at, fecha_publicacion) FROM videos
) u ORDER BY fecha DESC LIMIT 5")->fetchAll();

$tipoClass = [
    'Reportaje' => 'primary-btn',
    'Noticia'   => 'warning-btn',
    'Boletin'   => 'info-btn',
    'Podcast'   => 'orange-btn',
    'Video'     => 'close-btn',
];

$rolesResumen = '';
foreach ($roles as $i => $r) {
    if ($i > 0) {
        $rolesResumen .= ' · ';
    }
    $rolesResumen .= htmlspecialchars((string) $r['rol'], ENT_QUOTES, 'UTF-8') . ': ' . $r['c'];
}
if ($rolesResumen === '') {
    $rolesResumen = 'Sin usuarios';
}

$fmt = static function (int $n): string {
    return number_format($n, 0, ',', '.');
};

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
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />
    <title>Resumen | Panel de Administración</title>

    <!-- ========== All CSS files linkup ========= -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <style>
      .sidebar-nav-wrapper .sidebar-nav ul .nav-item.nav-item-has-children > a.sidebar-group-title::after {
        display: none;
      }
      .sidebar-nav-wrapper .sidebar-nav ul .nav-item.nav-item-has-children > a.sidebar-group-title {
        cursor: default;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 1.5px;
        font-weight: 700;
        color: #4b5675;
      }
    </style>
  </head>
  <body>
    <!-- ======== Preloader =========== -->
    <div id="preloader">
      <div class="spinner"></div>
    </div>
    <!-- ======== Preloader =========== -->

    <!-- ======== sidebar-nav start =========== -->
    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="index.php">
          <img src="assets/images/logo/dyd.png" alt="logo" />
        </a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li class="nav-item nav-item-has-children">
            <a href="#0" class="sidebar-group-title" aria-expanded="true">
              <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M8.74999 18.3333C12.2376 18.3333 15.1364 15.8128 15.7244 12.4941C15.8448 11.8143 15.2737 11.25 14.5833 11.25H9.99999C9.30966 11.25 8.74999 10.6903 8.74999 10V5.41666C8.74999 4.7263 8.18563 4.15512 7.50586 4.27556C4.18711 4.86357 1.66666 7.76243 1.66666 11.25C1.66666 15.162 4.83797 18.3333 8.74999 18.3333Z" />
                  <path
                    d="M17.0833 10C17.7737 10 18.3432 9.43708 18.2408 8.75433C17.7005 5.14918 14.8508 2.29947 11.2457 1.75912C10.5629 1.6568 10 2.2263 10 2.91665V9.16666C10 9.62691 10.3731 10 10.8333 10H17.0833Z" />
                </svg>
              </span>
              <span class="text">Dashboard</span>
            </a>
            <ul id="ddmenu_1" class="collapse show dropdown-nav" style="display:block;">
              <li>
                <a href="index.php" class="active"> Resumen </a>
              </li>
            </ul>
          </li>

          <li class="nav-item nav-item-has-children">
            <a href="#0" class="sidebar-group-title" aria-expanded="true">
              <span class="icon">
                <i class="lni lni-pencil" style="font-size: 20px;"></i>
              </span>
              <span class="text">Publicar</span>
            </a>
            <ul id="ddmenu_pub" class="collapse show dropdown-nav" style="display:block;">
              <li>
                <a href="publicar.php?tipo=noticia"> Noticias </a>
              </li>
              <li>
                <a href="publicar.php?tipo=reportaje"> Reportajes </a>
              </li>
              <li>
                <a href="publicar.php?tipo=boletin"> Boletines </a>
              </li>
              <li>
                <a href="publicar.php?tipo=podcast"> Podcasts </a>
              </li>
              <li>
                <a href="publicar.php?tipo=video"> Videos </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
    </aside>
    <div class="overlay"></div>
    <!-- ======== sidebar-nav end =========== -->

    <!-- ======== main-wrapper start =========== -->
    <main class="main-wrapper">
      <!-- ========== header start ========== -->
      <header class="header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-lg-5 col-md-5 col-6">
              <div class="header-left d-flex align-items-center">
                <div class="menu-toggle-btn mr-15">
                  <button id="menu-toggle" class="main-btn primary-btn btn-hover">
                    <i class="lni lni-chevron-left me-2"></i> Menu
                  </button>
                </div>
              </div>
            </div>
            <div class="col-lg-7 col-md-7 col-6">
              <div class="header-right">
                <!-- profile start -->
                <div class="profile-box ml-15">
                  <button class="dropdown-toggle bg-transparent border-0" type="button" id="profile"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-info">
                      <div class="info">
                        <div class="image">
                          <i class="lni lni-user"
                            style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #6b7280;"></i>
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
                          <i class="lni lni-user"
                            style="font-size: 16px; color: #6b7280; display: flex; align-items: center; justify-content: center; width: 28px; height: 28px;"></i>
                        </div>
                        <div class="content">
                          <h4 class="text-sm"><?= $usuarioNombre; ?></h4>
                          <a class="text-black/40 dark:text-white/40 hover:text-black dark:hover:text-white text-xs" href="#0"><?= $usuarioEmail; ?></a>
                        </div>
                      </div>
                    </li>
                    <li class="divider"></li>
                    <li>
                      <a href="logout.php"> <i class="lni lni-exit"></i> Salir </a>
                    </li>
                  </ul>
                </div>
                <!-- profile end -->
              </div>
            </div>
          </div>
        </div>
      </header>
      <!-- ========== header end ========== -->

      <!-- ========== section start ========== -->
      <section class="section">
        <div class="container-fluid">
          <!-- ========== title-wrapper start ========== -->
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="title">
                  <h2>Resumen</h2>
                </div>
              </div>
              <!-- end col -->
              <div class="col-md-6">
                <div class="breadcrumb-wrapper">
                  <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                      <li class="breadcrumb-item">
                        <a href="#0">Dashboard</a>
                      </li>
                      <li class="breadcrumb-item active" aria-current="page">
                        Resumen
                      </li>
                    </ol>
                  </nav>
                </div>
              </div>
              <!-- end col -->
            </div>
            <!-- end row -->
          </div>
          <!-- ========== title-wrapper end ========== -->

          <!-- ========== Resumen: contadores ========== -->
          <div class="row">
            <div class="col-xl-2 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon orange">
                  <i class="lni lni-layers"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Total de publicaciones</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($totalPublicaciones); ?></h3>
                  <p class="text-sm text-gray">Reportajes, noticias, boletines, podcasts y videos</p>
                </div>
              </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon purple">
                  <i class="lni lni-book"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Reportajes publicados</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($cReportajes); ?></h3>
                  <p class="text-sm text-gray">Artículos de largo formato</p>
                </div>
              </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon primary">
                  <i class="lni lni-pencil"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Noticias</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($cNoticias); ?></h3>
                  <p class="text-sm text-gray">Noticias recientes publicadas</p>
                </div>
              </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon success">
                  <i class="lni lni-envelope"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Boletines</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($cBoletines); ?></h3>
                  <p class="text-sm text-gray">Ediciones publicadas</p>
                </div>
              </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon purple">
                  <i class="lni lni-microphone"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Podcasts</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($cPodcasts); ?></h3>
                  <p class="text-sm text-gray">Episodios publicados</p>
                </div>
              </div>
            </div>
            <div class="col-xl-2 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon primary">
                  <i class="lni lni-video"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Videos</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($cVideos); ?></h3>
                  <p class="text-sm text-gray">Videos publicados</p>
                </div>
              </div>
            </div>
          </div>
          <!-- ========== Resumen: contadores end ========== -->

          <!-- ========== Gestión de Usuarios / Autores ========== -->
          <div class="row">
            <div class="col-12">
              <div class="title mb-10">
                <h3 class="text-bold mb-20">Gestión de Usuarios / Autores</h3>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-xl-4 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon purple">
                  <i class="lni lni-users"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Total de usuarios por rol</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($totalUsuarios); ?></h3>
                  <p class="text-sm text-gray mb-0"><?= $rolesResumen; ?></p>
                </div>
              </div>
            </div>
            <div class="col-xl-4 col-lg-4 col-sm-6">
              <div class="icon-card mb-30">
                <div class="icon success">
                  <i class="lni lni-user"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Total de autores</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($cAutores); ?></h3>
                  <p class="text-sm text-gray">Autores registrados en el portal</p>
                </div>
              </div>
            </div>
            <div class="col-xl-4 col-lg-4 col-sm-6">
              <div class="card-style mb-30">
                <div class="title mb-20">
                  <h6 class="text-medium">Top autores</h6>
                </div>
                <?php if (empty($topAutores)): ?>
                  <p class="text-sm text-gray">Aún no hay autores registrados.</p>
                <?php else: ?>
                  <?php foreach ($topAutores as $a): ?>
                    <div class="d-flex align-items-center justify-content-between py-2">
                      <div class="d-flex align-items-center">
                        <span
                          style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 50%; background: #f3f6f8; color: #365CF5; font-size: 14px; margin-right: 10px;">
                          <i class="lni lni-star-fill"></i>
                        </span>
                        <p class="text-sm mb-0"><?= htmlspecialchars(autor_nombre_completo($a), ENT_QUOTES, 'UTF-8'); ?></p>
                      </div>
                      <span class="status-btn primary-btn"><?= (int) $a['c'] . ' reportaje' . ((int) $a['c'] !== 1 ? 's' : ''); ?></span>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- ========== Gestión de Usuarios / Autores end ========== -->

          <!-- ========== Actividad ========== -->
          <div class="row">
            <div class="col-12">
              <div class="title mb-10">
                <h3 class="text-bold mb-20">Actividad</h3>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-lg-7">
              <div class="card-style mb-30">
                <div class="title d-flex flex-wrap justify-content-between">
                  <div class="left">
                    <h6 class="text-medium mb-30">Línea de tiempo de actividad</h6>
                  </div>
                </div>
                <div class="chart">
                  <canvas id="ChartActividad" style="width: 100%; height: 360px;"
                    data-labels="<?= htmlspecialchars($chartLabelsJson, ENT_QUOTES, 'UTF-8'); ?>"
                    data-values="<?= htmlspecialchars($chartDataJson, ENT_QUOTES, 'UTF-8'); ?>"></canvas>
                </div>
                <p class="text-sm text-gray mt-10 mb-0">Volumen de publicaciones por mes (últimos 12 meses).</p>
              </div>
            </div>
            <div class="col-lg-5">
              <div class="card-style mb-30">
                <div class="title mb-20">
                  <h6 class="text-medium">Últimas publicaciones</h6>
                </div>
                <?php if (empty($actualizaciones)): ?>
                  <p class="text-sm text-gray">Sin actividad aún.</p>
                <?php else: ?>
                  <?php foreach ($actualizaciones as $a): ?>
                    <div class="d-flex align-items-center justify-content-between py-2">
                      <div class="d-flex align-items-center">
                        <span class="status-btn <?= $tipoClass[$a['tipo']] ?? 'primary-btn'; ?>"><?= htmlspecialchars((string) $a['tipo'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <p class="text-sm mb-0 ml-10 text-truncate" style="max-width: 180px;"
                          title="<?= htmlspecialchars((string) $a['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
                          <?= htmlspecialchars(mb_strimwidth((string) $a['titulo'], 0, 38, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                      </div>
                      <p class="text-xs text-gray mb-0"><?= date('d/m/Y H:i', strtotime((string) $a['fecha'])); ?></p>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <!-- ========== Actividad end ========== -->

          <!-- ========== Recursos Multimedia ========== -->
          <div class="row">
            <div class="col-12">
              <div class="title mb-10">
                <h3 class="text-bold mb-20">Recursos Multimedia</h3>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-xl-6 col-lg-6 col-sm-12">
              <div class="icon-card mb-30" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalFotos">
                <div class="icon orange">
                  <i class="lni lni-image"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">Fotos</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($fotos); ?></h3>
                  <p class="text-sm text-gray mb-0">Promedio: <?= $promFotos; ?> fotos por publicación</p>
                  <p class="text-sm text-primary mt-5" style="color: #365CF5; font-weight: 500;">Ver últimas fotos →</p>
                </div>
              </div>
            </div>
            <div class="col-xl-6 col-lg-6 col-sm-12">
              <div class="icon-card mb-30" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalPdfs">
                <div class="icon success">
                  <i class="lni lni-download"></i>
                </div>
                <div class="content">
                  <h6 class="mb-10">PDFs</h6>
                  <h3 class="text-bold mb-10"><?= $fmt($pdfs); ?></h3>
                  <p class="text-sm text-gray mb-0">Promedio: <?= $promPdfs; ?> PDFs por publicación</p>
                  <p class="text-sm mt-5" style="color: #365CF5; font-weight: 500;">Ver últimos PDFs →</p>
                </div>
              </div>
            </div>
          </div>
          <!-- ========== Recursos Multimedia end ========== -->
        </div>
        <!-- end container -->
      </section>
      <!-- ========== section end ========== -->

      <!-- ========== Modal Fotos ========== -->
      <div class="modal fade" id="modalFotos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header py-3 px-4">
              <h5 class="modal-title text-bold mb-0">Últimas fotos</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <?php if (empty($ultFotos)): ?>
                <p class="text-sm text-gray mb-0">Sin fotos registradas.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table text-nowrap align-middle" style="font-size: 14px;">
                    <thead>
                      <tr>
                        <th style="width: 80px;">Foto</th>
                        <th>Pertenece a</th>
                        <th>Publicación</th>
                        <th>Descripción</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($ultFotos as $f): ?>
                        <tr>
                          <td>
                            <?php if ((int) $f['tiene_img'] === 1): ?>
                              <img src="../assets/images/imagen.php?id=<?= (int) $f['id']; ?>" alt=""
                                style="width: 64px; height: 44px; object-fit: cover; border-radius: 6px;">
                            <?php else: ?>
                              <span class="text-gray" style="font-size: 12px;">Sin imagen</span>
                            <?php endif; ?>
                          </td>
                          <td>
                            <span class="status-btn <?= $tipoClass[$f['tipo']] ?? 'primary-btn'; ?>"><?= htmlspecialchars((string) $f['tipo'], ENT_QUOTES, 'UTF-8'); ?></span>
                          </td>
                          <td><?= htmlspecialchars(mb_strimwidth((string) $f['titulo'], 0, 40, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?= htmlspecialchars(mb_strimwidth((string) $f['descripcion'], 0, 25, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <!-- ========== Modal Fotos end ========== -->

      <!-- ========== Modal PDFs ========== -->
      <div class="modal fade" id="modalPdfs" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">
            <div class="modal-header py-3 px-4">
              <h5 class="modal-title text-bold mb-0">Últimos PDFs</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <?php if (empty($ultPdfs)): ?>
                <p class="text-sm text-gray mb-0">Sin PDFs registrados.</p>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table text-nowrap align-middle" style="font-size: 14px;">
                    <thead>
                      <tr>
                        <th>Pertenece a</th>
                        <th>Publicación</th>
                        <th>Archivo</th>
                        <th>Fecha</th>
                        <th style="width: 90px;">Acción</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($ultPdfs as $p):
                        $href = $pdfHref($p['archivo'] ?? null); ?>
                        <tr>
                          <td>
                            <span class="status-btn <?= $tipoClass[$p['tipo']] ?? 'primary-btn'; ?>"><?= htmlspecialchars((string) $p['tipo'], ENT_QUOTES, 'UTF-8'); ?></span>
                          </td>
                          <td><?= htmlspecialchars(mb_strimwidth((string) $p['titulo'], 0, 45, '…', 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td style="font-size: 12px; color: #6B7280;"><?= htmlspecialchars(basename((string) $p['archivo']), ENT_QUOTES, 'UTF-8'); ?></td>
                          <td><?= date('d/m/Y', strtotime((string) $p['fecha'])); ?></td>
                          <td>
                            <?php if ($href !== ''): ?>
                              <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"
                                class="status-btn primary-btn" style="text-decoration: none;">Ver PDF</a>
                            <?php else: ?>
                              <span class="text-gray" style="font-size: 12px;">No disponible</span>
                            <?php endif; ?>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <!-- ========== Modal PDFs end ========== -->

      <!-- ========== footer start =========== -->
      <footer class="footer">
        <div class="container-fluid">
          <div class="row">
            <div class="col-md-6 order-last order-md-first">
              <div class="copyright text-center text-md-start">
                <p class="text-sm">
                  Designed and Developed by
                  <a href="https://plainadmin.com" rel="nofollow" target="_blank">
                    PlainAdmin
                  </a>
                </p>
              </div>
            </div>
            <!-- end col-->
            <div class="col-md-6">
              <div class="terms d-flex justify-content-center justify-content-md-end">
                <a href="#0" class="text-sm">Term & Conditions</a>
                <a href="#0" class="text-sm ml-15">Privacy & Policy</a>
              </div>
            </div>
          </div>
          <!-- end row -->
        </div>
        <!-- end container -->
      </footer>
      <!-- ========== footer end =========== -->
    </main>
    <!-- ======== main-wrapper end =========== -->

    <!-- ========= All Javascript files linkup ======== -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/Chart.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
      // =========== chart de actividad start
      (function () {
        const canvas = document.getElementById("ChartActividad");
        const ctxActividad = canvas.getContext("2d");
        const chartLabels = JSON.parse(canvas.dataset.labels);
        const chartData = JSON.parse(canvas.dataset.values);

        new Chart(ctxActividad, {
          type: "bar",
          data: {
            labels: chartLabels,
          datasets: [
            {
              label: "Publicaciones",
              backgroundColor: "#365CF5",
              borderRadius: 8,
              barThickness: 18,
              maxBarThickness: 22,
              data: chartData,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              backgroundColor: "#F3F6F8",
              titleColor: "#8F92A1",
              bodyColor: "#171717",
              bodyFont: {
                size: 14,
                weight: "bold",
              },
              displayColors: false,
              padding: { x: 20, y: 10 },
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
                padding: 10,
              },
              grid: {
                color: "rgba(143, 146, 161, .1)",
              },
            },
            x: {
              grid: {
                display: false,
              },
              ticks: {
                padding: 10,
              },
            },
          },
        },
        });
      })();
      // =========== chart de actividad end
    </script>
  </body>
</html>