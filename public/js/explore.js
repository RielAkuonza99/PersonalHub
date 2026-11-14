/* Explore Portfolio: quick search across pages, projects, skills and journey. */
const Explore = (() => {
  const $ = id => document.getElementById(id);
  const PAGES = [["home", "Home"], ["about", "About"], ["skills", "Skills"], ["projects", "Projects"], ["videos", "Videos"], ["journey", "Journey"], ["contact", "Contact"]];
  let items = [], shown = [], sel = 0;
  const go = k => { location.hash = "#/" + k; };
  const build = () => {
    items = [
      ...PAGES.map(([k, l]) => ({ label: l, sub: "Page", run: () => go(k) })),
      ...PROJECTS.map(p => ({ label: p.name, sub: "Project: " + p.tech.join(", "), key: p.desc, run: () => { go("projects"); setTimeout(() => Projects.open(p), 350); } })),
      ...SKILLS.map(s => ({ label: s.name, sub: "Skill: " + s.cat, run: () => go("skills") })),
      ...JOURNEY.map(j => ({ label: j.title, sub: "Journey: " + j.year, key: j.text, run: () => go("journey") }))
    ];
  };
  const render = () => {
    const q = $("xInput").value.trim().toLowerCase(), ul = $("xList"); ul.innerHTML = "";
    shown = (q ? items.filter(i => (i.label + " " + i.sub + " " + (i.key || "")).toLowerCase().includes(q)) : items.slice(0, PAGES.length)).slice(0, 8);
    sel = 0;
    if (!shown.length) { ul.innerHTML = '<li class="note">No results. Try another keyword.</li>'; return; }
    shown.forEach((it, i) => {
      const li = document.createElement("li"), b = document.createElement("button");
      b.type = "button"; b.setAttribute("role", "option"); b.setAttribute("aria-selected", i === sel);
      b.innerHTML = "<span></span><small></small>"; b.children[0].textContent = it.label; b.children[1].textContent = it.sub;
      b.addEventListener("click", () => pick(it)); li.append(b); ul.append(li);
    });
  };
  const mark = () => $("xList").querySelectorAll("button").forEach((b, i) => b.setAttribute("aria-selected", i === sel));
  const pick = it => { $("explore").close(); it.run(); };
  const open = () => { if (document.querySelector("dialog[open]")) return; build(); $("xInput").value = ""; render(); $("explore").showModal(); $("xInput").focus(); };
  const init = () => {
    $("exploreBtn").addEventListener("click", open);
    $("xClose").addEventListener("click", () => $("explore").close());
    $("explore").addEventListener("click", e => { if (e.target === $("explore")) $("explore").close(); });
    $("xInput").addEventListener("input", render);
    $("xInput").addEventListener("keydown", e => {
      if (e.key === "ArrowDown" || e.key === "ArrowUp") { e.preventDefault(); if (shown.length) { sel = (sel + (e.key === "ArrowDown" ? 1 : -1) + shown.length) % shown.length; mark(); } }
      else if (e.key === "Enter" && shown[sel]) { e.preventDefault(); pick(shown[sel]); }
    });
    document.addEventListener("keydown", e => { if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "k") { e.preventDefault(); open(); } });
  };
  return { init, open };
})();
