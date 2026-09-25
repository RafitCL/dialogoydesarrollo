<?php
require __DIR__ . '/auth.php';
require __DIR__ . '/_publicar_helper.php';

$tipos = [
    'noticia'   => ['label' => 'Noticia',   'tabla' => 'noticias',         'icon' => 'lni-pencil'],
    'reportaje' => ['label' => 'Reportaje', 'tabla' => 'reportajes',       'icon' => 'lni-book'],
    'boletin'   => ['label' => 'Boletín',   'tabla' => 'boletines',        'icon' => 'lni-envelope'],
    'podcast'   => ['label' => 'Podcast',   'tabla' => 'podcasts',         'icon' => 'lni-microphone'],
    'video'     => ['label' => 'Video',     'tabla' => 'videos',           'icon' => 'lni-video'],
];

$tipo = isset($_GET['tipo']) ? (string) $_GET['tipo'] : 'noticia';
if (!isset($tipos[$tipo])) {
    $tipo = 'noticia';
}

$info = $tipos[$tipo];
$tabla = $info['tabla'];
$error = '';
$flashSuccess = flash_get('success');

$editId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editando = $editId > 0;
$editado = null;
if ($editando) {
    $stmtR = db()->prepare("SELECT * FROM `$tabla` WHERE id = :id");
    $stmtR->execute(['id' => $editId]);
    $editado = $stmtR->fetch();
    if (!$editado) {
        flash_set('error', 'El registro que intentas editar ya no existe.');
        redirect('publicar.php?tipo=' . $tipo);
    }
}

