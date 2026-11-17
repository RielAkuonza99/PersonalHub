import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { SectionTitle } from "@/components/ui/SectionTitle";
import { Squiggle } from "@/components/ui/Doodles";
import { ProjectCard } from "@/components/projects/ProjectCard";
import { getProjects } from "@/lib/data";

export async function FeaturedProjects() {
  const projects = await getProjects(4);
  return (
    <section className="mx-auto mt-5 max-w-6xl px-4 sm:px-6">
      <div className="panel p-6 md:p-8">
        <div className="flex items-center justify-between">
          <SectionTitle className="flex items-center gap-3">
            FEATURED PROJECTS <Squiggle className="hidden w-12 text-volt sm:block" />
          </SectionTitle>
          <Link href="/projects" className="inline-flex items-center gap-2 text-sm font-medium text-lime">
            View All Projects <ArrowRight size={16} />
          </Link>
        </div>
        <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {projects.map((p) => <ProjectCard key={p.id} project={p} />)}
        </div>
      </div>
    </section>
  );
}
