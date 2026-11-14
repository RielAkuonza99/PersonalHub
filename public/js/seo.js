/* SEO: keeps the title, description, canonical, share tags and schema.org JSON-LD in step with the admin data and the current page,
   so search results can show the visitor's searched name (Riel Akuonza) and the matching section. Static copies of the same tags
   live in index.html for crawlers that do not run JavaScript. */
const Seo = (() => {
  let D, base, name, roles;
  const $ = s => document.querySelector(s);
  const put = (sel, attr, v) => { const el = $(sel); if (el && v) el.setAttribute(attr, v); };
  const abs = u => { try { return u ? new URL(u, base + "/").href : ""; } catch (e) { return ""; } };
  const cut = (t, n) => { t = String(t || "").replace(/\s+/g, " ").trim(); return t.length > n ? t.slice(0, n - 1).replace(/\s+\S*$/, "") + "…" : t; };

  const pageMeta = key => {
    const m = D.meta || {}, role = roles[0] || "Developer", about = cut(D.site.about, 155);
    const home = { t: m.title || `${name} — Informatics Student & Developer`, d: m.description || about };
    return ({
      home,
      about: { t: `Tentang ${name} — ${role} | Profil & Biodata`, d: `Tentang ${name}: ${about}` },
      skills: { t: `Skills ${name} — ${roles.slice(0, 3).join(", ") || "Developer"}`, d: `Daftar skills dan tingkat kemampuan ${name}.` },
      projects: { t: `Projects ${name} — Web, Aplikasi & Desain`, d: `Kumpulan project karya ${name}: web, aplikasi, dan desain.` },
      videos: { t: `Video ${name} — TikTok`, d: `Video TikTok ${name}, diputar langsung dari TikTok.` },
      journey: { t: `Perjalanan Belajar ${name}`, d: `Timeline perjalanan belajar dan karya ${name}.` },
      contact: { t: `Kontak ${name}`, d: `Hubungi ${name} untuk kolaborasi, magang, atau ide project.` }
    })[key] || home;
  };

  const graph = () => {
    const url = base + "/", ids = { p: url + "#person", w: url + "#website" };
    const sameAs = (D.site.socials || []).map(s => s.url).filter(u => /^https:\/\//i.test(u));
    const know = [...new Set([...roles, ...(D.skills || []).slice(0, 12).map(s => s.name)])].slice(0, 20);
    const alt = (D.meta && D.meta.alt_names || []).filter(n => n.toLowerCase() !== name.toLowerCase());
    const person = { "@type": "Person", "@id": ids.p, name, url, jobTitle: roles[0] || "Developer", description: cut(D.site.about, 300) };
    if (alt.length) person.alternateName = alt;
    const img = abs((D.meta && D.meta.og_image) || D.site.photo); if (img && !/\.svg(\?|$)/i.test(img)) person.image = img;
    if (sameAs.length) person.sameAs = sameAs;
    if (know.length) person.knowsAbout = know;
    return { "@context": "https://schema.org", "@graph": [
      person,
      { "@type": "WebSite", "@id": ids.w, url, name, alternateName: "Personal HUB", inLanguage: "id", publisher: { "@id": ids.p } },
      { "@type": "ProfilePage", "@id": url + "#profile", url, name: `Tentang ${name}`, mainEntity: { "@id": ids.p }, isPartOf: { "@id": ids.w } }
    ] };
  };

  const apply = () => {
    const key = (location.hash.match(/^#\/(\w+)/) || [, "home"])[1], p = pageMeta(key), m = D.meta || {};
    document.title = p.t;
    put('meta[name="description"]', "content", p.d);
    put('meta[property="og:title"]', "content", key === "home" ? (m.og_title || p.t) : p.t);
    put('meta[property="og:description"]', "content", key === "home" ? (m.og_description || p.d) : p.d);
    put('meta[name="twitter:title"]', "content", p.t); put('meta[name="twitter:description"]', "content", p.d);
    // Hash routes are not separate URLs for Google, so the canonical always stays on the main address.
  };

  const init = d => {
    D = d; name = (d.site.name || "Riel Akuonza").trim(); roles = d.site.roles || [];
    base = ((d.meta && d.meta.site_url) || location.origin).replace(/\/+$/, "");
    put('link[rel="canonical"]', "href", base + "/"); put('meta[property="og:url"]', "content", base + "/");
    put('meta[property="og:site_name"]', "content", name);
    const img = abs((d.meta && d.meta.og_image) || d.site.photo);
    if (img) { let t = $('meta[property="og:image"]'); if (!t) { t = document.createElement("meta"); t.setAttribute("property", "og:image"); document.head.append(t); } t.setAttribute("content", img); let w = $('meta[name="twitter:image"]'); if (!w) { w = document.createElement("meta"); w.setAttribute("name", "twitter:image"); document.head.append(w); } w.setAttribute("content", img); }
    const ld = $("#ldjson"); if (ld) ld.textContent = JSON.stringify(graph());
    apply(); addEventListener("hashchange", apply);
  };
  return { init };
})();
