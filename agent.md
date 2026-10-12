# Agent Instructions — CB QR Code

You are working inside the **cb-qr-code** WordPress plugin: it appends a locally generated QR code
to singular posts/pages. Small, flat, no build step — PHP 7.4+, jQuery, plain CSS.

Everything lives under `app/public/wp-content/plugins/cb-qr-code/`. Text domain and slug are
`cb-qr-code`; the function/option/hook prefix is `cbqrcode_`.


## Stack

| | |
|---|---|
| PHP floor | **7.4** — no enums, readonly, constructor promotion, `match`, nullsafe `?->`, named args, union types |
| Autoload | Composer PSR-4 `ChinmoyBiswas\CBQRCode\` → `includes/`, plus `files: includes/helpers.php` |
| Runtime deps | `endroid/qr-code` 4.8.5 → `bacon/bacon-qr-code` 2.0.8, `dasprid/enum` 1.0.6. `PngWriter` needs **GD** |
| JS / CSS | Hand-written jQuery in `assets/js/`, plain CSS in `assets/css/`. No npm, no bundler |

**`vendor/` is committed and hand-pruned** (tests/docs removed for the WordPress.org zip) even
though `.gitignore` lists `/vendor/` — the files were force-added. A `composer install`/`update`
re-adds what was pruned and can bump versions. Don't run either unless a dependency change is the
task, and then `composer install --no-dev` and diff `vendor/` before committing.

## Layout

```
cb-qr-code.php        Header, CBQRCODE_PLUGIN_{PATH,URL,VERSION}, vendor/autoload.php,
                      plugins_loaded → is_admin() ? Admin : Frontend
includes/
  Admin.php           Menu page (manage_options), asset enqueue, 2 AJAX handlers
  Frontend.php        the_content filter → appends the QR block; enqueues style.css + script.js
  QRGenerator.php     Static: generate() → PNG data URI, hex_to_rgb(), logo/attachment checks
  helpers.php         Namespaced functions: settings getters, field definitions, validate_fields()
templates/            Admin tab partials included by Admin::cbqrcode_settings_page()
                      (nav.php is unused — Admin renders the tab buttons itself)
assets/js/            admin-script.js (save/preview/media), admin-tabs.js, script.js (click-to-copy)
languages/            cb-qr-code.pot
```

AJAX requests run with `is_admin()` true, so `Admin` owns them; `Frontend` never loads in
`admin-ajax.php`.

## Contracts — don't rename without a migration

| Thing | Value |
|---|---|
| Options | `cbqrcode_settings` (array), `cbqrcode_post_types` (array) — no tables, no transients |
| Settings keys | Hyphenated, identical to the form field names: `qr-code-size`, `cbqrcode-url-mode`, … Renaming a field renames a stored key |
| AJAX actions | `cbqrcode_save_settings`, `cbqrcode_preview` |
| Nonce | action `cbqrcode_ajax_nonce`, POST field `security`, JS global `CBQRCodeAjax.{ajax_url,nonce}` |
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
| `php-pro` | PHP **language** only, capped at 7.4. Ignore its Laravel/Symfony/PHPStan/PHPUnit advice |
| `wp-plugin-directory-guidelines` | `readme.txt`, plugin header, GPL, anything WordPress.org reviews |
| `owasp-security-review` | Auditing a whole file or subsystem |
| `/security-review` (built-in) | The pending diff — before calling a security-relevant change done |

## Hard rules

- Every AJAX handler: **nonce → capability (`manage_options`) → sanitize each field from
  `wp_unslash( $_POST[...] )` → validate → act.** Never pass `$_POST` (or any unsanitized array)
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
  `Stable tag` in `readme.txt` — plus the `templates/about.php` fallback and the `.pot` header.

## Verify before claiming anything works

Run what the change earns and say what you ran:

```bash
# Syntax at the PHP floor — Herd's `php` on PATH is 8.4 and will not catch 7.4 breaks
P74="$HOME/Library/Application Support/Local/lightning-services/php-7.4.30+6/bin/darwin-arm64/bin/php"
for f in cb-qr-code.php includes/*.php templates/*.php; do "$P74" -l "$f"; done

# Plugin Check's own PHPCS ruleset (phpcs is not on PATH; Plugin Check ships one)
P82="$HOME/Library/Application Support/Local/lightning-services/php-8.2.27+1/bin/darwin-arm64/bin/php"
"$P82" ../plugin-check/vendor/squizlabs/php_codesniffer/bin/phpcs \
  --standard=../plugin-check/phpcs-rulesets/plugin-review.xml \
  --extensions=php --ignore='*/vendor/*' -s .
```

Both were clean on 2026-10-13. Behaviour changes (save, preview, frontend output) need a browser
pass on `https://cb-test.local` with the plugin active — the code paths are AJAX and `the_content`,
neither of which a CLI check exercises.

Site traps (from the site-level `CLAUDE.md`): every Local DB is named `local`, cb-test's socket is
`aRpCXvFUz`; CLI PHP and the site's web PHP (8.2.27) have different extensions — check GD under web
PHP, not CLI, before concluding QR generation can't work.

Keep `context/issues.md` and `context/progress-tracker.md` current as things land. If something
fails, say so with the output; never describe partial work as complete.
