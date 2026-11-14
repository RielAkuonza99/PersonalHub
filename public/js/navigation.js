const Nav = (() => {
  const init = () => {
    const header = document.getElementById("nav"), menu = document.getElementById("menu"), burger = document.getElementById("burger");
    const setOpen = o => { menu.classList.toggle("open", o); burger.setAttribute("aria-expanded", o); };
    burger.addEventListener("click", () => setOpen(!menu.classList.contains("open")));
    menu.addEventListener("click", e => { if (e.target.tagName === "A") setOpen(false); });
    document.addEventListener("keydown", e => { if (e.key === "Escape") setOpen(false); });
    const onScroll = () => header.classList.toggle("scrolled", scrollY > 10);
    addEventListener("scroll", onScroll, { passive: true }); onScroll();

    // Page router: #/home, #/about, #/skills, #/projects, #/journey, #/contact
    const pages = [...document.querySelectorAll(".page")], links = [...menu.querySelectorAll("a")];
    let cur = null, timer;
    const enter = next => {
      pages.forEach(p => { p.classList.remove("leave"); p.hidden = p !== next; });
      next.classList.add("enter"); scrollTo({ top: 0, behavior: "instant" });
      void next.offsetWidth; next.classList.remove("enter"); cur = next;
    };
    const route = animate => {
      const m = location.hash.match(/^#\/(\w+)/);
      if (!m && cur) return;
      const name = m && pages.some(p => p.dataset.page === m[1]) ? m[1] : "home";
      links.forEach(a => a.classList.toggle("active", a.getAttribute("href") === "#/" + name));
      const next = pages.find(p => p.dataset.page === name);
      if (next === cur) return;
      clearTimeout(timer); pages.forEach(p => p.classList.remove("leave"));
      if (cur && animate) { cur.classList.add("leave"); timer = setTimeout(() => enter(next), 220); }
      else enter(next);
    };
    addEventListener("hashchange", () => route(true)); route(false);
  };
  return { init };
})();
