# Contributing to Onco Arcade

Thanks for your interest! Onco Arcade is an educational project — contributions that improve **accuracy, clarity and playability** are very welcome.

## Ways to contribute

- 🐛 **Report a technical bug** → open a *Bug report* issue.
- ⚕️ **Report a clinical inaccuracy** → open a *Clinical accuracy* issue. **This matters most** for a teaching tool.
- 💡 **Suggest a new game or improvement** → open an issue and describe the idea.
- 🔧 **Fix or improve code** → open a pull request.

## Ground rules for a medical education tool

- **Clinical accuracy comes first.** Any scenario must be concordant with current guidelines (Recorad, NCCN, ESTRO…) and may be simplified *without becoming wrong*. When in doubt, cite your reference in the issue or PR.
- Onco Arcade is **not a medical device** (see the disclaimer in the README). Contributions must keep the content strictly educational.

## Code conventions

- Each game is a **single, self-contained file** (`games/<slug>/index.html`) — no build step, no framework. Load an external library from a CDN only if truly unavoidable.
- Respect the **visual charter**:
  - system font stack;
  - refined font weights (**400–560**, *never* heavy 700/800 bold);
  - the shared color tokens (`--primary`, `--violet`, `--cyan`, …);
  - both **light and dark** themes.
- Keep everything **bilingual FR / EN**.
- **No secrets in code.** Database credentials live in `oa-config.php`, *above* the web root, and must never be committed (see `.gitignore`).

## Before opening a pull request

- Validate the JS: `node --check` on the game's main `<script>`.
- Test on **mobile Safari (iOS)** and **Chrome** — most layout bugs surface there first.
- Check **both light and dark** themes.
- Hard-refresh or use a private window when testing (browser cache hides changes).

## Licensing of contributions

By contributing, you agree that:

- your **code** contributions are licensed under the **MIT License** (`LICENSE`);
- your **content** contributions (scenarios, text, images) are licensed under **CC BY-NC-SA 4.0** (`LICENSE-content`).

Please do **not** submit material you don't have the right to license — copyrighted figures or images, verbatim guideline text, etc.

## Pull request process

1. Fork and create a branch from `main`.
2. Keep PRs **focused** (one game, one fix).
3. Describe what changed. For clinical content, **cite the guideline**.
4. Link any related issue.

## Questions

Open an issue or reach out via the repository. Thank you for helping make oncology education a little more fun.
