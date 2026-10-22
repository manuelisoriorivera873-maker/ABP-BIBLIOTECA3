# Solicitudes de préstamo — SAIDE Biblioteca

Fecha: 22 de octubre de 2026. Rama: `mejora-registro-roles`.

## Diagnóstico

Existía el CRUD de préstamos definitivos, pero no había rutas, controlador, modelos, vistas ni tablas para solicitudes. El menú del lector no incluía ese flujo. El bloqueo no se resolvía habilitando una casilla existente.

El esquema real de MariaDB y el código de préstamos no contenían una configuración de máximos de 5 ejemplares y 3 por título. Se incorporaron esos límites al flujo nuevo en `config/prestamos.php`, sin cambiar el CRUD administrativo existente.

## Funcionamiento implementado

- USUARIO encuentra **Catálogo de libros**, **Solicitar préstamo**, **Mis solicitudes** y **Mis préstamos**. El catálogo también enlaza al formulario.
- El formulario permite elegir cantidades de varios títulos y muestra un resumen antes de enviar una sola solicitud.
- Se exige lector ACTIVO, disponibilidad real y ausencia de préstamos vencidos sin devolver. Los ejemplares DISPONIBLE que todavía tengan un detalle de préstamo sin devolución no se consideran disponibles.
- Se admiten hasta 5 ejemplares y 3 del mismo título. También se cuentan préstamos sin devolver y solicitudes PENDIENTE/APROBADA del lector para impedir eludir el límite mediante solicitudes separadas.
- La solicitud y sus detalles se guardan en una transacción. Se ignoran identificadores de lector enviados por el cliente: se utiliza exclusivamente la cuenta autenticada.
- La asignación automática exige BIBLIOTECARIO activo, relación de personal, permisos de rol Ver/Editar solicitudes, disponibilidad declarada y una sesión real que siga autenticada. Se comprueba el almacenamiento de sesiones; `ultimo_acceso` no acredita presencia.
- El bibliotecario activa **Disponible para atender solicitudes** en su cola. La actividad en esa página acredita disponibilidad durante 5 minutos; recargar renueva actividad y atiende asignaciones pendientes. Cerrar sesión invalida la comprobación. Los identificadores de sesión se almacenan cifrados, con huella SHA-256 para localizar el registro.
- Se distribuyen solicitudes pendientes entre bibliotecarios disponibles según carga. Si no hay ninguno, permanecen pendientes. Al volver a abrir la cola con disponibilidad activa se asignan o reasignan las pendientes cuyo responsable ya no esté disponible.
- El bibliotecario puede aprobar o rechazar, con motivo obligatorio. El lector consulta el estado y el motivo exclusivamente en sus propias solicitudes.
- Aprobar no reserva ejemplares ni crea el préstamo. **Confirmar entrega** exige aprobación previa y fecha límite; vuelve a validar lector, límites y existencias, bloquea filas y crea préstamo/detalles/cambios de inventario en una transacción. No se puede entregar dos veces.
- SUPERADMIN puede supervisar y atender solicitudes. Para registrar la entrega necesita su relación de bibliotecario, como exige la estructura existente de préstamos.

## RBAC y presentación

Se agregó el módulo Solicitudes a la matriz existente. La migración habilitó únicamente `USUARIO: solicitudes/ver,crear` y `BIBLIOTECARIO: solicitudes/ver,editar`, conservando las configuraciones anteriores de los demás módulos. No se crearon permisos individuales.

La revocación se consulta en el servidor en cada petición. Un USUARIO no puede aprobar, rechazar, entregar ni declarar disponibilidad aunque se marque Editar en su matriz. Sus consultas de solicitudes y préstamos se restringen al identificador de lector autenticado.

No se modificaron archivos CSS, estilos, colores, tipografías ni clases de los componentes existentes. Se reutilizaron los componentes actuales para los enlaces, formularios y tablas indispensables.

## Archivos de esta implementación

Nuevos:

- `app/Http/Controllers/SolicitudPrestamoController.php`
- `app/Models/SolicitudPrestamo.php`
- `app/Models/SolicitudDetalle.php`
- `app/Services/SolicitarPrestamo.php`
- `app/Services/SesionesOperativas.php`
- `config/prestamos.php`
- `database/migrations/2026_10_22_000003_create_solicitudes_prestamo.php`
- `resources/views/solicitudes/create.blade.php`
- `resources/views/solicitudes/index.blade.php`
- `public/assets/js/solicitudes.js`
- `scripts/instalar-solicitudes.php`
- `scripts/respaldo-verificado.php`
- `tests/Feature/SolicitudesPrestamoTest.php`
- `tests/JavaScript/solicitudes.test.cjs`
- Este informe.

