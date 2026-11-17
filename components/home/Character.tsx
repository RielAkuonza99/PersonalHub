/** Placeholder illustration in the style of the reference. Replace with final artwork in /public/images. */
export function Character({ className = "" }: { className?: string }) {
  return (
    <svg viewBox="0 0 360 420" className={className} role="img" aria-label="Illustrated character waving">
      {/* body / hoodie */}
      <path d="M70 420c-10-90 20-170 90-190l70 4c60 20 100 90 110 186z" fill="#6c2bff" />
      <path d="M150 232c10 40 60 40 74 0l-6-24h-62z" fill="#ffd9b3" />
      <path d="M160 228c8 30 48 30 58 0" fill="none" stroke="#c8ff1f" strokeWidth="3" />
      {/* waving arm */}
      <path d="M95 300C60 260 40 210 50 160" stroke="#6c2bff" strokeWidth="46" strokeLinecap="round" fill="none" />
      <path d="M32 150c-6-30 4-60 14-62 8 20 8 36 6 50m4-60c4-26 16-36 22-32 2 16-2 38-6 58m10-50c8-18 20-22 24-16-2 18-8 34-14 52M30 140c-6-8-20-4-14 10l24 40c14 20 44 14 46-12l2-24z" fill="#ffc48a" stroke="#08080d" strokeWidth="4" strokeLinejoin="round" />
      {/* head */}
      <ellipse cx="195" cy="140" rx="68" ry="74" fill="#ffd9b3" stroke="#08080d" strokeWidth="4" />
      {/* hair */}
      <path d="M118 128c-14-50 20-100 80-100s96 40 82 100c-10-30-30-44-60-48-26 10-60 22-102 48z" fill="#08080d" />
      <circle cx="150" cy="40" r="22" fill="#08080d" /><circle cx="205" cy="22" r="26" fill="#08080d" /><circle cx="255" cy="50" r="22" fill="#08080d" />
      {/* face */}
      <path d="M152 140q12-12 24 0" stroke="#08080d" strokeWidth="5" fill="none" strokeLinecap="round" />
      <circle cx="226" cy="138" r="14" fill="#fff" stroke="#08080d" strokeWidth="4" /><circle cx="228" cy="140" r="6" fill="#08080d" />
      <path d="M160 170q38 44 80 6z" fill="#7a1d1d" stroke="#08080d" strokeWidth="4" strokeLinejoin="round" />
      <path d="M170 176q28 12 62 2-4 14-30 16-22 0-32-18z" fill="#fff" />
      <circle cx="262" cy="150" r="6" fill="#08080d" />
      {/* clapperboard */}
      <g transform="rotate(-14 280 360)">
        <rect x="230" y="340" width="110" height="70" rx="6" fill="#08080d" stroke="#fff" strokeWidth="3" />
        <path d="M230 316h110v24H230z" fill="#fff" />
        <path d="M244 316l-12 24m34-24-12 24m34-24-12 24m34-24-12 24m34-24-12 24" stroke="#08080d" strokeWidth="8" />
        <path d="m270 360 28 16-28 16z" fill="#fff" />
      </g>
    </svg>
  );
}
