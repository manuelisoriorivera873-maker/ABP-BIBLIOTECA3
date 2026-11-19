# SAIDE Biblioteca — identidad original y enriquecimiento bibliográfico

Actualización posterior: [estado actual y ampliación de 27 títulos](C:/xampp/htdocs/biblioteca3/INFORME_AMPLIACION_CATALOGO.md). Los recuentos y respaldos siguientes corresponden a esta etapa anterior.

Implementado el 31/10/2026 en la rama `mejora-registro-roles`. Se conservó el catálogo de tarjetas y se recuperó el marco original de SAIDE. No se modificó `main`; no se hicieron commits, push ni merge. El árbol de trabajo ya contenía cambios de las tareas anteriores, que se conservaron.

**Diseño e integración**

El lector reutiliza el layout del sistema: marca naranja, menú azul marino, identidad autenticada, tipografía Segoe UI y botones azules. Los complementos CSS quedan bajo `.lector` y se cargan únicamente en las páginas del lector. El contenido administrativo de la navegación permanece en su rama Blade original; `app.css`, el JavaScript administrativo y las tablas administrativas no se modificaron en esta tarea. Las tarjetas se mantienen y las nuevas fichas de autores usan el mismo lenguaje visual.

Las biografías se enlazan mediante UUID; las claves primarias y los datos personales siguen en sus tablas originales. El middleware exige el permiso autores/ver también para la nueva ruta. Las consultas cargan anticipadamente las fichas de autores y la búsqueda incluye sus nombres bibliográficos con acentos. No se cambiaron las reglas del préstamo ni los permisos por rol.

**Libros incorporados: 15**

Cada título tiene una sinopsis original de 50–90 palabras. No se crearon ejemplares: los quince títulos nuevos muestran cero disponibilidad hasta registrar inventario físico real. Los años corresponden a las ediciones catalogadas, no necesariamente a la primera publicación de la obra.

