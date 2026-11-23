"""Generate voiceover ElevenLabs per scene + tulis src/timings.ts untuk Remotion.

Voice: Liam (TX3LPaxmHKxFdv7VOQHJ), model eleven_multilingual_v2.
Jalankan dari folder video/: python tools/make_vo.py
Key dibaca dari video/.env (ELEVENLABS_API_KEY=sk_...).

Catatan: VO sengaja TIDAK menyebut kata "Wonosobo" (permintaan user).
"""
from __future__ import annotations

import json
import math
import ssl
import time
import urllib.error
import urllib.request
from pathlib import Path

import certifi
from mutagen.mp3 import MP3

ROOT = Path(__file__).resolve().parent.parent
VO_DIR = ROOT / "public" / "vo"
SRC_DIR = ROOT / "src"
ENV_FILE = ROOT / ".env"

VOICE_ID = "TX3LPaxmHKxFdv7VOQHJ"  # Liam
MODEL = "eleven_multilingual_v2"
OUTPUT_FORMAT = "mp3_44100_128"
FPS = 30
PAD_AFTER = 0.45  # jeda napas antar scene (detik)

SCENES = [
    ("intro", "Liburan ke Dieng? Jangan jalan kaki. Sewa motor di Rentalmotor sekarang juga."),
    ("hero", "Cari motor favoritmu cepat. Pilih merek, tipe, dan tanggal sewa dari website."),
    ("katalog", "Motor matic lengkap: Mio, Scoopy, Beat, Vario, PCX, sampai NMAX. Harga mulai tujuh puluh ribu per hari."),
    ("booking", "Booking online butuh hitungan menit. Pembayaran aman lewat DANA, GoPay, ShopeePay, OVO, atau QRIS."),
    ("ai", "Bingung cari lokasi atau harga? Tanya asisten AI Rentalmotor."),
    ("rute", "Ambil motornya di toko, langsung eksplor Dieng."),
    ("cta", "Rentalmotor. Sewa motor mudah dan terpercaya."),
]


def load_key() -> str:
    if ENV_FILE.exists():
        for line in ENV_FILE.read_text(encoding="utf-8").splitlines():
            if line.strip().startswith("ELEVENLABS_API_KEY"):
                return line.split("=", 1)[1].strip()
    raise SystemExit("Key tidak ditemukan. Buat video/.env berisi ELEVENLABS_API_KEY=sk_...")


def tts(key: str, text: str, voice_id: str, out: Path, retries: int = 5) -> None:
    url = (
        f"https://api.elevenlabs.io/v1/text-to-speech/{voice_id}"
        f"?output_format={OUTPUT_FORMAT}"
    )
    payload = json.dumps({"text": text, "model_id": MODEL}).encode("utf-8")
    ctx = ssl.create_default_context(cafile=certifi.where())

    last_err: Exception | None = None
    for attempt in range(1, retries + 1):
        req = urllib.request.Request(
            url,
            data=payload,
            method="POST",
            headers={
                "xi-api-key": key,
                "Content-Type": "application/json",
                "Accept": "audio/mpeg",
            },
        )
        try:
            with urllib.request.urlopen(req, context=ctx, timeout=180) as res:
                audio = res.read()
            if len(audio) < 2000:
                raise RuntimeError(f"audio terlalu kecil: {len(audio)} byte")
            out.write_bytes(audio)
            return
        except urllib.error.HTTPError as exc:
            body = exc.read().decode("utf-8", "replace")
            last_err = RuntimeError(f"HTTP {exc.code}: {body[:220]}")
            if exc.code == 429:
                print(f"    rate limit, tunggu 8 dtk (percobaan {attempt}/{retries})")
                time.sleep(8)
                continue
            if exc.code in (401, 402):
                break  # gagal permanen, tidak ada gunanya diulang
        except Exception as exc:  # noqa: BLE001
            last_err = exc
            time.sleep(4)
    raise RuntimeError(f"gagal generate {out.name}: {last_err}")


def main() -> None:
    key = load_key()
    VO_DIR.mkdir(parents=True, exist_ok=True)
    SRC_DIR.mkdir(parents=True, exist_ok=True)
    rows = []
    for i, (sid, text) in enumerate(SCENES, 1):
        out = VO_DIR / f"vo-{i}-{sid}.mp3"
        print(f"[{i}/{len(SCENES)}] {sid}: {text}")
        tts(key, text, VOICE_ID, out)
        dur = MP3(str(out)).info.length
        frames = math.ceil((dur + PAD_AFTER) * FPS)
        rows.append((sid, f"vo/vo-{i}-{sid}.mp3", dur, frames))
        print(f"    {dur:.2f}s -> {frames} frames")
        if i < len(SCENES):
            time.sleep(2)  # hindari 429 concurrent limit free plan

    total = sum(r[3] for r in rows)
    lines = [
        "// DIBUAT OTOMATIS oleh tools/make_vo.py -- jangan edit manual.",
        "export const TIMINGS = {",
        f"  fps: {FPS},",
        "  scenes: [",
    ]
    for sid, vo, dur, frames in rows:
        lines.append(
            f"    {{ id: {sid!r}, vo: {vo!r}, seconds: {dur:.3f}, frames: {frames} }},"
        )
    lines += [
        "  ],",
        f"  totalFrames: {total},",
        "} as const;",
        "",
    ]
    (SRC_DIR / "timings.ts").write_text("\n".join(lines), encoding="utf-8")
    print(f"TOTAL: {total} frames = {total / FPS:.2f} detik -> src/timings.ts")


if __name__ == "__main__":
    main()
