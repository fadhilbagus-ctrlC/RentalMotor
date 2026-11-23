import React from 'react';
import { Img, staticFile } from 'remotion';
import { COLORS, enter } from '../shared';
import { CalendarIcon, ChatIcon, ChevronDown, SearchIcon, SendIcon } from './icons';

// Navbar mock — tinggi 116, mengikuti state login app (user tampil sebagai "Member")
export const Navbar: React.FC<{ frame: number; fps: number }> = ({ frame, fps }) => {
  const pop = enter(frame, fps, 4);
  return (
    <div
      style={{
        position: 'absolute',
        top: 18,
        left: 40,
        right: 40,
        height: 116,
        borderRadius: 30,
        background: COLORS.surface,
        boxShadow: '0 18px 44px rgba(4,10,30,0.45)',
        display: 'flex',
        alignItems: 'center',
        padding: '0 36px',
        opacity: pop,
        transform: `translateY(${(1 - pop) * -36}px)`,
      }}
    >
      <div style={{ fontSize: 38, fontWeight: 800, letterSpacing: -1 }}>
        <span style={{ color: COLORS.brand }}>Rentalmotor</span>
      </div>
      <div
        style={{
          display: 'flex',
          gap: 24,
          marginLeft: 32,
          fontSize: 28,
          fontWeight: 600,
          color: COLORS.inkSoft,
        }}
      >
        <span style={{ color: COLORS.brand }}>Beranda</span>
        <span>Katalog</span>
        <span>Booking</span>
        <span>Bantuan</span>
      </div>
      <div style={{ marginLeft: 'auto', display: 'flex', alignItems: 'center', gap: 14 }}>
        <div
          style={{
            width: 56,
            height: 56,
            borderRadius: '50%',
            background: COLORS.brand,
            color: '#fff',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontSize: 28,
            fontWeight: 800,
          }}
        >
          M
        </div>
        <div style={{ fontSize: 26, fontWeight: 700, color: COLORS.ink }}>Member</div>
      </div>
    </div>
  );
};

// Banner hero mock — top 150, h 620
export const HeroBanner: React.FC<{ frame: number; fps: number }> = ({ frame, fps }) => {
  const pop = enter(frame, fps, 12);
  return (
    <div
      style={{
        position: 'absolute',
        top: 150,
        left: 40,
        right: 40,
        height: 620,
        borderRadius: 44,
        background: `linear-gradient(135deg, ${COLORS.brand} 0%, ${COLORS.brandDark} 80%)`,
        boxShadow: '0 30px 70px rgba(4,10,30,0.5)',
        overflow: 'hidden',
        opacity: pop,
        transform: `scale(${0.95 + pop * 0.05})`,
      }}
    >
      <div
        style={{
          position: 'absolute',
          width: 430,
          height: 430,
          borderRadius: '50%',
          background: 'rgba(255,255,255,0.10)',
          top: -170,
          right: -120,
        }}
      />
      <div
        style={{
          position: 'absolute',
          width: 260,
          height: 260,
          borderRadius: '50%',
          background: 'rgba(255,255,255,0.08)',
          bottom: -110,
          left: -80,
        }}
      />
      <div
        style={{
          position: 'absolute',
          right: 30,
          bottom: 80,
          fontSize: 220,
          transform: 'rotate(-10deg)',
        }}
      >
        🛵
      </div>
      <div style={{ position: 'absolute', left: 56, top: 64, width: 600 }}>
        <div
          style={{
            display: 'inline-block',
            background: 'rgba(255,255,255,0.18)',
            color: '#fff',
            fontSize: 26,
            fontWeight: 700,
            letterSpacing: 4,
            padding: '12px 26px',
            borderRadius: 999,
          }}
        >
          RENTAL MOTOR ONLINE
        </div>
        <div
          style={{
            marginTop: 24,
            fontSize: 68,
            fontWeight: 800,
            color: '#fff',
            lineHeight: 1.12,
            letterSpacing: -1.5,
          }}
        >
          Sewa Motor
          <br />
          Gampang &amp; Cepat
        </div>
        <div
          style={{
            marginTop: 20,
            fontSize: 33,
            color: 'rgba(255,255,255,0.85)',
            fontWeight: 500,
          }}
        >
          Mulai Rp 70.000/hari
        </div>
        <div
          style={{
            marginTop: 34,
            display: 'inline-block',
            background: '#fff',
            color: COLORS.brandDark,
            fontSize: 32,
            fontWeight: 800,
            padding: '20px 40px',
            borderRadius: 999,
          }}
        >
          Mulai Sewa →
        </div>
      </div>
    </div>
  );
};

