-- Extend the initial draft without changing or replaying 0001_init.sql.
create extension if not exists "pgcrypto";

create table if not exists public.admin_users (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null unique references auth.users (id) on delete cascade,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create or replace function public.is_admin()
returns boolean
language sql
stable
security definer
set search_path = public, pg_temp
as $$
  select exists (
    select 1
    from public.admin_users
    where user_id = (select auth.uid())
  );
$$;

revoke all on function public.is_admin() from public;
grant execute on function public.is_admin() to authenticated;

alter table public.profile
  add column if not exists published boolean not null default true,
  add column if not exists created_at timestamptz not null default now();

alter table public.projects
  add column if not exists updated_at timestamptz not null default now();

alter table public.skills
  add column if not exists created_at timestamptz not null default now(),
  add column if not exists published boolean not null default true,
  add column if not exists updated_at timestamptz not null default now();

alter table public.technologies
  add column if not exists created_at timestamptz not null default now(),
  add column if not exists slug text,
  add column if not exists published boolean not null default true,
  add column if not exists updated_at timestamptz not null default now();

update public.technologies
set slug = coalesce(
  nullif(trim(both '-' from regexp_replace(lower(label), '[^a-z0-9]+', '-', 'g')), ''),
  'technology-' || id::text
)
where slug is null;

with duplicate_slugs as (
  select id, row_number() over (partition by slug order by id) as position
  from public.technologies
)
update public.technologies as technology
set slug = technology.slug || '-' || technology.id::text
from duplicate_slugs
where duplicate_slugs.id = technology.id
  and duplicate_slugs.position > 1;

alter table public.technologies alter column slug set not null;

alter table public.videos
  add column if not exists updated_at timestamptz not null default now();

alter table public.posts
  add column if not exists cover_path text,
  add column if not exists updated_at timestamptz not null default now();

alter table public.messages
  add column if not exists updated_at timestamptz not null default now();

alter table public.profile alter column published set default false;
alter table public.projects alter column published set default false;
alter table public.skills alter column published set default false;
alter table public.technologies alter column published set default false;
alter table public.videos alter column published set default false;

create table if not exists public.project_media (
  id uuid primary key default gen_random_uuid(),
  project_id uuid not null references public.projects (id) on delete cascade,
  storage_path text not null unique,
  alt_text text not null,
  media_type text not null default 'image' check (media_type in ('image', 'video')),
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (length(btrim(alt_text)) between 1 and 300),
  check (length(btrim(storage_path)) between 1 and 1024)
);

create table if not exists public.technology_docs (
  id uuid primary key default gen_random_uuid(),
  technology_id uuid not null references public.technologies (id) on delete cascade,
  slug text not null,
  title text not null,
  summary text,
  content text,
  url text,
  published boolean not null default false,
  sort_order integer not null default 0,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  unique (technology_id, slug),
  check (length(btrim(slug)) between 1 and 160),
  check (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$'),
  check (length(btrim(title)) between 1 and 200),
  check (summary is null or length(summary) <= 1000),
  check (url is null or url ~ '^https?://')
);

create table if not exists public.journeys (
  id uuid primary key default gen_random_uuid(),
  slug text not null unique,
  title text not null,
  description text,
  start_date date,
  end_date date,
  sort_order integer not null default 0,
  published boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (length(btrim(slug)) between 1 and 160),
  check (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$'),
  check (length(btrim(title)) between 1 and 200),
  check (description is null or length(description) <= 5000),
  check (end_date is null or start_date is null or end_date >= start_date)
);

create table if not exists public.experiences (
  id uuid primary key default gen_random_uuid(),
  journey_id uuid references public.journeys (id) on delete set null,
  title text not null,
  organization text,
  description text,
  start_date date,
  end_date date,
  sort_order integer not null default 0,
  published boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (length(btrim(title)) between 1 and 200),
  check (organization is null or length(organization) <= 200),
  check (description is null or length(description) <= 5000),
  check (end_date is null or start_date is null or end_date >= start_date)
);

create table if not exists public.site_settings (
  id uuid primary key default gen_random_uuid(),
  key text not null unique,
  value jsonb not null default '{}'::jsonb,
  is_public boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  check (key ~ '^[a-z][a-z0-9_.-]{0,99}$')
);

do $$
begin
  if not exists (select 1 from pg_constraint where conname = 'technologies_slug_format') then
    alter table public.technologies
      add constraint technologies_slug_format
      check (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$') not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'projects_slug_format') then
    alter table public.projects
      add constraint projects_slug_format
      check (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$') not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'projects_title_nonempty') then
    alter table public.projects
      add constraint projects_title_nonempty
      check (length(btrim(title)) between 1 and 200) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'projects_category_length') then
    alter table public.projects
      add constraint projects_category_length
      check (category is null or length(category) <= 120) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'projects_description_length') then
    alter table public.projects
      add constraint projects_description_length
      check (description is null or length(description) <= 20000) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'projects_cover_path_length') then
    alter table public.projects
      add constraint projects_cover_path_length
      check (cover_path is null or length(cover_path) <= 1024) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'projects_external_url_format') then
    alter table public.projects
      add constraint projects_external_url_format
      check (external_url is null or (length(external_url) <= 2048 and external_url ~ '^https?://')) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'profile_name_nonempty') then
    alter table public.profile
      add constraint profile_name_nonempty
      check (length(btrim(name)) between 1 and 160) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'skills_label_nonempty') then
    alter table public.skills
      add constraint skills_label_nonempty
      check (length(btrim(label)) between 1 and 120) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'technologies_label_nonempty') then
    alter table public.technologies
      add constraint technologies_label_nonempty
      check (length(btrim(label)) between 1 and 120) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'videos_title_nonempty') then
    alter table public.videos
      add constraint videos_title_nonempty
      check (length(btrim(title)) between 1 and 200) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'posts_slug_format') then
    alter table public.posts
      add constraint posts_slug_format
      check (slug ~ '^[a-z0-9]+(-[a-z0-9]+)*$') not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'messages_name_length') then
    alter table public.messages
      add constraint messages_name_length
      check (length(btrim(name)) between 1 and 120) not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'messages_email_format') then
    alter table public.messages
      add constraint messages_email_format
      check (email ~* '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$') not valid;
  end if;
  if not exists (select 1 from pg_constraint where conname = 'messages_body_length') then
    alter table public.messages
      add constraint messages_body_length
      check (length(btrim(body)) between 1 and 10000) not valid;
  end if;
