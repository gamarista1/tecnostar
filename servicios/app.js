'use strict';
const http = require('node:http');

const INICIO = Date.now();

// Solo lectura: este servicio nunca toca carrito, checkout ni pedidos (WooCommerce es la fuente única).
const servidor = http.createServer((req, res) => {
  if (req.method !== 'GET' && req.method !== 'HEAD') {
    res.writeHead(405, { Allow: 'GET, HEAD' }).end();
    return;
  }
  const { pathname } = new URL(req.url, 'http://localhost');
  if (pathname === '/salud') {
    res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
    res.end(JSON.stringify({ ok: true, servicio: 'tecnostar-servicios', node: process.version, activo_s: Math.round((Date.now() - INICIO) / 1000) }));
    return;
  }
  res.writeHead(404, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify({ ok: false, error: 'no_encontrado' }));
});

// Passenger inyecta el puerto/socket en PORT; en local cae a 3000.
servidor.listen(process.env.PORT || 3000);
