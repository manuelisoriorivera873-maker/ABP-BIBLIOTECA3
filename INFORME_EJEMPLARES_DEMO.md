# SAIDE Biblioteca — ejemplares de demostración

Aplicado en MariaDB `biblioteca2`, entorno `local`, el 31/10/2026. Se crearon registros en la base de desarrollo, no solamente en una base de pruebas.

**Resultado**

- Se revisaron los 44 títulos incorporados en las tres ampliaciones anteriores: 2 + 15 + 27. Los 44 carecían de ejemplares.
- Se creó un ejemplar disponible por título: 44 filas nuevas en ejemplares. Total de registros: 14 anteriores + 44 DEMO = 58.
- Códigos únicos `DEMO-PRUEBA-<ISBN>-01`. Ubicación dedicada `DEMOSTRACION - DATOS DE PRUEBA`, con descripción de inventario simulado sin validación física.
- Se reutilizó el estado DISPONIBLE existente, obtenido por nombre (ID real 1). La ubicación de demostración recibió su ID de MariaDB (11). No se inventaron IDs ni se crearon estados.
- Los 14 ejemplares anteriores y todas las filas previas de libros, personas, préstamos, devoluciones, cuentas y permisos permanecieron intactos.
- Una segunda ejecución dentro del instalador no cambió ninguna tabla ni generó duplicados.

Los ejemplares DEMO representan datos de desarrollo: no acreditan posesión física ni ubicación de una biblioteca real. Su código y ubicación los distinguen en los módulos administrativos existentes. La aplicación conserva el diseño y la presentación de disponibilidad actuales.

**Esquema y procedimiento**

Se inspeccionaron las definiciones actuales de libros, ejemplares, estados_ejemplar, ubicaciones_libros, prestamos y detalle_prestamo. Ejemplares requiere código de inventario (máximo 40 caracteres), libro, ubicación y estado; su código tiene índice único y sus tres relaciones tienen claves foráneas. [Esquema inspeccionado](C:/xampp/htdocs/biblioteca3/storage/app/private/ejemplares-demo/esquema.json).

El seeder se limita a entornos local, development o testing. El instalador exige biblioteca2 en MySQL local, entorno de desarrollo y las opciones explícitas --apply --development. No se cambiaron tablas ni claves primarias. La creación usa una transacción y bloquea cada libro antes de comprobar sus copias. Si ya existe cualquier ejemplar, se omite ese título, conservando sus estados; no se reactiva un ejemplar prestado ni se crean copias adicionales para sustituirlo. Un ISBN o título en conflicto aborta la operación.

El instalador respalda antes de escribir, compara todas las filas previas después de la carga y permite nuevas filas únicamente en ejemplares y en la ubicación de prueba. Luego compara hashes de todas las tablas antes y después de repetir el seeder.

Para revisar el plan sin insertar:

```powershell
php scripts/instalar-ejemplares-demo.php
```

Para repetir la carga idempotente en desarrollo:

```powershell
php scripts/instalar-ejemplares-demo.php --apply --development
```

**Respaldo verificable**

[storage/app/private/backups/biblioteca2-ejemplares-demo-20261031-183137.sql](C:/xampp/htdocs/biblioteca3/storage/app/private/backups/biblioteca2-ejemplares-demo-20261031-183137.sql).

SHA-256: `959954bad0927a0cb7f02f18597c069b6e4b69d15cd131bd411fd5f7a89ed705`. El procedimiento comprobó estructura, número de filas respaldadas por tabla, estabilidad durante el respaldo y checksum. No se ejecutaron migrate:fresh, migrate:refresh, eliminaciones ni reinicios de identificadores.

**Registros creados en biblioteca2**

Los identificadores de esta tabla son para la auditoría administrativa; no se añadieron a las vistas del lector. Todos los siguientes registros quedaron DISPONIBLES y relacionados con su libro.

