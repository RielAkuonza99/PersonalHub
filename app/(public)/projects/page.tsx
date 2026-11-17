import type { Metadata } from "next";
import { PageShell } from "@/components/layout/PageShell";
import { ProjectCard } from "@/components/projects/ProjectCard";
import { getProjects } from "@/lib/data";

export const metadata: Metadata = { title: "Projects" };

export default async function Page() {
  return (
    <PageShell title="Projects" description="Everything I've built, from web apps to bots.">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">{(await getProjects()).map((p) => <ProjectCard key={p.id} project={p} />)}</div>
    </PageShell>
  );
}
