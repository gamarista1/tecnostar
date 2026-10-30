// Cargador de islas: descarga el código de cada isla solo cuando su zona se acerca a la pantalla.
const islas = {
  'banner-motion': () => import('./banner-motion.js'),
};

function cargar(raiz) {
  const cargarIsla = islas[raiz.dataset.isla];
  if (cargarIsla) cargarIsla().then((m) => m.iniciar(raiz));
}

document.querySelectorAll('[data-isla]').forEach((raiz) => {
  if (!('IntersectionObserver' in window)) return cargar(raiz);
  const observador = new IntersectionObserver(
    (entradas) => {
      if (entradas.some((e) => e.isIntersecting)) {
        observador.disconnect();
        cargar(raiz);
      }
    },
    { rootMargin: '300px' }
  );
  observador.observe(raiz);
});
