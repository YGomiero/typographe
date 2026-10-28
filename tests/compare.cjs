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

// RobertG: long articles used to collapse into a single highlight crossing HTML.
const paragraph='<p>En vue de (re)photographier des stylos, un petit exercice.</p>';
const hyphenated=paragraph.replace('photographier','photo\u00adgra\u00adphier').replace('exercice','exer\u00adcice');
for(const repeat of [1,40,500]) {
  const original=paragraph.repeat(repeat), corrected=hyphenated.repeat(repeat);
  const changes=edits(original,corrected);
  assert.equal(changes.length,repeat*2,'Each hyphenated word remains independently selectable');
  assert.ok(changes.every(c=>!/[<>\s]/.test(c.before+c.after)),'No highlight crosses a word or an HTML element');
  assert.equal(applyEdits(original,changes),original);
  changes.forEach(c=>c.accepted=true);
  assert.equal(applyEdits(original,changes),corrected);
  changes[0].accepted=false;
  assert.equal(applyEdits(original,changes),corrected.replace('photo\u00adgra\u00adphier','photographier'));
}
for(const [original,corrected] of [
  ['Un petit exercice','Un petit exer\u00adcice'],
  ['<p>S’écartant de la route</p>','<p>S’écar\u00adtant de la route</p>'],
  [paragraph.repeat(40)+' Fin...',hyphenated.repeat(40)+' Fin…'],
  ['Début... '+paragraph.repeat(40)+' Fin...', 'Début… '+hyphenated.repeat(40)+' Fin…'],
  ['<p>"Bonjour" ! Un exercice...</p>'.repeat(100), '<p>«\u00a0Bonjour\u00a0»\u202f! Un exer\u00adcice…</p>'.repeat(100)],
  ['&quot;Exercice&quot; &amp; suite...', '«\u00a0Exer\u00adcice\u00a0» &amp; suite…'],
  ['<p>😀 exercice</p>', '<p>😀 exer\u00adcice</p>'],
  ['exer\u00adcice','exercice']
]) {
  const changes=edits(original,corrected);
  assert.equal(applyEdits(original,changes),original);
  changes.forEach(c=>c.accepted=true);
  assert.equal(applyEdits(original,changes),corrected);
  assert.ok(changes.every(c=>!/[<>]/.test(c.before+c.after)),'Unchanged HTML never joins a typography correction');
}
// Exercise both alignment paths with reproducible unrelated insertions/deletions.
let seed=20261028;
const random=()=>((seed=(Math.imul(seed,1664525)+1013904223)>>>0)/4294967296);
const tokens=['exercice','exer\u00adcice','été','😀',' ','.','…','!','\u202f','&amp;','\n'];
for(let n=0;n<100;n++) {
  const original=Array.from({length:n<90?30:1800},()=>tokens[Math.floor(random()*tokens.length)]).join('');
  let corrected=original;
  for(let i=0;i<20;i++) {
    const at=Math.floor(random()*corrected.length);
    corrected=corrected.slice(0,at)+tokens[Math.floor(random()*tokens.length)]+corrected.slice(at+Math.floor(random()*3));
  }
  const changes=edits(original,corrected);
  assert.equal(applyEdits(original,changes),original);
  changes.forEach(c=>c.accepted=true);
  assert.equal(applyEdits(original,changes),corrected);
}
console.log('RobertG regressions: word-level hyphenation, long/mixed HTML, individual rejection and 100 randomized round trips passed');
