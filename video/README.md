# Video Promo MotorKu — 9:16 (Remotion)

Video promosi vertikal **1080×1920 (9:16), 30 fps, ±40 detik**. Seluruh UI aplikasi dibuat ulang sebagai komponen React (mock UI) — bukan screenshot aplikasi.

## Isi video (7 scene)

| # | Scene | Isi |
|---|-------|-----|
| 1 | intro | Logo + tagline pembuka |
| 2 | hero | Mock beranda: Navbar, HeroBanner, SearchPanel, 3 chip fitur |
| 3 | katalog | Grid 2×2 ProductCard (foto produk asli app) |
| 4 | booking | BookingForm + pill pembayaran (DANA/GoPay/ShopeePay/OVO/QRIS) |
| 5 | ai | ChatWidget asisten AI + chip jarak |
| 6 | rute | Animasi rute toko → Dieng (kurva quadratic) |
| 7 | cta | Logo + tombol + QR code repo GitHub |

## Voiceover

- ElevenLabs voice **Liam** (`TX3LPaxmHKxFdv7VOQHJ`), model `eleven_multilingual_v2`
- **VO sengaja tidak menyebut kata "Wonosobo"** (permintaan owner) — lokasi hanya lewat visual
- `src/timings.ts` dibuat otomatis dari durasi MP3 oleh `tools/make_vo.py` — jangan edit manual

## Perintah

```bash
# 1. Dependensi (mirror cepat)
npm install --legacy-peer-deps --registry=https://registry.npmmirror.com

# 2. Voiceover + timings (butuh video/.env berisi ELEVENLABS_API_KEY)
python tools/make_vo.py

# 3. QR code repo GitHub -> public/github-qr.png
node tools/make_qr.mjs

# 4. Studio (preview interaktif)
npm run studio

# 5. Render final
npx remotion render MotorKuPromo out/motorku-promo-9x16-v2.mp4 --codec=h264 --browser-executable="C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --log=error
```

## Catatan

- `video/.env` berisi API key ElevenLabs — **di-gitignore, jangan pernah di-commit**
- Folder `samples/` (contoh suara) & `out/` (hasil render) juga di-gitignore
- Konfigurasi app yang dipakai mock UI: jam buka 05.00–23.00 WIB, user login tampil sebagai "Member", harga mulai Rp 70.000/hari
