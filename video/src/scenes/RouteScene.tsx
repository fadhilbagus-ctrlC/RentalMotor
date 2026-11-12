import React from 'react';
import { AbsoluteFill, Easing, interpolate, useCurrentFrame, useVideoConfig } from 'remotion';
import { fadeInOut, FONT, Headline, TopChip, spr } from '../shared';

// Titik kurva rute: toko Wonosobo (kiri bawah) -> Dieng (kanan atas)
const P0 = { x: 215, y: 1300 };
const C = { x: 545, y: 930 };
const P2 = { x: 872, y: 445 };

const qBez = (a: number, c: number, b: number, t: number): number => {
  const u = 1 - t;
  return u * u * a + 2 * u * t * c + t * t * b;
};

// Panjang kurva dihitung nyata (sampling 120 titik) supaya dash akurat
const PATH_LEN = (() => {
  let len = 0;
  let px = P0.x;
  let py = P0.y;
  for (let i = 1; i <= 120; i++) {
    const t = i / 120;
    const x = qBez(P0.x, C.x, P2.x, t);
    const y = qBez(P0.y, C.y, P2.y, t);
    len += Math.hypot(x - px, y - py);
    px = x;
    py = y;
  }
  return len;
})();

const STARS: Array<[number, number]> = Array.from({ length: 26 }, (_, i) => [
  ((i * 149) % 1000) + 40,
  ((i * 283) % 620) + 70,
]);

const PinLabel: React.FC<{ x: number; y: number; text: string; opacity: number }> = ({
  x,
  y,
  text,
  opacity,
}) => (
  <div
    style={{
      position: 'absolute',
      left: x,
      top: y,
      transform: 'translate(-50%, 0)',
      opacity,
      background: 'rgba(11,21,48,0.75)',
      border: '2px solid rgba(116,148,236,0.6)',
      color: '#e8eeff',
      fontSize: 30,
      fontWeight: 700,
      padding: '10px 24px',
      borderRadius: 12,
      whiteSpace: 'nowrap',
    }}
  >
    {text}
  </div>
);

export const RouteScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const opacity = fadeInOut(frame, frames, 10);

  const drawT = interpolate(frame, [12, Math.round(frames * 0.62)], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
    easing: Easing.inOut(Easing.quad),
  });
  const dx = qBez(P0.x, C.x, P2.x, drawT);
  const dy = qBez(P0.y, C.y, P2.y, drawT);

  const pinStart = spr(frame, 2, fps, 24);
  const pinEnd = spr(frame, Math.round(frames * 0.62) + 2, fps, 24);
  const kmPop = spr(frame, Math.round(frames * 0.46), fps, 24);

  return (
    <AbsoluteFill style={{ opacity, fontFamily: FONT }}>
      <AbsoluteFill
        style={{ background: 'linear-gradient(180deg, #0a1333 0%, #132a5e 52%, #0b1530 100%)' }}
      />
      {STARS.map(([x, y], i) => (
        <div
          key={i}
          style={{
            position: 'absolute',
            left: x,
            top: y,
            width: 6,
            height: 6,
            borderRadius: 99,
            background: '#fff',
            opacity: 0.2 + 0.6 * Math.abs(Math.sin(frame / 14 + i * 1.7)),
          }}
        />
      ))}

      <svg
        viewBox="0 0 1080 1920"
        style={{ position: 'absolute', inset: 0, width: '100%', height: '100%' }}
      >
        {/* siluet pegunungan Dieng */}
        <polygon
          points="0,1760 170,1470 370,1690 555,1395 760,1700 925,1510 1080,1745 1080,1920 0,1920"
          fill="rgba(10,22,52,0.9)"
        />
        <polygon
          points="0,1850 235,1630 465,1810 700,1570 905,1830 1080,1670 1080,1920 0,1920"
          fill="rgba(6,12,32,0.96)"
        />
        {/* jalur rencana (samar) */}
        <path
          d={`M ${P0.x} ${P0.y} Q ${C.x} ${C.y} ${P2.x} ${P2.y}`}
          fill="none"
          stroke="rgba(255,255,255,0.14)"
          strokeWidth={4}
          strokeDasharray="3 22"
          strokeLinecap="round"
        />
        {/* jalur progress digambar animasi */}
        <path
          d={`M ${P0.x} ${P0.y} Q ${C.x} ${C.y} ${P2.x} ${P2.y}`}
          fill="none"
          stroke="#7494ec"
          strokeWidth={12}
          strokeLinecap="round"
          strokeDasharray={PATH_LEN}
          strokeDashoffset={PATH_LEN * (1 - drawT)}
          style={{ filter: 'drop-shadow(0 0 14px rgba(116,148,236,0.65))' }}
        />
        {/* titik motor bergerak */}
        <circle cx={dx} cy={dy} r={38} fill="rgba(116,148,236,0.3)" />
        <circle cx={dx} cy={dy} r={18} fill="#ffffff" />
        {/* pin toko */}
        <g
          opacity={pinStart}
          transform={`translate(${P0.x} ${P0.y}) scale(${0.5 + pinStart * 0.5})`}
        >
          <circle r={26} fill="#7494ec" />
          <circle r={26} fill="none" stroke="#fff" strokeWidth={5} />
        </g>
        {/* pin Dieng */}
        <g
          opacity={pinEnd}
          transform={`translate(${P2.x} ${P2.y}) scale(${0.5 + pinEnd * 0.5})`}
        >
          <circle r={26} fill="#fff" />
          <circle r={26} fill="none" stroke="#7494ec" strokeWidth={5} />
        </g>
      </svg>

      <PinLabel x={P0.x} y={P0.y + 56} text="MotorKu Wonosobo" opacity={pinStart} />
      <PinLabel x={P2.x} y={P2.y + 56} text="Dieng Plateau 🏔️" opacity={pinEnd} />

      <div
        style={{
          position: 'absolute',
          left: C.x,
          top: C.y,
          transform: `translate(-50%, -50%) scale(${kmPop > 0 ? 0.6 + kmPop * 0.4 : 0.6})`,
          opacity: kmPop,
          background: '#fff',
          color: '#0b1530',
          fontWeight: 800,
          fontSize: 44,
          padding: '16px 38px',
          borderRadius: 999,
          boxShadow: '0 10px 34px rgba(0,0,0,0.35)',
        }}
      >
        ±17 km
      </div>

      <TopChip text="Ambil motor di Wonosobo" frame={frame} />
      <Headline title="Langsung eksplor Dieng!" sub="Liburan tanpa ribet" frame={frame} bottom={210} />
    </AbsoluteFill>
  );
};
