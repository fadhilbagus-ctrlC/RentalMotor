import React from 'react';
import { AbsoluteFill, Audio, Sequence, staticFile } from 'remotion';
import { TIMINGS } from './timings';
import { IntroScene } from './scenes/IntroScene';
import { CatalogScene } from './scenes/CatalogScene';
import { BookingScene } from './scenes/BookingScene';
import { AiScene } from './scenes/AiScene';
import { RouteScene } from './scenes/RouteScene';
import { CtaScene } from './scenes/CtaScene';

const SCENES: Record<string, React.FC<{ frames: number }>> = {
  intro: IntroScene,
  katalog: CatalogScene,
  booking: BookingScene,
  ai: AiScene,
  rute: RouteScene,
  cta: CtaScene,
};

/**
 * Promo MotorKu 9:16 - tiap scene 1 voiceover sendiri, tanpa musik.
 * Scene full-screen UI + teks overlay.
 */
export const MotorKuPromo: React.FC = () => {
  let offset = 0;
  return (
    <AbsoluteFill style={{ backgroundColor: '#0b1530' }}>
      {TIMINGS.scenes.map((s) => {
        const from = offset;
        offset += s.frames;
        const Scene = SCENES[s.id];
        return (
          <Sequence key={s.id} from={from} durationInFrames={s.frames} name={s.id}>
            {Scene ? <Scene frames={s.frames} /> : null}
            {s.vo ? <Audio src={staticFile(s.vo)} /> : null}
          </Sequence>
        );
      })}
    </AbsoluteFill>
  );
};
