<?php

namespace App\Support;

use App\Models\Artwork;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Modelo 3D para «Ver en mi pared» (§27): un cuadro con la foto de la obra en la cara
 * frontal y un canto oscuro, a la escala real de sus medidas. Las obras 2D no necesitan
 * modelado: se genera aquí mismo un archivo GLB (glTF 2.0 binario).
 *
 * El frente mira hacia +Z y la base queda en y = 0, como espera el visor para colgarlo en la pared.
 *
 * La proporción la manda la foto: así la obra nunca se ve deformada aunque las medidas se hayan
 * cargado al revés (alto × ancho o ancho × alto). De las medidas se toma el tamaño: el lado más
 * largo registrado es el lado más largo de la obra.
 */
class ArPlane
{
    /** Lado mayor de la textura: suficiente nitidez en el celular sin un archivo pesado. */
    private const MAX_TEXTURE = 2048;

    /** Profundidad mínima del cuadro, en cm, para obras sin profundidad registrada. */
    private const DEFAULT_DEPTH_CM = 2.5;

    /** ¿La obra tiene lo necesario (foto principal y medidas)? */
    public static function supports(Artwork $artwork): bool
    {
        return $artwork->height_cm > 0 && $artwork->width_cm > 0 && $artwork->images->firstWhere('is_primary', true);
    }

    /**
     * Clave del modelo: cambia si cambian la foto principal o las medidas, así el archivo
     * en caché se regenera solo.
     */
    public static function cacheKey(Artwork $artwork): string
    {
        $image = $artwork->images->firstWhere('is_primary', true);

        return substr(hash('sha256', implode('|', [
            $image?->path, $image?->sha256, $artwork->height_cm, $artwork->width_cm, $artwork->depth_cm, 'v2',
        ])), 0, 16);
    }

    /** GLB de la obra, leído de la caché del disco de imágenes o generado en el momento. */
    public static function forArtwork(Artwork $artwork): string
    {
        $path = "ar/{$artwork->id}-".self::cacheKey($artwork).'.glb';
        $disk = Disks::media();

        if ($disk->exists($path)) {
            return $disk->get($path);
        }

        [$jpeg, $ratio] = self::texture($artwork); // $ratio = alto / ancho de la foto
        [$width, $height] = self::realSize((float) $artwork->width_cm, (float) $artwork->height_cm, $ratio);

        $glb = self::build($jpeg, $width / 100, $height / 100, max((float) $artwork->depth_cm, self::DEFAULT_DEPTH_CM) / 100);

        // Se borran las versiones anteriores de esta obra antes de guardar la nueva.
        foreach ($disk->files('ar') as $old) {
            if (str_starts_with(basename($old), "{$artwork->id}-")) {
                $disk->delete($old);
            }
        }
        $disk->put($path, $glb);

        return $glb;
    }

    /**
     * Ancho y alto reales, en cm, con la proporción de la foto: el lado más largo de la foto
     * mide lo mismo que el lado más largo registrado.
     *
     * @return array{float, float}
     */
    public static function realSize(float $widthCm, float $heightCm, float $photoRatio): array
    {
        $long = max($widthCm, $heightCm);

        return $photoRatio >= 1
            ? [$long / $photoRatio, $long]   // vertical (o cuadrada)
            : [$long, $long * $photoRatio];  // apaisada
    }

