import Link from "next/link";
import { ArrowUpRight } from "lucide-react";
import type { Project } from "@/types";

export function ProjectCard({ project }: { project: Project }) {
  return (
    <Link href={project.href} className="block rounded-2xl bg-panel2 p-2.5 transition-transform hover:-translate-y-1">
      <div className={`relative aspect-[16/10] overflow-hidden rounded-xl bg-gradient-to-br ${project.accent}`}>
        {project.coverUrl && (
          // eslint-disable-next-line @next/next/no-img-element
          <img src={project.coverUrl} alt="" className="absolute inset-0 size-full object-cover" />
        )}
        <div aria-hidden="true" className="absolute -bottom-6 -right-6 size-24 rounded-full bg-black/20" />
        <div aria-hidden="true" className="absolute left-4 top-4 size-10 rounded-full border-4 border-black/30" />
      </div>
      <div className="flex items-end justify-between px-1.5 pb-1 pt-3">
        <div>
          <h3 className="text-xs font-semibold tracking-wide">{project.title}</h3>
          <p className="mt-0.5 text-[11px] text-white/50">{project.category}</p>
        </div>
        <ArrowUpRight size={14} />
      </div>
    </Link>
  );
}
