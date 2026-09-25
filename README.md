# Diálogo y Desarrollo Perú

> *"Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva."*

Portal web y **CMS propio** (PHP + MySQL, sin framework) del medio independiente **Diálogo y Desarrollo Perú**, también conocido como **DDP Noticias**.

Publica **reportajes** de largo formato con infografías en PDF, **noticias** breves, el **Boletín NTEP** (Nodo de Transparencia y Participación), **podcasts** y **videos**, e incluye un **panel de administración** con autenticación por sesión, roles, CRUD completo y analítica.

---

## Tabla de contenidos

- [Características](#características)
- [Stack tecnológico](#stack-tecnológico)
- [Requisitos](#requisitos)
- [Instalación local](#instalación-local)
- [Configuración](#configuración)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Base de datos](#base-de-datos)
- [Panel de administración](#panel-de-administración)
- [Despliegue automático](#despliegue-automático)
- [SEO](#seo)
- [Seguridad](#seguridad)
- [Problemas conocidos](#problemas-conocidos)
- [Créditos](#créditos)
- [Licencia](#licencia)
- [Autor](#autor)

---

## Características

### Sitio público

| Página | Archivo | Descripción |
| --- | --- | --- |
| Portada | `index.php` | Reportaje destacado (hero), rejilla de reportajes, noticias recientes, spotlight del Boletín NTEP, carruseles de podcast y video, banners institucionales y bloque de redes. |
| Reportajes | `pages/reportajes.php` | Listado con filtro por archivo mensual (`?mes=YYYY-MM`). |
| Reportaje | `pages/reportaje.php` | Detalle con JSON-LD `NewsArticle`, entradilla, cuerpo HTML, galería, descarga de PDF/infografía y sidebar con últimas publicaciones. |
| Boletines | `pages/boletines.php` | Archivo del Boletín NTEP con enlace directo al PDF de cada número. |
| Podcast | `pages/podcast.php` | Reproductores incrustados de Spotify y YouTube en `iframe` responsive. |
| Videos | `pages/video.php` | Archivo de videos incrustados. |
| Nosotros | `pages/nosotros.php` | Historia institucional, visión y misión. |
| Alianzas | `pages/alianzas.php` | Organizaciones aliadas. |
| Contacto | `pages/contacto.php` | Datos de contacto y redes sociales. |
| Sitemap | `sitemap.php` | Sitemap XML dinámico servido en `/sitemap.xml`. |
| Proxy de imágenes | `assets/images/imagen.php` | Sirve las imágenes almacenadas como BLOB con el `Content-Type` correcto y caché de 7 días. |

### Funcionalidades del CMS

- **5 tipos de contenido gestionables:** `noticia`, `reportaje`, `boletin`, `podcast`, `video`.
- **CRUD completo** con borrado lógico (publicar / despublicar). No existe el borrado físico.
- **Rol `destacado` único:** solo un reportaje puede ocupar la portada; el sistema desmarca el resto automáticamente.
- **Roles de usuario:** `admin` (dashboard y gestión) y `redactor` (solo publicación).
- **Dashboard con analítica:** KPIs por tipo de contenido, usuarios por rol, autores más activos, gráfico de publicaciones por mes (12 meses) con Chart.js, feed de actividad y contadores de fotos y PDFs.
- **Editor de texto enriquecido** propio (WYSIWYG sobre `contentEditable`) para el cuerpo de los reportajes.
- **Carga de archivos validada:** imágenes (JPG/PNG/WEBP/GIF, máx. 300 KB) y PDF (máx. 10 MB), con nombre aleatorio y tipo MIME detectado por `finfo` — nunca se confía en la extensión enviada por el cliente.
- **Doble almacenamiento de imágenes:** copia en `uploads/` y además BLOB en la tabla `fotos`.
- **Recuperación de contraseña** con clave temporal y cambio obligatorio en el siguiente inicio de sesión.
- **Sanitización de HTML** (`sane_html()`): elimina `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, atributos `on*` y URIs `javascript:`.

---

## Stack tecnológico

**Backend**

- PHP 8.0+ (usa `match`, `str_contains()`, `str_starts_with()`, `declare(strict_types=1)`)
- MySQL / MariaDB vía **PDO** con prepared statements reales (`ATTR_EMULATE_PREPARES => false`)
- Sesiones nativas de PHP
- `mail()` nativo para el restablecimiento de contraseña

**Frontend público**

- Bootstrap 4 (CSS 4.4.1 + JS 4.3.1)
- jQuery 3.3.1
- Owl Carousel 2.3.4 (carruseles)
- Font Awesome 4.7.0
- W3layouts — plantilla Media/News
- Google Fonts (Cabin)

**Panel de administración**

- Bootstrap 5.3
- Lineicons Free 4.0
- Chart.js 4.3.0 (gráfico del dashboard)
- PlainAdmin (plantilla base)
- `richtext.js` / `richtext.css` (editor WYSIWYG propio)

**Infraestructura**

- Hosting compartido en InfinityFree (Apache, PHP 8, MySQL)
- Despliegue por FTPS automatizado con GitHub Actions

> **No se utilizan** Composer, npm, autoloaders, namespaces ni frameworks. Todo el código es PHP procedural con `require` y `require_once`.

---

## Requisitos

- **PHP 8.0 o superior** con las extensiones `pdo_mysql`, `fileinfo`, `mbstring` y `session`
- **MySQL 5.7+** o **MariaDB 10.3+** (se requiere InnoDB por las claves foráneas)
- **Apache 2.4** con `mod_rewrite` (o cualquier servidor capaz de ejecutar PHP)
- Un editor de código (VS Code recomendado, ver `.vscode/settings.json`)
- Para el despliegue: una cuenta de **InfinityFree** con acceso FTPS

---

## Instalación local

**1. Clona el repositorio**

```bash
git clone https://github.com/RafitCL/dialogoydesarrollo.git
cd dialogoydesarrollo
```

**2. Configura el servidor local**

Copia el proyecto dentro del `htdocs` de XAMPP (o la carpeta pública equivalente) y levanta Apache y MySQL.

**3. Crea la base de datos e importa el esquema**

```bash
mysql -u root -p -e "CREATE DATABASE dialogoydesarrollo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p dialogoydesarrollo < dyd.sql
```

> El archivo `dyd.sql` se mantiene fuera del control de versiones por contener datos sensibles. Si no lo tienes a la mano, revisa la sección [Base de datos](#base-de-datos).

**4. Configura las credenciales** en `admin/config.php` — ver [Configuración](#configuración).

**5. Crea el primer usuario administrador**

```sql
INSERT INTO usuarios (nombres, ap_paterno, ap_materno, email, password_hash, rol)
VALUES ('Admin', 'Principal', 'DDP', 'admin@dialogoydesarrollo.com.pe',
        '$2y$10$TU_HASH_AQUI', 'admin');
```

Genera el hash con PHP:

```php
echo password_hash('TuClaveSegura1!', PASSWORD_DEFAULT);
```

**6. Verifica el sitio**

| Recurso | URL |
| --- | --- |
| Sitio público | `http://localhost/dialogoydesarrollo/` |
| Panel de administración | `http://localhost/dialogoydesarrollo/admin/login.php` |
| Sitemap | `http://localhost/dialogoydesarrollo/sitemap.xml` |

---

## Configuración

Toda la configuración del backend se concentra en **`admin/config.php`**, protegido por `.htaccess` para evitar su descarga directa.

```php
<?php
declare(strict_types=1);

session_start();

// Base de datos
const DB_HOST = 'localhost';   // en producción: sqlXXX.infinityfree.com
const DB_PORT = '3306';
const DB_NAME = 'dialogoydesarrollo';
const DB_USER = 'root';
const DB_PASS = 'TU_CLAVE_AQUI';

// URL pública del sitio (sin barra final)
const SITE_URL = 'https://dialogoydesarrollo.com.pe';
```

### Helpers definidos en `admin/config.php`

| Función | Propósito |
| --- | --- |
| `db()` | Singleton de PDO con `ERRMODE_EXCEPTION`, `FETCH_ASSOC` y prepared statements reales. |
| `redirect($url)` | Redirección con `header()` y `exit`. |
| `flash_set()` / `flash_get()` | Mensajes de una sola lectura vía sesión. |
| `is_logged_in()` / `es_admin()` | Control de sesión y de rol. |
| `password_valida($pass)` | Política: mínimo 8 caracteres, 1 mayúscula, 1 dígito y 1 carácter especial. |
| `sane_html($html)` | Saneamiento del HTML del cuerpo de los reportajes. |
| `render_desarrollo($texto)` | Convierte texto plano en `<p>` o deja pasar HTML ya saneado. |

### Helpers de la vista pública (`pages/_header.php`)

`e()`, `fecha_comun()`, `resumen()`, `img_publica()`, `img_foto()`, `mes_anio()`, `media_embed()`, `video_thumb()`, `nav_active()`.

---

## Estructura del proyecto

```text
index.php              Portada pública
sitemap.php            Sitemap XML dinámico
.htaccess              DirectoryIndex, sitemap y bloqueo de archivos sensibles
robots.txt             Permite todo excepto /admin/
assets/
  css/                 W3layouts Media/News + Bootstrap 4 + Font Awesome
  fonts/               Webfonts de Font Awesome
  images/              Logo, banners, imágenes de contenido
    imagen.php         Proxy público de imágenes BLOB
  js/                  Bootstrap 4, jQuery, Owl Carousel
pages/                 Páginas públicas (comparten _header y _footer)
  _header.php          head, SEO, Open Graph, JSON-LD, navbar y helpers
  _footer.php          Footer, botón volver arriba y scripts
  reportajes.php       Listado con filtro mensual
  reportaje.php        Detalle de un reportaje
  boletines.php        Archivo del Boletín NTEP
  podcast.php          Reproductores de podcast
  video.php            Archivo de videos
  nosotros.php         Historia, visión y misión
  alianzas.php         Organizaciones aliadas
  contacto.php         Datos de contacto
admin/                 Panel de administración (plantilla PlainAdmin)
  config.php           Credenciales, PDO y helpers   (sensible)
  auth.php             Guardia de sesión              (sensible)
  _publicar_helper.php Validación de subidas          (sensible)
  login.php            Inicio de sesión
  login_action.php     Procesamiento del login
  forgot.php           Recuperación de contraseña
  cambiar_password.php Cambio obligatorio de contraseña
  logout.php           Cierre de sesión
  register.php         Registro (no enlazado en la interfaz)
  index.php            Dashboard con analítica
  publicar.php         Listado y gestión de contenido
  publicar_nuevo.php   Formulario de alta y edición
  toggle_activo.php    Endpoint JSON publicar/despublicar
  toggle_destacado.php Endpoint JSON destacado
  vista_previa.php     Vista previa (no enlazada)
  migrate.php          Migraciones de esquema (expuesto)
  assets/              Bootstrap 5, Lineicons, Chart.js, richtext
uploads/               Archivos subidos por los editores
.github/workflows/     Despliegue automático por FTPS
.vscode/settings.json  Live Server en el puerto 5501
```

---

## Base de datos

Motor: **MySQL / MariaDB** con **InnoDB** y collation `utf8mb4_unicode_ci`.

### Tablas

| Tabla | Contenido |
| --- | --- |
| `usuarios` | Cuentas del panel: credenciales, rol (`admin` / `redactor`), clave temporal y estado. |
| `autores` | Firmas de los textos: nombres, apellidos, `nickname` y bandera `es_nickname`. |
| `reportajes` | Piezas largas: entradilla, cuerpo HTML, portada, PDF, fecha de publicación y bandera `es_destacado`. |
| `noticias` | Noticias breves con título, resumen, cuerpo, foto y fecha. |
| `boletines` | Números del Boletín NTEP con enlace al PDF. |
| `podcasts` | Episodios con enlace embebible de Spotify o YouTube. |
| `videos` | Videos con enlace embebible y miniatura. |
| `fotos` | Imágenes almacenadas como **BLOB**, con relación polimórfica a reportaje, boletín o noticia y constraint `CHECK` que garantiza una sola entidad. |

### Esquema y migraciones

El dump completo (`dyd.sql`) y los listados de usuarios se **excluyen del control de versiones** por contener datos sensibles. El esquema también se puede construir y actualizar mediante migraciones idempotentes desde el navegador:

```
http://localhost/dialogoydesarrollo/admin/migrate.php
```

> `admin/migrate.php` está expuesto en producción. Verifica o elimínalo del servidor una vez aplicado el esquema.

### Copias de seguridad

```bash
mysqldump -u USUARIO -p --single-transaction if0_43005144_db_DialogoDesarrollo > respaldo.sql
```

---

## Panel de administración

Ruta base: **`/admin/`** (excluida en `robots.txt`).

| Archivo | Función |
| --- | --- |
| `login.php` / `login_action.php` | Autenticación por correo y contraseña. |
| `forgot.php` | Restablecimiento por correo con clave temporal. |
| `cambiar_password.php` | Cambio obligatorio de clave en el primer ingreso tras un restablecimiento. |
| `logout.php` | Cierre de sesión. |
| `index.php` | Dashboard: KPIs, usuarios por rol, autores más activos, gráfico de publicaciones por mes (Chart.js) y feed de actividad. |
| `publicar.php` | Listado de contenido con filtros y acciones publicar / despublicar. |
| `publicar_nuevo.php` | Alta y edición de reportajes, noticias, boletines, podcasts y videos. |
| `toggle_activo.php` / `toggle_destacado.php` | Endpoints JSON para cambiar el estado y el destacado de portada. |
| `auth.php` | Guardia de sesión y de rol; se incluye al inicio de cada página protegida. |

### Control de acceso

- `admin` → acceso completo (dashboard, contenido y autores).
- `redactor` → acceso únicamente a la publicación de contenido.
- El destacado es único: al activar un reportaje como destacado, el sistema desmarca el resto.
- El borrado es lógico; no hay eliminación física de registros.

---

## Despliegue automático

Cada `push` a la rama **`main`** dispara `.github/workflows/deploy.yml`, que sincroniza el repositorio por **FTPS** con el hosting.

```yaml
on:
  push:
    branches:
      - main

server: ${{ secrets.FTP_SERVER }}
username: ${{ secrets.FTP_USERNAME }}
password: ${{ secrets.FTP_PASSWORD }}
protocol: ftps
server-dir: htdocs/
```

Exclusiones del despliegue: `**/.git*`, `**/.github/**`, `dyd.sql`, `usuarios.txt` y `admin/base de datos.txt`.

### Configuración de los secretos

En **Settings → Secrets and variables → Actions** del repositorio:

| Secreto | Valor |
| --- | --- |
| `FTP_SERVER` | Servidor FTPS de InfinityFree. |
| `FTP_USERNAME` | Usuario FTP del hosting. |
| `FTP_PASSWORD` | Contraseña FTP del hosting. |

Para revisar el historial de despliegues: **Actions → Desplegar a InfinityFree**.

> La credencial de la base de datos **no** viaja en el despliegue: se define directamente en `admin/config.php` dentro del servidor.

---

## SEO

- **Sitemap dinámico** en `/sitemap.xml`: `sitemap.php` genera el XML con reportajes, noticias, boletines, podcasts y videos; `.htaccess` lo sirve sin extensión.
- **Canonical y Open Graph** por página: `pages/_header.php` construye las etiquetas a partir de `SITE_URL` y de la ruta actual.
- **JSON-LD `NewsArticle`** en el detalle de cada reportaje y `Organization` en el sitio.
- **`robots.txt`** que permite todo el contenido público y bloquea `/admin/`.
- **Imágenes con caché**: `assets/images/imagen.php` envía `Content-Type` correcto y `Cache-Control` de 7 días.
- **URLs limpias** para las páginas públicas y títulos jerárquicos `H1 → H2 → H3`.

> Si cambia el dominio, actualiza simultáneamente `SITE_URL` en `admin/config.php` y la línea `Sitemap:` de `robots.txt`.

---

## Seguridad

Implementado en el proyecto:

- **Consultas preparadas** en todo el acceso a datos, con `ATTR_EMULATE_PREPARES => false`.
- **Salida escapada** con el helper `e()` en todas las vistas públicas.
- **Hashing de contraseñas** con `password_hash()` / `password_verify()` y política de contraseñas (`password_valida()`).
- **Saneamiento de HTML** (`sane_html()`) que elimina `<script>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, atributos `on*` y URIs `javascript:`.
- **Validación de archivos** por MIME real con `finfo` y renombrado aleatorio; se ignoran las extensiones enviadas por el cliente.
- **Guardia de sesión y rol** (`auth.php`) en todas las páginas del panel.
- **Bloqueo de archivos sensibles** (`config.php`, `auth.php`, `_publicar_helper.php`) mediante `.htaccess`.
- **`/admin/` desindexado** en `robots.txt`.
- **Secretos de despliegue** almacenados como secretos de GitHub Actions, nunca en el repositorio.

Pendiente de endurecer:

- ⚠️ **`admin/config.php` contiene credenciales de base de datos reales versionadas en Git.** Muévelas a variables de entorno, elimina el archivo del historial y **cambia la contraseña de la base de datos** en InfinityFree.
- Eliminar `admin/migrate.php` del servidor una vez aplicado el esquema.
- Activar HTTPS: hoy `SITE_URL` apunta a un subdominio de InfinityFree en `http://`.
- Restringir `register.php`, que no está enlazado en la interfaz pero sigue siendo accesible.

---

## Problemas conocidos

| Tema | Detalle |
| --- | --- |
| Dominio | `SITE_URL` y `robots.txt` apuntan a dominios distintos; hay que unificarlos al definir el dominio final. |
| HTTPS | No está configurado; las cookies de sesión viajan sin `Secure`. |
| `migrate.php` | Accesible públicamente en el servidor. |
| `register.php` | Registrador de usuarios presente en el código y no enlazado desde la interfaz. |
| Sin `LICENSE` | El proyecto todavía no tiene licencia declarada. |
| Sin pruebas | No hay suite de pruebas automatizadas; la validación es manual. |
| Autenticación | Sin límite de intentos ni verificación en dos pasos. |
| Respaldos | El dump de la base de datos se genera manualmente, fuera del flujo de despliegue. |

---

## Créditos

- **Diseño y desarrollo:** equipo de Diálogo y Desarrollo Perú.
- **Plantilla del sitio público:** W3layouts — plantilla Media/News.
- **Plantilla del panel:** PlainAdmin.
- **Librerías:** Bootstrap 4, Bootstrap 5, jQuery, Owl Carousel, Font Awesome, Lineicons y Chart.js.
- **Tipografía:** Cabin (Google Fonts).
- **Hosting:** InfinityFree.
- **Despliegue:** GitHub Actions con SamKirkland/FTP-Deploy-Action.

---

## Licencia

Este proyecto **aún no tiene licencia declarada**. Todos los derechos están reservados por sus autores. Contacta al equipo antes de reutilizar el código, las plantillas o el contenido.

---

## Autor

**Diálogo y Desarrollo Perú** —medio independiente de periodismo constructivo.
Desarrollado y mantenido por **RafitCL**.

- Repositorio: <https://github.com/RafitCL/dialogoydesarrollo>
- Sitio: <https://dialogoydesarrollo.com.pe>
