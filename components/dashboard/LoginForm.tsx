"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { createClient } from "@/lib/supabase/client";

type LoginFormProps = {
  configured: boolean;
  reason?: string;
};

export function LoginForm({ configured, reason }: LoginFormProps) {
  const router = useRouter();
  const [message, setMessage] = useState(
    !configured
      ? "Supabase is not configured. Add the public Supabase URL and anon key to your environment."
      : reason === "not-admin"
        ? "This account does not have administrator access."
        : reason === "auth-unavailable" || reason === "authorization-unavailable"
          ? "Unable to verify your administrator session. Please try again."
        : "",
  );
  const [pending, setPending] = useState(false);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setMessage("");

    if (!configured) {
      setMessage("Supabase is not configured. Add the public Supabase URL and anon key to your environment.");
      return;
    }

    const formData = new FormData(event.currentTarget);
    const email = String(formData.get("email") ?? "").trim();
    const password = String(formData.get("password") ?? "");

    if (!email || !password) {
      setMessage("Enter both your email and password.");
      return;
    }

    setPending(true);
    try {
      const supabase = createClient();
      const { data, error } = await supabase.auth.signInWithPassword({ email, password });
      if (error) {
        setMessage(error.message);
        return;
      }

      const { data: membership, error: membershipError } = await supabase
        .from("admin_users")
        .select("user_id")
        .eq("user_id", data.user.id)
        .maybeSingle();

      if (membershipError) {
        await supabase.auth.signOut();
        setMessage(`Unable to verify administrator access: ${membershipError.message}`);
        return;
      }

      if (!membership) {
        await supabase.auth.signOut();
        setMessage("This account does not have administrator access.");
        return;
      }

      router.replace("/dashboard");
      router.refresh();
    } catch (error) {
      setMessage(error instanceof Error ? error.message : "Unable to sign in.");
    } finally {
      setPending(false);
    }
  }

  return (
    <form className="mt-7 space-y-4" onSubmit={handleSubmit}>
      <label className="block text-sm">
        <span className="mb-2 block text-white/70">Email</span>
        <input
          autoComplete="username"
          className="w-full rounded-xl border border-white/10 bg-ink px-4 py-3 text-white"
          disabled={!configured || pending}
          name="email"
          required
          type="email"
        />
      </label>
      <label className="block text-sm">
        <span className="mb-2 block text-white/70">Password</span>
        <input
          autoComplete="current-password"
          className="w-full rounded-xl border border-white/10 bg-ink px-4 py-3 text-white"
          disabled={!configured || pending}
          name="password"
          required
          type="password"
        />
      </label>
      {message && (
        <p aria-live="polite" className="text-sm text-red-300" role="alert">
          {message}
        </p>
      )}
      <button className="btn-lime w-full justify-center disabled:cursor-not-allowed disabled:opacity-50" disabled={!configured || pending} type="submit">
        {pending ? "Signing in..." : "Sign in"}
      </button>
    </form>
  );
}
