# Panel del lector, catálogo e identificadores públicos — SAIDE Biblioteca

Fecha: 31 de octubre de 2026. Proyecto: `C:\xampp\htdocs\biblioteca3`. Rama de trabajo: `mejora-registro-roles`.

## Resultado implementado

El USUARIO inicia sesión en un espacio propio, separado de los layouts administrativos: bienvenida con su nombre, buscador, libros disponibles, categorías, catálogo de tarjetas, fichas bibliográficas, selección múltiple, solicitudes e historial de préstamos.

No se muestran IDs de libros, autores, categorías, editoriales, solicitudes, préstamos ni códigos de inventario al lector. Las fichas y acciones sobre libros utilizan UUID públicos; los filtros utilizan nombres. Los IDs y relaciones originales permanecen en MariaDB. Los administradores conservan sus tablas con ID y el diseño existente.

El catálogo permite buscar por título, autor, ISBN verificado, categoría y editorial; filtrar por categoría, editorial y disponibilidad; ordenar por título, autor o disponibilidad; y conservar filtros al paginar. La disponibilidad considera tanto el estado DISPONIBLE como la ausencia de un préstamo sin devolver para ese ejemplar.

Las tarjetas y fichas muestran los metadatos disponibles. Un ISBN no acreditado no se presenta como verificado. Las ediciones sin portada confirmada usan una alternativa local; no se les asigna la cubierta de otra edición.

## Diagnóstico y auditoría previa

Antes de incorporar registros se inspeccionaron libros, autores/personas, asociaciones, categorías, editoriales, ejemplares y relaciones con préstamos/devoluciones. Se generó este inventario de solo lectura:

`storage/app/private/auditorias/catalogo-20261031-144649.json`

Había 11 libros, sin ISBN duplicados. Los 11 valores previos de ISBN no pasan la validación de estructura/checksum ISBN-13. Esto acredita un problema en ese campo, **no que los libros sean ficticios**.

Se contrastaron las siguientes obras y sus autorías bibliográficas con fuentes editoriales o institucionales. No se acreditó la edición física de los ejemplares existentes, por lo que no se reemplazaron sus ISBN ni editoriales:

