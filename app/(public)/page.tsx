import { Hero } from "@/components/home/Hero";
import { Showreel } from "@/components/home/Showreel";
import { FeaturedProjects } from "@/components/home/FeaturedProjects";
import { Skills } from "@/components/home/Skills";
import { AboutMe } from "@/components/home/AboutMe";

export default function HomePage() {
  return (
    <>
      <Hero />
      <Showreel />
      <FeaturedProjects />
      <Skills />
      <AboutMe />
    </>
  );
}