end;
$$;

create index if not exists projects_published_sort_idx
  on public.projects (sort_order, created_at desc) where published;
create unique index if not exists technologies_slug_uidx on public.technologies (slug);
create index if not exists technologies_published_sort_idx
  on public.technologies (sort_order, label) where published;
create index if not exists skills_published_sort_idx
  on public.skills (sort_order, label) where published;
create index if not exists videos_published_sort_idx
  on public.videos (sort_order, created_at desc) where published;
create index if not exists posts_published_date_idx
  on public.posts (published_at desc, created_at desc) where published;
create index if not exists project_media_project_sort_idx
  on public.project_media (project_id, sort_order);
create index if not exists technology_docs_public_sort_idx
  on public.technology_docs (technology_id, sort_order) where published;
create index if not exists journeys_published_sort_idx
  on public.journeys (sort_order, start_date desc) where published;
create index if not exists experiences_journey_sort_idx
  on public.experiences (journey_id, sort_order, start_date desc);
create index if not exists experiences_published_sort_idx
  on public.experiences (sort_order, start_date desc) where published;
create index if not exists messages_created_at_idx on public.messages (created_at desc);
create index if not exists site_settings_public_key_idx
  on public.site_settings (key) where is_public;

create or replace function public.set_updated_at()
returns trigger
language plpgsql
set search_path = public, pg_temp
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

do $$
declare
  table_name text;
begin
  foreach table_name in array array[
    'admin_users', 'profile', 'projects', 'project_media', 'skills',
    'technologies', 'technology_docs', 'videos', 'journeys', 'experiences',
    'posts', 'messages', 'site_settings'
  ] loop
    execute format('drop trigger if exists set_updated_at on public.%I', table_name);
    execute format(
      'create trigger set_updated_at before update on public.%I for each row execute function public.set_updated_at()',
      table_name
    );
  end loop;
end;
$$;

alter table public.admin_users enable row level security;
alter table public.profile enable row level security;
alter table public.projects enable row level security;
alter table public.project_media enable row level security;
alter table public.skills enable row level security;
alter table public.technologies enable row level security;
alter table public.technology_docs enable row level security;
alter table public.videos enable row level security;
alter table public.journeys enable row level security;
alter table public.experiences enable row level security;
alter table public.posts enable row level security;
alter table public.messages enable row level security;
alter table public.site_settings enable row level security;

