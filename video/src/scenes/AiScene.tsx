import React from 'react';
import { AbsoluteFill, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, TopChip, enter, fadeInOut } from '../shared';
import { MapPinIcon } from '../ui/icons';
import { ChatWidget } from '../ui/widgets';

export const AiScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 10);
  const chipPop = enter(frame, fps, 96);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 8%, ${COLORS.bg2}, ${COLORS.bg} 58%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-120} y={100} size={460} color="rgba(56,189,248,0.36)" frame={frame} speed={26} />
      <GlowOrb x={700} y={1100} size={420} color="rgba(168,85,247,0.30)" frame={frame} speed={32} />
      <TopChip label="TANYA AI MOTORKU" frame={frame} frames={frames} />
      <ChatWidget frame={frame} fps={fps} />
      <div
        style={{
          position: 'absolute',
          top: 1080,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: chipPop,
          transform: `translateY(${(1 - chipPop) * 30}px)`,
        }}
      >
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 14,
            background: 'rgba(255,255,255,0.08)',
            border: '1px solid rgba(255,255,255,0.14)',
            color: '#fff',
            fontSize: 34,
            fontWeight: 700,
            padding: '20px 40px',
            borderRadius: 999,
          }}
        >
          <MapPinIcon size={36} color={COLORS.amber} />
          Sekitar 17,0 km dari kamu
        </div>
      </div>
    </AbsoluteFill>
  );
};
