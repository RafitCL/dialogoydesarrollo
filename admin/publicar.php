<?php
require __DIR__ . '/auth.php';

define('TIPOS', [
    'noticia'   => ['label' => 'Noticias',        'singular' => 'Noticia',    'icon' => 'lni-pencil',    'tabla' => 'noticias',         'titulo' => 'titulo'],
    'reportaje' => ['label' => 'Reportajes',      'singular' => 'Reportaje',  'icon' => 'lni-book',      'tabla' => 'reportajes',       'titulo' => 'titulo'],
    'boletin'   => ['label' => 'Boletines',       'singular' => 'Boletín',    'icon' => 'lni-envelope',  'tabla' => 'boletines',        'titulo' => 'numero_boletin'],
    'podcast'   => ['label' => 'Podcasts',        'singular' => 'Podcast',    'icon' => 'lni-microphone','tabla' => 'podcasts',         'titulo' => 'titulo'],
    'video'     => ['label' => 'Videos',          'singular' => 'Video',      'icon' => 'lni-video',     'tabla' => 'videos',           'titulo' => 'titulo'],
]);

$tipo = isset($_GET['tipo']) ? (string) $_GET['tipo'] : 'noticia';

if (!isset(TIPOS[$tipo])) {
    $tipo = 'noticia';
}

$info = TIPOS[$tipo];
$tabla = $info['tabla'];
$colTitulo = $info['titulo'];

$registros = db()->query("SELECT * FROM `$tabla` ORDER BY id DESC")->fetchAll();

$fotoCounts = [];
if (!empty($registros) && in_array($tipo, ['reportaje', 'boletin', 'noticia'], true)) {
    $colFoto = match ($tipo) {
        'boletin' => 'boletin_id',
        'noticia' => 'noticia_id',
        default   => 'reportaje_id',
    };
    $stmt = db()->prepare("SELECT `$colFoto` AS cid, COUNT(*) AS c FROM fotos WHERE `$colFoto` IS NOT NULL GROUP BY `$colFoto`");
    $stmt->execute();
    foreach ($stmt->fetchAll() as $row) {
        $fotoCounts[(int) $row['cid']] = (int) $row['c'];
    }
}

