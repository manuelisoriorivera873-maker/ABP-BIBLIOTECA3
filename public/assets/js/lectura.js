document.addEventListener('DOMContentLoaded', async () => {
    const visor = document.getElementById('lectura-visor');
    if (!visor) return;
    const estado = document.getElementById('lectura-estado');
    const anterior = document.getElementById('lectura-anterior');
    const siguiente = document.getElementById('lectura-siguiente');
    const tamano = document.getElementById('lectura-tamano');
    let posicion = visor.dataset.posicion;
    let timer;
    let guardados = Promise.resolve();
    const guardar = () => {
        if (!posicion) return;
        const body = JSON.stringify({posicion, tamano:Number(tamano.value)});
        // Serializa actualizaciones para que una petición lenta no sobrescriba otra posterior.
        guardados = guardados.catch(() => {}).then(async () => {
            const response = await fetch(visor.dataset.progreso, {method:'PUT', credentials:'same-origin',
                headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':visor.dataset.csrf}, body, keepalive:true});
            if (!response.ok) throw new Error('No se pudo guardar tu avance. Recarga e inténtalo de nuevo.');
            estado.textContent = 'Avance guardado';
        }).catch(() => { estado.textContent = 'No se pudo guardar tu avance. Recarga e inténtalo de nuevo.'; });
    };
    const programar = () => {clearTimeout(timer); timer = setTimeout(guardar, 500);};
    window.addEventListener('pagehide', () => {clearTimeout(timer);guardar();});
    document.addEventListener('visibilitychange', () => {if (document.hidden) {clearTimeout(timer);guardar();}});
    try {
        if (visor.dataset.formato === 'epub') {
            const response = await fetch(visor.dataset.archivo, {credentials:'same-origin'});
            if (!response.ok) throw new Error('Archivo no disponible.');
            const book = ePub(await response.arrayBuffer());
            const rendition = book.renderTo(visor, {width:'100%', height:'100%', spread:'none', allowScriptedContent:false, allowPopups:false});
            rendition.themes.fontSize(tamano.value + '%');
            try { await rendition.display(posicion || undefined); }
            catch { await rendition.display(); estado.textContent = 'La posición anterior no está disponible; comienza desde el inicio.'; }
            anterior.disabled = siguiente.disabled = false;
            let navegando = false;
            const mover = async direccion => {
                if (navegando) return;
                navegando = true;
                try { await rendition[direccion](); } catch { estado.textContent = 'No se pudo cambiar de página.'; }
                finally { navegando = false; }
            };
            anterior.addEventListener('click', () => mover('prev'));
            siguiente.addEventListener('click', () => mover('next'));
            tamano.addEventListener('change', () => {rendition.themes.fontSize(tamano.value + '%');programar();});
            rendition.on('relocated', location => {
                posicion = location.start.cfi;
                anterior.disabled = location.atStart;
                siguiente.disabled = location.atEnd;
                estado.textContent = 'Lectura lista'; programar();
            });
            posicion = rendition.currentLocation()?.start?.cfi || posicion;
            estado.textContent = 'Lectura lista';
        } else {
            const pdfjs = await import(visor.dataset.vendor + '/pdf.mjs');
            pdfjs.GlobalWorkerOptions.workerSrc = visor.dataset.vendor + '/pdf.worker.mjs';
            const pdf = await pdfjs.getDocument({url:visor.dataset.archivo, withCredentials:true, isEvalSupported:false, cMapUrl:visor.dataset.vendor+'/cmaps/', cMapPacked:true, standardFontDataUrl:visor.dataset.vendor+'/standard_fonts/', wasmUrl:visor.dataset.vendor+'/wasm/'}).promise;
            let pagina = Math.max(1, Math.min(pdf.numPages, Number(posicion) || 1));
            const canvas = document.createElement('canvas');
            canvas.setAttribute('role', 'img');
            visor.appendChild(canvas);
            let renderizando = false;
            const mostrar = async () => {
                if (renderizando) return;
                renderizando = true;
                anterior.disabled = siguiente.disabled = true;
                try {
                    const page = await pdf.getPage(pagina);
                    const normal = page.getViewport({scale:1});
                    const viewport = page.getViewport({scale:(visor.clientWidth / normal.width) * Number(tamano.value) / 100});
                    const ratio = Math.min(window.devicePixelRatio || 1, 2);
                    canvas.width = Math.floor(viewport.width * ratio); canvas.height = Math.floor(viewport.height * ratio);
                    canvas.style.width = viewport.width + 'px'; canvas.style.height = viewport.height + 'px';
                    canvas.setAttribute('aria-label', 'Página ' + pagina + ' de ' + pdf.numPages);
                    await page.render({canvasContext:canvas.getContext('2d'),viewport,transform:[ratio,0,0,ratio,0,0]}).promise;
                    posicion = String(pagina); estado.textContent = 'Página ' + pagina + ' de ' + pdf.numPages;programar();
                } finally {renderizando = false;anterior.disabled = pagina===1;siguiente.disabled=pagina===pdf.numPages;}
            };
            anterior.addEventListener('click', async () => {if (!renderizando && pagina>1) {pagina--;await mostrar().catch(() => {estado.textContent='No se pudo mostrar la página.';});}});
            siguiente.addEventListener('click', async () => {if (!renderizando && pagina<pdf.numPages) {pagina++;await mostrar().catch(() => {estado.textContent='No se pudo mostrar la página.';});}});
            tamano.addEventListener('change', () => mostrar().catch(() => {estado.textContent='No se pudo ajustar la página.';}));
            let resize;
            window.addEventListener('resize', () => {clearTimeout(resize);resize=setTimeout(() => mostrar().catch(() => {}),200);});
            await mostrar();
        }
    } catch { estado.textContent = 'No se pudo abrir el libro. Vuelve a la ficha para consultar su fuente.'; }
});