| ID libro | Título | ID ejemplar | Código de inventario | Disponibles |
|---:|---|---:|---|---:|
| 12 | La invención de Morel | 15 | DEMO-PRUEBA-9788466360272-01 | 1 |
| 13 | El coronel no tiene quien le escriba | 16 | DEMO-PRUEBA-9788497592352-01 | 1 |
| 14 | El llano en llamas | 17 | DEMO-PRUEBA-9788493442613-01 | 1 |
| 15 | El gallo de oro | 18 | DEMO-PRUEBA-9788492480180-01 | 1 |
| 16 | Bestiario | 19 | DEMO-PRUEBA-9788466331845-01 | 1 |
| 17 | Final del juego | 20 | DEMO-PRUEBA-9789871106578-01 | 1 |
| 18 | Historias de cronopios y de famas | 21 | DEMO-PRUEBA-9789505111893-01 | 1 |
| 19 | Ficciones | 22 | DEMO-PRUEBA-9789500428446-01 | 1 |
| 20 | El libro de arena | 23 | DEMO-PRUEBA-9788423338719-01 | 1 |
| 21 | De amor y de sombra | 24 | DEMO-PRUEBA-9788484507895-01 | 1 |
| 22 | La fiesta del Chivo | 25 | DEMO-PRUEBA-9788466303316-01 | 1 |
| 23 | La casa verde | 26 | DEMO-PRUEBA-9789707702493-01 | 1 |
| 24 | La muerte de Artemio Cruz | 27 | DEMO-PRUEBA-9789875780361-01 | 1 |
| 25 | Cien años de soledad | 28 | DEMO-PRUEBA-9786070714863-01 | 1 |
| 26 | El amor en los tiempos del cólera | 29 | DEMO-PRUEBA-9788497592451-01 | 1 |
| 27 | Oficio de tinieblas | 30 | DEMO-PRUEBA-9788493755904-01 | 1 |
| 28 | Orgullo y prejuicio | 31 | DEMO-PRUEBA-9788415083696-01 | 1 |
| 29 | La tregua | 32 | DEMO-PRUEBA-9788466322874-01 | 1 |
| 30 | El túnel | 33 | DEMO-PRUEBA-9788432250026-01 | 1 |
| 31 | Los detectives salvajes | 34 | DEMO-PRUEBA-9788433966636-01 | 1 |
| 32 | Las batallas en el desierto | 35 | DEMO-PRUEBA-9789684114739-01 | 1 |
| 33 | Los recuerdos del porvenir | 36 | DEMO-PRUEBA-9788492891146-01 | 1 |
| 34 | Cosmos | 37 | DEMO-PRUEBA-9780394502946-01 | 1 |
| 35 | The Selfish Gene | 38 | DEMO-PRUEBA-9780198788607-01 | 1 |
| 36 | Silent Spring | 39 | DEMO-PRUEBA-9780395166116-01 | 1 |
| 37 | The Man Who Mistook His Wife for a Hat: And Other Clinical Tales | 40 | DEMO-PRUEBA-9781101908310-01 | 1 |
| 38 | Code: The Hidden Language of Computer Hardware and Software | 41 | DEMO-PRUEBA-9780735611313-01 | 1 |
| 39 | Automate the Boring Stuff with Python: Practical Programming for Total Beginners | 42 | DEMO-PRUEBA-9781593279929-01 | 1 |
| 40 | The Linux Command Line: A Complete Introduction | 43 | DEMO-PRUEBA-9781593273897-01 | 1 |
| 41 | A Short History of Nearly Everything | 44 | DEMO-PRUEBA-9780552997041-01 | 1 |
| 42 | El infinito en un junco: La invención de los libros en el mundo antiguo | 45 | DEMO-PRUEBA-9788417860790-01 | 1 |
| 43 | Frankenstein | 46 | DEMO-PRUEBA-9780141439471-01 | 1 |
| 44 | Jane Eyre | 47 | DEMO-PRUEBA-9780141441146-01 | 1 |
| 45 | Wuthering Heights | 48 | DEMO-PRUEBA-9780141439556-01 | 1 |
| 46 | The Time Machine | 49 | DEMO-PRUEBA-9780141439976-01 | 1 |
| 47 | The Picture of Dorian Gray | 50 | DEMO-PRUEBA-9780141439570-01 | 1 |
| 48 | A Brief History of Time | 51 | DEMO-PRUEBA-9780553380163-01 | 1 |
| 49 | On the Origin of Species | 52 | DEMO-PRUEBA-9780140439120-01 | 1 |
| 50 | Clean Code: A Handbook of Agile Software Craftsmanship | 53 | DEMO-PRUEBA-9780132350884-01 | 1 |
| 51 | Think Python: How to Think Like a Computer Scientist | 54 | DEMO-PRUEBA-9781449330729-01 | 1 |
| 52 | Sapiens: A Brief History of Humankind | 55 | DEMO-PRUEBA-9780062316097-01 | 1 |
| 53 | Meditations | 56 | DEMO-PRUEBA-9780140449334-01 | 1 |
| 54 | The Design of Everyday Things | 57 | DEMO-PRUEBA-9780465050659-01 | 1 |
| 55 | The Republic | 58 | DEMO-PRUEBA-9780140455113-01 | 1 |

