import Link from "next/link";
import { ArrowRight } from "lucide-react";
import { getProfile } from "@/lib/data";
import { Crown } from "@/components/ui/Doodles";

export async function AboutMe() {
  const p = await getProfile();
  return (
    <section className="mx-auto mt-5 max-w-6xl px-4 sm:px-6">
      <div className="panel grid items-center gap-6 p-4 sm:grid-cols-[0.9fr_1.4fr] sm:p-5">
        <div className="relative aspect-[4/3] overflow-hidden rounded-2xl bg-lime p-5 text-ink">
          <p className="relative z-10 -rotate-6 font-display text-xl leading-tight">
            {p.mantra.map((m) => <span key={m} className="block">{m}</span>)}
          </p>
          <Crown className="absolute right-6 top-4 size-9 text-ink" />
          <div aria-hidden="true" className="absolute -bottom-10 right-4 size-40 rounded-full bg-ink" />
          <div aria-hidden="true" className="absolute bottom-0 right-12 size-20 rounded-full bg-[#ffd9b3]" />
        </div>
        <div className="sm:pr-6">
          <h2 className="font-display text-2xl underline decoration-volt decoration-4 underline-offset-8">ABOUT ME</h2>
          <p className="mt-4 max-w-md text-sm leading-relaxed text-white/70">{p.bio}</p>
          <Link href="/about" className="mt-4 inline-flex items-center gap-2 text-sm font-medium text-lime">
            Know More About Me <ArrowRight size={14} className="rounded-full border border-lime p-0.5" />
          </Link>
        </div>
      </div>
    </section>
  );
}
