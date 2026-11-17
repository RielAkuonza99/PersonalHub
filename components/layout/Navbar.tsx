"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useState } from "react";
import { Menu, X } from "lucide-react";
import { navLinks } from "./nav-links";

export function Navbar() {
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  return (
    <header className="absolute inset-x-0 top-0 z-30">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-5 sm:px-8">
        <Link href="/" className="font-display text-2xl tracking-tight">
          PersonalHub<span className="text-lime">.</span>
        </Link>

        <nav aria-label="Main" className="hidden items-center gap-8 text-sm font-medium md:flex">
          {navLinks.map((l) => (
            <Link key={l.href} href={l.href} className={pathname === l.href ? "text-lime" : "text-white hover:text-lime"}>
              {l.label}
            </Link>
          ))}
          <button aria-label="Open menu" className="grid size-11 place-items-center rounded-full bg-volt" onClick={() => setOpen(true)}>
            <Menu size={20} />
          </button>
        </nav>

        <button aria-label="Open menu" className="grid size-11 place-items-center rounded-full bg-volt md:hidden" onClick={() => setOpen(true)}>
          <Menu size={20} />
        </button>
      </div>

      {open && (
        <div role="dialog" aria-modal="true" aria-label="Menu" className="fixed inset-0 z-40 bg-ink/95 p-6 backdrop-blur">
          <div className="flex justify-end">
            <button aria-label="Close menu" className="grid size-11 place-items-center rounded-full bg-volt" onClick={() => setOpen(false)}>
              <X size={20} />
            </button>
          </div>
          <ul className="mt-10 space-y-6 font-display text-4xl">
            {[...navLinks, { label: "Videos", href: "/videos" }, { label: "Technologies", href: "/technologies" }].map((l) => (
              <li key={l.href}>
                <Link href={l.href} onClick={() => setOpen(false)} className={pathname === l.href ? "text-lime" : ""}>
                  {l.label}
                </Link>
              </li>
            ))}
          </ul>
        </div>
      )}
    </header>
  );
}
