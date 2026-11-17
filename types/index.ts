export type Profile = {
  name: string;
  greeting: string;
  roles: string[];
  tagline: string;
  bio: string;
  email: string;
  location: string;
  mantra: string[];
  socials: { label: string; href: string; icon: "instagram" | "dribbble" | "youtube" | "linkedin" | "github" }[];
};

export type Project = {
  id: string;
  slug: string;
  title: string;
  category: string;
  /** Tailwind gradient classes used until a real cover image exists */
  accent: string;
  coverUrl?: string;
  href: string;
};

export type Skill = { id: string; label: string; short: string; color: string };
export type Technology = Skill & { description: string };

export type Video = {
  id: string;
  title: string;
  description: string;
  thumbnailUrl?: string;
  videoUrl?: string;
};
