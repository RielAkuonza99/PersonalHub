import type { Metadata } from "next";
import { ProjectManager } from "@/components/dashboard/ProjectManager";
import { getAdminProjects } from "@/lib/data/admin-projects";

export const metadata: Metadata = { title: "Projects" };

export default async function DashboardProjectsPage() {
  const projects = await getAdminProjects();

  return (
    <>
      <h2 className="font-display text-3xl">Projects</h2>
      <p className="mt-2 text-sm text-white/60">Create, edit, publish, and organize portfolio projects.</p>
      <ProjectManager initialProjects={projects} />
    </>
  );
}
