# SAIDE Biblioteca: permisos por rol, paginación y orden numérico

Fecha: 22 de octubre de 2026. Rama: `mejora-registro-roles`.

Este informe describe el estado vigente y sustituye las indicaciones anteriores de administración de permisos individuales. La asignación del rol permanece exclusivamente en Personas → Editar persona.

## Resultado

- Matrices únicas para BIBLIOTECARIO y USUARIO, configurables por SUPERADMIN en Cuentas y permisos → Administración de permisos por rol.
- Todas las cuentas del mismo rol, incluidas las futuras, consultan la misma matriz. No se copian permisos a las cuentas.
- Revocación efectiva en la siguiente operación, sin caché de permisos de sesión ni recuperación de privilegios del archivo individual.
- Paginación compartida con clases Bootstrap 5, textos en español, resumen de registros, página activa, enlaces y controles deshabilitados. Conserva búsquedas y los nombres independientes de los paginadores.
- Listados administrativos ordenados por su identificador ascendente antes de paginar. No se renumeraron identificadores.
- Se conservan personas, cuentas, lectores, personal, autores, libros y todo el historial de préstamos, devoluciones y seguridad.

## Diagnóstico y correcciones

El método `CuentaAcceso::puede()` consultaba la colección individual `cuenta_permisos`. Los cambios de rol y el registro creaban o eliminaban esas filas. La lista de cuentas mostraba cantidades distintas de permisos para dos bibliotecarios.

Ahora `puede()` valida primero cuenta activa, rol y límites de seguridad; SUPERADMIN tiene acceso explícito y los demás roles consultan `rol_permisos`. Los permisos individuales antiguos no participan en la autorización, aunque permanezcan cargados en un modelo. También se retiraron las copias de permisos de ambos flujos de consola de creación de cuentas, además del registro público y los cambios de rol.

Cuentas y permisos ya no presenta una matriz por persona. Los enlaces antiguos de consulta redirigen a la matriz del rol; las antiguas solicitudes de escritura individual reciben HTTP 403. La lista de cuentas tiene búsqueda, paginación, ID, persona, rol, estado y un resumen compacto de autorizaciones heredadas.

En Personas, la creación o cambio de rol conserva la transacción, los vínculos mediante `id_persona`, la protección del último SUPERADMIN y la auditoría del actor y la cuenta. Las filas históricas de `cuenta_permisos` se conservan incluso cuando dejan de corresponder al nuevo rol: son archivo, no privilegios efectivos.

Los listados utilizaban criterios por apellido, título, fecha o ID descendente. Préstamos y devoluciones cargaban colecciones completas y paginaban en PHP. Ahora sus filtros de estado y pendientes se resuelven en SQL, se ordenan por ID y se paginan antes de transformar la página para presentación. Se conservan los casos de devoluciones parciales y los préstamos sin detalles, según el comportamiento previo.

