/**
 * Data access layer. Every function is async so the mock data can later be
 * swapped for Supabase queries (see lib/supabase) without touching the UI.
 */
import { profile, projects, skills, technologies, videos } from "./mock";
import type { Profile, Project, Skill, Technology, Video } from "@/types";

export async function getProfile(): Promise<Profile> { return profile; }
export async function getProjects(limit?: number): Promise<Project[]> { return limit ? projects.slice(0, limit) : projects; }
export async function getSkills(): Promise<Skill[]> { return skills; }
export async function getTechnologies(): Promise<Technology[]> { return technologies; }
export async function getVideos(): Promise<Video[]> { return videos; }
