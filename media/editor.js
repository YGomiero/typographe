(() => {
  'use strict';
  const start = () => {
    const cfg = Joomla.getOptions('typographe');
    if (!cfg) return;
    const field = document.getElementById(cfg.field);
    if (!field || document.getElementById('typographe-panel')) return;
    const t = (key) => Joomla.Text._(`PLG_SYSTEM_TYPOGRAPHE_${key}`);
    const el = (tag, text, cls) => {
      const node = document.createElement(tag);
      if (text) node.textContent = text;
      if (cls) node.className = cls;
      return node;
    };
    const read = () => ({
      text: field.offsetParent !== null ? field.value : (Joomla.editors?.instances?.[field.id]?.getValue() ?? field.value),
      title: document.getElementById('jform_title').value,
      language: document.getElementById('jform_language')?.value ?? '*'
    });
    const write = (value) => {
      const editor = Joomla.editors?.instances?.[field.id];
      if (editor) editor.setValue(value.text);
      field.value = value.text;
      if (cfg.title) document.getElementById('jform_title').value = value.title;
      field.dispatchEvent(new Event('change', {bubbles:true}));
      document.getElementById('jform_title').dispatchEvent(new Event('change', {bubbles:true}));
    };
    const panel = el('section', null, 'typographe-panel'); panel.id = 'typographe-panel';
    panel.setAttribute('aria-label', 'Typographe');
    const buttons = el('div', null, 'typographe-actions');
    const button = (key, parent, action) => {
      const b = el('button', t(key), 'btn btn-secondary'); b.type = 'button';
      b.addEventListener('click', action); parent.append(b); return b;
    };
    const status = el('p'); status.setAttribute('role','status'); status.setAttribute('aria-live','polite');
    const preview = el('div'); preview.hidden = true;
    const reviewed=el('input'); reviewed.type='hidden'; reviewed.name='typographe_reviewed';
    field.form.append(reviewed);
    let snapshot, result, undoValue, appliedValue;
    const same = (a,b) => a.text === b.text && a.title === b.title && a.language === b.language;
    const check = button('CHECK', buttons, async () => {
      snapshot = read(); preview.hidden = true; apply.disabled = true; check.disabled = true; status.textContent = t('BUSY');
      try {
        const body = new URLSearchParams({...snapshot, kind: cfg.kind, extension: document.getElementById('jform_extension')?.value ?? 'com_content', id: document.getElementById('jform_id')?.value ?? '0',
          catid: document.getElementById('jform_catid')?.value ?? '0', [cfg.token]:'1'});
        const response = await fetch(cfg.url, {method:'POST', body, credentials:'same-origin', headers:{'Accept':'application/json'}});
        const json = await response.json();
        if (!response.ok || !json.success || !json.data?.[0]) throw new Error();
        if (!same(snapshot, read())) { status.textContent = t('STALE'); return; }
        result = json.data[0];
        status.textContent = t(result.changed ? 'CHANGED' : 'CLEAN');
        preview.replaceChildren();
        if (result.changed) {
          const modes=el('div',null,'typographe-actions'), grid=el('div',null,'typographe-comparison');
          const diff=window.TypographeCompare;
          const originalResult={...result};
          const corrections=[...diff.edits(snapshot.title,result.title).map(c=>({...c,field:'title'})),
            ...diff.edits(snapshot.text,result.text).map(c=>({...c,field:'text'}))];
          corrections.forEach(c=>{c.accepted=true;});
          let sourceMode=false;
          const selection=el('div',null,'typographe-selection');
          const count=el('p'); count.setAttribute('role','status');
          const nonce='typo-'+crypto.randomUUID().replaceAll('-','');
          const update = () => {
            result={...originalResult,title:diff.applyEdits(snapshot.title,corrections.filter(c=>c.field==='title')),
              text:diff.applyEdits(snapshot.text,corrections.filter(c=>c.field==='text'))};
            count.textContent=corrections.filter(c=>c.accepted).length+' / '+corrections.length+' '+t('SELECTED');
            render(sourceMode);
          };


          selection.append(el('p',t('CLICK_HELP')),count);
          const interactive = (fieldName,source) => {
            const original=snapshot[fieldName], changes=corrections.map((c,id)=>({...c,id})).filter(c=>c.field===fieldName);
            const markers={}; let html='',cursor=0;
            const plain=source || fieldName==='title', tree=el(plain && source?'pre':'div');
            for(const c of changes) {
              const value=c.accepted?c.after:c.before;
              if(plain) {
                tree.append(document.createTextNode(original.slice(cursor,c.from)));
                const mark=el('mark',value); mark.dataset.correction=String(c.id);tree.append(mark);
              } else {
                const tag=nonce+'-'+c.id;markers[tag]=c.id;
                html+=original.slice(cursor,c.from)+'<'+tag+'>'+value+'</'+tag+'>';
              }
              cursor=c.to;
            }
            let output=tree;
            if(plain) tree.append(document.createTextNode(original.slice(cursor)));
            else output=diff.safeTree(html+original.slice(cursor),markers);
            output.querySelectorAll('mark[data-correction]').forEach(mark=>{
              const id=Number(mark.dataset.correction),c=corrections[id];
              mark.className='typographe-toggle';mark.dataset.accepted=String(c.accepted);
              mark.setAttribute('role','button');mark.tabIndex=0;mark.setAttribute('aria-pressed',String(c.accepted));
              mark.setAttribute('aria-label',t(c.accepted?'REJECT':'ACCEPT')+' '+(id+1));
              mark.title=t(c.accepted?'REJECT':'ACCEPT')+' '+(id+1);
              if(!mark.textContent) mark.textContent='∅';
              const toggle=()=>{c.accepted=!c.accepted;update();grid.querySelector('[data-correction="'+id+'"]')?.focus({preventScroll:true});};
              mark.addEventListener('click',toggle);
              mark.addEventListener('keydown',event=>{if(event.key==='Enter'||event.key===' '){event.preventDefault();toggle();}});
            });
            return output;
          };
          const render = (source) => {
            sourceMode=source;
            const positions=[...grid.querySelectorAll('.typographe-rendered')].map(n=>n.scrollTop);
            grid.replaceChildren();
            rendered.setAttribute('aria-pressed',String(!source));code.setAttribute('aria-pressed',String(source));
            [snapshot,result].forEach((value,index)=>{
              const col=el('div');col.append(el('h4',t(index?'AFTER':'BEFORE')));
              if(cfg.title) col.append(el('strong',t('TITLE')),index?interactive('title',false):el('p',snapshot.title));
              const body=index?interactive('text',source):source?el('pre',snapshot.text):diff.safeTree(snapshot.text);
              if(!source) body.classList.add('typographe-rendered');
              col.append(body);grid.append(col);
              if(!source) body.scrollTop=positions[index]||0;
            });
          };
          const rendered=button('RENDERED',modes,()=>render(false));
          const code=button('SOURCE',modes,()=>render(true));
          preview.append(modes,selection,grid);
          update(); preview.hidden = false; apply.disabled = false;
        }
      } catch { status.textContent = t('ERROR'); }
      finally { check.disabled = false; }
    });
    const apply = button('APPLY', buttons, () => {
      if (!snapshot || !same(snapshot, read())) { status.textContent = t('STALE'); apply.disabled = true; return; }
      undoValue = snapshot; write(result); appliedValue = read();
      reviewed.value=JSON.stringify(appliedValue);
      undo.disabled = false; apply.disabled = true; preview.hidden = true; status.textContent = t('APPLIED');
    }); apply.disabled = true;
    const undo = button('UNDO', buttons, () => {
      if (!same(appliedValue, read())) { status.textContent = t('STALE'); return; }
      write(undoValue); reviewed.value=''; undo.disabled = true; status.textContent = t('UNDONE');
    }); undo.disabled = true;
    button('CLOSE', buttons, () => { preview.hidden = true; apply.disabled = true; });
    panel.append(el('h3','Typographe'),el('p',t('HELP')),buttons,status,preview);
    (field.closest('.control-group') ?? field.parentElement).after(panel);
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',start); else start();
})();
