document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const menu = document.getElementById('sidebarButton');
    if (sidebar && menu) {
        menu.setAttribute('aria-controls', 'sidebar');
        menu.setAttribute('aria-expanded', String(sidebar.classList.contains('open')));
        // app.js conserva la apertura original; aquí se completa el cierre accesible.
        menu.addEventListener('click', () => menu.setAttribute('aria-expanded', String(sidebar.classList.contains('open'))));
        const cerrar = () => {
            sidebar.classList.remove('open');
            menu.setAttribute('aria-expanded', 'false');
        };
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                cerrar();
                menu.focus();
            }
        });
        document.addEventListener('click', event => {
            if (!sidebar.contains(event.target) && !menu.contains(event.target)) cerrar();
        });
    }
    document.querySelectorAll('img[data-portada-alternativa]').forEach(img => {
        const alternativa = () => { if (img.src !== img.dataset.portadaAlternativa) img.src = img.dataset.portadaAlternativa; };
        img.addEventListener('error', alternativa);
        if (img.complete && img.naturalWidth === 0) alternativa();
    });
    document.querySelectorAll('[data-envio-unico]').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Enviando solicitud…';
        });
    });
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-favorito]').forEach(button => {
        button.addEventListener('click', async () => {
            const estado = button.nextElementSibling;
            const deseado = button.getAttribute('aria-pressed') !== 'true';
            button.disabled = true;
            estado.textContent = 'Guardando…';
            try {
                const response = await fetch(button.dataset.favorito, {
                    method: deseado ? 'PUT' : 'DELETE', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': button.dataset.csrf }
                });
                if (!response.ok) throw new Error('No fue posible guardar. Recarga la página y vuelve a intentarlo.');
                const result = await response.json();
                // La portada puede aparecer en más de una sección de la misma página.
                document.querySelectorAll('[data-favorito]').forEach(other => {
                    if (other.dataset.favorito === button.dataset.favorito) {
                        other.setAttribute('aria-pressed', String(result.favorito));
                        other.textContent = result.favorito ? '★' : '☆';
                    }
                });
                estado.textContent = result.favorito ? 'Guardado en favoritos' : 'Quitado de favoritos';
            } catch { estado.textContent = 'No fue posible guardar. Recarga la página y vuelve a intentarlo.'; }
            finally { button.disabled = false; }
        });
    });
});
