import { getSkills } from "@/lib/data";
import { Squiggle } from "@/components/ui/Doodles";

export async function Skills() {
  const skills = await getSkills();
  return (
    <section className="mx-auto mt-5 max-w-6xl px-4 sm:px-6">
      <div className="panel flex flex-col gap-6 p-6 md:flex-row md:items-center md:gap-10 md:p-8">
        <h2 className="font-display text-3xl underline decoration-volt decoration-4 underline-offset-8">SKILLS</h2>
        <ul className="grid flex-1 grid-cols-3 gap-5 sm:grid-cols-6">
          {skills.map((s) => (
            <li key={s.id} className="flex flex-col items-center gap-2 text-center">
              <span
                className="grid size-14 place-items-center rounded-xl border-2 text-lg font-bold"
                style={{ borderColor: s.color, color: s.color, background: `${s.color}1a` }}
              >
                {s.short}
              </span>
              <span className="text-[11px] leading-tight text-white/70">{s.label}</span>
            </li>
          ))}
        </ul>
        <Squiggle className="hidden w-24 text-volt lg:block" />
      </div>
    </section>
  );
}