Además faltaban los archivos de Bootstrap referenciados por el layout. Eso explica parte de la presentación sin formato y afecta también a modales y otros componentes. Se restauraron archivos locales de Bootstrap 5.3.8 y su licencia MIT; se verificaron ambos archivos mediante los hashes SHA-384 publicados en la [documentación oficial de Bootstrap](https://getbootstrap.com/docs/5.3/getting-started/introduction/). La aplicación los sirve localmente y no depende de una CDN en ejecución.

## Matriz institucional aplicada

| Rol | Módulos Laravel | Operaciones iniciales |
| --- | --- | --- |
| BIBLIOTECARIO | personas, usuarios, autores, categorias, editoriales, ubicaciones, libros, ejemplares, prestamos | ver, crear, editar |
| BIBLIOTECARIO | bibliotecarios, estados-usuario, estados-ejemplar | ver |
| BIBLIOTECARIO | devoluciones | ver, crear |
| USUARIO | libros, autores, categorias, editoriales | ver |
| SUPERADMIN | Administración completa | Acceso explícito; no editable como matriz ordinaria |

Se cargaron **32 autorizaciones para BIBLIOTECARIO y 4 para USUARIO**. La matriz del bibliotecario es administrable; el rol USUARIO conserva un límite obligatorio de solo consulta de los cuatro módulos de catálogo, incluso ante peticiones manipuladas o una fila de base de datos incompatible. Roles, cuentas y privilegios siguen reservados al SUPERADMIN.

Adaptaciones al esquema real:

- `ubicaciones_libros` se administra mediante el módulo Laravel `ubicaciones`.
- `estados_usuario` y `estados_ejemplar` utilizan `estados-usuario` y `estados-ejemplar`.
- `asigna_autores`, `asigna_categorias` y `asigna_editoriales` se gestionan dentro de los formularios de libros, bajo crear/editar libros. No existen pantallas independientes de esas tablas. Retirar una asociación no requiere autorizar la eliminación del libro.
- `detalle_prestamo` se gestiona dentro del préstamo y sus operaciones transaccionales. Su relación se ordena por `id_detalle`.
- No se inventaron módulos administrativos independientes para tablas auxiliares.

La comparación previa encontró que la cuenta BIBLIOTECARIO ID 2 tenía 15 autorizaciones individuales y la cuenta ID 9 tenía 12. Ambas pasan a las mismas 32 autorizaciones institucionales. Se agregan respectivamente 17 y 20 autorizaciones de la matriz solicitada; no se tomó la unión de permisos existentes. Las otras seis cuentas USUARIO ya coincidían con sus cuatro consultas. SUPERADMIN conserva su acceso explícito. El detalle por cuenta se conserva en el manifiesto privado del respaldo.

## Base de datos y respaldo

Migración aplicada: `database/migrations/2026_10_22_000002_create_permisos_por_rol.php`.

Solo se agregaron:

1. `rol_permisos`: ID, rol, módulo y acción, con restricción única por `(rol, modulo, accion)`.
2. `historial_rol_permisos`: ID, actor, rol, detalle JSON y fecha; FK hacia la cuenta del administrador. El historial individual anterior permanece intacto porque su estructura exige una cuenta destinataria y no representa adecuadamente un cambio para todo un rol.

El historial nuevo contiene dos eventos de inicialización institucional. Los cambios posteriores desde la matriz registran administrador, rol, permisos anteriores, permisos nuevos y fecha dentro de la misma transacción que guarda la matriz.

El instalador dirigido `scripts/instalar-rbac.php` primero compara permisos, realiza un respaldo con `mysqldump --single-transaction` e incluye rutinas, eventos y triggers. Verifica las definiciones de todas las tablas, el número de INSERT por tabla, la finalización del dump y su SHA-256, y comprueba que no hubo cambios durante el respaldo. Después ejecuta únicamente la migración RBAC indicada por ruta; no ejecuta las otras migraciones pendientes.

Respaldo privado generado:

`storage/app/private/backups/biblioteca2-rbac-20261022-103716.sql`

- Tamaño: **56 127 bytes**.
- SHA-256: `9abf6f5b7b7863948a4f5892ac69867a447909500a1685191addc951c81791ba`.
- Archivos auxiliares: `.sha256` y `.json`, con las huellas de las tablas y la comparación de permisos.
- El directorio está excluido de Git. No se publicaron credenciales, hashes de contraseñas ni el dump.
- La verificación fue de integridad del archivo, contenido y conteos. No se hizo una restauración de ensayo en otra instancia.

Tras la migración se compararon las huellas SHA-256 de todas las filas de las tablas anteriores. Permanecieron iguales; solo `migrations` recibió el registro de esta migración. No se modificaron las estructuras anteriores ni se eliminaron filas. Permanecen **51 permisos individuales históricos**, 9 cuentas, 28 personas, 15 lectores, 5 bibliotecarios, 10 autores, 10 libros, 14 ejemplares, 10 préstamos, 11 detalles de préstamo, 5 devoluciones y 6 detalles de devolución.

No se ejecutaron `migrate:fresh`, `migrate:refresh`, `migrate:reset`, truncados, renumeraciones ni eliminaciones de bases de datos. Las pruebas de escritura se ejecutaron exclusivamente sobre SQLite `:memory:`.

## Paginación y orden

La vista compartida `resources/views/pagination/saide.blade.php` utiliza Bootstrap, etiquetas accesibles y `lang/es/pagination.php`. Muestra, por ejemplo, «Mostrando 16 a 28 de 28 registros» y «Anterior / Siguiente». Evita resúmenes duplicados y claves de traducción literales. Las reglas responsivas evitan viñetas, permiten ajuste de línea y reducen enlaces numéricos secundarios en pantallas estrechas.

| Listado | Orden |
| --- | --- |
| Personas | id_persona ascendente |
| Lectores | id_usuario ascendente |
| Bibliotecarios | id_bibliotecario ascendente |
| Autores | id_autor ascendente |
| Libros | id_libro ascendente |
| Ejemplares | id_ejemplar ascendente |
| Préstamos | id_prestamo ascendente |
| Devoluciones | id_devolucion ascendente |
| Pendientes de devolución | id_prestamo ascendente |
| Categorías | id_categoria ascendente |
| Editoriales | id_editorial ascendente |
| Ubicaciones | id_ubicacion ascendente |
| Estados de lectores | id_estado_usuario ascendente |
| Estados de ejemplares | id_estado_ejemplar ascendente |
| Cuentas | id_cuenta ascendente |
| Historial de cuentas y de roles | id_cambio ascendente, cada uno con su paginador |
| Detalles de préstamos/devoluciones | Identificador del detalle ascendente |

Los combos de selección conservan sus criterios descriptivos para facilitar la búsqueda; no son tablas de listados administrativos. La tarjeta del escritorio conserva la selección funcional de los seis préstamos recientes, mostrando ese conjunto en orden ascendente de ID. No sustituye al listado paginado completo de Préstamos.

## Archivos intervenidos

| Grupo | Archivos y cambios |
| --- | --- |
| Autorización | `app/Models/CuentaAcceso.php`, `app/Support/PermisosBiblioteca.php`: matriz institucional y consulta dinámica por rol. |
| Roles y altas | `app/Support/AdministrarCuenta.php`, `app/Http/Controllers/RegistroPublicoController.php`, `app/Support/CrearCuentaPorConsola.php`, `app/Console/Commands/CrearAccesoBiblioteca.php`: herencia sin escrituras individuales; conservación de auditoría y relaciones. |
| Cuentas | `app/Http/Controllers/CuentaAccesoController.php`, `routes/web.php`: matriz por rol, transacción, validaciones, historial, búsqueda y paginación. |
| Listados | Controladores `AutorController`, `BibliotecarioController`, `CatalogoController`, `DevolucionController`, `EjemplarController`, `LibroController`, `PersonaController`, `PrestamoController`, `UsuarioController`: orden ascendente y paginación SQL donde correspondía. |
| Relaciones y escritorio | `app/Models/Prestamo.php`, `app/Models/Devolucion.php`, `app/Http/Controllers/DashboardController.php`: detalles ordenados y conjunto reciente mostrado por ID. |
| Vistas | `resources/views/cuentas/index.blade.php`, `permisos.blade.php`, `historial.blade.php`, `resources/views/personas/acceso.blade.php`: matriz compartida, resumen heredado y textos actualizados. |
| Paginación | `app/Providers/AppServiceProvider.php`, `resources/views/pagination/saide.blade.php`, `lang/es/pagination.php`, `public/assets/css/app.css`. |
| Dependencia visual | `public/assets/vendor/bootstrap/bootstrap.min.css`, `bootstrap.bundle.min.js`, `LICENSE`: archivos locales faltantes. |
| Base de datos | Migración incremental RBAC y `scripts/instalar-rbac.php`: respaldo, comparación, instalación y conservación verificada. |
| Verificación | `scripts/verificar-biblioteca.php`, `tests/Feature/SeguridadBibliotecaTest.php`, `tests/Feature/RbacYPaginacionTest.php`, `phpunit.xml`: casos RBAC, paginación, filtros, integridad y coste de bcrypt reducido solo durante pruebas aisladas. |

Se conservaron los cambios locales de las solicitudes anteriores. El middleware sigue reservando `cuentas.*` al SUPERADMIN y utiliza `puede()` en las operaciones de los módulos; no fue necesario duplicar reglas de autorización en nuevas rutas administrativas.

## Pruebas y resultados

- **31 pruebas y 844 aserciones aprobadas** en la ejecución final de PHPUnit.
- Dos bibliotecarios reciben idénticos permisos en todos los módulos/acciones; un permiso individual cargado no concede una acción ausente de la matriz.
- Cambiar la matriz afecta a dos cuentas existentes y a una nueva. Revocar permisos bloquea la ruta incluso reutilizando los mismos objetos autenticados.
- SUPERADMIN configura las matrices; bibliotecario y lector reciben HTTP 403. USUARIO no puede obtener edición/eliminación mediante peticiones manipuladas ni filas incompatibles.
- La ruta individual de escritura rechaza cambios. El GET antiguo redirige al rol.
- Un fallo simulado del historial revierte toda la modificación de matriz.
- Se mantienen las pruebas de login por rol, registro, promoción/degradación desde Personas, último SUPERADMIN, credenciales, CSRF, preservación de relaciones y movimientos históricos.
- **17 casos de listados/paginadores** con registros insertados fuera de orden, más de una página y nombres deliberadamente inversos a los IDs. Se verificó el orden y que el último ID de una página sea menor que el primero de la siguiente, conservando `buscar=Registro` en los enlaces.
- Se probaron por separado activos, atrasados, devueltos y los dos paginadores simultáneos de devoluciones, conservando sus filtros.
- Se verificaron texto en español, totales, página activa y controles deshabilitados en el HTML renderizado.
- **67 archivos PHP** sin errores de sintaxis, BOM ni contenido previo a `<?php`; Pint y `git diff --check` sin errores finales.
- **73 rutas** sin duplicados por método y URI.
- Auditoría real de solo lectura: **58 respuestas HTTP 200**, incluidas las dos matrices, segunda página de Personas, catálogos, formularios de personas y cuentas. **26 FK** sin registros huérfanos; todos los conteos se conservaron durante esta auditoría.

## Límites y pendientes

- **Solicitudes de préstamo:** no existe actualmente un módulo, tabla, controlador o ruta de solicitudes en el proyecto inspeccionado. No se implementó un proceso nuevo ni se presenta como terminado. USUARIO sigue sin poder registrar un préstamo administrativo definitivo. Un futuro módulo de solicitudes deberá comprobar la propiedad de cada solicitud y mantener separadas la solicitud y la aprobación.
- **Verificación visual:** se generaron vistas HTML con datos sintéticos usando SQLite, pero la política del navegador bloqueó abrir archivos locales. No se eludió esa restricción. La revisión visual manual completa en escritorio y móvil queda pendiente; la estructura Bootstrap, traducciones, estado activo/deshabilitado, enlaces y orden están comprobados por pruebas de renderizado y respuesta.
- **Escrituras reales:** las modificaciones de matrices desde la interfaz y los logins se probaron en SQLite aislado. En MariaDB se aplicó la inicialización institucional autorizada y se verificó la lectura; no se crearon cuentas ni préstamos de ensayo reales.
- La migración histórica de acceso sigue pendiente en el historial anterior; no se reejecutó ni se alteraron manualmente sus registros para aplicar RBAC.

## Git y configuración

Rama activa: `mejora-registro-roles`. Se dejaron los cambios revisables, sin commit automático. No se cambió `main`, no se hizo push ni merge y no se usaron operaciones destructivas de Git. `.env`, las credenciales y `APP_KEY` permanecen intactos.
