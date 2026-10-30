/* Acceso de clientes: volteo accesible entre "Acceder" y "Crear cuenta". */
(function () {
	'use strict';
	var raiz = document.querySelector('[data-acceso]');
	if (!raiz) return;
	var tarjeta = raiz.querySelector('[data-card]');
	var caras = { entrar: raiz.querySelector('[data-face="entrar"]'), registro: raiz.querySelector('[data-face="registro"]') };
	var sinMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)');

	// Sin registro habilitado no hay nada que voltear.
	if (!caras.registro) return;

	// La tarjeta toma la altura de la cara visible (la otra puede ser más alta o más baja).
	function medir(sinAnimar) {
		var activa = tarjeta.classList.contains('is-flipped') ? caras.registro : caras.entrar;
		if (sinAnimar) tarjeta.style.transition = 'none';
		tarjeta.style.height = activa.offsetHeight + 'px';
		if (sinAnimar) {
			void tarjeta.offsetHeight;
			tarjeta.style.transition = '';
		}
	}

	function mostrar(cual, interactivo) {
		var registro = cual === 'registro';
		tarjeta.classList.toggle('is-flipped', registro);
		medir(!interactivo);
		// La cara oculta no recibe foco ni la leen los lectores de pantalla.
		caras.entrar.toggleAttribute('inert', registro);
		caras.entrar.setAttribute('aria-hidden', String(registro));
		caras.registro.toggleAttribute('inert', !registro);
		caras.registro.setAttribute('aria-hidden', String(!registro));
		if (!interactivo) return;
		history.replaceState(null, '', registro ? '#registro' : location.pathname + location.search);
		// El foco pasa al primer campo cuando la cara ya va por la mitad del giro.
		setTimeout(function () {
			var campo = (registro ? caras.registro : caras.entrar).querySelector('input');
			if (campo) campo.focus({ preventScroll: true });
		}, sinMovimiento.matches ? 0 : 280);
	}

	mostrar(tarjeta.classList.contains('is-flipped') || location.hash === '#registro' ? 'registro' : 'entrar', false);

	raiz.addEventListener('click', function (e) {
		var boton = e.target.closest('[data-flip]');
		if (!boton) return;
		e.preventDefault();
		mostrar(boton.getAttribute('data-flip'), true);
	});

	// Si el contenido cambia (avisos de error, tipografía que termina de cargar), la altura se reajusta sin animar.
	if ('ResizeObserver' in window) {
		var observador = new ResizeObserver(function () { medir(true); });
		observador.observe(caras.entrar);
		observador.observe(caras.registro);
	}

	window.addEventListener('hashchange', function () {
		mostrar(location.hash === '#registro' ? 'registro' : 'entrar', false);
	});

	// Al enviar, el botón muestra su estado de carga (sin bloquear el envío).
	raiz.addEventListener('submit', function (e) {
		var boton = e.target.querySelector('button[type="submit"]');
		if (boton) setTimeout(function () { boton.classList.add('is-loading'); }, 0);
	});
})();
