import Link from "next/link";
import { signOut } from "@/app/dashboard/actions";
import { dashboardNav } from "./nav";

export function Header() {
  return (
    <header className="border-b border-white/5">
      <div className="flex items-center justify-between px-5 py-4">
        <h1 className="font-display text-xl">Dashboard</h1>
        <div className="flex items-center gap-5">
          <Link href="/" className="text-sm text-lime">View site</Link>
          <form action={signOut}>
            <button className="text-sm text-white/70 hover:text-white" type="submit">Sign out</button>
          </form>
        </div>
      </div>
      <nav aria-label="Dashboard sections" className="flex gap-2 overflow-x-auto px-5 pb-3 md:hidden">
        {dashboardNav.map((n) => (
          <Link key={n.label} href={n.href} className="shrink-0 rounded-full bg-panel2 px-3 py-1.5 text-xs">{n.label}</Link>
        ))}
      </nav>
    </header>
  );
}
