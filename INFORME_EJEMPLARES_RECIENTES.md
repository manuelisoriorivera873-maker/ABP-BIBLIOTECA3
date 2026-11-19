# Corrección de ejemplares de los títulos recientes

Verificación completada el 1 de noviembre de 2026. Rama: mejora-registro-roles. Sin push ni merge, sin cambios a main ni al diseño.

## Causa y corrección

El catálogo contenía las últimas 32 ediciones, pero el seeder de inventario de demostración solo enumeraba los 44 títulos de las etapas anteriores. No era un bloqueo visual ni un error de autorización: no existían copias físicas que pudieran solicitarse.

Se agregó lectura-catalogo.json al alcance del seeder existente (76 títulos revisados). El instalador comprobó que los 76 ISBN estuvieran registrados, que no se repitieran en los manifiestos y que sus títulos coincidieran. Se insertó una copia únicamente cuando el libro no tenía ninguna. Cada libro se bloquea dentro de la transacción; una segunda ejecución no modifica datos. No se reactiva ni reemplaza ninguna copia existente. Los manifiestos no instalados se omiten al ejecutar el seeder solo; el instalador exige el conjunto completo antes de aplicarlo a esta base.

## Resultado real en MariaDB

| Comprobación | Resultado |
|---|---:|
| Libros registrados | 87 |
| Títulos recientes revisados | 76 |
| Títulos recientes sin ejemplares antes | 32 |
| Ejemplares DEMO insertados realmente | 32 |
| Total de ejemplares después | 90 |
| Títulos recientes disponibles después | 76 |
| Títulos disponibles en todo el catálogo | 81 |
| Títulos recientes sin ejemplares después | 0 |

Se conservaron todos los registros anteriores y se compararon los hashes de todas las tablas entre la primera y segunda ejecución. Las únicas tablas con inserciones autorizadas fueron ejemplares y ubicaciones_libros; en esta ocasión se reutilizó la ubicación de demostración existente.

Estado real usado: estados_ejemplar.id_estado_ejemplar = 1, DISPONIBLE. Ubicación real: id_ubicacion = 11, DEMOSTRACION - DATOS DE PRUEBA. Los códigos DEMO-PRUEBA-ISBN-01 y la ubicación dejan claro que no es inventario físico verificado. No se crearon nuevos estados ni se cambiaron claves primarias.

El verificador consultó SQL y las 76 fichas HTTP del lector: todas respondieron 200 y los botones coincidieron con la disponibilidad calculada por el backend. No se modificó ninguna vista, estilo, botón, portada, favorito ni apartado de lectura digital.

Pulgarcito no existe actualmente en libros (0 coincidencias). En la etapa bibliográfica quedó pendiente determinar su edición/adaptación; no se inventó un libro ni un ISBN para generar una copia. Los otros 32 títulos incorporados sí tienen su copia.

## Respaldo

Archivo completo previo a las inserciones: `C:/xampp/htdocs/biblioteca3/storage/app/private/backups/biblioteca2-ejemplares-demo-20261031-202122.sql`.
SHA-256: `17a4112c88646810d1ba08b8d036c8474f7148214aa00b08cb0078ef7fc2f051`.

Informe técnico con esquema, conteos y registros previos: `storage/app/private/ejemplares-demo/20261031-202122/resultado.json`. Incluye la lista exacta de las 32 filas creadas. La ejecución se interrumpió después de completarse; al reanudar se comprobó el resultado antes de continuar y no se repitieron inserciones.

## Ejemplares insertados

