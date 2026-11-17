"""Generate sample suara ElevenLabs untuk memilih voice video promo.

Jalankan dari folder video/:
    python tools/make_samples.py

Key dibaca dari video/.env (ELEVENLABS_API_KEY=sk_...).
Model: eleven_multilingual_v2. Request berurutan (free plan: 1 concurrent).
"""
from __future__ import annotations

import json
import ssl
import time
import urllib.error
import urllib.request
from pathlib import Path

import certifi
from mutagen.mp3 import MP3

ROOT = Path(__file__).resolve().parent.parent
OUT_DIR = ROOT / "samples"
ENV_FILE = ROOT / ".env"

MODEL = "eleven_multilingual_v2"
OUTPUT_FORMAT = "mp3_44100_128"
SAMPLE_TEXT = "Liburan ke Dieng? Jangan jalan kaki. Sewa motor di Motorent."

VOICES = [
    ("adam", "Adam", "pNInz6obpgDQGcFmaJgB"),
    ("antoni", "Antoni", "ErXwobaYiN019PkySvjV"),
    ("arnold", "Arnold", "VR6AewLTigWG4xSOukaG"),
    ("callum", "Callum", "N2lVS1w4EtoT3dr4eOWO"),
    ("daniel", "Daniel", "onwK4e9ZLuTAKqWW03F9"),
    ("george", "George", "JBFqnCBsd6RMkjVDRZzb"),
    ("liam", "Liam", "TX3LPaxmHKxFdv7VOQHJ"),
]


def load_key() -> str:
    if ENV_FILE.exists():
        for line in ENV_FILE.read_text(encoding="utf-8").splitlines():
            if line.strip().startswith("ELEVENLABS_API_KEY"):
                return line.split("=", 1)[1].strip()
    raise SystemExit("Key tidak ditemukan. Buat video/.env berisi ELEVENLABS_API_KEY=sk_...")


def tts(key: str, voice_id: str, out: Path, retries: int = 4) -> None:
    url = (
        f"https://api.elevenlabs.io/v1/text-to-speech/{voice_id}"
        f"?output_format={OUTPUT_FORMAT}"
    )
    payload = json.dumps({"text": SAMPLE_TEXT, "model_id": MODEL}).encode("utf-8")
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
            with urllib.request.urlopen(req, context=ctx, timeout=120) as res:
                audio = res.read()
            if len(audio) < 1000:
                raise RuntimeError(f"audio terlalu kecil: {len(audio)} byte")
            out.write_bytes(audio)
            return
        except urllib.error.HTTPError as exc:
            body = exc.read().decode("utf-8", "replace")
            last_err = RuntimeError(f"HTTP {exc.code}: {body[:200]}")
            if exc.code == 429:
                print(f"    rate limit, tunggu 6 dtk (percobaan {attempt}/{retries})")
                time.sleep(6)
                continue
            if exc.code in (401, 402):
                break  # tidak ada gunanya diulang
        except Exception as exc:  # noqa: BLE001
            last_err = exc
            time.sleep(3)
    raise RuntimeError(f"gagal untuk {out.name}: {last_err}")


def main() -> None:
    key = load_key()
    OUT_DIR.mkdir(parents=True, exist_ok=True)
    print(f"Kalimat: {SAMPLE_TEXT!r}\n")
    for i, (slug, name, voice_id) in enumerate(VOICES, 1):
        out = OUT_DIR / f"voice-{i:02d}-{slug}.mp3"
        print(f"[{i}/{len(VOICES)}] {name} ({voice_id}) ...")
        tts(key, voice_id, out)
        dur = MP3(str(out)).info.length
        print(f"    OK -> {out.name} ({dur:.1f} dtk)")
        if i < len(VOICES):
            time.sleep(2)  # hindari 429 concurrent limit free plan
    print(f"\nSelesai. {len(VOICES)} sample di {OUT_DIR}")


if __name__ == "__main__":
    main()
