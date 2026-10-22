const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

test('el resumen muestra varios títulos y recalcula cantidades sin interpretar HTML', () => {
    const inputs = [
        { value: '3', dataset: { titulo: 'Título <uno>' } },
        { value: '2', dataset: { titulo: 'Título dos' } },
        { value: '2', dataset: { titulo: 'Agotado' }, disabled: true },
    ];
    let cambio;
    const filas = [];
    const resumen = { replaceChildren: () => { filas.length = 0; }, appendChild: fila => filas.push(fila) };
    const total = {};
    const form = { querySelectorAll: () => inputs, addEventListener: (evento, fn) => { assert.equal(evento, 'input'); cambio = fn; } };
    const document = { getElementById: id => ({ solicitarPrestamo: form, resumenSolicitud: resumen, totalSolicitud: total })[id], createElement: () => ({}) };
    vm.runInNewContext(readFileSync('public/assets/js/solicitudes.js', 'utf8'), { document });
    assert.deepEqual(filas.map(fila => fila.textContent), ['Título <uno>: 3', 'Título dos: 2']);
    assert.equal(total.textContent, 'Total: 5 ejemplares');
    inputs[0].value = '0';
    cambio();
    assert.deepEqual(filas.map(fila => fila.textContent), ['Título dos: 2']);
    assert.equal(total.textContent, 'Total: 2 ejemplares');
});
