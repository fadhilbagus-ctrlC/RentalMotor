import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame, useVideoConfig } from 'remotion';
import { clamp01, fadeInOut, FONT, Headline, KenBurnsShot, Scrim, spr, TopChip } from '../shared';

const PAY = ['DANA', 'GoPay', 'ShopeePay', 'OVO', 'QRIS'];

export const BookingScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const opacity = fadeInOut(frame, frames, 10);
  const mid = Math.round(frames * 0.45);
  const cut = 8;
  const op1 = interpolate(frame, [mid - cut, mid + cut], [1, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const op2 = clamp01(1 - op1);

  return (
    <AbsoluteFill style={{ opacity, fontFamily: FONT }}>
      <AbsoluteFill style={{ opacity: op1 }}>
        <KenBurnsShot
          src="screens/detail.png"
          frame={frame}
          frames={frames}
          fromScale={1.02}
          toScale={1.09}
          driftY={-1}
        />
      </AbsoluteFill>
      <AbsoluteFill style={{ opacity: op2 }}>
        <KenBurnsShot
          src="screens/checkout.png"
          frame={Math.max(0, frame - mid)}
          frames={Math.max(1, frames - mid)}
          fromScale={1.06}
          toScale={1.1}
          driftY={-1}
        />
      </AbsoluteFill>
      <Scrim direction="bottom" />
      <TopChip text="Harga jelas, booking cepat" frame={frame} />
      <Headline title="Bayar semudah itu" frame={frame} bottom={200} />
      <div
        style={{
          position: 'absolute',
          left: 0,
          right: 0,
          bottom: 96,
          display: 'flex',
          justifyContent: 'center',
          gap: 14,
          flexWrap: 'wrap',
          padding: '0 40px',
        }}
      >
        {PAY.map((p, i) => {
          const s = spr(frame, 18 + i * 5, fps, 22, 13);
          return (
            <div
              key={p}
              style={{
                background: 'rgba(116,148,236,0.95)',
                color: '#fff',
                fontWeight: 700,
                fontSize: 34,
                padding: '12px 30px',
                borderRadius: 999,
                opacity: s,
                transform: `scale(${0.6 + s * 0.4})`,
                boxShadow: '0 8px 24px rgba(116,148,236,0.35)',
              }}
            >
              {p}
            </div>
          );
        })}
      </div>
    </AbsoluteFill>
  );
};
