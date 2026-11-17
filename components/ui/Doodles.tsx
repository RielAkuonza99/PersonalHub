type P = { className?: string };
const base = { fill: "none", strokeWidth: 3, strokeLinecap: "round", strokeLinejoin: "round" } as const;

export const Squiggle = ({ className }: P) => (
  <svg viewBox="0 0 80 30" className={className} aria-hidden="true"><path {...base} stroke="currentColor" d="M3 20c8-16 14 12 22-2s14 10 22-4 14 8 28-6" /></svg>
);
export const Bolt = ({ className }: P) => (
  <svg viewBox="0 0 40 60" className={className} aria-hidden="true"><path d="M24 2 4 34h14l-4 24 22-34H22z" fill="currentColor" /></svg>
);
export const Star = ({ className }: P) => (
  <svg viewBox="0 0 40 40" className={className} aria-hidden="true"><path d="m20 3 5 12 13 1-10 9 3 13-11-7-11 7 3-13L2 16l13-1z" fill="currentColor" /></svg>
);
export const Crown = ({ className }: P) => (
  <svg viewBox="0 0 50 40" className={className} aria-hidden="true"><path {...base} stroke="currentColor" d="M5 34 8 10l12 12L26 5l6 17 12-12 3 24z" /></svg>
);
export const Smiley = ({ className }: P) => (
  <svg viewBox="0 0 50 50" className={className} aria-hidden="true"><g {...base} stroke="currentColor"><circle cx="25" cy="25" r="19" /><path d="M17 20v3M33 20v3M15 30c6 8 14 8 20 0" /></g></svg>
);
export const Arrow = ({ className }: P) => (
  <svg viewBox="0 0 70 70" className={className} aria-hidden="true"><path {...base} stroke="currentColor" d="M8 8c-4 30 14 48 50 50M44 44l14 14-18 6" /></svg>
);
export const Blob = ({ className }: P) => (
  <svg viewBox="0 0 200 200" className={className} aria-hidden="true"><path fill="currentColor" d="M41 8c34-14 74 4 98 34s14 56 8 86-44 62-80 54S-4 138 6 96 8 22 41 8z" /></svg>
);
