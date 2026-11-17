import type { Metadata } from "next";
import { PageShell } from "@/components/layout/PageShell";
import { getTechnologies } from "@/lib/data";

export const metadata: Metadata = { title: "Technologies" };

export default async function Page() {
  return (
    <PageShell title="Technologies" description="The tools I use to build things.">
      <ul className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">{(await getTechnologies()).map((t) => <li key={t.id} className="panel p-4 text-center text-sm font-medium" style={{ color: t.color }}>{t.label}</li>)}</ul>
    </PageShell>
  );
}