function h(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar_autor'])) {
    $esAjax = (string) ($_POST['ajax'] ?? '') === '1';
    if ($esAjax) {
        header('Content-Type: application/json');
    }
    $autorNombres = trim($_POST['autor_nombres'] ?? '');
    $autorApPaterno = trim($_POST['autor_ap_paterno'] ?? '');
    $autorApMaterno = trim($_POST['autor_ap_materno'] ?? '');
    $autorNickname = trim($_POST['autor_nickname'] ?? '');
    $esNickname = isset($_POST['autor_es_nickname']) && (string) $_POST['autor_es_nickname'] === '1' ? 1 : 0;

    if ($autorNombres === '' || $autorApPaterno === '' || $autorApMaterno === '') {
        if ($esAjax) {
            echo json_encode(['ok' => false, 'error' => 'Nombres, apellido paterno y apellido materno son obligatorios.']);
            exit;
        }
        $error = 'Nombres, apellido paterno y apellido materno son obligatorios.';
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO autores (nombres, ap_paterno, ap_materno, nickname, es_nickname) VALUES (:n, :ap, :am, :nick, :es)');
            $stmt->execute([
                'n'    => $autorNombres,
                'ap'   => $autorApPaterno,
                'am'   => $autorApMaterno,
                'nick' => $autorNickname !== '' ? $autorNickname : null,
                'es'   => $esNickname,
            ]);
            $nuevoAutorId = (int) db()->lastInsertId();
            if ($esAjax) {
                echo json_encode([
                    'ok' => true,
                    'id' => $nuevoAutorId,
                    'nombre' => autor_nombre_completo([
                        'nombres' => $autorNombres,
                        'ap_paterno' => $autorApPaterno,
                        'ap_materno' => $autorApMaterno,
                        'nickname' => $autorNickname,
                        'es_nickname' => $esNickname,
                    ]),
                ]);
                exit;
            }
            flash_set('success', 'Autor "' . autor_nombre_completo([
                'nombres' => $autorNombres,
                'ap_paterno' => $autorApPaterno,
                'ap_materno' => $autorApMaterno,
                'nickname' => $autorNickname,
                'es_nickname' => $esNickname,
            ]) . '" agregado correctamente.');
            redirect('publicar_nuevo.php?tipo=' . $tipo);
        } catch (PDOException $e) {
            if ($esAjax) {
                echo json_encode(['ok' => false, 'error' => 'No se pudo guardar el autor. Verifica los datos.']);
                exit;
            }
            $error = 'No se pudo guardar el autor. Verifica los datos.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['agregar_autor'])) {
    $titulo = trim($_POST['titulo'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');

    if ($titulo === '') {
        $error = 'El título es obligatorio.';
    } elseif ($fecha === '' || !strtotime($fecha)) {
        $error = 'Ingresa una fecha de publicación válida.';
    } else {
        try {
            $uid = (int) $_SESSION['user_id'];
            $fechaDb = date('Y-m-d H:i:s', strtotime($fecha));
            $pdfSubido = (($_FILES['pdf'] ?? [])['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            switch ($tipo) {
                case 'noticia':
                case 'reportaje':
                case 'boletin':
                case 'podcast':
                case 'video':
                    $activo = isset($_POST['activo']) && (string) $_POST['activo'] === '1' ? 1 : 0;

                    if ($tipo === 'noticia') {
                        $link = trim($_POST['link'] ?? '');
                        if ($link === '') {
                            throw new RuntimeException('El link es obligatorio.');
                        }
                        $fotoNoticia = ((($_FILES['foto'] ?? [])['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)
                            ? subir_foto($_FILES['foto'])
                            : ($editando && isset($editado['foto']) && $editado['foto'] !== null ? $editado['foto'] : '');

                        if ($editando) {
                            $stmt = db()->prepare('UPDATE noticias SET titulo = :t, foto = :foto, link_externo = :link, fecha_publicacion = :fecha, activo = :activo, updated_at = NOW() WHERE id = :id');
                            $stmt->execute(['t' => $titulo, 'foto' => $fotoNoticia !== '' ? $fotoNoticia : null, 'link' => $link, 'fecha' => $fechaDb, 'activo' => $activo, 'id' => $editId]);
                            if (($_FILES['foto'] ?? [])['error'] === UPLOAD_ERR_OK) {
                                guardar_foto('noticia', $editId, $fotoNoticia, $titulo);
                            }
                        } else {
                            $stmt = db()->prepare('INSERT INTO noticias (titulo, foto, link_externo, fecha_publicacion, usuario_id, activo) VALUES (:t, :foto, :link, :fecha, :uid, 1)');
                            $stmt->execute(['t' => $titulo, 'foto' => $fotoNoticia !== '' ? $fotoNoticia : null, 'link' => $link, 'fecha' => $fechaDb, 'uid' => $uid]);
                            if ($fotoNoticia !== '') {
                                guardar_foto('noticia', (int) db()->lastInsertId(), $fotoNoticia, $titulo);
                            }
                        }
                        break;
                    }

                    if ($tipo === 'reportaje') {
                        $resumen = trim($_POST['resumen_corto'] ?? '');
                        $desarrollo = sane_html(trim($_POST['desarrollo'] ?? ''));
                        if ($desarrollo === '') {
                            $desarrollo = $titulo;
                        }
                        $esDestacado = isset($_POST['es_destacado']) && (string) $_POST['es_destacado'] === '1' ? 1 : 0;
                        $autorId = isset($_POST['autor_id']) && $_POST['autor_id'] !== '' ? (int) $_POST['autor_id'] : null;

                        if ($esDestacado) {
                            $cond = $editando ? 'WHERE id != ' . (int) $editId : '';
                            db()->exec("UPDATE reportajes SET es_destacado = 0 $cond");
                        }

                        $hayFoto = (($_FILES['foto'] ?? [])['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
                        $ruta = $hayFoto
                            ? subir_foto($_FILES['foto'])
                            : ($editando ? $editado['foto_principal'] : '');
                        if (!$hayFoto && !$editando) {
                            throw new RuntimeException('La foto principal es obligatoria.');
                        }
                        $pdf = $pdfSubido
                            ? subir_pdf($_FILES['pdf'])
                            : ($editando ? $editado['pdf_adjunto'] : '');

                        if ($editando) {
                            $stmt = db()->prepare('UPDATE reportajes SET titulo = :t, resumen_corto = :res, desarrollo = :dev, foto_principal = :f, pdf_adjunto = :pdf, fecha_publicacion = :fecha, es_destacado = :dest, autor_id = :autor, activo = :activo, updated_at = NOW() WHERE id = :id');
                            $stmt->execute(['t' => $titulo, 'res' => $resumen !== '' ? $resumen : null, 'dev' => $desarrollo, 'f' => $ruta, 'pdf' => $pdf !== '' ? $pdf : null, 'fecha' => $fechaDb, 'dest' => $esDestacado, 'autor' => $autorId, 'activo' => $activo, 'id' => $editId]);
                            if ($hayFoto) {
                                guardar_foto('reportaje', $editId, $ruta, $titulo);
                            }
                        } else {
                            $stmt = db()->prepare('INSERT INTO reportajes (titulo, resumen_corto, desarrollo, foto_principal, pdf_adjunto, fecha_publicacion, es_destacado, autor_id, usuario_id, activo) VALUES (:t, :res, :dev, :f, :pdf, :fecha, :dest, :autor, :uid, 1)');
                            $stmt->execute(['t' => $titulo, 'res' => $resumen !== '' ? $resumen : null, 'dev' => $desarrollo, 'f' => $ruta, 'pdf' => $pdf !== '' ? $pdf : null, 'fecha' => $fechaDb, 'dest' => $esDestacado, 'autor' => $autorId, 'uid' => $uid]);
                            guardar_foto('reportaje', (int) db()->lastInsertId(), $ruta, $titulo);
                        }
                        break;
                    }

                    if ($tipo === 'boletin') {
                        $hayFoto = (($_FILES['foto'] ?? [])['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
                        $ruta = $hayFoto
                            ? subir_foto($_FILES['foto'])
                            : ($editando ? $editado['foto_portada'] : '');
                        if (!$hayFoto && !$editando) {
                            throw new RuntimeException('La foto portada es obligatoria.');
                        }
                        $pdf = $pdfSubido
                            ? subir_pdf($_FILES['pdf'])
                            : ($editando ? $editado['archivo_pdf'] : '');
                        $resumen = trim($_POST['resumen'] ?? '');

                        if ($editando) {
                            $stmt = db()->prepare('UPDATE boletines SET numero_boletin = :t, resumen = :res, foto_portada = :f, archivo_pdf = :pdf, fecha_publicacion = :fecha, activo = :activo, updated_at = NOW() WHERE id = :id');
                            $stmt->execute(['t' => $titulo, 'res' => $resumen !== '' ? $resumen : null, 'f' => $ruta, 'pdf' => $pdf, 'fecha' => $fechaDb, 'activo' => $activo, 'id' => $editId]);
                            if ($hayFoto) {
                                guardar_foto('boletin', $editId, $ruta, $titulo);
                            }
                        } else {
                            $stmt = db()->prepare('INSERT INTO boletines (numero_boletin, resumen, foto_portada, archivo_pdf, fecha_publicacion, usuario_id, activo) VALUES (:t, :res, :f, :pdf, :fecha, :uid, 1)');
                            $stmt->execute(['t' => $titulo, 'res' => $resumen !== '' ? $resumen : null, 'f' => $ruta, 'pdf' => $pdf, 'fecha' => $fechaDb, 'uid' => $uid]);
                            guardar_foto('boletin', (int) db()->lastInsertId(), $ruta, $titulo);
                        }
                        break;
                    }

                    $url = trim($_POST['url'] ?? '');
                    if ($url === '') {
                        throw new RuntimeException('La URL es obligatoria.');
                    }
                    if ($editando) {
                        if ($tipo === 'podcast') {
                            $stmt = db()->prepare('UPDATE podcasts SET titulo = :t, url_embed = :url, fecha_publicacion = :fecha, activo = :activo, updated_at = NOW() WHERE id = :id');
                        } else {
                            $stmt = db()->prepare('UPDATE videos SET titulo = :t, url_embed = :url, fecha_publicacion = :fecha, activo = :activo, updated_at = NOW() WHERE id = :id');
                        }
                        $stmt->execute(['t' => $titulo, 'url' => $url, 'fecha' => $fechaDb, 'activo' => $activo, 'id' => $editId]);
                    } else {
                        $stmt = db()->prepare("INSERT INTO $tabla (titulo, url_embed, fecha_publicacion, usuario_id, activo) VALUES (:t, :url, :fecha, :uid, 1)");
                        $stmt->execute(['t' => $titulo, 'url' => $url, 'fecha' => $fechaDb, 'uid' => $uid]);
                    }
                    break;

                default:
                    throw new RuntimeException('Tipo inválido.');
            }

            flash_success_redirect('publicar.php?tipo=' . $tipo);
        } catch (RuntimeException | PDOException $e) {
            $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo guardar el registro.';
        }
    }
}

$autores = [];
if ($tipo === 'reportaje') {
    $autores = db()->query('SELECT id, nombres, ap_paterno, ap_materno, nickname, es_nickname FROM autores ORDER BY ap_paterno ASC, ap_materno ASC, nombres ASC')->fetchAll();
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
    <link rel="shortcut icon" href="assets/images/favicon.png" type="image/png" />
    <title><?= $editando ? 'Editar ' : 'Nuevo ' ?><?= $info['label'] ?> | Panel de Administración</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="assets/css/richtext.css" />
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
    <div id="preloader"><div class="spinner"></div></div>

    <aside class="sidebar-nav-wrapper">
      <div class="navbar-logo">
        <a href="index.php"><img src="assets/images/logo/dyd.png" alt="logo" /></a>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <?php if (es_admin()): ?>
          <li class="nav-item nav-item-has-children">
            <a href="#0" class="sidebar-group-title" aria-expanded="true">
              <span class="text">Dashboard</span>
            </a>
            <ul class="collapse show dropdown-nav" style="display:block;">
              <li><a href="index.php"> Resumen </a></li>
            </ul>
          </li>
          <?php endif; ?>
          <li class="nav-item nav-item-has-children">
            <a href="#0" class="sidebar-group-title" aria-expanded="true">
              <span class="text">Publicar</span>
            </a>
            <ul class="collapse show dropdown-nav" style="display:block;">
              <li><a href="publicar.php?tipo=noticia" class="<?= $tipo === 'noticia' ? 'active' : '' ?>"> Noticias </a></li>
              <li><a href="publicar.php?tipo=reportaje" class="<?= $tipo === 'reportaje' ? 'active' : '' ?>"> Reportajes </a></li>
              <li><a href="publicar.php?tipo=boletin" class="<?= $tipo === 'boletin' ? 'active' : '' ?>"> Boletines </a></li>
              <li><a href="publicar.php?tipo=podcast" class="<?= $tipo === 'podcast' ? 'active' : '' ?>"> Podcasts </a></li>
              <li><a href="publicar.php?tipo=video" class="<?= $tipo === 'video' ? 'active' : '' ?>"> Videos </a></li>
            </ul>
          </li>
        </ul>
      </nav>
    </aside>

    <main class="main-wrapper">
      <section class="section">
        <div class="container-fluid">
          <div class="title-wrapper pt-30">
            <div class="row align-items-center">
              <div class="col-12">
                <div class="title d-flex align-items-center gap-2">
                  <img src="assets/images/logo/dyd.png" alt="Diálogo y Desarrollo" style="height:36px;" />
                  <h2 class="mb-0"><?= $editando ? 'Editar ' : 'Nuevo ' ?><?= $info['label'] ?></h2>
                </div>
              </div>
            </div>
          </div>

          <?php if ($flashSuccess !== ''): ?>
            <div class="alert alert-success py-2" role="alert"><?= htmlspecialchars($flashSuccess) ?></div>
          <?php endif; ?>
          <?php if ($error !== ''): ?>
            <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>

          <div class="card-style mb-30">
            <form action="publicar_nuevo.php?tipo=<?= $tipo ?><?= $editando ? '&id=' . $editId : '' ?>" method="post" enctype="multipart/form-data">
              <div class="row">
                <div class="col-md-8">
                  <div class="mb-3">
                    <label for="titulo" class="form-label"><?= $tipo === 'boletin' ? 'Número de boletín' : 'Título' ?></label>
                    <input type="text" class="form-control" id="titulo" name="titulo" maxlength="255" required autofocus value="<?= h($tipo === 'boletin' ? ($editado['numero_boletin'] ?? '') : ($editado['titulo'] ?? '')) ?>" />
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="mb-3">
                    <label for="fecha" class="form-label">Fecha de publicación</label>
                    <div class="input-group">
                      <input type="datetime-local" class="form-control" id="fecha" name="fecha" required
                        max="<?= date('Y-m-d\TH:i') ?>"
                        value="<?= h(isset($editado['fecha_publicacion']) ? date('Y-m-d\TH:i', strtotime($editado['fecha_publicacion'])) : date('Y-m-d\TH:i')) ?>" />
                      <button type="button" class="btn btn-outline-secondary" id="btnHoy" title="Usar fecha y hora de ahora">Hoy</button>
                    </div>
                  </div>
                </div>

                <?php if ($tipo === 'noticia'): ?>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="link" class="form-label">Link de la noticia</label>
                      <input type="url" class="form-control" id="link" name="link" maxlength="500" placeholder="https://..." required value="<?= h($editado['link_externo'] ?? '') ?>" />
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="foto" class="form-label">Foto</label>
                      <?php if ($editando && !empty($editado['foto'])): ?>
                        <img src="../<?= h($editado['foto']) ?>" alt="" class="d-block mb-2" style="max-width:120px;max-height:80px;object-fit:cover;border-radius:4px;" />
                      <?php endif; ?>
                      <input type="file" class="form-control" id="foto" name="foto" accept="image/*" />
                      <div class="form-text">Máximo 300 KB. Comprime la imagen si es necesario.</div>
                    </div>
                  </div>

                <?php elseif ($tipo === 'reportaje'): ?>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="autor_id" class="form-label">Autor</label>
                      <div class="input-group">
                        <select class="form-select" id="autor_id" name="autor_id">
                          <option value="">-- Seleccionar --</option>
                          <?php foreach ($autores as $a): ?>
                            <option value="<?= (int) $a['id'] ?>" <?= $editando && (int) ($editado['autor_id'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= h(autor_nombre_completo($a)) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAutor" title="Agregar un nuevo autor">
                          <i class="lni lni-plus me-1"></i> Agregar autor
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="resumen_corto" class="form-label">Resumen corto</label>
                      <textarea class="form-control" id="resumen_corto" name="resumen_corto" rows="3"><?= h($editado['resumen_corto'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="mb-3">
                      <label for="desarrollo" class="form-label">Desarrollo (reportaje)</label>
                      <textarea class="form-control" id="desarrollo" name="desarrollo" rows="6" data-richtext><?= h($editado['desarrollo'] ?? '') ?></textarea>
                      <div class="form-text">Usa la barra de herramientas para dar formato al texto (negrita, color, tamaño, etc.).</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="foto" class="form-label">Foto principal</label>
                      <?php if ($editando && !empty($editado['foto_principal'])): ?>
                        <img src="../<?= h($editado['foto_principal']) ?>" alt="" class="d-block mb-2" style="max-width:120px;max-height:80px;object-fit:cover;border-radius:4px;" />
                      <?php endif; ?>
                      <input type="file" class="form-control" id="foto" name="foto" accept="image/*" <?= $editando ? '' : 'required' ?> />
                      <div class="form-text">Máximo 300 KB. Comprime la imagen si es necesario.</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="pdf" class="form-label">PDF adjunto</label>
                      <?php if ($editando && !empty($editado['pdf_adjunto'])): ?>
                        <a href="../<?= h($editado['pdf_adjunto']) ?>" target="_blank" class="d-block mb-2 text-sm">Ver PDF actual</a>
                      <?php endif; ?>
                      <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf" />
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="es_destacado" name="es_destacado" value="1" <?= $editando && (int) ($editado['es_destacado'] ?? 0) === 1 ? 'checked' : '' ?> />
                      <label class="form-check-label" for="es_destacado">Reportaje destacado</label>
                    </div>
                  </div>

                <?php elseif ($tipo === 'boletin'): ?>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="foto" class="form-label">Foto portada</label>
                      <?php if ($editando && !empty($editado['foto_portada'])): ?>
                        <img src="../<?= h($editado['foto_portada']) ?>" alt="" class="d-block mb-2" style="max-width:120px;max-height:80px;object-fit:cover;border-radius:4px;" />
                      <?php endif; ?>
                      <input type="file" class="form-control" id="foto" name="foto" accept="image/*" <?= $editando ? '' : 'required' ?> />
                      <div class="form-text">Máximo 300 KB. Comprime la imagen si es necesario.</div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3">
                      <label for="pdf" class="form-label">PDF del boletín</label>
                      <?php if ($editando && !empty($editado['archivo_pdf'])): ?>
                        <a href="../<?= h($editado['archivo_pdf']) ?>" target="_blank" class="d-block mb-2 text-sm">Ver PDF actual</a>
                      <?php endif; ?>
                      <input type="file" class="form-control" id="pdf" name="pdf" accept="application/pdf" />
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="mb-3">
                      <label for="resumen" class="form-label">Resumen</label>
                      <textarea class="form-control" id="resumen" name="resumen" rows="4"><?= h($editado['resumen'] ?? '') ?></textarea>
                    </div>
                  </div>

                <?php elseif ($tipo === 'podcast' || $tipo === 'video'): ?>
                  <div class="col-12">
                    <div class="mb-3">
                      <label for="url" class="form-label">URL de <?= $tipo === 'video' ? 'video' : 'incrustación / audio' ?></label>
                      <input type="url" class="form-control" id="url" name="url" maxlength="500" placeholder="https://..." required value="<?= h($editado['url_embed'] ?? '') ?>" />
                    </div>
                  </div>
                <?php endif; ?>

                <div class="col-12 mb-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= (int) ($editado['activo'] ?? 1) === 1 ? 'checked' : '' ?> />
                    <label class="form-check-label" for="activo">Activo (visible en la página web)</label>
                  </div>
                </div>

                <div class="col-12">
                  <button type="submit" class="main-btn primary-btn btn-hover"><i class="lni lni-save me-2"></i> Guardar</button>
                  <a href="publicar.php?tipo=<?= $tipo ?>" class="btn btn-light ms-2">Cancelar</a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </section>
    </main>

    <?php if ($tipo === 'reportaje'): ?>
      <!-- Modal: Agregar autor -->
      <div class="modal fade" id="modalAutor" tabindex="-1" aria-labelledby="modalAutorLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form id="formAutor" action="publicar_nuevo.php?tipo=reportaje" method="post">
              <input type="hidden" name="agregar_autor" value="1" />
              <input type="hidden" name="ajax" value="1" />
              <div class="modal-header">
                <h5 class="modal-title" id="modalAutorLabel"><i class="lni lni-user me-2"></i>Agregar autor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label for="autor_nombres" class="form-label">Nombres</label>
                  <input type="text" class="form-control" id="autor_nombres" name="autor_nombres" maxlength="100" required autofocus />
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3 mb-md-0">
                    <label for="autor_ap_paterno" class="form-label">Apellido paterno</label>
                    <input type="text" class="form-control" id="autor_ap_paterno" name="autor_ap_paterno" maxlength="100" required />
                  </div>
                  <div class="col-md-6">
                    <label for="autor_ap_materno" class="form-label">Apellido materno</label>
                    <input type="text" class="form-control" id="autor_ap_materno" name="autor_ap_materno" maxlength="100" required />
                  </div>
                </div>
                <div class="mb-3 mt-3">
                  <label for="autor_nickname" class="form-label">Nickname <small class="text-muted">(opcional)</small></label>
                  <input type="text" class="form-control" id="autor_nickname" name="autor_nickname" maxlength="100" placeholder="Nombre de pluma" />
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" id="autor_es_nickname" name="autor_es_nickname" value="1" />
                  <label class="form-check-label" for="autor_es_nickname">Usar el nickname como nombre público del autor</label>
                </div>
                <div id="autorMsg" class="mt-2 small"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="main-btn primary-btn btn-hover"><i class="lni lni-save me-1"></i> Guardar autor</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/richtext.js"></script>
    <script>
    (function () {
      var form = document.getElementById('formAutor');
      if (!form) return;

      var sel = document.getElementById('autor_id');
      var btn = form.querySelector('button[type="submit"]');
      var msg = document.getElementById('autorMsg');

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!form.checkValidity()) {
          form.reportValidity();
          return;
        }
        var fd = new FormData(form);
        btn.disabled = true;
        msg.textContent = '';
        btn.innerHTML = 'Guardando...';
        fetch(form.action, {
          method: 'POST',
          body: new URLSearchParams(fd)
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (d.ok) {
            var opt = document.createElement('option');
            opt.value = d.id;
            opt.textContent = d.nombre;
            sel.appendChild(opt);
            sel.value = String(d.id);
            var modal = bootstrap.Modal.getInstance(document.getElementById('modalAutor'));
            if (modal) modal.hide();
          } else {
            msg.textContent = d.error || 'No se pudo guardar el autor.';
            msg.style.color = '#dc3545';
          }
        })
        .catch(function () {
          msg.textContent = 'Error de conexión. Inténtalo de nuevo.';
          msg.style.color = '#dc3545';
        })
        .finally(function () {
          btn.disabled = false;
          btn.innerHTML = '<i class="lni lni-save me-1"></i> Guardar autor';
        });
      });
    })();

    (function () {
      var fecha = document.getElementById('fecha');
      var btnHoy = document.getElementById('btnHoy');
      if (!fecha || !btnHoy) return;

      function nowLocal() {
        var d = new Date();
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
      }

      btnHoy.addEventListener('click', function () {
        var now = nowLocal();
        fecha.value = now;
        fecha.max = now;
        fecha.blur();
      });

      fecha.addEventListener('change', function () {
        var max = nowLocal();
        fecha.max = max;
        if (fecha.value > max) {
          fecha.value = max;
        }
      });
    })();
    </script>
  </body>
</html>