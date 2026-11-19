# Inventario DEMO aleatorio y persistente

Verificado el 1 de noviembre de 2026 en biblioteca2 local de desarrollo. Rama mejora-registro-roles, sin modificar main ni realizar push o merge.

## Resultado real de MariaDB

| Concepto | Cantidad |
|---|---:|
| Libros del catálogo | 87 |
| Títulos DEMO procesados | 76 |
| Ejemplares existentes antes | 90 |
| Ejemplares nuevos insertados | 737 |
| Total de ejemplares después | 827 |
| Objetivos persistentes entre 1 y 20 | 76 |
| Registros originales excluidos | 11 |
| Códigos de inventario duplicados | 0 |
| Títulos con más de 20 copias conservados | 0 |

Los objetivos de los 76 títulos suman 813 copias DEMO. El total 827 incluye además las 14 copias anteriores de los títulos originales excluidos. El rango observado de objetivos es 1–20. Drácula quedó con 20, Dune con 15 y El principito con 17 copias.

## Procedimiento y protección del historial

Se guardó una sola cantidad objetivo, obtenida mediante random_int(1,20), para cada edición elegible. La tabla nueva inventario_demo_objetivos tiene id_libro como clave primaria y foránea, ISBN de control, objetivo, conteo inicial y fecha. MariaDB aplica además una restricción CHECK entre 1 y 20. Las ejecuciones posteriores reutilizan ese registro sin volver a sortearlo.

Se cuenta el total de copias existentes, incluidos todos sus estados. Se crea únicamente max(0, objetivo - existentes), dentro de una transacción y con bloqueo de los libros y las copias. Los códigos DEMO-PRUEBA-ISBN-secuencia se comprueban antes de insertar y la restricción única real de ejemplares los protege. Las nuevas copias usan el estado DISPONIBLE existente y la ubicación DEMOSTRACION - DATOS DE PRUEBA.

No se modificó ni eliminó ninguna copia previa. Una copia prestada cuenta para el total y conserva su estado. Si ya existen más de 20 copias, o más que el objetivo sorteado, se conservan y se informa; no se recorta inventario. Si una edición contiene copias sin identificación DEMO o con otra ubicación, se excluye de la ampliación. No se habilita este proceso en producción.

El instalador comprobó todos los registros anteriores contra su instantánea y comparó los hashes de todas las tablas entre la primera y la segunda ejecución. La segunda no creó copias ni cambió objetivos, fechas o registros históricos. Solo se ejecutó la migración incremental 2026_11_01_000008; no se ejecutaron migraciones destructivas.

## Respaldo previo verificado

Archivo: `C:/xampp/htdocs/biblioteca3/storage/app/private/backups/biblioteca2-inventario-aleatorio-20261101-174103.sql`.
SHA-256: `ca9cbe18d8c1a05413cf9fd4a9d0b028a2db6c6b7efe01db86a0ac804d874db7`.

El respaldo incluye estructura, conteos por tabla y comprobación de estabilidad de filas, con archivos .sha256 y .json asociados. El resultado completo de la operación está en storage/app/private/inventario-aleatorio/20261101-174112/resultado.json.

## Cantidad asignada por libro

