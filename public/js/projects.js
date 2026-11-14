const Projects = (() => {
  const $ = id => document.getElementById(id);
  const state = { cat: "All", q: "" };
  const PH = { web: "assets/images/project-placeholder.svg", design: "assets/images/design-placeholder.svg", app: "assets/images/app-placeholder.svg" };
  const chips = (el, items) => { el.innerHTML = ""; items.forEach(t => { const li = document.createElement("li"); li.textContent = t; el.append(li); }); };
  const filterBar = (el, pairs, current, onPick) => {
    el.innerHTML = "";
    pairs.forEach(([key, label]) => {
      const b = document.createElement("button");
      b.textContent = label; b.className = "chip-btn"; b.setAttribute("aria-pressed", key === current);
      b.addEventListener("click", () => onPick(key)); el.append(b);
    });
  };

  const skillEl = s => {
    const d = document.createElement("div"); d.className = "skill";
    d.innerHTML = `<div class="skill-top"><strong></strong><span>${s.level}%</span></div><div class="bar" role="progressbar" aria-valuenow="${s.level}" aria-valuemin="0" aria-valuemax="100"><i style="width:${s.level}%"></i></div>`;
    d.querySelector("strong").textContent = s.name; return d;
  };

  // Fixed-ratio media box: web 16:9, design 4:5, app 9:16. Image or video fills it without resizing the box.
  const fillMedia = (el, p, withVideo) => {
    const type = PH[p.type] ? p.type : "web";
    el.dataset.type = type; el.innerHTML = "";
    if (withVideo && p.videoEmbed) {
      const f = document.createElement("iframe");
      f.src = p.videoEmbed; f.title = p.name; f.loading = "lazy"; f.allowFullscreen = true; f.setAttribute("allow", "autoplay; fullscreen; picture-in-picture; encrypted-media"); f.referrerPolicy = "strict-origin-when-cross-origin"; el.append(f);
    } else if (withVideo && p.video) {
      const v = document.createElement("video");
      v.src = p.video; v.controls = true; v.playsInline = true; v.preload = "metadata"; if (p.image) v.poster = p.image;
      v.style.objectFit = p.videoCrop === "cover" ? "cover" : "contain";
      v.addEventListener("loadedmetadata", () => { if (p.videoStart > 0) v.currentTime = p.videoStart; });
      if (p.videoEnd > 0) v.addEventListener("timeupdate", () => { if (v.currentTime >= p.videoEnd) { v.pause(); v.currentTime = p.videoStart || 0; } });
      el.append(v);
    } else {
      const i = document.createElement("img");
      i.src = p.image || PH[type]; i.alt = ""; i.loading = "lazy";
      i.style.objectPosition = p.imageCrop === "top" ? "center top" : p.imageCrop === "bottom" ? "center bottom" : "center";
      el.append(i);
    }
  };

  const card = p => {
    const a = document.createElement("article"); a.className = "card";
    a.innerHTML = `<div class="media-wrap"><div class="media"></div></div><div class="card-body"><h3></h3><p></p><ul class="chips"></ul><button class="btn sm">View details</button></div>`;
    fillMedia(a.querySelector(".media"), p, false);
    a.querySelector("h3").textContent = p.name; a.querySelector("p").textContent = p.desc;
    chips(a.querySelector(".chips"), p.tech);
    a.querySelector("button").addEventListener("click", () => open(p));
    return a;
  };

  const renderSkills = (cat = "All") => {
    filterBar($("skillFilters"), SKILL_CATEGORIES, cat, renderSkills);
    const grid = $("skillGrid"); grid.innerHTML = "";
    SKILLS.filter(s => cat === "All" || s.cat === cat).forEach(s => grid.append(skillEl(s)));
  };

  const renderProjects = () => {
    const cats = ["All", ...new Set(PROJECTS.map(p => p.cat))];
    filterBar($("projFilters"), cats.map(c => [c, c]), state.cat, c => { state.cat = c; renderProjects(); });
    const q = state.q.trim().toLowerCase();
    const list = PROJECTS.filter(p => (state.cat === "All" || p.cat === state.cat) &&
      (!q || (p.name + " " + p.cat).toLowerCase().includes(q)));
    const grid = $("projGrid"); grid.innerHTML = "";
    list.forEach(p => grid.append(card(p)));
    $("projEmpty").hidden = list.length > 0;
  };

  function open(p) {
    fillMedia($("mMedia"), p, true);
    $("mTitle").textContent = p.name; $("mDesc").textContent = p.desc + " " + p.detail;
    chips($("mTech"), p.tech);
    const l = $("mLinks"); l.innerHTML = "";
    [["GitHub", p.github], ["Live demo", p.demo]].forEach(([t, u]) => {
      if (!u) return; const a = document.createElement("a");
      a.className = "btn sm"; a.href = u; a.target = "_blank"; a.rel = "noopener noreferrer"; a.textContent = t; l.append(a);
    });
    if (!l.children.length) l.textContent = "No public links yet.";
    $("modal").showModal();
  }

  const init = () => {
    renderSkills(); renderProjects();
    SKILLS.slice(0, 4).forEach(s => $("pvSkills").append(skillEl(s)));
    PROJECTS.slice(0, 3).forEach(p => $("pvProjects").append(card(p)));
    $("projSearchForm").addEventListener("submit", e => { e.preventDefault(); state.q = $("projSearch").value.trim(); renderProjects(); });
    $("projReset").addEventListener("click", () => { state.cat = "All"; state.q = ""; $("projSearch").value = ""; renderProjects(); });
    const m = $("modal");
    $("mClose").addEventListener("click", () => m.close());
    m.addEventListener("click", e => { if (e.target === m) m.close(); });
    m.addEventListener("close", () => { $("mMedia").innerHTML = ""; });
  };
  return { init, open };
})();
