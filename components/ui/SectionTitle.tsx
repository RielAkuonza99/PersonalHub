export function SectionTitle({ children, className = "" }: { children: React.ReactNode; className?: string }) {
  return <h2 className={`font-display text-3xl leading-none sm:text-4xl ${className}`}>{children}</h2>;
}
