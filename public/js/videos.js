/* TikTok videos: the card loads TikTok's official Embed Player while it is on screen, so the video streams live from TikTok.
   Only the numeric video id comes from the API; the player URL is built here. */
const Videos = (() => {
  const PLAYER = "https://www.tiktok.com/player/v1/";
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const valid = id => /^\d{10,25}$/.test(String(id));
  const src = (id, play) => `${PLAYER}${id}?controls=1&loop=1&rel=0&description=1&music_info=1&autoplay=${play ? "1&muted=1" : "0"}`;

  const frame = (v, play) => {
    const f = document.createElement("iframe");
    f.src = src(v.videoId, play); f.title = v.title || v.caption || "TikTok video";
    f.setAttribute("allow", "autoplay; fullscreen; picture-in-picture; encrypted-media"); f.allowFullscreen = true;
    f.referrerPolicy = "strict-origin-when-cross-origin";
    return f;
  };
  const stage = v => {
    const s = document.createElement("div"); s.className = "vid-stage";
    if (v.thumb) { const i = document.createElement("img"); i.src = v.thumb; i.alt = ""; i.loading = "lazy"; i.referrerPolicy = "no-referrer"; i.addEventListener("error", () => i.remove()); s.append(i); }
    const b = document.createElement("button"); b.type = "button"; b.className = "vid-play"; b.setAttribute("aria-label", "Putar video"); b.textContent = "▶";
    s.append(b); return s;
  };

  const card = (v, io) => {
    const a = document.createElement("article"); a.className = "card vid-card";
    const media = document.createElement("div"); media.className = "vid-media"; media.append(stage(v));
    const body = document.createElement("div"); body.className = "card-body";
    const h = document.createElement("h3"); h.textContent = v.caption || v.title || "TikTok video";
    const p = document.createElement("p");
    if (v.author) p.textContent = v.author + " · ";
    const link = document.createElement("a"); link.href = v.url; link.target = "_blank"; link.rel = "noopener noreferrer"; link.textContent = "Buka di TikTok"; p.append(link);
    body.append(h, p); a.append(media, body);
    media.addEventListener("click", e => { if (e.target.closest(".vid-play")) load(media, v, true); });
    media._v = v; io.observe(media);
    return a;
  };
  // Only one player runs at a time: the rest go back to their cover image.
  let active = null;
  const unload = m => { if (m && m.querySelector("iframe")) { m.textContent = ""; m.append(stage(m._v)); } };
  const load = (m, v, play) => { if (active && active !== m) unload(active); m.textContent = ""; m.append(frame(v, play)); active = m; };

  const render = (el, list, io) => { el.textContent = ""; list.forEach(v => el.append(card(v, io))); };
  const init = list => {
    const items = (list || []).filter(v => valid(v.videoId));
    const grid = document.getElementById("videoGrid"), pv = document.getElementById("pvVideos"), empty = document.getElementById("videoEmpty"), pvSec = document.getElementById("pvVideosSec");
    if (!grid) return;
    if (empty) empty.hidden = items.length > 0;
    if (pvSec) pvSec.hidden = items.length === 0;
    // Autoplay muted when a card is mostly visible (skipped for reduced-motion users, who tap play instead).
    const io = new IntersectionObserver(entries => entries.forEach(en => {
      const m = en.target;
      if (en.isIntersecting && en.intersectionRatio >= .6) { if (!reduce && !m.querySelector("iframe") && m.offsetParent) load(m, m._v, true); }
      else if (!en.isIntersecting && m.querySelector("iframe")) { unload(m); if (active === m) active = null; }
    }), { threshold: [0, .6] });
    render(grid, items, io);
    if (pv) render(pv, items.slice(0, 3), io);
  };
  return { init };
})();
