import { Play, ArrowUpRight, Mouse } from "lucide-react";
import { getProfile } from "@/lib/data";
import { ButtonLink } from "@/components/ui/Button";
import { Arrow, Blob, Bolt, Crown, Smiley, Squiggle, Star } from "@/components/ui/Doodles";
import { Character } from "./Character";

export async function Hero() {
  const p = await getProfile();
  return (
    <section className="relative overflow-hidden pt-28 sm:pt-32 lg:pb-0">
      {/* blobs */}
      <Blob className="absolute -left-24 -top-24 size-72 text-volt" />
      <Blob className="absolute -left-20 bottom-4 size-56 text-sky" />
      <Blob className="absolute -right-20 top-24 size-64 text-flame/90" />

      <div className="relative mx-auto grid max-w-6xl items-end gap-6 px-5 sm:px-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div className="relative z-10 pb-10 lg:pb-24">
          <p className="font-hand text-3xl">
            {p.greeting} <Arrow className="inline size-10 -rotate-12" />
          </p>
          <h1 className="relative mt-1 font-display text-[22vw] leading-[0.85] sm:text-8xl lg:text-[9.5rem]">
            <span className="mr-3 align-top text-[0.45em]">I&apos;m</span>
            {p.name.toUpperCase()}
            <Crown className="absolute -top-2 right-[8%] hidden size-12 text-lime sm:block" />
            <svg viewBox="0 0 400 24" className="mt-1 h-4 w-[85%] text-lime" aria-hidden="true">
              <path d="M2 16C90 4 220 2 398 8" stroke="currentColor" strokeWidth="12" strokeLinecap="round" fill="none" />
            </svg>
          </h1>
          <p className="mt-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-base sm:text-lg">
            {p.roles.map((r, i) => (
              <span key={r} className="flex items-center gap-3">
                {i > 0 && <span className="size-1.5 rounded-full bg-lime" aria-hidden="true" />}
                {r}
              </span>
            ))}
          </p>
          <div className="mt-8 flex flex-wrap gap-4">
            <ButtonLink href="/videos"><Play size={16} fill="currentColor" /> Watch Showreel</ButtonLink>
            <ButtonLink href="/projects" variant="ghost">View Projects <ArrowUpRight size={16} /></ButtonLink>
          </div>
          <div className="mt-10 hidden items-center gap-3 text-xs text-white/70 lg:flex">
            <Mouse size={22} /> Scroll Down
          </div>
        </div>

        <div className="relative mx-auto w-full max-w-md lg:max-w-none">
          <div className="absolute left-2 top-0 z-10 max-w-[11rem] lg:left-0 rotate-[-8deg] rounded-3xl rounded-bl-sm bg-sky px-4 py-3 text-center font-hand text-xl uppercase leading-tight">
            {p.tagline}
          </div>
          <Star className="absolute right-6 top-0 size-10 text-yellow-400" />
          <Bolt className="absolute -bottom-2 left-6 size-10 text-lime" />
          <Smiley className="absolute bottom-6 left-0 size-14 text-lime" />
          <Squiggle className="absolute right-0 top-16 w-20 text-sky" />
          <Character className="relative mx-auto mt-14 block w-[70%] lg:ml-auto lg:mr-0 lg:w-[78%]" />
        </div>
      </div>
    </section>
  );
}
