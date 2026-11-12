import React from 'react';
import { AbsoluteFill, Img, interpolate, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { fadeInOut, FONT, spr } from '../shared';

export const CtaScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const opacity = fadeInOut(frame, frames, 10);
  const zoom = interpolate(frame, [0, frames], [1.14, 1.04]);
  const rise = interpolate(frame, [6, 26], [40, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const op = interpolate(frame, [6, 22], [0, 1], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });
  const pulse = 1 + 0.035 * Math.sin(frame / 7);
  const btn = spr(frame, 22, fps, 22, 13);

  return (
    <AbsoluteFill style={{ opacity, fontFamily: FONT }}>
      <AbsoluteFill>
        <Img
          src={staticFile('hero.jpg')}
          style={{
            width: '100%',
            height: '100%',
            objectFit: 'cover',
            transform: `scale(${zoom})`,
            filter: 'blur(4px) brightness(0.5)',
          }}
        />
      </AbsoluteFill>
      <AbsoluteFill
        style={{
          background:
            'linear-gradient(180deg, rgba(8,15,38,0.55) 0%, rgba(11,21,48,0.35) 45%, rgba(8,15,38,0.9) 100%)',
        }}
      />
      <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center' }}>
        <div
          style={{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            opacity: op,
            transform: `translateY(${rise}px)`,
            textAlign: 'center',
            padding: '0 70px',
          }}
        >
          <Img src={staticFile('logo-white.png')} style={{ width: 360, marginBottom: 40 }} />
          <div
            style={{
              fontSize: 84,
              fontWeight: 800,
              color: '#fff',
              lineHeight: 1.15,
              textShadow: '0 8px 30px rgba(0,0,0,0.5)',
            }}
          >
            MotorKu Wonosobo
          </div>
          <div
            style={{
              fontSize: 36,
              color: 'rgba(255,255,255,0.85)',
              marginTop: 18,
              fontWeight: 500,
            }}
          >
            Jl. Raya Wonosobo No. 88, Jawa Tengah
          </div>
          <div style={{ fontSize: 30, color: 'rgba(255,255,255,0.7)', marginTop: 8 }}>
            Setiap hari 05.00 – 23.00 WIB
          </div>
          <div
            style={{
              marginTop: 56,
              transform: `scale(${(0.7 + btn * 0.3) * pulse})`,
              opacity: btn,
              background: '#7494ec',
              color: '#fff',
              fontSize: 44,
              fontWeight: 800,
              padding: '26px 64px',
              borderRadius: 999,
              boxShadow: '0 18px 50px rgba(116,148,236,0.45)',
            }}
          >
            Sewa Motor Sekarang
          </div>
        </div>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
