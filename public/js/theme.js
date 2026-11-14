const Theme = (() => {
  const root = document.documentElement;
  const apply = t => { root.dataset.theme = t; try { localStorage.setItem("theme", t); } catch (e) {} };
  const init = () => {
    let saved = null; try { saved = localStorage.getItem("theme"); } catch (e) {}
    if (!saved && matchMedia("(prefers-color-scheme: dark)").matches) root.dataset.theme = "dark";
    document.getElementById("themeBtn").addEventListener("click", () => apply(root.dataset.theme === "dark" ? "light" : "dark"));
  };
  return { init };
})();
