import React from 'react';
import { Composition } from 'remotion';
import { TIMINGS } from './timings';
import { RentalmotorPromo } from './RentalmotorPromo';
import { RentalmotorPoster } from './Poster';

export const RemotionRoot: React.FC = () => (
  <>
    <Composition
      id="RentalmotorPromo"
      component={RentalmotorPromo}
      durationInFrames={TIMINGS.totalFrames}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
    <Composition
      id="RentalmotorPoster"
      component={RentalmotorPoster}
      durationInFrames={1}
      fps={TIMINGS.fps}
      width={1080}
      height={1920}
    />
  </>
);
