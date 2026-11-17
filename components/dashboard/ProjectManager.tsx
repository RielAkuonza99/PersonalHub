"use client";

import { FormEvent, useState } from "react";
import { createClient } from "@/lib/supabase/client";
import type { AdminProject } from "@/lib/data/admin-projects";

type ProjectFields = Omit<AdminProject, "id">;

function validateProject(project: ProjectFields): string | null {
  if (!project.title.trim() || project.title.length > 200) return "Title must be between 1 and 200 characters.";
  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(project.slug) || project.slug.length > 160) {
    return "Use a lowercase slug with letters, numbers, and single hyphens.";
  }
  if (project.category && project.category.length > 120) return "Category must be 120 characters or fewer.";
  if (project.description && project.description.length > 20000) return "Description must be 20,000 characters or fewer.";
  if (project.cover_path && project.cover_path.length > 1024) return "Cover path must be 1,024 characters or fewer.";
  if (project.external_url) {
    if (project.external_url.length > 2048) return "External URL must be 2,048 characters or fewer.";
    try {
      const url = new URL(project.external_url);
      if (url.protocol !== "https:" && url.protocol !== "http:") return "External URL must use HTTP or HTTPS.";
    } catch {
      return "Enter a valid external URL.";
    }
  }
  return null;
}

