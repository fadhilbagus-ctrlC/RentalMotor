"""Generate voiceover per scene pakai edge-tts + tulis src/timings.ts untuk Remotion.

Voice: id-ID-GadisNeural (AI perempuan Indonesia).
Jalankan dari folder video/: python tools/make_vo.py
"""
import asyncio
import math
from pathlib import Path

import edge_tts
import mutagen

ROOT = Path(__file__).resolve().parent.parent      # folder video/
VO_DIR = ROOT / "public" / "vo"
SRC_DIR = ROOT / "src"

VOICE = "id-ID-GadisNeural"
RATE = "+20%"    # tempo lebih rancak biar total mendekati 30 detik
FPS = 30
PAD_AFTER = 0.15 # ekor silence MP3 tiap file sudah berfungsi sebagai jeda natural

SCENES = [
    ("intro",   "Liburan ke Dieng? Jangan jalan kaki."),
    ("katalog", "Sewa motor di MotorKu. Pilih Beat, Vario, NMAX, sampai PCX."),
    ("booking", "Booking cepat. Bayar pakai DANA, GoPay, ShopeePay, OVO, atau QRIS."),
    ("ai",      "Lokasi dan jarak? Tanya asisten AI MotorKu."),
    ("rute",    "Ambil motor di Wonosobo, langsung eksplor Dieng."),
    ("cta",     "MotorKu Wonosobo. Sewa motor mudah dan terpercaya."),
]


async def generate(text: str, out: Path, attempts: int = 3) -> float:
    last_err: Exception | None = None
    for i in range(1, attempts + 1):
        try:
            comm = edge_tts.Communicate(text, VOICE, rate=RATE)
            await comm.save(str(out))
            dur = mutagen.File(str(out)).info.length
            if dur > 0.5:
                return dur
            raise RuntimeError(f"durasi tidak wajar: {dur}")
        except Exception as exc:
            last_err = exc
            print(f"    percobaan {i}/{attempts} gagal: {exc}")
            await asyncio.sleep(2)
    raise RuntimeError(f"edge-tts gagal untuk {out.name}: {last_err}")


async def main() -> None:
    VO_DIR.mkdir(parents=True, exist_ok=True)
    rows = []
    for i, (sid, text) in enumerate(SCENES, 1):
        out = VO_DIR / f"vo-{i}-{sid}.mp3"
        print(f"[{i}/{len(SCENES)}] {sid}: {text}")
        dur = await generate(text, out)
        frames = math.ceil((dur + PAD_AFTER) * FPS)
        rows.append((sid, f"vo/vo-{i}-{sid}.mp3", dur, frames))
        print(f"    {dur:.2f}s -> {frames} frames")

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
    asyncio.run(main())
