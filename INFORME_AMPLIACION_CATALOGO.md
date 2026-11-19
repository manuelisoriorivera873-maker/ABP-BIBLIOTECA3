# SAIDE Biblioteca — ampliación y revisión de portadas

Aplicado directamente el 31/10/2026. Rama `mejora-registro-roles`; sin commit, push ni merge. Se preservaron los cambios pendientes de las tareas anteriores.

**Resultado**

- Una portada recuperada de un libro existente: Cien años de soledad. Se sustituyó la imagen de una página de título por la cubierta de Diana vinculada al ISBN 9786070714863.
- Veintisiete libros y veintisiete autores incorporados, cada libro con portada contrastada y datos de edición.
- Catálogo final: 55 títulos, 44 portadas contrastadas, 54 sinopsis y 39 fichas de autor.
- Once registros originales conservan su ISBN y su portada genérica: diez obras están corroboradas, pero sus ISBN fallan el dígito de control; un registro carece de formato de ISBN y de correspondencia bibliográfica confirmada.
- Inventario y movimientos conservados: 14 ejemplares, 13 préstamos, 16 detalles de préstamo y 6 devoluciones. Ningún ejemplar o cuenta fue creado por la importación.

**Diseño y encabezado**

Se conservaron menú azul marino, marca naranja, tipografía, botones, sombras, tamaños de portada y columnas. No se modificaron el layout administrativo ni sus hojas de estilo. Los resúmenes de las tarjetas reutilizan la tipografía existente y una única regla nueva de recorte a tres líneas; las fichas mantienen la sinopsis completa. El campo de idioma usa la lista bibliográfica existente.

El encabezado ya tenía `position: sticky; top: 0; z-index: 1020`. Se comprobó ese comportamiento sin introducir cambios visuales: a 1280 px de ancho, tras desplazar 1440 px, su posición vertical fue 0 y el elemento superior visible pertenecía a la barra; a 390 px también permaneció en 0. El contenido comienza debajo de la barra. El ancho de contenido no superó el ancho útil de la pantalla y el menú móvil abrió y cerró con Escape.

La página inicial reutiliza las tarjetas existentes para libros disponibles, seis categorías con obras, nuevos títulos del sistema, autores destacados y una selección de distintas categorías. Las novedades se ordenan por incorporación al catálogo, no por fecha original de publicación; la selección general no se presenta como personalización. Los datos proceden de registros reales.

**Libros añadidos: 27**

Los años siguientes corresponden a las ediciones consultadas. Se distinguieron las ediciones inglesas de las españolas; el idioma figura en la ficha. Todos los ISBN nuevos superan la comprobación matemática y corresponden a un registro de edición consultado. Las cubiertas se revisaron visualmente por título, autor y sello cuando este era visible.