| Obra | Autoría contrastada | Fuente |
|---|---|---|
| Pedro Páramo | Juan Rulfo | [Instituto Cervantes](https://cervantes.org/es/sobre-nosotros/sala-prensa/notas-prensa/primera-edicion-pedro-paramo-juan-rulfo-libros-legados) |
| Rayuela | Julio Cortázar | [Penguin Libros](https://www.penguinlibros.com/es/literatura-contemporanea/33499-libro-rayuela-9788466331906) |
| El Aleph | Jorge Luis Borges | [Penguin Libros](https://www.penguinlibros.com/es/literatura-contemporanea/35941-libro-el-aleph-9788499089515) |
| La casa de los espíritus | Isabel Allende | [Planeta](https://www.planetadelibros.com.mx/libro-la-casa-de-los-espiritus/249330) |
| Como agua para chocolate | Laura Esquivel | [Planeta](https://www.planetadelibros.com.mx/seleccion-editorial/autores-laura-esquivel-libros) |
| El laberinto de la soledad | Octavio Paz | [FCE](https://www.fondodeculturaeconomica.com/Ficha/9786071668882/F) |
| La ciudad y los perros | Mario Vargas Llosa | [Penguin Libros](https://www.penguinlibros.com/es/literatura-contemporanea/7244-libro-la-ciudad-y-los-perros-9788420454054) |
| Aura | Carlos Fuentes | [Enciclopedia de la Literatura en México, catálogo de Era](https://www.elem.mx/institucion/editorial/83/1404/2) |
| Crónica de una muerte anunciada | Gabriel García Márquez | [Penguin Libros](https://www.penguinlibros.com/es/literatura-contemporanea/35988-libro-cronica-de-una-muerte-anunciada-9788497592437) |
| Balún Canán | Rosario Castellanos | [FCE](https://www.fondodeculturaeconomica.com/Ficha/9786071687937/F) |

El registro **La Prueba de Lectura**, con valor ISBN `1828GBF`, permanece sin correspondencia bibliográfica confirmada. No se declaró ficticio ni se eliminó. No tenía ejemplares ni detalles de préstamo en la auditoría.

Se corrigió la presentación ortográfica de títulos mediante fichas complementarias, sin reescribir las filas originales de libros ni los datos personales de los autores.

## Nuevos títulos y portadas reales

Se incorporaron de forma idempotente dos títulos que no estaban registrados:

| Título | Edición acreditada | Portada |
|---|---|---|
| La invención de Morel, Adolfo Bioy Casares | Debolsillo, 2022; ISBN 9788466360272. [Ficha del editor](https://www.penguinrandomhouse.com/books/717728/la-invencion-de-morel--the-invention-of-morel-by-adolfo-bioy-casares/9788466360272/) | [Open Library, edición por ISBN](https://openlibrary.org/isbn/9788466360272), cubierta 15224600 |
| El coronel no tiene quien le escriba, Gabriel García Márquez | Debolsillo, 2003; ISBN 9788497592352. [Ficha del editor](https://www.penguinlibros.com/es/literatura-contemporanea/35980-libro-el-coronel-no-tiene-quien-le-escriba-9788497592352) | [Open Library, edición por ISBN](https://openlibrary.org/isbn/9788497592352), cubierta 15182514 |

Se contrastaron los metadatos de ambas ediciones en Open Library y se inspeccionaron visualmente sus imágenes. Las sinopsis breves son resúmenes propios de las fichas editoriales, no reproducciones del texto de los libros.

Las portadas se enlazan al servicio oficial, con atribución en la ficha, conforme a sus [indicaciones de uso](https://openlibrary.org/dev/docs/api/covers). Se utilizan imágenes medianas, dimensiones reservadas, `object-fit`, carga diferida y alternativa local ante errores. No se realizan consultas de metadatos a servicios externos durante las peticiones del lector. Si la administración cambia el título o ISBN de un libro, se invalidan los metadatos y la portada anteriores, conservando su UUID, para evitar atribuir otra edición por error.

**No se añadieron ejemplares físicos a biblioteca2.** Ambos títulos nuevos figuran sin disponibilidad hasta que la biblioteca registre existencias reales. Los ejemplares utilizados en las pruebas pertenecen únicamente a SQLite aislado.

La presentación de eLibro se consultó como inspiración para búsqueda y organización bibliográfica; no se reutilizaron su identidad, código ni imágenes.

## Solicitudes, préstamos y seguridad

- La selección usa UUID públicos y se conserva en la sesión al navegar. Permite agregar, actualizar cantidades, quitar libros y revisar un resumen sin identificadores técnicos.
- Se exige confirmación antes del envío. Un token único persistido evita duplicar solicitudes por reintentos. Si falla cualquier detalle, la transacción revierte también el token y permite reintentar.
- Se mantienen los límites de 5 ejemplares y 3 por título, estado ACTIVO, disponibilidad, préstamos vencidos y cantidades acumuladas en préstamos activos y solicitudes abiertas. Toda validación se ejecuta en el servidor.
- La asignación utiliza el servicio existente de bibliotecarios autorizados, disponibles y con sesión operativa comprobada. Sin personal disponible, queda pendiente.
- Según el flujo solicitado en este documento, **aprobar crea inmediatamente un solo préstamo con todos sus detalles**, fecha actual, fecha límite indicada por el bibliotecario y actualización de ejemplares. La operación usa bloqueos y transacción; una solicitud atendida no se vuelve a aprobar ni rechazar.
- Solo BIBLIOTECARIO autorizado aprueba o rechaza. SUPERADMIN no interviene en esa operación. Se mantuvieron los estilos y componentes administrativos, ajustando únicamente el formulario de aprobación para solicitar la fecha límite en esa misma operación.
- Las aprobaciones antiguas sin préstamo, si existen, no se convierten automáticamente en préstamos retroactivos; conservan sus datos y muestran un aviso de revisión.
- Las consultas del lector se limitan a sus registros. Se muestran fecha, estado, respuesta, rechazo y acceso al historial, sin folios internos.
- Hay aviso dentro de la aplicación si cambian los estados desde la última consulta de esa página.
- `puede()` y el middleware impiden acceso administrativo al USUARIO aun si conserva casillas históricas adicionales. No se asignaron permisos individuales ni se alteraron las matrices vigentes de otros roles.

## Cambios de base de datos y respaldo

Se ejecutó exclusivamente `2026_10_31_000004_create_libro_fichas.php`, mediante el instalador dirigido y con respaldo previo verificado:

`storage/app/private/backups/biblioteca2-lector-20261031-144958.sql`

SHA-256: `4da8a5bdc02a4d196380cbdc4386a08a02256dedb90a13d174a9068fff612612`.

Tablas añadidas:

- `libro_fichas`: relación uno a uno con libros, UUID público único, metadatos verificados y procedencia.
- `solicitud_envios`: token de envío único y relación con la solicitud, para prevenir duplicados.

Se añadieron dos libros, sus asociaciones bibliográficas, un autor/persona nuevo y la editorial Debolsillo. Se reutilizaron autor y categoría ya existentes cuando correspondía. Los 11 libros originales y todas las filas previas se verificaron íntegras tras la instalación. La biblioteca conserva 14 ejemplares físicos; no se reescribieron préstamos, devoluciones ni solicitudes históricas.

No se cambiaron claves primarias ni se ejecutaron migraciones destructivas. No se agregaron índices a tablas históricas: se revisaron sus PK, UNIQUE ISBN y FK existentes. Los nuevos UUID y tokens tienen índices únicos.

## Rendimiento y accesibilidad

La paginación y el filtrado de libros se realizan en SQL. Se precargan fichas, autores/personas, categorías y editoriales; la disponibilidad se calcula en la consulta. No se carga toda la biblioteca para filtrar. La prueba de múltiples tarjetas comprueba que no se consulta un autor por tarjeta. El permiso de agregar a solicitudes se obtiene una vez por página.

Se conservan los permisos dinámicos entre peticiones, sin caché que retrase revocaciones. Los metadatos externos se conservan localmente en las fichas y no se consultan en cada visita.

Se incorporaron navegación por teclado, foco visible, enlace para saltar al contenido, etiquetas, textos alternativos, estados vacíos y adaptación a móvil/tableta/escritorio. Los avisos de Bootstrap funcionan también en el layout propio del lector.

## Pruebas y evidencia

- **54 pruebas PHP, 2003 aserciones, correctas**, exclusivamente en SQLite en memoria. Incluyen regresiones del registro, roles, autorización, administración y paginación, además de catálogo, UUID, filtros, metadatos, selección múltiple, privacidad, notificaciones, idempotencia y rollback.
- **3 pruebas JavaScript correctas**: matriz, resumen previo existente y portada alternativa/prevención de reenvíos del nuevo lector.
- **85 GET sobre MariaDB con respuesta 200**, incluyendo las 13 fichas públicas; **35 relaciones FK verificadas**, sin alterar conteos al consultar.
- Recorrido en navegador con base SQLite separada: login lector → catálogo → ficha → dos títulos/tres ejemplares → resumen → envío → login bibliotecario → disponibilidad/asignación → aprobación → préstamo con tres detalles → login lector → estado y préstamo privado.
- La aprobación se comprobó como un único registro de préstamo. Las pruebas bloquean una segunda aprobación, el rechazo posterior y la operación desde SUPERADMIN/USUARIO; también provocan fallos de detalles para comprobar rollback.
- Portadas reales cargadas y correspondientes a ambas ediciones. Se provocó una URL rota únicamente en SQLite para comprobar la sustitución por SVG y luego se restauró.
- Revisión visual a 1280 px, 768 px y 390 px, sin desbordamiento de la página. El menú móvil permite desplazamiento horizontal.
- Sin errores JavaScript inesperados. El único 404 provocado deliberadamente fue el de la portada de prueba. `git diff --check` correcto.

Capturas del entorno aislado, con persona ficticia:

- [Catálogo en escritorio](C:/xampp/htdocs/biblioteca3/storage/app/private/prueba-lector/catalogo-escritorio.jpg).
- [Catálogo en móvil](C:/xampp/htdocs/biblioteca3/storage/app/private/prueba-lector/catalogo-movil.jpg).

No se crearon usuarios, solicitudes ni préstamos ficticios en MariaDB para probar el flujo.

## Archivos creados y modificados

Todos están dentro de `C:\xampp\htdocs\biblioteca3`.

**Nuevos**

- `app/Http/Controllers/LectorController.php`
- `app/Models/LibroFicha.php`
- `app/Services/CatalogoLector.php`
- `database/migrations/2026_10_31_000004_create_libro_fichas.php`
- `database/data/bibliografia.php`
- `database/seeders/CatalogoVerificadoSeeder.php`
- `resources/views/layouts/lector.blade.php`
- `resources/views/lector/`: `inicio`, `explorar`, `tarjeta`, `ficha`, `bibliografia`, `bandeja`, `solicitudes` y `prestamos`, todas Blade.
- `public/assets/css/lector.css`
- `public/assets/js/lector.js`
- `public/assets/img/portada-generica.svg`
- `scripts/auditar-catalogo.php`
- `scripts/instalar-lector.php`
- `tests/Feature/LectorCatalogoTest.php`
- `tests/JavaScript/lector.test.cjs`
- Este informe.

**Existentes modificados**

- `routes/web.php`: rutas propias del lector con UUID.
- `bootstrap/app.php`, `AccesoController.php`, `RegistroPublicoController.php` y `CuentaAcceso.php`: inicio del lector y autorización coherente.
- `app/Http/Middleware/AutorizarBiblioteca.php`: navegación y acceso del lector, con compatibilidad mediante redirecciones de consultas anteriores.
- `app/Models/Libro.php`: creación de ficha pública para futuros registros y relación de metadatos.
- `app/Http/Controllers/LibroController.php`: conservar la eliminación administrativa autorizada mediante una transacción que también trata su ficha; si otra relación impide eliminar el libro, se revierte todo.
- `app/Http/Controllers/SolicitudPrestamoController.php`, `app/Services/SolicitarPrestamo.php` y `resources/views/solicitudes/index.blade.php`: aprobación automática y ausencia de doble contabilización de préstamos aprobados.
- `app/Support/PermisosBiblioteca.php`: permisos iniciales coherentes con el módulo de solicitudes ya instalado; no se reescribieron las matrices vigentes.
- `resources/views/acceso/sinpermisos.blade.php`: layout exclusivo del lector cuando corresponde.
- `scripts/verificar-biblioteca.php`: páginas públicas y ausencia de IDs.
- `tests/Feature/RbacYPaginacionTest.php`, `SeguridadBibliotecaTest.php`, `SolicitudesPrestamoTest.php` y `tests/Unit/PermisosBibliotecaTest.php`: expectativas y comprobaciones de los requisitos actuales.

No se modificaron `public/assets/css/app.css`, el layout administrativo ni la tabla administrativa del catálogo.

## Propuesta de revisión pendiente y límites

1. Cotejar la edición física de los 11 libros anteriores con su página legal o un registro bibliográfico inequívoco. Solo entonces completar ISBN verificado, editorial, año y portada correspondiente. Los ISBN incorrectos siguen preservados en la información original; no se sustituyeron por los de otra edición.
2. Revisar documentalmente La Prueba de Lectura. No hay evidencia suficiente para declararlo ficticio. Cualquier sustitución o eliminación requiere autorización explícita y revisión de relaciones.
3. No se borraron autores, personas ni asociaciones previas. Las diferencias de escritura de nombres y editoriales quedan para cotejo, evitando reescribir datos personales por una atribución bibliográfica.
4. Las portadas dependen de Open Library; ante caída o ausencia se muestra la alternativa local. No se garantizan imágenes reales para ediciones todavía no identificadas.
5. La búsqueda usa LIKE y paginación SQL. Un catálogo de escala mucho mayor requerirá medición y, si procede, búsqueda de texto completo; no se añadieron índices especulativos sin evidencia.

La rama continúa siendo `mejora-registro-roles`, con los cambios disponibles para revisión. No se modificó `main`, no se creó commit y no se ejecutó `git push`.