drop policy if exists "public read profile" on public.profile;
drop policy if exists "public read projects" on public.projects;
drop policy if exists "public read skills" on public.skills;
drop policy if exists "public read technologies" on public.technologies;
drop policy if exists "public read videos" on public.videos;
drop policy if exists "public read posts" on public.posts;

create policy "admin users read own membership" on public.admin_users
  for select to authenticated using (user_id = (select auth.uid()));
create policy "admins manage admin users" on public.admin_users
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published profile" on public.profile
  for select to anon, authenticated using (published);
create policy "admins manage profile" on public.profile
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published projects" on public.projects
  for select to anon, authenticated using (published);
create policy "admins manage projects" on public.projects
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published project media" on public.project_media
  for select to anon, authenticated using (
    exists (select 1 from public.projects where id = project_id and published)
  );
create policy "admins manage project media" on public.project_media
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published skills" on public.skills
  for select to anon, authenticated using (published);
create policy "admins manage skills" on public.skills
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published technologies" on public.technologies
  for select to anon, authenticated using (published);
create policy "admins manage technologies" on public.technologies
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published technology docs" on public.technology_docs
  for select to anon, authenticated using (
    published and exists (
      select 1 from public.technologies
      where id = technology_id and published
    )
  );
create policy "admins manage technology docs" on public.technology_docs
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published videos" on public.videos
  for select to anon, authenticated using (published);
create policy "admins manage videos" on public.videos
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published journeys" on public.journeys
  for select to anon, authenticated using (published);
create policy "admins manage journeys" on public.journeys
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published experiences" on public.experiences
  for select to anon, authenticated using (
    published and (
      journey_id is null or exists (
        select 1 from public.journeys
        where id = journey_id and published
      )
    )
  );
create policy "admins manage experiences" on public.experiences
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read published posts" on public.posts
  for select to anon, authenticated using (published);
create policy "admins manage posts" on public.posts
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public submit valid messages" on public.messages
  for insert to anon, authenticated with check (
    length(btrim(name)) between 1 and 120
    and email ~* '^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$'
    and length(btrim(body)) between 1 and 10000
  );
create policy "admins manage messages" on public.messages
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

create policy "public read public site settings" on public.site_settings
  for select to anon, authenticated using (is_public);
create policy "admins manage site settings" on public.site_settings
  for all to authenticated using (public.is_admin()) with check (public.is_admin());

grant select on public.profile, public.projects, public.project_media,
  public.skills, public.technologies, public.technology_docs, public.videos,
  public.journeys, public.experiences, public.posts, public.site_settings
  to anon, authenticated;
grant insert on public.messages to anon, authenticated;
grant select, insert, update, delete on public.admin_users,
  public.profile, public.projects, public.project_media, public.skills,
  public.technologies, public.technology_docs, public.videos, public.journeys,
  public.experiences, public.posts, public.messages, public.site_settings
  to authenticated;

insert into storage.buckets (
  id, name, public, file_size_limit, allowed_mime_types
)
values (
  'media', 'media', false, 10485760,
  array['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif']
)
on conflict (id) do update
set public = false,
    file_size_limit = excluded.file_size_limit,
    allowed_mime_types = excluded.allowed_mime_types;

create policy "public read media used by published content" on storage.objects
  for select to anon, authenticated using (
    bucket_id = 'media' and (
      exists (select 1 from public.profile where published and avatar_path = name)
      or exists (select 1 from public.projects where published and cover_path = name)
      or exists (
        select 1
        from public.project_media media
        join public.projects project on project.id = media.project_id
        where project.published and media.storage_path = name
      )
      or exists (select 1 from public.skills where published and icon_path = name)
      or exists (select 1 from public.technologies where published and icon_path = name)
      or exists (select 1 from public.videos where published and thumbnail_path = name)
      or exists (select 1 from public.posts where published and cover_path = name)
    )
  );
create policy "admins read media" on storage.objects
  for select to authenticated using (bucket_id = 'media' and public.is_admin());
create policy "admins upload media" on storage.objects
  for insert to authenticated
  with check (bucket_id = 'media' and public.is_admin());
create policy "admins update media" on storage.objects
  for update to authenticated
  using (bucket_id = 'media' and public.is_admin())
  with check (bucket_id = 'media' and public.is_admin());
create policy "admins delete media" on storage.objects
  for delete to authenticated
  using (bucket_id = 'media' and public.is_admin());
