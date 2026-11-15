import React from 'react';

type IconProps = { size?: number; color?: string; width?: number };

const svgProps = (size: number, color: string, strokeWidth: number) => ({
  width: size,
  height: size,
  viewBox: '0 0 24 24',
  fill: 'none' as const,
  stroke: color,
  strokeWidth,
  strokeLinecap: 'round' as const,
  strokeLinejoin: 'round' as const,
});

export const SearchIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <circle cx="11" cy="11" r="7" />
    <path d="M20.4 20.4 16.6 16.6" />
  </svg>
);

export const FilterIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="M4 5h16l-6.2 7.2V19l-3.6-2v-4.8Z" />
  </svg>
);

export const CalendarIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <rect x="3.5" y="5" width="17" height="16" rx="3" />
    <path d="M3.5 10h17M8 3v4M16 3v4" />
  </svg>
);

export const ShieldIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="M12 3 5 5.8v5.4c0 4.4 3 7.6 7 9.8 4-2.2 7-5.4 7-9.8V5.8Z" />
    <path d="m9.2 12 2 2 3.6-4" />
  </svg>
);

export const ChatIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5c-1.5 0-3-.4-4.2-1L3 20l1-4.3A8.5 8.5 0 1 1 21 11.5Z" />
  </svg>
);

export const SendIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="M21.5 2.5 2.8 9.8l7.4 2.4 2.4 7.4 8.9-17.1Z" />
    <path d="M21.5 2.5 10.2 12.2" />
  </svg>
);

export const MapPinIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="M12 21s7-6.1 7-11a7 7 0 1 0-14 0c0 4.9 7 11 7 11Z" />
    <circle cx="12" cy="10" r="2.6" />
  </svg>
);

export const BoltIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.2 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="m13 2-9 12h7l-1 8 9-12h-7V2Z" />
  </svg>
);

export const CheckIcon: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.6 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="m4.5 12.5 5 5 10-11" />
  </svg>
);

export const ChevronDown: React.FC<IconProps> = ({ size = 24, color = '#0b1530', width = 2.4 }) => (
  <svg {...svgProps(size, color, width)}>
    <path d="m6 9 6 6 6-6" />
  </svg>
);

export const StarIcon: React.FC<{ size?: number; color?: string }> = ({
  size = 24,
  color = '#ffd166',
}) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill={color}>
    <path d="m12 2.5 2.9 6 6.6.9-4.8 4.6 1.2 6.5-5.9-3.1-5.9 3.1 1.2-6.5L2.5 9.4l6.6-.9Z" />
  </svg>
);
