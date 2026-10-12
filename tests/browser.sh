#!/usr/bin/env bash
#
# Run tests/browser/checklist.js in a real browser via playwright-cli.
#
#   tests/browser.sh <label>   -> screenshots in tests/snapshots/<label>/screens/
#
# Same env vars as tests/snapshot.sh. Starts from, and returns to, a fresh
# install (both options deleted). Credentials only ever exist in a temp file
# that is deleted on exit.
#
# Dev tool only — excluded from the release zip by .distignore.

set -euo pipefail

LABEL="${1:?usage: tests/browser.sh <label>}"

for var in P82 CBQR_SOCK CBQR_WP_PATH CBQR_SITE CBQR_ADMIN_USER CBQR_ADMIN_PASS CBQR_POST_ID CBQR_LOGO_ID; do
    if [[ -z "${!var:-}" ]]; then
        echo "missing env var: $var (see tests/README.md)" >&2
        exit 2
    fi
done

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCREENS="$HERE/snapshots/$LABEL/screens"
SESSION="cbqr-$$"
TMP="$(mktemp -d)"

WP_PHAR="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp"
WP() {
    "$P82" -d mysqli.default_socket="$CBQR_SOCK" "$WP_PHAR" --path="$CBQR_WP_PATH" "$@" 2>/dev/null
}
reset_options() {
    WP option delete cbqrcode_settings   >/dev/null || true
    WP option delete cbqrcode_post_types >/dev/null || true
}
cleanup() {
    playwright-cli -s="$SESSION" close >/dev/null 2>&1 || true
    reset_options
    rm -rf "$TMP"
}
trap cleanup EXIT

rm -rf "$SCREENS"
mkdir -p "$SCREENS"
reset_options

json() { "$P82" -r 'echo json_encode($argv[1], JSON_UNESCAPED_SLASHES);' "$1"; }

{
    echo "async page => {"
    echo "  const CFG = {"
    echo "    site: $(json "$CBQR_SITE"), user: $(json "$CBQR_ADMIN_USER"), pass: $(json "$CBQR_ADMIN_PASS"),"
    echo "    postId: $CBQR_POST_ID, logoId: $CBQR_LOGO_ID, screens: $(json "$SCREENS")"
    echo "  };"
    echo "  return ("
    cat "$HERE/browser/checklist.js"
    echo "  )(page, CFG);"
    echo "}"
} > "$TMP/run.js"

# Run from the temp dir: playwright-cli writes .playwright-cli/ logs into the
# cwd, and echoes the script (credentials included) — only the result is shown.
cd "$TMP"
playwright-cli -s="$SESSION" open about:blank >/dev/null
playwright-cli -s="$SESSION" resize 1400 1000 >/dev/null
playwright-cli -s="$SESSION" run-code --filename="$TMP/run.js" > "$TMP/out.txt" 2>&1 || true
sed -n '/^### Result/,/^### Ran/p;/^### Error/,/^### /p' "$TMP/out.txt" | grep -v '^### Ran'

if grep -q '"failed": *0' "$TMP/out.txt" || grep -q '"failed":0' "$TMP/out.txt"; then
    echo "browser checklist: PASS ($LABEL)"
else
    echo "browser checklist: FAIL ($LABEL)" >&2
    exit 1
fi
