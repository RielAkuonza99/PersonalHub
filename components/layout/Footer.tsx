import { Dribbble, Github, Instagram, Linkedin, Mail, MapPin, Youtube } from "lucide-react";
import { getProfile } from "@/lib/data";

const icons = { instagram: Instagram, dribbble: Dribbble, youtube: Youtube, linkedin: Linkedin, github: Github };

export async function Footer() {
  const p = await getProfile();
  return (
    <footer className="mx-auto max-w-6xl px-4 pb-10 sm:px-6">
      <div className="panel relative flex flex-col gap-8 overflow-hidden p-8 md:flex-row md:items-center md:gap-12">
        <p className="font-hand text-4xl leading-tight sm:text-5xl">
          Let&apos;s make <br />
          something{" "}
          <span className="inline-block rotate-[-3deg] rounded-full border-2 border-lime px-3 text-lime">dope!</span>
        </p>
        <div className="hidden h-16 w-px bg-white/10 md:block" />
        <ul className="space-y-3 text-sm">
          <li className="flex items-center gap-3"><Mail size={18} /> {p.email}</li>
          <li className="flex items-center gap-3"><MapPin size={18} /> {p.location}</li>
        </ul>
        <ul className="flex gap-3 md:ml-auto md:mr-16">
          {p.socials.map((s) => {
            const Icon = icons[s.icon];
            return (
              <li key={s.label}>
                <a href={s.href} aria-label={s.label} className="grid size-10 place-items-center rounded-full border border-white/30 hover:border-lime hover:text-lime">
                  <Icon size={17} />
                </a>
              </li>
            );
          })}
        </ul>
        <div aria-hidden="true" className="absolute -bottom-10 -right-6 size-24 rounded-full bg-sky/90" />
      </div>
    </footer>
  );
}
