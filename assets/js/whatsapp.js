/* Menú radial de WhatsApp. Sin dependencias. Contrato:
   [data-wa-abrir="<contexto>"] abre el menú con ese contexto; [data-wa-sede="<canal>"] salta el menú;
   evento window 'ts:wa' con detail.canal al elegir un canal. */
(function () {
  var raiz = document.querySelector('[data-wa]');
  if (!raiz) return;
  var fab = raiz.querySelector('[data-wa-fab]');
  var lista = raiz.querySelector('.ts-wa__lista');
  var KEY = 'ts_wa_ultima';
  var contexto = '';
  var abierto = false;

  function leer() { try { return localStorage.getItem(KEY) || ''; } catch (e) { return ''; } }
  function guardar(id) { try { localStorage.setItem(KEY, id); } catch (e) {} }
  function items() { return [].slice.call(raiz.querySelectorAll('[data-wa-canal]')); }

  // Mensaje: el contexto libre o, si falta o es un código (tipo:slug), el título de la página.
  function url(a) {
    var frases = { 'busqueda': 'busco un producto que no encuentro en la tienda', 'te-lo-conseguimos': 'busco un producto que no encuentro en la tienda' };
    var tema = frases[contexto] || (contexto && !/^[a-z_]+:[\w-]+$/i.test(contexto) ? contexto : document.title);
    var texto = 'Hola, quiero información: ' + tema + ' ' + location.href;
    return 'https://wa.me/' + a.getAttribute('data-wa-num') + '?text=' + encodeURIComponent(texto);
  }
  function elegido(a) {
    guardar(a.getAttribute('data-wa-canal'));
    try { window.dispatchEvent(new CustomEvent('ts:wa', { detail: { canal: a.getAttribute('data-wa-canal') } })); } catch (e) {}
  }

  // Ordena: la última sede usada va primero (ángulo 0). --k reparte el arco de 0 a 1.
  function ordenar() {
    var id = leer(), its = items();
    var ult = its.filter(function (a) { return a.getAttribute('data-wa-canal') === id; })[0];
    if (ult && its[0] !== ult) lista.insertBefore(ult.parentNode, lista.firstChild);
    items().forEach(function (a, i, todos) { a.parentNode.style.setProperty('--k', todos.length > 1 ? i / (todos.length - 1) : 0); });
  }

  function abrir(ctx, enfocar) {
    if (typeof ctx === 'string') contexto = ctx;
    abierto = true;
    raiz.classList.add('is-open');
    fab.setAttribute('aria-expanded', 'true');
    items().forEach(function (a) { a.tabIndex = 0; });
    if (enfocar !== false) items()[0].focus();
  }
  function cerrar(devolver) {
    if (!abierto) return;
    abierto = false;
    raiz.classList.remove('is-open');
    fab.setAttribute('aria-expanded', 'false');
    items().forEach(function (a) { a.tabIndex = -1; });
    if (devolver) fab.focus();
  }

  fab.setAttribute('role', 'button');
  fab.setAttribute('aria-haspopup', 'true');
  fab.setAttribute('aria-expanded', 'false');
  fab.removeAttribute('target');
  ordenar();

  fab.addEventListener('click', function (e) {
    if (e.ctrlKey || e.metaKey || e.shiftKey) return;
    e.preventDefault();
    if (abierto) cerrar(false); else { contexto = ''; abrir('', e.detail === 0); }
  });
  fab.addEventListener('keydown', function (e) {
    if (e.key === ' ') { e.preventDefault(); fab.click(); }
  });

  raiz.addEventListener('click', function (e) {
    var a = e.target.closest('[data-wa-canal]');
    if (!a) return;
    a.href = url(a);
    elegido(a);
    cerrar(false);
  });

  raiz.addEventListener('keydown', function (e) {
    if (!abierto) return;
    if (e.key === 'Escape') { e.preventDefault(); cerrar(true); return; }
    var its = items(), i = its.indexOf(document.activeElement);
    var sig = e.key === 'ArrowUp' || e.key === 'ArrowLeft' || e.key === 'ArrowDown' || e.key === 'ArrowRight';
    if (!sig) return;
    e.preventDefault();
    var atras = e.key === 'ArrowUp' || e.key === 'ArrowLeft';
    if (i < 0) its[atras ? its.length - 1 : 0].focus();
    else its[(i + (atras ? -1 : 1) + its.length) % its.length].focus();
  });

  raiz.addEventListener('focusout', function (e) {
    if (abierto && e.relatedTarget && !raiz.contains(e.relatedTarget)) cerrar(false);
  });
  document.addEventListener('click', function (e) {
    var sede = e.target.closest && e.target.closest('[data-wa-sede]');
    if (sede) {
      var a = raiz.querySelector('[data-wa-canal="' + sede.getAttribute('data-wa-sede') + '"]');
      if (a) {
        e.preventDefault();
        contexto = sede.getAttribute('data-wa-abrir') || '';
        elegido(a);
        window.open(url(a), '_blank', 'noopener');
        return;
      }
    }
    var disp = e.target.closest && e.target.closest('[data-wa-abrir]');
    if (disp && !raiz.contains(disp)) {
      e.preventDefault();
      abrir(disp.getAttribute('data-wa-abrir') || '', true);
      return;
    }
    if (abierto && !raiz.contains(e.target)) cerrar(false);
  });
})();
