'use strict';
const http = require('node:http');
const video = require('./video-firma');

const INICIO = Date.now();

// Límite de /video/firmar en memoria: 60 por hora por cliente y un tope total por hora.
const LIMITE_CLIENTE = 60;
const LIMITE_GLOBAL = 5000;
const HORA_MS = 3600 * 1000;
const contadores = new Map();
let total = { n: 0, fin: 0 };

/**
 * Cliente para el límite: IP que añadió el proxy más cercano (último valor de X-Forwarded-For, que el visitante no
 * controla) o la del socket, más el identificador opaco que manda WordPress (X-Tecnostar-Cliente: HMAC de la IP del
 * visitante), porque las llamadas de WordPress llegan todas desde la IP del servidor.
 */
function clienteDe(req) {
  const xff = String(req.headers['x-forwarded-for'] || '').split(',').map((s) => s.trim()).filter(Boolean);
  const ip = xff.length ? xff[xff.length - 1] : (req.socket && req.socket.remoteAddress) || '';
  const wp = String(req.headers['x-tecnostar-cliente'] || '');
  return ip + '|' + (/^[a-f0-9]{16,64}$/.test(wp) ? wp : '');
}

function consumir(clave, ahora) {
  if (total.fin <= ahora) total = { n: 0, fin: ahora + HORA_MS };
  if (total.n >= LIMITE_GLOBAL) return false;
  if (contadores.size > 20000) {
    for (const [k, v] of contadores) if (v.fin <= ahora) contadores.delete(k);
  }
  let c = contadores.get(clave);
  if (!c || c.fin <= ahora) c = { n: 0, fin: ahora + HORA_MS };
  if (c.n >= LIMITE_CLIENTE) return false;
  c.n++;
  total.n++;
  contadores.set(clave, c);
  return true;
}

function json(res, estado, cuerpo, extra) {
  res.writeHead(estado, Object.assign({ 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store', 'X-Robots-Tag': 'noindex' }, extra || {}));
  res.end(JSON.stringify(cuerpo));
}

// Solo lectura: este servicio nunca toca carrito, checkout ni pedidos (WooCommerce es la fuente única).
const servidor = http.createServer((req, res) => {
  if (req.method !== 'GET' && req.method !== 'HEAD') {
    res.writeHead(405, { Allow: 'GET, HEAD' }).end();
    return;
  }
  const url = new URL(req.url, 'http://localhost');
  const { pathname } = url;
  if (pathname === '/salud') {
    res.writeHead(200, { 'Content-Type': 'application/json; charset=utf-8', 'Cache-Control': 'no-store' });
    res.end(JSON.stringify({ ok: true, servicio: 'tecnostar-servicios', node: process.version, activo_s: Math.round((Date.now() - INICIO) / 1000) }));
    return;
  }
  if (pathname === '/video/firmar') {
    // URL prefirmada de 1 hora para un video del bucket privado de R2. No se registra la clave ni la URL.
    if (req.method !== 'GET') return json(res, 405, { error: 'metodo' }, { Allow: 'GET' });
    const claves = url.searchParams.getAll('clave');
    const clave = claves.length === 1 ? claves[0] : '';
    if (!video.claveValida(clave)) return json(res, 400, { error: 'clave' });
    const ahora = Date.now();
    if (!consumir(clienteDe(req), ahora)) return json(res, 429, { error: 'limite' }, { 'Retry-After': '3600' });
    const cfg = video.configDesdeEntorno();
    if (!cfg) return json(res, 503, { error: 'sin-configuracion' });
    const firmada = video.urlR2(clave, cfg, ahora, video.CADUCIDAD);
    if (!firmada) return json(res, 500, { error: 'firma' });
    return json(res, 200, { url: firmada, expira: Math.floor(ahora / 1000) + video.CADUCIDAD });
  }
  res.writeHead(404, { 'Content-Type': 'application/json; charset=utf-8' });
  res.end(JSON.stringify({ ok: false, error: 'no_encontrado' }));
});

// Passenger inyecta el puerto/socket en PORT; en local cae a 3000.
servidor.listen(process.env.PORT || 3000);