**Verificación de disponibilidad y permisos**

El verificador consultó los 44 títulos mediante el mismo servicio de catálogo que utiliza el lector: cada uno tenía exactamente un ejemplar prestable. Renderizó sus 44 fichas con una cuenta USUARIO activa, obtuvo HTTP 200 y comprobó que el botón Agregar a mi solicitud no tuviera disabled. [Resultado por título](C:/xampp/htdocs/biblioteca3/storage/app/private/ejemplares-demo/verificacion.json).

Los permisos y reglas del sistema no se modificaron. Se conservan el máximo de cinco ejemplares por préstamo, tres por título, el estado permitido del lector y las demás validaciones de préstamos y solicitudes. No se añadió ninguna cuenta a biblioteca2.

**Pruebas realizadas**

- PHP: 59 pruebas, 2443 aserciones correctas. Incluyen la nueva carga de 44 DEMO, su repetición, rechazo en entorno production, solicitud del lector, aprobación por bibliotecario, préstamo con detalle, reducción de disponibilidad, devolución y recuperación de disponibilidad.
- El seeder se repitió con el ejemplar de la prueba en PRESTADO: no lo reactivó ni creó otra copia.
- Las pruebas existentes de límites, autorización, privacidad, duplicados y catálogo continúan pasando.
- Navegador sobre SQLite separado, con el mismo catálogo y el mismo seeder: inicio como USUARIO, búsqueda de La tregua, un ejemplar disponible y botón habilitado, selección, envío y estado PENDIENTE. Inicio como BIBLIOTECARIO, recepción operativa, aprobación y préstamo vinculado.
- Consulta SQL de esa prueba después de aprobar: un préstamo, un detalle, estado PRESTADO y disponibilidad cero.
- La herramienta del navegador se bloqueó al intentar completar el diálogo de confirmación de devolución. No se modificó el formulario para evitarlo. La devolución se completó y verificó mediante una petición de integración al mismo endpoint y controlador, con autorización de BIBLIOTECARIO, en esa misma base SQLite: HTTP 302, un movimiento de devolución, estado DISPONIBLE y disponibilidad uno.
- El ciclo completo también pasó por HTTP en la suite automatizada. Los movimientos de esta prueba quedaron en SQLite aislado, no se añadieron a los históricos de biblioteca2.

| Comprobación del ciclo aislado | Tras aprobar | Tras devolver |
|---|---:|---:|
| Préstamos | 1 | 1 |
| Detalles | 1 | 1 |
| Devoluciones | 0 | 1 |
| Disponibilidad del título | 0 | 1 |
| Estado | PRESTADO | DISPONIBLE |

[Comprobación después del préstamo](C:/xampp/htdocs/biblioteca3/storage/app/private/ejemplares-demo/prueba-prestado.json) · [Comprobación después de la devolución](C:/xampp/htdocs/biblioteca3/storage/app/private/ejemplares-demo/prueba-devuelto.json)

**Archivos de esta tarea**

- [database/seeders/EjemplaresDemostracionSeeder.php](C:/xampp/htdocs/biblioteca3/database/seeders/EjemplaresDemostracionSeeder.php)
- [scripts/instalar-ejemplares-demo.php](C:/xampp/htdocs/biblioteca3/scripts/instalar-ejemplares-demo.php)
- [scripts/verificar-ejemplares-demo.php](C:/xampp/htdocs/biblioteca3/scripts/verificar-ejemplares-demo.php)
- [tests/Feature/LectorCatalogoTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/LectorCatalogoTest.php)
- [INFORME_EJEMPLARES_DEMO.md](C:/xampp/htdocs/biblioteca3/INFORME_EJEMPLARES_DEMO.md)

No se editaron vistas, CSS, JavaScript ni layouts administrativos. No se alteró el proceso existente de préstamos. Se preservaron los cambios pendientes anteriores del repositorio. Los informes previos describen cortes anteriores, cuando todavía no se habían autorizado estos ejemplares de demostración.

**Estado Git y evidencia**

Rama `mejora-registro-roles`; sin commit, push ni merge y sin modificaciones a main. Los respaldos, SQLite y evidencias permanecen en storage/app/private, fuera de Git.

[Lector con ejemplar disponible y botón habilitado en la prueba aislada](C:/xampp/htdocs/biblioteca3/storage/app/private/ejemplares-demo/disponible-prueba.jpg)
