# ArtHub · Pendientes

> Igual en **Art-Hub-Back** y **Art-Hub-Front**. Al cerrar un punto, márcalo `[x]` en los dos repos.
> El detalle de cada semana y cómo montar el entorno están en [`CRONOGRAMA.md`](CRONOGRAMA.md).
> Última actualización: 24 de octubre de 2026.

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

## 1. Urgente: servidor de producción

- [ ] **Revisar los registros del primer despliegue del 23/10:** que las migraciones y el seeder hayan terminado sin errores.
- [ ] **Entrar a `https://<backend>/ingresar`** con `ADMIN_EMAIL` y `ADMIN_PASSWORD`. Las cuentas de demostración no existen en producción.
- [ ] **Conectar Cloudflare R2:**
  - Buckets `arthub-media` (público) y `arthub-documentos` (privado).
  - Variables `MEDIA_DISK=r2_media`, `DOCUMENTS_DISK=r2_documents` y las `R2_*` de `.env.example`.
  - **Mientras no se haga, todo lo que se sube (fotos, documentos, certificados, modelos 3D) se pierde en el siguiente despliegue.**
- [ ] **Correo real** (Resend o Amazon SES): configurar `MAIL_MAILER` y `MAIL_*`. Sin esto no llegan la recuperación de contraseña, los avisos de revisión ni las solicitudes.
- [ ] **Programar la comprobación de integridad** a diario: `php artisan arthub:comprobar-integridad`.
- [ ] **Respaldos automáticos** de PostgreSQL y, si se puede, un ambiente de pruebas (*staging*).
- [ ] **Confirmar que `FRONTEND_URL` es el dominio definitivo:** va impreso en el QR de cada certificado, así que si cambia hay que reemitirlos.

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
