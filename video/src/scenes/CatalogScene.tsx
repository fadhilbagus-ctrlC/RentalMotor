import React from 'react';
import { AbsoluteFill, useCurrentFrame, useVideoConfig } from 'remotion';
import { COLORS, GlowOrb, TopChip, enter, fadeInOut } from '../shared';
import { ProductCard } from '../ui/widgets';

const ITEMS = [
  {
    img: 'products/yamaha-mio-m3.webp',
    name: 'Yamaha Mio M3',
    spec: 'Matic · 110cc',
    price: 'Rp 70.000',
    left: 60,
    top: 380,
    delay: 10,
  },
  {
    img: 'products/honda-beat-deluxe.webp',
    name: 'Honda Beat Deluxe',
    spec: 'Matic · 110cc',
    price: 'Rp 75.000',
    left: 560,
    top: 380,
    delay: 20,
  },
  {
    img: 'products/honda-scoopy.webp',
    name: 'Honda Scoopy',
    spec: 'Matic · 110cc',
    price: 'Rp 85.000',
    left: 60,
    top: 900,
    delay: 30,
  },
  {
    img: 'products/yamaha-nmax.webp',
    name: 'Yamaha NMAX',
    spec: 'Matic · 155cc',
    price: 'Rp 120.000',
    left: 560,
    top: 900,
    delay: 40,
  },
];

export const CatalogScene: React.FC<{ frames: number }> = ({ frames }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const o = fadeInOut(frame, frames, 10);
  const extraPop = enter(frame, fps, 62);
  const pricePop = enter(frame, fps, 76);
  return (
    <AbsoluteFill
      style={{
        background: `radial-gradient(circle at 50% 10%, ${COLORS.bg2}, ${COLORS.bg} 58%)`,
        opacity: o,
      }}
    >
      <GlowOrb x={-120} y={200} size={480} color="rgba(56,189,248,0.36)" frame={frame} speed={26} />
      <GlowOrb x={700} y={900} size={440} color="rgba(168,85,247,0.30)" frame={frame} speed={32} />
      <TopChip label="KATALOG MOTOR" frame={frame} frames={frames} />
      {ITEMS.map((item, i) => (
        <ProductCard
          key={item.name}
          img={item.img}
          name={item.name}
          spec={item.spec}
          price={item.price}
          left={item.left}
          top={item.top}
          frame={frame}
          fps={fps}
          delay={item.delay}
          floatPhase={i * 1.7}
        />
      ))}
      <div
        style={{
          position: 'absolute',
          top: 1450,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: extraPop,
        }}
      >
        <div
          style={{
            background: 'rgba(255,255,255,0.08)',
            border: '1px solid rgba(255,255,255,0.14)',
            color: 'rgba(255,255,255,0.85)',
            fontSize: 30,
            fontWeight: 600,
            padding: '16px 38px',
            borderRadius: 999,
          }}
        >
          + Vario 125 &amp; PCX 160 juga tersedia
        </div>
      </div>
      <div
        style={{
          position: 'absolute',
          top: 1560,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          opacity: pricePop,
          transform: `scale(${0.85 + pricePop * 0.15})`,
        }}
      >
        <div
          style={{
            background: COLORS.brand,
            color: '#fff',
            fontSize: 44,
            fontWeight: 800,
            padding: '24px 56px',
            borderRadius: 999,
            boxShadow: '0 22px 50px rgba(93,127,224,0.4)',
          }}
        >
          Mulai Rp 70.000 / hari
        </div>
      </div>
      <div
        style={{
          position: 'absolute',
          top: 1690,
          left: 0,
          right: 0,
          textAlign: 'center',
          fontSize: 28,
          fontWeight: 700,
          color: COLORS.amber,
          letterSpacing: 1.5,
          opacity: pricePop,
        }}
      >
        Paket mingguan lebih hemat
      </div>
    </AbsoluteFill>
  );
};
