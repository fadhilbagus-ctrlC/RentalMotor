import React from 'react';
import { AbsoluteFill, Img, staticFile } from 'remotion';
import { COLORS, FONT, GlowOrb, LogoMark } from './shared';
import { FilterIcon, SearchIcon, ShieldIcon, StarIcon } from './ui/icons';

// Poster statis 1080x1920 (Story) — gaya modern, nyambung dengan bahasa visual video.
// Kontak hanya GitHub (tanpa alamat/WA/IG), konsisten dengan video.

const PRICES = [
  { name: 'Mio M3', price: 'Rp 70.000' },
  { name: 'Beat', price: 'Rp 75.000' },
  { name: 'Scoopy', price: 'Rp 85.000' },
  { name: 'Vario 125', price: 'Rp 90.000' },
  { name: 'NMAX', price: 'Rp 120.000' },
  { name: 'PCX 160', price: 'Rp 130.000' },
];

const FEATURES = [
  { icon: SearchIcon, label: 'Pencarian instan', tint: COLORS.accent },
  { icon: FilterIcon, label: 'Filter merek & tipe', tint: COLORS.purple },
  { icon: ShieldIcon, label: 'Harga transparan', tint: COLORS.green },
];

const PosterCard: React.FC<{
  img: string;
  name: string;
  spec: string;
  price: string;
  width: number;
  photoH: number;
  left: number;
  top: number;
  rotate: number;
  z: number;
}> = ({ img, name, spec, price, width, photoH, left, top, rotate, z }) => (
  <div
    style={{
      position: 'absolute',
      left,
      top,
      width,
      zIndex: z,
      background: COLORS.surface,
      borderRadius: 40,
      overflow: 'hidden',
      boxShadow: '0 40px 90px rgba(3,8,25,0.55)',
      transform: `rotate(${rotate}deg)`,
    }}
  >
    <div style={{ position: 'relative' }}>
      <Img
        src={staticFile(img)}
        style={{ width: '100%', height: photoH, objectFit: 'cover', display: 'block' }}
      />
      <div
        style={{
          position: 'absolute',
          top: 22,
          left: 22,
          background: COLORS.green,
          color: '#fff',
          fontSize: 22,
          fontWeight: 800,
          padding: '10px 22px',
          borderRadius: 999,
        }}
      >
        Tersedia
      </div>
    </div>
    <div style={{ padding: '26px 30px 30px' }}>
      <div style={{ fontSize: 34, fontWeight: 800, color: COLORS.ink }}>{name}</div>
      <div style={{ marginTop: 8, fontSize: 24, color: COLORS.muted, fontWeight: 600 }}>{spec}</div>
      <div style={{ marginTop: 12, display: 'flex', alignItems: 'baseline', gap: 8 }}>
        <div style={{ fontSize: 36, fontWeight: 800, color: COLORS.brandDark }}>{price}</div>
        <div style={{ fontSize: 24, color: COLORS.muted, fontWeight: 600 }}>/hari</div>
      </div>
    </div>
  </div>
);

const PricePill: React.FC<{ name: string; price: string }> = ({ name, price }) => (
  <div
    style={{
      display: 'flex',
      alignItems: 'baseline',
      gap: 10,
      background: 'rgba(255,255,255,0.07)',
      border: '1px solid rgba(255,255,255,0.13)',
      borderRadius: 999,
      padding: '14px 24px',
    }}
  >
    <span style={{ fontSize: 25, fontWeight: 800, color: '#ffffff' }}>{name}</span>
    <span style={{ fontSize: 25, fontWeight: 700, color: COLORS.amber }}>{price}</span>
  </div>
);

