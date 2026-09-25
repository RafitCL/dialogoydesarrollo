<?php
$pagina = 'alianzas';
$titulo = 'Alianzas';
$descripcion = 'Alianzas institucionales de Diálogo y Desarrollo Perú con organizaciones comprometidas con el diálogo en el país.';
require __DIR__ . '/_header.php';

$alianzas = [
    [
        'nombre'  => 'Ministerio del Ambiente del Perú',
        'siglas'  => 'MINAM',
        'descripcion' => 'Alianza de colaboración para difundir información veraz sobre gestión ambiental, diálogo territorial y desarrollo sostenible en las regiones del Perú.',
        'desde'   => '2024',
    ],
];
?>
<section class="breadcrumb-area py-sm-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="breadcrumb-contents">
          <h2 class="title-big">Alianzas</h2>
          <div class="breadcrumb">
            <ul>
              <li><a href="../index.php">Inicio</a></li>
              <li class="active">Alianzas</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="w3l-banner py-5">
  <div class="midd-w3 py-lg-4 py-md-3">
    <div class="container">
      <p class="mb-5">Estas son las instituciones y organizaciones con las que Diálogo y Desarrollo construye alianzas para amplificar las acciones de diálogo en el país.</p>
      <div class="row">
        <?php foreach ($alianzas as $a): ?>
          <div class="col-lg-6">
            <div class="blog-info border p-4 mb-4" style="border-radius: 8px;">
              <img src="../assets/images/bannerimg.jpg" alt="<?= e($a['nombre']) ?>" class="img-fluid rounded mb-3" />
              <h5 class="text-uppercase" style="color: #92400e; letter-spacing: 2px; font-size: 0.85rem;"><?= e($a['siglas']) ?></h5>
              <h4><a href="#" class="d-block"><?= e($a['nombre']) ?></a></h4>
              <p class="text-muted mt-2 mb-0"><?= e($a['descripcion']) ?></p>
              <p class="mt-3 mb-0"><strong>Desde:</strong> <?= e($a['desde']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>