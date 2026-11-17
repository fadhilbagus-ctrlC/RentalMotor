import React from 'react';
import { AbsoluteFill, Img, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, Headline, LogoMark, enter, fadeInOut } from '../shared';

export const CtaScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 8);
  const logoPop = enter(frame, fps, 0);
  const btnPop = enter(frame, fps, 18);
  const qrPop = enter(frame, fps, 30);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 28%, ${COLORS.bg2}, ${COLORS.bg} 65%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-100} y={200} size={480} color="rgba(56,189,248,0.38)" frame={frame} speed={28} />
      <GlowOrb x={640} y={900} size={460} color="rgba(168,85,247,0.30)" frame={frame} speed={34} />
      <div
        style={{
          position: 'absolute',
          top: 300,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: logoPop,
          transform: `scale(${0.7 + logoPop * 0.3})`,
        }}
      >
        <LogoMark height={230} />
      </div>
      <Headline
        lines={['Sewa Motor', 'Mudah & Terpercaya']}
        accentLines={[1]}
        top={620}
        frame={frame}
        size={86}
        delay={8}
      />
      <div
        style={{
          position: 'absolute',
          top: 950,
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
            fontSize: 44,
            fontWeight: 800,
            padding: '26px 64px',
            borderRadius: 999,
            boxShadow: '0 22px 50px rgba(93,127,224,0.4)',
          }}
        >
          Booking Sekarang
        </div>
      </div>
      <div
        style={{
          position: 'absolute',
          top: 1180,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: qrPop,
          transform: `translateY(${(1 - qrPop) * 40}px)`,
        }}
      >
        <div
          style={{
            background: '#ffffff',
            borderRadius: 40,
            padding: '34px 34px 26px',
            boxShadow: '0 30px 70px rgba(4,10,30,0.5)',
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
          }}
        >
          <Img
            src={staticFile('github-qr.png')}
            style={{ width: 300, height: 300, borderRadius: 14, display: 'block' }}
          />
          <div
            style={{
              marginTop: 16,
              fontSize: 26,
              fontWeight: 700,
              color: COLORS.inkSoft,
            }}
          >
            Scan — repo GitHub Motorent
          </div>
        </div>
      </div>
    </AbsoluteFill>
  );
};
