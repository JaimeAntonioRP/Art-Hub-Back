# ArtHub · Despliegue en un VPS de Hostinger con dominio en GoDaddy

> Igual en **Art-Hub-Back** y **Art-Hub-Front**. Los archivos de configuración están en la carpeta `deploy/` de cada repo.

## Cómo queda

```
                GoDaddy (DNS)
   TU_DOMINIO ─┐               ┌─ api.TU_DOMINIO
               ▼               ▼
        ┌──────── VPS Hostinger (Ubuntu 24.04) ────────┐
        │  Nginx + https (Let's Encrypt)                │
        │   ├─ TU_DOMINIO      → Next.js (PM2, :3000)   │  /var/www/arthub-front
        │   └─ api.TU_DOMINIO  → Laravel (PHP-FPM 8.3)  │  /var/www/arthub-back
        │  PostgreSQL 16 · cron de Laravel              │
        └───────────────────────────────────────────────┘
```

- **`TU_DOMINIO`** (por ejemplo `arthub.pe`): el sitio público. `www.` redirige al dominio sin `www`.
- **`api.TU_DOMINIO`**: la API, los paneles `/admin` y `/artista`, y el login único `/ingresar`.
- En toda esta guía, reemplaza **`TU_DOMINIO`** por el dominio real y **`IP_DEL_VPS`** por la IP del servidor.

## 0. Antes de empezar

Ten a mano:

- [ ] **La IP del VPS** y la contraseña de root: hPanel → **VPS** → tu servidor → **Resumen**.
- [ ] **Acceso a GoDaddy** para editar el DNS del dominio.
- [ ] **Un respaldo de la base actual** (Railway o Render), tomado desde tu computadora:
  ```bash
  pg_dump "URL_DE_LA_BASE_ACTUAL" -Fc --no-owner --no-acl -f arthub.dump
  ```
  `pg_dump` debe ser de la misma versión de PostgreSQL que el servidor actual, o más nueva.
- [ ] **El `APP_KEY` del backend actual**, copiado de las variables de Railway o Render. Así siguen funcionando las sesiones y los enlaces firmados.
- [ ] **Las credenciales del correo:** Resend, Amazon SES o el SMTP de tu correo.
- [ ] **El sistema operativo del VPS:** en hPanel → VPS → **Sistema operativo**, elige **Ubuntu 24.04** limpio, sin panel. **Reinstalar el sistema borra el VPS.**

## 1. DNS en GoDaddy (hazlo primero: tarda en propagarse)

En GoDaddy → **Mis productos** → el dominio → **DNS** → **Administrar DNS**:

| Tipo | Nombre | Valor | TTL |
|---|---|---|---|
| A | `@` | `IP_DEL_VPS` | 600 s |
| A | `api` | `IP_DEL_VPS` | 600 s |
| CNAME | `www` | `@` | 600 s |

- **Edita** el registro `A @` que ya existe; no agregues otro. **Borra** cualquier otro `A @`, por ejemplo el de «Parked» o el de Vercel.
- Si el dominio tiene **Reenvío (Forwarding)** activo, quítalo.
- Para comprobarlo desde tu computadora (debe responder `IP_DEL_VPS`; puede tardar entre minutos y unas horas):
  ```bash
  nslookup api.TU_DOMINIO
  ```

## 2. Preparar el servidor

Entra por SSH como root (`ssh root@IP_DEL_VPS`) y ejecuta:

```bash
apt update && apt upgrade -y
timedatectl set-timezone America/Lima

# Usuario de trabajo (no se usa root para el día a día)
adduser deploy
usermod -aG sudo deploy

# Cortafuegos: SSH ahora; la web se abre después de instalar Nginx
ufw allow OpenSSH && ufw --force enable

# Nginx, PHP 8.3, PostgreSQL, Composer, certbot
apt install -y nginx postgresql git unzip composer certbot python3-certbot-nginx \
  php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath
ufw allow 'Nginx Full'

# Node.js 22 LTS y PM2 (para el sitio Next.js)
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt install -y nodejs
npm install -g pm2

# PHP-FPM corre como «deploy»: así los archivos que crea Laravel y los del despliegue tienen el mismo dueño
sed -i 's/^user = www-data/user = deploy/; s/^group = www-data/group = deploy/' /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart php8.3-fpm

# El script de despliegue puede recargar PHP sin pedir contraseña
echo 'deploy ALL=NOPASSWD: /usr/bin/systemctl reload php8.3-fpm' > /etc/sudoers.d/arthub
chmod 440 /etc/sudoers.d/arthub

mkdir -p /var/www && chown deploy:deploy /var/www
```

