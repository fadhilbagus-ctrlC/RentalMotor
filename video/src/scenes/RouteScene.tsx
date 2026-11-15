import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, TopChip, enter, fadeInOut } from '../shared';
import { MapPinIcon } from '../ui/icons';

// Port dari RouteScene v1 (final): kurva quadratic P0 -> C -> P2,
// sampling PATH_LEN, tanpa emoji di dalam SVG <text>.
const P0 = { x: 215, y: 1300 };
const C = { x: 545, y: 930 };
const P2 = { x: 872, y: 445 };
const PATH_LEN = 160;

const quad = (t: number): { x: number; y: number } => {
  const u = 1 - t;
  return {
    x: u * u * P0.x + 2 * u * t * C.x + t * t * P2.x,
    y: u * u * P0.y + 2 * u * t * C.y + t * t * P2.y,
  };
};

const SAMPLES: { x: number; y: number }[] = Array.from({ length: PATH_LEN + 1 }, (_, i) =>
  quad(i / PATH_LEN),
);

const toPoints = (pts: { x: number; y: number }[]): string =>
  pts.map((p) => `${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' ');

const FULL_PATH = toPoints(SAMPLES);

const PinLabel: React.FC<{
  x: number;
  y: number;
  label: string;
  pop: number;
}> = ({ x, y, label, pop }) => (
  <div
    style={{
      position: 'absolute',
      left: x,
      top: y,
      transform: `translate(-50%, calc(-100% - 46px)) scale(${0.6 + pop * 0.4})`,
      transformOrigin: '50% 100%',
      opacity: pop,
    }}
  >
    <div
      style={{
        background: '#ffffff',
        color: COLORS.ink,
        fontSize: 32,
        fontWeight: 800,
        padding: '14px 28px',
        borderRadius: 20,
        boxShadow: '0 14px 34px rgba(4,10,30,0.5)',
        whiteSpace: 'nowrap',
      }}
    >
      {label}
    </div>
    <div
      style={{
        width: 0,
        height: 0,
        margin: '0 auto',
        borderLeft: '12px solid transparent',
        borderRight: '12px solid transparent',
        borderTop: '14px solid #ffffff',
      }}
    />
  </div>
);

const PinDot: React.FC<{ x: number; y: number; color: string; pop: number }> = ({
  x,
  y,
  color,
  pop,
}) => (
  <div
    style={{
      position: 'absolute',
      left: x,
      top: y,
      transform: `translate(-50%, -50%) scale(${pop})`,
    }}
  >
    <div
      style={{
        width: 26,
        height: 26,
        borderRadius: '50%',
        background: color,
        border: '5px solid #fff',
        boxShadow: '0 0 0 12px rgba(255,255,255,0.12)',
      }}
    />
  </div>
);

export const RouteScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 10);
  const p = interpolate(frame, [8, frames - 14], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const idx = Math.round(p * PATH_LEN);
  const head = SAMPLES[idx];
  const shown = toPoints(SAMPLES.slice(0, idx + 1));
  const startPop = enter(frame, fps, 6);
  const endPop = enter(frame, fps, frames - 16);
  const chipPop = enter(frame, fps, 20);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 40%, ${COLORS.bg2}, ${COLORS.bg} 62%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-100} y={600} size={480} color="rgba(56,189,248,0.30)" frame={frame} speed={28} />
      <GlowOrb x={620} y={-100} size={420} color="rgba(168,85,247,0.28)" frame={frame} speed={34} />
      {/* dekorasi jalan & danau, statis */}
      <svg
        width={1080}
        height={1920}
        style={{ position: 'absolute', top: 0, left: 0 }}
        viewBox="0 0 1080 1920"
      >
        <polyline
          points="0,1560 190,1470 420,1540 660,1430 900,1520 1080,1440"
          stroke="rgba(255,255,255,0.05)"
          strokeWidth={30}
          fill="none"
          strokeLinecap="round"
        />
        <polyline
          points="0,880 210,970 430,860 690,990 940,870 1080,930"
          stroke="rgba(255,255,255,0.045)"
          strokeWidth={26}
          fill="none"
          strokeLinecap="round"
        />
        <circle cx="880" cy="1460" r="120" fill="rgba(56,189,248,0.05)" />
        <circle cx="240" cy="500" r="150" fill="rgba(168,85,247,0.05)" />
        <defs>
          <linearGradient id="routeGrad" x1="0" y1="1" x2="1" y2="0">
            <stop offset="0%" stopColor={COLORS.accent} />
            <stop offset="100%" stopColor={COLORS.purple} />
          </linearGradient>
        </defs>
        {/* jalur penuh (ghost) */}
        <polyline
          points={FULL_PATH}
          stroke="rgba(255,255,255,0.16)"
          strokeWidth={12}
          fill="none"
          strokeLinecap="round"
          strokeDasharray="1 30"
        />
        {/* jalur yang sudah ditempuh */}
        <polyline
          points={shown}
          stroke="url(#routeGrad)"
          strokeWidth={14}
          fill="none"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
        <circle cx={head.x} cy={head.y} r={30} fill="rgba(56,189,248,0.30)" />
        <circle cx={head.x} cy={head.y} r={15} fill="#ffffff" stroke={COLORS.accent} strokeWidth={6} />
      </svg>
      <TopChip label="RUTE PERJALANAN" frame={frame} frames={frames} />
      <PinDot x={P0.x} y={P0.y} color={COLORS.brand} pop={startPop} />
      <PinDot x={P2.x} y={P2.y} color={COLORS.purple} pop={endPop} />
      <PinLabel x={P0.x} y={P0.y} label="MotorKu" pop={startPop} />
      <PinLabel x={P2.x} y={P2.y} label="Dieng Plateau 🏔️" pop={endPop} />
      <div
        style={{
          position: 'absolute',
          top: 1420,
          left: 60,
          display: 'flex',
          alignItems: 'center',
          gap: 14,
          background: 'rgba(255,255,255,0.08)',
          border: '1px solid rgba(255,255,255,0.14)',
          color: '#fff',
          fontSize: 32,
          fontWeight: 700,
          padding: '20px 38px',
          borderRadius: 999,
          opacity: chipPop,
          transform: `translateY(${(1 - chipPop) * 30}px)`,
        }}
      >
        <MapPinIcon size={34} color={COLORS.amber} />
        Ambil motor di toko
      </div>
    </AbsoluteFill>
  );
};
