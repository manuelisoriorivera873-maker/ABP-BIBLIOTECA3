# Lectura digital, favoritos y catálogo en español

Implementado en la rama `mejora-registro-roles`, sobre el proyecto existente. No se ejecutó push ni merge y no se modificó main. Se conservaron los cambios anteriores.

## Resultado instalado en desarrollo

- 32 ediciones españolas incorporadas: 30 obras nuevas y dos ediciones españolas de obras ya presentes en inglés (Frankenstein y La máquina del tiempo). Las ediciones inglesas, sus ISBN y sus ejemplares se conservan.
- 25 autores añadidos con biografías y referencias; los hermanos Grimm tienen registros separados y comparten su libro.
- Catálogo total: 87 libros, 64 autores; 55 ediciones identificadas en español, 21 en inglés y 11 ediciones históricas pendientes de determinar.
- 32 portadas nuevas contrastadas visualmente. Se descartaron páginas interiores, contraportadas y cubiertas ajenas.
- No se crearon ejemplares físicos para estos títulos. Se conservan los 58 existentes, incluidos los 44 DEMO de la etapa anterior.
- Préstamos: 13; detalle_prestamo: 16; devoluciones: 6; detalle_devolucion: 7. Cuentas: 10 y permisos centrales: 41, conservados.

## Favoritos y autorización

Estrella pequeña (aproximadamente 43 px en la comprobación de escritorio), usando los estilos de botones y una utilidad Bootstrap existente. PUT y DELETE asíncronos con CSRF, confirmación y recuperación ante error. La clave compuesta usuario/libro impide duplicados. La cuenta autenticada determina el lector: no se acepta asignar favoritos ni avances a otra persona. Mis favoritos utiliza las tarjetas existentes y persiste después del cierre de sesión. No reserva inventario ni crea préstamos.

Las rutas nuevas utilizan auth + biblioteca y el permiso central libros/ver. El controlador vuelve a comprobar rol USUARIO y relación de lector. Las rutas administrativas y la aprobación bibliotecaria conservan su autorización. No se asignaron permisos individuales. Los cambios de permisos se consultan en cada petición.

## Recursos digitales habilitados

