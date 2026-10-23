const assert=require('node:assert/strict');
const {ranges}=require('../media/compare.js');
const cases=[['same','same'],['Bonjour !','Bonjour\u202f!'],['test...','test…'],['"belle"','« belle »'],['','added'],['removed',''],['été','été'],['😀...','😀…'],['a'.repeat(1200),'b'.repeat(1200)]];
for(const [a,b] of cases){const [left,right]=ranges(a,b); const remove=(s,rs)=>[...rs].reverse().reduce((v,[x,y])=>v.slice(0,x)+v.slice(y),s); assert.equal(remove(a,left),remove(b,right)); if(a===b) assert.deepEqual([left,right],[[],[]]);}
const [left,right]=ranges('Bonjour !','Bonjour\u202f!'); assert.deepEqual(left,[[7,8]]); assert.deepEqual(right,[[7,8]]);
console.log('10 comparison tests passed');
const {edits,applyEdits}=require('../media/compare.js');
for(const [a,b] of [...cases,['<p>A &quot;mot&quot;...</p>','<p>A «\u00a0mot\u00a0»…</p>']]) {
 const changes=edits(a,b); assert.equal(applyEdits(a,changes),a);
 changes.forEach(c=>c.accepted=true); assert.equal(applyEdits(a,changes),b);

}
const changes=edits('Bonjour ! Suite...','Bonjour\u202f! Suite…');
assert.equal(changes.length,2); changes[1].accepted=true;
assert.equal(applyEdits('Bonjour ! Suite...',changes),'Bonjour ! Suite…');
console.log('Selective application: all accepted, all refused, mixed and HTML entities passed');
