-- =============================================================================
--  Arthub · Supabase / PostgreSQL dump
--  Generado desde SQLite el 2026-05-20
--  Pegar en: Supabase Dashboard → SQL Editor → New query → Run
-- =============================================================================

-- Desactivar FK durante la carga masiva
SET session_replication_role = 'replica';

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: users
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "users" CASCADE;

CREATE TABLE "users" (
  "id"                BIGSERIAL PRIMARY KEY,
  "name"              VARCHAR(255)  NOT NULL,
  "email"             VARCHAR(255)  NOT NULL,
  "email_verified_at" TIMESTAMPTZ,
  "password"          VARCHAR(255)  NOT NULL,
  "role"              VARCHAR(20)   NOT NULL DEFAULT 'investor'
                        CHECK ("role" IN ('admin', 'investor')),
  "wallet_address"    VARCHAR(255),
  "remember_token"    VARCHAR(100),
  "created_at"        TIMESTAMPTZ,
  "updated_at"        TIMESTAMPTZ,
  CONSTRAINT "users_email_unique" UNIQUE ("email")
);

INSERT INTO "users" ("id","name","email","email_verified_at","password","role","wallet_address","remember_token","created_at","updated_at") VALUES
(1,  'Demo',                          'demo@arthub.test',                    NULL, '$2y$12$q3ifNNLruyhYyGXRgJ1m4.1jIbefPzqwL9858sC7Bqmaql1Cn1TcS', 'investor', NULL,                                        NULL, '2026-05-20 15:06:20+00', '2026-05-20 16:19:45+00'),
(2,  'Jaime Antonio Rodriguez Phillco','jrodriguezphillco@gmail.com',         NULL, '$2y$12$3Lg174EYOz0Nr21TLON.m.3ba8NHTAeTBQHqHF0YJvdWUfzwXp8.q', 'investor', NULL,                                        NULL, '2026-05-20 15:07:49+00', '2026-05-20 16:19:32+00'),
(3,  'Administrador Arthub',          'admin@arthub.pe',                     NULL, '$2y$12$HbuPLRZC0FTwspyLxdLGTuLMCQgwg4j0wtvqiEJxCEWt6EvWICAA2', 'admin',    NULL,                                        NULL, '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(4,  'Carla Inversionista',           'investor@arthub.pe',                  NULL, '$2y$12$Pky1Pr/KY5VcCLnC.X4bjOZF/3bUohu/Gv0NQso8/SsK5z7O62J5e', 'investor', '0x9C2A8F77D1B4E5A60F2c2A1B7CB6A0BD13E4F112', NULL, '2026-05-20 15:11:18+00', '2026-05-20 16:19:13+00'),
(5,  'Mateo Coleccionista',           'collector@arthub.pe',                 NULL, '$2y$12$hr9a/D2KKXgSqFgKAtY5bOuATQF2R7DVTs7Y3Ncn0ADWkMCv7F5B.', 'investor', '0x7F11C3B92E9AE5631F8C0D5b81e7d442Aa12B0Ee', NULL, '2026-05-20 15:11:18+00', '2026-05-20 16:19:14+00'),
(6,  'Carla Montoya Quispe',          'carla.montoya@arthub.pe',             NULL, '$2y$12$2NmdcIbJehoCP.WBbfs0C.2Zfqxr53jUNG63LPtbpklpjreOcaboa', 'investor', '0x9C2A8F77D1B4E5A60F2c2A1B7CB6A0BD13E4F112', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(7,  'Mateo Coleccionista',           'mateo@arthub.pe',                     NULL, '$2y$12$y5YiTzQs./7oOg.202/0DeZoxmvDfG6sBwVZlF/75nQMKnS3f905S', 'investor', '0x7F11C3B92E9AE5631F8C0D5b81e7d442Aa12B0Ee', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(8,  'Sofia Bergmann',                'sofia.bergmann@gallery-zurich.ch',    NULL, '$2y$12$dO9NZa5Okqyf17buGrIDCeyppsgGk7by48998V/xH08yqIfi94T/2',  'investor', '0xA4c0D17F8bE2931C05Ab77d5E3c0B4A18FF92D3c', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(9,  'Alejandro Ruiz Vargas',         'alejandro.ruiz@fondo-capital.mx',     NULL, '$2y$12$bc5kEjymLp2jPDE1HbCK7OTsjnzsPzWRHrDqK75h/2GxnNPxVMOJG', 'investor', '0xB81e7D3c2F44A0c55E9CB6A17D8F20B3C4E5F6A7', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(10, 'Priya Nair',                    'priya.nair@artfund-singapore.sg',     NULL, '$2y$12$cN6B6/p9YiUzg.Hd7Waalep6CBtifZjRIv1LalRcLUErD5jh75r/6', 'investor', '0xC92bA4E31D7F5B6C8E0A9B2C3D4E5F6A7B8C9D0E', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(11, 'Thomas Leclerc',                'thomas.leclerc@patrimoine-paris.fr',  NULL, '$2y$12$ZN524JIENATI70MMVRvumOdiFvpaWJXlwLg2VxoC2k3zI25iU8nf6',  'investor', '0xD03cB5F42E8A7C9D0E1F2A3B4C5D6E7F8A9B0C1D', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(12, 'Isabella Ferretti',             'i.ferretti@collezioni-roma.it',       NULL, '$2y$12$jNZnIUcdYlX5nN2oTKTjPeRE45/KDPdiWyGoCBXsZBtMlN4Ll.ZL.', 'investor', NULL,                                        NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(13, 'James Whitmore',                'j.whitmore@miami-arts.us',            NULL, '$2y$12$BC727K0I2CWHmdjhGYJbreDvV8QkdK75.egdTzAs8EuILHLFQ8IAy',  'investor', '0xE14dC6A53F9B8D0E2F3A4B5C6D7E8F9A0B1C2D3E', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(14, 'Lin Wei',                       'lin.wei@artventure-hk.com',           NULL, '$2y$12$J8oCn/dJEKkP/uDg5qHU9uE0rVxpQoip8NlcRahiYZwiI2/P.OjSy',  'investor', '0xF25eD7B64A0C9E1F3A4B5C6D7E8F9A0B1C2D3E4F', NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00');

SELECT setval(pg_get_serial_sequence('users','id'), MAX(id)) FROM "users";

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: artworks
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "artworks" CASCADE;

CREATE TABLE "artworks" (
  "id"           BIGSERIAL PRIMARY KEY,
  "title"        VARCHAR(255)    NOT NULL,
  "artist_name"  VARCHAR(255)    NOT NULL,
  "price"        NUMERIC(12, 2)  NOT NULL,
  "description"  TEXT            NOT NULL,
  "image_url"    VARCHAR(2048)   NOT NULL,
  "model_3d_url" VARCHAR(2048),
  "status"       VARCHAR(20)     NOT NULL DEFAULT 'available'
                   CHECK ("status" IN ('available', 'sold')),
  "created_at"   TIMESTAMPTZ,
  "updated_at"   TIMESTAMPTZ
);

INSERT INTO "artworks" ("id","title","artist_name","price","description","image_url","model_3d_url","status","created_at","updated_at") VALUES
(1,  'Procesion del Senor de los Temblores',  'Mariano Fuentes Lira',                   18500.00, 'Oleo sobre lienzo de gran formato que retrata la procesion central del Cusco con detalle minucioso en los mantos bordados y la arquitectura colonial de la Plaza de Armas. Obra de referencia del barroco andino del siglo XX.',                                                                                'https://images.unsplash.com/photo-1578926375605-eaf7559b1458?w=1200', NULL,                                                             'available', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(2,  'Virgen del Carmen de Paucartambo',      'Edilberto Merida',                        12750.00, 'Pieza de la escuela cusquena contemporanea inspirada en la festividad mariana de Paucartambo. Tecnica mixta con pan de oro y pigmentos minerales. Certificada por el Ministerio de Cultura del Peru.',                                                                                                       'https://images.unsplash.com/photo-1582555172866-f73bb12a2ab3?w=1200', 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',    'available', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(3,  'Retablo de Ollantaytambo',              'Hilario Mendivil',                        22300.00, 'Retablo en madera policromada que recrea escenas del Valle Sagrado y la cosmovision andina. Trabajo en yeso de papa con acabado en esmalte. Pieza unica de alto valor etnografico.',                                                                                                                         'https://images.unsplash.com/photo-1577083552431-6e5fd01988ec?w=1200', NULL,                                                             'available', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(4,  'Pachamama bajo la Cruz del Sur',        'Maximiliana Palomino',                     9800.00, 'Tecnica mixta sobre tabla con pigmentos minerales del Valle. Sintesis entre lo prehispanico y lo barroco andino. La obra explora la dualidad cosmologica inca a traves de simbolos geometricos y figuras zoomorfas.',                                                                                       'https://images.unsplash.com/photo-1549887534-1541e9326642?w=1200', NULL,                                                              'available', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(5,  'Mercado de San Pedro',                  'Antonio Olave',                            7600.00, 'Escena costumbrista que documenta el comercio tradicional del Cusco a inicios del siglo XX.',                                                                                                                                                                                                               'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=1200', NULL,                                                             'available', '2026-05-20 15:11:18+00', '2026-05-20 15:11:18+00'),
(6,  'Inti Raymi, ofrenda solar',             'Mariano Fuentes Lira',                    31200.00, 'Pieza monumental 180x240 cm que captura la ceremonia del Inti Raymi en la Fortaleza de Sacsayhuaman con simbolismo incaico y pinceladas neobarrocas. Presentada en la Bienal de Arte Andino 2019.',                                                                                                        'https://images.unsplash.com/photo-1577720643272-265f09367456?w=1200', 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',    'available', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(7,  'Choquequirao en la bruma',              'Edilberto Merida',                        14400.00, 'Paisaje contemplativo del complejo arqueologico de Choquequirao visto desde el mirador de Capuliyoc. Oleo sobre tela de algodon andino con imprimatura ocre. Atmosfera niebla matinal capturada in situ.',                                                                                                  'https://images.unsplash.com/photo-1526481280699-c40e3d68fdf2?w=1200', NULL,                                                             'available', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(8,  'Tejedoras de Chinchero',                'Maximiliana Palomino',                     8900.00, 'Retrato colectivo de las maestras tejedoras de Chinchero, custodias del telar de cintura ancestral. Tecnica acuarela sobre papel Fabriano 300g con trazos en tinta ferrogalica.',                                                                                                                          'https://images.unsplash.com/photo-1604079628040-94301bb21b91?w=1200', NULL,                                                             'available', '2026-05-20 15:11:18+00', '2026-05-20 15:11:18+00'),
(9,  'Mercado de San Pedro al amanecer',      'Antonio Olave Palomino',                   7600.00, 'Escena costumbrista que documenta el comercio tradicional del Cusco a inicios del siglo XX. Oleo sobre tela con influencia de la pintura academica limena y la tradicion narrativa andina.',                                                                                                                'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?w=1200', NULL,                                                             'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(10, 'La Ultima Cena Andina',                 'Marcos Zapata (replica documentada)',     45000.00, 'Replica documentada y certificada de la celebre obra de la Catedral del Cusco que muestra la Ultima Cena con elementos gastronomicos andinos. Ejecutada con criterios de alta fidelidad cromatica y compositiva.',                                                                                         'https://images.unsplash.com/photo-1561214115-f2f134cc4912?w=1200', 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',     'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(11, 'Nevado Ausangate en verano',            'Victor Delfin Quiroga',                   16800.00, 'Vista del Ausangate a 6384 metros durante el solsticio de diciembre. Pintura al oleo con espatula sobre tela gruesa. Paleta fria y luminosa que evoca la experiencia mistica del Qoyllur Riti.',                                                                                                           'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200', NULL,                                                             'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(12, 'Danzantes de Tijeras',                  'Joaquin Lopez Antay',                     11200.00, 'Representacion de los danzantes de tijeras de Ayacucho en plena competencia ritual. Obra en acrilico sobre tela con superposicion de capas veladas. Patrimonio Cultural de la Nacion declarado por la UNESCO.',                                                                                            'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=1200', NULL,                                                              'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(13, 'Quechua madre e hijo',                  'Hilario Mendivil',                        19500.00, 'Escultura en yeso de papa pintada al frio que representa la iconografia maternal andina. Serie Nacimiento del mundo, pieza central. Cuello elongado caracteristico de la escuela mendivil reconocida internacionalmente.',                                                                                   'https://images.unsplash.com/photo-1555685812-4b943f1cb0eb?w=1200', 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',     'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(14, 'Plaza Mayor del Cusco 1890',            'Juan de Espinosa Medrano (atribuida)',    52000.00, 'Obra atribuida documentalmente al periodo colonial tardio. Vista panoramica de la Plaza Mayor del Cusco con fuente central y calesas. Oleo sobre lienzo encolado en tabla de cedro andino.',                                                                                                                'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=1200', NULL,                                                              'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(15, 'Chakana cosmica',                       'Maximiliana Palomino',                     6400.00, 'Serie geometrica sobre la cruz andina como eje del universo. Tecnica mixta, oro fino y arcilla cocida sobre panel de madera. Pieza de coleccion de formato reducido 40x40 cm.',                                                                                                                            'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=1200', NULL,                                                             'available', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(16, 'Rio Urubamba en agosto',                'Victor Delfin Quiroga',                   13300.00, 'Vista del Rio Urubamba a su paso por el Valle Sagrado en epoca de estiaje. Impresionismo andino con influencia de la escuela de Barbizon. Colores ocres y verdes que evocan la temporada seca del altiplano.',                                                                                             'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200', NULL,                                                             'sold',      '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00');

SELECT setval(pg_get_serial_sequence('artworks','id'), MAX(id)) FROM "artworks";

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: certificates
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "certificates" CASCADE;

CREATE TABLE "certificates" (
  "id"               BIGSERIAL PRIMARY KEY,
  "artwork_id"       BIGINT          NOT NULL,
  "biometric_hash"   VARCHAR(255)    NOT NULL,
  "match_percentage" NUMERIC(5, 2)   NOT NULL,
  "blockchain_tx_id" VARCHAR(255),
  "contract_address" VARCHAR(255),
  "created_at"       TIMESTAMPTZ,
  "updated_at"       TIMESTAMPTZ,
  CONSTRAINT "certificates_artwork_id_fkey"
    FOREIGN KEY ("artwork_id") REFERENCES "artworks"("id") ON DELETE CASCADE
);

INSERT INTO "certificates" ("id","artwork_id","biometric_hash","match_percentage","blockchain_tx_id","contract_address","created_at","updated_at") VALUES
(1,  1,  '7c5fdaa3209bc97fc36927114cbe1bd4c58d9eb8806a277d3fafd5d708ba982a', 98.42, '0x663f89643e76bb36e2834602b44cce2f9e14bf72bfa6187ccb4f34ace76b62fe', '0x4d916276999f85159884d8733ff7205b1a8d8ec5', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(2,  2,  '2b5b32d0108c9b742633f3ab49d42847371b10affea386a6651e637694ee6330', 96.10, '0xca3fe065fda9a4d1b2e85f2f709aeccdc17e882b2507dbf53d52f22d21b37939', '0xb3409293bba417dec4169a396110684209d549a7', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(3,  3,  '0d2c0750dff1be19baed90a9c4320fe12ab239e8d5af5e39c2369fdb3297238f', 99.05, '0xc4c57b026c272a95911a55cdd88ce3f3b7ff8c52cae69c193860b46d763f7af3', '0xe5afdb1c47396e47fe02a6e21b7a374fba1ec070', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(4,  4,  '5a8b0d596582ccbc224eb1e82a73e0e6ecac471eb1ea6527dec84243ba2e8fea', 94.78, NULL, NULL, '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(5,  5,  'c5f760c5d08361f1046956f4d30baa3ea092b350c83e70f1bc655f26251c1630', 92.30, NULL, NULL, '2026-05-20 15:11:18+00', '2026-05-20 15:11:18+00'),
(6,  6,  '99e870e89f2efe017723f7929acec4e236366b003c81e857a3b8a3999b7d5e75', 97.65, '0x3a663ce5e81de93385e98e2ef830ac1e1a380ec37bc1afa7eba85ea63930e705', '0xcda376334b2d1be21f8ea966c2d58d6f7ac01e56', '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(7,  7,  '6f92ca90ed3512da71d9f9f959262f5f8277b96816b94ec6e55ceca22b8d3293', 95.20, NULL, NULL, '2026-05-20 15:11:18+00', '2026-05-20 16:26:15+00'),
(8,  8,  '83cbd8a775acc464c9780bd2882a5a2af1aa36f5d827707036f9c450da6e4cec', 93.55, NULL, NULL, '2026-05-20 15:11:18+00', '2026-05-20 15:11:18+00'),
(9,  9,  '1a62cfd9b6c520296c29605b6324f185b56367d4264398ee7bd5b03072c9a3f0', 92.30, NULL, NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(10, 10, '22d0f5d3df3bf8b2c66745c780a143516b031b6c0c68f6f45ed14d8e21a0de08', 99.87, '0x8903e92a14a866103930cdc5b5f07c543eec3d08b8b3a7d1a852284f3443049b', '0x58e4469e452ec3e9dcc075cf1792f20a254467d1', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(11, 11, '11f0295b49469b073f101bca39841ebd7031843986512ff1a058e5c5da8ae43c', 91.40, NULL, NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(12, 12, '56c095879ff683482178d2e51175ccad80c0f949279405fe808379f1c85c0554', 96.88, '0xb83af0ab03c06479e7485bd94d1ee4dd7bd3375e890c60b0149ec4635c8dc96c', '0x829d65b002dee0ab9094788d0942c84cdacf87be', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(13, 13, 'e1cea295fb6eb7030f894d35296e72e3d56d37ed01a3155dd95ea519b030cd1a', 98.10, '0xab32f876837cda0add1f0bb52a91f3fb7c036e999437f9eb4570a6e11b679757', '0x3a70e542695f3e3f5b2df958bac5411c0872755c', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(14, 14, '46756944bd64c4b230e657eb433185bed0ebed2a96a905e73bde8f1b55c95487', 97.33, '0x9aca1b490ca0ea8bd13327b68fb428d8b54210708db743b0fa1ddb9290ae9ee1', '0xe904c76bcbde2e095ba0e06d30b7ef46b78601d2', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(15, 15, 'ab0c4bd919908c7df3698d21ce53e135c3e03d9e390295af7324a0a216e97b67', 89.75, NULL, NULL, '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00'),
(16, 16, '1e98805e6232906cf37ae5acaf850beb5e3a5050dd6123588e486b5a94f64636', 95.60, '0x1dfb63d0a1b1de28d38ac6e03b66f867634cbb2d3c3a56dac9f586466930f0a6', '0x5b8d1a45f037671b60d24a8079d8144925caed5b', '2026-05-20 16:26:15+00', '2026-05-20 16:26:15+00');

SELECT setval(pg_get_serial_sequence('certificates','id'), MAX(id)) FROM "certificates";

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: personal_access_tokens  (Laravel Sanctum)
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "personal_access_tokens" CASCADE;

CREATE TABLE "personal_access_tokens" (
  "id"             BIGSERIAL PRIMARY KEY,
  "tokenable_type" VARCHAR(255)  NOT NULL,
  "tokenable_id"   BIGINT        NOT NULL,
  "name"           TEXT          NOT NULL,
  "token"          VARCHAR(64)   NOT NULL,
  "abilities"      TEXT,
  "last_used_at"   TIMESTAMPTZ,
  "expires_at"     TIMESTAMPTZ,
  "created_at"     TIMESTAMPTZ,
  "updated_at"     TIMESTAMPTZ,
  CONSTRAINT "personal_access_tokens_token_unique" UNIQUE ("token")
);

CREATE INDEX "pat_tokenable_idx"
  ON "personal_access_tokens" ("tokenable_type", "tokenable_id");
CREATE INDEX "pat_expires_at_idx"
  ON "personal_access_tokens" ("expires_at");

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: migrations  (control de versiones Laravel)
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "migrations" CASCADE;

CREATE TABLE "migrations" (
  "id"        SERIAL PRIMARY KEY,
  "migration" VARCHAR(255) NOT NULL,
  "batch"     INTEGER      NOT NULL
);

INSERT INTO "migrations" ("id","migration","batch") VALUES
(1, '0001_01_01_000000_create_users_table',                  1),
(2, '0001_01_01_000001_create_cache_table',                  1),
(3, '0001_01_01_000002_create_jobs_table',                   1),
(4, '2026_05_19_000001_create_artworks_table',               1),
(5, '2026_05_19_000002_create_certificates_table',           1),
(6, '2026_05_19_211317_create_personal_access_tokens_table', 1);

SELECT setval(pg_get_serial_sequence('migrations','id'), MAX(id)) FROM "migrations";

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: cache
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "cache" CASCADE;

CREATE TABLE "cache" (
  "key"        VARCHAR(255) NOT NULL,
  "value"      TEXT         NOT NULL,
  "expiration" INTEGER      NOT NULL,
  PRIMARY KEY ("key")
);

DROP TABLE IF EXISTS "cache_locks" CASCADE;

CREATE TABLE "cache_locks" (
  "key"        VARCHAR(255) NOT NULL,
  "owner"      VARCHAR(255) NOT NULL,
  "expiration" INTEGER      NOT NULL,
  PRIMARY KEY ("key")
);

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: jobs / failed_jobs / job_batches
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "jobs" CASCADE;

CREATE TABLE "jobs" (
  "id"           BIGSERIAL PRIMARY KEY,
  "queue"        VARCHAR(255) NOT NULL,
  "payload"      TEXT         NOT NULL,
  "attempts"     SMALLINT     NOT NULL,
  "reserved_at"  INTEGER,
  "available_at" INTEGER      NOT NULL,
  "created_at"   INTEGER      NOT NULL
);

CREATE INDEX "jobs_queue_index" ON "jobs" ("queue");

DROP TABLE IF EXISTS "failed_jobs" CASCADE;

CREATE TABLE "failed_jobs" (
  "id"         BIGSERIAL PRIMARY KEY,
  "uuid"       VARCHAR(255) NOT NULL,
  "connection" TEXT         NOT NULL,
  "queue"      TEXT         NOT NULL,
  "payload"    TEXT         NOT NULL,
  "exception"  TEXT         NOT NULL,
  "failed_at"  TIMESTAMPTZ  NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid")
);

DROP TABLE IF EXISTS "job_batches" CASCADE;

CREATE TABLE "job_batches" (
  "id"             VARCHAR(255) NOT NULL,
  "name"           VARCHAR(255) NOT NULL,
  "total_jobs"     INTEGER      NOT NULL,
  "pending_jobs"   INTEGER      NOT NULL,
  "failed_jobs"    INTEGER      NOT NULL,
  "failed_job_ids" TEXT         NOT NULL,
  "options"        TEXT,
  "cancelled_at"   INTEGER,
  "created_at"     INTEGER      NOT NULL,
  "finished_at"    INTEGER,
  PRIMARY KEY ("id")
);

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: sessions
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "sessions" CASCADE;

CREATE TABLE "sessions" (
  "id"            VARCHAR(255) NOT NULL PRIMARY KEY,
  "user_id"       BIGINT,
  "ip_address"    VARCHAR(45),
  "user_agent"    TEXT,
  "payload"       TEXT         NOT NULL,
  "last_activity" INTEGER      NOT NULL
);

CREATE INDEX "sessions_user_id_index"       ON "sessions" ("user_id");
CREATE INDEX "sessions_last_activity_index" ON "sessions" ("last_activity");

-- ─────────────────────────────────────────────────────────────────────────────
--  TABLA: password_reset_tokens
-- ─────────────────────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS "password_reset_tokens" CASCADE;

CREATE TABLE "password_reset_tokens" (
  "email"      VARCHAR(255) NOT NULL PRIMARY KEY,
  "token"      VARCHAR(255) NOT NULL,
  "created_at" TIMESTAMPTZ
);

-- ─────────────────────────────────────────────────────────────────────────────
--  Reactivar FK
-- ─────────────────────────────────────────────────────────────────────────────
SET session_replication_role = 'origin';

-- =============================================================================
--  FIN DEL SCRIPT
--  Tablas importadas: users (14 filas), artworks (16 filas),
--  certificates (16 filas), + tablas de sistema Laravel vacias
-- =============================================================================
