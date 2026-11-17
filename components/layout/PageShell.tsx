export function PageShell({ title, description, children }: { title: string; description: string; children?: React.ReactNode }) {
  return (
    <section className="mx-auto max-w-6xl px-5 pb-10 pt-32 sm:px-8">
      <h1 className="font-display text-5xl sm:text-6xl">{title}</h1>
      <p className="mt-4 max-w-xl text-white/70">{description}</p>
      <div className="mt-10">{children}</div>
    </section>
  );
}
