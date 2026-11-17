# Supabase setup

## Environment

Copy `.env.example` to `.env.local` and set:

- `NEXT_PUBLIC_SUPABASE_URL` — project URL.
- `NEXT_PUBLIC_SUPABASE_ANON_KEY` — public/anon key; protected by Row Level Security.
- `NEXT_PUBLIC_SUPABASE_STORAGE_BUCKET` — leave as `media` (the bucket provisioned by the migration); changing it requires provisioning equivalent private-bucket policies for that bucket.
- `SUPABASE_SERVICE_ROLE_KEY` — server-only secret for future trusted server operations. It is not needed by the current login flow and must never use a `NEXT_PUBLIC_` prefix.

The public pages continue to use local mock data when database reads are not configured. Administrator sign-in and dashboard access require the Supabase URL and anon key.

## Database migrations

Use the Supabase SQL editor or the Supabase CLI to apply migrations in order:

1. `Database/migrations/0001_init.sql` — original project schema.
2. `Database/migrations/0002_content_security.sql` — added tables, constraints, indexes, timestamps, RLS, administrator authorization, and private media bucket policies.

Do not rerun the initial migration after applying the second migration. The `Database/schema.sql` file is the original draft/reference; migrations are the source of truth for an existing database.

Migration `0002` creates `project_media`, `technology_docs`, `journeys`, `experiences`, `site_settings`, and `admin_users`; adds the missing publication, slug, media, and update fields; installs timestamp triggers; and limits anonymous reads to published content. Public contact submissions may insert validated messages but cannot read them.

The `media` bucket is private. Public reads are allowed only for files referenced by published content. Authenticated administrators can upload, update, and delete files. Media uploads currently accept JPEG, PNG, WebP, AVIF, and GIF files up to 10 MB. Use stored object paths in database rows; do not store file bytes in PostgreSQL.

## Administrator provisioning

1. Create the intended account in Supabase Authentication.
2. Copy its user UUID from the Auth users list.
3. In the SQL editor, grant the explicit allow-list membership:

   ```sql
   insert into public.admin_users (user_id)
   values ('AUTH_USER_UUID');
   ```

4. Remove access by deleting that row:

   ```sql
   delete from public.admin_users
   where user_id = 'AUTH_USER_UUID';
   ```

Only allow-listed users can enter `/dashboard`. The middleware checks the verified Supabase session and membership, and the dashboard layout repeats the server-side check. A signed-in user is not automatically an administrator.

## Current implementation boundary

Authentication and authorization are active, and project CRUD is connected to Supabase with RLS-backed browser operations. Media preview/progress UI, the other content CRUD modules, and public database-backed pages remain incremental follow-up work. The current Supabase Storage SDK upload helper validates file type, size, and image signatures; it does not report byte-level upload progress.
