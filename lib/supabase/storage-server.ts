import "server-only";
import { createClient } from "./server";
import { STORAGE_BUCKET } from "./storage-config";

const SIGNED_URL_TTL_SECONDS = 60 * 60;

export async function createMediaSignedUrl(path: string): Promise<string> {
  if (
    !path ||
    path.startsWith("/") ||
    path.includes("\\") ||
    path.length > 1024 ||
    path.split("/").some((segment) => !segment || segment === "." || segment === "..")
  ) {
    throw new Error("The media storage path is invalid.");
  }

  const supabase = await createClient();
  const { data, error } = await supabase.storage
    .from(STORAGE_BUCKET)
    .createSignedUrl(path, SIGNED_URL_TTL_SECONDS);

  if (error) {
    throw new Error(`Unable to create media URL: ${error.message}`);
  }

  return data.signedUrl;
}
