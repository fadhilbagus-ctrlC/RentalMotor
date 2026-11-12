/**
 * Capture screenshot full-screen UI MotorKu untuk video Remotion (v2).
 *
 * Viewport 360x640, deviceScaleFactor 3 -> PNG 1080x1920 (pas 9:16).
 * Data pribadi disamarkan: nama navbar -> "Member", foto avatar -> huruf "M".
 *
 * Alur:
 *  1. home.png       : katalog bagian atas (hero)
 *  2. catalog.png    : grid motor
 *  3. detail.png     : detail PCX 160 + form booking terisi (harga total)
 *  4. checkout.png   : metode pembayaran (booking pending baru dibuat via form app)
 *  5. ai.png         : widget AI + chip jarak (mock geolocation Dieng ~17 km)
 *
 * Booking tes dibuat lalu DI-CANCEL supaya status produk kembali available.
 * (Row booking hasil capture dihapus terpisah lewat SQL.)
 *
 * Jalankan dari folder video/: node tools/capture.mjs
 */
import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const VIDEO_ROOT = path.resolve(__dirname, '..');
const OUT_DIR = path.join(VIDEO_ROOT, 'public', 'screens');

const EDGE = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const PORT = 9224;
const BASE = 'http://localhost/pkk/assets/user/index.php';
const SESSION = 'opencodepromo';
const PROFILE = path.join(tmpdir(), 'opencode', 'edgeprof_capture_' + Date.now());
// Koordinat Dieng -> chip jarak sekitar 17 km dari toko
const GEO = { latitude: -7.20698, longitude: 109.91198, accuracy: 20 };
const DETAIL_PRODUCT_ID = 8; // Honda PCX 160 (available, sesuai VO "sampai PCX")

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Jalankan cuma scene tertentu: node tools/capture.mjs detail (tanpa arg = semua)
const only = process.argv[2] || null;
const want = (name) => !only || only === name;

// Tanggal lokal (WIB) tanpa toISOString biar tidak geser zona
const localDate = (offsetDays) => {
  const d = new Date();
  d.setDate(d.getDate() + offsetDays);
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${d.getFullYear()}-${m}-${day}`;
};

// Masking data pribadi sebelum tiap screenshot
const MASK_JS = `(function(){
  document.querySelectorAll('.user-details .name').forEach(function(e){e.textContent='Member';});
  document.querySelectorAll('.navbar-user .avatar').forEach(function(a){
    a.textContent='M';
    a.querySelectorAll('img').forEach(function(i){i.remove();});
  });
  return true;
})()`;

async function waitCDP(timeout = 30000) {
  const end = Date.now() + timeout;
  while (Date.now() < end) {
    try {
      const res = await fetch(`http://127.0.0.1:${PORT}/json/version`);
      if (res.ok) return;
    } catch {}
    await sleep(400);
  }
  throw new Error('Edge CDP tidak merespons di port ' + PORT);
}

async function firstPageTarget() {
  const res = await fetch(`http://127.0.0.1:${PORT}/json/list`);
  const targets = await res.json();
  const page = targets.find((t) => t.type === 'page' && t.webSocketDebuggerUrl);
  if (!page) throw new Error('Target page tidak ditemukan');
  return page;
}

