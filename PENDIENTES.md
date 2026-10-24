# ArtHub · Pendientes

> Igual en **Art-Hub-Back** y **Art-Hub-Front**. Al cerrar un punto, márcalo `[x]` en los dos repos.
> El detalle de cada semana y cómo montar el entorno están en [`CRONOGRAMA.md`](CRONOGRAMA.md).
> Última actualización: 24 de octubre de 2026 (sitio en producción en el VPS).

## Estado actual

- **Las 8 semanas del plan están programadas** y publicadas en `main` desde el 23/10/2026:
  - Art-Hub-Back: `271d47d`.
  - Art-Hub-Front: `32803ad`.
- `main` despliega en producción:
  - **Vercel** publica el front.
  - **El backend** ejecuta `migrate --force` y `db:seed --force` al arrancar.
- **Tests del backend:** `php vendor/bin/phpunit` → 104 en verde.
- **Para continuar en otra máquina:**

  ```bash
  git clone https://github.com/JaimeAntonioRP/Art-Hub-Back.git
  git clone https://github.com/JaimeAntonioRP/Art-Hub-Front.git
  ```

  Rama `main` en ambos; luego sigue la sección 2 de `CRONOGRAMA.md`.
- **Para trabajar:** crea una rama nueva desde `main` y fusiónala cuando esté probada. No subas `render.yaml`, `start.sh`, `.claude/settings.local.json` ni `next-env.d.ts`.

---

## 1. Producción en el VPS de Hostinger

**En línea desde el 24/10/2026: https://arthub.com.pe** (sitio) y **https://api.arthub.com.pe** (API, paneles y `/ingresar`).
- VPS `2.24.212.131`: Ubuntu 26.04, PHP 8.5, PostgreSQL 18, Node 22 y PM2.
- El código está en `/var/www/arthub-back` y `/var/www/arthub-front`, rama `main`. Para actualizar: `./deploy/deploy.sh` en cada carpeta, como `deploy`.
- Los pasos están en [`DESPLIEGUE.md`](DESPLIEGUE.md).

- [x] DNS en GoDaddy: `@`, `www` y `api` apuntan al VPS.
- [x] Servidor preparado, con cortafuegos (solo SSH y web).
- [x] Base nueva con el seeder: solo la colección de Augusto (1 artista y 19 obras).
- [x] Backend en `api.arthub.com.pe` y sitio en `arthub.com.pe` (PM2 arranca solo al reiniciar el servidor).
- [x] https con Let's Encrypt en los tres nombres, con renovación automática (vence el 22/01/2027 si no se renovara).
- [x] Cron de Laravel instalado; la comprobación de integridad corre a diario a las 03:00.
- [x] Administrador creado (`jrodriguezphillco@gmail.com`). La contraseña se quitó del `.env`. **Conviene cambiarla por una de 12 caracteres o más** desde el panel.
- [ ] **Correo real.** Hoy `MAIL_MAILER=log`: los avisos van al log del servidor. Para la cuenta de GoDaddy, en `/var/www/arthub-back/.env`: `MAIL_MAILER=smtp`, `MAIL_HOST=smtpout.secureserver.net`, `MAIL_PORT=465`, `MAIL_SCHEME=smtps` y `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS` con esa cuenta. Después: `php artisan optimize`.
- [ ] **Respaldos diarios** de la base y de `storage/app` (DESPLIEGUE.md, «Respaldos»), más los snapshots del VPS en hPanel.
- [ ] **Apagar lo anterior:** el proyecto de Vercel (o quitarle el despliegue automático) y el backend de Railway/Render.
- [ ] **Quitar el acceso SSH de Claude** cuando ya no haga falta: borrar la línea `claude-arthub-20261024` de `/root/.ssh/authorized_keys`.
- [ ] R2 no es urgente: en el VPS los archivos no se borran al actualizar. Sirve como respaldo fuera del servidor.

## 2. Pruebas

- [ ] **«Ver en mi pared» en celulares reales**, con el sitio en https:
  - Un Android con Chrome y ARCore.
  - Un iPhone con iOS 16 o superior en Safari.
  - Verificar que la obra se cuelga a escala aproximada.
- [ ] **Certificado de punta a punta:** emitirlo desde el panel, escanear el QR con el celular y comprobar el PDF en `/verificar`.
- [ ] **Recorrido completo con el cliente:**
  - registro del artista;
  - obra y expediente;
  - revisión y publicación;
  - solicitud y venta;
  - certificado.
- [ ] **Revisión final en móvil y tablet (§17)** de las páginas que aún no se revisaron: `/artistas`, `/registro`, `/recuperar` y `/restablecer`.

## 3. Contenido y datos (los carga el cliente o el admin)

- [ ] **Corregir las medidas de las obras de ejemplo.**
  - En 12 de las 19 están como «lado corto × lado largo», aunque la obra sea vertical.
  - La ficha y el certificado muestran lo cargado. La realidad aumentada no se deforma, porque usa la proporción de la foto.
- [ ] Subir las imágenes de portada en buena resolución (1920 px o más).
- [ ] Revisar en **Sitio web → Páginas** la bio del CTO, que todavía menciona blockchain.
- [ ] Revisar los textos del formulario de solicitud, que prometen el contacto de un asesor.
- [ ] Marcar como **relevantes** los documentos del expediente que deben ir en cada certificado.

## 4. Decisiones del cliente

- [ ] **Redacción del certificado PDF:** hoy dice que acredita el registro y la revisión del expediente, y que *no constituye un peritaje de autenticidad*.
- [ ] **Cuenta del comprador:** hoy entra y vuelve al catálogo sin nada propio. Propuesta: una página «Mis solicitudes» y que el formulario de solicitud se rellene solo con sus datos.
- [ ] **¿Mostrar los propietarios en el portal de verificación?** Hoy no se muestran, porque son datos personales.
- [ ] **Dispositivos de aceptación de la realidad aumentada.** Propuesta: un Android de gama media con Chrome, y un iPhone con iOS 16 o superior.
- [ ] **Condiciones del contrato:** forma de pago por etapa, plazos, rondas de observaciones, garantía y costos de terceros (R2, correo y hosting).

## 5. Mejoras técnicas pendientes

- [ ] **Miniaturas automáticas** de las imágenes (unos 400 px y 1600 px) al subirlas. Hoy se sirve la original.
- [ ] **`/artistas` y `/artistas/[slug]` con el estilo nuevo**, y enlace a `/obras?artist=slug`.
- [ ] **Páginas `/registro`, `/recuperar` y `/restablecer` con el estilo limpio** del sitio. El login `/ingresar` ya lo tiene.
- [ ] **Manual básico de uso** (admin y artista) y **guía de despliegue**.

## 6. Cierre

- [ ] Firmar la **aceptación de la Etapa 1** (semanas 0–4).
- [ ] Firmar la **aceptación de la Etapa 2** (semanas 5–8), después de las pruebas de realidad aumentada en los dispositivos acordados.
