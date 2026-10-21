-- ======================================================
-- ALTERACIONES PARA BIBLIOTECA 3
-- Objetivo:
-- 1) Normalizar AUTORES para relacionarlo con PERSONAS.
-- 2) Crear DETALLE_DEVOLUCION para manejar devoluciones
--    parciales o múltiples de uno o varios libros.
-- 3) Migrar datos previos cuando sea posible.
-- ======================================================

START TRANSACTION;

-- ------------------------------------------------------
-- 1. Respaldar / completar personas a partir de autores
-- ------------------------------------------------------
INSERT INTO personas (nombre, apellido_paterno, apellido_materno)
SELECT a.nombre, a.apellido_paterno, a.apellido_materno
FROM autores a
LEFT JOIN personas p
    ON UPPER(TRIM(p.nombre)) = UPPER(TRIM(a.nombre))
   AND UPPER(TRIM(p.apellido_paterno)) = UPPER(TRIM(a.apellido_paterno))
   AND UPPER(TRIM(COALESCE(p.apellido_materno, ''))) = UPPER(TRIM(COALESCE(a.apellido_materno, '')))
WHERE p.id_persona IS NULL;

-- ------------------------------------------------------
-- 2. Agregar id_persona en autores y enlazar registros
-- ------------------------------------------------------
ALTER TABLE autores
    ADD COLUMN id_persona INT NULL AFTER id_autor;

UPDATE autores a
JOIN personas p
    ON UPPER(TRIM(p.nombre)) = UPPER(TRIM(a.nombre))
   AND UPPER(TRIM(p.apellido_paterno)) = UPPER(TRIM(a.apellido_paterno))
   AND UPPER(TRIM(COALESCE(p.apellido_materno, ''))) = UPPER(TRIM(COALESCE(a.apellido_materno, '')))
SET a.id_persona = p.id_persona
WHERE a.id_persona IS NULL;

ALTER TABLE autores
    MODIFY COLUMN id_persona INT NOT NULL,
    ADD CONSTRAINT uq_autores_id_persona UNIQUE (id_persona),
    ADD CONSTRAINT fk_autores_personas
        FOREIGN KEY (id_persona) REFERENCES personas (id_persona)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

-- Eliminar redundancia de nombres dentro de autores
ALTER TABLE autores
    DROP COLUMN nombre,
    DROP COLUMN apellido_paterno,
    DROP COLUMN apellido_materno;

-- ------------------------------------------------------
-- 3. Crear detalle_devolucion
--    Una devolución puede incluir uno o varios libros.
-- ------------------------------------------------------
CREATE TABLE detalle_devolucion (
    id_detalle_devolucion INT AUTO_INCREMENT PRIMARY KEY,
    id_devolucion INT NOT NULL,
    id_detalle_prestamo INT NOT NULL,
    observaciones VARCHAR(255) NULL,
    CONSTRAINT uq_detalle_devolucion_detalle UNIQUE (id_detalle_prestamo),
    CONSTRAINT fk_detalle_devolucion_devoluciones
        FOREIGN KEY (id_devolucion) REFERENCES devoluciones (id_devolucion)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detalle_devolucion_detalle_prestamo
        FOREIGN KEY (id_detalle_prestamo) REFERENCES detalle_prestamo (id_detalle)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------
-- 4. Migrar devoluciones anteriores
--    Si antes una devolución representaba el préstamo completo,
--    entonces ligamos todos sus detalles.
-- ------------------------------------------------------
INSERT INTO detalle_devolucion (id_devolucion, id_detalle_prestamo, observaciones)
SELECT d.id_devolucion, dp.id_detalle, 'Migrado desde estructura anterior'
FROM devoluciones d
JOIN detalle_prestamo dp ON dp.id_prestamo = d.id_prestamo
LEFT JOIN detalle_devolucion dd ON dd.id_detalle_prestamo = dp.id_detalle
WHERE dd.id_detalle_devolucion IS NULL;

COMMIT;
