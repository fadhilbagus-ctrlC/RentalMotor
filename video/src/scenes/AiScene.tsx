import React from 'react';
import { AbsoluteFill, useCurrentFrame } from 'remotion';
import { fadeInOut, FONT, Headline, KenBurnsShot, Scrim } from '../shared';

export const AiScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const opacity = fadeInOut(frame, frames, 10);

  return (
    <AbsoluteFill style={{ opacity, fontFamily: FONT }}>
      <KenBurnsShot
        src="screens/ai.png"
        frame={frame}
        frames={frames}
        fromScale={1.02}
        toScale={1.06}
      />
      <Scrim direction="bottom" />
      <Headline title="Lokasi & jarak, langsung dijawab" frame={frame} />
    </AbsoluteFill>
  );
};
