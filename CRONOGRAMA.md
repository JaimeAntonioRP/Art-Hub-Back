# ArtHub · Cronograma y estado del proyecto

> Este archivo es igual en **Art-Hub-Back** y **Art-Hub-Front**. Sirve para retomar el trabajo en otra máquina.
> Última actualización: 24 de octubre de 2026. **Las 8 semanas están programadas y publicadas en `main` (23/10/2026).** La lista corta de lo que falta está en [`PENDIENTES.md`](PENDIENTES.md).

Las bases son la propuesta *ArtHub_2_Etapas.docx* (dos etapas de S/ 6,000 cada una) y la hoja de ruta acordada:

- semana 0: limpieza;
- semanas 1 a 4: Etapa 1, ArtHub Core (§4–§17);
- semanas 5 a 8: Etapa 2, Certificación y experiencia (§19–§28).

Quedan fuera del alcance: pagos en línea, blockchain, NFT, IA, fraccionamiento e idiomas múltiples.

---

## 1. Dónde está el código

| Repo | Rama de trabajo (la última) | PR abierto |
|---|---|---|
| Art-Hub-Back (Laravel 12 + Filament 4) | `semana-8-realidad-aumentada` | [Art-Hub-Back#1](https://github.com/JaimeAntonioRP/Art-Hub-Back/pull/1) (Etapa 1, desde `semana-4-catalogo-solicitudes`) → `main` |
| Art-Hub-Front (Next.js 16) | `semana-8-realidad-aumentada` | [Art-Hub-Front#1](https://github.com/JaimeAntonioRP/Art-Hub-Front/pull/1) (Etapa 1, desde `semana-4-catalogo-solicitudes`) → `main` |

Las ramas están **apiladas**: cada semana sale de la anterior, así que la última rama ya contiene todo.

- **Back:** `semana-0-limpieza-esquema` → `semana-1-usuarios-artistas` → `semana-2-obras` → `semana-3-contenido-revision` → `home-limpio` → `semana-4-catalogo-solicitudes` → `semana-5-huellas-versiones` → `semana-6-certificados` → `login-unico` → `semana-7-propietarios-ventas` → `semana-8-realidad-aumentada`
- **Front:** `semana-0-limpieza` → `paleta-colores` → `semana-1-usuarios-artistas` → `semana-3-contenido-revision` → `home-limpio` → `semana-4-catalogo-solicitudes` → `semana-5-huellas-versiones` (solo este archivo) → `semana-6-certificados` → `login-unico` → `semana-7-propietarios-ventas` (solo este archivo) → `semana-8-realidad-aumentada`

Los PR abiertos cubren la Etapa 1. Para la Etapa 2, cuando se fusionen, abrir un PR nuevo desde la última rama.

**Convención:** cada semana nueva va en una rama nueva que sale de la anterior (por ejemplo `semana-5-huellas-versiones`). Estos archivos **no se suben nunca**: `render.yaml`, `start.sh`, `.claude/settings.local.json` y `next-env.d.ts`.

---

## 2. Montar el entorno en otra máquina

### Backend (Art-Hub-Back)

Requisitos:
- PHP 8.2 o superior, con las extensiones `intl`, `pdo_sqlite` o `pdo_pgsql`, `fileinfo`, `gd` (QR y PDF), `dom`, `mbstring` y `zip`;
- Composer 2.

Filament necesita `intl`. En XAMPP, esa extensión viene desactivada: actívala en `php.ini` o ejecuta PHP con `php -d extension=intl ...`.

```bash
git clone https://github.com/JaimeAntonioRP/Art-Hub-Back.git
cd Art-Hub-Back
git checkout semana-8-realidad-aumentada
composer install
cp .env.example .env
php artisan key:generate
```

Variables mínimas en `.env` para desarrollo:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
FRONTEND_URL=http://localhost:3001
DB_CONNECTION=sqlite          # o pgsql con DB_HOST/DB_DATABASE/...
DB_DATABASE=/ruta/absoluta/database/database.sqlite
MAIL_MAILER=log               # los correos quedan en storage/logs/laravel.log
ADMIN_EMAIL=admin@arthub.pe
ADMIN_PASSWORD=elige-una-clave
MEDIA_DISK=public
DOCUMENTS_DISK=local
```

Luego:

```bash
touch database/database.sqlite        # solo si usas SQLite
php artisan migrate --seed            # el seeder NO borra datos: crea admin, contenido y obras de ejemplo si faltan
php artisan storage:link
php artisan serve --port=8001
```

- **Login único:** `http://127.0.0.1:8001/ingresar` (o `/login` en el sitio, que redirige ahí). Cada rol sigue a su lugar: el admin a `/admin`, el artista a `/artista` y el comprador al sitio. Los paneles ya no tienen login propio, y al cerrar sesión se vuelve a `/ingresar`. Si alguien abre un enlace de un correo sin sesión, después de ingresar vuelve a ese enlace, siempre que sea de su propio panel.
- **Cuentas de demostración** (solo con `APP_ENV=local`; las crea `php artisan migrate --seed` o `php artisan db:seed --class=DemoAccountsSeeder`). La contraseña de las tres es `DEMO_PASSWORD`, por defecto `arthub-demo`:

  | Correo | Rol | Entra en |
  |---|---|---|
  | `admin@arthub.test` | Administrador | `/admin` |
  | `artista@arthub.test` | Artista (administra la colección de Augusto Garcia Peñalva) | `/artista` |
  | `comprador@arthub.test` | Comprador | sitio público |

  En producción no se crean nunca. Ahí el administrador inicial es `ADMIN_EMAIL` con `ADMIN_PASSWORD`.
- Tests: `php vendor/bin/phpunit`. Deben salir **104 tests en verde** (tardan unos 20 s por la generación de PDF). `phpunit.xml` fuerza SQLite en memoria, así que nunca toca tu base de datos.

### Frontend (Art-Hub-Front)

Requisito: Node 20 o superior.

```bash
git clone https://github.com/JaimeAntonioRP/Art-Hub-Front.git
cd Art-Hub-Front
git checkout semana-8-realidad-aumentada
npm install
echo "NEXT_PUBLIC_API_URL=http://127.0.0.1:8001/api" > .env.local
npm run dev -- -p 3001
```

Notas:
- Este Next.js 16 tiene cambios frente a versiones anteriores (`params` y `searchParams` son Promises, entre otros). Antes de tocar APIs de Next, lee `node_modules/next/dist/docs/` (ver `AGENTS.md`).
- El contenido del sitio (logo, header, footer, portada y páginas) se edita en **Panel admin → Sitio web** y aparece en menos de 60 s.

---

## 3. Lo que ya está hecho

| Semana | Secciones | Estado | Qué incluye |
|---|---|---|---|
| 0 · Limpieza | — | ✅ | Sin inversión, ventanas, compra directa, IA ni blockchain simulados. Seeder que no destruye datos. Esquema nuevo. Laravel 11 → 12 (avisos de seguridad) y Filament 4. Paleta de colores unificada. |
| 1 · Usuarios y artistas | §4 §5 §6 | ✅ | Roles admin, artista y comprador (se elige al registrarse). Recuperar contraseña y activar o desactivar cuentas. Perfil de artista con estados (pendiente, observado, aprobado, rechazado, inactivo) y revisión del admin con correo. |
| 2 · Obras y expediente | §7 §8 §9 | ✅* | Obra con código `AH-XXX-AAAA-NNNNNN` y medidas numéricas. Galería por tipo de imagen con una principal. Expediente con categorías, visibilidad y SHA-256 al subir, descargado con URL firmada. Discos configurables para R2. |
| 3 · Revisión y paneles | §10 §11 §15 §16 | ✅ | Estados borrador → en revisión → observado/aprobado/rechazado → publicado/despublicado. Notas de revisión e historial automático. Escritorios con indicadores para admin y artista. Contenido del sitio editable (CMS por bloques). |
| Home de venta | §12 | ✅ | Hero grande editable, galería limpia y botón destacado en el header. Sin datos del usuario en el header. |
| 4 · Catálogo y solicitudes | §12 §13 §14 §17 | ✅* | Catálogo con búsqueda, filtros, orden y paginado en el servidor. Ficha con galería, pantalla completa, 3D, ficha técnica y obras relacionadas. Formulario de solicitud con consentimiento, campo trampa y límite por IP, que avisa por correo al admin y al interesado. Bandeja **Solicitudes** en el panel admin. |

| 5 · Huellas y versiones | §21 §23 §24 (+ §22 interno) | ✅ | Cada versión registra su huella con algoritmo, fecha y autor. **Nueva versión** de un documento con motivo obligatorio, sin sobrescribir la anterior. Modal **Versiones** con la huella completa y descarga de cada una. **Comprobar integridad** (solo admin), que recalcula la huella y marca el documento como Íntegro o Alterado. Comando `php artisan arthub:comprobar-integridad`. Huella SHA-256 de las imágenes subidas. Historial con eventos de expediente y de solicitudes, filtrable. |

| 6 · Certificado, QR y verificación | §19 §20 §22 | ✅ | El admin emite, reemite o anula desde la pestaña **Certificados** de la obra. El PDF (dompdf) lleva foto, ficha, documentos relevantes con su huella, la huella de los datos y un QR generado en el servidor. Estados vigente, anulado (con motivo) y reemplazado. Portal `/verificar` por número o QR, y comprobación de un PDF en el navegador. |

| Login único | §5 | ✅ | Una sola página `/ingresar` para todos los roles, con redirección según el rol. Freno a la fuerza bruta (6 intentos por minuto) y aviso si el formulario expiró. La recuperación de contraseña es la del sitio (`/recuperar`). |

| 7 · Propietarios y ventas | §25 §26 | ✅ | Pestañas **Ventas** y **Propietarios** en la obra (solo el admin). Registro de reserva, venta o transferencia, también desde una solicitud. La disponibilidad y el propietario actual se actualizan solos. Un registro equivocado se anula con motivo y se deshacen sus efectos. Listado general en **Ventas**. |

| 8 · Realidad aumentada | §27 | ✅* | Botón **«Ver en mi pared»** en la ficha. El modelo 3D (GLB) se genera en el servidor desde la foto principal y las medidas, a tamaño real y sin deformar la foto. En el celular abre la cámara; en la computadora muestra el modelo y un QR para seguir en el teléfono. Falta la prueba en celulares reales. |

\* Con pendientes menores, listados en la sección 4.

---

## 4. Lo que falta para cerrar el proyecto

### Pendientes técnicos

- [ ] **Conectar Cloudflare R2.** Crear dos buckets: `arthub-media` (público) y `arthub-documentos` (privado). Luego definir `MEDIA_DISK=r2_media`, `DOCUMENTS_DISK=r2_documents` y las variables `R2_*` (están en `.env.example`). **Mientras no se haga, en Render o Railway los archivos subidos se pierden en cada deploy.**
- [ ] **Correo real** (Resend o Amazon SES): configurar `MAIL_MAILER` y `MAIL_*`. Sin esto no llegan la recuperación de contraseña, los avisos de revisión ni las solicitudes.
- [ ] **Staging y respaldos:** un ambiente de pruebas y copias de seguridad automáticas de PostgreSQL.
- [ ] **Miniaturas automáticas de imágenes** (§8, semana 2). Hoy se sirve la imagen original. Generar versiones de unos 400 px y 1600 px al subir.
- [ ] **Página `/artistas` y `/artistas/[slug]` con el nuevo estilo.** Todavía tienen el diseño anterior (estilos en línea). Ponerlas en el lenguaje de la portada y del catálogo, y en la ficha del artista enlazar a `/obras?artist=slug`.
- [ ] **Páginas de cuenta** (`/registro`, `/recuperar`, `/restablecer`): aplicarles el mismo estilo limpio. El login ya tiene el estilo nuevo (`/ingresar` en el backend).
- [ ] **QA responsive final (§17)** en todas las páginas: escritorio, tablet y móvil (375 px), con contraste AA. Catálogo, ficha y portada ya están revisados.
- [ ] **Manual básico de uso** (admin y artista) y **documentación técnica** de despliegue (semana 4 del plan).
- [ ] **Probar «Ver en mi pared» en celulares reales**: un Android con Chrome y ARCore, y un iPhone con iOS 16 o superior en Safari. Necesita el sitio publicado con **https**, porque la cámara en modo AR no funciona en `localhost` desde el teléfono.
- [ ] Desplegar y probar con el cliente. **Firmar la aceptación de la Etapa 1** y, después, la de la **Etapa 2**.

- [ ] **Decidir qué hace el comprador con su cuenta.** Hoy entra y vuelve al catálogo sin nada propio. Propuesta: una página «Mis solicitudes» y que el formulario de solicitud se rellene solo con sus datos.

### Pendientes de contenido (los ve el cliente)

- [ ] Subir las imágenes de portada en buena resolución (1920 px o más).
- [ ] Revisar en el CMS la bio del CTO, que todavía menciona blockchain.
- [ ] Revisar los textos del formulario de solicitud, que prometen el contacto de un asesor.
- [ ] **Corregir las medidas de las obras de ejemplo.** En 12 de las 19 obras las medidas no coinciden con la foto: casi siempre están escritas como «lado corto × lado largo» aunque la obra sea vertical. El modelo de AR no se deforma, porque usa la proporción de la foto y el lado más largo registrado, pero la ficha y el certificado muestran las medidas tal como están cargadas.
- [ ] Que el cliente apruebe la redacción del certificado PDF («no constituye un peritaje de autenticidad»).

### Despliegue (`main` ya publicado el 23/10/2026)

**Atención: publicar en `main` despliega en producción.**
- **Vercel** publica el front.
- **El backend** (`Dockerfile` / `nixpacks.toml`) ejecuta `php artisan migrate --force` y `php artisan db:seed --force` al arrancar, **sobre la base real**.

Ya se fusionó `semana-8-realidad-aumentada` en `main` en los dos repos. Los PR Art-Hub-Back#1 y Art-Hub-Front#1 quedaron cerrados como fusionados. Cada nuevo cambio va en una rama desde `main`, y antes de publicarlo hay que tener en cuenta lo siguiente:

1. Respaldar la base de datos de producción.
2. Definir en el servidor:
   - `APP_ENV=production` y `ADMIN_PASSWORD`;
   - `MAIL_*`;
   - `FRONTEND_URL` con el dominio real del sitio (va impreso en el QR de los certificados);
   - en el front, `NEXT_PUBLIC_API_URL`.
3. Conectar R2 (o asumir que se pierden los archivos subidos en cada despliegue).
4. Publicar back y front **juntos**: el front usa las respuestas nuevas de la API.
5. Programar `php artisan arthub:comprobar-integridad` a diario.

---

## 5. Etapa 2 · Certificación y experiencia digital (semanas 5–8)

### Semana 5 · Huellas, versiones e historial avanzado (§21 §23 §24) ✅
- [ ] **Prueba rápida de AR** en los dispositivos acordados. **Sigue pendiente:** necesita un Android y un iPhone reales. Abrir en el celular la ficha de una obra con modelo 3D (por ejemplo `/obras/1`) y tocar «3D / AR».
- [x] Registro de huellas por versión: algoritmo (`hash_algorithm`), fecha y autor (`uploaded_by`).
- [x] Nueva versión de un documento sin sobrescribir la anterior, con motivo obligatorio. El modelo también rechaza versiones sin motivo.
- [x] Modal «Versiones» con todas las versiones, su huella completa, el motivo y la descarga.
- [x] Comprobación de integridad (adelanto interno del §22): en el panel, solo para el admin, y por consola con `arthub:comprobar-integridad [--obra=ID]`, que devuelve un código de error si algo falla. Cada fallo queda en el historial.
- [x] Huella SHA-256 de las imágenes subidas (`artwork_images.sha256`), para el certificado de la semana 6.
- [x] Historial con nuevos eventos: documento agregado, versionado o eliminado; integridad no verificada; solicitud recibida o actualizada (sin datos personales). Tiene un filtro por grupo: ficha, expediente o solicitudes.
- **Al desplegar:** programar `php artisan arthub:comprobar-integridad` a diario (cron o scheduler de Render/Railway).
- **Cumplido:** un documento tiene varias versiones consultables, cada una con su huella. Lo cubren los tests de `tests/Feature/FingerprintTest.php`.

Los próximos eventos del historial (certificado emitido o anulado, venta, cambio de propietario) se agregan en las semanas 6 y 7.

### Semana 6 · Certificado PDF, QR y verificación (§19 §20 §22) ✅
- [x] **PDF del certificado** (`barryvdh/laravel-dompdf`), solo desde una obra aprobada o publicada.
  - Lleva el número `<código de la obra>-C<n>`, la foto principal, la ficha, los documentos **relevantes** con su huella, la huella de los datos certificados y el QR.
  - Se guarda en el disco de documentos: `certificados/<obra>/<número>.pdf`.
  - Se genera **al momento**, no en la cola, porque tarda menos de un segundo. Si algún día se pasa a la cola, hay que tener un worker corriendo.
- [x] **QR generado en el servidor** (`chillerlan/php-qrcode`). Se retiró `api.qrserver.com`.
- [x] **Estados:**
  - **Vigente.**
  - **Reemplazado:** al reemitir, el anterior remite al nuevo.
  - **Anulado:** el motivo es obligatorio y se muestra en el portal.
  - Cada cambio queda en el historial de la obra.
- [x] **Las dos huellas:**
  - El PDF lleva impresa la huella de los **datos** (`data_sha256`, sobre un `snapshot` JSON de orden fijo).
  - El portal publica la huella del **archivo PDF** (`pdf_sha256`).
- [x] **Aviso «La obra cambió» en el panel** cuando la ficha, la imagen principal o un documento relevante cambiaron después de emitir. Así el admin sabe que conviene reemitir.
- [x] **Portal `/verificar`:**
  - Se consulta por número o pegando el enlace del QR. La búsqueda funciona sin JavaScript.
  - La página de cada certificado muestra el estado, la obra y las huellas.
  - **El PDF solo se descarga con el token del QR, no con el número.**
  - El portal no guarda en caché el estado: una anulación se ve al instante.
- [x] **Comprobar un PDF** en el portal: la huella se calcula en el navegador con `crypto.subtle` y el archivo nunca se sube.
  - En la página de un certificado, se compara con ese PDF.
  - En `/verificar`, se busca a qué certificado corresponde (`GET /api/certificates/fingerprint/{sha256}`).
- [x] **Panel del artista:** ve sus certificados y descarga el PDF; no puede emitir ni anular.
- **Cumplido:** el QR lleva a la obra y al estado del certificado, y un PDF alterado aparece como «Integridad no verificada». Lo probé en el navegador y lo cubren los tests de `tests/Feature/CertificateTest.php`.
- **Al desplegar:**
  - `FRONTEND_URL` debe ser el dominio real, porque es el enlace que queda impreso en el QR. Si cambia el dominio, hay que reemitir.
  - Los certificados de la versión anterior de la plataforma quedan **anulados** por migración, con su motivo.

### Semana 7 · Propietarios y ventas (§25 §26) ✅
- [x] **Registro manual** en la pestaña **Ventas** de la obra, o desde una **solicitud** con el botón «Registrar reserva o venta», que rellena los datos del interesado. Lo que se ofrece depende de la disponibilidad:
  - **Reserva:** solo para una obra disponible. La obra pasa a «Reservada» y el propietario no cambia.
  - **Venta:** para una obra disponible o reservada. Pasa a «Vendida»; el precio final es obligatorio y el comprador queda como propietario actual.
  - **Transferencia:** solo para obras vendidas o no a la venta (herencia, donación, reventa privada). Cambia el propietario y la disponibilidad no cambia.
- [x] **Historial de propietarios** (pestaña **Propietarios**):
  - El primer propietario es el artista, desde que registró la obra; esa fila se crea con la primera venta o transferencia.
  - El propietario actual es el que no tiene fecha de fin.
- [x] **Solicitud de origen:** al registrar una venta queda «Confirmada»; con una reserva, «En evaluación».
- [x] **Anular un registro equivocado:** pide motivo y solo se puede con el último registro vigente. La disponibilidad y el propietario vuelven a como estaban, y el registro queda visible como «Anulado».
- [x] **Historial de la obra:** registra reserva, venta y transferencia, y «Registro de venta anulado». **Sin datos del comprador**, porque el artista también ve el historial.
- [x] **Privacidad:** solo el admin ve compradores, precios finales y propietarios. Las pestañas no aparecen en el panel del artista.
- [x] **Listado general «Ventas»** en el menú del admin, con filtros por tipo y estado (por defecto, los vigentes).
- **Cumplido:** registrar una venta pone la obra en «Vendida», crea el nuevo propietario y deja el evento en el historial. Lo probé en el panel y lo cubren los tests de `tests/Feature/SaleTest.php`.
- **Pendiente con el cliente:** ¿los propietarios deben verse en el portal de verificación? Hoy **no** se muestran, porque son datos personales.

### Semana 8 · Realidad aumentada y cierre (§27) ✅ (falta la prueba en celulares)
- [x] **Modelo GLB generado en el servidor** (`app/Support/ArPlane.php`): un cuadro con la foto principal al frente y un canto oscuro, a tamaño real. Las obras 2D no necesitan modelado.
  - **La proporción la manda la foto**, así nunca se deforma; de las medidas se toma el lado más largo.
  - La profundidad es la registrada, o 2,5 cm si no hay.
  - La foto se reduce a 2048 px como máximo. Un modelo pesa unos 300 KB.
  - Se guarda en caché en el disco de imágenes (`ar/<obra>-<clave>.glb`) y se regenera solo si cambian la foto o las medidas.
  - Pasa el **validador oficial de glTF (Khronos)** sin errores ni advertencias.
- [x] **API:**
  - `GET /api/artworks/{id}/ar.glb` sirve el modelo, con CORS y caché.
  - `GET /api/artworks/{id}/ar-qr.png` da un QR que abre la ficha en el celular (`/obras/{id}?ar=1`).
  - La ficha trae `ar_model_url`, versionada para que nunca quede un modelo viejo en caché, y `ar_qr_url`.
  - Solo obras publicadas, con foto principal y medidas.
- [x] **Botón «Ver en mi pared»** en la ficha (`components/catalog/WallView.tsx`):
  - Usa model-viewer 3.5.0 en modo `ar-placement="wall"` y `ar-scale="fixed"`, que fija el tamaño real y no deja agrandarlo.
  - Funciona con WebXR (Android), Scene Viewer (Android) y Quick Look (iPhone); para iPhone, model-viewer arma el USDZ solo.
  - **En un celular compatible:** el botón «Abrir la cámara».
  - **En la computadora:** el modelo para girarlo y un QR para seguir en el teléfono.
  - **En un celular sin AR:** explica qué navegadores sirven.
  - Al llegar desde el QR (`?ar=1`), el panel se abre solo.
- [ ] **Pruebas en los dispositivos acordados** (ver sección 4) y **aceptación de la Etapa 2**.
- **Listo cuando:** en los dispositivos acordados la obra aparece en la pared a escala aproximada. Lo cubren los tests de `tests/Feature/ArTest.php`; falta la prueba física.

---

## 6. Decisiones que siguen abiertas con el cliente

- **Forma de pago** de cada etapa. Sugerencia: 40 % al inicio, 30 % en la demo y 30 % en la aceptación.
- **Plazo:** días calendario o hábiles, contados desde que se entregan contenidos y accesos.
- **Condiciones de entrega:** rondas de observaciones, garantía, vigencia de la propuesta y carga inicial de datos.
- **Dispositivos de aceptación de la AR.** Propuesta: Android de gama media con Chrome y ARCore, e iPhone con iOS 16 o superior en Safari.
- **Regla del código AH.** Implementada como `AH-<región del artista>-<año de registro>-<correlativo global de 6 dígitos>`. Se asigna al crear la obra y no cambia.
- **Proveedores y costos de terceros:** Cloudflare R2, el correo transaccional y el hosting son costos del cliente (§31).

---

## 7. Referencias rápidas

| Qué | Dónde |
|---|---|
| Reglas de estados de obra | `app/Models/Artwork.php` (`transitionTo`, `ARTIST_TRANSITIONS`, `ADMIN_TRANSITIONS`) |
| Permisos | `app/Policies/ArtworkPolicy.php` |
| Discos de almacenamiento | `config/arthub.php`, `config/filesystems.php`, `app/Support/Disks.php` |
| Contenido del sitio (API) | `app/Support/SiteContent.php`, `GET /api/site`, `GET /api/pages/{slug}` |
| Catálogo y ficha (API) | `app/Http/Controllers/Api/ArtworkController.php` |
| Solicitudes (API) | `POST /api/artworks/{id}/requests` → `CommercialRequestController` |
| Paneles Filament | `app/Filament/` (admin) y `app/Filament/Artista/` (artista) |
| Front: contenido y catálogo | `lib/content.ts`, `lib/catalog.ts`, `components/blocks/`, `components/catalog/` |
| Front: tokens de color | `app/globals.css` (escalas oro/azul/piedra/terracota/quenua/ocre y tokens semánticos) |
