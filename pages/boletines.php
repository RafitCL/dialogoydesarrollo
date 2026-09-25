<?php
$pagina = 'boletines';
$titulo = 'Boletines NTEP';
$descripcion = 'Boletines del Nodo de Transparencia y Participación (NTEP) del Perú — análisis y noticias de la semana.';
require __DIR__ . '/_header.php';

$boletines = db()->query('SELECT * FROM boletines WHERE activo = 1 ORDER BY fecha_publicacion DESC')->fetchAll();
?>
<section class="breadcrumb-area py-sm-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="breadcrumb-contents">
          <h2 class="title-big">Boletines NTEP</h2>
          <div class="breadcrumb">
            <ul>
              <li><a href="../index.php">Inicio</a></li>
              <li class="active">Boletines</li>
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
      <div class="row">
        <?php if (empty($boletines)): ?>
          <div class="col-12">
            <p class="text-muted">Pronto publicaremos un nuevo boletín.</p>
          </div>
        <?php else: ?>
          <?php foreach ($boletines as $boletin): ?>
            <?php
              $linkBoletin = img_publica($boletin['archivo_pdf'] ?? '', '');
              if ($linkBoletin === '') {
                  $linkBoletin = img_foto('boletin', (int) $boletin['id'], '../assets/images/boletin-ntep-45.png');
              }
              $hrefBoletin = (string) ($boletin['archivo_pdf'] ?? '');
              $hrefBoletin = ($hrefBoletin !== '' && $hrefBoletin !== '#') ? '../' . $hrefBoletin : $linkBoletin;
            ?>
            <div class="col-lg-4 col-md-6 grids5-info mb-5">
              <a target="_blank" href="<?= e($hrefBoletin) ?>" class="d-block">
                <img src="<?= e(img_foto('boletin', (int) $boletin['id'], '../assets/images/boletin-ntep-45.png')) ?>" alt="<?= e($boletin['numero_boletin'] ?? '') ?>" class="img-fluid" />
              </a>
              <div class="blog-info">
                <h5><?= e(fecha_comun($boletin['fecha_publicacion'] ?? null)) ?></h5>
                <h4><a target="_blank" href="<?= e($hrefBoletin) ?>" class="d-block"><?= e($boletin['numero_boletin'] ?? '') ?></a></h4>
                <p class="text-muted mt-2 mb-0"><?= e(resumen($boletin['resumen'] ?? '', 120)) ?></p>
                <a target="_blank" href="<?= e($hrefBoletin) ?>" class="btn mt-4 p-0">Ver boletín <span class="fa fa-arrow-right"></span></a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>