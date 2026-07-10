/* =====================================================================
   ONCO ARCADE — Comportements partagés (v1.0)
   Thème (clair/sombre) + langue (FR/EN) persistants via localStorage.
   Auto-câblage : ajoutez  data-oa-theme-toggle  /  data-oa-lang-toggle
   à n'importe quel bouton, aucun JS spécifique par jeu n'est requis.
   Expose window.OncoArcade.
   ===================================================================== */
(function () {
  "use strict";
  var LS_THEME = "oa-theme";
  var LS_LANG  = "oa-lang";
  var root = document.documentElement;

  function getTheme() {
    try { return localStorage.getItem(LS_THEME); } catch (e) { return null; }
  }
  function getLang() {
    try { return localStorage.getItem(LS_LANG); } catch (e) { return null; }
  }

  function setTheme(theme, persist) {
    theme = theme === "dark" ? "dark" : "light";
    root.setAttribute("data-theme", theme);
    if (persist !== false) { try { localStorage.setItem(LS_THEME, theme); } catch (e) {} }
  }
  function setLang(lang, persist) {
    lang = lang === "en" ? "en" : "fr";
    root.setAttribute("lang", lang);
    if (persist !== false) { try { localStorage.setItem(LS_LANG, lang); } catch (e) {} }
    // Met à jour les libellés des boutons de langue
    document.querySelectorAll("[data-oa-lang-toggle]").forEach(function (b) {
      b.textContent = lang === "fr" ? "FR / EN" : "EN / FR";
    });
    document.dispatchEvent(new CustomEvent("oa:langchange", { detail: { lang: lang } }));
  }

  function toggleTheme() { setTheme(root.getAttribute("data-theme") === "dark" ? "light" : "dark"); }
  function toggleLang()  { setLang(root.getAttribute("lang") === "fr" ? "en" : "fr"); }

  /* ---- Toasts ---- */
  function toast(msg, kind, ms) {
    var wrap = document.querySelector(".oa-toasts");
    if (!wrap) { wrap = document.createElement("div"); wrap.className = "oa-toasts"; document.body.appendChild(wrap); }
    var t = document.createElement("div");
    t.className = "oa-toast" + (kind ? " " + kind : "");
    t.textContent = msg;
    wrap.appendChild(t);
    setTimeout(function () {
      t.style.transition = "opacity .3s ease";
      t.style.opacity = "0";
      setTimeout(function () { t.remove(); }, 320);
    }, ms || 2600);
  }

  /* ---- Initialisation ---- */
  function init() {
    // Thème : préférence stockée, sinon attribut HTML existant, sinon clair.
    var stored = getTheme();
    if (stored) setTheme(stored, false);
    else if (!root.getAttribute("data-theme")) setTheme("light", false);

    var lang = getLang();
    if (lang) setLang(lang, false);
    else setLang(root.getAttribute("lang") === "en" ? "en" : "fr", false);

    // Auto-câblage des boutons
    document.querySelectorAll("[data-oa-theme-toggle]").forEach(function (b) {
      b.addEventListener("click", toggleTheme);
    });
    document.querySelectorAll("[data-oa-lang-toggle]").forEach(function (b) {
      b.addEventListener("click", toggleLang);
    });
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();

  window.OncoArcade = {
    setTheme: setTheme,
    setLang: setLang,
    toggleTheme: toggleTheme,
    toggleLang: toggleLang,
    toast: toast
  };
})();
