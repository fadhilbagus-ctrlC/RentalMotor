import React from 'react';
import { AbsoluteFill, Img, interpolate, spring, staticFile, useCurrentFrame, useVideoConfig } from 'remotion';
import { fadeInOut, FONT, Scrim } from '../shared';

export const IntroScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const opacity = fadeInOut(frame, frames, 10);
  const scale = interpolate(frame, [0, frames], [1.06, 1.18]);
  const pop = spring({ frame, fps, config: { damping: 15, mass: 0.8 }, durationInFrames: 28 });
  const rise = interpolate(frame, [10, 30], [50, 0], {
    extrapolateLeft: 'clamp',
    extrapolateRight: 'clamp',
  });

  return (
    <AbsoluteFill style={{ opacity, fontFamily: FONT }}>
      <AbsoluteFill>
        <Img
          src={staticFile('hero.jpg')}
          style={{ width: '100%', height: '100%', objectFit: 'cover', transform: `scale(${scale})` }}
        />
      </AbsoluteFill>
      <Scrim direction="full" />
      <AbsoluteFill style={{ alignItems: 'center', justifyContent: 'center' }}>
        <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center' }}>
          <Img
            src={staticFile('logo-white.png')}
            style={{
              width: 330,
              marginBottom: 40,
              transform: `scale(${0.85 + pop * 0.15})`,
              opacity: pop,
            }}
          />
          <div
            style={{
              opacity: pop,
              transform: `translateY(${rise}px)`,
              textAlign: 'center',
              padding: '0 70px',
            }}
          >
            <div
              style={{
                fontSize: 96,
                fontWeight: 800,
                color: '#fff',
                lineHeight: 1.12,
                textShadow: '0 10px 40px rgba(0,0,0,0.5)',
              }}
            >
              Liburan ke Dieng?
            </div>
            <div
              style={{
                fontSize: 40,
                color: 'rgba(255,255,255,0.9)',
                marginTop: 20,
                fontWeight: 600,
              }}
            >
              Jangan jalan kaki 🚀
            </div>
          </div>
        </div>
      </AbsoluteFill>
    </AbsoluteFill>
  );
};
