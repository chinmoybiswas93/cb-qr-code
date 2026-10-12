# 0 — Restructure onto Omnio's layout (executable plan)

> **Status: done** (PRs #1–#5, 2026-10-13). Every PR passed G1–G8, and `baseline == pr-0/a/b/c` with 32/32 identical captures each time. Two deviations from the text below are recorded in the PRs: `Settings` kept the `cbqrcode_`-prefixed method names (agent.md rule) instead of `current()` etc., and the `.pot` check compares against the strings in the 1.0.0 *code*, because the committed `.pot` turned out to be hand-written and stale.

| Tier | Depends on | Size |
|---|---|---|
| Foundation (before 1) | — | L — 1 docs PR + PR 0 + PRs A, B, C, merged one at a time |

## Goal

Move cb-qr-code onto the same shape as `omnio-image-optimizer`: `boot/`, `config/`, a slim
`framework/` kernel, and `app/` with one hook manifest. **Nothing a site or a third party can
observe changes.** The one deliberate exception is the AJAX guard in PR B (see Decisions). After
this, every later spec has an obvious home.

## Decisions (agreed 2026-10-13)

| Topic | Decision |
|---|---|
| PR flow | **Sequential.** Each PR branches from up-to-date `main` only after the previous one is merged |
| Order | docs PR → **PR 0** (PHP floor) → **A** (kernel) → **B** (handlers) → **C** (compat + removal) |
| Namespace | `ChinmoyBiswas\CBQRCode\App\` → `app/`, `ChinmoyBiswas\CBQRCode\Framework\` → `framework/` |
| Kernel | **Slim:** Application, Config, View (Omnio's, re-namespaced); Container with `bind/singleton/instance/alias/make/bound` and **no** reflection auto-wiring. No Router/Route/Policies until spec 18 |
| Hook timing | **Unchanged** in all PRs: `plugins_loaded` at priority 10, then `is_admin() ? admin handlers : frontend handler` |
| AJAX guard | **Unified in PR B** through `App\Http\AjaxGuard`: `check_ajax_referer('cbqrcode_ajax_nonce', 'security', false)`, then `manage_options`. Each handler keeps its **error shape** (save → `{errors:[…]}`, preview → `{message:…}`). The only intended behaviour change: the save handler now also fires core's `check_ajax_referer` action |
| Compat | **Permanent and silent.** `QRGenerator` is a real `class_alias`. `Admin`/`Frontend` become no-op singletons, since the manifest already registers the hooks. The 4 namespaced helper functions become one-line wrappers |
| PHP floor | **8.1** (PR 0). endroid 4.8.5 already requires it. Code stays **7.4-compatible in syntax**. Lint: PHP 8.2 on everything including `vendor/`, plus PHP 7.4 on our own code |
| Composer | PR 0: `config.platform.php` = `"8.1"`, `require.php` = `">=8.1"`, `composer update --lock --no-dev`. Then only `composer dump-autoload -o --no-dev`. Never `install`/`update` packages |
| Version | **No bump** in any of these PRs |
| Tests | Committed `tests/` (snapshot + compare scripts, browser checklist). `tests/snapshots/` is gitignored. `.distignore` keeps `tests/`, `context/` and dev files out of the zip |
| Docs | Each PR updates `agent.md` and the `context/` files for what it changed. PR C also updates the layout sections of `DEVELOPMENT_INSTRUCTIONS.md` and `README.md` |
| `.pot` | Regenerated in PR C. The set of msgids must be identical |
| Test site | Kaya QR deactivated while testing. Fixtures: post `cbqr-test-post`, page `cbqr-test-page`, an imported 64×64 test PNG, and user `cbqr-subscriber`. Admin login comes from env vars only (never committed). At the end: fixtures, subscriber and logo deleted, Kaya reactivated, **cb-qr-code left active** with its options deleted, i.e. a fresh-install state |

## Why the PHP floor comes first (PR 0)

Found while planning: `vendor/composer/platform_check.php` fatals below PHP 8.1, because
endroid/qr-code 4.8.5 requires `^8.1`. 21 endroid files don't parse on 7.4 (constructor
promotion). Reproduced:

```
php7.4 -r "require 'vendor/autoload.php';"
Fatal error: Composer detected issues in your platform: Your Composer dependencies require a PHP
version ">= 8.1.0". You are running 7.4.30.
```

So published 1.0.0 already needs 8.1, while `readme.txt` says 7.4. Also, `composer.lock` is
already stale on `main` (`composer validate` fails). A scratch-copy trial of PR 0's commands showed
the lock resolves to the **same versions**, and nothing pruned returns to `vendor/`. Only these
change: the lock's `content-hash`/platform fields, and `vendor/composer/installed.{json,php}`
root-package metadata. Logged as issue #12.

---

## Shared machinery

### Commands (`$P74`, `$P82`, `$WP` used throughout)

```bash
LS="$HOME/Library/Application Support/Local"
P74="$LS/lightning-services/php-7.4.30+6/bin/darwin-arm64/bin/php"
P82="$LS/lightning-services/php-8.2.27+1/bin/darwin-arm64/bin/php"
SOCK="$LS/run/aRpCXvFUz/mysql/mysqld.sock"            # cb-test — never another socket
WP() { "$P82" -d mysqli.default_socket="$SOCK" \
  /Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp \
  --path="/Users/chinmoy/Local Sites/cb-test/app/public" "$@"; }
```

### Gates — every PR runs all of them, in this order, and the PR description lists the results

| Gate | Check | Pass when |
|---|---|---|
| G1 lint | `$P82 -l` on every `.php` incl. `vendor/`; `$P74 -l` on every `.php` outside `vendor/` | 0 failures |
| G2 PHPCS | Plugin Check ruleset (command in `agent.md`), `--ignore='*/vendor/*,*/tests/*'` | 0 errors, 0 warnings |
| G3 composer | `composer validate --no-check-publish` (from PR 0 on) | no lock-file error |
| G4 autoload | `WP eval` asserting `class_exists`/`function_exists` for every class/function the PR adds, moves or aliases | all true |
| G5 snapshot | `tests/snapshot.sh <pr>` then `tests/compare.sh baseline <pr>` | **empty diff**. PR B: also empty, because the guard change isn't visible in responses |
| G6 browser | `tests/browser.sh <pr>` (runs `tests/browser/checklist.js` via `playwright-cli run-code`) | 34/34 checks pass, incl. "console has no errors"; screenshots match baseline on visual review |
| G7 log | Lines added to `wp-content/debug.log` during G5 + G6 that mention `cb-qr-code` / `cbqrcode` / `CBQRCode` | none |
| G8 security | Security review of the diff (PR B and C, which touch the AJAX paths) | no findings, or each one answered in the PR |

A failing gate stops the PR. I report the output, fix, and re-run **all** the gates. A partial pass
is never reported as done.

### Snapshot scenarios (`tests/snapshot.sh`)

Each scenario sets options through `WP option`, then captures. Captures are normalised: nonces,
`ver=` stay as-is (they must match), and per-request nonce values are masked.

| Scenario | State |
|---|---|
| S1 fresh | both options deleted |
| S2 saved | size 200, margin 4, dark `1e3a8a`, light `fef3c7`, label `Test QR`, logo = test PNG, logo-size 30, font 16, position `left`; post types `post,page`; URL mode `permalink` |
| S3 custom URL | S2 + mode `custom`, URL `https://example.com/?a=1&b=2` |

For each scenario:
- **Frontend:** the `cbqrcode`-related markup of `cbqr-test-post`, `cbqr-test-page` and the home
  page: the `.cbqrcode-qr` block, plus every `<link>`/`<script>` tag whose id or src contains
  `cbqrcode`.
- **Admin:** the `.cbqrcode-dashboard` markup of the settings page, plus the plugin's
  enqueued tags and the `CBQRCodeAjax` object with the nonce masked.
- **Preview:** the `cbqrcode_preview` JSON for that scenario's appearance values.

Write paths, run from S1 and restored afterwards:
- save appearance (valid) → response + options JSON
- save settings (valid) → response + options JSON
- custom mode with an empty URL → error JSON
- dark = `zzz` → error JSON
- bad nonce → save and preview
- `cbqr-subscriber` → save and preview
- logged out → save and preview

**Determinism check:** the baseline is captured twice (`baseline`, `baseline-2`), and the compare
must be empty before any PR starts. If it isn't, the normaliser gets fixed first.

### Browser checklist (`tests/browser/checklist.js`, run by `tests/browser.sh`)

1. Log in as admin, open **CB QR Code**. The page renders and the console has no errors.
2. All 4 tabs switch. Reload: the last tab is remembered.
3. Appearance: change the label → the preview updates. Change the dark colour → the preview
   updates.
4. Select Image opens the media modal. Pick the test PNG → the file name shows, Remove appears, and
   the preview has the logo. Remove → it's cleared.
5. Save Settings on Appearance → success notice. Reload → the values persist.
6. Settings tab: Custom URL shows the field. Save it empty → an error notice. Fill it and save →
   success. Reload → it persists. Switch back to Permalink and save.
7. Frontend `cbqr-test-post`: the QR shows on the correct side. Click it → the `copied` class
   flashes. No console errors.
8. Screenshots of steps 1, 3 and 7 go to `tests/snapshots/<label>/screens/`, for visual comparison
   with the baseline.

---

## Step 0 — environment + baseline (no code changes)

1. `WP plugin deactivate kaya-qr-code-generator`, then `WP plugin activate cb-qr-code`.
2. Create the fixtures:
   - `WP post create` for `cbqr-test-post` (post) and `cbqr-test-page` (page), published.
   - `WP user create cbqr-subscriber --role=subscriber`, with the password kept in the scratchpad
     only.
   - Generate a 64×64 PNG in the scratchpad and import it with `WP media import`.
   - Record all the IDs in the scratchpad.
3. Build `tests/snapshot.sh`, `tests/compare.sh`, `tests/lib/extract.php`, `tests/browser.sh` and
   `tests/browser/checklist.js` in the **working tree, without committing**. They get committed in
   PR A.
4. Capture `baseline` and `baseline-2` from the current `main` code. The determinism check must
   pass.
5. Run G1, G2, G6 and G7 on `main` and record the results. This is the "before" picture.
   G1 on `vendor/` with `$P74` is **expected to fail** — that's what PR 0 fixes.

### Step 0 results (2026-10-13, on `main` @ d65e8f2)

- Fixtures: post 108, page 109, logo 110 (imported PNG; Omnio converted it to WebP), subscriber 4.
  Permalinks are plain (`?p=108`, `?page_id=109`).
- `baseline` == `baseline-2`: 32 files, identical. The tampered-copy check makes `compare.sh` exit 1.
- Browser checklist: 34/34, five consecutive clean runs.
- G1: 8.2 lint, 159 files, 0 failures. 7.4 lint of our code, 12 files, 0 failures. Under 7.4,
  `vendor/` fails with 21 files (issue #12).
- G2: clean. G3: **fails** (stale lock, issue #12). G7: 0 new lines in `debug.log`.
- Harness fixes made while building it: the admin nonce is read from `CBQRCodeAjax` (the first
  `"nonce"` on the page is heartbeat's). The media modal reopens on the user's last tab, so the
  script clicks Media Library. The colour-preview check waits for the label's blur preview first,
  because of the race below.
- New defect found: issue #13 (preview responses aren't sequenced).

## Docs PR — `docs/specs-and-restructure-plan`

What's committed: `context/specs/` (all 20 files, including this plan), `progress-tracker.md`, the
current `agent.md` edits, and `issues.md` with new issues #12 (PHP floor + stale lock) and #13 (preview race).
No code. Gates: none. Merge before PR 0.

## PR 0 — `fix/php-floor-8-1`

**Changes**
1. `cb-qr-code.php` header: add `Requires at least: 6.0` and `Requires PHP: 8.1`. This closes issue
   #9; 6.0 matches `readme.txt`.
2. `readme.txt`: `Requires PHP: 8.1`.
3. `composer.json`: `require.php` `">=8.1"`, `config.platform.php` `"8.1"`.
4. `composer update --lock --no-dev --no-interaction`, then `composer dump-autoload -o --no-dev`.
   Then `git add -f vendor/composer/…` for the files that changed.
5. `agent.md` (Stack, Verify, Hard rules): floor 8.1, syntax stays 7.4-compatible, lint commands
   per G1. Update `code-standards.md` the same way. `issues.md`: close #9 and #12.

**Extra checks**
- `diff -rq` of `vendor/` before/after: only `vendor/composer/{installed.json,installed.php}`
  (plus autoload files, if any) may differ. Anything else stops the PR.
- `$P82 -r "require 'vendor/autoload.php';"` succeeds.
- `WP plugin activate` is still accepted on 8.2.

**Gates:** G1–G7 (G5 against `baseline`: empty).

## PR A — `refactor/restructure-a-kernel`

**Adds** (4-space indents and short arrays everywhere, an `ABSPATH` guard in every file, 7.4
syntax)
- `framework/Application.php`: paths/urls, loads `config/`, binds `view`, sets the `App` facade,
  requires `app/Hooks/actions.php`.
- `framework/Container.php`: slim (see Decisions). `make()` on an unbound key throws.
- `framework/Config.php`, `framework/View.php`: Omnio's code, re-namespaced and reformatted.
- `config/app.php` holds: name, slug, text domain, both option names, both AJAX actions, nonce
  action + field, admin page slug + hook, and the `cbqrcode_` prefix.
- `app/App.php`: the static facade (`make`, `singleton`, `instance`, `alias`, `bound`, `config`,
  `view`, `path`, `url`).
- `boot/app.php` returns `function ( $file ) { return new Application( $file ); }`. No
  activation/deactivation hooks, because the plugin has none today.
- `app/Hooks/actions.php` defines **`cbqrcode_init_plugin()`** (same global name, same body) and
  `add_action( 'plugins_loaded', 'cbqrcode_init_plugin' )`. Anything that `remove_action`s it keeps
  working.
- `tests/` (from Step 0) + `tests/README.md`; `.gitignore` gets `tests/snapshots/`.
- `.distignore`. `WORDPRESS_REPO_INSTRUCTIONS.md` gets a zip command that honours it.

**Changes**
- `cb-qr-code.php`: the header and constants are unchanged, and so is the `vendor/autoload.php`
  check with its notice. The old function and hook are replaced by
  `$cbqrcode_boot = require __DIR__ . '/boot/app.php'; $cbqrcode_boot( __FILE__ );`.
- `composer.json` psr-4: add `App\` and `Framework\` and **keep** `"ChinmoyBiswas\\CBQRCode\\": "includes/"`.
  `dump-autoload -o --no-dev`.
- `agent.md` Layout: add `boot/`, `config/`, `framework/`, `app/`, `tests/`, `.distignore`.

**Untouched:** `includes/`, `templates/`, `assets/`.

**Extra checks:** G4 for `App`, `Application`, `Container`, `Config`, `View`, plus
`cbqrcode_init_plugin` having `has_action('plugins_loaded') === 10`. The zip built per the new doc
command contains no `tests/`, `context/` or `*.md` except `readme.txt` (list it with `unzip -l`).

**Gates:** G1–G7.

## PR B — `refactor/restructure-b-handlers`

**Adds** (method names keep their `cbqrcode_` prefix; each handler has `register()`)

| New | Moved from | Notes |
|---|---|---|
| `app/Services/QRGenerator.php` | `includes/QRGenerator.php` | body verbatim |
| `app/Services/Settings.php` | `includes/helpers.php` | `current()`, `allowed_post_types()`, `field_definitions()`, `validate_fields()` — bodies verbatim, now pointing at the new `QRGenerator` |
| `app/Http/AjaxGuard.php` | the two inline checks | `verify( $error_key )` → `check_ajax_referer(…, false)` then `manage_options`; sends `{errors:[msg]}` or `{message:msg}` with today's exact strings |
| `app/Hooks/Handlers/MenuHandler.php` | `Admin::cbqrcode_add_admin_menu`, `cbqrcode_settings_page` | tabs render through `App::view()`; `$settings`/post types are passed in, not fetched by the view |
| `app/Hooks/Handlers/AdminAssetHandler.php` | `Admin::cbqrcode_enqueue_admin_assets` | URLs from `CBQRCODE_PLUGIN_URL` — **not** `plugin_dir_url(dirname(__FILE__))`, which would point into `app/Hooks/` after the move |
| `app/Hooks/Handlers/SettingsAjaxHandler.php` | save + both tab handlers | guard → the same sanitize/validate/save |
| `app/Hooks/Handlers/PreviewAjaxHandler.php` | preview | guard → the same body |
| `app/Hooks/Handlers/FrontendHandler.php` | `Frontend` | enqueue + `the_content`, verbatim |
| `app/Views/admin/{header,appearance,settings,about,support}.php` | `templates/` | markup verbatim; data comes from variables |

**Changes**
- `cbqrcode_init_plugin()` now calls the handlers' `register()` in the same order the old
  constructors used: `admin_menu`, `admin_enqueue_scripts`, `wp_ajax_cbqrcode_save_settings`,
  `wp_ajax_cbqrcode_preview` / `wp_enqueue_scripts`, `the_content`. Priorities stay the same.
- `includes/` and `templates/` stay on disk, **unused**, so this PR only adds code and switches
  over to it. PR C deletes them.
- `agent.md` Layout + Contracts note on the guard; `code-standards.md` security pattern →
  `AjaxGuard`.

**Extra checks**
- G4 for every new class.
- `has_action` priorities match the baseline, captured with `WP eval` on an admin and a frontend
  request.
- grep confirms nothing still `include`s `templates/` or instantiates `Admin`/`Frontend`.

**Gates:** G1–G8.

## PR C — `refactor/restructure-c-compat`

**Adds**
- `boot/compat.php` (a Composer `files` autoload):
  - `class_alias( App\Services\QRGenerator::class, 'ChinmoyBiswas\CBQRCode\QRGenerator' )`
  - `Admin` and `Frontend`: each has `get_instance()` returning a singleton that registers nothing
  - namespaced `cbqrcode_get_current_settings`, `cbqrcode_get_allowed_post_types`,
    `cbqrcode_get_field_definitions`, `cbqrcode_validate_fields` → `Settings` one-liners

**Removes:** `includes/` (all 4 files), `templates/` (incl. the unused `nav.php`, issue #11), and
the `includes/` psr-4 root. `files` becomes `["boot/compat.php"]`. Then `dump-autoload -o --no-dev`.

**Changes**
- `languages/cb-qr-code.pot`: `WP i18n make-pot . languages/cb-qr-code.pot --exclude=vendor,tests,context`.
  Check: the sorted msgid sets before and after are identical, so only `#:` references and the
  header change.
- Docs:
  - `agent.md` Layout: final shape, no `includes/` or `templates/`.
  - `project-overview.md` data flow.
  - Layout sections of `DEVELOPMENT_INSTRUCTIONS.md` and `README.md`.
  - `issues.md`: #11 partly closed (nav.php).
  - Tracker: spec 0 → done.

**Extra checks**
- G4 on the compat surface: `\ChinmoyBiswas\CBQRCode\QRGenerator::generate('x')` returns a
  `data:image/png` URI, all 4 functions return what `Settings` returns, and `Admin::get_instance()`
  doesn't add a second `admin_menu` callback (count before and after).
- `git ls-files includes templates` is empty.

**Gates:** G1–G8.

## Cleanup — after PR C merges

1. Delete the fixture post/page, `cbqr-subscriber` and the test PNG attachment.
2. `WP option delete cbqrcode_settings cbqrcode_post_types` (back to fresh-install state).
3. `WP plugin activate kaya-qr-code-generator`; cb-qr-code stays active.
4. Delete the scratchpad credentials file.

## Where each future spec lands

| Spec | Home |
|---|---|
| 1 Shortcode | `Services/Renderer.php` + `Handlers/ShortcodeHandler.php`; a `defaults()` added to `Settings` |
| 2 Block | `Handlers/BlockHandler.php` + `assets/blocks/qr-code/{block.json,index.js,index.asset.php}` |
| 3, 4, 7, 14 | `Services/QRGenerator.php` options + the Appearance view |
| 5 Per-post | `Handlers/MetaBoxHandler.php` + `Views/admin/metabox.php` |
| 6, 12 | `Services/DisplayConditions.php`, used by `FrontendHandler` |
| 8 Uninstall | `uninstall.php` |
| 9 Cache | `Services/Cache.php`; cleanup cron → `Hooks/Scheduler/` |
| 10 Dynamic QR | `Handlers/LinkPostTypeHandler.php`, `RedirectHandler.php`; activation hooks added to `boot/app.php` then |
| 11, 16 | `Services/Payload/*` |
| 13 Widget | `app/Widgets/QRWidget.php` |
| 15 WooCommerce | `app/Modules/WooCommerce/`, loaded only if `class_exists('WooCommerce')` |
| 17 Bulk | `Handlers/BulkPageHandler.php` + `Views/admin/bulk.php` |
| 18 Developer API | `boot/globals.php`; REST → port Omnio's `Router`/`Route`/Policies then |
| 19 Styles | `Services/Styles/` |

## Done when

- The plugin runs `boot/` → `framework/` → `app/Hooks/actions.php`, and `includes/` +
  `templates/` are gone.
- Every G5 diff against `baseline` is empty.
- Every gate passed on every PR, and each PR description records the results.
- Cleanup is done, and docs match the code.