| Libro | id_libro | id_ejemplar | Código | Estado |
|---|---:|---:|---|---|
| El principito | 56 | 59 | DEMO-PRUEBA-9788418797453-01 | DISPONIBLE |
| Alicia en el país de las maravillas, Fantasmagoría y Un cuento enredado | 57 | 60 | DEMO-PRUEBA-9788484034094-01 | DISPONIBLE |
| Pinocho | 58 | 61 | DEMO-PRUEBA-9788449427329-01 | DISPONIBLE |
| Peter Pan en los jardines de Kensington | 59 | 62 | DEMO-PRUEBA-9788439216131-01 | DISPONIBLE |
| Cuentos de los hermanos Grimm | 60 | 63 | DEMO-PRUEBA-9788439216278-01 | DISPONIBLE |
| Frankenstein | 61 | 64 | DEMO-PRUEBA-9788445019795-01 | DISPONIBLE |
| El corazón delator y otros cuentos | 62 | 65 | DEMO-PRUEBA-9789875506503-01 | DISPONIBLE |
| La caída de la casa Usher y otros cuentos | 63 | 66 | DEMO-PRUEBA-9789500396318-01 | DISPONIBLE |
| Viaje al centro de la Tierra | 64 | 67 | DEMO-PRUEBA-9789500285971-01 | DISPONIBLE |
| Fahrenheit 451 | 65 | 68 | DEMO-PRUEBA-9788445006771-01 | DISPONIBLE |
| Dune | 66 | 69 | DEMO-PRUEBA-9788497596824-01 | DISPONIBLE |
| Los tres mosqueteros, vol. I | 67 | 70 | DEMO-PRUEBA-9788447148592-01 | DISPONIBLE |
| El conde de Montecristo, 1 | 68 | 71 | DEMO-PRUEBA-9788420739540-01 | DISPONIBLE |
| Rimas y leyendas | 69 | 72 | DEMO-PRUEBA-9788467021479-01 | DISPONIBLE |
| Don Juan Tenorio | 70 | 73 | DEMO-PRUEBA-9788470396946-01 | DISPONIBLE |
| La vida es sueño | 71 | 74 | DEMO-PRUEBA-9788497590624-01 | DISPONIBLE |
| Platero y yo | 72 | 75 | DEMO-PRUEBA-9788426138149-01 | DISPONIBLE |
| La Celestina | 73 | 76 | DEMO-PRUEBA-9781589770119-01 | DISPONIBLE |
| Otra vuelta de tuerca | 74 | 77 | DEMO-PRUEBA-9788491043225-01 | DISPONIBLE |
| El extraño caso del doctor Jekyll y el señor Hyde | 75 | 78 | DEMO-PRUEBA-9788490650615-01 | DISPONIBLE |
| Estudio en escarlata | 76 | 79 | DEMO-PRUEBA-9788420665696-01 | DISPONIBLE |
| El sabueso de los Baskerville | 77 | 80 | DEMO-PRUEBA-9788420665689-01 | DISPONIBLE |
| El asesinato de Roger Ackroyd | 78 | 81 | DEMO-PRUEBA-9788492966660-01 | DISPONIBLE |
| Asesinato en el Orient Express | 79 | 82 | DEMO-PRUEBA-9788478712090-01 | DISPONIBLE |
| La guerra de los mundos | 80 | 83 | DEMO-PRUEBA-9788413621395-01 | DISPONIBLE |
| La máquina del tiempo | 81 | 84 | DEMO-PRUEBA-9788415988762-01 | DISPONIBLE |
| Un mundo feliz | 82 | 85 | DEMO-PRUEBA-9788484502418-01 | DISPONIBLE |
| Mujercitas | 83 | 86 | DEMO-PRUEBA-9788418395758-01 | DISPONIBLE |
| La isla del tesoro | 84 | 87 | DEMO-PRUEBA-9788491050889-01 | DISPONIBLE |
| El mago de Oz | 85 | 88 | DEMO-PRUEBA-9788420608563-01 | DISPONIBLE |
| Drácula | 86 | 89 | DEMO-PRUEBA-9788491819103-01 | DISPONIBLE |
| Don Quijote de la Mancha | 87 | 90 | DEMO-PRUEBA-9788420412146-01 | DISPONIBLE |

## Prueba funcional completa

Se usó una base SQLite separada en storage/app/private/ejemplares-recientes/catalogo-ejemplares-recientes.sqlite, con cuentas ficticias. Las inserciones de ejemplares sí se hicieron en MariaDB; el préstamo de prueba se mantuvo aislado para no mezclarlo con el historial existente.

1. Login como USUARIO y comprobación del botón habilitado de Drácula y Dune.
2. Selección de un ejemplar de cada libro y envío de una sola solicitud.
3. Login como BIBLIOTECARIO; la solicitud apareció pendiente y se asignó al activar la recepción.
4. Aprobación con fecha límite 2026-11-08. Resultado: un préstamo y dos registros en detalle_prestamo, sin confirmación adicional de entrega.
5. Comprobación en el módulo Préstamos y en Mis préstamos del lector.
6. Ambas copias quedaron PRESTADO; los botones de solicitud quedaron deshabilitados por falta de disponibilidad.
7. Consulta SQL del archivo de prueba: un préstamo, dos detalles y cero copias disponibles de esos dos títulos. Consola del navegador sin errores.

PHPUnit: **66 pruebas y 2817 aserciones correctas**. La nueva prueba verifica las 32 copias, la idempotencia, la conservación de las anteriores, la solicitud conjunta, la aprobación única (segundo intento HTTP 409), que el seeder no reactive copias prestadas y la recuperación de disponibilidad al devolver. Las pruebas existentes conservan los límites de cinco ejemplares y tres por título y el control de autorización.

La auditoría final de MariaDB mantiene 13 préstamos y 16 detalles de préstamo. El historial de devoluciones y todos los registros previos se verificaron contra el respaldo de esta ejecución; no se sustituyeron por los conteos de informes históricos anteriores.

Evidencias: auditoria-final.json, prestamo-aprobado.jpg y mis-prestamos.jpg en storage/app/private/ejemplares-recientes. PHPUnit: storage/app/private/ejemplares-demo/pruebas-recientes.txt.

## Archivos modificados

- database/seeders/EjemplaresDemostracionSeeder.php: incluye los 32 títulos recientes y conserva la creación idempotente solo para libros registrados sin copias.
- scripts/instalar-ejemplares-demo.php: guardas de entorno, respaldo, transacción con comprobación de filas históricas, comparación de idempotencia e informes por ejecución con las filas nuevas.
- scripts/verificar-ejemplares-demo.php: alcance dinámico de 76 títulos y comprobación de botones según disponibilidad real, sin asumir que una copia prestada debe reactivarse.
- scripts/verificar-lectura.php: elimina la antigua suposición de que todos los títulos de esta ampliación deben tener cero ejemplares; conserva los demás controles digitales.
- tests/Feature/LectorCatalogoTest.php: regresión de las últimas 32 copias y flujo conjunto Drácula/Dune.
- INFORME_EJEMPLARES_RECIENTES.md: este informe.

## Reejecución segura

```text
php scripts/instalar-ejemplares-demo.php
php scripts/instalar-ejemplares-demo.php --apply --development
php scripts/verificar-ejemplares-demo.php
```

La primera orden muestra alcance y faltantes sin insertar. La segunda exige biblioteca2 local con entorno local/development, crea un respaldo y ejecuta la transacción. Si ya hay una copia, se conserva tal como está: no se crean duplicados ni se altera su estado.
