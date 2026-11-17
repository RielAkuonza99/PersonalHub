import { FolderKanban, LayoutDashboard, Newspaper, Settings, UserRound, Video, Wrench } from "lucide-react";

export const dashboardNav = [
  { label: "Overview", href: "/dashboard", icon: LayoutDashboard },
  { label: "Projects", href: "/dashboard/projects", icon: FolderKanban },
  { label: "Videos", href: "/dashboard#videos", icon: Video },
  { label: "Technologies", href: "/dashboard#technologies", icon: Wrench },
  { label: "Blog", href: "/dashboard#blog", icon: Newspaper },
  { label: "Profile", href: "/dashboard#profile", icon: UserRound },
  { label: "Settings", href: "/dashboard#settings", icon: Settings },
];
