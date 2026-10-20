# ArtHub · Cronograma y estado del proyecto

> Este archivo es igual en **Art-Hub-Back** y **Art-Hub-Front**. Sirve para retomar el trabajo en otra máquina.
> Última actualización: 20 de octubre de 2026.

Las bases son la propuesta *ArtHub_2_Etapas.docx* (dos etapas de S/ 6,000 cada una) y la hoja de ruta acordada:

- semana 0: limpieza;
- semanas 1 a 4: Etapa 1, ArtHub Core (§4–§17);
- semanas 5 a 8: Etapa 2, Certificación y experiencia (§19–§28).

Quedan fuera del alcance: pagos en línea, blockchain, NFT, IA, fraccionamiento e idiomas múltiples.

---

## 1. Dónde está el código

| Repo | Rama de trabajo (la última) | PR abierto |
|---|---|---|
| Art-Hub-Back (Laravel 12 + Filament 4) | `semana-4-catalogo-solicitudes` | [Art-Hub-Back#1](https://github.com/JaimeAntonioRP/Art-Hub-Back/pull/1) → `main` |
| Art-Hub-Front (Next.js 16) | `semana-4-catalogo-solicitudes` | [Art-Hub-Front#1](https://github.com/JaimeAntonioRP/Art-Hub-Front/pull/1) → `main` |

Las ramas están **apiladas**: cada semana sale de la anterior, así que `semana-4-catalogo-solicitudes` ya contiene todo.

- **Back:** `semana-0-limpieza-esquema` → `semana-1-usuarios-artistas` → `semana-2-obras` → `semana-3-contenido-revision` → `home-limpio` → `semana-4-catalogo-solicitudes`
- **Front:** `semana-0-limpieza` → `paleta-colores` → `semana-1-usuarios-artistas` → `semana-3-contenido-revision` → `home-limpio` → `semana-4-catalogo-solicitudes`

**Convención:** cada semana nueva va en una rama nueva que sale de la anterior (por ejemplo `semana-5-huellas-versiones`). Estos archivos **no se suben nunca**: `render.yaml`, `start.sh`, `.claude/settings.local.json` y `next-env.d.ts`.

---

## 2. Montar el entorno en otra máquina

### Backend (Art-Hub-Back)

Requisitos:
- PHP 8.2 o superior, con las extensiones `intl`, `pdo_sqlite` o `pdo_pgsql`, `fileinfo`, `gd` y `zip`;
- Composer 2.

Filament necesita `intl`. En XAMPP, esa extensión viene desactivada: actívala en `php.ini` o ejecuta PHP con `php -d extension=intl ...`.

```bash
git clone https://github.com/JaimeAntonioRP/Art-Hub-Back.git
cd Art-Hub-Back
git checkout semana-4-catalogo-solicitudes
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

- Paneles: `http://127.0.0.1:8001/admin` (administrador) y `http://127.0.0.1:8001/artista` (artista).
- Tests: `php vendor/bin/phpunit`. Deben salir **65 tests en verde**. `phpunit.xml` fuerza SQLite en memoria, así que nunca toca tu base de datos.

### Frontend (Art-Hub-Front)

Requisito: Node 20 o superior.

```bash
git clone https://github.com/JaimeAntonioRP/Art-Hub-Front.git
cd Art-Hub-Front
git checkout semana-4-catalogo-solicitudes
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

\* Con pendientes menores, listados en la sección 4.

---

## 4. Lo que falta para cerrar la Etapa 1

### Pendientes técnicos

- [ ] **Conectar Cloudflare R2.** Crear dos buckets: `arthub-media` (público) y `arthub-documentos` (privado). Luego definir `MEDIA_DISK=r2_media`, `DOCUMENTS_DISK=r2_documents` y las variables `R2_*` (están en `.env.example`). **Mientras no se haga, en Render o Railway los archivos subidos se pierden en cada deploy.**
- [ ] **Correo real** (Resend o Amazon SES): configurar `MAIL_MAILER` y `MAIL_*`. Sin esto no llegan la recuperación de contraseña, los avisos de revisión ni las solicitudes.
- [ ] **Staging y respaldos:** un ambiente de pruebas y copias de seguridad automáticas de PostgreSQL.
- [ ] **Miniaturas automáticas de imágenes** (§8, semana 2). Hoy se sirve la imagen original. Generar versiones de unos 400 px y 1600 px al subir.
- [ ] **Página `/artistas` y `/artistas/[slug]` con el nuevo estilo.** Todavía tienen el diseño anterior (estilos en línea). Ponerlas en el lenguaje de la portada y del catálogo, y en la ficha del artista enlazar a `/obras?artist=slug`.
- [ ] **Páginas de cuenta** (`/login`, `/registro`, `/recuperar`, `/restablecer`): aplicarles el mismo estilo limpio.
- [ ] **QA responsive final (§17)** en todas las páginas: escritorio, tablet y móvil (375 px), con contraste AA. Catálogo, ficha y portada ya están revisados.
- [ ] **Manual básico de uso** (admin y artista) y **documentación técnica** de despliegue (semana 4 del plan).
- [ ] Desplegar y probar con el cliente. **Firmar la aceptación de la Etapa 1.**

### Pendientes de contenido (los ve el cliente)

- [ ] Subir las imágenes de portada en buena resolución (1920 px o más).
- [ ] Revisar en el CMS la bio del CTO, que todavía menciona blockchain.
- [ ] Revisar los textos del formulario de solicitud, que prometen el contacto de un asesor.

### Antes de fusionar los PR y desplegar

1. Respaldar la base de datos de producción.
2. Fusionar los dos PR **juntos**: el front usa las respuestas nuevas de la API.
3. Ejecutar `php artisan migrate --force` y `php artisan db:seed --force`. El seeder no borra datos.
4. Definir `ADMIN_PASSWORD`, `MAIL_*`, `FRONTEND_URL` y, en el front, `NEXT_PUBLIC_API_URL`.

---

## 5. Etapa 2 · Certificación y experiencia digital (semanas 5–8)

### Semana 5 · Huellas, versiones e historial avanzado (§21 §23 §24)
- [ ] El primer día, una **prueba rápida de AR** en los dispositivos acordados, para descubrir pronto los problemas.
- [ ] Registro de huellas por versión: algoritmo, fecha y autor. El SHA-256 ya se guarda desde la semana 2.
- [ ] Subir una nueva versión de un documento sin sobrescribir la anterior, con motivo obligatorio. La tabla `document_versions` ya existe.
- [ ] Nuevos tipos de evento en el historial: documento versionado, certificado emitido, venta, etc.
- **Listo cuando:** un documento tiene 3 versiones consultables, cada una con su huella.

### Semana 6 · Certificado PDF, QR y verificación (§19 §20 §22)
- [ ] Generar el PDF del certificado desde una obra aprobada (por ejemplo con `barryvdh/laravel-dompdf`), en la cola de trabajos.
- [ ] **El QR se genera en el servidor.** Hoy `/verificar/[token]` usa `api.qrserver.com`, que es externo y debe retirarse.
- [ ] Estados del certificado: vigente, anulado y reemplazado, con reemisión.
- [ ] Huellas del certificado: el PDF muestra la huella de los **datos** de la obra, y el portal muestra la huella del **PDF** emitido (un PDF no puede contener su propia huella).
- [ ] Portal público `/verificar` por código o QR, rehecho con el nuevo estilo.
- [ ] Comprobación de integridad de un archivo: la huella se calcula en el navegador y el archivo no se sube.
- **Listo cuando:** al escanear el QR se ve la obra y el estado del certificado, y un PDF alterado aparece como «Integridad no verificada».

### Semana 7 · Propietarios y ventas (§25 §26)
- [ ] Registro manual de una reserva, venta o transferencia. Puede partir de una **solicitud confirmada**, que ya existe en el panel.
- [ ] Al registrar una venta, la disponibilidad y el propietario actual se actualizan solos.
- [ ] Historial de propietarios.
- **Listo cuando:** registrar una venta pone la obra en «vendida», crea el nuevo propietario y deja el evento en el historial.

### Semana 8 · Realidad aumentada y cierre (§27)
- [ ] Generar un plano GLB desde la imagen principal y las medidas reales. Las obras 2D no necesitan modelado.
- [ ] Botón «Ver en mi pared» en la ficha. El visor model-viewer 3.5.0 ya está integrado. Debe tener una alternativa para dispositivos no compatibles.
- [ ] Pruebas en los dispositivos acordados y entrega final. **Aceptación de la Etapa 2.**
- **Listo cuando:** en los dispositivos acordados la obra aparece en la pared a escala aproximada.

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
