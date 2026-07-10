# Onco Arcade 🎮

Free, browser-based mini-games to learn and revise **oncology, radiation therapy and medical physics** — one new game unlocked every week.

🌐 **Play:** https://oncoarcade.com

Onco Arcade turns a clinical gesture, a treatment decision or an everyday constraint into a fast, visual challenge: pilot a VMAT arc against the clock, validate a dosimetry plan, build a spread-out Bragg peak in proton therapy, place a prostate seed implant, clear a chemotherapy order under pressure, make the call at a tumor board, or explore a radiation oncology department in a career-progression game. It's arcade — with real medical substance.

> **The idea:** maybe we remember better when we play.

---

## The games

Released one per week during the season (see the in-page season calendar):

| # | Game | What you do |
|---|------|-------------|
| 1 | **GTV** | Contour the gross tumor volume |
| 2 | **Plan, Please** | Validate treatment plans (built on Recorad) — schemes & dose constraints |
| 3 | **OK Chimio / Fit for chemo** | Validate chemotherapy orders under pressure (14 regimens) |
| 4 | **MLC Ninja** | Shape the beam with the multileaf collimator |
| 5 | **Fraction Hero** | Fractionation & scheduling |
| 6 | **Seed Master** | Prostate LDR seed implant (TRUS-guided) |
| 7 | **Beamlet** | IMRT beamlet optimization |
| 8 | **Bragg Peak** | Build a spread-out Bragg peak (SOBP) in proton therapy |
| 9 | **Tumor Board** | RCP / MCQ — NCCN-concordant decisions (45,000+ procedurally generated cases) |
| 10 | **RadCraft** | Explore a radiotherapy department; progress from resident to department chief |

*(Arc Racer — a VMAT time-trial — is also included as an always-available game.)*

Scenarios are grounded in recommendations to stay realistic.

---

## How it works

- Each game is a **self-contained single-file** HTML/CSS/JS app (`games/<slug>/index.html`) — no build step, no framework, no dependencies.
- A lightweight **PHP + MySQL backend** (`api/*.php`) powers the shared **leaderboard**, team scores, star ratings and problem reports.
- A **season system** (in `index.html`) unlocks one game per week automatically, based on the visitor's date, and highlights the "game of the week".
- Bilingual **FR / EN** throughout, light/dark theme.

---

## Running & deploying

**Frontend (the games):** static files. Deploy the repository contents to your web root (e.g. `oncoarcade.com/`). No build needed. GitHub Pages can host the games statically — but the leaderboard needs PHP (see below).

**Leaderboard backend (optional):**
1. Copy `api/*.php` to a PHP host (e.g. OVH).
2. Create a MySQL database.
3. Create a config file **`oa-config.php` above the web root** (never inside it, never committed) returning an array:
   ```php
   <?php return [
     'host' => 'localhost',
     'name' => 'your_db',
     'user' => 'your_user',
     'pass' => 'your_password',
     'charset' => 'utf8mb4',
     'timezone' => 'Europe/Paris',
     'shared_secret' => '',   // must match the frontend; empty = open submissions
   ];
   ```
   `api/db.php` loads this file — **no credentials live in the code**.

**Configuration:** the season start date and reveal order are set at the top of the `oa-season` script in `index.html` (`SEASON_START`, `ORDER`). Preview any week with `?preview=all` or `?week=N`.

---

## Built with AI

Onco Arcade was designed and developed hand-in-hand with AI models (**Claude**). It's both a teaching tool and a small experiment in AI-assisted creation of medical educational content — every clinical scenario was reviewed against guidelines before release.

---

## License

This project is **dual-licensed**:

- **Source code** → **MIT** — see [`LICENSE`](LICENSE).
- **Content** (clinical scenarios, text, images) → **CC BY-NC-SA 4.0** — see [`LICENSE-content`](LICENSE-content).
  You may share and adapt the content for **non-commercial** use, with **attribution**, and must share derivatives under the **same license**.

## Brand

The **"Onco Arcade" name, logo and banner artwork are reserved** and are **not** covered by the licenses above. You are welcome to fork and reuse the code, but please do not present a modified version as the official Onco Arcade, and do not use the name or logo in a way that implies endorsement.

## ⚕️ Medical disclaimer

Onco Arcade is an **educational and experimental** project. The games are **not medical devices**, do **not** replace clinical training, and must **never** be used to guide real patient care. Clinical content is simplified for teaching.

---

## Author

Created by **Pr Jean-Emmanuel Bibault** — radiation oncologist & AI researcher (Hôpital Européen Georges-Pompidou, Université Paris Cité).

Feedback and issues welcome via the repository's Issues tab.
