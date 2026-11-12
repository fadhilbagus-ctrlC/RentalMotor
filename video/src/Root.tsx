import React from 'react';
import { Composition } from 'remotion';
import { MotorKuPromo } from './MotorKuPromo';
import { TIMINGS } from './timings';

export const RemotionRoot: React.FC = () => {
  return (
    <Composition
      id="MotorKuPromo"
      component={MotorKuPromo}
      durationInFrames={TIMINGS.totalFrames}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
  );
};
