# Bibliotecas del lector

Versiones fijadas en package-lock.json. Desde la raíz del proyecto:

    npm ci --ignore-scripts --prefix scripts/lector-vendor
    node scripts/lector-vendor/sync.mjs

Se publican solamente los archivos necesarios y sus licencias. node_modules permanece fuera de public y no se versiona. EPUB.js 0.3.93 (BSD), JSZip 3.10.1 (MIT/GPL; se utiliza MIT), PDF.js 5.4.394 (Apache 2.0). El parser XML de las dependencias de Node está fijado mediante override en 0.9.12; el navegador utiliza su DOMParser.

No se habilita allowScriptedContent ni allowPopups en EPUB.js. El catálogo solo habilita archivos revisados con checksum fijo, no admite cargas de usuarios ni rutas arbitrarias.