async function main() {
  mkdirSync(OUT_DIR, { recursive: true });
  console.log('Launch Edge headless (port ' + PORT + ')');
  const edge = spawn(EDGE, [
    '--headless=new',
    '--disable-gpu',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${PROFILE}`,
    '--no-first-run',
    '--no-default-browser-check',
    '--disable-extensions',
    'about:blank',
  ], { stdio: 'ignore' });

  let bookingId = null;

  try {
    await waitCDP();
    const page = await firstPageTarget();
    const ws = new WebSocket(page.webSocketDebuggerUrl);
    await new Promise((res, rej) => { ws.onopen = res; ws.onerror = () => rej(new Error('WS gagal')); });

    let seq = 0;
    const pending = new Map();
    ws.onmessage = (ev) => {
      const msg = JSON.parse(ev.data);
      if (msg.id && pending.has(msg.id)) {
        const { resolve, reject } = pending.get(msg.id);
        pending.delete(msg.id);
        if (msg.error) reject(new Error(`${msg.error.message} (code ${msg.error.code})`));
        else resolve(msg.result);
      }
    };
    const send = (method, params = {}) => new Promise((resolve, reject) => {
      const id = ++seq;
      pending.set(id, { resolve, reject });
      ws.send(JSON.stringify({ id, method, params }));
    });

    await send('Page.enable');
    await send('Runtime.enable');
    await send('Network.enable');
    await send('Emulation.setDeviceMetricsOverride', {
      width: 360, height: 640, deviceScaleFactor: 3, mobile: true,
    });
    await send('Network.setCookie', {
      name: 'PHPSESSID', value: SESSION, domain: 'localhost', path: '/',
    });

    const evalJS = async (expression) => {
      const r = await send('Runtime.evaluate', { expression, returnByValue: true, awaitPromise: true });
      if (r.exceptionDetails) {
        const d = r.exceptionDetails;
        throw new Error('Eval error: ' + (d.exception?.description || d.text || JSON.stringify(d)));
      }
      return r.result.value;
    };

    const navigate = async (url, settle = 2500) => {
      await send('Page.navigate', { url });
      const end = Date.now() + 20000;
      while (Date.now() < end) {
        const st = await evalJS('document.readyState');
        if (st === 'complete') break;
        await sleep(250);
      }
      await sleep(settle);
    };

    const waitFor = async (expr, timeout = 15000, interval = 400) => {
      const end = Date.now() + timeout;
      while (Date.now() < end) {
        if (await evalJS(expr)) return true;
        await sleep(interval);
      }
      return false;
    };

    const shot = async (file) => {
      await evalJS(MASK_JS);
      const data = await send('Page.captureScreenshot', { format: 'png', captureBeyondViewport: false });
      const buf = Buffer.from(data.data, 'base64');
      writeFileSync(path.join(OUT_DIR, file), buf);
      console.log('OK ' + file + ' (' + Math.round(buf.length / 1024) + ' KB)');
    };

    // Isi tanggal form booking (display saja, belum submit)
    const fillDates = async () => {
      const pickup = localDate(1);
      const ret = localDate(3);
      const total = await evalJS(
        `(function(){
          var p=document.getElementById('pickup_date'), r=document.getElementById('return_date');
          if(!p||!r) return 'form tidak ditemukan';
          p.value='${pickup}'; r.value='${ret}';
          p.dispatchEvent(new Event('change',{bubbles:true}));
          r.dispatchEvent(new Event('change',{bubbles:true}));
          return document.getElementById('display_total').textContent;
        })()`
      );
      return { pickup, ret, total };
    };

    const scrollBookingForm = `(function(){var el=document.querySelector('.booking-form')||document.querySelector('.price-section');if(el){el.scrollIntoView({block:'start'});window.scrollBy(0,-8);}return true;})()`;

    // ---- 1. HOME (hero + sambutan) ----
    if (want('home')) {
      await navigate(`${BASE}?page=katalog`);
      await shot('home.png');
    }

    // ---- 2. KATALOG (grid motor) ----
    if (want('catalog')) {
      if (only) await navigate(`${BASE}?page=katalog`);
      await evalJS(
        `(function(){var el=document.querySelector('#motor-tersedia')||document.querySelector('.product-grid');if(el){el.scrollIntoView({block:'start'});window.scrollBy(0,-8);}return true;})()`
      );
      await sleep(1200);
      await shot('catalog.png');
    }

    // ---- 3. DETAIL PCX (form booking terisi) ----
    if (want('detail')) {
      await navigate(`${BASE}?page=detail&id=${DETAIL_PRODUCT_ID}`);
      const { pickup, ret, total } = await fillDates();
      console.log('Detail PCX: pickup ' + pickup + ' -> ' + ret + ', total ' + total);
      await evalJS(scrollBookingForm);
      await sleep(900);
      await shot('detail.png');
    }

    // ---- 4. SUBMIT booking -> checkout -> cancel ----
    if (want('checkout')) {
      await navigate(`${BASE}?page=detail&id=${DETAIL_PRODUCT_ID}`);
      const { pickup, ret, total } = await fillDates();
      console.log('Detail PCX: pickup ' + pickup + ' -> ' + ret + ', total ' + total);

      await evalJS(`document.getElementById('bookingForm').submit();true`);
    const navOk = await waitFor(
      `location.href.indexOf('checkout&id=')>-1 && document.readyState==='complete'`,
      15000
    );
    if (!navOk) {
      const flash = await evalJS(
        `(function(){var a=document.querySelector('.alert');return a?a.textContent.trim():'(tanpa pesan)';})()`
      );
      throw new Error('Booking gagal dibuat. Pesan: ' + flash);
    }
    const href = await evalJS('location.href');
    const m = href.match(/checkout&id=(\d+)/);
    bookingId = m ? Number(m[1]) : null;
    console.log('Booking dibuat: #' + bookingId);

    const headingOk = await waitFor(
      `(function(){return [].some.call(document.querySelectorAll('h2'),function(h){return h.textContent.indexOf('Metode Pembayaran')>-1;});})()`,
      8000
    );
    if (!headingOk) console.log('PERINGATAN: heading Metode Pembayaran tidak muncul');
    await evalJS(
      `(function(){var h=[].slice.call(document.querySelectorAll('h2')).find(function(x){return x.textContent.indexOf('Metode Pembayaran')>-1;});if(h){h.scrollIntoView({block:'start'});window.scrollBy(0,-14);}return true;})()`
    );
    await sleep(1000);
    await shot('checkout.png');

    // ---- 5. CANCEL booking (kembalikan kondisi app) ----
    const cancelled = await evalJS(
      `(function(){
        var forms=document.querySelectorAll('form');
        for (var i=0;i<forms.length;i++){
          var a=forms[i].querySelector('input[name="action"]');
          if (a && a.value==='cancel'){ forms[i].submit(); return true; }
        }
        return false;
      })()`
    );
    if (!cancelled) throw new Error('Form cancel tidak ditemukan di checkout');
    await waitFor(`location.href.indexOf('page=riwayat')>-1 && document.readyState==='complete'`, 15000);
      console.log('Booking #' + bookingId + ' dicancel via app (status produk akan dipulihkan app)');
    }

    // ---- 6. AI CHAT (lokasi & jarak) ----
    if (want('ai')) {
      await navigate(`${BASE}?page=katalog`, 2000);
    await send('Browser.grantPermissions', { permissions: ['geolocation'], origin: 'http://localhost' });
    await send('Emulation.setGeolocationOverride', GEO);
    await evalJS(`document.getElementById('aiChatToggle').click();true`);
    await sleep(800);
    await evalJS(`document.getElementById('aiChatLocation').click();true`);
    const chipOk = await waitFor(
      `(function(){var t=document.getElementById('aiChatLocationText');return !!t && t.textContent.indexOf('km dari toko')>-1;})()`,
      15000
    );
    if (!chipOk) throw new Error('Chip jarak tidak muncul (geolocation gagal?)');
    await sleep(700);

    const QUESTION = 'Di mana lokasi MotorKu dan berapa jarak dari lokasi saya?';
    const ERR_WORDS = ['gagal', 'tidak tersedia', 'coba lagi', 'terhubung', 'kedaluwarsa'];

    const askOnce = async () => {
      const before = await evalJS(`document.querySelectorAll('.ai-msg-bot').length`);
      await evalJS(
        `(function(){var i=document.getElementById('aiChatInput');i.value=${JSON.stringify(QUESTION)};` +
        `i.dispatchEvent(new Event('input',{bubbles:true}));document.getElementById('aiChatSend').click();return true;})()`
      );
      const ok = await waitFor(
        `document.querySelectorAll('.ai-msg-bot').length>${before} && !document.querySelector('.ai-msg-typing')`,
        45000
      );
      if (!ok) throw new Error('Balasan AI tidak muncul dalam 45 detik');
      const msgs = await evalJS(
        `(function(){return [].slice.call(document.querySelectorAll('.ai-msg-bot')).map(function(e){return e.textContent;});})()`
      );
      return msgs[msgs.length - 1];
    };

    let reply = await askOnce();
    for (let t = 1; t <= 2 && ERR_WORDS.some((w) => reply.toLowerCase().includes(w)); t++) {
      console.log('Jawaban AI error, percobaan ulang ' + t + '/2 ...');
      await sleep(2500);
      reply = await askOnce();
    }
    if (ERR_WORDS.some((w) => reply.toLowerCase().includes(w))) {
      console.log('PERINGATAN: jawaban AI masih error -> ' + reply.slice(0, 160));
    } else {
      console.log('Jawaban AI: ' + reply.slice(0, 160));
    }
      await sleep(900);
      await shot('ai.png');
    }

    console.log('SELESAI. Screenshot di ' + OUT_DIR + ' | bookingId=' + bookingId);
    ws.close();
  } finally {
    try { spawn('taskkill', ['/pid', String(edge.pid), '/T', '/F'], { stdio: 'ignore' }); } catch {}
    await sleep(600);
    try { rmSync(PROFILE, { recursive: true, force: true }); } catch {}
    if (bookingId) {
      console.log('BOOKING_ID=' + bookingId);
    }
  }
}

main().catch((e) => {
  console.error('GAGAL: ' + e.message);
  process.exit(1);
});
