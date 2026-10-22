<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Certificado {{ $certificate->number }}</title>
<style>
    @page { margin: 22mm 20mm 18mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #0D1B2A; line-height: 1.45; }
    .serif { font-family: 'DejaVu Serif', serif; }
    .muted { color: #6A5F51; }
    .gold { color: #7A5C1B; }
    .brand { font-size: 10pt; letter-spacing: 4pt; font-weight: bold; color: #0D1B2A; }
    .brand-tag { font-size: 6.5pt; letter-spacing: 2pt; color: #7A5C1B; text-transform: uppercase; }
    .rule { border-top: 1.2pt solid #CBA24A; margin: 10pt 0 16pt; }
    h1 { font-family: 'DejaVu Serif', serif; font-weight: normal; font-size: 22pt; margin: 0 0 4pt; }
    .label { font-size: 6.8pt; letter-spacing: 1.2pt; text-transform: uppercase; color: #6A5F51; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; }
    .data td { padding: 5pt 0; border-bottom: 0.6pt solid #E2D3BA; }
    .data td.k { width: 32%; color: #6A5F51; font-size: 8.5pt; }
    .title { font-family: 'DejaVu Serif', serif; font-size: 15pt; }
    .photo { width: 100%; max-height: 92mm; border: 0.6pt solid #E2D3BA; }
    .docs td { padding: 4pt 6pt 4pt 0; border-bottom: 0.6pt solid #EFE6D5; font-size: 8pt; }
    .hash { font-family: 'DejaVu Sans Mono', monospace; font-size: 7.4pt; word-break: break-all; }
    .box { border: 0.8pt solid #E2D3BA; padding: 10pt 12pt; }
    .note { font-size: 7.4pt; color: #6A5F51; line-height: 1.5; }
</style>
</head>
<body>
    <table>
        <tr>
            <td>
                <div class="brand">ARTHUB</div>
                <div class="brand-tag">Arte · Tecnología · Confianza</div>
            </td>
            <td style="text-align: right;">
                <div class="label">Certificado N.º</div>
                <div class="serif" style="font-size: 12pt;">{{ $certificate->number }}</div>
                <div class="muted" style="font-size: 8pt;">Emitido el {{ $certificate->issued_at->timezone('America/Lima')->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    <h1>Certificado de registro de obra</h1>
    <p class="muted" style="margin: 0 0 16pt;">
        La obra descrita está registrada en ArtHub con el código <strong class="gold">{{ $data['codigo'] }}</strong>
        y su expediente documental fue revisado por el equipo de ArtHub.
    </p>

    <table>
        <tr>
            @if ($image)
                <td style="width: 44%; padding-right: 14pt;">
                    <img src="{{ $image }}" class="photo" alt="">
                </td>
            @endif
            <td>
                <div class="title">{{ $data['titulo'] }}</div>
                <div class="muted" style="margin-bottom: 8pt;">{{ $data['artista'] }}</div>
                <table class="data">
                    <tr><td class="k">Código</td><td>{{ $data['codigo'] }}</td></tr>
                    @if ($data['tecnica'])<tr><td class="k">Técnica</td><td>{{ $data['tecnica'] }}</td></tr>@endif
                    @if ($data['soporte'])<tr><td class="k">Soporte</td><td>{{ $data['soporte'] }}</td></tr>@endif
                    @if ($data['alto_cm'] && $data['ancho_cm'])
                        <tr><td class="k">Medidas</td><td>{{ $data['alto_cm'] }} × {{ $data['ancho_cm'] }}{{ $data['profundidad_cm'] ? ' × '.$data['profundidad_cm'] : '' }} cm</td></tr>
                    @endif
                    @if ($data['anio'])<tr><td class="k">Año</td><td>{{ $data['anio'] }}</td></tr>@endif
                </table>
            </td>
        </tr>
    </table>

    @if ($documents)
        <div class="label" style="margin: 18pt 0 4pt;">Documentos del expediente incluidos</div>
        <table class="docs">
            @foreach ($documents as $doc)
                <tr>
                    <td style="width: 46%;">{{ $doc['titulo'] }} <span class="muted">({{ $categoryLabels[$doc['categoria']] ?? $doc['categoria'] }}, v{{ $doc['version'] }})</span></td>
                    <td class="hash muted">SHA-256 {{ $doc['sha256'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <table class="box" style="margin-top: 20pt;">
        <tr>
            <td style="width: 34mm;"><img src="{{ $qr }}" style="width: 30mm; height: 30mm;" alt=""></td>
            <td style="padding-left: 10pt;">
                <div class="label">Verificación</div>
                <p style="margin: 3pt 0 8pt;">
                    Escanee el código o visite<br>
                    <span class="gold">{{ $verifyUrl }}</span><br>
                    para consultar el estado de este certificado.
                </p>
                <div class="label">Huella digital de los datos certificados</div>
                <div class="hash">SHA-256 {{ $certificate->data_sha256 }}</div>
            </td>
        </tr>
    </table>

    <p class="note" style="margin-top: 16pt;">
        Este certificado acredita el registro de la obra y la revisión de su expediente en ArtHub; no constituye un peritaje de autenticidad.
        La huella digital de este archivo PDF se publica en el portal de verificación: si el archivo fue modificado, no coincidirá.
        Un certificado anulado o reemplazado deja de ser válido; su estado actual siempre se consulta en el portal.
    </p>
</body>
</html>
