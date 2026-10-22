# Auditoría y estabilización de SAIDE Biblioteca

**Estado vigente:** consultar también `INFORME_RBAC_PAGINACION.md`. La solicitud más reciente reemplaza los permisos individuales por matrices compartidas por rol; las secciones anteriores de este informe se conservan como registro histórico de las revisiones.

Fecha: 22 de octubre de 2026. Proyecto: `C:\xampp\htdocs\biblioteca3`.

**Requisito vigente:** la sección final «Corrección: asignación exclusiva desde Personas» sustituye las indicaciones previas sobre cambio administrativo de roles desde Cuentas y permisos.

## A. Diagnóstico inicial

- Rama inicial y final: `mejora-registro-roles`. El directorio ya contenía modificaciones y archivos sin seguimiento relacionados con el registro, roles, permisos y pruebas. Se conservaron y se trabajó sobre ellos.
- PHP CLI real: 8.4.0. La aplicación Laravel arranca y registra 71 rutas del proyecto, sin duplicados por método y URI.
- La conexión real utiliza MariaDB/MySQL y la base `biblioteca2`. Se consultó directamente su estructura; el SQL aportado, generado el 21 de octubre, se utilizó como referencia y no se importó sobre los datos actuales.
- Los errores históricos de tablas inexistentes y `autores.id_persona` no están presentes en el esquema actual. Las tablas `cuentas_acceso`, `cuenta_permisos`, `historial_permisos` y `detalle_devolucion` existen.
- El login local ya tenía redirecciones explícitas y una anotación de `CuentaAcceso` para reconocer `forceFill()`. El método `destinoInicio()` sí existe en el modelo. Se eliminó su uso innecesario en el middleware para expresar directamente el destino del lector.
- Los cambios locales previos ya incluían registro transaccional, cambios de rol y protección del último SUPERADMIN. Sus 9 pruebas iniciales pasaron; se ampliaron para verificar comportamientos pendientes.
- El alta administrativa de lectores siempre creaba una persona, incluso cuando ya existía como autora o bibliotecaria.
- Las tablas de acceso fueron creadas manualmente y `migrations` está vacía. La migración original intentaría crearlas otra vez; además, su reversión eliminaba tablas de seguridad.
- La validación de contraseñas no comprobaba el límite de 72 bytes de bcrypt. Los patrones HTML del registro necesitaban escapes compatibles con los navegadores actuales.
- No se encontraron archivos PHP con BOM o contenido previo a la etiqueta inicial en los directorios revisados.

## B. Correcciones realizadas en esta revisión

| Archivo | Cambio |
| --- | --- |
| `app/Http/Controllers/AccesoController.php` | Corregida la indentación de la cuenta tipada y aplicado formato Laravel; conservados destinos explícitos, último acceso y cierre seguro de sesión. |
| `app/Http/Middleware/AutorizarBiblioteca.php` | Redirección explícita de lectores al catálogo; se conservan la comprobación de cuenta activa, el bloqueo de administración y la autorización por módulo/acción. |
| `app/Http/Controllers/RegistroPublicoController.php` | Mensajes de validación en español, límite de bcrypt y `ultimo_acceso` dentro de la misma transacción que persona, lector, cuenta, permisos e historial. |
| `app/Http/Controllers/UsuarioController.php` | Selección de persona existente, bloqueo de duplicación de lector y bloqueo transaccional de la persona seleccionada. Sus nombres se conservan aunque la solicitud envíe otros. Validación de teléfono coherente con el registro público. |
| `resources/views/usuarios/form.blade.php` | Selector de personas existentes y alternancia de campos para registrar una nueva. Se mantienen los componentes visuales actuales. |
| `app/Http/Controllers/CuentaAccesoController.php` | Regla de límite bcrypt en creación y actualización de contraseña; formato Laravel. Se conservaron y probaron las modificaciones previas de roles y permisos. |
| `app/Rules/LimiteBcrypt.php` | Validación reutilizable de longitud en bytes, incluidos caracteres multibyte, cuando el algoritmo es bcrypt. |
| `resources/views/acceso/registro.blade.php` | Corregidos los escapes de los patrones HTML de teléfono y nombre de usuario. |
| `database/migrations/2026_10_20_000001_create_acceso_y_permisos_tables.php` | Reconoce las tres tablas existentes cuando contienen las columnas requeridas; rechaza esquemas parciales o incompletos antes de ejecutar DDL. La reversión queda bloqueada para conservar cuentas e historial. |
| `tests/Feature/SeguridadBibliotecaTest.php` | Ampliación de pruebas funcionales, de autorización, persistencia, integridad histórica y CSRF. |
| `tests/Fixtures/schema.sql` | Relaciones y restricciones de libros, ejemplares, préstamos y devoluciones para pruebas aisladas más representativas. |
| `scripts/verificar-biblioteca.php` | Auditoría CLI de solo lectura: conteos, todas las FK reales, formato de hashes, compatibilidad de roles/permisos y renderizado de pantallas con guard y sesiones en memoria. No imprime hashes ni datos personales. |

