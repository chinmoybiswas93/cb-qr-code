#!/usr/bin/env bash
#
# Capture everything cb-qr-code renders or returns, for before/after comparison.
#
#   tests/snapshot.sh <label>      -> tests/snapshots/<label>/
#   tests/compare.sh <a> <b>       -> diff, empty when nothing changed
#
# Needs a running site with cb-qr-code active and the fixtures described in
# tests/README.md. Every setting this script writes is deleted again at the end,
# leaving the plugin in its fresh-install state.
#
# Dev tool only — excluded from the release zip by .distignore.

set -euo pipefail

LABEL="${1:-}"
if [[ -z "$LABEL" ]]; then
    echo "usage: tests/snapshot.sh <label>" >&2
    exit 2
fi

for var in P82 CBQR_SOCK CBQR_WP_PATH CBQR_SITE CBQR_ADMIN_USER CBQR_ADMIN_PASS \
           CBQR_SUB_USER CBQR_SUB_PASS CBQR_POST_ID CBQR_PAGE_ID CBQR_LOGO_ID; do
    if [[ -z "${!var:-}" ]]; then
        echo "missing env var: $var (see tests/README.md)" >&2
        exit 2
    fi
done

HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OUT="$HERE/snapshots/$LABEL"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

rm -rf "$OUT"
mkdir -p "$OUT"

WP_PHAR="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp"
WP() {
    "$P82" -d mysqli.default_socket="$CBQR_SOCK" "$WP_PHAR" --path="$CBQR_WP_PATH" "$@" 2>/dev/null
}

AJAX_URL="$CBQR_SITE/wp-admin/admin-ajax.php"
ADMIN_PAGE="$CBQR_SITE/wp-admin/admin.php?page=cbqrcode_admin_dashboard"

# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

login() { # <user> <pass> <cookie-jar>
    curl -sk -c "$3" -b "wordpress_test_cookie=WP%20Cookie%20check" \
        --data-urlencode "log=$1" --data-urlencode "pwd=$2" \
        -d "wp-submit=Log+In" -d "testcookie=1" \
        --data-urlencode "redirect_to=$CBQR_SITE/wp-admin/" \
        -o /dev/null "$CBQR_SITE/wp-login.php"
    if ! grep -q "wordpress_logged_in_" "$3"; then
        echo "login failed for $1" >&2
        exit 1
    fi
}

# Nonce for a logged-in curl session, computed by WordPress itself. Needed for
# users who can't open the plugin page (the subscriber), so the request reaches
# the capability check rather than failing at the nonce.
nonce_for_session() { # <user-id> <cookie-jar>
    local cookie
    cookie="$(awk '$6 ~ /^wordpress_logged_in_/ {print $7}' "$2" | head -1)"
    CBQR_COOKIE="$cookie" WP eval '
        $_COOKIE[ LOGGED_IN_COOKIE ] = urldecode( getenv( "CBQR_COOKIE" ) );
        wp_set_current_user( '"$1"' );
        echo wp_create_nonce( "cbqrcode_ajax_nonce" );'
}

ajax() { # <name> <cookie-jar|-> <data>
    local jar=() code
    [[ "$2" != "-" ]] && jar=(-b "$2")
    code="$(curl -sk ${jar[@]+"${jar[@]}"} -o "$TMP/body" -w '%{http_code}' --data "$3" "$AJAX_URL")"
    { echo "HTTP $code"; cat "$TMP/body"; echo; } > "$OUT/$1.txt"
}

dump_options() { # <name>
    {
        echo "cbqrcode_settings:";   WP option get cbqrcode_settings   --format=json || echo "(absent)"
        echo "cbqrcode_post_types:"; WP option get cbqrcode_post_types --format=json || echo "(absent)"
    } > "$OUT/$1.txt"
}

set_scenario() { # s1|s2|s3
    WP option delete cbqrcode_settings   >/dev/null || true
    WP option delete cbqrcode_post_types >/dev/null || true
    [[ "$1" == "s1" ]] && return 0

    local logo_url mode="permalink" custom=""
    logo_url="$(WP eval "echo wp_get_attachment_url( $CBQR_LOGO_ID );")"
    if [[ "$1" == "s3" ]]; then
        mode="custom"
        custom="https://example.com/?a=1&b=2"
    fi

    WP option update cbqrcode_settings --format=json "$(cat <<JSON
{"cbqrcode-url-mode":"$mode","cbqrcode-custom-url":"$custom",
 "qr-code-label":"Test QR","qr-code-size":200,"qr-code-margin":4,
 "qr-code-dark":"1e3a8a","qr-code-light":"fef3c7",
 "qr-code-logo-id":$CBQR_LOGO_ID,"qr-code-logo-size":30,"qr-code-font-size":16,
 "qr-code-position":"left","qr-code-logo-url":"$logo_url"}
JSON
)" >/dev/null
    WP option update cbqrcode_post_types --format=json '["post","page"]' >/dev/null
}

