# Agent Instructions — CB QR Code

You are working inside the **cb-qr-code** WordPress plugin: it appends a locally generated QR code
to singular posts/pages. Small, flat, no build step — PHP 8.1+ (written in 7.4-compatible syntax), jQuery, plain CSS.

Everything lives under `app/public/wp-content/plugins/cb-qr-code/`. Text domain and slug are
`cb-qr-code`; the function/option/hook prefix is `cbqrcode_`.


## Stack

| | |
|---|---|
| PHP floor | **8.1** — set by `endroid/qr-code` 4.8.5 (`^8.1`), whose Composer platform check refuses to load below it (issue #12). Header and readme both say `Requires PHP: 8.1` |
| PHP syntax | **7.4-compatible anyway** — no enums, readonly, constructor promotion, `match`, nullsafe `?->`, named args, union types. Keeps a later endroid downgrade possible |
| Autoload | Composer PSR-4: `ChinmoyBiswas\CBQRCode\App\` → `app/`, `…\Framework\` → `framework/`; plus `files: boot/compat.php` (the 1.0.0 names). After adding or moving a class: `composer dump-autoload -o --no-dev`, then `git add -f vendor/composer/` |
| Runtime deps | `endroid/qr-code` 4.8.5 → `bacon/bacon-qr-code` 2.0.8, `dasprid/enum` 1.0.6. `PngWriter` needs **GD** |
| JS / CSS | Hand-written jQuery in `assets/js/`, plain CSS in `assets/css/`. No npm, no bundler |

**`vendor/` is committed and hand-pruned** (tests/docs removed for the WordPress.org zip) even
though `.gitignore` lists `/vendor/` — the files were force-added. A `composer install`/`update`
re-adds what was pruned and can bump versions. Don't run either unless a dependency change is the
task, and then `composer install --no-dev` and diff `vendor/` before committing.

## Layout

```
cb-qr-code.php        Header, CBQRCODE_PLUGIN_{PATH,URL,VERSION}, vendor/autoload.php,
                      then boot/app.php
boot/app.php          Returns the boot closure → new Framework\Application( __FILE__ )
boot/compat.php       1.0.0 public names, permanent: QRGenerator class_alias, the 4
                      namespaced cbqrcode_* helper functions → Settings, and no-op
                      Admin/Frontend singletons. Composer "files" — loaded every request
config/app.php        Plain array via App::config()->get('app.…'): option names, AJAX
                      actions, nonce, admin page slug — all contracts
framework/            ChinmoyBiswas\CBQRCode\Framework\ — the slim kernel
  Application.php     Paths/URLs, loads config/, binds `view`, sets the App facade,
                      requires app/Hooks/actions.php
  Container.php       bind/singleton/instance/alias/make — no auto-wiring
  Config.php          Dot-notation access over config/*.php
  View.php            app/Views renderer (dot or slash names), realpath-guarded
app/                  ChinmoyBiswas\CBQRCode\App\
  App.php             Static facade: make/singleton/config/view/path/url
  Hooks/actions.php   THE registration manifest: cbqrcode_init_plugin() on plugins_loaded
                      → is_admin() ? the 4 admin handlers : FrontendHandler (same split,
                      order and priorities as 1.0.0)
  Hooks/Handlers/     One class per concern, each with register():
    MenuHandler           add_menu_page (manage_options) + renders the tabs via App::view()
    AdminAssetHandler     admin CSS/JS + CBQRCodeAjax, plugin page only
    SettingsAjaxHandler   cbqrcode_save_settings: guard → sanitize → validate → save
    PreviewAjaxHandler    cbqrcode_preview: guard → sanitize → render preview
    FrontendHandler       the_content → appends the QR block; enqueues style.css + script.js
  Http/AjaxGuard.php  Nonce then manage_options for every AJAX handler; caller picks the
                      JSON error shape ('errors' for save, 'message' for preview)
  Services/
    QRGenerator.php   Static: generate() → PNG data URI, hex_to_rgb(), logo/attachment checks
    Settings.php      Static: settings getters, field definitions, cbqrcode_validate_fields(),
                      cbqrcode_get_custom_url() / cbqrcode_is_http_url() (http/https only),
                      cbqrcode_get_defaults() — the only defaults source — and
                      cbqrcode_get_resolved_settings() (stored over defaults, clamped)
  Views/admin/        header, appearance, settings, about, support — data passed in by
                      MenuHandler ($settings, $post_types, $allowed_post_types)
assets/js/            admin-script.js (save/preview/media), admin-tabs.js, script.js (click-to-copy)
languages/            cb-qr-code.pot
uninstall.php         Deletes both options (per site on multisite). Every spec that stores
                      new data adds its cleanup here in the same PR (spec 8)
tests/                Snapshot/compare/browser harness — see tests/README.md. Not shipped
.distignore           What stays out of the WordPress.org zip
```

AJAX requests run with `is_admin()` true, so the admin handlers own them; `FrontendHandler`
never loads in `admin-ajax.php`.

## Contracts — don't rename without a migration

| Thing | Value |
|---|---|
| Options | `cbqrcode_settings` (array), `cbqrcode_post_types` (array) — no tables, no transients |
| Settings keys | Hyphenated, identical to the form field names: `qr-code-size`, `cbqrcode-url-mode`, … Renaming a field renames a stored key |
| AJAX actions | `cbqrcode_save_settings`, `cbqrcode_preview` |
| Nonce | action `cbqrcode_ajax_nonce`, POST field `security`, JS global `CBQRCodeAjax.{ajax_url,nonce}` (plus `i18n`, strings only — not a contract) |
| Public filters | `cbqrcode_before_qr_code`, `cbqrcode_after_qr_code` — `( $html, $post_type, $settings )`. Third-party API |
| Admin page | slug `cbqrcode_admin_dashboard`, hook `toplevel_page_cbqrcode_admin_dashboard` |
| CSS prefix | `cbqrcode-` |

## Context files

| Need | Where |
|---|---|
| What the plugin does, data flow, defaults | `context/project-overview.md` |
| Security pattern, PHP/JS style, i18n | `context/code-standards.md` — read before writing PHP |
| Known defects | `context/issues.md` — **read directly**, short |
| Current phase / what's in flight | `context/progress-tracker.md` — **read directly**, short |
| Planned features (one spec per PR) | `context/specs/` — read the one you're building, plus `specs/README.md` |
| Release / WordPress.org checks | `context/pre-release-checks.md` |

`DEVELOPMENT_INSTRUCTIONS.md` and `WORDPRESS_REPO_INSTRUCTIONS.md` are the older human docs. They
have drifted from the code in places (`code-standards.md` lists where); where they disagree with
`context/`, **the code and `context/` win**. `ISSUES.md` is empty — use `context/issues.md`.

## Research

The plugin's own code is ~1,100 lines across 4 classes, 6 templates and 3 scripts. **Read the files
directly** — there is no `graphify-out/` for this plugin, and never query another plugin's graph
for it. Never read into `vendor/` unless the question is about endroid's API.

## Skills

Project skills (`wordpress-pro`, `php-pro`, `owasp-security-review`,
`wp-plugin-directory-guidelines`) live in `app/public/.claude/skills/` and **only load when Claude
is started from `app/public`** — a session started inside this plugin folder does not see them.

| Skill | When |
|---|---|
| `wordpress-pro` | WordPress **API** questions: sanitize/escape choice, nonces, capabilities, hooks, i18n. This plugin *is* conventional (`add_menu_page`, `admin-ajax`, `wp_enqueue_*`), so its patterns mostly apply. Skip its theme/Gutenberg/WooCommerce references |
| `php-pro` | PHP **language** only, 7.4-compatible syntax (runtime floor 8.1). Ignore its Laravel/Symfony/PHPStan/PHPUnit advice |
| `wp-plugin-directory-guidelines` | `readme.txt`, plugin header, GPL, anything WordPress.org reviews |
| `owasp-security-review` | Auditing a whole file or subsystem |
| `/security-review` (built-in) | The pending diff — before calling a security-relevant change done |

## Hard rules

- Every AJAX handler: **`AjaxGuard::verify()` (nonce → capability `manage_options`) → sanitize
  each field from `wp_unslash( $_POST[...] )` → validate → act.** PHPCS can't see the guard's nonce
  check, so wrap the `$_POST` reads in a `phpcs:disable/enable WordPress.Security.NonceVerification.Missing`
  pair that names it — never a file-wide disable. Never pass `$_POST` (or any unsanitized array)
  into a method or helper; helpers never read superglobals.
- Escape at output, always — including strings returned in JSON that the JS injects with `.html()`.
- Never add an external API call or remote QR service. Local generation is the plugin's
  WordPress.org selling point and a guideline requirement.
- Never add a Composer dependency, and never add npm/a build step, without being asked.
- Never hand-edit `vendor/`.
- Don't change a contract from the table above unless the task is that change; a renamed settings
  key needs a read-fallback for the old one.
- Match the surrounding style (4-space indent, short arrays, `cbqrcode_`-prefixed method names).
  Don't reformat code you aren't otherwise changing.
- Version bumps touch **three** places together: the plugin header, `CBQRCODE_PLUGIN_VERSION`, and
  `Stable tag` in `readme.txt` — plus the `app/Views/admin/about.php` fallback and the `.pot` header.

## Verify before claiming anything works

Run what the change earns and say what you ran:

```bash
# Local has no PHP 8.1, so the floor is linted with 8.2 (lowest available) over everything,
# vendor/ included — and our own code with 7.4, which keeps it 7.4-compatible in syntax.
# Herd's `php` on PATH is 8.4; don't use it for either.
P74="$HOME/Library/Application Support/Local/lightning-services/php-7.4.30+6/bin/darwin-arm64/bin/php"
P82="$HOME/Library/Application Support/Local/lightning-services/php-8.2.27+1/bin/darwin-arm64/bin/php"
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 "$P82" -l | grep -v '^No syntax errors'
find . -name '*.php' -not -path './vendor/*' -not -path './.git/*' -print0 | xargs -0 -n1 "$P74" -l | grep -v '^No syntax errors'

# Composer metadata is consistent (lock matches composer.json)
composer validate --no-check-publish

# Plugin Check's own PHPCS ruleset (phpcs is not on PATH; Plugin Check ships one).
# tests/ is excluded: it isn't shipped, and its CLI scripts write to STDERR directly.
"$P82" ../plugin-check/vendor/squizlabs/php_codesniffer/bin/phpcs \
  --standard=../plugin-check/phpcs-rulesets/plugin-review.xml \
  --extensions=php --ignore='*/vendor/*,*/tests/*' -s .
```

Both were clean on 2026-10-13. Behaviour changes (save, preview, frontend output) need a browser
pass on `https://cb-test.local` with the plugin active — the code paths are AJAX and `the_content`,
neither of which a CLI check exercises.

Site traps (from the site-level `CLAUDE.md`): every Local DB is named `local`, cb-test's socket is
`aRpCXvFUz`; CLI PHP and the site's web PHP (8.2.27) have different extensions — check GD under web
PHP, not CLI, before concluding QR generation can't work. **OPcache:** after a `git checkout`, the
site's PHP-FPM keeps serving the previous branch's compiled code for a few seconds — wait (or
request twice) before capturing, or a before/after comparison silently compares the old code with
itself. `wp db query` doesn't get the socket and fails silently; use `wp option list --search='cbqrcode*'`.

Keep `context/issues.md` and `context/progress-tracker.md` current as things land. If something
fails, say so with the output; never describe partial work as complete.
