import React from 'react';
import { Composition } from 'remotion';
import { TIMINGS } from './timings';
import { MotorentPromo } from './MotorentPromo';
import { MotorentPoster } from './Poster';

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="MotorentPromo"
      component={MotorentPromo}
      durationInFrames={TIMINGS.totalFrames}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
    <Composition
      id="MotorentPoster"
      component={MotorentPoster}
      durationInFrames={1}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
  </>
);
