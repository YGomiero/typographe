/* Preview-only differences. No marked HTML is ever sent back to the editor. */
((root) => {
  'use strict';
  function ranges(a, b) {
    const tokenize = s => [...s.matchAll(/&(?:#[0-9]+|#x[0-9a-f]+|[a-z][a-z0-9]+);|[\p{L}\p{N}]+|[^\p{L}\p{N}]/giu)].map(m => ({v:m[0], p:m.index}));
    const x = tokenize(a), y = tokenize(b), left = [], right = [];
    let start = 0, xe = x.length, ye = y.length;
    while (start < xe && start < ye && x[start].v === y[start].v) start++;
    while (xe > start && ye > start && x[xe-1].v === y[ye-1].v) { xe--; ye--; }
    const add = (out,t,i) => out.push([t[i].p,t[i].p+t[i].v.length]);
    if ((xe-start)*(ye-start) > 1000000) {
      if (xe > start) left.push([x[start].p,x[xe-1].p+x[xe-1].v.length]);
      if (ye > start) right.push([y[start].p,y[ye-1].p+y[ye-1].v.length]);
      return [left,right];
    }
    const width = ye-start+1, table = new Uint32Array((xe-start+1)*width);
    for (let i=xe-1;i>=start;i--) for (let j=ye-1;j>=start;j--) {
      const p=(i-start)*width+j-start;
      table[p]=x[i].v===y[j].v ? 1+table[p+width+1] : Math.max(table[p+width],table[p+1]);
    }
    let i=start,j=start;
    while(i<xe || j<ye) {
      if(i<xe && j<ye && x[i].v===y[j].v) {i++;j++;}
      else if(i<xe && (j===ye || table[(i-start+1)*width+j-start]>=table[(i-start)*width+j-start+1])) add(left,x,i++);
      else add(right,y,j++);
    }
    return [left,right];
  }
  function safeTree(html, markers = {}) {
    const template=document.createElement('template'); template.innerHTML=html;
    const out=document.createElement('div');
    const allowed=new Set('p div span br hr strong b em i u s del ins blockquote ul ol li h1 h2 h3 h4 h5 h6 table thead tbody tfoot tr td th pre code sub sup dl dt dd'.split(' '));
    const blocked=new Set('script style iframe object embed link meta base template svg math form input textarea button select video audio'.split(' '));
    function copy(source,target) {
      for(const node of source.childNodes) {
        if(node.nodeType===3) target.append(document.createTextNode(node.data));
        else if(node.nodeType===1 && !blocked.has(node.localName)) {
          if(node.localName==='img') {target.append(document.createTextNode(node.getAttribute('alt') || ''));continue;}
          const marker=Object.prototype.hasOwnProperty.call(markers,node.localName);
          const next=marker ? document.createElement('mark') : allowed.has(node.localName) ? document.createElement(node.localName) : document.createDocumentFragment();
          if(marker) next.dataset.correction=String(markers[node.localName]);
          copy(node,next); target.append(next);
        }
      }
    }
    copy(template.content,out); return out;
  }
  function mark(tree, changes, label) {
    const walker=document.createTreeWalker(tree,NodeFilter.SHOW_TEXT), nodes=[];
    while(walker.nextNode()) nodes.push(walker.currentNode);
    let offset=0;
    for(const node of nodes) {
      const text=node.data, fragment=document.createDocumentFragment(); let cursor=0;
      for(const [a,b] of changes) {
        const from=Math.max(0,a-offset), to=Math.min(text.length,b-offset);
        if(to<=from) continue;
        fragment.append(document.createTextNode(text.slice(cursor,from)));
        const highlight=document.createElement('mark'); highlight.className='typographe-highlight';
        highlight.textContent=text.slice(from,to); highlight.title=label;
        fragment.append(highlight); cursor=to;
      }
      fragment.append(document.createTextNode(text.slice(cursor))); offset+=text.length; node.replaceWith(fragment);
    }
  }
  function edits(a,b) {
    const [left,right]=ranges(a,b), out=[];
    let i=0,j=0,l=0,r=0;
    while(i<a.length || j<b.length) {
      if((left[l] && left[l][0]===i) || (right[r] && right[r][0]===j)) {
        const from=i, target=j;
        while(left[l] && left[l][0]===i) i=left[l++][1];
        while(right[r] && right[r][0]===j) j=right[r++][1];
        out.push({from,to:i,before:a.slice(from,i),after:b.slice(target,j),accepted:false});
      } else {i++;j++;}
    }
    return out;
  }
  function applyEdits(original, changes) {
    return changes.reduceRight((text,c)=>c.accepted ? text.slice(0,c.from)+c.after+text.slice(c.to) : text, original);
  }
  const api={ranges,safeTree,mark,edits,applyEdits};
  if(typeof module==='object' && module.exports) module.exports=api; else root.TypographeCompare=api;
})(typeof window==='undefined' ? globalThis : window);
