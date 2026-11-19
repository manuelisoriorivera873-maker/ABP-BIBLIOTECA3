const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
function montar(fetch){
 const listeners=[];
 const buttons=[0,1].map(()=>({dataset:{favorito:'/lector/favoritos/uuid',csrf:'token'},pressed:'false',disabled:false,textContent:'☆',nextElementSibling:{textContent:''},addEventListener(_,fn){this.click=fn;},getAttribute(){return this.pressed;},setAttribute(_,v){this.pressed=v;}}));
 const document={getElementById:()=>null,addEventListener:(_,f)=>listeners.push(f),querySelectorAll:s=>s==='[data-favorito]'?buttons:[]};
 vm.runInNewContext(fs.readFileSync('public/assets/js/lector.js','utf8'),{document,fetch});listeners.forEach(f=>f());return buttons;
}
test('favoritos guardan por PUT/DELETE y sincronizan tarjetas repetidas',async()=>{
 const requests=[];const b=montar(async(url,options)=>{requests.push(options);return {ok:true,json:async()=>({favorito:options.method==='PUT'})};});
 await b[0].click();assert.equal(b[0].pressed,'true');assert.equal(b[1].textContent,'★');assert.equal(requests[0].method,'PUT');assert.equal(requests[0].headers['X-CSRF-TOKEN'],'token');
 await b[1].click();assert.equal(requests[1].method,'DELETE');assert.equal(b[0].pressed,'false');assert.equal(b[1].disabled,false);
});
test('error de red conserva la estrella y permite reintentar',async()=>{
 const b=montar(async()=>({ok:false}));await b[0].click();assert.equal(b[0].pressed,'false');assert.equal(b[0].textContent,'☆');assert.equal(b[0].disabled,false);assert.match(b[0].nextElementSibling.textContent,/No fue posible/);
});
