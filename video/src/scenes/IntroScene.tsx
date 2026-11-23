import React from 'react';
import { AbsoluteFill, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, Headline, LogoMark, enter, fadeInOut } from '../shared';

export const IntroScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 10);
  const logoPop = enter(frame, fps, 2);
  const btnPop = enter(frame, fps, 44);
  const notePop = enter(frame, fps, 62);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 18%, ${COLORS.bg2} 0%, ${COLORS.bg} 62%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-140} y={-60} size={520} color="rgba(56,189,248,0.40)" frame={frame} speed={26} />
      <GlowOrb x={700} y={1100} size={480} color="rgba(168,85,247,0.32)" frame={frame} speed={32} />
      <div
        style={{
          position: 'absolute',
          top: 520,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: logoPop,
          transform: `scale(${0.7 + logoPop * 0.3})`,
        }}
      >
        <LogoMark height={260} />
      </div>
      <Headline
        lines={['Liburan ke Dieng?', 'Jangan jalan kaki.']}
        accentLines={[1]}
        top={900}
        frame={frame}
        size={88}
        delay={14}
      />
      <div
        style={{
          position: 'absolute',
          top: 1250,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: btnPop,
          transform: `translateY(${(1 - btnPop) * 36}px)`,
        }}
      >
        <div
          style={{
            background: COLORS.brand,
            color: '#fff',
            fontSize: 42,
            fontWeight: 800,
            padding: '26px 62px',
            borderRadius: 999,
            boxShadow: '0 22px 50px rgba(93,127,224,0.4)',
          }}
        >
          Sewa motor di Rentalmotor
        </div>
      </div>
      <div
        style={{
          position: 'absolute',
          top: 1480,
          left: 0,
          right: 0,
          textAlign: 'center',
          fontSize: 30,
          fontWeight: 600,
          color: 'rgba(255,255,255,0.55)',
          letterSpacing: 2,
          opacity: notePop,
        }}
      >
        Booking online · ambil di toko
      </div>
    </AbsoluteFill>
  );
};
