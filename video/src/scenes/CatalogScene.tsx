import React from 'react';
import { AbsoluteFill, interpolate, useCurrentFrame } from 'remotion';
import { clamp01, fadeInOut, FONT, Headline, KenBurnsShot, Scrim, TopChip } from '../shared';

export const CatalogScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const opacity = fadeInOut(frame, frames, 10);
  const mid = Math.round(frames * 0.44);
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
          src="screens/home.png"
          frame={frame}
          frames={frames}
          fromScale={1.02}
          toScale={1.08}
          driftY={-1.5}
        />
      </AbsoluteFill>
      <AbsoluteFill style={{ opacity: op2 }}>
        <KenBurnsShot
          src="screens/catalog.png"
          frame={Math.max(0, frame - mid)}
          frames={Math.max(1, frames - mid)}
          fromScale={1.06}
          toScale={1.1}
          driftY={-1}
        />
      </AbsoluteFill>
      <Scrim direction="bottom" />
      <TopChip text="Katalog MotorKu" frame={frame} />
      <Headline title="Pilih motornya" sub="Beat • Vario • NMAX • PCX" frame={frame} />
    </AbsoluteFill>
  );
};