| Título | Autor | ISBN | Editorial | Año | Fuente |
|---|---|---|---|---|---|
| El llano en llamas | Juan Rulfo | 9788493442613 | RM | 2006 | [Registro bibliográfico](https://openlibrary.org/books/OL13353863M) |
| El gallo de oro | Juan Rulfo | 9788492480180 | RM | 2010 | [Registro bibliográfico](https://openlibrary.org/books/OL26221248M) |
| Bestiario | Julio Cortázar | 9788466331845 | Debolsillo | 2016 | [Registro bibliográfico](https://www.penguinrandomhouse.com/books/579132/bestiario--bestiary-by-julio-cortazar/) |
| Final del juego | Julio Cortázar | 9789871106578 | Suma | 2004 | [Registro bibliográfico](https://openlibrary.org/books/OL9199407M) |
| Historias de cronopios y de famas | Julio Cortázar | 9789505111893 | Aguilar | 1995 | [Registro bibliográfico](https://openlibrary.org/books/OL9592664M) |
| Ficciones | Jorge Luis Borges | 9789500428446 | Emecé | 2006 | [Registro bibliográfico](https://openlibrary.org/books/OL47800257M) |
| El libro de arena | Jorge Luis Borges | 9788423338719 | Destino | 2006 | [Registro bibliográfico](https://openlibrary.org/books/OL9808124M) |
| De amor y de sombra | Isabel Allende | 9788484507895 | Plaza & Janés | 2001 | [Registro bibliográfico](https://openlibrary.org/books/OL9146128M) |
| La fiesta del Chivo | Mario Vargas Llosa | 9788466303316 | Suma de Letras | 2003 | [Registro bibliográfico](https://openlibrary.org/books/OL9133932M) |
| La casa verde | Mario Vargas Llosa | 9789707702493 | Alfaguara | 2005 | [Registro bibliográfico](https://openlibrary.org/books/OL9179837M) |
| La muerte de Artemio Cruz | Carlos Fuentes | 9789875780361 | Suma | 2006 | [Registro bibliográfico](https://openlibrary.org/books/OL9200450M) |
| Cien años de soledad | Gabriel García Márquez | 9786070714863 | Diana | 2012 | [Registro bibliográfico](https://openlibrary.org/books/OL38570141M) |
| El amor en los tiempos del cólera | Gabriel García Márquez | 9788497592451 | Debolsillo | 2003 | [Registro bibliográfico](https://openlibrary.org/books/OL22132438M) |
| Oficio de tinieblas | Rosario Castellanos | 9788493755904 | Libros del Silencio | 2009 | [Registro bibliográfico](https://openlibrary.org/books/OL47145136M) |
| Orgullo y prejuicio | Jane Austen | 9788415083696 | Albor Libros | 2017 | [Registro bibliográfico](https://openlibrary.org/books/OL47286332M) |

**Comprobaciones y límites bibliográficos**

Las portadas fueron descargadas para inspección visual privada y contrastadas por título. Se descartó la imagen sin título de Bestiario y se sustituyó por la portada editorial correspondiente al ISBN registrado. [Fuente editorial de Bestiario](https://www.penguinrandomhouse.com/books/579132/bestiario--bestiary-by-julio-cortazar/).

Corrección de la revisión posterior: las cubiertas de El llano en llamas y Oficio de tinieblas sí coinciden con RM y Libros del Silencio, respectivamente. La interpretación inicial de sus sellos como FCE fue errónea. De amor y de sombra muestra Ave Fénix/Debolsillo y su registro bibliográfico consigna Plaza & Janés como empresa editora. No se atribuye a los ejemplares físicos una edición que no haya sido cotejada.

El catálogo de RM confirma su ISBN para El llano en llamas; el año 2006 procede de Open Library, cuyo campo editorial emplea el nombre del distribuidor Cúspide. Se conservó RM como editorial tras el contraste. [Catálogo oficial RM](https://editorialrm.com/catalogos/2021.pdf). Oficio de tinieblas se registró con el año 2009 indicado por Open Library; existen referencias comerciales a otras reimpresiones.

Se descartaron Eva Luna en francés y una adaptación graduada inglesa de 1984 para no presentarlas como ediciones españolas íntegras. Google Books respondió 429: sus resultados no se usaron como evidencia.

Los once registros originales con ISBN inválido o edición no comprobada permanecen intactos y siguen sin mostrar ISBN verificado ni una portada atribuida a una edición no cotejada. No se borró «La Prueba de Lectura»: su identificación bibliográfica continúa pendiente.

Las imágenes se enlazan a sus proveedores con crédito, carga diferida en tarjetas y alternativa local ante errores; no se declara que las cubiertas tengan licencia libre. Las fotografías se sustituyen por un avatar SVG original, evitando atribuir derechos de uso no comprobados. No hay llamadas externas obligatorias para navegar, buscar o solicitar libros.

**Autores enriquecidos: 12**

Biografías originales de 60–100 palabras, nombre bibliográfico, nacionalidad y fechas cuando están verificadas. Se reutilizaron las once personas/autores existentes; solo se añadió Jane Austen, después de comprobar que no existía. No se reescribieron nombres civiles ni se añadieron roles a personas.

| Autor | Fuente biográfica |
|---|---|
| Juan Rulfo | [Open Library](https://openlibrary.org/authors/OL199285A) |
| Julio Cortázar | [Open Library](https://openlibrary.org/authors/OL2631008A) |
| Jorge Luis Borges | [Open Library](https://openlibrary.org/authors/OL18928A) |
| Isabel Allende | [Open Library](https://openlibrary.org/authors/OL228079A) |
| Laura Esquivel | [Open Library](https://openlibrary.org/authors/OL85306A) |
| Octavio Paz | [Open Library](https://openlibrary.org/authors/OL60534A) |
| Mario Vargas Llosa | [Open Library](https://openlibrary.org/authors/OL74490A) |
| Carlos Fuentes | [Open Library](https://openlibrary.org/authors/OL4333664A) |
| Gabriel García Márquez | [Open Library](https://openlibrary.org/authors/OL27363A) |
| Rosario Castellanos | [Open Library](https://openlibrary.org/authors/OL203132A) |
| Adolfo Bioy Casares | [Open Library](https://openlibrary.org/authors/OL67141A) |
| Jane Austen | [Open Library](https://openlibrary.org/authors/OL21594A) |

Las fechas exactas de Laura Esquivel se dejaron vacías porque la fuente recuperada solo indica el año de nacimiento. Las fichas muestran únicamente fechas comprobadas, sin completar datos por suposición. Las obras se obtienen desde las relaciones existentes con libros y se paginan.

**Categorías y editoriales**

Se añadieron once descripciones de categorías, incluida Cuento. Doce editoriales cuentan con información complementaria: descripciones verificadas del sello o una descripción limitada a una obra publicada por ellas y comprobada en la edición citada. Destino muestra España como país, apoyado en la Biblioteca de Catalunya; los países no comprobados se dejan vacíos. Las nueve editoriales restantes mantienen un aviso de información pendiente. No se inventaron países ni historias empresariales. Cada tarjeta permite explorar sus libros.

[Descripción oficial de Debolsillo](https://www.penguinlibros.com/es/11338-debolsillo?frame=0). [Destino, Biblioteca de Catalunya](https://www.bnc.cat/esl/layout/set/print/Editors-i-Editats-de-Catalunya/Editorials/Edicions-Destino).

**Base de datos y respaldo**

Migración aditiva dirigida `2026_10_31_000005_create_fichas_bibliograficas.php`: tablas `autor_fichas`, `categoria_fichas`, `editorial_fichas`, con claves foráneas a las entidades originales. No se cambiaron claves primarias ni tablas históricas. El seeder usa una transacción, valida ISBN, comprueba conflictos de identidad, reutiliza personas y evita repetir relaciones. Una identidad ambigua o un ISBN en conflicto aborta la transacción.

Respaldo completo antes de la incorporación: [storage/app/private/backups/biblioteca2-enriquecimiento-20261031-165056.sql](C:/xampp/htdocs/biblioteca3/storage/app/private/backups/biblioteca2-enriquecimiento-20261031-165056.sql). SHA-256: `9b36d3fb6b7b20b36d5952047a4686abf9f16185cf66e50f0f2135ff89863277`.

Segundo respaldo antes de comprobar el instalador final sobre el catálogo ampliado: [storage/app/private/backups/biblioteca2-enriquecimiento-20261031-165656.sql](C:/xampp/htdocs/biblioteca3/storage/app/private/backups/biblioteca2-enriquecimiento-20261031-165656.sql). SHA-256: `e7bc9c0cba37a9479c74c8ed65f18899856c1cd582e4768640aa3c06071e585f`.

Ambos respaldos verifican estructura, número de filas por tabla, estabilidad de los datos durante el respaldo y checksum. El instalador compara cada fila previa completa después de la carga. La ejecución final compara además hashes y conteos de todas las tablas, incluidas las tres nuevas, antes y después de repetir el seeder.

| Tabla | Antes | Después |
|---|---:|---:|
| libros | 13 | 28 |
| libro_fichas | 13 | 28 |
| personas | 31 | 32 |
| autores | 11 | 12 |
| categorias | 10 | 11 |
| editoriales | 11 | 21 |
| asigna_autores | 13 | 28 |
| asigna_categorias | 13 | 28 |
| asigna_editoriales | 13 | 28 |
| ejemplares | 14 | 14 |
| prestamos | 13 | 13 |
| detalle_prestamo | 16 | 16 |
| devoluciones | 6 | 6 |
| detalle_devolucion | 7 | 7 |
| cuentas_acceso | 10 | 10 |
| usuarios | 16 | 16 |
| bibliotecarios | 5 | 5 |
| solicitudes_prestamo | 2 | 2 |
| solicitud_detalles | 4 | 4 |

Las tres tablas nuevas contienen 12 fichas de autor, 11 de categoría y 12 de editorial. No se alteraron cuentas, permisos, disponibilidad operativa, préstamos ni devoluciones. No hubo eliminaciones, `migrate:fresh` ni regularización de solicitudes históricas.

**Pruebas realizadas**

- Suite PHP completa: 55 pruebas y 2147 aserciones, todas correctas, con SQLite en memoria.
- Después de completar la búsqueda por nombre bibliográfico acentuado: 9 pruebas del catálogo y 317 aserciones, todas correctas.
- JavaScript: 3 pruebas correctas, incluidas alternativa de portada y prevención de reenvío; se mantienen las pruebas de matriz y resumen existentes.
- Pruebas de catálogo: UUID, ausencia de IDs internos, permisos revocados, biografías y obras, búsqueda ISBN/autor acentuado, filtros, paginación y carga anticipada; idempotencia del seeder y conservación del inventario e históricos.
- Las pruebas de solicitudes incluidas en la suite verifican múltiples títulos, máximo de cinco ejemplares y tres por título, validación del lector, stock, asignación, aprobación transaccional, rechazo, privacidad y prevención de duplicados.
- Auditoría de lectura en biblioteca2: 113 páginas GET correctas, 38 relaciones comprobadas y cero errores; incluye todas las fichas de libros y las doce biografías. Conteos sin cambios durante la auditoría. [Resultado de auditoría](C:/xampp/htdocs/biblioteca3/storage/app/private/enriquecimiento/verificacion.txt).
- Navegador sobre SQLite independiente: inicio como lector, catálogo, filtro de disponibilidad, ficha de Bestiario y biografía de Cortázar con sus obras; selección de dos títulos, resumen, envío único y estado PENDIENTE. Inicio como bibliotecario, disponibilidad operativa, aprobación, solicitud APROBADA con préstamo vinculado y sin acciones repetidas; regreso como lector y préstamo activo con ambos títulos y vencimiento 14/11/2026. Sin errores de consola.
- Adaptación comprobada a 1366, 768 y 390 px; no se detectó desbordamiento horizontal. Menú móvil abre y cierra con Escape. El tamaño temporal se restableció y el servidor/pestaña de pruebas se cerraron.
- `git diff --check` sin errores de espacios; Pint aplicado a los archivos PHP nuevos o propios de esta mejora.

**Archivos de esta tarea**

- [resources/views/layouts/app.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/layouts/app.blade.php)
- [resources/views/layouts/lector.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/layouts/lector.blade.php)
- [resources/views/lector/menu.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/menu.blade.php)
- [resources/views/lector/bibliografia.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/bibliografia.blade.php)
- [resources/views/lector/autor.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/autor.blade.php)
- [resources/views/lector/autores-enlaces.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/autores-enlaces.blade.php)
- [resources/views/lector/ficha.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/ficha.blade.php)
- [resources/views/lector/tarjeta.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/tarjeta.blade.php)
- [resources/views/lector/bandeja.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/bandeja.blade.php)
- [public/assets/css/lector.css](C:/xampp/htdocs/biblioteca3/public/assets/css/lector.css)
- [public/assets/js/lector.js](C:/xampp/htdocs/biblioteca3/public/assets/js/lector.js)
- [public/assets/img/portada-generica.svg](C:/xampp/htdocs/biblioteca3/public/assets/img/portada-generica.svg)
- [public/assets/img/autor-avatar.svg](C:/xampp/htdocs/biblioteca3/public/assets/img/autor-avatar.svg)
- [app/Http/Controllers/LectorController.php](C:/xampp/htdocs/biblioteca3/app/Http/Controllers/LectorController.php)
- [app/Http/Middleware/AutorizarBiblioteca.php](C:/xampp/htdocs/biblioteca3/app/Http/Middleware/AutorizarBiblioteca.php)
- [app/Services/CatalogoLector.php](C:/xampp/htdocs/biblioteca3/app/Services/CatalogoLector.php)
- [app/Models/Autor.php](C:/xampp/htdocs/biblioteca3/app/Models/Autor.php)
- [app/Models/Categoria.php](C:/xampp/htdocs/biblioteca3/app/Models/Categoria.php)
- [app/Models/Editorial.php](C:/xampp/htdocs/biblioteca3/app/Models/Editorial.php)
- [app/Models/AutorFicha.php](C:/xampp/htdocs/biblioteca3/app/Models/AutorFicha.php)
- [app/Models/CategoriaFicha.php](C:/xampp/htdocs/biblioteca3/app/Models/CategoriaFicha.php)
- [app/Models/EditorialFicha.php](C:/xampp/htdocs/biblioteca3/app/Models/EditorialFicha.php)
- [routes/web.php](C:/xampp/htdocs/biblioteca3/routes/web.php)
- [database/migrations/2026_10_31_000005_create_fichas_bibliograficas.php](C:/xampp/htdocs/biblioteca3/database/migrations/2026_10_31_000005_create_fichas_bibliograficas.php)
- [database/data/enriquecimiento.json](C:/xampp/htdocs/biblioteca3/database/data/enriquecimiento.json)
- [database/seeders/EnriquecerCatalogoSeeder.php](C:/xampp/htdocs/biblioteca3/database/seeders/EnriquecerCatalogoSeeder.php)
- [scripts/instalar-enriquecimiento.php](C:/xampp/htdocs/biblioteca3/scripts/instalar-enriquecimiento.php)
- [scripts/verificar-biblioteca.php](C:/xampp/htdocs/biblioteca3/scripts/verificar-biblioteca.php)
- [tests/Feature/LectorCatalogoTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/LectorCatalogoTest.php)
- [tests/Feature/RbacYPaginacionTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/RbacYPaginacionTest.php)
- [tests/Feature/SeguridadBibliotecaTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/SeguridadBibliotecaTest.php)
- [tests/Feature/SolicitudesPrestamoTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/SolicitudesPrestamoTest.php)
- [tests/JavaScript/lector.test.cjs](C:/xampp/htdocs/biblioteca3/tests/JavaScript/lector.test.cjs)

Los tres últimos tests de otros módulos solo incorporan la migración complementaria a sus bases aisladas. No se sustituyeron ni revirtieron los cambios anteriores del repositorio. Los datos de investigación, respaldos, evidencias y SQLite de pruebas permanecen en `storage/app/private`, excluido de Git.

**Evidencias**

[Catálogo en escritorio](C:/xampp/htdocs/biblioteca3/storage/app/private/enriquecimiento/catalogo-escritorio.jpg) · [Ficha del autor en móvil](C:/xampp/htdocs/biblioteca3/storage/app/private/enriquecimiento/autor-movil.jpg) · [Préstamo en entorno aislado](C:/xampp/htdocs/biblioteca3/storage/app/private/enriquecimiento/prestamo-prueba.jpg)

**Estado Git al finalizar**

Rama `mejora-registro-roles`; cambios sin commit. `main` intacta, sin push ni merge. El estado completo incluye trabajo previo y esta mejora:

```text
 M app/Http/Controllers/AccesoController.php
 M app/Http/Controllers/LibroController.php
 M app/Http/Controllers/RegistroPublicoController.php
 M app/Http/Controllers/SolicitudPrestamoController.php
 M app/Http/Middleware/AutorizarBiblioteca.php
 M app/Models/Autor.php
 M app/Models/Categoria.php
 M app/Models/CuentaAcceso.php
 M app/Models/Editorial.php
 M app/Models/Libro.php
 M app/Services/SolicitarPrestamo.php
 M app/Support/PermisosBiblioteca.php
 M bootstrap/app.php
 M resources/views/acceso/sinpermisos.blade.php
 M resources/views/layouts/app.blade.php
 M resources/views/solicitudes/index.blade.php
 M routes/web.php
 M scripts/verificar-biblioteca.php
 M tests/Feature/RbacYPaginacionTest.php
 M tests/Feature/SeguridadBibliotecaTest.php
 M tests/Feature/SolicitudesPrestamoTest.php
 M tests/Unit/PermisosBibliotecaTest.php
?? INFORME_PANEL_LECTOR.md
?? app/Http/Controllers/LectorController.php
?? app/Models/AutorFicha.php
?? app/Models/CategoriaFicha.php
?? app/Models/EditorialFicha.php
?? app/Models/LibroFicha.php
?? app/Services/CatalogoLector.php
?? database/data/
?? database/migrations/2026_10_31_000004_create_libro_fichas.php
?? database/migrations/2026_10_31_000005_create_fichas_bibliograficas.php
?? database/seeders/CatalogoVerificadoSeeder.php
?? database/seeders/EnriquecerCatalogoSeeder.php
?? public/assets/css/lector.css
?? public/assets/img/
?? public/assets/js/lector.js
?? resources/views/layouts/lector.blade.php
?? resources/views/lector/
?? scripts/auditar-catalogo.php
?? scripts/instalar-enriquecimiento.php
?? scripts/instalar-lector.php
?? tests/Feature/LectorCatalogoTest.php
?? tests/JavaScript/lector.test.cjs
```
