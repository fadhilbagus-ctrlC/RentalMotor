import React from 'react';
import { Img, interpolate, spring, staticFile, useVideoConfig } from 'remotion';

// Palet diambil dari app: primary #7494ec (assets/login/style.css), aksen #ffd166
export const COLORS = {
  bg: '#0b1530',
  bg2: '#152450',
  surface: '#ffffff',
  surface2: '#eef2fa',
  ink: '#0b1530',
  inkSoft: '#43537a',
  muted: '#7c89ab',
  line: '#e3e9f4',
  brand: '#7494ec',
  brandDark: '#5d7fe0',
  accent: '#38bdf8',
  purple: '#a855f7',
  green: '#16a34a',
  amber: '#ffd166',
};

export const FONT = "'Segoe UI', 'Inter', system-ui, Arial, sans-serif";

export const fadeInOut = (frame: number, frames: number, dur = 10): number =>
  interpolate(frame, [0, dur, frames - dur, frames], [0, 1, 1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

export const enter = (frame: number, fps: number, delay = 0): number =>
  spring({
    frame: Math.max(0, frame - delay),
    fps,
    config: { damping: 15, mass: 0.75, stiffness: 140 },
  });

export const GlowOrb: React.FC<{
  x: number;
  y: number;
  size: number;
  color: string;
  frame: number;
  speed?: number;
}> = ({ x, y, size, color, frame, speed = 24 }) => (
  <div
    style={{
      position: 'absolute',
      left: x,
      top: y,
      width: size,
      height: size,
      borderRadius: '50%',
      background: `radial-gradient(circle at 50% 50%, ${color} 0%, transparent 70%)`,
      filter: 'blur(70px)',
      transform: `translate(${Math.sin(frame / speed) * 46}px, ${
        Math.cos((frame / speed) * 0.8) * 46
      }px)`,
    }}
  />
);

export const LogoMark: React.FC<{ height?: number }> = ({ height = 140 }) => (
  <Img src={staticFile('logo-white.png')} style={{ height, objectFit: 'contain' }} />
);

export const TopChip: React.FC<{ label: string; frame: number; frames: number }> = ({
  label,
  frame,
  frames,
}) => {
  const { fps } = useVideoConfig();
  const pop = enter(frame, fps, 2);
  const o = fadeInOut(frame, frames, 8);
  return (
    <div
      style={{
        position: 'absolute',
        top: 105,
        left: 0,
        right: 0,
        display: 'flex',
        justifyContent: 'center',
        opacity: o,
      }}
    >
      <div
        style={{
          transform: `scale(${0.8 + pop * 0.2})`,
          background: COLORS.brand,
          color: '#ffffff',
          fontSize: 32,
          fontWeight: 800,
          letterSpacing: 7,
          padding: '20px 48px',
          borderRadius: 999,
          boxShadow: '0 16px 40px rgba(0,0,0,0.4)',
        }}
      >
        {label}
      </div>
    </div>
  );
};

export const Headline: React.FC<{
  lines: string[];
  accentLines?: number[];
  top: number;
  frame: number;
  size?: number;
  delay?: number;
}> = ({ lines, accentLines = [], top, frame, size = 84, delay = 0 }) => {
  const { fps } = useVideoConfig();
  const pop = enter(frame, fps, delay);
  return (
    <div
      style={{
        position: 'absolute',
        top,
        left: 70,
        right: 70,
        textAlign: 'center',
        opacity: pop,
        transform: `translateY(${(1 - pop) * 46}px)`,
      }}
    >
      {lines.map((line, i) => (
        <div
          key={line}
          style={{
            fontSize: size,
            fontWeight: 800,
            lineHeight: 1.14,
            letterSpacing: -1.5,
            color: accentLines.includes(i) ? COLORS.amber : '#ffffff',
          }}
        >
          {line}
        </div>
      ))}
    </div>
  );
};
