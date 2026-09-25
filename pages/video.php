<?php
$pagina = 'video';
$titulo = 'Video';
$descripcion = 'Videos de Diálogo y Desarrollo Perú sobre construcción de diálogo y desarrollo en el Perú.';
require __DIR__ . '/_header.php';

$videos = db()->query('SELECT * FROM videos WHERE activo = 1 ORDER BY fecha_publicacion DESC')->fetchAll();
$videos = array_filter($videos, fn($v) => media_embed((string) ($v['url_embed'] ?? ''))[0] !== '');
?>
<style>
  .ddp-media {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    background: #0b0f19;
  }
  .ddp-media .embed-responsive {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    padding-bottom: 0;
  }
  .ddp-media .embed-responsive-item {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
  }
  .ddp-media > iframe {
    position: absolute;
    top: 50%;
    left: 0;
    width: 100%;
    height: 152px;
    transform: translateY(-50%);
    border: 0;
  }
</style>
<section class="breadcrumb-area py-sm-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="breadcrumb-contents">
          <h2 class="title-big">Video</h2>
          <div class="breadcrumb">
            <ul>
              <li><a href="../index.php">Inicio</a></li>
              <li class="active">Video</li>
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
        <?php if (empty($videos)): ?>
          <div class="col-12">
            <p class="text-muted">Pronto nuevos videos publicados.</p>
          </div>
        <?php else: ?>
          <?php foreach ($videos as $i => $v): ?>
            <?php [$embedUrl, $tipo] = media_embed((string) ($v['url_embed'] ?? '')); ?>
            <div class="col-lg-6 col-sm-12 mt-5">
              <div class="area-box">
                <div class="ddp-media">
                  <?php if ($tipo === 'video'): ?>
                    <div class="embed-responsive embed-responsive-16by9">
                      <iframe class="embed-responsive-item" src="<?= e($embedUrl) ?>" title="<?= e($v['titulo'] ?? 'Video') ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy"></iframe>
                    </div>
                  <?php else: ?>
                    <iframe src="<?= e($embedUrl) ?>" width="100%" height="152" frameBorder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy" style="border-radius:12px; border:0;" title="<?= e($v['titulo'] ?? 'Video') ?>"></iframe>
                  <?php endif; ?>
                </div>
                <h5 class="mt-3 mb-1" style="color: #92400e; font-size: 0.85rem;"><?= e($tipo === 'video' ? 'Video' : 'Audio') ?> · <?= e(fecha_comun($v['fecha_publicacion'] ?? null)) ?></h5>
                <p class="mb-0"><?= e(resumen($v['titulo'] ?? '', 90)) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>