export const MotorKuPoster: React.FC = () => {
  return (
    <AbsoluteFill
      style={{
        fontFamily: FONT,
        background: `linear-gradient(180deg, ${COLORS.bg2} 0%, ${COLORS.bg} 55%)`,
      }}
    >
      <GlowOrb x={-160} y={-120} size={560} color="rgba(116,148,236,0.45)" frame={40} speed={24} />
      <GlowOrb x={680} y={240} size={520} color="rgba(168,85,247,0.35)" frame={40} speed={30} />
      <GlowOrb x={-80} y={1380} size={520} color="rgba(56,189,248,0.28)" frame={40} speed={26} />

      {/* grid titik halus */}
      <div
        style={{
          position: 'absolute',
          inset: 0,
          backgroundImage: 'radial-gradient(rgba(255,255,255,0.05) 1.5px, transparent 1.5px)',
          backgroundSize: '46px 46px',
        }}
      />
      {/* cincin dekoratif */}
      <div
        style={{
          position: 'absolute',
          width: 430,
          height: 430,
          borderRadius: '50%',
          border: '2px solid rgba(255,255,255,0.07)',
          right: -150,
          top: 1150,
        }}
      />
      <div
        style={{
          position: 'absolute',
          width: 260,
          height: 260,
          borderRadius: '50%',
          border: '2px solid rgba(255,255,255,0.06)',
          left: -95,
          top: 610,
        }}
      />

      {/* kicker */}
      <div
        style={{
          position: 'absolute',
          top: 90,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
        }}
      >
        <div
          style={{
            border: '2px solid rgba(255,255,255,0.22)',
            color: 'rgba(255,255,255,0.85)',
            fontSize: 24,
            fontWeight: 800,
            letterSpacing: 7,
            padding: '13px 34px',
            borderRadius: 999,
          }}
        >
          RENTAL MOTOR
        </div>
      </div>

      {/* logo + wordmark */}
      <div
        style={{
          position: 'absolute',
          top: 172,
          left: 0,
          right: 0,
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 20,
        }}
      >
        <LogoMark height={96} />
        <div style={{ fontSize: 62, fontWeight: 900, letterSpacing: -1 }}>
          <span style={{ color: COLORS.brand }}>Motor</span>
          <span style={{ color: '#ffffff' }}>Ku</span>
        </div>
      </div>

      {/* headline */}
      <div style={{ position: 'absolute', top: 340, left: 60, right: 60, textAlign: 'center' }}>
        <div style={{ fontSize: 88, fontWeight: 900, color: '#ffffff', lineHeight: 1.08, letterSpacing: -2 }}>
          Sewa Motor
        </div>
        <div
          style={{
            fontSize: 88,
            fontWeight: 900,
            lineHeight: 1.08,
            letterSpacing: -2,
            background: `linear-gradient(90deg, ${COLORS.brand}, ${COLORS.purple})`,
            WebkitBackgroundClip: 'text',
            backgroundClip: 'text',
            color: 'transparent',
          }}
        >
          Gampang &amp; Cepat
        </div>
        <div
          style={{
            marginTop: 22,
            fontSize: 28,
            color: 'rgba(255,255,255,0.68)',
            fontWeight: 600,
          }}
        >
          Booking online · ambil di toko · buka 05.00–23.00 WIB
        </div>
      </div>

      {/* kolase kartu produk */}
      <PosterCard
        img="products/honda-scoopy.webp"
        name="Honda Scoopy"
        spec="Matic · 110cc"
        price="Rp 85.000"
        width={420}
        photoH={280}
        left={55}
        top={730}
        rotate={-6}
        z={1}
      />
      <PosterCard
        img="products/yamaha-nmax.webp"
        name="Yamaha NMAX"
        spec="Matic · 155cc"
        price="Rp 120.000"
        width={520}
        photoH={320}
        left={455}
        top={680}
        rotate={3}
        z={2}
      />
      {/* chip rating melayang */}
      <div
        style={{
          position: 'absolute',
          left: 885,
          top: 640,
          zIndex: 3,
          transform: 'rotate(6deg)',
          display: 'flex',
          alignItems: 'center',
          gap: 10,
          background: 'rgba(255,255,255,0.94)',
          borderRadius: 999,
          padding: '12px 22px',
          boxShadow: '0 16px 40px rgba(3,8,25,0.45)',
        }}
      >
        <StarIcon size={30} />
        <span style={{ fontSize: 28, fontWeight: 800, color: COLORS.ink }}>4.9</span>
      </div>

      {/* strip daftar harga */}
      <div
        style={{
          position: 'absolute',
          top: 1270,
          left: 60,
          right: 60,
          display: 'flex',
          justifyContent: 'center',
          flexWrap: 'wrap',
          gap: 14,
        }}
      >
        {PRICES.map((p) => (
          <PricePill key={p.name} name={p.name} price={p.price} />
        ))}
      </div>

      {/* bar fitur */}
      <div
        style={{
          position: 'absolute',
          top: 1470,
          left: 60,
          right: 60,
          height: 104,
          borderRadius: 30,
          background: 'rgba(255,255,255,0.06)',
          border: '1px solid rgba(255,255,255,0.12)',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'space-around',
          padding: '0 20px',
        }}
      >
        {FEATURES.map((f) => {
          const Icon = f.icon;
          return (
            <div key={f.label} style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
              <div
                style={{
                  width: 58,
                  height: 58,
                  borderRadius: 18,
                  background: 'rgba(255,255,255,0.08)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Icon size={32} color={f.tint} />
              </div>
              <span style={{ fontSize: 25, fontWeight: 600, color: 'rgba(255,255,255,0.9)' }}>
                {f.label}
              </span>
            </div>
          );
        })}
      </div>

      {/* badge harga utama */}
      <div
        style={{
          position: 'absolute',
          top: 1630,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          gap: 20,
        }}
      >
        <div
          style={{
            background: `linear-gradient(90deg, ${COLORS.brand}, ${COLORS.brandDark})`,
            color: '#fff',
            fontSize: 42,
            fontWeight: 900,
            padding: '22px 52px',
            borderRadius: 999,
            boxShadow: '0 26px 60px rgba(93,127,224,0.45)',
          }}
        >
          Mulai Rp 70.000 / hari
        </div>
        <div style={{ fontSize: 26, fontWeight: 700, color: COLORS.amber }}>
          · paket mingguan hemat
        </div>
      </div>

      {/* footer: QR GitHub */}
      <div
        style={{
          position: 'absolute',
          top: 1746,
          left: 0,
          right: 0,
          display: 'flex',
          justifyContent: 'center',
        }}
      >
        <div
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 22,
            background: '#ffffff',
            borderRadius: 28,
            padding: '20px 32px 20px 20px',
            boxShadow: '0 24px 60px rgba(3,8,25,0.5)',
          }}
        >
          <Img
            src={staticFile('github-qr.png')}
            style={{ width: 112, height: 112, borderRadius: 14, display: 'block' }}
          />
          <div>
            <div style={{ fontSize: 26, fontWeight: 800, color: COLORS.ink }}>
              github.com/fadhilbagus-ctrlC/RentalMotor
            </div>
            <div style={{ marginTop: 6, fontSize: 22, fontWeight: 600, color: COLORS.inkSoft }}>
              Scan QR — kode sumber &amp; detail project
            </div>
          </div>
        </div>
      </div>

    </AbsoluteFill>
  );
};
