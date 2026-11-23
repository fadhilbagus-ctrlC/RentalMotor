import React from 'react';
import { AbsoluteFill, Audio, Sequence, staticFile } from 'remotion';
import { COLORS, FONT } from './shared';
import { TIMINGS } from './timings';
import { IntroScene } from './scenes/IntroScene';
import { HeroScene } from './scenes/HeroScene';
import { CatalogScene } from './scenes/CatalogScene';
import { BookingScene } from './scenes/BookingScene';
import { AiScene } from './scenes/AiScene';
import { RouteScene } from './scenes/RouteScene';
import { CtaScene } from './scenes/CtaScene';

type SceneId = (typeof TIMINGS.scenes)[number]['id'];

const SCENES: Record<SceneId, React.FC<{ frames: number }>> = {
  intro: IntroScene,
  hero: HeroScene,
  katalog: CatalogScene,
  booking: BookingScene,
  ai: AiScene,
  rute: RouteScene,
  cta: CtaScene,
};

export const RentalmotorPromo: React.FC = () => (
  <AbsoluteFill style={{ backgroundColor: COLORS.bg, fontFamily: FONT }}>
    {TIMINGS.scenes.map((scene, i) => {
      const from = TIMINGS.scenes.slice(0, i).reduce((acc, s) => acc + s.frames, 0);
      const SceneComp = SCENES[scene.id];
      return (
        <Sequence key={scene.id} from={from} durationInFrames={scene.frames} name={scene.id}>
          <SceneComp frames={scene.frames} />
          <Audio src={staticFile(scene.vo)} />
        </Sequence>
      );
    })}
  </AbsoluteFill>
);
