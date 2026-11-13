# Despliegue de Diálogo y Desarrollo en AWS (Capa Gratuita)

## 1. Arquitectura recomendada (12 meses gratis)

| Servicio | Plan gratuito | Rol |
|---|---|---|
| **EC2** | t2.micro / t3.micro (750 h/mes) | Apache + PHP 8 |
| **RDS MySQL** | db.t3.micro (750 h/mes, 20 GB) | Base de datos |
| **S3** (opcional) | 5 GB | Almacenamiento de uploads/PDFs |
| **CloudFront** (opcional) | 1 TB/mes | CDN para assets |
| **Elastic IP** | Gratis si está asociada a instancia | IP fija |

> Alternativa más simple y barata: **Lightsail** (plan de $3.50/mes no es gratuito, pero tiene prueba de 3 meses para algunos bundles). Para 100 % gratuito usa EC2 + RDS.

## 2. Crear la base de datos (RDS)

1. Consola AWS → RDS → Create database.
2. Engine: **MySQL 8.0** (o MariaDB 10.x).
3. Template: **Free tier**.
4. DB instance identifier: `ddp-db`.
5. Master username: `ddp_admin`, password segura.
6. Public access: **Yes** (solo para setup; luego restringe por Security Group).
7. Security Group: permite 3306 desde la IP de tu EC2 (o 0.0.0.0/0 temporalmente).
8. Anota el **endpoint** (ej. `ddp-db.xxxxx.us-east-1.rds.amazonaws.com`).

Cargar el esquema:
```bash
mysql -h <endpoint> -u ddp_admin -p < deploy/schema.sql
mysql -h <endpoint> -u ddp_admin -p < deploy/seed.sql
```

## 3. Crear la instancia EC2

1. EC2 → Launch instance.
2. AMI: **Amazon Linux 2023** (free tier eligible).
3. Instance type: **t2.micro** (o t3.micro en regiones nuevas).
4. Key pair: crea y descarga una `.pem`.
5. Security Group: abre 22 (SSH) y 80 (HTTP). Opcional 443.
6. User data: pega el contenido de `deploy/ec2-userdata.sh` (edita la URL del repo).
7. Launch.

## 4. Configurar credenciales en EC2

SSH a la instancia:
```bash
ssh -i tu-clave.pem ec2-user@<IP_PUBLICA>
```

Edita `/etc/httpd/conf.d/ddp-env.conf`:
```apache
SetEnv DB_HOST ddp-db.xxxxx.us-east-1.rds.amazonaws.com
SetEnv DB_PORT 3306
SetEnv DB_NAME dialogoydesarrollo
SetEnv DB_USER ddp_admin
SetEnv DB_PASS TU_PASSWORD_SEGURA
SetEnv SITE_URL https://tu-dominio.com
```

Reinicia Apache:
```bash
sudo systemctl restart httpd
```

## 5. Subir el código

Opción A — git clone (recomendado):
```bash
cd /var/www/html
sudo git pull origin main
```

Opción B — scp desde tu máquina:
```bash
scp -i tu-clave.pem -r ./dialogoydesarrollo ec2-user@<IP>:/var/www/html/
```

## 6. Permisos y uploads

```bash
sudo chown -R apache:apache /var/www/html
sudo chmod -R 755 /var/www/html
sudo mkdir -p /var/www/html/uploads
sudo chown -R apache:apache /var/www/html/uploads
sudo chmod -R 775 /var/www/html/uploads
```

## 7. HTTPS gratuito (Let's Encrypt)

```bash
sudo dnf install -y certbot python3-certbot-apache
sudo certbot --apache -d tu-dominio.com
```
Luego actualiza `SITE_URL` a `https://...`.

## 8. Dominio gratuito (opcional)

- Freenom (dominios .tk/.ml) o un subdominio de DuckDNS.
- Apunta el registro A a tu Elastic IP.

## 9. CI/CD con GitHub Actions (opcional)

Crea `.github/workflows/deploy-aws.yml`:
```yaml
name: Deploy to AWS EC2
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.EC2_HOST }}
          username: ec2-user
          key: ${{ secrets.EC2_SSH_KEY }}
          script: |
            cd /var/www/html
            git pull origin main
            sudo systemctl restart httpd
```

## 10. Costos y límites del free tier

- EC2 t2.micro: 750 h/mes durante 12 meses.
- RDS db.t3.micro: 750 h/mes durante 12 meses.
- Almacenamiento RDS: 20 GB.
- Transferencia saliente: 15 GB/mes.
- **Importante:** detén o termina los recursos antes de los 12 meses para evitar cargos. Configura una alerta de facturación en AWS Budgets.

## 11. Checklist de seguridad antes de publicar

- [ ] Cambiar la contraseña del admin en `seed.sql` (hash real).
- [ ] Eliminar `admin/migrate.php` del servidor.
- [ ] Restringir `register.php` o eliminarlo.
- [ ] Configurar HTTPS y cookies `Secure`.
- [ ] Restringir el Security Group de RDS a la IP de la EC2.
- [ ] Activar AWS Budgets con alerta a $1 USD.