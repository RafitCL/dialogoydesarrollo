<?php
$pagina = 'contacto';
$titulo = 'Contacto';
$descripcion = 'Contacta con Diálogo y Desarrollo Perú — correo, teléfono y dirección para colaborar o recibir información.';
require __DIR__ . '/_header.php';

$correo      = 'contacto@dialogoydesarrollo.com.pe';
$telefono    = '+51 987 654 321';
$celular      = '+51 912 345 678';
$direccion   = 'Av. Arequipa 1234, Cercado de Lima, Perú';
?>
<section class="breadcrumb-area py-sm-5 py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="breadcrumb-contents">
          <h2 class="title-big">Contacto</h2>
          <div class="breadcrumb">
            <ul>
              <li><a href="../index.php">Inicio</a></li>
              <li class="active">Contacto</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<section class="w3l-features py-5">
  <div class="container py-lg-4 py-md-3">
    <div class="row">
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="blog-info border p-4 text-center" style="border-radius: 8px;">
          <span class="fa fa-envelope fa-2x mb-3" style="color: #92400e;"></span>
          <h5 class="text-uppercase mb-2">Correo</h5>
          <p class="mb-0"><a href="mailto:<?= e($correo) ?>"><?= e($correo) ?></a></p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="blog-info border p-4 text-center" style="border-radius: 8px;">
          <span class="fa fa-phone fa-2x mb-3" style="color: #92400e;"></span>
          <h5 class="text-uppercase mb-2">Teléfono</h5>
          <p class="mb-0"><?= e($telefono) ?></p>
          <p class="mb-0"><?= e($celular) ?></p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 mb-4">
        <div class="blog-info border p-4 text-center" style="border-radius: 8px;">
          <span class="fa fa-map-marker fa-2x mb-3" style="color: #92400e;"></span>
          <h5 class="text-uppercase mb-2">Dirección</h5>
          <p class="mb-0"><?= e($direccion) ?></p>
        </div>
      </div>
    </div>
    <div class="welcome-left text-center mt-sm-5 mt-3 py-md-4">
      <h3 class="title-big">Síguenos en nuestras Redes Sociales</h3>
      <div class="main-social-footer-29 mt-3">
        <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square fa-2x"></span></a>
        <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="../assets/images/tiktokg.png" alt="TikTok" /></a>
        <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram fa-2x"></span></a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/_footer.php'; ?>