export function ProjectManager({ initialProjects }: { initialProjects: AdminProject[] }) {
  const [projects, setProjects] = useState(initialProjects);
  const [editing, setEditing] = useState<AdminProject | null>(null);
  const [message, setMessage] = useState("");
  const [messageIsError, setMessageIsError] = useState(false);
  const [saving, setSaving] = useState(false);
  const [deletingId, setDeletingId] = useState<string | null>(null);

  async function saveProject(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const form = event.currentTarget;
    const fields = new FormData(form);
    const sortOrder = Number(fields.get("sort_order") ?? 0);

    if (!Number.isSafeInteger(sortOrder)) {
      setMessage("Sort order must be a whole number.");
      setMessageIsError(true);
      return;
    }

    const project: ProjectFields = {
      slug: String(fields.get("slug") ?? "").trim(),
      title: String(fields.get("title") ?? "").trim(),
      category: String(fields.get("category") ?? "").trim() || null,
      description: String(fields.get("description") ?? "").trim() || null,
      cover_path: String(fields.get("cover_path") ?? "").trim() || null,
      external_url: String(fields.get("external_url") ?? "").trim() || null,
      featured: fields.has("featured"),
      published: fields.has("published"),
      sort_order: sortOrder,
    };
    const validationError = validateProject(project);
    if (validationError) {
      setMessage(validationError);
      setMessageIsError(true);
      return;
    }

    setSaving(true);
    setMessage("");
    setMessageIsError(false);
    try {
      const supabase = createClient();
      if (editing) {
        const { data, error } = await supabase
          .from("projects")
          .update(project)
          .eq("id", editing.id)
          .select("id, slug, title, category, description, cover_path, external_url, featured, sort_order, published")
          .maybeSingle();

        if (error) throw new Error(error.message);
        if (!data) throw new Error("The project no longer exists or could not be updated.");
        setProjects((current) =>
          current
            .map((item) => item.id === data.id ? data : item)
            .sort((a, b) => a.sort_order - b.sort_order),
        );
        setMessage("Project updated.");
      } else {
        const { data, error } = await supabase
          .from("projects")
          .insert(project)
          .select("id, slug, title, category, description, cover_path, external_url, featured, sort_order, published")
          .single();

        if (error) throw new Error(error.message);
        setProjects((current) => [...current, data].sort((a, b) => a.sort_order - b.sort_order));
        setMessage("Project created.");
      }

      form.reset();
      setEditing(null);
    } catch (error) {
      setMessage(error instanceof Error ? `Unable to save project: ${error.message}` : "Unable to save project.");
      setMessageIsError(true);
    } finally {
      setSaving(false);
    }
  }

  async function deleteProject(project: AdminProject) {
    if (!window.confirm(`Delete "${project.title}"? This cannot be undone.`)) return;

    setDeletingId(project.id);
    setMessage("");
    setMessageIsError(false);
    try {
      const { data, error } = await createClient()
        .from("projects")
        .delete()
        .eq("id", project.id)
        .select("id")
        .maybeSingle();

      if (error) throw new Error(error.message);
      if (!data) throw new Error("The project no longer exists or could not be deleted.");
      setProjects((current) => current.filter((item) => item.id !== project.id));
      if (editing?.id === project.id) setEditing(null);
      setMessage("Project deleted.");
    } catch (error) {
      setMessage(error instanceof Error ? `Unable to delete project: ${error.message}` : "Unable to delete project.");
      setMessageIsError(true);
    } finally {
      setDeletingId(null);
    }
  }

  return (
    <div className="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
      <section aria-labelledby="project-list-title" className="panel p-5 sm:p-6">
        <h3 className="font-display text-xl" id="project-list-title">All projects ({projects.length})</h3>
        {projects.length === 0 ? (
          <p className="mt-5 text-sm text-white/60">No projects yet. Add one using the form.</p>
        ) : (
          <ul className="mt-4 divide-y divide-white/10">
            {projects.map((project) => (
              <li className="flex flex-wrap items-center justify-between gap-4 py-4" key={project.id}>
                <div className="min-w-0">
                  <p className="truncate font-medium">{project.title}</p>
                  <p className="mt-1 text-xs text-white/50">
                    /{project.slug} · {project.published ? "Published" : "Draft"}
                    {project.featured ? " · Featured" : ""}
                  </p>
                </div>
                <div className="flex gap-2">
                  <button
                    className="rounded-full border border-white/15 px-3 py-1.5 text-xs hover:bg-white/5"
                    onClick={() => { setEditing(project); setMessage(""); setMessageIsError(false); }}
                    type="button"
                  >
                    Edit
                  </button>
                  <button
                    className="rounded-full border border-red-400/40 px-3 py-1.5 text-xs text-red-200 hover:bg-red-400/10 disabled:opacity-50"
                    disabled={deletingId === project.id}
                    onClick={() => void deleteProject(project)}
                    type="button"
                  >
                    {deletingId === project.id ? "Deleting..." : "Delete"}
                  </button>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>

      <section aria-labelledby="project-form-title" className="panel p-5 sm:p-6">
        <h3 className="font-display text-xl" id="project-form-title">
          {editing ? "Edit project" : "New project"}
        </h3>
        <form className="mt-4 space-y-3" key={editing?.id ?? "new-project"} onSubmit={saveProject}>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">Title</span>
            <input className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.title ?? ""} maxLength={200} name="title" required />
          </label>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">Slug</span>
            <input className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.slug ?? ""} maxLength={160} name="slug" pattern="[a-z0-9]+(-[a-z0-9]+)*" required />
          </label>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">Category</span>
            <input className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.category ?? ""} maxLength={120} name="category" />
          </label>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">Description</span>
            <textarea className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.description ?? ""} maxLength={20000} name="description" rows={4} />
          </label>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">Cover storage path</span>
            <input className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.cover_path ?? ""} maxLength={1024} name="cover_path" placeholder="projects/example/cover.webp" />
          </label>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">External URL</span>
            <input className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.external_url ?? ""} maxLength={2048} name="external_url" type="url" />
          </label>
          <label className="block text-sm">
            <span className="mb-1.5 block text-white/70">Sort order</span>
            <input className="w-full rounded-xl border border-white/10 bg-ink px-3 py-2.5" defaultValue={editing?.sort_order ?? 0} name="sort_order" step={1} type="number" />
          </label>
          <div className="flex flex-wrap gap-5 pt-1">
            <label className="flex items-center gap-2 text-sm text-white/70">
              <input defaultChecked={editing?.featured ?? false} name="featured" type="checkbox" />
              Featured
            </label>
            <label className="flex items-center gap-2 text-sm text-white/70">
              <input defaultChecked={editing?.published ?? false} name="published" type="checkbox" />
              Published
            </label>
          </div>
          {message && (
            <p
              aria-live="polite"
              className={`text-sm ${messageIsError ? "text-red-300" : "text-lime"}`}
              role={messageIsError ? "alert" : "status"}
            >
              {message}
            </p>
          )}
          <div className="flex gap-3 pt-2">
            <button className="btn-lime justify-center disabled:opacity-50" disabled={saving} type="submit">
              {saving ? "Saving..." : editing ? "Save changes" : "Create project"}
            </button>
            {editing && (
              <button className="btn-ghost" onClick={() => setEditing(null)} type="button">Cancel</button>
            )}
          </div>
        </form>
      </section>
    </div>
  );
}