| Libro | ISBN | Antes | Objetivo persistente | Creados | Total después | Disponibles en MariaDB |
|---|---|---:|---:|---:|---:|---:|
| La invención de Morel | 9788466360272 | 1 | 17 | 16 | 17 | 17 |
| El coronel no tiene quien le escriba | 9788497592352 | 1 | 3 | 2 | 3 | 3 |
| El llano en llamas | 9788493442613 | 1 | 8 | 7 | 8 | 8 |
| El gallo de oro | 9788492480180 | 1 | 1 | 0 | 1 | 1 |
| Bestiario | 9788466331845 | 1 | 20 | 19 | 20 | 20 |
| Final del juego | 9789871106578 | 1 | 20 | 19 | 20 | 20 |
| Historias de cronopios y de famas | 9789505111893 | 1 | 14 | 13 | 14 | 14 |
| Ficciones | 9789500428446 | 1 | 3 | 2 | 3 | 3 |
| El libro de arena | 9788423338719 | 1 | 18 | 17 | 18 | 18 |
| De amor y de sombra | 9788484507895 | 1 | 11 | 10 | 11 | 11 |
| La fiesta del Chivo | 9788466303316 | 1 | 5 | 4 | 5 | 5 |
| La casa verde | 9789707702493 | 1 | 19 | 18 | 19 | 19 |
| La muerte de Artemio Cruz | 9789875780361 | 1 | 6 | 5 | 6 | 6 |
| Cien años de soledad | 9786070714863 | 1 | 20 | 19 | 20 | 20 |
| El amor en los tiempos del cólera | 9788497592451 | 1 | 7 | 6 | 7 | 7 |
| Oficio de tinieblas | 9788493755904 | 1 | 1 | 0 | 1 | 1 |
| Orgullo y prejuicio | 9788415083696 | 1 | 16 | 15 | 16 | 16 |
| La tregua | 9788466322874 | 1 | 8 | 7 | 8 | 8 |
| El túnel | 9788432250026 | 1 | 6 | 5 | 6 | 6 |
| Los detectives salvajes | 9788433966636 | 1 | 16 | 15 | 16 | 16 |
| Las batallas en el desierto | 9789684114739 | 1 | 14 | 13 | 14 | 14 |
| Los recuerdos del porvenir | 9788492891146 | 1 | 11 | 10 | 11 | 11 |
| Cosmos | 9780394502946 | 1 | 17 | 16 | 17 | 17 |
| The Selfish Gene | 9780198788607 | 1 | 17 | 16 | 17 | 17 |
| Silent Spring | 9780395166116 | 1 | 19 | 18 | 19 | 19 |
| The Man Who Mistook His Wife for a Hat: And Other Clinical Tales | 9781101908310 | 1 | 13 | 12 | 13 | 13 |
| Code: The Hidden Language of Computer Hardware and Software | 9780735611313 | 1 | 6 | 5 | 6 | 6 |
| Automate the Boring Stuff with Python: Practical Programming for Total Beginners | 9781593279929 | 1 | 7 | 6 | 7 | 7 |
| The Linux Command Line: A Complete Introduction | 9781593273897 | 1 | 1 | 0 | 1 | 1 |
| A Short History of Nearly Everything | 9780552997041 | 1 | 8 | 7 | 8 | 8 |
| El infinito en un junco: La invención de los libros en el mundo antiguo | 9788417860790 | 1 | 1 | 0 | 1 | 1 |
| Frankenstein | 9780141439471 | 1 | 15 | 14 | 15 | 15 |
| Jane Eyre | 9780141441146 | 1 | 4 | 3 | 4 | 4 |
| Wuthering Heights | 9780141439556 | 1 | 11 | 10 | 11 | 11 |
| The Time Machine | 9780141439976 | 1 | 20 | 19 | 20 | 20 |
| The Picture of Dorian Gray | 9780141439570 | 1 | 9 | 8 | 9 | 9 |
| A Brief History of Time | 9780553380163 | 1 | 14 | 13 | 14 | 14 |
| On the Origin of Species | 9780140439120 | 1 | 10 | 9 | 10 | 10 |
| Clean Code: A Handbook of Agile Software Craftsmanship | 9780132350884 | 1 | 19 | 18 | 19 | 19 |
| Think Python: How to Think Like a Computer Scientist | 9781449330729 | 1 | 5 | 4 | 5 | 5 |
| Sapiens: A Brief History of Humankind | 9780062316097 | 1 | 5 | 4 | 5 | 5 |
| Meditations | 9780140449334 | 1 | 3 | 2 | 3 | 3 |
| The Design of Everyday Things | 9780465050659 | 1 | 17 | 16 | 17 | 17 |
| The Republic | 9780140455113 | 1 | 16 | 15 | 16 | 16 |
| El principito | 9788418797453 | 1 | 17 | 16 | 17 | 17 |
| Alicia en el país de las maravillas, Fantasmagoría y Un cuento enredado | 9788484034094 | 1 | 6 | 5 | 6 | 6 |
| Pinocho | 9788449427329 | 1 | 17 | 16 | 17 | 17 |
| Peter Pan en los jardines de Kensington | 9788439216131 | 1 | 9 | 8 | 9 | 9 |
| Cuentos de los hermanos Grimm | 9788439216278 | 1 | 17 | 16 | 17 | 17 |
| Frankenstein | 9788445019795 | 1 | 1 | 0 | 1 | 1 |
| El corazón delator y otros cuentos | 9789875506503 | 1 | 10 | 9 | 10 | 10 |
| La caída de la casa Usher y otros cuentos | 9789500396318 | 1 | 14 | 13 | 14 | 14 |
| Viaje al centro de la Tierra | 9789500285971 | 1 | 2 | 1 | 2 | 2 |
| Fahrenheit 451 | 9788445006771 | 1 | 2 | 1 | 2 | 2 |
| Dune | 9788497596824 | 1 | 15 | 14 | 15 | 15 |
| Los tres mosqueteros, vol. I | 9788447148592 | 1 | 4 | 3 | 4 | 4 |
| El conde de Montecristo, 1 | 9788420739540 | 1 | 7 | 6 | 7 | 7 |
| Rimas y leyendas | 9788467021479 | 1 | 6 | 5 | 6 | 6 |
| Don Juan Tenorio | 9788470396946 | 1 | 10 | 9 | 10 | 10 |
| La vida es sueño | 9788497590624 | 1 | 12 | 11 | 12 | 12 |
| Platero y yo | 9788426138149 | 1 | 5 | 4 | 5 | 5 |
| La Celestina | 9781589770119 | 1 | 8 | 7 | 8 | 8 |
| Otra vuelta de tuerca | 9788491043225 | 1 | 4 | 3 | 4 | 4 |
| El extraño caso del doctor Jekyll y el señor Hyde | 9788490650615 | 1 | 15 | 14 | 15 | 15 |
| Estudio en escarlata | 9788420665696 | 1 | 13 | 12 | 13 | 13 |
| El sabueso de los Baskerville | 9788420665689 | 1 | 12 | 11 | 12 | 12 |
| El asesinato de Roger Ackroyd | 9788492966660 | 1 | 9 | 8 | 9 | 9 |
| Asesinato en el Orient Express | 9788478712090 | 1 | 18 | 17 | 18 | 18 |
| La guerra de los mundos | 9788413621395 | 1 | 11 | 10 | 11 | 11 |
| La máquina del tiempo | 9788415988762 | 1 | 11 | 10 | 11 | 11 |
| Un mundo feliz | 9788484502418 | 1 | 11 | 10 | 11 | 11 |
| Mujercitas | 9788418395758 | 1 | 16 | 15 | 16 | 16 |
| La isla del tesoro | 9788491050889 | 1 | 14 | 13 | 14 | 14 |
| El mago de Oz | 9788420608563 | 1 | 8 | 7 | 8 | 8 |
| Drácula | 9788491819103 | 1 | 20 | 19 | 20 | 20 |
| Don Quijote de la Mancha | 9788420412146 | 1 | 8 | 7 | 8 | 8 |

