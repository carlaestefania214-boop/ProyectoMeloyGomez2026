-- ============================================================
--  VELINDA — Base de datos completa
--  Archivo: velinda_db.sql
--  Ejecutar en phpMyAdmin o MySQL desde cPanel
-- ============================================================

CREATE DATABASE IF NOT EXISTS `icei_42091155`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `icei_42091155`;

-- ─────────────────────────────────
--  TABLA: categorias
-- ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `categorias` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`      VARCHAR(80)  NOT NULL,
  `slug`        VARCHAR(80)  NOT NULL UNIQUE,
  `icono`       VARCHAR(10)  DEFAULT '👗',
  `activa`      TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────
--  TABLA: productos
-- ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `productos` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`          VARCHAR(180)        NOT NULL,
  `descripcion`     TEXT,
  `categoria_id`    INT UNSIGNED        NOT NULL,
  `precio`          DECIMAL(10,2)       NOT NULL,
  `precio_oferta`   DECIMAL(10,2)       DEFAULT NULL,
  `stock`           INT UNSIGNED        NOT NULL DEFAULT 0,
  `talles`          VARCHAR(100)        DEFAULT NULL,
  `colores`         VARCHAR(120)        DEFAULT NULL,
  `imagen_url`      VARCHAR(255)        DEFAULT NULL,
  `imagen2_url`     VARCHAR(255)        DEFAULT NULL,
  `imagen3_url`     VARCHAR(255)        DEFAULT NULL,
  `destacado`       TINYINT(1)          NOT NULL DEFAULT 0,
  `nuevo`           TINYINT(1)          NOT NULL DEFAULT 0,
  `activo`          TINYINT(1)          NOT NULL DEFAULT 1,
  `ventas`          INT UNSIGNED        NOT NULL DEFAULT 0,
  `fecha_carga`     DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT,
  INDEX `idx_cat`       (`categoria_id`),
  INDEX `idx_activo`    (`activo`),
  INDEX `idx_destacado` (`destacado`),
  INDEX `idx_precio`    (`precio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────
--  TABLA: clientes
-- ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `clientes` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre`          VARCHAR(100)  NOT NULL,
  `apellido`        VARCHAR(100)  NOT NULL,
  `email`           VARCHAR(150)  NOT NULL UNIQUE,
  `telefono`        VARCHAR(30)   DEFAULT NULL,
  `direccion`       VARCHAR(255)  DEFAULT NULL,
  `ciudad`          VARCHAR(100)  DEFAULT NULL,
  `provincia`       VARCHAR(100)  DEFAULT NULL,
  `activo`          TINYINT(1)    NOT NULL DEFAULT 1,
  `fecha_registro`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email`  (`email`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────
--  TABLA: opiniones
-- ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `opiniones` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `producto_id`     INT UNSIGNED  NOT NULL,
  `cliente_nombre`  VARCHAR(120)  NOT NULL,
  `cliente_email`   VARCHAR(150)  DEFAULT NULL,
  `estrellas`       TINYINT       NOT NULL CHECK (`estrellas` BETWEEN 1 AND 5),
  `comentario`      TEXT          NOT NULL,
  `aprobada`        TINYINT(1)    NOT NULL DEFAULT 0,
  `fecha`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE CASCADE,
  INDEX `idx_producto`  (`producto_id`),
  INDEX `idx_aprobada`  (`aprobada`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────
--  TABLA: compras
-- ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `compras` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `codigo`          VARCHAR(20)   NOT NULL UNIQUE,
  `cliente_nombre`  VARCHAR(120)  NOT NULL,
  `cliente_email`   VARCHAR(150)  NOT NULL,
  `cliente_tel`     VARCHAR(40)   DEFAULT NULL,
  `cliente_dir`     VARCHAR(255)  DEFAULT NULL,
  `total`           DECIMAL(12,2) NOT NULL,
  `estado`          ENUM('pendiente','confirmado','enviado','entregado','cancelado')
                                  NOT NULL DEFAULT 'pendiente',
  `metodo_pago`     VARCHAR(60)   DEFAULT NULL,
  `notas`           TEXT          DEFAULT NULL,
  `fecha`           DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_estado`  (`estado`),
  INDEX `idx_email`   (`cliente_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────
--  TABLA: compra_items
-- ─────────────────────────────────
CREATE TABLE IF NOT EXISTS `compra_items` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `compra_id`   INT UNSIGNED    NOT NULL,
  `producto_id` INT UNSIGNED    NOT NULL,
  `nombre`      VARCHAR(180)    NOT NULL,
  `precio`      DECIMAL(10,2)   NOT NULL,
  `cantidad`    INT UNSIGNED    NOT NULL DEFAULT 1,
  `talle`       VARCHAR(20)     DEFAULT NULL,
  `color`       VARCHAR(40)     DEFAULT NULL,
  FOREIGN KEY (`compra_id`)   REFERENCES `compras`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `productos`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────
--  DATOS INICIALES — categorías
-- ─────────────────────────────────
INSERT INTO `categorias` (`nombre`, `slug`, `icono`) VALUES
('Vestidos',   'vestidos',   '👗'),
('Tops',       'tops',       '👕'),
('Blazers',    'blazers',    '🧥'),
('Pantalones', 'pantalones', '👖'),
('Faldas',     'faldas',     '🩱'),
('Accesorios', 'accesorios', '👜');

-- ─────────────────────────────────
--  DATOS INICIALES — productos
-- ─────────────────────────────────
INSERT INTO `productos`
  (`nombre`,`descripcion`,`categoria_id`,`precio`,`precio_oferta`,`stock`,`talles`,`colores`,`destacado`,`nuevo`,`ventas`)
VALUES
('Vestido Maxi Floral Claudia','Vestido largo con estampado floral, tela liviana ideal para verano. Elástico en cintura.',  1, 18900, 14900, 15, 'S,M,L,XL',   'Floral multicolor,Azul',     1, 1, 42),
('Vestido Midi Lino Crudo',    'Vestido midi de lino natural 100%, suelto y muy fresco. Escote en V.',                     1, 21000, NULL,  12, 'S,M,L',      'Crudo,Blanco roto',          1, 0, 28),
('Blazer Oversize Milano',     'Blazer de corte oversize con forro interior, botón dorado. Ideal para look casual-formal.', 3, 22500, NULL,   8, 'S,M,L,XL',   'Camel,Negro,Gris',           1, 1, 19),
('Camisa Seda Rosa',           'Camisa confeccionada en tela tipo seda, manga larga, escote en V con botones nácar.',       2, 14700, 10290, 20, 'XS,S,M,L',   'Rosa,Blanco,Celeste',        0, 1, 55),
('Top Crop Negro',             'Top corto de algodón 100%, espalda descubierta con tiras cruzadas.',                       2,  9800,  7500, 25, 'XS,S,M,L',   'Negro,Blanco,Nude',          1, 0, 73),
('Pantalón Palazzo Beige',     'Pantalón de pierna ancha, tiro alto. Cintura elástica, caída impecable.',                  4, 16200, NULL,  10, 'S,M,L,XL',   'Beige,Negro,Blanco',         0, 0, 31),
('Pantalón Cargo Verde',       'Pantalón cargo con bolsillos laterales, estilo urbano y cómodo.',                          4, 17500, 13500,  9, 'S,M,L,XL',   'Verde militar,Negro',        0, 1, 18),
('Falda Mini Cuero Eco',       'Falda mini en cuero ecológico, cierre lateral, look rockero y moderno.',                   5, 13200,  9900, 14, 'XS,S,M,L',   'Negro,Vino,Camel',           1, 1, 38),
('Cartera Clutch Dorada',      'Cartera de mano ideal para eventos, cierre magnético dorado. Cadena removible.',           6,  8500, NULL,   8, 'Única',      'Dorado,Plateado,Negro',      0, 0, 22),
('Falda Midi Plisada',         'Falda midi plisada en gasa, elástica en cintura. Muy versátil y elegante.',                5, 15800, 11500, 16, 'S,M,L,XL',   'Verde, Rosa, Negro, Camel',  1, 0, 47);

-- ─────────────────────────────────
--  DATOS INICIALES — clientes
-- ─────────────────────────────────
INSERT INTO `clientes` (`nombre`,`apellido`,`email`,`telefono`,`ciudad`,`provincia`) VALUES
('María',     'González',  'maria@email.com',   '3764-111111', 'Posadas',   'Misiones'),
('Laura',     'Fernández', 'laura@email.com',   '3764-222222', 'Oberá',     'Misiones'),
('Sofía',     'Ramírez',   'sofia@email.com',   '3764-333333', 'Apóstoles', 'Misiones'),
('Valentina', 'López',     'vale@email.com',    '3764-444444', 'Eldorado',  'Misiones'),
('Camila',    'Martínez',  'camila@email.com',  '3764-555555', 'Posadas',   'Misiones');

-- ─────────────────────────────────
--  DATOS INICIALES — opiniones
-- ─────────────────────────────────
INSERT INTO `opiniones` (`producto_id`,`cliente_nombre`,`estrellas`,`comentario`,`aprobada`) VALUES
(1, 'María G.',    5, '¡Hermoso vestido! La tela es súper liviana y el estampado muy lindo. Llegó rápido y bien embalado.', 1),
(1, 'Laura F.',    4, 'Me encantó el diseño, queda muy bien. El talle M me quedó perfecto. Lo recomiendo.',                1),
(3, 'Valentina L.',5, 'El blazer es de muy buena calidad. Lo usé en el trabajo y recibí muchos elogios.',                  1),
(5, 'Sofía R.',    5, 'El top es hermoso y muy cómodo. Lo compré en negro y ya quiero comprarlo en blanco también!',        1),
(8, 'Camila M.',   4, 'La falda queda increíble. El cuero eco es muy bonito y resistente. Talle fiel.',                    1),
(4, 'Ana P.',      5, 'La camisa es preciosa, la tela cae perfecta. Pedí talle M y quedó exacto.',                         1),
(6, 'Luciana B.',  4, 'El pantalón palazzo es muy cómodo y elegante. Perfecto para la oficina.',                           1);

-- ─────────────────────────────────
--  DATOS INICIALES — compras
-- ─────────────────────────────────
INSERT INTO `compras`
  (`codigo`,`cliente_nombre`,`cliente_email`,`cliente_tel`,`total`,`estado`,`metodo_pago`)
VALUES
('VEL-001','María González',   'maria@email.com', '3764-111111', 43800, 'entregado', 'Transferencia'),
('VEL-002','Laura Fernández',  'laura@email.com', '3764-222222', 22500, 'enviado',   'Mercado Pago'),
('VEL-003','Sofía Ramírez',    'sofia@email.com', '3764-333333', 14900, 'confirmado','Efectivo'),
('VEL-004','Valentina López',  'vale@email.com',  '3764-444444', 31500, 'pendiente', 'Transferencia');

INSERT INTO `compra_items` (`compra_id`,`producto_id`,`nombre`,`precio`,`cantidad`,`talle`,`color`) VALUES
(1, 1, 'Vestido Maxi Floral Claudia', 14900, 1, 'M',  'Floral multicolor'),
(1, 3, 'Blazer Oversize Milano',      22500, 1, 'L',  'Camel'),
(1, 9, 'Cartera Clutch Dorada',        8500, 1, NULL, 'Dorado'),
(2, 3, 'Blazer Oversize Milano',      22500, 1, 'S',  'Negro'),
(3, 1, 'Vestido Maxi Floral Claudia', 14900, 1, 'S',  'Azul'),
(4, 5, 'Top Crop Negro',               7500, 2, 'XS', 'Negro'),
(4, 6, 'Pantalón Palazzo Beige',      16200, 1, 'S',  'Beige');
