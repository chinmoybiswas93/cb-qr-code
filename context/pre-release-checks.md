# Pre-release checks

Things to settle before a WordPress.org upload. Not defects — those are in [issues.md](issues.md).
An entry is settled when it has been **run**, not reasoned about.

| # | Check | How |
|---|---|---|
| 1 | Version agrees in all places | Plugin header `Version`, `CBQRCODE_PLUGIN_VERSION`, readme `Stable tag`, the `about.php` fallback, the `.pot` header. Diff against `../cb-qr-code-org/` (the live 1.0.0) to see what actually changed since publishing |
| 2 | `Tested up to` is current | cb-test runs WordPress 7.1.3; the readme says 6.8 |
| 3 | PHP lint | Both loops in `agent.md` — 8.2 over everything incl. `vendor/`, 7.4 over our own code. Local's binaries, not Herd's 8.4 |
| 4 | Plugin Check, code sniffs | PHPCS command in `agent.md` (clean on 2026-10-13) |
| 5 | Plugin Check, full run on the **zip** | Readme parsing, headers and file scans only run through the plugin itself. Extract the zip on a throwaway site, or into a separate folder — **never over this git working tree**, WordPress deletes the folder before unpacking |
| 6 | Zip excludes dev files | `WORDPRESS_REPO_INSTRUCTIONS.md` strips `.git*` and `*.md`, which covers `agent.md` and `CLAUDE.md` — also remove `context/` and `.claude/`. |
| 7 | `vendor/` is the pruned tree | `git status vendor/` clean; no `tests/`/`docs/` under `vendor/*/*` |
| 8 | QR renders under web PHP | GD is required by `PngWriter`. Confirm with the plugin active on cb-test — a CLI `php -m` says nothing about php-fpm |
| 9 | Browser pass | Save each tab, preview updates, logo select/remove, QR on an allowed post type, click-to-copy, a post with a non-ASCII slug (issue 1) |
