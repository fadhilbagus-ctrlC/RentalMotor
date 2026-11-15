import React from 'react';
import { Composition } from 'remotion';
import { TIMINGS } from './timings';
import { MotorKuPromo } from './MotorKuPromo';

export const RemotionRoot: React.FC = () => (
  <Composition
    id="MotorKuPromo"
    component={MotorKuPromo}
    durationInFrames={TIMINGS.totalFrames}
    fps={TIMINGS.fps}
    width={1080}
    height={1920}
  />
);
