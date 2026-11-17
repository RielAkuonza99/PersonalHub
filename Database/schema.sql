-- PersonalHub initial schema draft (PostgreSQL / Supabase). Not applied automatically.
create extension if not exists "pgcrypto";

create table profile (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  roles text[] not null default '{}',
  tagline text,
  bio text,
  email text,
  location text,
  avatar_path text,
  updated_at timestamptz not null default now()
);

create table projects (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  category text,
  description text,
  cover_path text,
  external_url text,
  featured boolean not null default false,
  sort_order int not null default 0,
  published boolean not null default true,
  created_at timestamptz not null default now()
);

create table skills (
  id uuid primary key default gen_random_uuid(),
  label text not null,
  icon_path text,
  sort_order int not null default 0
);

create table technologies (
  id uuid primary key default gen_random_uuid(),
  label text not null,
  description text,
  icon_path text,
  sort_order int not null default 0
);

create table videos (
  id uuid primary key default gen_random_uuid(),
  title text not null,
  description text,
  video_url text,
  thumbnail_path text,
  is_showreel boolean not null default false,
  published boolean not null default true,
  created_at timestamptz not null default now()
);

create table posts (
  id uuid primary key default gen_random_uuid(),
  slug text unique not null,
  title text not null,
  excerpt text,
  body text,
  published boolean not null default false,
  published_at timestamptz,
  created_at timestamptz not null default now()
);

create table messages (
  id uuid primary key default gen_random_uuid(),
  name text not null,
  email text not null,
  body text not null,
  created_at timestamptz not null default now()
);

-- Public read for published content; writes need an authenticated admin (policies to be added).
alter table profile enable row level security;
alter table projects enable row level security;
alter table skills enable row level security;
alter table technologies enable row level security;
alter table videos enable row level security;
alter table posts enable row level security;
alter table messages enable row level security;

create policy "public read projects" on projects for select using (published);
create policy "public read videos" on videos for select using (published);
create policy "public read posts" on posts for select using (published);
create policy "public read profile" on profile for select using (true);
create policy "public read skills" on skills for select using (true);
create policy "public read technologies" on technologies for select using (true);
