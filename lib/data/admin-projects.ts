import "server-only";
import { createClient } from "@/lib/supabase/server";

export type AdminProject = {
  id: string;
  slug: string;
  title: string;
  category: string | null;
  description: string | null;
  cover_path: string | null;
  external_url: string | null;
  featured: boolean;
  sort_order: number;
  published: boolean;
};

export async function getAdminProjects(): Promise<AdminProject[]> {
  const supabase = await createClient();
  const { data, error } = await supabase
    .from("projects")
    .select("id, slug, title, category, description, cover_path, external_url, featured, sort_order, published")
    .order("sort_order", { ascending: true })
    .order("created_at", { ascending: false });

  if (error) {
    throw new Error(`Unable to load projects: ${error.message}`);
  }

  return data ?? [];
}
