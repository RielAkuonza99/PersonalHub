import { createClient } from "./client";
import { STORAGE_BUCKET } from "./storage-config";

export const MAX_MEDIA_FILE_SIZE = 10 * 1024 * 1024;

const allowedMediaTypes = new Set([
  "image/jpeg",
  "image/png",
  "image/webp",
  "image/avif",
  "image/gif",
]);

async function validateMedia(file: File, path: string) {
  if (!allowedMediaTypes.has(file.type)) {
    throw new Error("Choose a JPEG, PNG, WebP, AVIF, or GIF image.");
  }
  if (file.size === 0 || file.size > MAX_MEDIA_FILE_SIZE) {
    throw new Error("Image files must be between 1 byte and 10 MB.");
  }
  if (
    !path ||
    path.startsWith("/") ||
    path.includes("\\") ||
    path.split("/").some((segment) => !segment || segment === "." || segment === "..") ||
    path.length > 1024
  ) {
    throw new Error("The media storage path is invalid.");
  }

  const bytes = new Uint8Array(await file.slice(0, 12).arrayBuffer());
  const startsWith = (...signature: number[]) =>
    signature.every((byte, index) => bytes[index] === byte);
  const isImage =
    (file.type === "image/jpeg" && startsWith(0xff, 0xd8, 0xff)) ||
    (file.type === "image/png" && startsWith(0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a)) ||
    (file.type === "image/webp" && startsWith(0x52, 0x49, 0x46, 0x46) && startsWithAt(8, 0x57, 0x45, 0x42, 0x50)) ||
    (file.type === "image/gif" && (startsWith(0x47, 0x49, 0x46, 0x38, 0x37, 0x61) || startsWith(0x47, 0x49, 0x46, 0x38, 0x39, 0x61))) ||
    (file.type === "image/avif" && startsWithAt(4, 0x66, 0x74, 0x79, 0x70) && (startsWithAt(8, 0x61, 0x76, 0x69, 0x66) || startsWithAt(8, 0x61, 0x76, 0x69, 0x73)));

  function startsWithAt(offset: number, ...signature: number[]) {
    return signature.every((byte, index) => bytes[offset + index] === byte);
  }

  if (!isImage) {
    throw new Error("The file contents do not match the selected image type.");
  }
}

export async function uploadMedia(file: File, path: string): Promise<string> {
  await validateMedia(file, path);
  const { data, error } = await createClient().storage
    .from(STORAGE_BUCKET)
    .upload(path, file, { contentType: file.type, upsert: false });

  if (error) {
    throw new Error(`Unable to upload media: ${error.message}`);
  }

  return data.path;
}

export async function deleteMedia(path: string): Promise<void> {
  if (
    !path ||
    path.startsWith("/") ||
    path.includes("\\") ||
    path.split("/").some((segment) => !segment || segment === "." || segment === "..") ||
    path.length > 1024
  ) {
    throw new Error("The media storage path is invalid.");
  }

  const { error } = await createClient().storage.from(STORAGE_BUCKET).remove([path]);
  if (error) {
    throw new Error(`Unable to delete media: ${error.message}`);
  }
}
