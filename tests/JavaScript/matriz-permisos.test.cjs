const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { resolve } = require('node:path');
const vm = require('node:vm');

test('la matriz sincroniza Ver y CRUD sin alterar otros módulos ni restricciones', () => {
    class Casilla {
        constructor(modulo, accion, checked = false, disabled = false) {
            Object.assign(this, { type: 'checkbox', dataset: { modulo, accion }, checked, disabled });
        }
    }
    const ver = new Casilla('libros', 'ver', true);
    const crear = new Casilla('libros', 'crear', true);
    const editar = new Casilla('libros', 'editar', true);
    const eliminar = new Casilla('libros', 'eliminar', true);
    const otroModulo = new Casilla('autores', 'ver', true);
    const restringida = new Casilla('libros', 'administrar', false, true);
    const grupo = [ver, crear, editar, eliminar, otroModulo, restringida];
    let cambio;
    const formulario = {
        addEventListener(tipo, callback) { if (tipo === 'change') cambio = callback; },
        querySelectorAll() { return grupo; },
    };
    const document = {
        addEventListener(tipo, callback) { if (tipo === 'DOMContentLoaded') callback(); },
        querySelectorAll(selector) { return selector === '[data-matriz-permisos]' ? [formulario] : []; },
        getElementById() { return null; },
        querySelector() { return null; },
    };
    vm.runInNewContext(readFileSync(resolve(__dirname, '../../public/assets/js/app.js'), 'utf8'), { document, HTMLInputElement: Casilla });
    ver.checked = false;
    cambio({ target: ver });
    assert.deepEqual(grupo.slice(0, 4).map(input => input.checked), [false, false, false, false]);
    assert.equal(otroModulo.checked, true);
    eliminar.checked = true;
    cambio({ target: eliminar });
    assert.equal(ver.checked, true);
    assert.equal(eliminar.checked, true);
    assert.equal(crear.checked, false);
    cambio({ target: restringida });
    assert.equal(restringida.disabled, true);
    assert.equal(restringida.checked, false);
    assert.equal(ver.checked, true);
});
