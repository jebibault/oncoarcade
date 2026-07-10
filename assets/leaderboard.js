/* =====================================================================
   ONCO ARCADE — Client Leaderboard (v1.0)
   Parle au backend PHP/MySQL hébergé sur OVH.
   Usage :
     OncoLeaderboard.config({ base: "/arcade/api", secret: "..." });
     await OncoLeaderboard.submit({ game:"beamlet-rush", name:"JEB", score:1240 });
     const rows = await OncoLeaderboard.top({ game:"beamlet-rush", period:"week", limit:10 });
     const glob = await OncoLeaderboard.top({ game:"global",       period:"week", limit:10 });

   NOTE HONNÊTE sur la sécurité :
   Un jeu 100% client ne peut PAS garantir l'intégrité des scores : tout ce qui
   tourne dans le navigateur est modifiable. La signature ci-dessous relève de
   l'"obfuscation" (elle décourage le curl trivial), pas de la cryptographie.
   La vraie défense est côté serveur : plafonds de score par jeu + limitation
   de débit (voir api/config.php et api/submit.php).
   ===================================================================== */
(function () {
  "use strict";

  var cfg = { base: "/arcade/api", secret: null, timeoutMs: 8000 };

  function config(opts) { Object.assign(cfg, opts || {}); return cfg; }

  function timeout(promise, ms) {
    return new Promise(function (resolve, reject) {
      var t = setTimeout(function () { reject(new Error("timeout")); }, ms);
      promise.then(function (v) { clearTimeout(t); resolve(v); },
                   function (e) { clearTimeout(t); reject(e); });
    });
  }

  // HMAC-SHA256 (obfuscation-grade) via SubtleCrypto si un secret est fourni.
  async function sign(payload) {
    if (!cfg.secret || !window.crypto || !window.crypto.subtle) return null;
    try {
      var enc = new TextEncoder();
      var key = await crypto.subtle.importKey(
        "raw", enc.encode(cfg.secret),
        { name: "HMAC", hash: "SHA-256" }, false, ["sign"]
      );
      var mac = await crypto.subtle.sign("HMAC", key, enc.encode(payload));
      return Array.prototype.map.call(new Uint8Array(mac),
        function (b) { return ("0" + b.toString(16)).slice(-2); }).join("");
    } catch (e) { return null; }
  }

  async function submit(opts) {
    var game = String(opts.game || "").trim();
    var name = String(opts.name || "").trim().slice(0, 24) || "ANON";
    var score = Math.max(0, Math.floor(Number(opts.score) || 0));
    var ts = Date.now();
    var body = { game: game, name: name, score: score, ts: ts };
    if (opts.meta) body.meta = opts.meta;
    var sig = await sign(game + "|" + name + "|" + score + "|" + ts);
    if (sig) body.sig = sig;

    var res = await timeout(fetch(cfg.base + "/submit.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body)
    }), cfg.timeoutMs);
    if (!res.ok) throw new Error("submit failed: " + res.status);
    return res.json(); // { ok, rank, best, total }
  }

  async function top(opts) {
    opts = opts || {};
    var q = new URLSearchParams({
      game: opts.game || "global",
      period: opts.period || "week",   // week | today | all
      limit: String(opts.limit || 10)
    });
    var res = await timeout(fetch(cfg.base + "/top.php?" + q.toString()), cfg.timeoutMs);
    if (!res.ok) throw new Error("top failed: " + res.status);
    return res.json(); // { ok, scope, period, rows:[{rank,name,score,games}] }
  }

  // Mémorise le pseudo du joueur pour préremplir les prochaines soumissions.
  function rememberName(name) { try { localStorage.setItem("oa-name", name); } catch (e) {} }
  function lastName() { try { return localStorage.getItem("oa-name") || ""; } catch (e) { return ""; } }

  window.OncoLeaderboard = {
    config: config,
    submit: submit,
    top: top,
    rememberName: rememberName,
    lastName: lastName
  };
})();
