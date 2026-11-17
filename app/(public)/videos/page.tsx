import type { Metadata } from "next";
import { PageShell } from "@/components/layout/PageShell";

export const metadata: Metadata = { title: "Videos" };

export default function Page() {
  return (
    <PageShell title="Videos" description="Showreel and video work.">
      <div className="panel max-w-2xl p-6 text-sm text-white/70">Video library coming soon.</div>
    </PageShell>
  );
}
