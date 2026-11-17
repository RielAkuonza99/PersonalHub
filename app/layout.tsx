import type { Metadata, Viewport } from "next";
import "./globals.css";

export const metadata: Metadata = {
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? "https://personalhub.my.id"),
  title: { default: "PersonalHub", template: "%s | PersonalHub" },
  description: "Portfolio of Riel: web apps, bots and apps built with a playful touch.",
};
export const viewport: Viewport = { themeColor: "#08080d" };

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="" />
        {/* Swap for self-hosted files in /public/fonts when ready */}
        <link
          rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Gochi+Hand&family=Permanent+Marker&family=Poppins:wght@400;500;600&display=swap"
        />
      </head>
      <body>{children}</body>
    </html>
  );
}
