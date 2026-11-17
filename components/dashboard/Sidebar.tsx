import Link from "next/link";
import { dashboardNav } from "./nav";

export function Sidebar() {
  return (
    <aside className="hidden w-60 shrink-0 border-r border-white/5 bg-panel p-5 md:block">
      <Link href="/" className="font-display text-2xl">PersonalHub<span className="text-lime">.</span></Link>
      <nav aria-label="Dashboard" className="mt-8 space-y-1">
        {dashboardNav.map(({ label, href, icon: Icon }) => (
          <Link key={label} href={href} className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/70 hover:bg-white/5 hover:text-white">
            <Icon size={17} /> {label}
          </Link>
        ))}
      </nav>
    </aside>
  );
}
