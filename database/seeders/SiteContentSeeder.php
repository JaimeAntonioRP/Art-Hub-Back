<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Setting;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

/**
 * Contenido inicial del sitio. Solo crea lo que falta: nunca pisa lo que se
 * editó desde el panel. Los textos siguen el alcance del plan (sin inversión,
 * blockchain ni IA de autenticación).
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSettings();
        foreach ($this->pages() as $slug => $page) {
            Page::firstOrCreate(['slug' => $slug], $page);
        }
    }

    private function seedSettings(): void
    {
        $existing = Setting::pluck('key')->all();
        $missing = array_diff_key([
            'brand_name' => 'ArtHub',
            'brand_tagline' => 'Arte · Tecnología · Confianza',
            'logo_url' => '/logifinal.png',
            'meta_description' => 'Catálogo de arte cusqueño: obras documentadas, artistas revisados y verificación pública de cada pieza.',
            'utility_text' => 'Cusco, Perú',
            'utility_link_label' => 'Verificar una obra',
            'utility_link_url' => '/verificar',
            'header_menu' => [
                ['label' => 'Catálogo', 'url' => '/obras'],
                ['label' => 'Verificar', 'url' => '/verificar'],
                ['label' => 'Cómo funciona', 'url' => '/como-funciona'],
                ['label' => 'Tecnología', 'url' => '/tecnologia'],
                ['label' => 'Artistas', 'url' => '/artistas'],
                ['label' => 'Nosotros', 'url' => '/nosotros'],
            ],
            'footer_about' => 'Catálogo y registro de arte cusqueño: obras documentadas, artistas revisados y verificación pública.',
            'footer_columns' => [
                ['title' => 'Navegación', 'links' => [
                    ['label' => 'Catálogo', 'url' => '/obras'],
                    ['label' => 'Cómo funciona', 'url' => '/como-funciona'],
                    ['label' => 'Tecnología', 'url' => '/tecnologia'],
                    ['label' => 'Artistas', 'url' => '/artistas'],
                    ['label' => 'Nosotros', 'url' => '/nosotros'],
                ]],
                ['title' => 'Ayuda', 'links' => [
                    ['label' => 'Verificar una obra', 'url' => '/verificar'],
                    ['label' => 'Crear cuenta', 'url' => '/registro'],
                    ['label' => 'Soy artista', 'url' => '/registro'],
                ]],
            ],
            'copyright' => '© '.date('Y').' ArtHub S.A.C. Todos los derechos reservados.',
            'contact_email' => 'info@arthub.pe',
            'contact_phone' => '+51 84 000 000',
            'contact_address' => 'Cusco, Perú',
        ], array_flip($existing));

        SiteContent::save($missing);
    }

    /** @return array<string, array> */
    private function pages(): array
    {
        $artistSteps = [
            ['title' => 'Crea tu cuenta', 'text' => 'Regístrate como artista y completa tu perfil: biografía, técnicas, trayectoria y fotografía.'],
            ['title' => 'Registra tus obras', 'text' => 'Carga la ficha técnica, las medidas, las imágenes y los documentos de cada obra.'],
            ['title' => 'Revisión de ArtHub', 'text' => 'El equipo revisa la información y, si hace falta, te deja observaciones para corregir.'],
            ['title' => 'Publicación', 'text' => 'La obra aparece en el catálogo con su código ArtHub y su ficha pública.'],
        ];

        return [
            'inicio' => [
                'title' => 'Inicio',
                'description' => 'Catálogo de arte cusqueño con fichas técnicas, artistas revisados y verificación pública de cada obra.',
                'blocks' => [
                    $this->block('hero', [
                        'eyebrow' => 'Arte cusqueño · Cusco, Perú',
                        'title' => 'Obras con historia,',
                        'highlight' => 'documentadas y verificables',
                        'text' => 'ArtHub reúne a artistas cusqueños y sus obras en un catálogo revisado. Cada pieza tiene ficha técnica, imágenes, expediente documental y un código único para consultarla.',
                        'show_featured_artwork' => true,
                        'primary_label' => 'Explorar catálogo', 'primary_url' => '/obras',
                        'secondary_label' => 'Soy artista', 'secondary_url' => '/registro',
                    ]),
                    $this->block('features', [
                        'eyebrow' => 'Qué encuentras en ArtHub',
                        'title' => 'Un registro serio para cada obra',
                        'items' => [
                            ['icon' => 'catalogo', 'title' => 'Catálogo revisado', 'text' => 'Cada obra pasa por la revisión del equipo de ArtHub antes de publicarse.'],
                            ['icon' => 'documento', 'title' => 'Expediente documental', 'text' => 'Declaraciones del artista, certificados existentes, procedencia e informes, asociados a la obra.'],
                            ['icon' => 'verificar', 'title' => 'Consulta pública', 'text' => 'Un código único por obra para consultar su registro desde cualquier dispositivo.'],
                            ['icon' => 'artista', 'title' => 'Perfiles de artistas', 'text' => 'Trayectoria, técnicas y reconocimientos de cada artista, revisados por ArtHub.'],
                        ],
                    ]),
                    $this->block('featured_artworks', [
                        'eyebrow' => 'Colección',
                        'title' => 'Obras publicadas recientemente',
                        'limit' => 6,
                        'button_label' => 'Ver catálogo',
                    ]),
                    $this->block('steps', [
                        'eyebrow' => 'Para artistas',
                        'title' => 'Publica tu obra en cuatro pasos',
                        'items' => $artistSteps,
                    ]),
                    $this->block('cta', [
                        'title' => '¿Te interesa una obra?',
                        'text' => 'Solicita información desde su ficha y el equipo de ArtHub te contactará para coordinar.',
                        'primary_label' => 'Ver catálogo', 'primary_url' => '/obras',
                        'secondary_label' => 'Verificar una obra', 'secondary_url' => '/verificar',
                    ]),
                ],
            ],
            'como-funciona' => [
                'title' => 'Cómo funciona',
                'description' => 'Cómo se registra, revisa y publica una obra en ArtHub, y cómo solicitar información sobre ella.',
                'blocks' => [
                    $this->block('hero', [
                        'eyebrow' => 'Cómo funciona',
                        'title' => 'Del taller del artista',
                        'highlight' => 'al catálogo',
                        'text' => 'Cada obra se registra con su ficha y sus documentos, pasa por una revisión y recién entonces se publica.',
                        'image_url' => '/obras/madre-cusquena.jpg',
                        'primary_label' => 'Crear cuenta', 'primary_url' => '/registro',
                    ]),
                    $this->block('steps', [
                        'eyebrow' => 'Para artistas',
                        'title' => 'Registrar y publicar una obra',
                        'items' => $artistSteps,
                    ]),
                    $this->block('steps', [
                        'eyebrow' => 'Para coleccionistas',
                        'title' => 'Encontrar y consultar una obra',
                        'items' => [
                            ['title' => 'Explora el catálogo', 'text' => 'Filtra por artista, técnica, disponibilidad y precio.'],
                            ['title' => 'Revisa la ficha', 'text' => 'Imágenes, medidas, técnica, información del artista y su código ArtHub.'],
                            ['title' => 'Solicita información', 'text' => 'Envía tu consulta o tu intención de compra desde la ficha.'],
                            ['title' => 'Coordina con ArtHub', 'text' => 'El equipo te contacta para acordar el pago y la entrega.'],
                        ],
                    ]),
                    $this->block('video', [
                        'eyebrow' => 'En video',
                        'title' => 'ArtHub en pocos minutos',
                        'video_url' => '/videos/como-funciona.mp4',
                    ]),
                    $this->block('faq', [
                        'title' => 'Preguntas frecuentes',
                        'items' => [
                            ['question' => '¿ArtHub certifica la autenticidad de las obras?', 'answer' => 'ArtHub revisa y registra la información y los documentos de cada obra. Los peritajes, si existen, los realizan especialistas externos y se adjuntan al expediente.'],
                            ['question' => '¿Qué es el código ArtHub?', 'answer' => 'Es el identificador único de cada obra (por ejemplo, AH-CUS-2026-000001). Con él puedes consultar su registro en la sección Verificar.'],
                            ['question' => '¿Cómo se paga una obra?', 'answer' => 'ArtHub no cobra en línea. Después de tu solicitud, el equipo coordina contigo el pago y la entrega.'],
                            ['question' => '¿Puedo publicar mis obras como artista?', 'answer' => 'Sí. Crea una cuenta de artista, completa tu perfil y registra tus obras; el equipo de ArtHub las revisa antes de publicarlas.'],
                        ],
                    ]),
                    $this->block('cta', [
                        'title' => '¿Eres artista?',
                        'text' => 'Crea tu cuenta y registra tu primera obra.',
                        'primary_label' => 'Crear cuenta de artista', 'primary_url' => '/registro',
                    ]),
                ],
            ],
            'tecnologia' => [
                'title' => 'Tecnología',
                'description' => 'Cómo ArtHub documenta cada obra: ficha estructurada, expediente, código único y vista 3D.',
                'blocks' => [
                    $this->block('hero', [
                        'eyebrow' => 'Tecnología',
                        'title' => 'Herramientas para documentar',
                        'highlight' => 'cada obra',
                        'text' => 'ArtHub ordena la información de cada pieza para que coleccionistas, artistas e instituciones la consulten con confianza.',
                        'show_featured_artwork' => true,
                    ]),
                    $this->block('features', [
                        'eyebrow' => 'Hoy en ArtHub',
                        'title' => 'Cómo se registra una obra',
                        'items' => [
                            ['icon' => 'obra', 'title' => 'Ficha estructurada', 'text' => 'Técnica, soporte, medidas, año, ubicación e información histórica en un mismo formato.'],
                            ['icon' => 'documento', 'title' => 'Expediente privado', 'text' => 'Los documentos se guardan de forma privada y solo los ve el equipo de ArtHub y el artista.'],
                            ['icon' => 'verificar', 'title' => 'Código único', 'text' => 'Cada obra recibe un código ArtHub para consultar su registro públicamente.'],
                            ['icon' => 'realidad', 'title' => 'Vista 3D', 'text' => 'Algunas obras incluyen un modelo 3D que puedes girar en su ficha.'],
                        ],
                    ]),
                    $this->block('text', [
                        'eyebrow' => 'Próximamente',
                        'title' => 'Certificado digital y realidad aumentada',
                        'body' => "Estamos preparando el certificado digital ArtHub con código QR y la verificación de integridad de los documentos del expediente.\n\nTambién podrás ver obras a escala en tu propio espacio con la cámara de tu teléfono.",
                    ]),
                    $this->block('cta', [
                        'title' => 'Consulta el registro de una obra',
                        'primary_label' => 'Verificar una obra', 'primary_url' => '/verificar',
                    ]),
                ],
            ],
            'nosotros' => [
                'title' => 'Nosotros',
                'description' => 'El equipo detrás de ArtHub y su propósito: conectar el arte cusqueño con el mundo.',
                'blocks' => [
                    $this->block('hero', [
                        'eyebrow' => 'Nosotros',
                        'title' => 'Conectamos el arte cusqueño',
                        'highlight' => 'con el mundo',
                        'text' => 'Un equipo multidisciplinario unido por la misión de difundir el arte cusqueño y proteger el patrimonio cultural del Perú con un registro documentado de cada obra y de su artista.',
                    ]),
                    $this->block('features', [
                        'eyebrow' => 'Lo que nos guía',
                        'title' => 'Nuestros valores',
                        'items' => [
                            ['icon' => 'obra', 'title' => 'Arte auténtico', 'text' => 'Cada obra pasa por una revisión y queda registrada con su ficha técnica, imágenes y documentación.'],
                            ['icon' => 'catalogo', 'title' => 'Alcance global', 'text' => 'Conectamos a coleccionistas de todo el mundo con los maestros del arte cusqueño.'],
                            ['icon' => 'verificar', 'title' => 'Transparencia', 'text' => 'La información de cada obra se puede consultar con su código ArtHub.'],
                            ['icon' => 'artista', 'title' => 'Patrimonio vivo', 'text' => 'Preservamos y difundimos el legado cultural del Cusco para las generaciones futuras.'],
                        ],
                    ]),
                    $this->block('team', [
                        'eyebrow' => 'Equipo',
                        'title' => 'Quiénes somos',
                        'members' => [
                            ['name' => 'Johanna Calanche Fernandez', 'role' => 'CEO', 'role_label' => 'Chief Executive Officer', 'photo_url' => '/equipo/johana.png', 'bio' => 'Lidera la visión estratégica de Arthub, conectando el arte cusqueño con mercados globales de inversión. Impulsa el crecimiento sostenible y la identidad de la plataforma.', 'institutions' => ['UNSAAC', 'UAUTONOMA', 'Univ. Norbert Wiener', 'PEI Consultores']],
                            ['name' => 'Augusto García Peñalva', 'role' => 'CAO', 'role_label' => 'Chief Art Officer', 'photo_url' => '/equipo/augusto.png', 'bio' => 'Máxima autoridad en curaduría y autenticación artística. Con formación en la UNADQTC, garantiza el rigor académico y cultural de cada obra certificada en Arthub.', 'institutions' => ['UNADQTC', 'Ministerio de Cultura Perú', 'UNESCO']],
                            ['name' => 'Manuel Ryu García', 'role' => 'CMO', 'role_label' => 'Chief Marketing Officer', 'photo_url' => '/equipo/manuel.png', 'bio' => 'Dirige la estrategia de marca y posicionamiento internacional. Trabaja con organismos como ZEGEL y el DIRCETUR Cusco para amplificar el alcance del arte peruano.', 'institutions' => ['Universidad del Pacífico', 'ZEGEL', 'DIRCETUR Cusco', 'AXON', 'PROM PERU']],
                            ['name' => 'Rodriguez Phillco Jaime Antonio', 'role' => 'CTO', 'role_label' => 'Chief Technology Officer', 'photo_url' => '/equipo/jaime.png', 'bio' => 'Arquitecto del ecosistema tecnológico de Arthub. Diseña la infraestructura blockchain, los algoritmos de autenticación biométrica y la plataforma de tokenización de activos artísticos.', 'institutions' => ['UNSAAC', 'GEREDU', 'JISA ADVENTURE', 'JUMPER STUDIO AI']],
                            ['name' => 'Atauchi Mamani Jose Emilio', 'role' => 'CTO', 'role_label' => 'Chief Technology Officer', 'photo_url' => '/equipo/emilio.png', 'bio' => 'Co-responsable de la arquitectura técnica. Especialista en sistemas distribuidos e integración con redes de energía y datos. Experiencia en infraestructura crítica con Electro Sur Este.', 'institutions' => ['UNSAAC', 'Electro Sur Este']],
                        ],
                    ]),
                    $this->block('cta', [
                        'title' => '¿Quieres trabajar con nosotros?',
                        'text' => 'Escríbenos a info@arthub.pe.',
                        'primary_label' => 'Ver catálogo', 'primary_url' => '/obras',
                    ]),
                ],
            ],
        ];
    }

    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }
}
