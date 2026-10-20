<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Discos de almacenamiento
    |--------------------------------------------------------------------------
    |
    | media: imágenes públicas (obras, fotos de artistas). Debe tener URL pública.
    | documents: expediente documental (§9). Privado: solo se descarga tras
    | comprobar permisos (en R2, con un enlace firmado de pocos minutos).
    |
    | En local se usan los discos "public" y "local". En producción, los discos
    | del servidor se pierden en cada deploy: usar r2_media y r2_documents.
    |
    */

    'disks' => [
        'media' => env('MEDIA_DISK', 'public'),
        'documents' => env('DOCUMENTS_DISK', 'local'),
    ],

    // Minutos de validez del enlace firmado de un documento en R2.
    'document_link_minutes' => (int) env('DOCUMENT_LINK_MINUTES', 5),

];