$usuarioNombre = htmlspecialchars((string) ($_SESSION['user_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
$usuarioRol    = htmlspecialchars((string) ($_SESSION['user_rol'] ?? ''), ENT_QUOTES, 'UTF-8');
$usuarioEmail  = htmlspecialchars((string) ($_SESSION['user_email'] ?? ''), ENT_QUOTES, 'UTF-8');
$flashSuccess = flash_get('success');
$flashError = flash_get('error');
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />
    <title>Publicar | Panel de Administración</title>
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
      .ddp-toggle {
        width: 22px;
        height: 22px;
        border: 2px solid #9ca3af;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all .15s ease;
      }
      .ddp-toggle:hover {
        border-color: #6b7280;
      }
      .ddp-toggle.checked {
        background: #000;
        border-color: #000;
      }
      .ddp-toggle.checked::after {
        content: '\2713';
        color: #fff;
        font-size: 14px;
        line-height: 1;
        font-weight: 700;
      }
    </style>
  </head>
  <body>
    <div id="preloader"><div class="spinner"></div></div>

    <!-- sidebar -->
    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="index.php"><img src="assets/images/logo/dyd.png" alt="logo" /></a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <?php if (es_admin()): ?>
          <li class="nav-item nav-item-has-children">
            <a href="#0" class="sidebar-group-title" aria-expanded="true">
              <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M8.74999 18.3333C12.2376 18.3333 15.1364 15.8128 15.7244 12.4941C15.8448 11.8143 15.2737 11.25 14.5833 11.25H9.99999C9.30966 11.25 8.74999 10.6903 8.74999 10V5.41666C8.74999 4.7263 8.18563 4.15512 7.50586 4.27556C4.18711 4.86357 1.66666 7.76243 1.66666 11.25C1.66666 15.162 4.83797 18.3333 8.74999 18.3333Z" />
                  <path d="M17.0833 10C17.7737 10 18.3432 9.43708 18.2408 8.75433C17.7005 5.14918 14.8508 2.29947 11.2457 1.75912C10.5629 1.6568 10 2.2263 10 2.91665V9.16666C10 9.62691 10.3731 10 10.8333 10H17.0833Z" />
                </svg>
              </span>
              <span class="text">Dashboard</span>
            </a>
            <ul class="collapse show dropdown-nav" style="display:block;">
              <li><a href="index.php"> Resumen </a></li>
            </ul>
          </li>
          <?php endif; ?>

          <li class="nav-item nav-item-has-children">
            <a href="#0" class="sidebar-group-title" aria-expanded="true">
              <span class="icon">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3.33332 4.16667C3.33332 3.24619 4.07952 2.5 5 2.5H15C15.9205 2.5 16.6667 3.24619 16.6667 4.16667V6.66667H3.33332V4.16667Z" fill="currentColor" />
                  <path d="M3.33332 8.33333H16.6667V15.8333C16.6667 16.7538 15.9205 17.5 15 17.5H5C4.07952 17.5 3.33332 16.7538 3.33332 15.8333V8.33333Z" fill="currentColor" />
                </svg>
              </span>
              <span class="text">Publicar</span>
            </a>
            <ul class="collapse show dropdown-nav" style="display:block;">
              <?php foreach (TIPOS as $key => $t): ?>
                <li>
                  <a href="publicar.php?tipo=<?= $key ?>" class="<?= $key === $tipo ? 'active' : '' ?>"> <?= $t['label'] ?> </a>
                </li>
              <?php endforeach; ?>
            </ul>
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
                <div class="title d-flex align-items-center gap-2">
                  <img src="assets/images/logo/dyd.png" alt="Diálogo y Desarrollo" style="height:36px;" />
                  <h2 class="mb-0"><?= $info['label'] ?></h2>
                </div>
              </div>
              <div class="col-md-6">
                <div class="breadcrumb-wrapper">
                  <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                      <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                      <li class="breadcrumb-item"><a href="publicar.php">Publicar</a></li>
                      <li class="breadcrumb-item active" aria-current="page"><?= $info['label'] ?></li>
                    </ol>
                  </nav>
                </div>
              </div>
            </div>
          </div>

          <div class="row mb-30">
            <div class="col-12">
              <?php if ($flashSuccess !== ''): ?>
                <div class="alert alert-success py-2" role="alert"><?= htmlspecialchars($flashSuccess) ?></div>
              <?php endif; ?>
              <?php if ($flashError !== ''): ?>
                <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($flashError) ?></div>
              <?php endif; ?>
              <a href="publicar_nuevo.php?tipo=<?= $tipo ?>" class="main-btn primary-btn btn-hover">
                <i class="lni lni-plus me-2"></i> Agregar <?= $info['singular'] ?>
              </a>
            </div>
          </div>

          <div class="card-style mb-30">
            <div class="table-responsive">
              <table class="table top-selling-table">
                <thead>
                  <tr>
                    <th><h6 class="text-sm text-medium">Título</h6></th>
                    <th><h6 class="text-sm text-medium">Foto</h6></th>
                    <th><h6 class="text-sm text-medium">Fecha</h6></th>
                    <?php if ($tipo === 'noticia'): ?>
                      <th><h6 class="text-sm text-medium">Link</h6></th>
                    <?php endif; ?>
                    <?php if ($tipo === 'reportaje'): ?>
                      <th><h6 class="text-sm text-medium">Resumen</h6></th>
                      <th><h6 class="text-sm text-medium">Desarrollo</h6></th>
                      <th><h6 class="text-sm text-medium">PDF</h6></th>
                      <th><h6 class="text-sm text-medium">Destacado</h6></th>
                    <?php endif; ?>
                    <?php if ($tipo === 'boletin'): ?>
                      <th><h6 class="text-sm text-medium">Resumen</h6></th>
                      <th><h6 class="text-sm text-medium">PDF</h6></th>
                    <?php endif; ?>
                    <th><h6 class="text-sm text-medium">Acciones</h6></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($registros)): ?>
                    <?php $colspan = 4 + ($tipo === 'noticia' ? 1 : 0) + ($tipo === 'reportaje' ? 4 : 0) + ($tipo === 'boletin' ? 2 : 0); ?>
                    <tr><td colspan="<?= $colspan ?>"><p class="text-sm text-gray">Aún no hay <?= mb_strtolower($info['label']) ?> publicados.</p></td></tr>
                  <?php else: ?>
                    <?php foreach ($registros as $r): ?>
                      <?php
                        $tituloVal = $tipo === 'boletin' ? ($r['numero_boletin'] ?? '') : ($r['titulo'] ?? '');
                        $colFoto = match ($tipo) {
                            'boletin'   => 'foto_portada',
                            'noticia'   => 'foto',
                            'reportaje' => 'foto_principal',
                            default     => '',
                        };
                        $colFk = match ($tipo) {
                            'boletin' => 'boletin_id',
                            'noticia' => 'noticia_id',
                            'reportaje' => 'reportaje_id',
                            default => '',
                        };
                        $rutaFoto = $colFoto !== '' ? (string) ($r[$colFoto] ?? '') : '';
                        $fotoData = null;
                        if ($colFk !== '') {
                            $stmtF = db()->prepare("SELECT imagen FROM fotos WHERE $colFk = :id ORDER BY orden ASC, id ASC LIMIT 1");
                            $stmtF->execute(['id' => (int) $r['id']]);
                            $fila = $stmtF->fetch();
                            if ($fila !== false && $fila['imagen'] !== null) {
                                $fotoData = $fila['imagen'];
                            }
                        }
                        if ($fotoData !== null) {
                            $fotoSrc = 'data:image/jpeg;base64,' . base64_encode($fotoData);
                        } elseif ($rutaFoto !== '') {
                            $fotoSrc = '../' . htmlspecialchars($rutaFoto, ENT_QUOTES, 'UTF-8');
                        } else {
                            $fotoSrc = '';
                        }
                        $isActivo = (int) ($r['activo'] ?? 1) === 1;
                      ?>
                      <tr style="<?= $isActivo ? '' : 'opacity:.55;' ?>">
                        <td><p class="text-sm fw-500"><?= htmlspecialchars((string) $tituloVal, ENT_QUOTES, 'UTF-8') ?></p></td>
                        <td>
                          <?php if ($fotoSrc !== ''): ?>
                            <img src="<?= $fotoSrc ?>" alt="" style="width:50px;height:36px;object-fit:cover;border-radius:4px;" onerror="this.style.display='none'" />
                          <?php else: ?>
                            <span class="status-btn dark-btn">—</span>
                          <?php endif; ?>
                        </td>
                        <td><p class="text-sm"><?= date('d/m/Y', strtotime((string) $r['fecha_publicacion'])) ?></p></td>
                        <?php if ($tipo === 'noticia'): ?>
                          <td><a href="<?= htmlspecialchars((string) ($r['link_externo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="text-sm text-primary" title="Abrir link"><i class="lni lni-link"></i></a></td>
                        <?php endif; ?>
                        <?php if ($tipo === 'reportaje'): ?>
                          <td><p class="text-sm" style="max-width:220px;white-space:normal;"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($r['resumen_corto'] ?? '')), 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></p></td>
                          <td><p class="text-sm" style="max-width:220px;white-space:normal;"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($r['desarrollo'] ?? '')), 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></p></td>
                          <td>
                            <?php if (!empty($r['pdf_adjunto'])): ?>
                              <a href="../<?= htmlspecialchars((string) $r['pdf_adjunto'], ENT_QUOTES, 'UTF-8') ?>" class="status-btn success-btn" title="Descargar PDF" style="padding:4px 8px;" download><?= htmlspecialchars(basename((string) $r['pdf_adjunto'])) ?></a>
                            <?php else: ?>
                              <span class="status-btn dark-btn">—</span>
                            <?php endif; ?>
                          </td>
                        <?php endif; ?>
                        <?php if ($tipo === 'reportaje'): ?>
                          <td>
                            <button type="button" class="ddp-toggle <?= (int) ($r['es_destacado'] ?? 0) === 1 ? 'checked' : '' ?>" title="<?= (int) ($r['es_destacado'] ?? 0) === 1 ? 'Es destacado — clic para quitar' : 'Marcar como destacado' ?>" aria-checked="<?= (int) ($r['es_destacado'] ?? 0) === 1 ? 'true' : 'false' ?>" onclick="toggleDestacado(<?= (int) $r['id'] ?>, this)"></button>
                          </td>
                        <?php endif; ?>
                        <?php if ($tipo === 'boletin'): ?>
                          <td><p class="text-sm" style="max-width:220px;white-space:normal;"><?= htmlspecialchars(mb_strimwidth(strip_tags((string) ($r['resumen'] ?? '')), 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></p></td>
                          <td>
                            <?php if (!empty($r['archivo_pdf']) && $r['archivo_pdf'] !== '#'): ?>
                              <a href="../<?= htmlspecialchars((string) $r['archivo_pdf'], ENT_QUOTES, 'UTF-8') ?>" class="status-btn success-btn" title="Descargar PDF" style="padding:4px 8px;" download><?= htmlspecialchars(basename((string) $r['archivo_pdf'])) ?></a>
                            <?php else: ?>
                              <span class="status-btn dark-btn">—</span>
                            <?php endif; ?>
                          </td>
                        <?php endif; ?>
                        <td>
                          <div class="d-flex gap-1">
                            <a href="publicar_nuevo.php?tipo=<?= $tipo ?>&id=<?= (int) $r['id'] ?>" class="status-btn info-btn" title="Editar" style="padding:4px 8px;"><i class="lni lni-pencil"></i></a>
                            <button type="button" class="status-btn <?= $isActivo ? 'success-btn' : 'dark-btn' ?>" title="<?= $isActivo ? 'Desactivar' : 'Activar' ?>" style="padding:4px 8px;cursor:pointer;" onclick="toggleActivo('<?= $tipo ?>', <?= (int) $r['id'] ?>, this)"><i class="lni lni-<?= $isActivo ? 'eye' : 'eye-slash' ?>"></i></button>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
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
    <script>
    function toggleActivo(tabla, id, btn){
      fetch('toggle_activo.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tabla='+encodeURIComponent(tabla)+'&id='+id})
      .then(function(r){return r.json()})
      .then(function(d){
        if(d.ok){
          var tr=btn.closest('tr');
          if(d.activo){
            tr.style.opacity='1';
            btn.className='status-btn success-btn';
            btn.title='Desactivar';
            btn.innerHTML='<i class="lni lni-eye"></i>';
          } else {
            tr.style.opacity='.55';
            btn.className='status-btn dark-btn';
            btn.title='Activar';
            btn.innerHTML='<i class="lni lni-eye-slash"></i>';
          }
        }
      });
    }

    function toggleDestacado(id, btn){
      fetch('toggle_destacado.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'id='+id})
      .then(function(r){return r.json()})
      .then(function(d){
        if(d.ok){
          var todos = document.querySelectorAll('[onclick^="toggleDestacado"]');
          todos.forEach(function(b){
            b.classList.remove('checked');
            b.title='Marcar como destacado';
            b.setAttribute('aria-checked','false');
          });
          if(d.es_destacado){
            btn.classList.add('checked');
            btn.title='Es destacado — clic para quitar';
            btn.setAttribute('aria-checked','true');
          }
        }
      });
    }
    </script>
  </body>
</html>