// Panel pencarian mock — top 740, overlap 30px ke banner
export const SearchPanel: React.FC<{ frame: number; fps: number }> = ({ frame, fps }) => {
  const pop = enter(frame, fps, 22);
  const fields = [
    { label: 'MEREK', value: 'Honda', chev: true, delay: 32 },
    { label: 'TIPE', value: 'Matic', chev: true, delay: 40 },
    { label: 'TANGGAL', value: '17 Nov 2026', chev: false, delay: 48 },
  ];
  return (
    <div
      style={{
        position: 'absolute',
        top: 740,
        left: 60,
        right: 60,
        borderRadius: 40,
        background: COLORS.surface,
        boxShadow: '0 30px 70px rgba(4,10,30,0.55)',
        padding: '40px 40px 44px',
        opacity: pop,
        transform: `translateY(${(1 - pop) * 40}px)`,
      }}
    >
      <div style={{ display: 'flex', gap: 22 }}>
        {fields.map((f) => {
          const p = enter(frame, fps, f.delay);
          return (
            <div
              key={f.label}
              style={{
                flex: 1,
                background: COLORS.surface2,
                borderRadius: 26,
                padding: '22px 26px',
                opacity: p,
                transform: `translateY(${(1 - p) * 20}px)`,
              }}
            >
              <div style={{ fontSize: 22, fontWeight: 700, letterSpacing: 2.5, color: COLORS.muted }}>
                {f.label}
              </div>
              <div
                style={{
                  marginTop: 10,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                }}
              >
                <div style={{ fontSize: 34, fontWeight: 800, color: COLORS.ink }}>{f.value}</div>
                {f.chev ? (
                  <ChevronDown size={28} color={COLORS.muted} />
                ) : (
                  <CalendarIcon size={30} color={COLORS.brand} />
                )}
              </div>
            </div>
          );
        })}
      </div>
      <div
        style={{
          marginTop: 28,
          height: 100,
          borderRadius: 28,
          background: COLORS.brand,
          color: '#fff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          gap: 16,
          fontSize: 36,
          fontWeight: 800,
        }}
      >
        <SearchIcon size={36} color="#ffffff" /> Cari Motor
      </div>
    </div>
  );
};

// Kartu produk mock — foto asli dari assets/uploads/products
export const ProductCard: React.FC<{
  img: string;
  name: string;
  spec: string;
  price: string;
  left: number;
  top: number;
  frame: number;
  fps: number;
  delay?: number;
  floatPhase?: number;
}> = ({ img, name, spec, price, left, top, frame, fps, delay = 0, floatPhase = 0 }) => {
  const pop = enter(frame, fps, delay);
  const float = Math.sin(frame / 26 + floatPhase) * 7;
  return (
    <div
      style={{
        position: 'absolute',
        left,
        top,
        width: 460,
        borderRadius: 36,
        overflow: 'hidden',
        background: COLORS.surface,
        boxShadow: '0 26px 60px rgba(4,10,30,0.5)',
        opacity: pop,
        transform: `translateY(${(1 - pop) * 60 + float}px) scale(${0.92 + pop * 0.08})`,
      }}
    >
      <div style={{ position: 'relative' }}>
        <Img
          src={staticFile(img)}
          style={{ width: '100%', height: 300, objectFit: 'cover', display: 'block' }}
        />
        <div
          style={{
            position: 'absolute',
            top: 22,
            left: 22,
            background: COLORS.green,
            color: '#fff',
            fontSize: 24,
            fontWeight: 800,
            padding: '10px 22px',
            borderRadius: 999,
          }}
        >
          Tersedia
        </div>
      </div>
      <div style={{ padding: '26px 30px 30px' }}>
        <div style={{ fontSize: 36, fontWeight: 800, color: COLORS.ink }}>{name}</div>
        <div style={{ marginTop: 8, fontSize: 26, color: COLORS.muted, fontWeight: 600 }}>{spec}</div>
        <div style={{ marginTop: 14, display: 'flex', alignItems: 'baseline', gap: 8 }}>
          <div style={{ fontSize: 40, fontWeight: 800, color: COLORS.brandDark }}>{price}</div>
          <div style={{ fontSize: 26, color: COLORS.muted, fontWeight: 600 }}>/hari</div>
        </div>
      </div>
    </div>
  );
};

