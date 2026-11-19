import {copyFile,cp,mkdir} from 'node:fs/promises';
import {fileURLToPath} from 'node:url';
const source = new URL('./node_modules/',import.meta.url);
const target = new URL('../../public/assets/vendor/lectura/',import.meta.url);
await mkdir(target,{recursive:true});
for (const [from,to] of [['epubjs/dist/epub.min.js','epub.min.js'],['jszip/dist/jszip.min.js','jszip.min.js'],['pdfjs-dist/build/pdf.mjs','pdf.mjs'],['pdfjs-dist/build/pdf.worker.mjs','pdf.worker.mjs'],['epubjs/LICENSE','LICENSE-epubjs'],['jszip/LICENSE.markdown','LICENSE-jszip'],['pdfjs-dist/LICENSE','LICENSE-pdfjs']]) await copyFile(new URL(from,source),new URL(to,target));
for(const directory of ['cmaps','standard_fonts','wasm']) await cp(fileURLToPath(new URL('pdfjs-dist/'+directory+'/',source)),fileURLToPath(new URL(directory+'/',target)),{recursive:true});
console.log('Bibliotecas y licencias del lector copiadas desde el lockfile local.');
