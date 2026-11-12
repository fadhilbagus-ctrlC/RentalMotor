# Video Promo MotorKu (Remotion)

Video promo 9:16 (1080x1920, 30fps, ±33 detik) untuk MotorKu Wonosobo,
dibuat dengan [Remotion](https://remotion.dev) + voiceover AI (`edge-tts`).

Output: `out/motorku-promo-9x16.mp4` (di-gitignore).

## Struktur

```
video/
  src/
    Root.tsx            # registrasi komposisi 1080x1920@30fps
    MotorKuPromo.tsx    # susunan 6 scene + audio
    timings.ts          # durasi scene (auto-generate dari tools/make_vo.py)
    scenes/             # IntroScene, CatalogScene, BookingScene, AiScene, RouteScene, CtaScene
  public/
    screens/            # screenshot asli app (360x640 @3x = 1080x1920)
    vo/                 # voiceover per scene (id-ID-GadisNeural)
    hero.jpg, logo-white.png
  tools/
    capture.mjs         # screenshot via headless Edge + CDP (mock geolocation Dieng)
    make_vo.py          # voiceover via edge-tts + durasi -> src/timings.ts
```

## Cara regenerasi

```bash
# 1. Screenshot (butuh Apache+MySQL Laragon jalan & session 'opencodepromo')
node tools/capture.mjs            # semua
node tools/capture.mjs detail     # satu scene saja (home|catalog|detail|checkout|ai)

# 2. Voiceover + timing
python tools/make_vo.py

# 3. Preview di studio
npx remotion studio

# 4. Render final
npx remotion render MotorKuPromo out/motorku-promo-9x16.mp4 --codec=h264
```

## Catatan

- Nama & foto profil di screenshot otomatis disamarkan jadi "Member".
- Capture checkout membuat booking pending sementara (PCX 160) lalu
  di-cancel otomatis; hapus row-nya lewat SQL setelah capture.
- Tempo voiceover `+20%` (lihat `tools/make_vo.py`) supaya total mendekati 30 detik.