No se atribuyen a esta revisión los cambios que ya estaban presentes al iniciar. Se preservaron también sus modificaciones en `.gitignore`, modelos, configuración, rutas, CSS, vistas de acceso, bibliotecarios y cuentas, y el script previo `scripts/proteger-acceso.php`.

## C. Base de datos

Se inspeccionaron las 22 tablas actuales y sus definiciones. Se verificaron las 25 relaciones declaradas mediante claves foráneas: no se encontraron registros huérfanos.

| Tabla | Registros antes y después |
| --- | ---: |
| personas | 24 |
| usuarios | 10 |
| bibliotecarios | 4 |
| autores | 10 |
| cuentas_acceso | 3 |
| cuenta_permisos | 19 |
| historial_permisos | 5 |
| libros | 10 |
| ejemplares | 14 |
| prestamos | 10 |
| detalle_prestamo | 11 |
| devoluciones | 5 |
| detalle_devolucion | 6 |

Todos los conteos de las 22 tablas permanecieron iguales durante la auditoría de solo lectura. `autores` y `bibliotecarios` ya contienen únicamente su identificador e `id_persona`. Las cuentas ya tienen índices únicos de nombre de usuario, lector y bibliotecario, además de sus FK correspondientes.

No se ejecutaron ALTER, migraciones, importaciones ni reparaciones sobre la base real. No fue necesario crear un respaldo para una alteración porque no se realizó ninguna. El SQL aportado no se consideró un respaldo verificado del estado actual.

La modificación de la migración es preventiva y no cambia por sí misma el registro de `migrations`, que continúa vacío. Su comprobación de compatibilidad verifica tablas y columnas; no sustituye una auditoría de tipos, índices y FK. Estos últimos se inspeccionaron directamente en la base actual.

## D. Funcionalidades verificadas

- Login de SUPERADMIN y BIBLIOTECARIO al escritorio, y de USUARIO al catálogo; se ignora un destino guardado hacia `/registro`.
- Rechazo de cuentas inactivas, actualización de último acceso y cierre de sesión.
- Registro público: persona, lector, cuenta USUARIO, cuatro permisos de consulta, historial e inicio automático de sesión.
- Rol público fijado por el backend: un `rol=SUPERADMIN` y permisos administrativos enviados por el visitante no otorgan privilegios.
- Detección de coincidencias de personas en el registro público, con derivación a revisión administrativa, sin apropiación automática de un registro existente.
- Reversión de todos los registros del autorregistro si falla la inserción del historial.
- SUPERADMIN consulta cuentas e historial, cambia roles y administra permisos. Se conserva al menos un SUPERADMIN activo.
- Promoción de lector a bibliotecario reutilizando su persona; conservación del lector histórico; promoción a SUPERADMIN y regreso a USUARIO con revocación de privilegios administrativos.
- CRUD real de libros y sus asociaciones en las pruebas; visibilidad de botones y bloqueo de peticiones directas después de revocar permisos.
- Bibliotecarios creados desde una persona existente; lectores administrativos ahora pueden reutilizarla también.
- Conservación exacta de personas, lectores, libros, ejemplares, préstamos, detalles y devoluciones en una prueba de cambios sucesivos de rol.