Existentes modificados para integrar el flujo:

- `routes/web.php`: rutas protegidas.
- `app/Http/Middleware/AutorizarBiblioteca.php`: correspondencia de acciones de disponibilidad y consulta propia con RBAC.
- `app/Support/PermisosBiblioteca.php`: módulo en la matriz existente; habilitación mediante migración incremental.
- `resources/views/layouts/app.blade.php`: enlaces del lector y del personal.
- `resources/views/libros/index.blade.php`: acceso a Solicitar préstamo.
- `resources/views/prestamos/index.blade.php`: título Mis préstamos y enlace Limpiar que conserva la ruta del lector.
- `resources/views/dashboard/personal.blade.php`: acceso al nuevo módulo desde el escritorio del bibliotecario.
- `scripts/instalar-rbac.php`: reutilización del respaldo verificado, sin duplicar el procedimiento; imports antes del bootstrap.
- `scripts/verificar-biblioteca.php`: comprobaciones adicionales de solo lectura para las nuevas páginas y enlaces.

Se conservaron los cambios previos que ya estaban presentes en otros archivos al comenzar esta tarea.

## Migración y conservación de datos

Se ejecutó únicamente la migración nueva mediante `php scripts/instalar-solicitudes.php --apply`, después de generar y verificar un respaldo completo.

Respaldo privado:

`storage/app/private/backups/biblioteca2-solicitudes-20261022-120038.sql`

SHA-256:

`5d7a89de23866c1b855b53e6607a68d198823aaa95b5c8511e8fc17444c27aa0`

Se añadieron `solicitudes_prestamo`, `solicitud_detalles` y `sesiones_operativas`, con tipos de claves foráneas compatibles con el esquema real. Se verificaron las huellas de las tablas históricas y la igualdad de cada fila previa de permisos e historial. Solo se añadieron cuatro permisos, dos eventos de habilitación y el registro de migración.

Se conservaron las 9 cuentas, 28 personas, 15 lectores, 10 préstamos y sus detalles/devoluciones. No se crearon cuentas, préstamos ni solicitudes de prueba en `biblioteca2`. No se ejecutó `migrate:fresh`, no se modificó `main` y no se hizo push.

## Pruebas realizadas

1. **46 pruebas PHP, 1858 aserciones, todas correctas.** Base SQLite exclusivamente en memoria, comprobada al iniciar cada prueba. Incluyen las pruebas anteriores y 10 pruebas nuevas de solicitudes.
2. Recorrido HTTP con **dos sesiones independientes y login por contraseña**: bibliotecario disponible → login lector → catálogo y cuatro enlaces → formulario → una solicitud de dos títulos y cinco ejemplares → asignación → aprobación sin préstamo definitivo → entrega → consulta del lector y préstamo visible.
3. Ausencia de personal disponible, recepción posterior, rechazo y motivo visible solo al dueño.
4. Cantidades vacías, negativas, no enteras, más de 3 por título, más de 5 en total, falta de existencias, lector suspendido, cuentas inactivas, cuenta sin vínculo de lector, solicitudes abiertas y préstamos vencidos.
5. Propiedad de registros, intentos de aprobar como lector, revocación inmediata para varias cuentas del mismo rol, supervisión del SUPERADMIN y ausencia de permisos individuales.
6. Disponibilidad desactivada, caducidad de actividad, cierre de sesión, reasignación a otro bibliotecario disponible y prohibición de modificar una solicitud asignada a otro responsable activo.
7. Revalidación al entregar, bloqueo de entrega duplicada y fallos deliberados de base de datos para comprobar rollback de solicitud/detalles y préstamo/inventario.
8. **2 pruebas JavaScript correctas**: regresión de la matriz y resumen de varios títulos, recálculo y texto seguro.
9. **MariaDB real: 64 GET con respuesta 200 y 33 relaciones FK verificadas**, sin alterar conteos. Incluye páginas del lector y escritorio del bibliotecario. Estas comprobaciones usan el guard en memoria; no cambian contraseñas ni último acceso.
10. `git diff --check` sin errores. Ningún archivo CSS modificado.

El recorrido con escrituras se probó de forma aislada mediante HTTP automatizado; no se utilizaron credenciales personales ni se registraron operaciones ficticias en la base real.
