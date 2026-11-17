import type { Metadata } from "next";
import Link from "next/link";
import { LoginForm } from "@/components/dashboard/LoginForm";

export const metadata: Metadata = {
  title: "Administrator login",
  robots: { index: false, follow: false },
};

type LoginPageProps = {
  searchParams: Promise<{ reason?: string }>;
};

export default async function LoginPage({ searchParams }: LoginPageProps) {
  const { reason } = await searchParams;
  const configured = Boolean(
    process.env.NEXT_PUBLIC_SUPABASE_URL && process.env.NEXT_PUBLIC_SUPABASE_ANON_KEY,
  );

  return (
    <main className="flex min-h-screen items-center justify-center px-5 py-12">
      <section className="panel w-full max-w-md p-7 sm:p-9">
        <Link href="/" className="font-display text-2xl">
          PersonalHub<span className="text-lime">.</span>
        </Link>
        <h1 className="mt-8 font-display text-3xl">Administrator login</h1>
        <p className="mt-2 text-sm text-white/60">
          Sign in with an account that has administrator access.
        </p>
        <LoginForm configured={configured} reason={reason} />
      </section>
    </main>
  );
}
