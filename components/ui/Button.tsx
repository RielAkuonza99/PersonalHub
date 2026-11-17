import Link from "next/link";
import type { ReactNode } from "react";

type Props = { href: string; variant?: "lime" | "ghost"; children: ReactNode; className?: string };

export function ButtonLink({ href, variant = "lime", children, className = "" }: Props) {
  return (
    <Link href={href} className={`${variant === "lime" ? "btn-lime" : "btn-ghost"} ${className}`}>
      {children}
    </Link>
  );
}
