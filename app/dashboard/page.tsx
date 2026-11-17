import { getProjects, getSkills, getVideos } from "@/lib/data";

export default async function DashboardPage() {
  const [projects, videos, skills] = await Promise.all([getProjects(), getVideos(), getSkills()]);
  const stats = [
    { label: "Projects", value: projects.length },
    { label: "Videos", value: videos.length },
    { label: "Technologies", value: skills.length },
    { label: "Blog posts", value: 0 },
  ];
  return (
    <>
      <h2 className="font-display text-3xl">Overview</h2>
      <ul className="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        {stats.map((s) => (
          <li key={s.label} className="panel p-5">
            <p className="text-3xl font-semibold text-lime">{s.value}</p>
            <p className="mt-1 text-sm text-white/60">{s.label}</p>
          </li>
        ))}
      </ul>
      <p className="panel mt-6 p-5 text-sm text-white/60">
        Overview counts still use local mock data. Project management is connected to Supabase; the remaining content modules will be added incrementally.
      </p>
    </>
  );
}
