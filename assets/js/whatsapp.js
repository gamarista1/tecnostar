/* Botón flotante de WhatsApp: un solo icono que abre un pre-chat (canal, motivo y mensaje)
   y, alrededor, un arco con las redes sociales. Sin dependencias. Contrato:
   [data-wa-abrir="<contexto>"] abre el pre-chat con ese contexto; [data-wa-sede="<canal>"] salta el pre-chat;
   [data-pqrs-abrir] abre el pre-chat ya en PQRS; evento window 'ts:wa' con detail.canal al enviar. */
(function () {
  var raiz = document.querySelector('[data-wa]');
  if (!raiz) return;
  var fab = raiz.querySelector('[data-wa-fab]');
  var pre = raiz.querySelector('.ts-wa__pre');
  var cuerpo = pre.querySelector('[data-pre-cuerpo]');
  var chips = pre.querySelector('[data-pre-chips]');
  var form = pre.querySelector('[data-pre-form]');
  var area = pre.querySelector('textarea');
  var inom = pre.querySelector('[data-pre-nombre]');
  var bsub = pre.querySelector('.ts-wa__enviar');
  var contexto = '';
  var canal = null, motivo = '', nombre = '', actual = 1;

  var MOTIVOS = {
    sede: ['Estado de mi pedido', 'Atención al cliente', 'Compras', 'Otro motivo'],
    pqrs: ['Petición', 'Queja', 'Reclamo', 'Sugerencia', 'Felicitación', 'Otro motivo']
  };
  function canales() {
    return [].slice.call(pre.querySelectorAll('[data-pre-canal]')).map(function (n) {
      return { id: n.getAttribute('data-pre-canal'), num: n.getAttribute('data-wa-num'), nombre: n.getAttribute('data-nombre') };
    });
  }
  function porId(id) { return canales().filter(function (c) { return c.id === id; })[0]; }
  function items() { return [].slice.call(raiz.querySelectorAll('.ts-wa__item')); }

  function tema() {
    var frases = { 'busqueda': 'busco un producto que no encuentro en la tienda', 'te-lo-conseguimos': 'busco un producto que no encuentro en la tienda' };
    return frases[contexto] || (contexto && !/^[a-z_]+:[\w-]+$/i.test(contexto) ? contexto : '');
  }
  function enlace(c, lineas) { return 'https://wa.me/' + c.num + '?text=' + encodeURIComponent(lineas.join('\n')); }
  function enviado(c) { try { window.dispatchEvent(new CustomEvent('ts:wa', { detail: { canal: c.id } })); } catch (e) {} }

  function burbuja(texto, yo) {
    var p = document.createElement('p');
    p.className = 'ts-wa__bur' + (yo ? ' ts-wa__bur--yo' : '');
    p.textContent = texto;
    cuerpo.appendChild(p);
    cuerpo.scrollTop = cuerpo.scrollHeight;
  }
  function chip(texto, attr, valor, suave) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'ts-wa__chip' + (suave ? ' ts-wa__chip--suave' : '');
    b.setAttribute(attr, valor);
    b.textContent = texto;
    chips.appendChild(b);
  }

  // Pasos: 1 canal, 2 motivo, 3 nombre, 4 mensaje.
  function paso(n) {
    actual = n;
    cuerpo.textContent = '';
    chips.textContent = '';
    chips.hidden = n >= 3;
    form.hidden = n < 3;
    inom.hidden = n !== 3;
    area.hidden = n !== 4;
    inom.required = n === 3;
    area.required = n === 4;
    bsub.textContent = n === 3 ? 'Continuar' : 'Enviar por WhatsApp';
    pre.querySelector('.ts-wa__nota').hidden = n !== 4;
    burbuja('Hola 👋 ¿Con quién quieres comunicarte?');
    if (n === 1) {
      canal = null; motivo = '';
      canales().forEach(function (c) { chip(c.id === 'pqrs' ? 'PQRS: quejas, sugerencias y más' : c.nombre, 'data-pre-elegir', c.id); });
      return;
    }
    burbuja(canal.nombre, true);
    burbuja(canal.id === 'pqrs'
      ? 'Este canal recibe peticiones, quejas, reclamos y sugerencias de todas las sedes y de la página web, y también tus felicitaciones. ¿Cuál es el motivo?'
      : '¿Qué necesitas?');
    if (n === 2) {
      MOTIVOS[canal.id === 'pqrs' ? 'pqrs' : 'sede'].forEach(function (m) { chip(m, 'data-pre-motivo', m); });
      chip('Cambiar de canal', 'data-pre-canales', '1', true);
      return;
    }
    burbuja(motivo, true);
    burbuja('¿Cuál es tu nombre?');
    if (n === 3) return;
    burbuja(nombre, true);
    burbuja('Mucho gusto, ' + nombre.split(' ')[0] + '. Cuéntanos brevemente lo que necesitas. Si aplica, indica tu número de pedido.');
  }
  function enfocar() {
    if (actual === 3) { inom.focus(); return; }
    if (actual === 4) { area.focus(); return; }
    var b = chips.querySelector('button');
    if (b) b.focus();
  }

  function abrirPre(id) {
    var c = id ? porId(id) : null;
    pre.hidden = false;
    raiz.classList.add('is-open');
    fab.setAttribute('aria-expanded', 'true');
    items().forEach(function (a) { a.tabIndex = 0; });
    if (c) { canal = c; motivo = ''; paso(2); } else paso(1);
    enfocar();
  }
  function cerrar(devolver) {
    if (pre.hidden) return;
    pre.hidden = true;
    raiz.classList.remove('is-open');
    fab.setAttribute('aria-expanded', 'false');
    items().forEach(function (a) { a.tabIndex = -1; });
    if (devolver) fab.focus();
  }

  fab.setAttribute('role', 'button');
  fab.setAttribute('aria-haspopup', 'dialog');
  fab.setAttribute('aria-expanded', 'false');
  fab.removeAttribute('target');

  fab.addEventListener('click', function (e) {
    if (e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();
    if (!pre.hidden) cerrar(false); else { contexto = ''; abrirPre(); }
  });
  fab.addEventListener('keydown', function (e) { if (e.key === ' ') { e.preventDefault(); fab.click(); } });

  pre.addEventListener('click', function (e) {
    var t = e.target.closest('button');
    if (!t) return;
    if (t.hasAttribute('data-pre-elegir')) { canal = porId(t.getAttribute('data-pre-elegir')); paso(2); enfocar(); }
    else if (t.hasAttribute('data-pre-motivo')) { motivo = t.getAttribute('data-pre-motivo'); paso(3); enfocar(); }
    else if (t.hasAttribute('data-pre-canales')) { paso(1); enfocar(); }
    else if (t.hasAttribute('data-pre-atras')) { if (actual === 4) paso(3); else { motivo = ''; paso(2); } enfocar(); }
    else if (t.hasAttribute('data-pre-cerrar')) cerrar(true);
  });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (actual === 3) {
      nombre = inom.value.trim();
      if (!nombre) { inom.focus(); return; }
      paso(4); enfocar();
      return;
    }
    var txt = area.value.trim();
    if (!txt) { area.focus(); return; }
    var l = ['Hola, soy ' + nombre + ' y escribo a Tecnostar (' + canal.nombre + ').', 'Motivo: ' + motivo];
    if (tema()) l.push('Asunto: ' + tema());
    l.push('', txt, '', 'Desde: ' + location.href);
    enviado(canal);
    window.open(enlace(canal, l), '_blank', 'noopener');
    area.value = '';
    cerrar(true);
  });
  raiz.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && !pre.hidden) { e.preventDefault(); cerrar(true); }
  });
  raiz.addEventListener('click', function (e) {
    if (e.target.closest('.ts-wa__item')) cerrar(false);
  });

  document.addEventListener('click', function (e) {
    var sede = e.target.closest && e.target.closest('[data-wa-sede]');
    if (sede) {
      var c = porId(sede.getAttribute('data-wa-sede'));
      if (c) {
        e.preventDefault();
        contexto = sede.getAttribute('data-wa-abrir') || '';
        enviado(c);
        window.open(enlace(c, ['Hola, quiero información: ' + (tema() || document.title) + ' ' + location.href]), '_blank', 'noopener');
        return;
      }
    }
    if (e.target.closest && e.target.closest('[data-pqrs-abrir]')) { e.preventDefault(); contexto = ''; abrirPre('pqrs'); return; }
    var disp = e.target.closest && e.target.closest('[data-wa-abrir]');
    if (disp && !raiz.contains(disp)) {
      e.preventDefault();
      contexto = disp.getAttribute('data-wa-abrir') || '';
      abrirPre();
      return;
    }
    if (!pre.hidden && e.target.isConnected && !raiz.contains(e.target)) cerrar(false);
  });
})();