## Títulos excluidos

Estos registros quedan fuera de los manifiestos de demostración y conservan sus 14 copias históricas. No se les generó objetivo aleatorio.

- Pedro Paramo: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- Rayuela: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- El Aleph: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- La casa de los espiritus: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- Como agua para chocolate: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- El laberinto de la soledad: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- La ciudad y los perros: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- Aura: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- Cronica de una muerte anunciada: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- Balun Canan: Fuera de los manifiestos de demostración; se conserva el inventario histórico.
- La Prueba de Lectura: Fuera de los manifiestos de demostración; se conserva el inventario histórico.

## Solicitudes, aprobación y devolución

- PHPUnit: **69 pruebas, 2859 aserciones correctas**. Se comprueban objetivos aleatorios válidos, persistencia incluso dos días después, cero duplicados, rechazo de producción, conservación de un título con 21 copias y exclusión de inventario sin marca DEMO.
- Prueba de préstamo automatizada: tres copias de un título y dos de otro, cinco detalles físicos distintos en un préstamo. Se rechazan cuatro por título; las copias prestadas no se reactivan al repetir el seeder; al devolver las cinco se recupera su disponibilidad y permanecen los mismos objetivos.
- Navegador con cuentas ficticias en SQLite, separado de MariaDB: USUARIO solicita Drácula ×3 y Dune ×2; BIBLIOTECARIO aprueba; aparece un único préstamo con cinco detalles. La disponibilidad pasa de 20/15 a 17/13. Consulta SQL del entorno aislado confirma los cinco detalles y los estados. Sin errores de consola.
- Verificador real de catálogo: las 76 fichas responden HTTP 200 y sus botones coinciden con el stock disponible del backend.
- Auditoría real: 87 libros, 76 objetivos, 827 ejemplares, cero códigos duplicados, 13 préstamos históricos y 16 detalles históricos conservados.
- Pint y git diff --check correctos.

Las pruebas de solicitud se mantuvieron aisladas para no añadir préstamos ficticios al historial de MariaDB. La ampliación de inventario y sus objetivos sí están ejecutados y verificados en MariaDB. La disponibilidad sigue calculándose desde la base de datos, con los estados y detalles de préstamos existentes; no se escribieron cantidades en Blade.

## Archivos modificados o añadidos

- database/seeders/EjemplaresDemostracionSeeder.php: nuevo método completarCantidadesAleatorias; se reutiliza el seeder existente y se conserva el método anterior.
- database/migrations/2026_11_01_000008_create_inventario_demo_objetivos.php: objetivos persistentes, relación por id_libro y restricciones.
- scripts/completar-inventario-aleatorio.php: validación de entorno, respaldo, migración dirigida, ejecución, guardas de integridad e idempotencia e informe.
- scripts/verificar-inventario-aleatorio.php: auditoría SQL de solo lectura de objetivos, cantidades y unicidad.
- tests/Feature/LectorCatalogoTest.php: tres pruebas nuevas del inventario aleatorio y préstamos de varias copias.
- INFORME_INVENTARIO_ALEATORIO.md e INVENTARIO_ALEATORIO_POR_LIBRO.csv: informe y cantidades asignadas.

No se modificaron vistas, CSS, menús, botones, portadas, favoritos, lectura digital ni paneles administrativos.

## Reejecución

```text
php scripts/completar-inventario-aleatorio.php
php scripts/completar-inventario-aleatorio.php --apply --development
php scripts/verificar-inventario-aleatorio.php
```

La primera orden solo muestra el alcance. La segunda respalda antes de escribir y conserva los objetivos ya sorteados; no vuelve a agregar de 1 a 20 sobre las existencias. La tercera es de solo lectura.

Evidencias privadas: inventario-aleatorio/pruebas.txt, verificacion.json, auditoria-final.json y prestamo-cinco-copias.jpg, bajo storage/app/private.
