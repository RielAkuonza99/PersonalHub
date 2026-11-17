import type { Metadata } from "next";
import { PageShell } from "@/components/layout/PageShell";
import { getProfile } from "@/lib/data";

export const metadata: Metadata = { title: "Contact" };

export default async function Page() {
  return (
    <PageShell title="Contact" description="Say hello. Contact form is not wired up yet.">
      <p className="text-lg text-lime">{(await getProfile()).email}</p>
    </PageShell>
  );
}