| Libro | Acceso | Versión y condiciones |
|---|---|---|
| Don Quijote de la Mancha | integrado / EPUB | [Don Quijote · Project Gutenberg, eBook 2000 (texto original español; sin notas de la RAE)](https://www.gutenberg.org/ebooks/2000). [Condiciones](https://www.gutenberg.org/policy/license.html). |
| Rimas y leyendas | externo / HTML | [Rimas (1885) · Wikisource; solo el poemario, sin las leyendas de esta edición](https://es.wikisource.org/wiki/Rimas_(B%C3%A9cquer,_1885)). [Condiciones](https://es.wikisource.org/wiki/Wikisource:Derechos_de_autor). |
| Don Juan Tenorio | externo / HTML | [Don Juan Tenorio · transcripción de Wikisource, independiente de las notas de Castalia](https://es.wikisource.org/wiki/Don_Juan_Tenorio). [Condiciones](https://es.wikisource.org/wiki/Wikisource:Derechos_de_autor). |
| La vida es sueño | externo / HTML | [La vida es sueño · transcripción de Wikisource, sin el aparato de Debolsillo](https://es.wikisource.org/wiki/La_vida_es_sue%C3%B1o). [Condiciones](https://es.wikisource.org/wiki/Wikisource:Derechos_de_autor). |
| El mago de Oz | previa / PDF | [El mago de Oz · primeras páginas oficiales de Alianza (fragmento)](https://www.alianzaeditorial.es/libro/literatura/el-mago-de-oz-l-frank-baum-9788420608563/). [Condiciones](https://www.alianzaeditorial.es/primer_capitulo/el-mago-de-oz.pdf). |

Un EPUB completo integrado, tres lecturas externas y una vista previa oficial. No se presenta la vista previa como libro completo. El enlace de Rimas permite únicamente el poemario de 1885, sin las leyendas de la edición comercial. La transcripción de Don Quijote es independiente de las notas y estudios modernos de la edición impresa Alfaguara/RAE.

La selección tuvo en cuenta que el dominio público estadounidense no basta por sí solo para México. Se consultó el [artículo 29 de la LFDA](https://www.diputados.gob.mx/LeyesBiblio/pdf_mov/Ley_Federal_del_Derecho_de_Autor.pdf), las fechas de los autores y las [condiciones de Gutenberg](https://www.gutenberg.org/policy/license.html). No se alojan traducciones modernas de El principito, Drácula, Dune ni las otras ediciones comerciales. El PDF de El mago de Oz se abre únicamente en la fuente editorial que ofrece el fragmento.

El EPUB permanece en storage/app/private/lecturas y se sirve solo por URL opaca autenticada. Se revisó su ZIP para excluir scripts, manejadores de eventos y rutas inseguras. SHA-256: `8ee8c3650d397da45097214f21cff57d2ce8d035dfcd6585e0096e3bb74989fa`. Cada apertura valida ruta, disponibilidad, licencia y checksum. No hay cargas de lectores, proxy de URL arbitraria ni archivos restringidos en public. Un recurso faltante o inválido no muestra botón de lectura.

EPUB.js tiene scripts y ventanas emergentes deshabilitados. PDF.js funciona con worker, fuentes, CMaps y WASM locales. Ambos formatos tienen navegación, ajuste de texto o ampliación y avance privado persistente. El PDF integrado se comprobó con un documento original de dos páginas, exclusivamente en SQLite de prueba; no se incorporó ese documento a biblioteca2.

## Géneros y conservación del diseño

Se ampliaron aventuras, novela histórica, literatura universal, misterio, novela detectivesca, teatro y romance; también se añadieron títulos a infantil, terror, fantasía, poesía y ciencia ficción existentes. Las etiquetas equivalentes Cuento/Cuentos e Infantil/Literatura infantil se agrupan para el lector. Se agregaron relaciones con las categorías canónicas sin borrar etiquetas ni relaciones anteriores.

La revisión automática rechazó una propuesta de eliminar dos etiquetas redundantes recién creadas por contradecir la instrucción de no borrar información. Esa eliminación no se ejecutó. Se implementó la alternativa sin borrados descrita arriba. No queda pendiente aprobar una eliminación para utilizar la funcionalidad.

Se conservan sidebar, encabezado, identidad, tarjetas, fuentes y estilos administrativos. Solo se añadieron los controles solicitados. app.css y el layout administrativo no se editaron en esta etapa. lector.css añade únicamente el tamaño/overflow del contenedor nuevo de lectura y su canvas; no cambia reglas visuales anteriores. Los enlaces del lector siguen utilizando UUID y no muestran id_libro.

## Ediciones incorporadas

| Título | Autoría | ISBN | Editorial / año | Fuente |
|---|---|---|---|---|
| El principito | Antoine de Saint-Exupéry | 9788418797453 | Salamandra Infantil y Juvenil / 2022 | [Ficha](https://openlibrary.org/books/OL47134281M) |
| Alicia en el país de las maravillas, Fantasmagoría y Un cuento enredado | Lewis Carroll | 9788484034094 | Edimat Libros / 2004 | [Ficha](https://openlibrary.org/books/OL9145665M) |
| Pinocho | Carlo Collodi | 9788449427329 | Grupo Oceano / 2004 | [Ficha](https://openlibrary.org/books/OL9683624M) |
| Peter Pan en los jardines de Kensington | J. M. Barrie | 9788439216131 | Gaviota / 2005 | [Ficha](https://openlibrary.org/books/OL39479014M) |
| Cuentos de los hermanos Grimm | Jacob Grimm y Wilhelm Grimm | 9788439216278 | Gaviota / 2005 | [Ficha](https://openlibrary.org/books/OL9639280M) |
| Frankenstein | Mary Shelley | 9788445019795 | Minotauro / 2025 | [Ficha](https://openlibrary.org/books/OL62694929M) |
| El corazón delator y otros cuentos | Edgar Allan Poe | 9789875506503 | Longseller / 2006 | [Ficha](https://openlibrary.org/books/OL13201020M) |
| La caída de la casa Usher y otros cuentos | Edgar Allan Poe | 9789500396318 | Losada / 2009 | [Ficha](https://openlibrary.org/books/OL36693582M) |
| Viaje al centro de la Tierra | Jules Verne | 9789500285971 | El Ateneo / 2001 | [Ficha](https://openlibrary.org/books/OL12951842M) |
| Fahrenheit 451 | Ray Bradbury | 9788445006771 | MINOTAURO / 2020 | [Ficha](https://openlibrary.org/books/OL35713031M) |
| Dune | Frank Herbert | 9788497596824 | Debolsillo / 2005 | [Ficha](https://openlibrary.org/books/OL9154477M) |
| Los tres mosqueteros, vol. I | Alexandre Dumas | 9788447148592 | Salvat / 2020 | [Ficha](https://openlibrary.org/books/OL38240240M) |
| El conde de Montecristo, 1 | Alexandre Dumas | 9788420739540 | Anaya Publishers / 1999 | [Ficha](https://openlibrary.org/books/OL13193502M) |
| Rimas y leyendas | Gustavo Adolfo Bécquer | 9788467021479 | Espasa-Calpe / 2006 | [Ficha](https://openlibrary.org/books/OL18842552M) |
| Don Juan Tenorio | José Zorrilla | 9788470396946 | Castalia / 2000 | [Ficha](https://openlibrary.org/books/OL9134834M) |
| La vida es sueño | Pedro Calderón de la Barca | 9788497590624 | Debolsillo / 2002 | [Ficha](https://openlibrary.org/books/OL57794157M) |
| Platero y yo | Juan Ramón Jiménez | 9788426138149 | Juventud / 2010 | [Ficha](https://openlibrary.org/books/OL24371152M) |
| La Celestina | Fernando de Rojas | 9781589770119 | Juan de la Cuesta / 2003 | [Ficha](https://openlibrary.org/books/OL8843826M) |
| Otra vuelta de tuerca | Henry James | 9788491043225 | Alianza Editorial / 2016 | [Ficha](https://openlibrary.org/books/OL27532721M) |
| El extraño caso del doctor Jekyll y el señor Hyde | Robert Louis Stevenson | 9788490650615 | Alba / 2015 | [Ficha](https://openlibrary.org/books/OL32372874M) |
| Estudio en escarlata | Arthur Conan Doyle | 9788420665696 | Alianza Editorial / 2012 | [Ficha](https://www.alianzaeditorial.es/libro/bibliotecas-de-autor/estudio-en-escarlata-arthur-conan-doyle-9788420665696/) |
| El sabueso de los Baskerville | Arthur Conan Doyle | 9788420665689 | Alianza Editorial / 2012 | [Ficha](https://openlibrary.org/books/OL50530327M) |
| El asesinato de Roger Ackroyd | Agatha Christie | 9788492966660 | RBA / 2011 | [Ficha](https://openlibrary.org/books/OL26438714M) |
| Asesinato en el Orient Express | Agatha Christie | 9788478712090 | RBA / 2004 | [Ficha](https://openlibrary.org/books/OL9142141M) |
| La guerra de los mundos | H. G. Wells | 9788413621395 | Alianza Editorial / 2021 | [Ficha](https://openlibrary.org/books/OL35382486M) |
| La máquina del tiempo | H. G. Wells | 9788415988762 | Sportula / 2015 | [Ficha](https://openlibrary.org/books/OL39200420M) |
| Un mundo feliz | Aldous Huxley | 9788484502418 | Debolsillo / 2002 | [Ficha](https://openlibrary.org/books/OL9340298M) |
| Mujercitas | Louisa May Alcott | 9788418395758 | Editorial Alma / 2022 | [Ficha](https://openlibrary.org/books/OL37490562M) |
| La isla del tesoro | Robert Louis Stevenson | 9788491050889 | Penguin Clásicos / 2015 | [Ficha](https://openlibrary.org/books/OL28807776M) |
| El mago de Oz | L. Frank Baum | 9788420608563 | Alianza Editorial / 2012 | [Ficha](https://www.alianzaeditorial.es/libro/literatura/el-mago-de-oz-l-frank-baum-9788420608563/) |
| Drácula | Bram Stoker | 9788491819103 | Alianza Editorial / 2020 | [Ficha](https://www.alianzaeditorial.es/libro/literatura/dracula-bram-stoker-9788491819103/) |
| Don Quijote de la Mancha | Miguel de Cervantes | 9788420412146 | Alfaguara / 2015 | [Ficha](https://openlibrary.org/books/OL27203733M) |

Los tres mosqueteros y El conde de Montecristo están identificados expresamente como primeros volúmenes. Peter Pan en los jardines de Kensington es una obra distinta de Peter y Wendy. Alicia reúne las tres obras indicadas en su título. Patricia S. Finch figura como responsable de la edición de La Celestina; Fernando de Rojas se conserva como autor.

## Pendientes identificados

- Veinte mil leguas de viaje submarino: Las imágenes de las ediciones cotejadas son una contraportada y una página interior; no se importó como edición con portada verificada.
- Pulgarcito (Charles Perrault): El registro localizado acredita también a Natalia Molero; requiere determinar la adaptación y obtener una portada de edición inequívoca.
- Las 11 ediciones originales sin ISBN fiable permanecen pendientes de cotejo físico, sin sustituciones de ISBN ni portadas inventadas. Se conserva su información anterior; La Prueba de Lectura continúa sin correspondencia bibliográfica confirmada.
- Orgullo y prejuicio no se duplicó: su edición española ya existía.

## Archivos de esta etapa

- Backend: app/Http/Controllers/LecturaController.php, LectorController.php; app/Models/RecursoDigital.php, Libro.php, Categoria.php; app/Services/CatalogoLector.php; routes/web.php.
- Esquema: database/migrations/2026_10_31_000007_create_lectura_y_favoritos.php (incremental y dirigida, sin recrear tablas históricas).
- Datos: database/data/lectura-catalogo.json; database/seeders/LecturaCatalogoSeeder.php; EnriquecerCatalogoSeeder.php (varios autores, reutilización de fichas existentes y ediciones explícitamente cotejadas). El seeder de ejemplares DEMO conserva su alcance anterior.
- Vistas: resources/views/lector/estrella, digital, favoritos, leer, tarjeta, ficha, menu y explorar.blade.php; resources/views/layouts/lector.blade.php (versión de assets).
- Cliente: public/assets/js/lector.js, lectura.js; public/assets/css/lector.css (solo contenedor nuevo); public/assets/vendor/lectura con bibliotecas, recursos y licencias.
- Instalación/verificación: scripts/instalar-lectura.php, verificar-lectura.php, preparar-recursos-lectura.py; scripts/lector-vendor/package.json, package-lock.json, sync.mjs y README.md.
- Pruebas: tests/Feature/LectorCatalogoTest.php; migración nueva en las fixtures de RbacYPaginacionTest.php, SeguridadBibliotecaTest.php y SolicitudesPrestamoTest.php; tests/JavaScript/favoritos.test.cjs y ajuste del mock de lector.test.cjs.

## Respaldo e instalación

Antes de la primera modificación de MariaDB se verificó el respaldo completo `storage/app/private/backups/biblioteca2-lectura-favoritos-20261031-194033.sql`.
SHA-256: `6438ad4b1e3bbad0471ae3700d89731dd735b340ca505a2976586795bc705eb4`.

También existe respaldo previo a la agrupación no destructiva: biblioteca2-lectura-favoritos-20261031-194526.sql, en la misma carpeta privada. El instalador comprobó todas las filas anteriores y ejecutó el seeder dos veces comparando hashes de todas las tablas para confirmar idempotencia. La única ampliación de metadatos históricos permitida fue obra_fuente en las dos ediciones inglesas cotejadas.

Para reproducir en esta base de desarrollo local:

```text
python scripts/preparar-recursos-lectura.py
php scripts/instalar-lectura.php             # muestra alcance, sin escribir
php scripts/instalar-lectura.php --apply     # respaldo + migración dirigida + transacción
php scripts/verificar-lectura.php           # solo lectura
```

Si cambia el EPUB de origen, el checksum impide habilitarlo sin nueva revisión. Las bibliotecas se reconstruyen con npm ci --ignore-scripts --prefix scripts/lector-vendor y node scripts/lector-vendor/sync.mjs; no hace falta Node para servir la aplicación.

## Pruebas ejecutadas

- PHPUnit: **65 pruebas, 2660 aserciones, correctas**. Incluye favoritos idempotentes/privados, rechazo de identidad ajena, revocación RBAC, idiomas, importación sin inventario, varios autores, recursos deshabilitados/inseguros, vistas previas y conservación del ciclo de préstamo/devolución y límites 5/3.
- JavaScript: **5 pruebas correctas**; PUT/DELETE, sincronización entre tarjetas, error y reintento, y regresiones de portada, matriz y cantidades.
- Navegador con cuenta ficticia y SQLite aislado: login, filtro español, estrella, Mis favoritos, persistencia tras logout/login, eliminación y recarga vacía; EPUB real, navegación por capítulos, tamaño 125%, reanudación; PDF propio de dos páginas, siguiente, ampliación y reanudación. Sin errores de consola.
- Vista móvil 390×844: sin desbordamiento horizontal; encabezado superior fijo y texto ajustable.
- Auditoría de solo lectura de MariaDB: 32 fichas nuevas, cinco accesos digitales y favoritos responden correctamente, sin errores. Otras 276 comprobaciones GET del verificador general también finalizaron sin errores.
- Pint sobre archivos PHP modificados y git diff --check.

Evidencias privadas: storage/app/private/digital/auditoria-final.json, pruebas-finales.txt, verificacion-real.txt, favoritos-final.jpg, lectura-final.jpg, lectura-movil.jpg y pdf-prueba.jpg. La consulta de estas pruebas no crea favoritos ni préstamos en las cuentas reales.
