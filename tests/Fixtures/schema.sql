CREATE TABLE `asigna_autores` (
`id_asigna_autor` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_libro` INTEGER NOT NULL REFERENCES libros(id_libro) ON DELETE CASCADE,
`id_autor` INTEGER NOT NULL REFERENCES autores(id_autor),
UNIQUE (id_libro, id_autor)
);
CREATE TABLE `asigna_categorias` (
`id_asigna_categoria` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_libro` INTEGER NOT NULL REFERENCES libros(id_libro) ON DELETE CASCADE,
`id_categoria` INTEGER NOT NULL REFERENCES categorias(id_categoria),
UNIQUE (id_libro, id_categoria)
);
CREATE TABLE `asigna_editoriales` (
`id_asigna_editorial` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_libro` INTEGER NOT NULL REFERENCES libros(id_libro) ON DELETE CASCADE,
`id_editorial` INTEGER NOT NULL REFERENCES editoriales(id_editorial),
UNIQUE (id_libro, id_editorial)
);
CREATE TABLE `autores` (`id_autor` INTEGER PRIMARY KEY AUTOINCREMENT, `id_persona` INTEGER NOT NULL UNIQUE REFERENCES personas(id_persona));
CREATE TABLE `bibliotecarios` (
`id_bibliotecario` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_persona` INTEGER NOT NULL REFERENCES personas(id_persona)
);
CREATE TABLE `categorias` (
`id_categoria` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre` varchar(100) NOT NULL
);
CREATE TABLE `cuentas_acceso` (
`id_cuenta` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre_usuario` varchar(60) NOT NULL UNIQUE,
`password` varchar(255) NOT NULL,
`rol` varchar(20) NOT NULL,
`activo` INTEGER NOT NULL DEFAULT 1,
`id_usuario` INTEGER DEFAULT NULL UNIQUE REFERENCES usuarios(id_usuario),
`id_bibliotecario` INTEGER DEFAULT NULL UNIQUE REFERENCES bibliotecarios(id_bibliotecario),
`ultimo_acceso` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL
);
CREATE TABLE `cuenta_permisos` (
`id_permiso` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_cuenta` INTEGER NOT NULL REFERENCES cuentas_acceso(id_cuenta) ON DELETE CASCADE,
`modulo` varchar(40) NOT NULL,
`accion` varchar(12) NOT NULL
);
CREATE TABLE `detalle_devolucion` (
`id_detalle_devolucion` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_devolucion` INTEGER NOT NULL REFERENCES devoluciones(id_devolucion) ON DELETE CASCADE,
`id_detalle_prestamo` INTEGER NOT NULL UNIQUE REFERENCES detalle_prestamo(id_detalle),
`observaciones` varchar(255) DEFAULT NULL
);
CREATE TABLE `detalle_prestamo` (
`id_detalle` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_prestamo` INTEGER NOT NULL REFERENCES prestamos(id_prestamo) ON DELETE CASCADE,
`id_ejemplar` INTEGER NOT NULL REFERENCES ejemplares(id_ejemplar),
UNIQUE (id_prestamo, id_ejemplar)
);
CREATE TABLE `devoluciones` (
`id_devolucion` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_prestamo` INTEGER NOT NULL REFERENCES prestamos(id_prestamo),
`fecha_devolucion` date NOT NULL
);
CREATE TABLE `editoriales` (
`id_editorial` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre` varchar(120) NOT NULL
);
CREATE TABLE `ejemplares` (
`id_ejemplar` INTEGER PRIMARY KEY AUTOINCREMENT,
`codigo_inventario` varchar(40) NOT NULL,
`id_libro` INTEGER NOT NULL REFERENCES libros(id_libro),
`id_ubicacion` INTEGER NOT NULL REFERENCES ubicaciones_libros(id_ubicacion),
`id_estado_ejemplar` INTEGER NOT NULL REFERENCES estados_ejemplar(id_estado_ejemplar)
);
CREATE TABLE `estados_ejemplar` (
`id_estado_ejemplar` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre_estado` varchar(50) NOT NULL
);
CREATE TABLE `estados_usuario` (
`id_estado_usuario` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre_estado_usuario` varchar(50) NOT NULL
);
CREATE TABLE `historial_permisos` (
`id_cambio` INTEGER PRIMARY KEY AUTOINCREMENT,
`actor_id` INTEGER UNSIGNED DEFAULT NULL REFERENCES cuentas_acceso(id_cuenta) ON DELETE SET NULL,
`cuenta_id` INTEGER NOT NULL REFERENCES cuentas_acceso(id_cuenta),
`evento` varchar(40) NOT NULL,
`detalle` longtext DEFAULT NULL CHECK (json_valid(`detalle`)),
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE `libros` (
`id_libro` INTEGER PRIMARY KEY AUTOINCREMENT,
`isbn` varchar(20) NOT NULL,
`titulo` varchar(200) NOT NULL
);
CREATE TABLE `migrations` (
`id` INTEGER UNSIGNED NOT NULL,
`migration` varchar(255) NOT NULL,
`batch` INTEGER NOT NULL
);
CREATE TABLE `personas` (
`id_persona` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre` varchar(80) NOT NULL,
`apellido_paterno` varchar(80) NOT NULL,
`apellido_materno` varchar(80) NOT NULL
);
CREATE TABLE `prestamos` (
`id_prestamo` INTEGER PRIMARY KEY AUTOINCREMENT,
`id_usuario` INTEGER NOT NULL REFERENCES usuarios(id_usuario),
`fecha_prestamo` date NOT NULL,
`fecha_limite` date NOT NULL,
`id_bibliotecario` INTEGER NOT NULL REFERENCES bibliotecarios(id_bibliotecario)
);
CREATE TABLE `ubicaciones_libros` (
`id_ubicacion` INTEGER PRIMARY KEY AUTOINCREMENT,
`nombre_ubicacion` varchar(100) NOT NULL,
`descripcion` varchar(255) DEFAULT NULL
);
CREATE TABLE `usuarios` (
`id_usuario` INTEGER PRIMARY KEY AUTOINCREMENT,
`telefono` varchar(20) NOT NULL UNIQUE,
`id_persona` INTEGER NOT NULL REFERENCES personas(id_persona),
`id_estado_usuario` INTEGER NOT NULL REFERENCES estados_usuario(id_estado_usuario)
);
CREATE UNIQUE INDEX uq_bibliotecario_persona ON bibliotecarios(id_persona);
CREATE UNIQUE INDEX uq_permiso ON cuenta_permisos(id_cuenta, modulo, accion);