## E. Evidencia de pruebas

1. `php vendor/phpunit/phpunit/phpunit`: **16 pruebas, 262 aserciones, todas aprobadas**. La configuración fuerza SQLite `:memory:` y cada prueba funcional comprueba esa conexión antes de crear el esquema.
2. Las pruebas cubren credenciales y destinos, cuentas inactivas, registro, intento de escalada, duplicados, rollback, roles, último administrador, permisos CRUD, conservación histórica, longitud de contraseña y rechazo HTTP 419 de solicitudes sin token CSRF. La prueba CSRF activa expresamente la comprobación que Laravel omite normalmente en PHPUnit.
3. Auditoría en MariaDB: **25 FK sin huérfanos**, hashes reconocidos como bcrypt/Argon y permisos compatibles con el rol; **14 pantallas HTTP 200**: escritorio, cuentas, nueva cuenta, historial, libros, autores, personas, usuarios, alta de usuario, bibliotecarios, alta de bibliotecario, préstamos, devoluciones y ejemplares. El guard administrativo se utilizó solo en memoria para estas consultas GET; no se iniciaron sesiones mediante contraseña ni se actualizó último acceso.
4. `php artisan route:list --except-vendor --json`: **71 rutas, cero duplicadas por método/URI**.
5. `php -l` y revisión del inicio de los **62 archivos PHP** en `app`, `bootstrap`, `config`, `routes`, `database`, `scripts` y `tests`: sin errores de sintaxis, BOM o contenido anterior a `<?php`.
6. Pint aplicado a los archivos PHP intervenidos; `git diff --check` sin errores de espacios.
7. Navegador local: login con estilos y enlace de registro; el enlace abre `/registro` con las secciones de datos personales y credenciales, contraseñas ocultas y enlace de regreso. Se observó el diseño en el ancho disponible del panel; no se realizó una matriz completa de dispositivos.

## F. Límites y asuntos pendientes

- Las escrituras y los inicios de sesión por contraseña se verificaron en SQLite aislado. En MariaDB real se verificaron estructura, integridad y consultas/renderizado. No se cambiaron contraseñas, roles o datos reales para probarlos. Una aceptación manual con cuentas reales sigue siendo una comprobación adicional, no realizada en esta revisión.
- No se ejecutó la migración preventiva en la base real ni se marcó artificialmente como aplicada. El historial de migraciones continúa pendiente de conciliar durante un procedimiento de mantenimiento con respaldo; esto no bloquea los flujos probados de la aplicación actual.
- Las coincidencias de nombres del registro público requieren revisión del personal. No se implementó verificación de identidad externa ni vinculación automática, para evitar apropiación de personas existentes.
- Las pruebas no certifican concurrencia de múltiples procesos ni compatibilidad visual con todos los navegadores y tamaños de pantalla.

## G. Git y conservación

- Rama de trabajo: `mejora-registro-roles`.
- Se conservaron las modificaciones locales existentes y se dejaron los cambios revisables en el directorio de trabajo, sin commit automático.
- No se cambió de rama ni se modificó la referencia `main`.
- No se ejecutaron push, merge, force-push, reset destructivo o limpieza del proyecto.
- No se modificaron `.env` ni `APP_KEY`, ni se incluyeron contraseñas o hashes en este informe.
- Los archivos modificados en esta revisión se enumeran en B; el estado completo, incluyendo los cambios previos, puede consultarse con `git status --short`.

## Ampliación prioritaria: roles desde Editar persona