// Form booking mock — field + ringkasan harga + total
export const BookingForm: React.FC<{ frame: number; fps: number }> = ({ frame, fps }) => {
  const pop = enter(frame, fps, 6);
  const fields = [
    { label: 'NAMA PENYEWA', value: 'Rizky Pratama', delay: 18 },
    { label: 'NO. HP', value: '0812 •••• 4455', delay: 26 },
    { label: 'TANGGAL AMBIL', value: '17 Nov 2026', delay: 34 },
    { label: 'DURASI', value: '3 Hari', delay: 42 },
  ];
  const totalPulse = frame > 70 ? 1 + Math.sin((frame - 70) / 7) * 0.025 : 1;
  return (
    <div
      style={{
        position: 'absolute',
        top: 320,
        left: 70,
        right: 70,
        borderRadius: 44,
        background: COLORS.surface,
        boxShadow: '0 30px 70px rgba(4,10,30,0.5)',
        padding: '44px 44px 48px',
        opacity: pop,
        transform: `translateY(${(1 - pop) * 40}px)`,
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
        <div style={{ fontSize: 46, fontWeight: 800, color: COLORS.ink }}>Form Booking</div>
        <div
          style={{
            background: COLORS.brand,
            color: '#fff',
            fontSize: 24,
            fontWeight: 800,
            letterSpacing: 3,
            padding: '12px 26px',
            borderRadius: 999,
          }}
        >
          ONLINE
        </div>
      </div>
      <div style={{ marginTop: 30, display: 'flex', flexWrap: 'wrap', gap: 22 }}>
        {fields.map((f) => {
          const p = enter(frame, fps, f.delay);
          return (
            <div
              key={f.label}
              style={{
                width: 400,
                background: COLORS.surface2,
                borderRadius: 26,
                padding: '22px 28px',
                opacity: p,
                transform: `translateY(${(1 - p) * 20}px)`,
              }}
            >
              <div style={{ fontSize: 22, fontWeight: 700, letterSpacing: 2.5, color: COLORS.muted }}>
                {f.label}
              </div>
              <div style={{ marginTop: 10, fontSize: 34, fontWeight: 800, color: COLORS.ink }}>
                {f.value}
              </div>
            </div>
          );
        })}
      </div>
      <div
        style={{
          marginTop: 30,
          background: 'rgba(116,148,236,0.10)',
          border: '2px solid rgba(116,148,236,0.25)',
          borderRadius: 28,
          padding: '28px 32px',
        }}
      >
        <div style={{ display: 'flex', justifyContent: 'space-between' }}>
          <div style={{ fontSize: 30, fontWeight: 600, color: COLORS.inkSoft }}>
            Yamaha Mio M3 × 3 hari
          </div>
          <div style={{ fontSize: 30, fontWeight: 800, color: COLORS.ink }}>Rp 210.000</div>
        </div>
        <div style={{ marginTop: 18, borderTop: '2px dashed rgba(11,21,48,0.15)' }} />
        <div
          style={{
            marginTop: 18,
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}
        >
          <div style={{ fontSize: 34, fontWeight: 800, color: COLORS.ink }}>Total Bayar</div>
          <div
            style={{
              fontSize: 48,
              fontWeight: 800,
              color: COLORS.brandDark,
              transform: `scale(${totalPulse})`,
            }}
          >
            Rp 210.000
          </div>
        </div>
      </div>
      <div
        style={{
          marginTop: 30,
          height: 100,
          borderRadius: 28,
          background: COLORS.brand,
          color: '#fff',
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          fontSize: 38,
          fontWeight: 800,
        }}
      >
        Bayar &amp; Konfirmasi
      </div>
    </div>
  );
};

// Chat widget mock — asisten AI Rentalmotor
export const ChatWidget: React.FC<{ frame: number; fps: number }> = ({ frame, fps }) => {
  const pop = enter(frame, fps, 4);
  const userPop = enter(frame, fps, 16);
  const aiPop = enter(frame, fps, 44);
  const typingPop = enter(frame, fps, 72);
  const dotOps = [0, 1, 2].map(
    (i) => 0.35 + 0.65 * Math.abs(Math.sin(frame / 6 - i * 0.9)),
  );
  return (
    <div
      style={{
        position: 'absolute',
        top: 330,
        left: 60,
        right: 60,
        borderRadius: 44,
        background: COLORS.surface,
        boxShadow: '0 30px 70px rgba(4,10,30,0.5)',
        padding: '38px 38px 40px',
        opacity: pop,
        transform: `translateY(${(1 - pop) * 40}px)`,
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', gap: 22 }}>
        <div
          style={{
            width: 84,
            height: 84,
            borderRadius: '50%',
            background: COLORS.brand,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <ChatIcon size={40} color="#ffffff" />
        </div>
        <div>
          <div style={{ fontSize: 42, fontWeight: 800, color: COLORS.ink }}>Rentalmotor AI</div>
          <div style={{ marginTop: 6, display: 'flex', alignItems: 'center', gap: 10 }}>
            <div style={{ width: 16, height: 16, borderRadius: '50%', background: COLORS.green }} />
            <div style={{ fontSize: 26, color: COLORS.muted, fontWeight: 600 }}>
              Online · balas cepat
            </div>
          </div>
        </div>
      </div>
      <div style={{ marginTop: 28, borderTop: `2px solid ${COLORS.line}` }} />
      <div style={{ marginTop: 30, display: 'flex', flexDirection: 'column', gap: 24 }}>
        <div
          style={{
            alignSelf: 'flex-end',
            maxWidth: 640,
            background: COLORS.brand,
            color: '#fff',
            borderRadius: '30px 30px 8px 30px',
            padding: '24px 32px',
            fontSize: 32,
            fontWeight: 600,
            opacity: userPop,
            transform: `translateY(${(1 - userPop) * 24}px)`,
          }}
        >
          Lokasi toko di mana, kak?
        </div>
        <div
          style={{
            alignSelf: 'flex-start',
            maxWidth: 760,
            background: COLORS.surface2,
            color: COLORS.ink,
            borderRadius: '30px 30px 30px 8px',
            padding: '24px 32px',
            fontSize: 32,
            fontWeight: 500,
            lineHeight: 1.4,
            opacity: aiPop,
            transform: `translateY(${(1 - aiPop) * 24}px)`,
          }}
        >
          Toko Rentalmotor buka tiap hari 05.00–23.00 WIB. Bisa ambil motor langsung di tempat 📍
        </div>
        <div
          style={{
            alignSelf: 'flex-start',
            background: COLORS.surface2,
            borderRadius: 30,
            padding: '22px 30px',
            display: 'flex',
            gap: 10,
            opacity: typingPop,
          }}
        >
          {dotOps.map((op, i) => (
            <div
              key={i}
              style={{
                width: 16,
                height: 16,
                borderRadius: '50%',
                background: COLORS.brand,
                opacity: op,
              }}
            />
          ))}
        </div>
      </div>
      <div
        style={{
          marginTop: 34,
          height: 96,
          borderRadius: 999,
          background: COLORS.surface2,
          display: 'flex',
          alignItems: 'center',
          padding: '0 18px 0 36px',
        }}
      >
        <div style={{ flex: 1, fontSize: 30, color: COLORS.muted, fontWeight: 600 }}>
          Tanya apa saja…
        </div>
        <div
          style={{
            width: 72,
            height: 72,
            borderRadius: '50%',
            background: COLORS.brand,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <SendIcon size={32} color="#ffffff" />
        </div>
      </div>
    </div>
  );
};