capture_frontend() { # <scenario>
    local name url
    for name in post page home; do
        case "$name" in
            post) url="$CBQR_SITE/?p=$CBQR_POST_ID" ;;
            page) url="$CBQR_SITE/?page_id=$CBQR_PAGE_ID" ;;
            home) url="$CBQR_SITE/" ;;
        esac
        curl -sk -o "$TMP/page.html" "$url"
        "$P82" "$HERE/lib/extract.php" frontend "$TMP/page.html" > "$OUT/$1-frontend-$name.txt" 2>/dev/null
    done
}

capture_admin() { # <scenario>   sets ADMIN_NONCE
    curl -sk -b "$TMP/admin.jar" -o "$TMP/admin.html" "$ADMIN_PAGE"
    "$P82" "$HERE/lib/extract.php" admin "$TMP/admin.html" > "$OUT/$1-admin.txt" 2>/dev/null
    ADMIN_NONCE="$(grep -o 'CBQRCodeAjax = {[^}]*}' "$TMP/admin.html" | grep -o '"nonce":"[a-f0-9]*"' | cut -d'"' -f4)"
    if [[ -z "$ADMIN_NONCE" ]]; then
        echo "could not read CBQRCodeAjax.nonce from the admin page" >&2
        exit 1
    fi
}

capture_preview() { # <scenario>
    local form
    form="$("$P82" "$HERE/lib/extract.php" form "$TMP/admin.html" 2>/dev/null)"
    ajax "$1-preview" "$TMP/admin.jar" "$form&action=cbqrcode_preview&security=$ADMIN_NONCE"
}

# ---------------------------------------------------------------------------
# Run
# ---------------------------------------------------------------------------

login "$CBQR_ADMIN_USER" "$CBQR_ADMIN_PASS" "$TMP/admin.jar"
login "$CBQR_SUB_USER"   "$CBQR_SUB_PASS"   "$TMP/sub.jar"
SUB_ID="$(WP user get "$CBQR_SUB_USER" --field=ID)"
SUB_NONCE="$(nonce_for_session "$SUB_ID" "$TMP/sub.jar")"

for s in s1 s2 s3; do
    set_scenario "$s"
    dump_options "$s-options"
    capture_frontend "$s"
    capture_admin "$s"
    capture_preview "$s"
done

# Write paths, from a fresh state.
set_scenario s1
capture_admin s1-again >/dev/null
rm -f "$OUT/s1-again-admin.txt"

APPEARANCE="qr-code-label=Saved%20QR&qr-code-size=180&qr-code-margin=3&qr-code-dark=112233&qr-code-light=eeeeee&qr-code-logo-id=$CBQR_LOGO_ID&qr-code-logo-size=40&qr-code-font-size=14&qr-code-position=left"
SETTINGS="cbqrcode-url-mode=permalink&cbqrcode-custom-url=&cbqrcode-post-types%5B%5D=post&cbqrcode-post-types%5B%5D=page"

ajax w1-save-appearance "$TMP/admin.jar" "$APPEARANCE&action=cbqrcode_save_settings&tab=appearance&security=$ADMIN_NONCE"
dump_options w1-options
ajax w2-save-settings "$TMP/admin.jar" "$SETTINGS&action=cbqrcode_save_settings&tab=settings&security=$ADMIN_NONCE"
dump_options w2-options
ajax w3-custom-empty-url "$TMP/admin.jar" "cbqrcode-url-mode=custom&cbqrcode-custom-url=&cbqrcode-post-types%5B%5D=post&action=cbqrcode_save_settings&tab=settings&security=$ADMIN_NONCE"
ajax w4-bad-hex "$TMP/admin.jar" "${APPEARANCE/112233/zzz}&action=cbqrcode_save_settings&tab=appearance&security=$ADMIN_NONCE"
dump_options w4-options
ajax w5-bad-nonce-save    "$TMP/admin.jar" "$APPEARANCE&action=cbqrcode_save_settings&tab=appearance&security=0000000000"
ajax w6-bad-nonce-preview "$TMP/admin.jar" "$APPEARANCE&action=cbqrcode_preview&security=0000000000"
ajax w7-subscriber-save    "$TMP/sub.jar" "$APPEARANCE&action=cbqrcode_save_settings&tab=appearance&security=$SUB_NONCE"
ajax w8-subscriber-preview "$TMP/sub.jar" "$APPEARANCE&action=cbqrcode_preview&security=$SUB_NONCE"
ajax w9-logged-out-save     - "$APPEARANCE&action=cbqrcode_save_settings&tab=appearance&security=$ADMIN_NONCE"
ajax w10-logged-out-preview - "$APPEARANCE&action=cbqrcode_preview&security=$ADMIN_NONCE"
dump_options w10-options

# Back to fresh-install state.
set_scenario s1

echo "snapshot written: $OUT ($(ls "$OUT" | wc -l | tr -d ' ') files)"
