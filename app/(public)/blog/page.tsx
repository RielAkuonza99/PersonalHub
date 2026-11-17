import type { Metadata } from "next";
import { PageShell } from "@/components/layout/PageShell";

export const metadata: Metadata = { title: "Blog" };

export default function Page() {
  return (
    <PageShell title="Blog" description="Notes and write-ups.">
      <div className="panel max-w-2xl p-6 text-sm text-white/70">No posts yet.</div>
    </PageShell>
  );
}
