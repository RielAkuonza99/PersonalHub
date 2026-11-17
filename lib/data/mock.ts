import type { Profile, Project, Skill, Technology, Video } from "@/types";

export const profile: Profile = {
  name: "Riel",
  greeting: "Hey,",
  roles: ["Web Developer", "Bot Builder", "App Maker"],
  tagline: "Let's create something awesome!",
  bio: "I'm Riel, an independent developer who builds web apps, WhatsApp bots and Android apps. I like turning rough ideas into playful, useful products that people enjoy using.",
  email: "hello@personalhub.my.id",
  location: "Indonesia",
  mantra: ["Focus", "Discipline", "Consistency"],
  socials: [
    { label: "Instagram", href: "#", icon: "instagram" },
    { label: "Dribbble", href: "#", icon: "dribbble" },
    { label: "YouTube", href: "#", icon: "youtube" },
    { label: "LinkedIn", href: "#", icon: "linkedin" },
  ],
};

export const projects: Project[] = [
  { id: "1", slug: "run", title: "RUN!", category: "Web App", accent: "from-yellow-300 to-amber-400", href: "/projects" },
  { id: "2", slug: "daydreams", title: "DAYDREAMS", category: "Android App", accent: "from-orange-500 to-red-500", href: "/projects" },
  { id: "3", slug: "lost-signal", title: "LOST SIGNAL", category: "WhatsApp Bot", accent: "from-violet-700 to-fuchsia-600", href: "/projects" },
  { id: "4", slug: "groovy-tunes", title: "GROOVY TUNES", category: "Web Platform", accent: "from-teal-400 to-emerald-500", href: "/projects" },
];

export const skills: Skill[] = [
  { id: "next", label: "Next.js", short: "Nx", color: "#e8e8f0" },
  { id: "react", label: "React", short: "Re", color: "#4fd8ff" },
  { id: "ts", label: "TypeScript", short: "Ts", color: "#3b82f6" },
  { id: "tw", label: "Tailwind CSS", short: "Tw", color: "#2dd4bf" },
  { id: "supabase", label: "Supabase", short: "Sb", color: "#3ecf8e" },
  { id: "pg", label: "PostgreSQL", short: "Pg", color: "#8b9dff" },
];

export const technologies: Technology[] = skills.map((s) => ({ ...s, description: "Used across current and upcoming projects." }));

export const videos: Video[] = [
  { id: "v1", title: "Showreel", description: "A quick glimpse of my work and my love for bringing stories to life." },
];
