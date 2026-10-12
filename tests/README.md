# Tests

Before/after checks for changes that must not alter behaviour, plus a browser pass. They drive a
real site (cb-test), not a unit-test runner. Dev only: `.distignore` keeps this folder out of the
WordPress.org zip.

| Script | What it does |
|---|---|
| `snapshot.sh <label>` | Captures everything the plugin renders or returns into `snapshots/<label>/` (32 files). |
| `compare.sh <a> <b>` | `diff -ru` of two snapshots. Prints `identical: …` and exits 0, or prints the diff and exits 1. |
| `browser.sh <label>` | Runs `browser/checklist.js` through `playwright-cli run-code` (34 checks), with screenshots in `snapshots/<label>/screens/`. |
| `lib/extract.php` | Pulls the plugin-owned markup out of a page (`frontend`, `admin`) or serializes the Appearance form (`form`). |

`snapshots/` is gitignored. Its contents are site-specific: URLs, attachment IDs and PNG bytes.

## What a snapshot covers

The scenarios are set with `wp option update`:
- **s1:** fresh install.
- **s2:** non-default appearance with a logo.
- **s3:** s2 plus a custom URL with a query string.

For each scenario the snapshot records:
- the QR markup and the plugin's `<link>`/`<script>` tags on the fixture post, the fixture page and
  the home page
- the plugin's admin page markup, with nonces masked
- the `cbqrcode_preview` response for the Appearance form as rendered

Then the write paths, starting from a fresh install:
- valid Appearance save and valid Settings save, with the options after each
- custom URL mode with an empty URL
- an invalid hex colour
- a bad nonce, a subscriber, and a logged-out request, each against both endpoints

Each script deletes `cbqrcode_settings` and `cbqrcode_post_types` at the end, leaving the plugin in
its fresh-install state.

## Setup

**Site:** cb-test with cb-qr-code active and Kaya QR inactive, so another plugin's QR markup doesn't
end up in captures.

**Fixtures** (create once):
- a published post and a published page
- an imported image to use as the logo
- a subscriber user

**Environment variables** (keep them in a file outside the repo, never commit them):

```bash
export P82="$HOME/Library/Application Support/Local/lightning-services/php-8.2.27+1/bin/darwin-arm64/bin/php"
export CBQR_SOCK="$HOME/Library/Application Support/Local/run/aRpCXvFUz/mysql/mysqld.sock"  # cb-test only
export CBQR_WP_PATH="/Users/<you>/Local Sites/cb-test/app/public"
export CBQR_SITE="https://cb-test.local"
export CBQR_ADMIN_USER=…  CBQR_ADMIN_PASS=…
export CBQR_SUB_USER=…    CBQR_SUB_PASS=…
export CBQR_POST_ID=…  CBQR_PAGE_ID=…  CBQR_LOGO_ID=…
```

## Typical run

```bash
tests/snapshot.sh baseline      # on main, before the change
tests/snapshot.sh baseline-2    # determinism check
tests/compare.sh baseline baseline-2

# … make the change …
tests/snapshot.sh my-branch && tests/compare.sh baseline my-branch
tests/browser.sh my-branch
```

## Notes

- **Nonces.** The admin nonce is read from the `CBQRCodeAjax` object. The first `"nonce"` on the page
  belongs to heartbeat. The subscriber, who can't open the plugin page, gets a nonce computed by
  WordPress for their own session, so their requests reach the capability check.
- **`browser.sh` runs from a temp dir.** `playwright-cli` writes `.playwright-cli/` logs into the cwd
  and echoes the script back, credentials included. Only the result block is printed.
- **The colour-preview check waits for the label's blur preview first.** Preview responses aren't
  sequenced (`context/issues.md` #13).
