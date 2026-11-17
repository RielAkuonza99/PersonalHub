import Link from "next/link";
import { Play } from "lucide-react";
import { SectionTitle } from "@/components/ui/SectionTitle";
import { getProfile, getVideos } from "@/lib/data";

export async function Showreel() {
  const [p, videos] = await Promise.all([getProfile(), getVideos()]);
  const reel = videos[0];
  return (
    <section className="mx-auto max-w-6xl px-4 sm:px-6">
      <div className="panel grid items-center gap-8 p-6 md:grid-cols-[0.8fr_2fr] md:p-8">
        <div>
          <SectionTitle>
            <span className="mr-2 text-volt" aria-hidden="true">✦</span>SHOWREEL
          </SectionTitle>
          <p className="mt-5 max-w-xs text-sm leading-relaxed text-white/70">{reel?.description ?? p.tagline}</p>
          <Link href="/videos" className="mt-6 inline-flex items-center gap-2 text-sm font-medium text-lime">
            Watch Now <Play size={14} className="rounded-full border border-lime p-0.5" />
          </Link>
        </div>
        <Link href="/videos" aria-label="Watch showreel" className="group relative block aspect-[16/7] overflow-hidden rounded-2xl bg-gradient-to-b from-violet-900 via-fuchsia-900 to-indigo-950">
          <div className="absolute right-[18%] top-[18%] size-24 rounded-full bg-rose-500 sm:size-32" />
          <div className="absolute inset-x-0 bottom-0 flex items-end justify-around px-4 opacity-70" aria-hidden="true">
            {[40, 70, 50, 90, 60, 80, 45, 65].map((h, i) => (
              <div key={i} className="w-[8%] bg-indigo-950" style={{ height: `${h}%`, maxHeight: "60%" }} />
            ))}
          </div>
          <span className="absolute left-1/2 top-1/2 grid size-16 -translate-x-1/2 -translate-y-1/2 place-items-center rounded-full bg-lime text-ink transition-transform group-hover:scale-105">
            <Play fill="currentColor" size={24} />
          </span>
        </Link>
      </div>
    </section>
  );
}