| Título | Autor | ISBN | Editorial | Año | Categoría | Idioma | Fuente |
|---|---|---|---|---:|---|---|---|
| La tregua | Mario Benedetti | 9788466322874 | Santillana | 2009 | Novela | Español | [Edición](https://openlibrary.org/books/OL26319445M) · [Portada](https://covers.openlibrary.org/b/id/7993929-M.jpg?default=false) |
| El túnel | Ernesto Sábato | 9788432250026 | Seix Barral | 1980 | Novela | Español | [Edición](https://openlibrary.org/books/OL9121174M) · [Portada](https://covers.openlibrary.org/b/id/13303500-M.jpg?default=false) |
| Los detectives salvajes | Roberto Bolaño | 9788433966636 | Anagrama | 2011 | Novela | Español | [Edición](https://openlibrary.org/books/OL61669380M) · [Portada](https://covers.openlibrary.org/b/id/15215334-M.jpg?default=false) |
| Las batallas en el desierto | José Emilio Pacheco | 9789684114739 | Era | 2005 | Novela | Español | [Edición](https://openlibrary.org/books/OL9174364M) · [Portada](https://covers.openlibrary.org/b/id/4924277-M.jpg?default=false) |
| Los recuerdos del porvenir | Elena Garro | 9788492891146 | 451 Editores | 2011 | Novela | Español | [Edición](https://openlibrary.org/books/OL39482895M) · [Portada](https://covers.openlibrary.org/b/id/12887163-M.jpg?default=false) |
| Cosmos | Carl Sagan | 9780394502946 | Random House | 1980 | Divulgación científica | Inglés | [Edición](https://openlibrary.org/books/OL24740450M) · [Portada](https://covers.openlibrary.org/b/id/10601318-M.jpg?default=false) |
| The Selfish Gene | Richard Dawkins | 9780198788607 | Oxford University Press | 2016 | Divulgación científica | Inglés | [Edición](https://openlibrary.org/books/OL28000467M) · [Portada](https://covers.openlibrary.org/b/id/15179535-M.jpg?default=false) |
| Silent Spring | Rachel Carson | 9780395166116 | Houghton Mifflin | 1994 | Divulgación científica | Inglés | [Edición](https://openlibrary.org/books/OL39460744M) · [Portada](https://covers.openlibrary.org/b/id/15199104-M.jpg?default=false) |
| The Man Who Mistook His Wife for a Hat: And Other Clinical Tales | Oliver Sacks | 9781101908310 | Everyman's Library | 2023 | Divulgación científica | Inglés | [Edición](https://openlibrary.org/books/OL46626867M) · [Portada](https://covers.openlibrary.org/b/id/13663010-M.jpg?default=false) |
| Code: The Hidden Language of Computer Hardware and Software | Charles Petzold | 9780735611313 | Microsoft Press | 2000 | Tecnico | Inglés | [Edición](https://openlibrary.org/books/OL7890773M) · [Portada](https://covers.openlibrary.org/b/id/461152-M.jpg?default=false) |
| Automate the Boring Stuff with Python: Practical Programming for Total Beginners | Al Sweigart | 9781593279929 | No Starch Press | 2019 | Tecnico | Inglés | [Edición](https://openlibrary.org/books/OL28749300M) · [Portada](https://covers.openlibrary.org/b/id/10355256-M.jpg?default=false) |
| The Linux Command Line: A Complete Introduction | William Shotts | 9781593273897 | No Starch Press | 2012 | Tecnico | Inglés | [Edición](https://nostarch.com/releases/linuxcommandline_release.html) · [Portada](https://covers.openlibrary.org/b/id/7087755-M.jpg?default=false) |
| A Short History of Nearly Everything | Bill Bryson | 9780552997041 | Black Swan | 2004 | Divulgación científica | Inglés | [Edición](https://openlibrary.org/books/OL49829494M) · [Portada](https://covers.openlibrary.org/b/id/15214565-M.jpg?default=false) |
| El infinito en un junco: La invención de los libros en el mundo antiguo | Irene Vallejo | 9788417860790 | Siruela | 2020 | Ensayo | Español | [Edición](https://openlibrary.org/books/OL28108908M) · [Portada](https://covers.openlibrary.org/b/id/9689877-M.jpg?default=false) |
| Frankenstein | Mary Shelley | 9780141439471 | Penguin Books | 2003 | Novela | Inglés | [Edición](https://openlibrary.org/books/OL3701481M) · [Portada](https://covers.openlibrary.org/b/id/109033-M.jpg?default=false) |
| Jane Eyre | Charlotte Brontë | 9780141441146 | Penguin Classics | 2006 | Novela | Inglés | [Edición](https://openlibrary.org/books/OL7359947M) · [Portada](https://covers.openlibrary.org/b/id/109090-M.jpg?default=false) |
| Wuthering Heights | Emily Brontë | 9780141439556 | Penguin Classics | 2002 | Novela | Inglés | [Edición](https://openlibrary.org/books/OL7359924M) · [Portada](https://covers.openlibrary.org/b/id/109038-M.jpg?default=false) |
| The Time Machine | H. G. Wells | 9780141439976 | Penguin Classics | 2005 | Ciencia Ficcion | Inglés | [Edición](https://openlibrary.org/books/OL7359933M) · [Portada](https://covers.openlibrary.org/b/id/109075-M.jpg?default=false) |
| The Picture of Dorian Gray | Oscar Wilde | 9780141439570 | Penguin Books | 2003 | Novela | Inglés | [Edición](https://openlibrary.org/books/OL3701357M) · [Portada](https://covers.openlibrary.org/b/id/15259210-M.jpg?default=false) |
| A Brief History of Time | Stephen Hawking | 9780553380163 | Bantam | 1998 | Divulgación científica | Inglés | [Edición](https://www.penguinrandomhouseretail.com/book/?isbn=9780553380163) · [Portada](https://covers.openlibrary.org/b/id/14589690-M.jpg?default=false) |
| On the Origin of Species | Charles Darwin | 9780140439120 | Penguin Books | 2008 | Divulgación científica | Inglés | [Edición](https://openlibrary.org/books/OL7354999M) · [Portada](https://covers.openlibrary.org/b/id/12808077-M.jpg?default=false) |
| Clean Code: A Handbook of Agile Software Craftsmanship | Robert C. Martin | 9780132350884 | Prentice Hall | 2008 | Tecnico | Inglés | [Edición](https://openlibrary.org/books/OL26222911M) · [Portada](https://covers.openlibrary.org/b/id/15126503-M.jpg?default=false) |
| Think Python: How to Think Like a Computer Scientist | Allen B. Downey | 9781449330729 | O'Reilly Media | 2012 | Tecnico | Inglés | [Edición](https://openlibrary.org/books/OL25763659M) · [Portada](https://covers.openlibrary.org/b/id/7363360-M.jpg?default=false) |
| Sapiens: A Brief History of Humankind | Yuval Noah Harari | 9780062316097 | Harper | 2015 | Historia | Inglés | [Edición](https://openlibrary.org/books/OL27000666M) · [Portada](https://covers.openlibrary.org/b/id/8594920-M.jpg?default=false) |
| Meditations | Marco Aurelio | 9780140449334 | Penguin Books | 2006 | Filosofía | Inglés | [Edición](https://openlibrary.org/books/OL62588651M) · [Portada](https://covers.openlibrary.org/b/id/15256763-M.jpg?default=false) |
| The Design of Everyday Things | Donald A. Norman | 9780465050659 | Basic Books | 2013 | Tecnico | Inglés | [Edición](https://openlibrary.org/books/OL25726927M) · [Portada](https://covers.openlibrary.org/b/id/14428212-M.jpg?default=false) |
| The Republic | Platón | 9780140455113 | Penguin Classics | 2007 | Filosofía | Inglés | [Edición](https://openlibrary.org/books/OL10097386M) · [Portada](https://covers.openlibrary.org/b/id/2324117-M.jpg?default=false) |

Distribución: Novela: 9, Divulgación científica: 7, Tecnico: 6, Ensayo: 1, Ciencia Ficcion: 1, Historia: 1, Filosofía: 2.

Se descartaron resultados abreviados, audiolibros y adaptaciones gráficas antes de preparar la carga. Entre las treinta ediciones sometidas a revisión visual, tres no se incorporaron: The History of the Ancient World tenía una fotografía ajena en su primera imagen; The Metamorphosis mostraba una cubierta de Martino mientras el registro correspondía a Bantam; A Room of One’s Own no tuvo corroboración suficiente de cubierta para la edición elegida. Esas obras no se consideran ficticias: sus candidatos permanecen en la evidencia privada, sin forzar registros.

Los datos del libro de Linux se contrastaron con el anuncio editorial, que indica enero de 2012, corrigiendo el 2011 del registro comunitario. [No Starch Press](https://nostarch.com/releases/linuxcommandline_release.html). La edición de Hawking se contrastó como Bantam de 1998. [Penguin Random House](https://www.penguinrandomhouseretail.com/book/?isbn=9780553380163). La fecha de Wuthering Heights, 31/12/2002, coincide con la ficha editorial consultada. [Penguin Random House](https://prhinternationalsales.com/book/?isbn=9780141439556).

**Portada recuperada y correcciones de la revisión anterior**

La cubierta recuperada de Cien años de soledad muestra título, autor y sello Diana. La referencia de imagen está vinculada al ISBN exacto y coincide con la edición descrita en el catálogo contrastado. [Cubierta por ISBN, Casa del Libro](https://imagessl0.casadellibro.com/a/l/t7/63/9786070714863.jpg). [Ficha con ISBN, Diana y año 2012, Sanborns](https://www.sanborns.com.mx/producto/31627/cien-anos-de-soledad).

La revisión a mayor resolución confirmó que El llano en llamas muestra el sello rm y que Oficio de tinieblas coincide con la cubierta de Libros del Silencio, con su sello S y la mención al prólogo de Jordi Soler. La atribución de ambas imágenes a FCE en el informe anterior era incorrecta; se corrigieron las notas del manifiesto y del informe anterior. Esas dos cubiertas ya existían y no se cuentan como recuperaciones nuevas. [Catálogo RM, ISBN 9788493442613](https://editorialrm.com/catalogos/2021.pdf). [Oficio de tinieblas por su ISBN](https://www.casadellibro.com/libro-oficio-de-tinieblas/9788493755904/1614884).

De amor y de sombra muestra Ave Fénix/Debolsillo, mientras el registro de su ISBN consigna Plaza & Janés como empresa editora. Se conservó la distinción entre sello y empresa; tampoco se atribuyó una edición física a los once ejemplares pendientes.

Las miniaturas se enlazan a sus fuentes y mantienen las proporciones, carga diferida y alternativa local existentes. Se añadió `default=false` en las referencias de Open Library para que una imagen inexistente produzca un error manejable en vez de una miniatura vacía. Se comprobó el contenido gráfico de las imágenes asignadas, no únicamente un código HTTP 200. Los autores mantienen el avatar existente.

**Registros pendientes de revisión física: 11**

El usuario confirmó que aún no dispone de los ISBN impresos. Por ello se conservaron los once valores originales y sus relaciones. La investigación por obra y autor permite añadir las sinopsis de diez títulos sin asignarles el ISBN de una edición hallada en Internet. La Prueba de Lectura conserva un aviso de revisión y no recibió una sinopsis inventada.

| Título registrado | ISBN actual conservado | Diagnóstico | Información de la obra |
|---|---|---|---|
| Pedro Paramo | 978-607-100-001-1 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=Pedro+Paramo&author=Juan+Rulfo&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| Rayuela | 978-607-100-002-8 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=Rayuela&author=Julio+Cortazar&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| El Aleph | 978-607-100-003-5 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=El+Aleph&author=Jorge+Luis+Borges&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| La casa de los espiritus | 978-607-100-004-2 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=La+casa+de+los+espiritus&author=Isabel+Allende&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| Como agua para chocolate | 978-607-100-005-9 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=Como+agua+para+chocolate&author=Laura+Esquivel&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| El laberinto de la soledad | 978-607-100-006-6 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=El+laberinto+de+la+soledad&author=Octavio+Paz&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| La ciudad y los perros | 978-607-100-007-3 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=La+ciudad+y+los+perros&author=Mario+Vargas&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| Aura | 978-607-100-008-0 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=Aura&author=Carlos+Fuentes&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| Cronica de una muerte anunciada | 978-607-100-009-7 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=Cronica+de+una+muerte+anunciada&author=Gabriel+Garcia&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| Balun Canan | 978-607-100-010-3 | Dígito de control inválido; edición física sin identificar | [Obra y autor corroborados](https://openlibrary.org/search.json?title=Balun+Canan&author=Rosario+Castellanos&limit=2&fields=key%2Ctitle%2Cauthor_name); sinopsis añadida |
| La Prueba de Lectura | 1828GBF | Formato no identificable como ISBN; obra pendiente | Sin correspondencia confirmada en la consulta; pendiente |

Acción posterior: cotejar título, autor, editorial, año e ISBN impresos en cada ejemplar. Los resultados de una búsqueda por obra no prueban qué edición física posee la biblioteca. Las claves primarias, ejemplares e históricos siguen vinculados a sus registros originales.

**Autores incorporados**

Veintisiete biografías originales de 60–100 palabras. Se conservó la relación personas → autores, sin crear cuentas de acceso. Las fechas parciales no se completaron con días ficticios; las fechas antiguas o insuficientemente precisas quedaron vacías. Los nombres bibliográficos pueden diferir del nombre civil o incluir iniciales; el importador reutiliza su ficha para conservar la identidad al repetirse.

| Autor | Nacionalidad comprobada | Nacimiento | Fallecimiento | Fuente |
|---|---|---|---|---|
| Mario Benedetti | Uruguaya | 1920-09-14 | 2009-05-17 | [Ficha](https://openlibrary.org/authors/OL60411A) · [Contraste 1](https://www.wikidata.org/wiki/Q16285) |
| Ernesto Sábato | Argentina | 1911-06-24 | 2011-04-30 | [Ficha](https://openlibrary.org/authors/OL2748652A) · [Contraste 1](https://www.wikidata.org/wiki/Q186630) |
| Roberto Bolaño | Chilena | 1953-04-28 | 2003-07-15 | [Ficha](https://openlibrary.org/authors/OL56795A) · [Contraste 1](https://www.wikidata.org/wiki/Q297532) |
| José Emilio Pacheco | Mexicana | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL4185408A) · [Contraste 1](https://www.cervantes.es/imagenes/File/Nota%20de%20prensa.pdf) · [Contraste 2](https://icau.mec.gub.uy/innovaportal/v/46292/70/mecweb/fallecio-jose-emilio-pacheco?parentid=44548) |
| Elena Garro | Mexicana | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL53103A) |
| Carl Sagan | Estadounidense | 1934-11-09 | 1996-12-20 | [Ficha](https://openlibrary.org/authors/OL450295A) · [Contraste 1](https://www.wikidata.org/wiki/Q410) |
| Richard Dawkins | Británica | 1941-03-26 | — | [Ficha](https://openlibrary.org/authors/OL236174A) · [Contraste 1](https://www.wikidata.org/wiki/Q44461) |
| Rachel Carson | Estadounidense | 1907-05-27 | 1964-04-14 | [Ficha](https://openlibrary.org/authors/OL225621A) · [Contraste 1](https://www.wikidata.org/wiki/Q100948) |
| Oliver Sacks | Británica | 1933-07-09 | 2015-08-30 | [Ficha](https://openlibrary.org/authors/OL217344A) · [Contraste 1](https://www.wikidata.org/wiki/Q258662) |
| Charles Petzold | Estadounidense | 1953-02-02 | — | [Ficha](https://openlibrary.org/authors/OL236999A) · [Contraste 1](https://www.wikidata.org/wiki/Q93023) · [Contraste 2](https://www.microsoftpressstore.com/authors/bio.aspx?a=e0ed0f06-5274-471e-82e2-a09bd14e1640) |
| Al Sweigart | Pendiente | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL7244458A) · [Contraste 1](https://www.wikidata.org/wiki/Q125994279) |
| William Shotts | Pendiente | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL6984243A) · [Contraste 1](https://nostarch.com/node/820) |
| Bill Bryson | Estadounidense | 1951-12-08 | — | [Ficha](https://openlibrary.org/authors/OL21718A) · [Contraste 1](https://www.wikidata.org/wiki/Q310155) |
| Irene Vallejo | Española | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL5980983A) · [Contraste 1](https://www.wikidata.org/wiki/Q42897117) |
| Mary Shelley | Británica | 1797-08-30 | 1851-02-01 | [Ficha](https://openlibrary.org/authors/OL25342A) · [Contraste 1](https://www.wikidata.org/wiki/Q47152) |
| Charlotte Brontë | Inglesa | 1816-04-21 | 1855-03-31 | [Ficha](https://openlibrary.org/authors/OL113362A) · [Contraste 1](https://www.wikidata.org/wiki/Q127332) |
| Emily Brontë | Inglesa | 1818-07-30 | 1848-12-19 | [Ficha](https://openlibrary.org/authors/OL24529A) · [Contraste 1](https://www.wikidata.org/wiki/Q80137) |
| H. G. Wells | Británica | 1866-09-21 | 1946-08-13 | [Ficha](https://openlibrary.org/authors/OL13066A) · [Contraste 1](https://www.wikidata.org/wiki/Q42511) |
| Oscar Wilde | Irlandesa | 1854-10-16 | 1900-11-30 | [Ficha](https://openlibrary.org/authors/OL20646A) · [Contraste 1](https://www.wikidata.org/wiki/Q30875) |
| Stephen Hawking | Británica | 1942-01-08 | 2018-03-14 | [Ficha](https://openlibrary.org/authors/OL2675573A) · [Contraste 1](https://www.wikidata.org/wiki/Q17714) |
| Charles Darwin | Británica | 1809-02-12 | 1882-04-19 | [Ficha](https://openlibrary.org/authors/OL35839A) · [Contraste 1](https://www.wikidata.org/wiki/Q1035) |
| Robert C. Martin | Estadounidense | 1952-12-05 | — | [Ficha](https://openlibrary.org/authors/OL2653686A) · [Contraste 1](https://www.wikidata.org/wiki/Q93010) |
| Allen B. Downey | Pendiente | 1967-05-11 | — | [Ficha](https://openlibrary.org/authors/OL6815485A) |
| Yuval Noah Harari | Israelí | 1976-02-24 | — | [Ficha](https://openlibrary.org/authors/OL3778242A) · [Contraste 1](https://www.wikidata.org/wiki/Q2484404) |
| Marco Aurelio | Romana | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL133986A) · [Contraste 1](https://www.wikidata.org/wiki/Q1430) |
| Donald A. Norman | Estadounidense | 1935-12-25 | — | [Ficha](https://openlibrary.org/authors/OL224976A) · [Contraste 1](https://www.wikidata.org/wiki/Q92804) |
| Platón | Griega | Sin fecha exacta | — | [Ficha](https://openlibrary.org/authors/OL12823A) · [Contraste 1](https://www.wikidata.org/wiki/Q859) |

**Fuentes y uso de API**

- [Open Library Books API](https://openlibrary.org/dev/docs/api/books) y endpoints JSON de obras, ediciones, ISBN y autores.
- [Open Library Covers API](https://openlibrary.org/dev/docs/api/covers), con imágenes concretas obtenidas de los registros consultados.
- API oficial de Wikidata: consultas agrupadas para contrastar datos biográficos; se descartaron afirmaciones obsoletas y fechas de precisión insuficiente.
- Catálogos editoriales y referencias primarias citadas en las filas y notas. Para el argumento de Los detectives salvajes se contrastó la ficha de [Anagrama](https://www.anagrama-ed.es/libro/narrativas-hispanicas/los-detectives-salvajes/9788433910868/NH_256), porque la descripción comunitaria estaba mezclada con otra obra.
- Google Books limitó la consulta con HTTP 429; la carga se basó en las otras fuentes verificadas.

Las consultas se realizaron desde scripts de investigación acotados, secuenciales y con caché privada. Los resultados no se importan automáticamente: los manifiestos contienen únicamente la selección revisada. Las páginas de Laravel leen MariaDB y no consultan servicios bibliográficos externos al cargarse. Las portadas utilizan la caché del navegador y la alternativa local cuando falla la conexión. Las copias de auditoría no se publicaron como un repositorio de imágenes.

**Base de datos y protección del histórico**

Se aplicó únicamente la migración aditiva `2026_10_31_000006_add_revision_bibliografica.php`: añadió idioma, nota de revisión y fecha de comprobación de portada a libro_fichas. El importador común ya existente gestiona personas, autores y relaciones; CompletarCatalogoSeeder lo reutiliza dentro de una transacción que incluye las revisiones de las fichas. ISBN o autoría en conflicto provocan rollback. El instalador revisa cada fila previa y limita las modificaciones antiguas a los campos bibliográficos autorizados.

Respaldo completo verificado antes de aplicar: [storage/app/private/backups/biblioteca2-ampliacion-20261031-174602.sql](C:/xampp/htdocs/biblioteca3/storage/app/private/backups/biblioteca2-ampliacion-20261031-174602.sql).

SHA-256: `c69e45feef52dd22183938cb560b3c200aa81deff29e891462405218151c8a33`. Se verificaron estructura, cantidad de INSERT por tabla, estabilidad de filas durante el respaldo y checksum.

| Tabla | Antes | Después |
|---|---:|---:|
| libros | 28 | 55 |
| libro_fichas | 28 | 55 |
| personas | 32 | 59 |
| autores | 12 | 39 |
| autor_fichas | 12 | 39 |
| categorias | 11 | 13 |
| categoria_fichas | 11 | 13 |
| editoriales | 21 | 38 |
| editorial_fichas | 12 | 32 |
| asigna_autores | 28 | 55 |
| asigna_categorias | 28 | 55 |
| asigna_editoriales | 28 | 55 |
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
| rol_permisos | 41 | 41 |

Se repitió la carga y se compararon hashes de todas las tablas: sin diferencias. La auditoría posterior volvió a comparar el contenido completo de diecinueve tablas protegidas con el respaldo inicial, incluidas cuentas, permisos, solicitudes, sesiones operativas, préstamos, detalles y devoluciones. También comprobó cero ISBN normalizados duplicados, cero autores bibliográficos duplicados y cero ejemplares para los veintisiete títulos nuevos. [Auditoría final](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/auditoria-final.json).

**Pruebas realizadas**

- PHP: 57 pruebas y 2412 aserciones correctas, sobre SQLite en memoria. Se probó repetir el seeder dos días después para confirmar que tampoco cambian las fechas verificadas.
- Importación: conservación de ISBN pendientes, UUID, relaciones, ejemplares y préstamos; rollback de toda la carga ante un ISBN de otra identidad; sinopsis y biografías entre 60 y 100 palabras.
- Autorización y catálogo: búsquedas, filtros, paginación, rechazo de solicitudes sin inventario, ausencia de ID internos en las vistas del lector, privacidad y permisos revocados. Las pruebas anteriores de límites, aprobación, rechazo y duplicados siguen pasando.
- JavaScript: 3 pruebas correctas, incluidas alternativa de imagen y prevención de reenvío. Se conservaron las pruebas de matriz y resumen.
- Auditoría en biblioteca2: 194 respuestas GET correctas, 38 relaciones verificadas y cero errores. Incluye todas las fichas y biografías. [Salida de verificación](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/verificacion-produccion.txt).
- Navegador aislado: 55 títulos; secciones del inicio; filtros; siete libros científicos; resúmenes de tres líneas; ficha en inglés; biografía y obra de Stephen Hawking; carga de la nueva cubierta de Cien años de soledad.
- Encabezado y móvil: comprobado en escritorio de 1280 px y pantalla de 390 px, durante desplazamiento; sin desbordamiento horizontal; menú abre y cierra con Escape.
- Préstamo en SQLite separado: lector selecciona dos títulos, confirma una solicitud, bibliotecario activa recepción y aprueba; se vincula un préstamo único y el lector ve ambos títulos y el vencimiento 14/11/2026. La cuenta BIBLIOTECARIO no carga lector.css. Sin errores de consola.
- `git diff --check` correcto; formateador aplicado a los archivos PHP correspondientes.

**Archivos modificados o añadidos en esta tarea**

- [app/Http/Controllers/LectorController.php](C:/xampp/htdocs/biblioteca3/app/Http/Controllers/LectorController.php)
- [app/Models/Libro.php](C:/xampp/htdocs/biblioteca3/app/Models/Libro.php)
- [app/Models/LibroFicha.php](C:/xampp/htdocs/biblioteca3/app/Models/LibroFicha.php)
- [database/migrations/2026_10_31_000006_add_revision_bibliografica.php](C:/xampp/htdocs/biblioteca3/database/migrations/2026_10_31_000006_add_revision_bibliografica.php)
- [database/seeders/EnriquecerCatalogoSeeder.php](C:/xampp/htdocs/biblioteca3/database/seeders/EnriquecerCatalogoSeeder.php)
- [database/seeders/CompletarCatalogoSeeder.php](C:/xampp/htdocs/biblioteca3/database/seeders/CompletarCatalogoSeeder.php)
- [database/data/ampliacion.json](C:/xampp/htdocs/biblioteca3/database/data/ampliacion.json)
- [database/data/revision-catalogo.json](C:/xampp/htdocs/biblioteca3/database/data/revision-catalogo.json)
- [database/data/enriquecimiento.json](C:/xampp/htdocs/biblioteca3/database/data/enriquecimiento.json)
- [database/data/bibliografia.php](C:/xampp/htdocs/biblioteca3/database/data/bibliografia.php)
- [resources/views/layouts/lector.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/layouts/lector.blade.php)
- [resources/views/lector/inicio.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/inicio.blade.php)
- [resources/views/lector/tarjeta.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/tarjeta.blade.php)
- [resources/views/lector/ficha.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/ficha.blade.php)
- [resources/views/lector/bibliografia.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/bibliografia.blade.php)
- [resources/views/lector/autor-tarjeta.blade.php](C:/xampp/htdocs/biblioteca3/resources/views/lector/autor-tarjeta.blade.php)
- [public/assets/css/lector.css](C:/xampp/htdocs/biblioteca3/public/assets/css/lector.css)
- [scripts/instalar-ampliacion.php](C:/xampp/htdocs/biblioteca3/scripts/instalar-ampliacion.php)
- [scripts/auditar-ampliacion.php](C:/xampp/htdocs/biblioteca3/scripts/auditar-ampliacion.php)
- [scripts/consultar-bibliografia.py](C:/xampp/htdocs/biblioteca3/scripts/consultar-bibliografia.py)
- [scripts/contrastar-ediciones.py](C:/xampp/htdocs/biblioteca3/scripts/contrastar-ediciones.py)
- [scripts/comprobar-portadas.py](C:/xampp/htdocs/biblioteca3/scripts/comprobar-portadas.py)
- [scripts/comprobar-portadas-existentes.py](C:/xampp/htdocs/biblioteca3/scripts/comprobar-portadas-existentes.py)
- [tests/Feature/LectorCatalogoTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/LectorCatalogoTest.php)
- [tests/Feature/RbacYPaginacionTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/RbacYPaginacionTest.php)
- [tests/Feature/SeguridadBibliotecaTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/SeguridadBibliotecaTest.php)
- [tests/Feature/SolicitudesPrestamoTest.php](C:/xampp/htdocs/biblioteca3/tests/Feature/SolicitudesPrestamoTest.php)
- [INFORME_IDENTIDAD_CATALOGO.md](C:/xampp/htdocs/biblioteca3/INFORME_IDENTIDAD_CATALOGO.md)
- [INFORME_AMPLIACION_CATALOGO.md](C:/xampp/htdocs/biblioteca3/INFORME_AMPLIACION_CATALOGO.md)

En los tres tests de otros módulos solo se añadió la nueva migración al entorno aislado. Los manifiestos anteriores recibieron las sinopsis completas, la nueva referencia de Cien años de soledad y las correcciones de notas para evitar regresiones al reutilizarlos. El informe anterior conserva sus recuentos históricos y enlaza a este estado actualizado. Los cambios de otros archivos que aparezcan en Git ya estaban pendientes al iniciar esta tarea.

**Evidencias visuales y estado del repositorio**

[Portada recuperada](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/portada-recuperada.jpg) · [Tarjetas y resúmenes](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/catalogo-escritorio.jpg) · [Encabezado durante desplazamiento](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/encabezado-scroll.jpg) · [Móvil](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/catalogo-movil.jpg) · [Préstamo de prueba](C:/xampp/htdocs/biblioteca3/storage/app/private/ampliacion/prestamo-prueba.jpg)

Las imágenes y cuentas de demostración pertenecen a SQLite aislado; las pruebas no generaron movimientos en biblioteca2. Los respaldos, resultados de API, imágenes de inspección y SQLite se guardan en storage/app/private, excluido de Git.

Rama activa: `mejora-registro-roles`. Sin cambios en main, commit, push ni merge. Estado de trabajo al generar este informe:

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
?? INFORME_AMPLIACION_CATALOGO.md
?? INFORME_IDENTIDAD_CATALOGO.md
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
?? database/migrations/2026_10_31_000006_add_revision_bibliografica.php
?? database/seeders/CatalogoVerificadoSeeder.php
?? database/seeders/CompletarCatalogoSeeder.php
?? database/seeders/EnriquecerCatalogoSeeder.php
?? public/assets/css/lector.css
?? public/assets/img/
?? public/assets/js/lector.js
?? resources/views/layouts/lector.blade.php
?? resources/views/lector/
?? scripts/auditar-ampliacion.php
?? scripts/auditar-catalogo.php
?? scripts/comprobar-portadas-existentes.py
?? scripts/comprobar-portadas.py
?? scripts/consultar-bibliografia.py
?? scripts/contrastar-ediciones.py
?? scripts/instalar-ampliacion.php
?? scripts/instalar-enriquecimiento.php
?? scripts/instalar-lector.php
?? tests/Feature/LectorCatalogoTest.php
?? tests/JavaScript/lector.test.cjs
```
