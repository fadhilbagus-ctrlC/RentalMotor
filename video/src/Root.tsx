import React from 'react';
import { Composition } from 'remotion';
import { TIMINGS } from './timings';
import { MotorKuPromo } from './MotorKuPromo';
import { MotorKuPoster } from './Poster';

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="MotorKuPromo"
      component={MotorKuPromo}
      durationInFrames={TIMINGS.totalFrames}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
    <Composition
      id="MotorKuPoster"
      component={MotorKuPoster}
      durationInFrames={1}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
  </>
);