    /**
     * Foto principal como JPEG, reducida si hace falta, y su proporción (alto / ancho).
     *
     * @return array{string, float}
     */
    private static function texture(Artwork $artwork): array
    {
        $image = $artwork->images->firstWhere('is_primary', true);

        try {
            if ($image->isStoredFile()) {
                $bytes = Disks::media()->get($image->path);
            } else {
                $url = str_starts_with($image->path, '/') ? config('app.frontend_url').$image->path : $image->path;
                $bytes = Http::timeout(10)->get($url)->throw()->body();
            }
        } catch (Throwable $e) {
            throw new RuntimeException("No se pudo leer la foto principal de la obra {$artwork->id}.", previous: $e);
        }

        $source = @imagecreatefromstring((string) $bytes);
        if (! $source) {
            throw new RuntimeException("La foto principal de la obra {$artwork->id} no es una imagen válida.");
        }

        $w = imagesx($source);
        $h = imagesy($source);
        $scale = min(1, self::MAX_TEXTURE / max($w, $h));
        $target = imagecreatetruecolor(max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale)));
        imagecopyresampled($target, $source, 0, 0, 0, 0, imagesx($target), imagesy($target), $w, $h);

        ob_start();
        imagejpeg($target, null, 85);

        return [(string) ob_get_clean(), $h / $w];
    }

    /**
     * Arma el GLB: un prisma de ancho × alto × profundidad (en metros). La cara frontal lleva
     * la foto (material 0) y las otras cinco un color de canto (material 1).
     */
    public static function build(string $jpeg, float $width, float $height, float $depth): string
    {
        $x0 = -$width / 2;
        $x1 = $width / 2;
        $z1 = 0.0;       // frente
        $z0 = -$depth;   // contra la pared

        // Frente: v = 0 arriba (convención de glTF).
        $front = [
            'pos' => [[$x0, 0, $z1], [$x1, 0, $z1], [$x1, $height, $z1], [$x0, $height, $z1]],
            'nrm' => array_fill(0, 4, [0, 0, 1]),
            'uv'  => [[0, 1], [1, 1], [1, 0], [0, 0]],
            'idx' => [0, 1, 2, 0, 2, 3],
        ];

        // Canto: atrás, arriba, abajo, izquierda y derecha (cada cara con su normal).
        $faces = [
            [[[$x1, 0, $z0], [$x0, 0, $z0], [$x0, $height, $z0], [$x1, $height, $z0]], [0, 0, -1]],
            [[[$x0, $height, $z1], [$x1, $height, $z1], [$x1, $height, $z0], [$x0, $height, $z0]], [0, 1, 0]],
            [[[$x0, 0, $z0], [$x1, 0, $z0], [$x1, 0, $z1], [$x0, 0, $z1]], [0, -1, 0]],
            [[[$x0, 0, $z0], [$x0, 0, $z1], [$x0, $height, $z1], [$x0, $height, $z0]], [-1, 0, 0]],
            [[[$x1, 0, $z1], [$x1, 0, $z0], [$x1, $height, $z0], [$x1, $height, $z1]], [1, 0, 0]],
        ];
        $side = ['pos' => [], 'nrm' => [], 'idx' => []];
        foreach ($faces as $i => [$quad, $normal]) {
            array_push($side['pos'], ...$quad);
            array_push($side['nrm'], ...array_fill(0, 4, $normal));
            $b = $i * 4;
            array_push($side['idx'], $b, $b + 1, $b + 2, $b, $b + 2, $b + 3);
        }

        $bin = '';
        $views = [];
        $accessors = [];
        $addView = function (string $data, ?int $target) use (&$bin, &$views): int {
            $bin .= str_repeat("\0", (4 - strlen($bin) % 4) % 4); // alineado a 4 bytes
            $views[] = array_filter(['buffer' => 0, 'byteOffset' => strlen($bin), 'byteLength' => strlen($data), 'target' => $target], fn ($v) => $v !== null);
            $bin .= $data;

            return count($views) - 1;
        };
        $addAccessor = function (array $rows, string $type, ?array $bounds = null) use (&$accessors, $addView): int {
            $flat = array_merge(...array_map(fn ($r) => (array) $r, $rows));
            $isIndex = $type === 'SCALAR';
            $view = $addView(pack($isIndex ? 'v*' : 'g*', ...$flat), $isIndex ? 34963 : 34962);
            $accessors[] = array_filter([
                'bufferView'    => $view,
                'componentType' => $isIndex ? 5123 : 5126, // uint16 o float32
                'count'         => count($rows),
                'type'          => $type,
                'min'           => $bounds[0] ?? null,
                'max'           => $bounds[1] ?? null,
            ], fn ($v) => $v !== null);

            return count($accessors) - 1;
        };
        $bounds = fn (array $pos) => [
            [min(array_column($pos, 0)), min(array_column($pos, 1)), min(array_column($pos, 2))],
            [max(array_column($pos, 0)), max(array_column($pos, 1)), max(array_column($pos, 2))],
        ];

        $frontPrimitive = [
            'attributes' => [
                'POSITION'   => $addAccessor($front['pos'], 'VEC3', $bounds($front['pos'])),
                'NORMAL'     => $addAccessor($front['nrm'], 'VEC3'),
                'TEXCOORD_0' => $addAccessor($front['uv'], 'VEC2'),
            ],
            'indices'  => $addAccessor($front['idx'], 'SCALAR'),
            'material' => 0,
        ];
        $sidePrimitive = [
            'attributes' => [
                'POSITION' => $addAccessor($side['pos'], 'VEC3', $bounds($side['pos'])),
                'NORMAL'   => $addAccessor($side['nrm'], 'VEC3'),
            ],
            'indices'  => $addAccessor($side['idx'], 'SCALAR'),
            'material' => 1,
        ];
        $imageView = $addView($jpeg, null);
        $bin .= str_repeat("\0", (4 - strlen($bin) % 4) % 4);

        $json = json_encode([
            'asset'      => ['version' => '2.0', 'generator' => 'ArtHub ArPlane'],
            'scene'      => 0,
            'scenes'     => [['nodes' => [0]]],
            'nodes'      => [['mesh' => 0, 'name' => 'Obra']],
            'meshes'     => [['primitives' => [$frontPrimitive, $sidePrimitive]]],
            'materials'  => [
                ['name' => 'Obra', 'pbrMetallicRoughness' => ['baseColorTexture' => ['index' => 0], 'metallicFactor' => 0, 'roughnessFactor' => 0.85]],
                ['name' => 'Canto', 'pbrMetallicRoughness' => ['baseColorFactor' => [0.13, 0.11, 0.09, 1], 'metallicFactor' => 0, 'roughnessFactor' => 0.9]],
            ],
            'textures'   => [['source' => 0, 'sampler' => 0]],
            'samplers'   => [['magFilter' => 9729, 'minFilter' => 9987, 'wrapS' => 33071, 'wrapT' => 33071]],
            'images'     => [['bufferView' => $imageView, 'mimeType' => 'image/jpeg']],
            'accessors'  => $accessors,
            'bufferViews'=> $views,
            'buffers'    => [['byteLength' => strlen($bin)]],
        ], JSON_UNESCAPED_SLASHES);
        $json .= str_repeat(' ', (4 - strlen($json) % 4) % 4);

        $chunks = pack('V', strlen($json)).'JSON'.$json.pack('V', strlen($bin))."BIN\0".$bin;

        return 'glTF'.pack('V', 2).pack('V', 12 + strlen($chunks)).$chunks;
    }
}
