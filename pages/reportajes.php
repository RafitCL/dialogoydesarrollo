<?php
$pagina = 'reportajes';
$titulo = 'Reportajes';
$descripcion = 'Reportajes de periodismo independiente sobre diálogo, desarrollo sostenible y participación ciudadana en el Perú.';
require __DIR__ . '/_header.php';

$mesSel = isset($_GET['mes']) ? preg_replace('/[^0-9-]/', '', (string) $_GET['mes']) : '';
$mesesDisponibles = db()->query("SELECT DISTINCT DATE_FORMAT(fecha_publicacion, '%Y-%m') AS mes FROM reportajes WHERE activo = 1 ORDER BY mes DESC")->fetchAll();

$sql = "SELECT r.*, a.nombres AS a_nombres, a.nickname AS a_nick, a.es_nickname AS a_es
        FROM reportajes r
        LEFT JOIN autores a ON a.id = r.autor_id";
$params = [];
$where = [];
$where[] = 'r.activo = 1';
if ($mesSel !== '' && preg_match('/^\d{4}-\d{2}$/', $mesSel)) {
    $where[] = "DATE_FORMAT(r.fecha_publicacion, '%Y-%m') = :mes";
    $params['mes'] = $mesSel;
}
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY r.es_destacado DESC, r.fecha_publicacion DESC";
$st = db()->prepare($sql);
$st->execute($params);
$reportajes = $st->fetchAll();
?>
<section class="breadcrumb-area py-sm-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="breadcrumb-contents">
          <h2 class="title-big">Reportajes</h2>
          <div class="breadcrumb">
            <ul>
              <li><a href="../index.php">Inicio</a></li>
              <li class="active">Reportajes</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<div class="grids-block-5 py-5">
  <section class="py-lg-4 py-md-3">
    <div class="container">
      <?php if (!empty($mesesDisponibles)): ?>
        <div class="row mb-4">
          <div class="col-12 d-flex align-items-center justify-content-flex-start flex-wrap">
            <form method="get" action="reportajes.php" class="d-flex align-items-center flex-wrap">
              <label for="filtro-mes" class="mr-2 mb-0 font-weight-bold" style="color: #92400e;">Archivos por mes:</label>
              <select id="filtro-mes" name="mes" class="form-control mr-2" style="max-width: 240px;">
                <option value="">Todos los meses</option>
                <?php foreach ($mesesDisponibles as $ms): ?>
                  <option value="<?= e($ms['mes']) ?>" <?= $mesSel === $ms['mes'] ? 'selected' : '' ?>><?= e(mes_anio($ms['mes'])) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-style btn-primary">Filtrar</button>
              <?php if ($mesSel !== ''): ?>
                <a href="reportajes.php" class="btn btn-style btn-outline-secondary ml-1">Limpiar</a>
              <?php endif; ?>
            </form>
          </div>
        </div>
      <?php endif; ?>
      <div class="row">
        <?php if (empty($reportajes)): ?>
          <div class="col-12">
            <p class="text-muted">Aún no hay reportajes publicados.</p>
          </div>
        <?php else: ?>
          <?php foreach ($reportajes as $i => $r): ?>
            <div class="col-lg-4 col-md-6 grids5-info <?= $i >= 3 ? 'mt-5' : '' ?>">
              <a href="reportaje.php?id=<?= (int) $r['id'] ?>" class="d-block">
                <img src="<?= e(img_foto('reportaje', (int) $r['id'], '../assets/images/reportaje-28-08-26.jpg')) ?>" alt="<?= e($r['titulo'] ?? '') ?>" class="img-fluid" />
              </a>
              <div class="blog-info">
                <h5><?= e(fecha_comun($r['fecha_publicacion'] ?? null)) ?></h5>
                <h4><a href="reportaje.php?id=<?= (int) $r['id'] ?>" class="d-block"><?= e($r['titulo'] ?? '') ?></a></h4>
                <p class="text-muted mt-2 mb-0"><?= e(resumen($r['resumen_corto'] ?? $r['desarrollo'] ?? '', 110)) ?></p>
                <a href="reportaje.php?id=<?= (int) $r['id'] ?>" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span></a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>