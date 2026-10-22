(() => {
    const form = document.getElementById('solicitarPrestamo');
    if (!form) return;
    const resumen = document.getElementById('resumenSolicitud');
    const total = document.getElementById('totalSolicitud');
    function actualizar() {
        resumen.replaceChildren();
        let cantidad = 0;
        form.querySelectorAll('input[data-titulo]').forEach(input => {
            if (input.disabled) return;
            const valor = Number(input.value) || 0;
            if (valor <= 0) return;
            cantidad += valor;
            const fila = document.createElement('li');
            fila.textContent = `${input.dataset.titulo}: ${valor}`;
            resumen.appendChild(fila);
        });
        total.textContent = `Total: ${cantidad} ejemplares`;
    }
    form.addEventListener('input', actualizar);
    actualizar();
})();
