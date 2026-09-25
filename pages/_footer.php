<!-- footer -->
    <section class="w3l-footer-29-main py-5" id="footer">
      <div class="footer-29 py-md-3">
        <div class="container">
          <div class="row footer-top-29">
            <div class="col-lg-6 col-md-6 footer-list-29 footer-1">
              <h6 class="footer-title-29">Quiénes Somos</h6>
              <p>Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
              <div class="main-social-footer-29">
                <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square"></span></a>
                <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="../assets/images/tiktokp.png" alt="TikTok" /></a>
                <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
              </div>
            </div>
            <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
              <ul>
                <h6 class="footer-title-29">Contenido</h6>
                <li><a href="../index.php#actualidad">Noticias</a></li>
                <li><a href="reportajes.php">Reportajes</a></li>
                <li><a href="podcast.php">Podcast</a></li>
                <li><a href="video.php">Video</a></li>
              </ul>
            </div>
            <div class="col-lg-3 col-md-6 mt-lg-0 mt-5 footer-list-29 footer-3">
              <div class="properties">
                <h6 class="footer-title-29">Contacto</h6>
                <ul>
                  <li><a href="contacto.php">contacto@dialogoydesarrollo.com.pe</a></li>
                </ul>
              </div>
            </div>
          </div>
          <div class="bottom-copies text-center">
            <p class="copy-footer-29">© <?= date('Y'); ?> Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info">WebSolutions</a></p>
          </div>
        </div>
      </div>
      <button onclick="topFunction()" id="movetop" title="Go to top">
        <span class="fa fa-angle-up"></span>
      </button>
      <script>
        window.onscroll = function () {
          scrollFunction()
        };

        function scrollFunction() {
          if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
            document.getElementById("movetop").style.display = "block";
          } else {
            document.getElementById("movetop").style.display = "none";
          }
        }

        function topFunction() {
          document.body.scrollTop = 0;
          document.documentElement.scrollTop = 0;
        }
      </script>
    </section>
    <!-- //footer -->

    <script src="../assets/js/jquery-3.3.1.min.js"></script>
    <script src="../assets/js/theme-change.js"></script>
    <script>
      $(window).on("scroll", function () {
        var scroll = $(window).scrollTop();
        if (scroll >= 80) {
          $("#site-header").addClass("nav-fixed");
        } else {
          $("#site-header").removeClass("nav-fixed");
        }
      });
      $(".navbar-toggler").on("click", function () {
        $("header").toggleClass("active");
      });
      $(document).on("ready", function () {
        if ($(window).width() > 991) {
          $("header").removeClass("active");
        }
        $(window).on("resize", function () {
          if ($(window).width() > 991) {
            $("header").removeClass("active");
          }
        });
      });
    </script>
    <script src="../assets/js/bootstrap.min.js"></script>
  </body>
</html>