Se incorporó «Rol y acceso al sistema» debajo de los datos personales, conservando las clases de formulario y botones de SAIDE. La sección solo se renderiza para SUPERADMIN. El backend rechaza con HTTP 403 cualquier campo de acceso enviado por un bibliotecario, aunque tenga permiso para editar personas; los lectores siguen sin poder acceder a la edición.

- Permite seleccionar USUARIO, BIBLIOTECARIO o SUPERADMIN y activar/desactivar una cuenta existente. Un cambio de rol restablece sus permisos al mínimo inicial; la matriz detallada permanece en Cuentas y permisos y tiene enlace desde el formulario.
- Una persona sin cuenta puede guardarse normalmente sin crear acceso, o marcar «Crear cuenta con credenciales válidas». Esta opción exige usuario único, contraseña de al menos 12 caracteres y confirmación. No se generan contraseñas automáticamente.
- No se agregó `rol` a `personas` ni se alteró el esquema SQL. Las relaciones existentes de lector y personal se conservan; las promociones reutilizan `id_persona` y crean la relación de bibliotecario solo cuando hace falta. Un SUPERADMIN nuevo se vincula mediante bibliotecarios para mantener identificable a su persona.
- Se extrae la actualización a `app/Support/AdministrarCuenta.php`, utilizada por Personas y Cuentas y permisos: autorización de SUPERADMIN activo, bloqueo de cuentas en orden estable, protección del último administrador, relaciones, permisos e historial. La actualización de persona y cuenta forma una sola transacción.
- El historial incluye actor, cuenta, rol anterior, rol nuevo, origen y fecha. La activación de una cuenta con contraseña heredada sin hash válido se rechaza y deriva a la administración de credenciales.
- La cuenta enviada debe pertenecer a la persona de la URL. Si una persona tiene varias cuentas, se muestran enlaces para seleccionar la cuenta; las otras no se modifican.
- Las contraseñas del nuevo formulario se excluyen del recorte de espacios y del almacenamiento temporal de datos al producirse errores de validación.

Archivos de esta ampliación: `PersonaController.php`, `CuentaAccesoController.php`, `CuentaAcceso.php`, `AdministrarCuenta.php` (nuevo), `bootstrap/app.php`, `resources/views/personas/form.blade.php`, `resources/views/personas/acceso.blade.php` (nuevo), `SeguridadBibliotecaTest.php`, `scripts/verificar-biblioteca.php` y este informe. Se inspeccionaron también Persona, Usuario, Bibliotecario, personas.edit, middleware y rutas; no requirieron cambios adicionales para este flujo.

Validación final de la ampliación: **24 pruebas y 425 aserciones aprobadas** en SQLite `:memory:`. Cubren altas con los tres roles y login posterior, cambios de rol desde Personas y login posterior, revocación de privilegios, conservación exacta de préstamos/devoluciones, edición normal del personal, intentos de escalada, cuenta ajena, último SUPERADMIN, credenciales obligatorias, contraseñas no almacenadas en old input, rollback, activación y selección entre varias cuentas. Los archivos PHP intervenidos pasan `php -l`, se formatearon con Pint y `git diff --check` no reporta errores.

Auditoría real de solo lectura: 25 FK sin huérfanos y **41 respuestas HTTP 200**, incluyendo los **27 formularios existentes de Editar persona**. Los conteos iniciales y finales de esa ejecución coinciden. Estos conteos reflejan el estado actual del usuario, que puede diferir de la auditoría anterior. Las escrituras e inicios de sesión de prueba siguieron exclusivamente en SQLite; no se asignaron roles ni se crearon cuentas de prueba en `biblioteca2`.

Se mantiene la rama `mejora-registro-roles`, sin commit, push, cambios en `main` ni operaciones destructivas en la base real.

## Corrección: asignación exclusiva desde Personas

La única interfaz administrativa de asignación de roles es ahora **Personas → Editar persona → Asignación de rol**. Su desplegable muestra el rol actual y las opciones USUARIO — Usuario lector, BIBLIOTECARIO — Bibliotecario y SUPERADMIN — Superadministrador. Solo SUPERADMIN ve y utiliza esta sección; la edición ordinaria de personas sigue disponible al personal según sus permisos.

