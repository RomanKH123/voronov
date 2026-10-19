import assert from 'node:assert/strict';
import vm from 'node:vm';
import {readFileSync} from 'node:fs';

class Element {
    constructor(tag='div') { this.tagName=tag;this.children=[];this.dataset={};this.events={};this.attrs={};this.className='';this.classList={add(){},remove(){}}; }
    append(...nodes) {for(const n of nodes){n.remove();this.children.push(n);n.parent=this;}}
    remove(){if(this.parent)this.parent.children.splice(this.parent.children.indexOf(this),1);this.parent=null;}
    before(n){this.parent.insertBefore(n,this);}
    after(n){this.parent.insertBefore(n,this.nextElementSibling);}
    insertBefore(n,ref){n.remove();this.children.splice(ref?this.children.indexOf(ref):this.children.length,0,n);n.parent=this;}
    setAttribute(k,v){this.attrs[k]=v;}
    addEventListener(k,fn){(this.events[k]??=[]).push(fn);}
    async fire(k,e={}){for(const fn of this.events[k]||[])await fn(e);}
    get nextElementSibling(){return this.parent?.children[this.parent.children.indexOf(this)+1]||null;}
    get previousElementSibling(){return this.parent?.children[this.parent.children.indexOf(this)-1]||null;}
    querySelectorAll(s){return this.children.flatMap(c=>[...(matches(c,s)?[c]:[]),...c.querySelectorAll(s)]);}
    querySelector(s){return this.querySelectorAll(s)[0]||null;}
    getBoundingClientRect(){return {top:0,height:100};}
    focus(){}
}
function matches(el,s){
    if(s.startsWith('#'))return el.id===s.slice(1);
    if(s.startsWith('[data-move='))return el.dataset.move===s.match(/"(.*?)"/)[1];
    if(s==='[role=status]')return el.attrs.role==='status';
    if(s.startsWith('.'))return el.className.split(' ').includes(s.slice(1).split('[')[0]);
    return el.tagName===s;
}
const root=new Element(),list=new Element();root.append(list);
function populate(ids){
    list.children.slice().forEach(r=>r.remove());
    for(const id of ids){const r=new Element(),t=new Element();r.className='table-row';r.dataset.id=String(id);t.className='table-title';t.textContent='Work '+id;r.append(t);list.append(r);}
}
let request,fail=false;
const window={addEventListener(k,fn){this[k]=fn;}};
const context=vm.createContext({window,document:{createElement:t=>new Element(t),getElementById:id=>root.querySelector('#'+id)},fetch:async(url,options)=>{
    request={url,...options};return {ok:!fail,json:async()=>({success:!fail,message:'Save failed'})};
}});
vm.runInContext(readFileSync(new URL('../CMM/work-order.js',import.meta.url),'utf8'),context);
populate([1,2,3,4,5,6,7]);window.initWorkOrder(list,Array(7).fill({}));
const ids=()=>list.children.map(r=>Number(r.dataset.id));
const toolbar=()=>root.querySelector('#work-order-toolbar');
const save=()=>toolbar().querySelector('button');
assert(save().disabled);
await list.children[2].querySelector('[data-move="up"]').fire('click');
assert.deepEqual(ids(),[1,3,2,4,5,6,7]);assert(window.workOrderDirty);
await list.children[6].querySelector('.work-drag').fire('dragstart',{dataTransfer:{setData(){}}});
await list.children[0].fire('drop',{clientY:0,preventDefault(){}});
assert.deepEqual(ids(),[7,1,3,2,4,5,6]);
fail=true;await save().fire('click');assert(window.workOrderDirty);assert(!save().disabled);
assert.equal(toolbar().querySelector('[role=status]').textContent,'Save failed');
fail=false;await save().fire('click');assert(!window.workOrderDirty);assert(save().disabled);
assert.equal(request.url,'/CMM/api.php?action=reorder');
assert.deepEqual(JSON.parse(request.body).ids,[7,1,3,2,4,5,6]);
await list.children[0].querySelector('[data-move="down"]').fire('click');
let warned=false;window.beforeunload({preventDefault(){warned=true;}});assert(warned);
populate([]);window.initWorkOrder(list,[]);assert(!toolbar());assert(!window.workOrderDirty);
console.log('PASS: arrows, drag/drop, save payload, failure retry, dirty state and empty list.');
