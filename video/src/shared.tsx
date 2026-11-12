import React from 'react';
import { AbsoluteFill, Img, interpolate, spring, staticFile } from 'remotion';

export const FONT = "-apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
export const BRAND = '#7494ec';
export const INK = '#0b1530';

export const clamp01 = (v: number): number => Math.min(1, Math.max(0, v));

/** Fade masuk & keluar untuk tiap scene. */
export const fadeInOut = (frame: number, frames: number, fade = 8): number =>
  interpolate(frame, [0, fade, frames - fade, frames], [0, 1, 1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

/** Spring dengan jeda aman (tidak menerima frame negatif). */
export const spr = (
  frame: number,
  delay: number,
  fps: number,
  dur = 24,
  damping = 12,
): number => {
  const f = frame - delay;
  if (f <= 0) return 0;
  return spring({ frame: f, fps, config: { damping, mass: 0.8 }, durationInFrames: dur });
};

/** Screenshot full-screen dengan efek Ken Burns (zoom + geser halus). */
export const KenBurnsShot: React.FC<{
  src: string;
  frame: number;
  frames: number;
  fromScale?: number;
  toScale?: number;
  driftY?: number;
}> = ({ src, frame, frames, fromScale = 1.03, toScale = 1.12, driftY = 0 }) => {
  const t = clamp01(frame / Math.max(1, frames));
  const scale = fromScale + (toScale - fromScale) * t;
  return (
    <AbsoluteFill>
      <Img
        src={staticFile(src)}
        style={{
          width: '100%',
          height: '100%',
          objectFit: 'cover',
          transform: `scale(${scale}) translateY(${driftY * t}%)`,
        }}
      />
    </AbsoluteFill>
  );
};

/** Gradient tipis biar teks overlay terbaca. */
export const Scrim: React.FC<{ direction?: 'bottom' | 'top' | 'full' }> = ({
  direction = 'bottom',
}) => (
  <AbsoluteFill
    style={{
      background:
        direction === 'bottom'
          ? 'linear-gradient(180deg, rgba(8,15,38,0) 42%, rgba(8,15,38,0.88) 100%)'
          : direction === 'top'
            ? 'linear-gradient(0deg, rgba(8,15,38,0) 55%, rgba(8,15,38,0.72) 100%)'
            : 'linear-gradient(180deg, rgba(8,15,38,0.38) 0%, rgba(8,15,38,0.12) 40%, rgba(8,15,38,0.86) 100%)',
    }}
  />
);

/** Headline besar di bawah layar. */
export const Headline: React.FC<{
  title: string;
  sub?: string;
  frame: number;
  bottom?: number;
}> = ({ title, sub, frame, bottom = 200 }) => {
  const rise = interpolate(frame, [6, 26], [44, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const op = interpolate(frame, [6, 22], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  return (
    <div
      style={{
        position: 'absolute',
        left: 64,
        right: 64,
        bottom,
        textAlign: 'center',
        opacity: op,
        transform: `translateY(${rise}px)`,
      }}
    >
      <div
        style={{
          fontSize: 84,
          fontWeight: 800,
          color: '#fff',
          lineHeight: 1.14,
          textShadow: '0 8px 32px rgba(0,0,0,0.55)',
        }}
      >
        {title}
      </div>
      {sub ? (
        <div
          style={{
            fontSize: 40,
            color: 'rgba(255,255,255,0.92)',
            marginTop: 16,
            fontWeight: 600,
          }}
        >
          {sub}
        </div>
      ) : null}
    </div>
  );
};

/** Chip kecil di atas layar. */
export const TopChip: React.FC<{ text: string; frame: number }> = ({ text, frame }) => {
  const op = interpolate(frame, [4, 16], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  return (
    <div
      style={{
        position: 'absolute',
        top: 105,
        left: 0,
        right: 0,
        display: 'flex',
        justifyContent: 'center',
        opacity: op,
      }}
    >
      <div
        style={{
          background: 'rgba(11,21,48,0.92)',
          border: '2px solid rgba(116,148,236,0.65)',
          color: '#dfe7ff',
          padding: '14px 36px',
          borderRadius: 999,
          fontSize: 34,
          fontWeight: 700,
          letterSpacing: 0.5,
          backdropFilter: 'blur(6px)',
        }}
      >
        {text}
      </div>
    </div>
  );
};
