#!/bin/bash
# User-Data para instancia EC2 (Amazon Linux 2023) - Capa gratuita
# Instala Apache + PHP 8 + MySQL client y despliega el código vía git clone.

set -euxo pipefail

# Actualizar e instalar paquetes
dnf -y update
dnf -y install httpd php php-mysqlnd php-gd php-mbstring php-xml php-zip git mysql

# Habilitar Apache
systemctl enable --now httpd

# Directorio web
WEBROOT=/var/www/html
rm -rf "$WEBROOT"/*
# Clona tu repositorio (ajusta la URL)
git clone https://github.com/RafitCL/dialogoydesarrollo.git "$WEBROOT"

# Permisos
chown -R apache:apache "$WEBROOT"
chmod -R 755 "$WEBROOT"
mkdir -p "$WEBROOT/uploads"
chown -R apache:apache "$WEBROOT/uploads"
chmod -R 775 "$WEBROOT/uploads"

# Configurar variables de entorno para Apache (mod_env)
cat >/etc/httpd/conf.d/ddp-env.conf <<'EOF'
SetEnv DB_HOST __DB_HOST__
SetEnv DB_PORT 3306
SetEnv DB_NAME __DB_NAME__
SetEnv DB_USER __DB_USER__
SetEnv DB_PASS __DB_PASS__
SetEnv SITE_URL __SITE_URL__
EOF

# Reiniciar Apache
systemctl restart httpd

# Habilitar mod_rewrite (AllowOverride)
sed -i 's/AllowOverride None/AllowOverride All/g' /etc/httpd/conf/httpd.conf
systemctl restart httpd

echo "Setup completado. Edita /etc/httpd/conf.d/ddp-env.conf con las credenciales reales."