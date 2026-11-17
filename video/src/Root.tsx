import React from 'react';
import { Composition } from 'remotion';
import { TIMINGS } from './timings';
import { MotorentPromo } from './MotorentPromo';

export const RemotionRoot: React.FC = () => (
  <Composition
    id="MotorentPromo"
    component={MotorentPromo}
    durationInFrames={TIMINGS.totalFrames}
    fps={TIMINGS.fps}
    width={1080}
    height={1920}
  />
);
