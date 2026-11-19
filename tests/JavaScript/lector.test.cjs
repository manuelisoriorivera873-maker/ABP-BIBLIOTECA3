const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const vm = require('node:vm');

test('el lector utiliza una portada alternativa y evita reenvíos del formulario', () => {
    let fallo, enviar;
    const img = { src: 'https://ejemplo.invalid/portada.jpg', dataset: { portadaAlternativa: '/portada.svg' }, complete: false, naturalWidth: 0, addEventListener: (_,fn) => { fallo = fn; } };
    const button = {disabled:false,textContent:'Confirmar y enviar solicitud'};
    const form = {addEventListener:(_,fn)=>{enviar=fn;},querySelector:()=>button};
    const document = {getElementById:()=>null,addEventListener:(_,fn)=>fn(),querySelectorAll:s=>s.startsWith('img')?[img]:(s==='[data-envio-unico]'?[form]:[])};
    vm.runInNewContext(readFileSync('public/assets/js/lector.js','utf8'),{document});
    fallo();
    assert.equal(img.src,'/portada.svg');
    fallo();
    assert.equal(img.src,'/portada.svg');
    enviar();
    assert.equal(button.disabled,true);
    assert.equal(button.textContent,'Enviando solicitud…');
});
