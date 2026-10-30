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

	function mostrar(cual, interactivo) {
		var registro = cual === 'registro';
		tarjeta.classList.toggle('is-flipped', registro);
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

	window.addEventListener('hashchange', function () {
		mostrar(location.hash === '#registro' ? 'registro' : 'entrar', false);
	});

	// Al enviar, el botón muestra su estado de carga (sin bloquear el envío).
	raiz.addEventListener('submit', function (e) {
		var boton = e.target.querySelector('button[type="submit"]');
		if (boton) setTimeout(function () { boton.classList.add('is-loading'); }, 0);
	});
})();