## 3. Acceso del servidor a GitHub

Como `deploy` (`su - deploy`):

```bash
ssh-keygen -t ed25519 -C "arthub-vps" -N "" -f ~/.ssh/id_ed25519
cat ~/.ssh/id_ed25519.pub
```

Esa clave pública se agrega en GitHub, en **cada** repo: **Settings → Deploy keys → Add deploy key**, sin permiso de escritura. Si GitHub no acepta la misma clave en los dos repos, agrégala como clave SSH de tu cuenta en **Settings → SSH and GPG keys**.

```bash
cd /var/www
git clone git@github.com:JaimeAntonioRP/Art-Hub-Back.git arthub-back
git clone git@github.com:JaimeAntonioRP/Art-Hub-Front.git arthub-front
```

## 4. Base de datos

Como root:

```bash
sudo -u postgres psql -c "CREATE USER arthub WITH PASSWORD 'CLAVE_SEGURA';"
sudo -u postgres psql -c "CREATE DATABASE arthub OWNER arthub;"
```

Copia el respaldo desde tu computadora y restáuralo en el servidor:

```bash
# En tu computadora
scp arthub.dump deploy@IP_DEL_VPS:/home/deploy/

# En el servidor
pg_restore -h 127.0.0.1 -U arthub -d arthub --no-owner --no-acl /home/deploy/arthub.dump
```

Si `pg_restore` dice **«unsupported version»**, el respaldo viene de un PostgreSQL más nuevo que el 16 de Ubuntu. En ese caso, instala la misma versión desde el repositorio oficial de PostgreSQL (`apt.postgresql.org`) y repite este paso.

## 5. Backend (Laravel) en `api.TU_DOMINIO`

Como `deploy`:

```bash
cd /var/www/arthub-back
cp deploy/env.vps.example .env
nano .env    # completa: APP_KEY, TU_DOMINIO, DB_PASSWORD, MAIL_*, ADMIN_*

composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan storage:link
php artisan optimize && php artisan filament:optimize
crontab deploy/crontab          # tareas programadas (comprobación diaria de integridad)
```

**El seeder, solo la primera vez y solo si hace falta.** Si restauraste la base actual, ya tiene el contenido y el administrador. Si es una base vacía, ejecuta una sola vez:

```bash
php artisan db:seed --force
```

No lo pongas en cada despliegue: vuelve a crear las obras de ejemplo que el admin haya editado o borrado.

Nginx, como root:

```bash
cp /var/www/arthub-back/deploy/nginx-api.conf /etc/nginx/sites-available/arthub-api
sed -i 's/TU_DOMINIO/tudominio.com/g' /etc/nginx/sites-available/arthub-api   # ← tu dominio real
ln -s /etc/nginx/sites-available/arthub-api /etc/nginx/sites-enabled/
```

## 6. Sitio público (Next.js) en `TU_DOMINIO`

Como `deploy`:

```bash
cd /var/www/arthub-front
cp deploy/env.production.local.example .env.production.local
nano .env.production.local      # NEXT_PUBLIC_API_URL=https://api.TU_DOMINIO/api

npm ci
npm run build
pm2 start ecosystem.config.cjs
pm2 save
pm2 startup     # muestra un comando con «sudo …»: cópialo y ejecútalo para que arranque con el servidor
```

Nginx, como root:

```bash
cp /var/www/arthub-front/deploy/nginx-front.conf /etc/nginx/sites-available/arthub-front
sed -i 's/TU_DOMINIO/tudominio.com/g' /etc/nginx/sites-available/arthub-front   # ← tu dominio real
ln -s /etc/nginx/sites-available/arthub-front /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
```

## 7. Comprobar por http (antes del certificado)

