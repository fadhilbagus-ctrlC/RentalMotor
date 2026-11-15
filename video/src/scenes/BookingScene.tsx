import React from 'react';
import { AbsoluteFill, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, TopChip, enter, fadeInOut } from '../shared';
import { ShieldIcon } from '../ui/icons';
import { BookingForm } from '../ui/widgets';

const PAYMENTS = ['DANA', 'GoPay', 'ShopeePay', 'OVO', 'QRIS'];

export const BookingScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 10);
  const labelPop = enter(frame, fps, 68);
  const notePop = enter(frame, fps, 170);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 8%, ${COLORS.bg2}, ${COLORS.bg} 58%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-120} y={100} size={460} color="rgba(56,189,248,0.36)" frame={frame} speed={26} />
      <GlowOrb x={720} y={1000} size={440} color="rgba(168,85,247,0.30)" frame={frame} speed={32} />
      <TopChip label="BOOKING ONLINE" frame={frame} frames={frames} />
      <BookingForm frame={frame} fps={fps} />
      <div
        style={{
          position: 'absolute',
          top: 1190,
          left: 0,
          right: 0,
          textAlign: 'center',
          fontSize: 26,
          fontWeight: 700,
          letterSpacing: 4,
          color: COLORS.muted,
          opacity: labelPop,
        }}
      >
        PEMBAYARAN
      </div>
      <div
        style={{
          position: 'absolute',
          top: 1260,
          left: 60,
          right: 60,
          display: 'flex',
          justifyContent: 'center',
          flexWrap: 'wrap',
          gap: 18,
        }}
      >
        {PAYMENTS.map((pay, i) => {
          const pop = enter(frame, fps, 80 + i * 14);
          return (
            <div
              key={pay}
              style={{
                background: 'rgba(255,255,255,0.08)',
                border: '1px solid rgba(255,255,255,0.14)',
                color: '#fff',
                fontSize: 32,
                fontWeight: 700,
                padding: '18px 32px',
                borderRadius: 999,
                opacity: pop,
                transform: `scale(${0.8 + pop * 0.2})`,
              }}
            >
              {pay}
            </div>
          );
        })}
      </div>
      <div
        style={{
          position: 'absolute',
          top: 1420,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          gap: 14,
          opacity: notePop,
        }}
      >
        <ShieldIcon size={34} color={COLORS.accent} />
        <div style={{ fontSize: 30, fontWeight: 600, color: 'rgba(255,255,255,0.75)' }}>
          Pembayaran aman &amp; terlacak
        </div>
      </div>
    </AbsoluteFill>
  );
};
