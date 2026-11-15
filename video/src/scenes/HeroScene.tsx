import React from 'react';
import { AbsoluteFill, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, enter, fadeInOut } from '../shared';
import { FilterIcon, SearchIcon, ShieldIcon } from '../ui/icons';
import { HeroBanner, Navbar, SearchPanel } from '../ui/widgets';

const FEATURES = [
  {
    icon: SearchIcon,
    label: 'Pencarian instan',
    tint: COLORS.accent,
    tintBg: 'rgba(56,189,248,0.16)',
  },
  {
    icon: FilterIcon,
    label: 'Filter merek & tipe',
    tint: COLORS.purple,
    tintBg: 'rgba(168,85,247,0.16)',
  },
  {
    icon: ShieldIcon,
    label: 'Harga transparan',
    tint: COLORS.green,
    tintBg: 'rgba(22,163,74,0.16)',
  },
];

export const HeroScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 10);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 0%, ${COLORS.bg2}, ${COLORS.bg} 60%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-120} y={-80} size={460} color="rgba(56,189,248,0.42)" frame={frame} speed={24} />
      <GlowOrb x={720} y={60} size={420} color="rgba(168,85,247,0.35)" frame={frame} speed={30} />
      <Navbar frame={frame} fps={fps} />
      <HeroBanner frame={frame} fps={fps} />
      <SearchPanel frame={frame} fps={fps} />
      {FEATURES.map((f, i) => {
        const pop = enter(frame, fps, 66 + i * 12);
        const Icon = f.icon;
        return (
          <div
            key={f.label}
            style={{
              position: 'absolute',
              top: 1230 + i * 162,
              left: 60,
              right: 60,
              display: 'flex',
              alignItems: 'center',
              gap: 28,
              background: 'rgba(255,255,255,0.07)',
              border: '1px solid rgba(255,255,255,0.12)',
              borderRadius: 30,
              padding: '26px 34px',
              opacity: pop,
              transform: `translateX(${(1 - pop) * (i % 2 === 0 ? -50 : 50)}px)`,
            }}
          >
            <div
              style={{
                width: 92,
                height: 92,
                borderRadius: 26,
                background: f.tintBg,
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Icon size={44} color={f.tint} />
            </div>
            <div style={{ fontSize: 40, fontWeight: 700, color: '#fff' }}>{f.label}</div>
          </div>
        );
      })}
    </AbsoluteFill>
  );
};