- `http://api.TU_DOMINIO/ingresar` muestra el login de ArtHub.
- `http://TU_DOMINIO` muestra la portada con las obras.

Si alguno falla, revisa la sección «Problemas frecuentes».

## 8. https con Let's Encrypt

Cuando el DNS ya apunte al VPS (paso 1), como root:

```bash
certbot --nginx -d TU_DOMINIO -d www.TU_DOMINIO -d api.TU_DOMINIO --redirect -m tu-correo@ejemplo.com --agree-tos
```

Certbot agrega el https a los dos sitios, redirige http → https y renueva el certificado solo. Para comprobar la renovación: `certbot renew --dry-run`.

## 9. Verificación final

- [ ] `https://TU_DOMINIO`: la portada, el catálogo `/obras` con filtros y la ficha de una obra.
- [ ] `https://api.TU_DOMINIO/ingresar`: entra con `ADMIN_EMAIL` y `ADMIN_PASSWORD` y llega a `/admin`.
- [ ] Desde la ficha, envía una solicitud de prueba: llega el correo al admin y al interesado.
- [ ] Emite un certificado de prueba: el QR del PDF debe abrir `https://TU_DOMINIO/verificar/…`.
- [ ] En el celular, abre una obra y prueba **«Ver en mi pared»**: ya hay https, así que la cámara en modo AR debería abrir.
- [ ] Mañana, revisa que la tarea diaria corrió: `php artisan schedule:list` y `storage/logs/`.

## 10. Apagar lo anterior

Cuando todo funcione en el VPS:

- [ ] **Vercel:** quita el dominio del proyecto, o desconecta el despliegue automático para que no compita con el VPS.
- [ ] **Railway o Render:** apaga el backend y la base anteriores, después de comprobar que el respaldo se restauró bien.
- [ ] Revisa en GoDaddy que no quede ningún registro apuntando a Vercel.

## Actualizar después de un cambio en `main`

```bash
ssh deploy@IP_DEL_VPS
cd /var/www/arthub-back  && ./deploy/deploy.sh    # migraciones y cachés, sin seeder
cd /var/www/arthub-front && ./deploy/deploy.sh    # compila y reinicia con PM2
```

Si hay cambios en los dos repos, actualiza primero el backend.

## Respaldos (recomendado)

Diario, como `deploy` (`crontab -e`), con 14 días de historia:

```
30 2 * * * pg_dump -h 127.0.0.1 -U arthub -Fc arthub > /home/deploy/respaldos/db-$(date +\%F).dump && find /home/deploy/respaldos -mtime +14 -delete
45 2 * * * tar -czf /home/deploy/respaldos/archivos-$(date +\%F).tgz -C /var/www/arthub-back storage/app
```

- Crea antes la carpeta con `mkdir -p ~/respaldos`, y un archivo `~/.pgpass` con `127.0.0.1:5432:arthub:arthub:CLAVE_SEGURA` (`chmod 600 ~/.pgpass`).
- Descarga los respaldos a otro lugar de vez en cuando. Además, Hostinger ofrece **copias del VPS completo** (snapshots) en hPanel.

## Problemas frecuentes

| Síntoma | Causa probable | Qué hacer |
|---|---|---|
| **502 Bad Gateway** en el sitio | Next no está corriendo | `pm2 status` y `pm2 logs arthub-front` |
| **500** en la API o los paneles | Error de Laravel | `tail -50 /var/www/arthub-back/storage/logs/laravel-*.log` |
| El sitio carga pero sin obras | `NEXT_PUBLIC_API_URL` mal puesta, o el CORS | Revisa `.env.production.local` y vuelve a compilar; `FRONTEND_URL` del backend debe ser exactamente `https://TU_DOMINIO` |
| No llegan correos | `MAIL_*` | `php artisan tinker` → `Mail::raw('prueba', fn($m) => $m->to('tu@correo.com')->subject('Prueba'));` |
| «Page Expired» en `/ingresar` | Cookies de sesión | `SESSION_SECURE_COOKIE=true` solo con https; después de cambiar el `.env`, `php artisan optimize` |
| Certbot falla | El DNS aún no apunta al VPS | Espera, y comprueba con `nslookup` (paso 1) |