Se retiraron el selector de rol y la creación duplicada desde Cuentas y permisos. El antiguo GET `/cuentas/nueva` redirige a Personas y POST `/cuentas` rechaza el alta con HTTP 403. PUT `/cuentas/{cuenta}` rechaza los campos de rol, vínculos o acceso, incluso si los envía un SUPERADMIN; conserva la modificación de credenciales y estado. El componente de actualización también impide recibir un cambio de rol desde el flujo de cuentas y toma el rol vigente después de bloquear el registro, para que una edición de credenciales no sobrescriba cambios realizados desde Personas.

Cuentas y permisos conserva su matriz individual, historial y administración de credenciales. Sus enlaces para asignar roles abren Editar persona, seleccionando la cuenta vinculada. Las personas sin cuenta conservan el proceso de creación explícita con contraseña confirmada; nunca reciben un rol por existir únicamente en `personas`.

**Problema adicional corregido:** el listado Personas identificaba las relaciones de lector y personal con etiquetas que podían confundirse con roles de acceso. Ahora diferencia esas relaciones de una columna «Rol de acceso», visible para SUPERADMIN, obtenida de las cuentas reales. Una relación de bibliotecario sin cuenta muestra «SIN CUENTA», sin aparentar privilegios administrativos.

**Archivos modificados en esta corrección:**

- `app/Http/Controllers/CuentaAccesoController.php`: cierre del alta duplicada y rechazo explícito de modificaciones de rol desde Cuentas.
- `app/Http/Controllers/PersonaController.php`: carga de cuentas para el listado administrativo sin mostrarlas al personal ordinario.
- `app/Support/AdministrarCuenta.php`: restricción del origen del cambio de rol y conservación del rol vigente en ediciones de credenciales.
- `resources/views/personas/acceso.blade.php`: título y etiquetas del ComboBox conforme al requisito corregido.
- `resources/views/personas/index.blade.php`: distinción entre relaciones y rol real de la cuenta.
- `resources/views/cuentas/formulario.blade.php`: eliminación del selector y alta; rol mostrado como texto y enlace a Personas.
- `resources/views/cuentas/index.blade.php`: sustitución de accesos duplicados por enlaces a Personas.
- `tests/Feature/SeguridadBibliotecaTest.php`: actualización de los flujos de prueba y nuevos casos de exclusividad, bloqueo y listado.
- `scripts/verificar-biblioteca.php`: revisión de todos los formularios actuales de personas y cuentas; el alta retirada ya no se trata como una pantalla HTTP 200.
- `INFORME_AUDITORIA_SAIDE.md`: registro del requisito vigente y sus verificaciones.

Se verificaron la asignación desde Personas y el login posterior con cada rol, la ocultación y rechazo de solicitudes del personal, consulta autorizada de Personas y Usuarios, preservación del historial, protección del último administrador y funcionamiento de los permisos individuales. También se probaron el rechazo del antiguo alta/cambio de rol desde Cuentas y la modificación legítima de credenciales sin alterar rol ni permisos.

Resultado final de PHPUnit: **26 pruebas, 472 aserciones, todas aprobadas** sobre SQLite `:memory:`. Los archivos PHP intervenidos pasan `php -l`; Pint aplicó el formato Laravel y `git diff --check` no reportó errores. Las 71 rutas del proyecto no tienen duplicados por método/URI.

La auditoría real de solo lectura devolvió **48 respuestas HTTP 200**: 13 pantallas generales, los 28 formularios actuales de Editar persona y los 7 de credenciales. Las 25 FK no tienen registros huérfanos y los conteos de tablas permanecieron iguales dentro de esta ejecución. No se ejecutaron escrituras de prueba en `biblioteca2`, migraciones ni cambios de esquema. Se mantiene `mejora-registro-roles`, con los cambios locales preservados, sin modificar `main` ni realizar push.
