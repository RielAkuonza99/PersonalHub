var SITE, SKILLS, SKILL_CATEGORIES, PROJECTS, JOURNEY, VIDEOS; // filled from data/data.json
const API_PUBLIC = window.PERSONALHUB_API_BASE || "https://app.personalhub.my.id/api/public/";
const trackVisit = () => {
  if (location.protocol === "file:") return;
  fetch(new URL("visit.php", API_PUBLIC), { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ path: location.hash || "/" }), keepalive: true }).catch(() => {});
};
trackVisit(); addEventListener("hashchange", trackVisit);
(async () => {
  const $ = id => document.getElementById(id);
  const L = UI.loader; L.start();
  let d;
  const load = async url => { const r = await fetch(url, { cache: "no-cache" }); if (!r.ok) throw new Error(r.status); return r.json(); };
  try {
    d = await load(new URL("content.php", API_PUBLIC)); // primary source: the separate backend
    if (!d || !d.site) throw new Error("bad payload");
  } catch (e) {
    try { d = await load("data/data.json"); } // archived fallback if the backend is down or empty
    catch (e2) { return L.fail("Cannot load content. Open the site through a web host (PHP), not as a plain file."); }
  }
  ({ site: SITE, skills: SKILLS, skillCategories: SKILL_CATEGORIES, projects: PROJECTS, journey: JOURNEY } = d);
  if (d.meta) {
    document.title = d.meta.title || document.title;
    const setMeta = (selector, value) => {
      const el = document.querySelector(selector);
      if (el && typeof value === "string") el.content = value;
    };
    setMeta('meta[name="description"]', d.meta.description);
    setMeta('meta[property="og:title"]', d.meta.og_title);
    setMeta('meta[property="og:description"]', d.meta.og_description);
    setMeta('meta[name="theme-color"]', d.meta.theme_color);
    setMeta('meta[name="google-site-verification"]', d.meta.gsv);
    const icon = document.querySelector('link[rel="icon"]');
    if (icon && d.meta.favicon) {
      icon.href = d.meta.favicon;
      if (/\.svg(?:\?|$)/i.test(d.meta.favicon)) icon.type = "image/svg+xml";
      else icon.removeAttribute("type");
    }
  }
  L.progress(45, "Building pages…");

  const li = (el, text) => { const x = document.createElement("li"); x.textContent = text; el.append(x); };
  const links = (el, items) => items.forEach(s => {
    const x = document.createElement("li"), a = document.createElement("a");
    a.innerHTML = Icons.svg(s.id) + "<span></span>"; a.lastChild.textContent = s.label;
    a.href = s.url; if (!s.url.startsWith("mailto:")) { a.target = "_blank"; a.rel = "noopener noreferrer"; }
    x.append(a); el.append(x);
  });
  const timeline = (el, list) => list.forEach(j => {
    const x = document.createElement("li");
    x.innerHTML = "<time></time><div><h3><a></a></h3><p></p></div>";
    x.querySelector("time").textContent = j.year;
    const title = x.querySelector("h3 a"); title.textContent = j.title; title.href = "#/journey/" + (j.slug || encodeURIComponent(j.year + "-" + j.title));
    x.querySelector("p").textContent = j.text;
    el.append(x);
  });

  document.querySelectorAll("[data-bind]").forEach(el => el.textContent = SITE[el.dataset.bind]);
  document.querySelectorAll("[data-bind-src]").forEach(el => el.src = SITE[el.dataset.bindSrc]);
  $("year").textContent = new Date().getFullYear();
  const contact = [{ id: "mail", label: SITE.email, url: "mailto:" + SITE.email }, ...SITE.socials];
  links($("heroSocials"), SITE.socials); links($("footSocials"), SITE.socials);
  links($("contactLinks"), contact); links($("pvLinks"), contact);
  SITE.bio.forEach(([k, v]) => { const x = document.createElement("div"); x.innerHTML = "<dt></dt><dd></dd>"; x.children[0].textContent = k; x.children[1].textContent = v; $("bio").append(x); });
  SITE.roles.forEach(t => li($("focus"), t)); SITE.interests.forEach(t => li($("interests"), t)); SITE.goals.forEach(t => li($("goals"), t));
  timeline($("timeline"), JOURNEY); timeline($("pvTimeline"), JOURNEY.slice(-3));
  const renderJourneyDetail = () => {
    const match = location.hash.match(/^#\/journey\/([^/?]+)/); const detail = $("journeyDetail"); if (!match) { detail.hidden = true; return; }
    const slug = decodeURIComponent(match[1]); const item = JOURNEY.find(j => (j.slug || "") === slug || slug === j.year + "-" + j.title.toLowerCase().replace(/[^a-z0-9]+/g, "-")); if (!item) { detail.hidden = true; return; }
    detail.hidden = false; detail.textContent = "";
    const breadcrumb = document.createElement("nav"); breadcrumb.className = "breadcrumb"; breadcrumb.setAttribute("aria-label", "Breadcrumb"); const home = document.createElement("a"); home.href="#/home"; home.textContent="Home"; const label=document.createElement("span"); label.textContent="Journey"; const current=document.createElement("strong"); current.textContent=item.year+" "+item.title; breadcrumb.append(home,label,current);
    const year=document.createElement("p"); year.className="muted"; year.textContent=item.year; const title=document.createElement("h2"); title.textContent=item.title; const copy=document.createElement("div"); copy.className="journey-copy";
    const allowed=new Set(["P","BR","STRONG","B","EM","I","U","UL","OL","LI","A","H2","H3","BLOCKQUOTE","CODE","SPAN"]);
    const parsed=new DOMParser().parseFromString("<div>"+(item.html||item.text||"")+"</div>","text/html"); const clone=(node)=>{ if(node.nodeType===Node.TEXT_NODE)return document.createTextNode(node.textContent||""); if(node.nodeType!==Node.ELEMENT_NODE)return document.createTextNode(""); if(!allowed.has(node.tagName)) {const fragment=document.createDocumentFragment(); node.childNodes.forEach(child=>fragment.append(clone(child))); return fragment;} const clean=document.createElement(node.tagName.toLowerCase()); if(node.tagName==="A"){const href=node.getAttribute("href")||""; try{const u=new URL(href,location.href); if(["http:","https:","mailto:"].includes(u.protocol)){clean.href=u.href;clean.target="_blank";clean.rel="noopener noreferrer";}}catch(e){}} node.childNodes.forEach(child=>clean.append(clone(child))); return clean;};
    Array.from(parsed.body.firstElementChild.childNodes).forEach(node=>copy.append(clone(node)));
    detail.append(breadcrumb,year,title,copy);
    if(item.cover){const cover=document.createElement("img");cover.src=item.cover;cover.alt="";cover.loading="lazy";cover.className="journey-cover";detail.append(cover)}
    if(item.document){const doc=document.createElement("a");doc.href=item.document;doc.target="_blank";doc.rel="noopener noreferrer";doc.className="btn sm";doc.textContent="Buka PDF dokumentasi";detail.append(doc)}
    if(item.link){const link=document.createElement("a");link.href=item.link;link.target="_blank";link.rel="noopener noreferrer";link.className="btn sm";link.textContent="Buka dokumentasi";detail.append(link)}
  };
  addEventListener("hashchange", renderJourneyDetail); renderJourneyDetail();

  VIDEOS = d.videos || []; Seo.init(d); Theme.init(); Nav.init(); Projects.init(); Videos.init(VIDEOS); Explore.init(); UI.init();
  L.progress(80, "Loading fonts…");
  await Promise.race([document.fonts ? document.fonts.ready : 0, new Promise(r => setTimeout(r, 2500))]);
  L.progress(92, "Almost ready…");
  if (document.readyState !== "complete") await Promise.race([new Promise(r => addEventListener("load", r)), new Promise(r => setTimeout(r, 3000))]);
  L.progress(100, "Ready");
})();
