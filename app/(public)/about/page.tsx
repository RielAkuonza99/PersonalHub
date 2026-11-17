import type { Metadata } from "next";
import { PageShell } from "@/components/layout/PageShell";

export const metadata: Metadata = { title: "About" };

export default function Page() {
  return (
    <PageShell title="About" description="A bit about who I am and how I work.">
      <div className="panel max-w-2xl p-6 text-sm text-white/70">About content will be loaded from the profile table.</div>
    </PageShell>
  );
}
