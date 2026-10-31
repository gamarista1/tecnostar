'use strict';
/**
 * URLs prefirmadas de Cloudflare R2 (API S3, AWS Signature Version 4 por query string, UNSIGNED-PAYLOAD).
 * Lógica pura con `node:crypto`, sin dependencias. Las claves solo llegan por process.env (R2_*), nunca por archivo,
 * y este módulo no escribe nada en logs.
 */
const crypto = require('node:crypto');

const CADUCIDAD = 3600;
const REGEX_CLAVE = /^videos\/[a-z0-9][a-z0-9._-]{0,120}\.mp4$/;

/** Clave válida: videos/<nombre>.mp4, minúsculas, sin subcarpetas ni "..". */
function claveValida(clave) {
  return typeof clave === 'string' && REGEX_CLAVE.test(clave) && !clave.includes('..');
}

/** RFC 3986 (como rawurlencode de PHP). */
function codificar(s) {
  return encodeURIComponent(s).replace(/[!'()*]/g, (c) => '%' + c.charCodeAt(0).toString(16).toUpperCase());
}

const hmac = (clave, dato, hex) => crypto.createHmac('sha256', clave).update(dato, 'utf8').digest(hex ? 'hex' : undefined);
const fechaAmz = (ms) => new Date(ms).toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '');

/**
 * Firma una URL. ruta sin codificar y empezando por "/". ahoraMs: milisegundos Unix.
 */
function prefirmar({ host, ruta, region, servicio, access, secret, ahoraMs, caducidad, metodo = 'GET' }) {
  const exp = Math.max(1, Math.min(604800, Math.floor(caducidad)));
  const fechaHora = fechaAmz(ahoraMs);
  const fecha = fechaHora.slice(0, 8);
  const alcance = `${fecha}/${region}/${servicio}/aws4_request`;
  const h = String(host).toLowerCase();
  const uri = ruta.split('/').map(codificar).join('/');
  const query = [
    ['X-Amz-Algorithm', 'AWS4-HMAC-SHA256'],
    ['X-Amz-Credential', `${access}/${alcance}`],
    ['X-Amz-Date', fechaHora],
    ['X-Amz-Expires', String(exp)],
    ['X-Amz-SignedHeaders', 'host'],
  ].sort((a, b) => (a[0] < b[0] ? -1 : a[0] > b[0] ? 1 : 0))
    .map(([k, v]) => codificar(k) + '=' + codificar(v)).join('&');
  const peticion = `${metodo}\n${uri}\n${query}\nhost:${h}\n\nhost\nUNSIGNED-PAYLOAD`;
  const aFirmar = `AWS4-HMAC-SHA256\n${fechaHora}\n${alcance}\n` + crypto.createHash('sha256').update(peticion, 'utf8').digest('hex');
  const kFecha = hmac('AWS4' + secret, fecha);
  const kRegion = hmac(kFecha, region);
  const kServicio = hmac(kRegion, servicio);
  const kFirma = hmac(kServicio, 'aws4_request');
  return `https://${h}${uri}?${query}&X-Amz-Signature=${hmac(kFirma, aFirmar, true)}`;
}

/** Configuración desde el entorno, o null si falta o no tiene forma válida. */
function configDesdeEntorno(env = process.env) {
  const c = {
    accountId: String(env.R2_ACCOUNT_ID || '').trim(),
    accessKeyId: String(env.R2_ACCESS_KEY_ID || '').trim(),
    secretAccessKey: String(env.R2_SECRET_ACCESS_KEY || '').trim(),
    bucket: String(env.R2_BUCKET || 'media').trim(),
  };
  if (!/^[a-f0-9]{32}$/i.test(c.accountId) || !c.accessKeyId || !c.secretAccessKey || !/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/.test(c.bucket)) return null;
  return c;
}

/** URL prefirmada de un objeto del bucket (estilo ruta /<bucket>/<clave>, región auto, servicio s3). '' si algo no vale. */
function urlR2(clave, cfg, ahoraMs = Date.now(), caducidad = CADUCIDAD) {
  if (!claveValida(clave) || !cfg) return '';
  return prefirmar({
    host: `${cfg.accountId.toLowerCase()}.r2.cloudflarestorage.com`,
    ruta: `/${cfg.bucket}/${clave}`,
    region: 'auto',
    servicio: 's3',
    access: cfg.accessKeyId,
    secret: cfg.secretAccessKey,
    ahoraMs,
    caducidad,
  });
}

module.exports = { CADUCIDAD, claveValida, prefirmar, configDesdeEntorno, urlR2 };
