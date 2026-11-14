const UI = (() => {
  const $ = id => document.getElementById(id);
  const toast = msg => {
    const t = document.createElement("div"); t.className = "toast"; t.textContent = msg; $("toasts").append(t);
    setTimeout(() => t.remove(), 4000);
  };

  const typing = () => {
    const el = $("role"); let i = 0, c = 0, del = false;
    if (matchMedia("(prefers-reduced-motion: reduce)").matches) { el.textContent = SITE.roles[0]; return; }
    const tick = () => {
      const w = SITE.roles[i];
      c += del ? -1 : 1; el.textContent = w.slice(0, c);
      let d = del ? 35 : 70;
      if (!del && c === w.length) { del = true; d = 1600; }
      else if (del && c === 0) { del = false; i = (i + 1) % SITE.roles.length; d = 350; }
      setTimeout(tick, d);
    };
    tick();
  };

  const reveal = () => {
    const els = document.querySelectorAll(".reveal");
    if (!("IntersectionObserver" in window)) return els.forEach(e => e.classList.add("in"));
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add("in"); io.unobserve(e.target); } }), { threshold: 0.08 });
    els.forEach(e => io.observe(e));
  };

  const cursor = () => {
    if (!matchMedia("(hover: hover) and (pointer: fine)").matches) return;
    const c = $("cursor"); document.body.classList.add("has-cursor");
    addEventListener("pointermove", e => { c.style.transform = `translate(${e.clientX}px,${e.clientY}px)`; });
    document.addEventListener("pointerover", e => c.classList.toggle("big", !!e.target.closest("a,button,input,textarea")));
  };

  const form = () => {
    const f = $("form"), btn = $("submitBtn");
    const rules = {
      name: v => (v.trim().length >= 2 && v.trim().length <= 120) || "Enter a name between 2 and 120 characters.",
      email: v => (v.length <= 190 && /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) || "Enter a valid email address, like name@example.com.",
      message: v => (v.trim().length >= 10 && v.trim().length <= 5000) || "Write a message between 10 and 5,000 characters."
    };
    const check = el => {
      const r = rules[el.name](el.value), ok = r === true;
      el.setAttribute("aria-invalid", !ok); el.parentElement.querySelector(".err").textContent = ok ? "" : r; return ok;
    };
    f.querySelectorAll("input,textarea").forEach(el => el.addEventListener("blur", () => check(el)));
    f.addEventListener("submit", e => {
      e.preventDefault();
      const ok = [...f.querySelectorAll("input,textarea")].map(check).every(Boolean);
      if (!ok) return toast("Fix the highlighted fields.");
      btn.classList.add("loading"); btn.disabled = true;
      f.setAttribute("aria-busy", "true");
      btn.setAttribute("aria-label", "Sending message");
      const d = new FormData(f);
      const mailto = () => {
        const body = `${d.get("message")}\n\n— ${d.get("name")} (${d.get("email")})`;
        location.href = `mailto:${SITE.email}?subject=${encodeURIComponent("Message from portfolio")}&body=${encodeURIComponent(body)}`;
        toast("Could not send directly. Your email app should open instead.");
      };
      fetch(new URL("contact.php", API_PUBLIC), { method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ name: d.get("name"), email: d.get("email"), message: d.get("message") }) })
        .then(r => { if (r.status === 429) toast("Too many messages. Please try again later."); else if (!r.ok) throw new Error(r.status); else { f.reset(); toast("Message sent. Thank you!"); } })
        .catch(mailto)
        .finally(() => { btn.classList.remove("loading"); btn.disabled = false; f.removeAttribute("aria-busy"); btn.removeAttribute("aria-label"); });
    });
  };

  // Loading screen: progress bar follows real boot steps, then eases into the page.
  const loader = (() => {
    let target = 6, p = 0, t0 = 0, stopped = false;
    const reduceMotion = matchMedia("(prefers-reduced-motion: reduce)").matches;
    const el = () => $("loader");
    const finish = () => {
      el().classList.add("done"); document.documentElement.classList.remove("loading");
    };
    const frame = () => {
      if (stopped) return;
      p = reduceMotion ? target : Math.min(target, p + Math.max((target - p) * 0.07, target > p ? 0.2 : 0));
      $("ldBar").style.transform = `scaleX(${p / 100})`; $("ldPct").textContent = Math.round(p) + "%";
      $("ldBar").parentElement.setAttribute("aria-valuenow", String(Math.round(p)));
      if (p >= 99.5 && performance.now() - t0 > 1400) return finish();
      requestAnimationFrame(frame);
    };
    return {
      start() { t0 = performance.now(); requestAnimationFrame(frame); },
      progress(n, msg) { target = Math.min(100, Math.max(target, n)); if (msg) $("ldMsg").textContent = msg; },
      fail(msg) {
        stopped = true; $("ldPct").textContent = "Error"; $("ldMsg").textContent = msg;
        $("ldBar").parentElement.setAttribute("aria-valuetext", "Loading failed");
      }
    };
  })();

  const init = () => {
    typing(); reveal(); cursor(); form();
    $("toTop").addEventListener("click", () => scrollTo({ top: 0, behavior: "smooth" }));
    const cv = $("cvBtn");
    if (SITE.cv) { cv.href = SITE.cv; if (/^https?:/i.test(SITE.cv)) { cv.target = "_blank"; cv.rel = "noopener noreferrer"; } else cv.setAttribute("download", ""); }
    else cv.addEventListener("click", e => { e.preventDefault(); toast("CV not added yet. Add a CV in the dashboard."); });
  };
  return { init, toast, loader };
})();
