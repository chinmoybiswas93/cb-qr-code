<?php
/**
 * Pulls the plugin-owned parts out of a captured HTML page, so snapshots
 * compare only what cb-qr-code renders and not the theme or core around it.
 *
 * Usage: php extract.php <frontend|admin|form> <file.html>
 *
 *   frontend  every .cbqrcode-qr block, plus cbqrcode <link>/<script> tags
 *   admin     the .cbqrcode-dashboard markup, plus cbqrcode <link>/<script> tags
 *   form      the Appearance form serialized the way jQuery's serializeArray()
 *             does it in admin-script.js (minus action/tab), as a query string
 *
 * Dev tool only — not loaded by WordPress, excluded from the release zip.
 */

if ( PHP_SAPI !== 'cli' ) {
    exit;
}

$mode = $argv[1] ?? '';
$file = $argv[2] ?? '';

if ( ! in_array( $mode, [ 'frontend', 'admin', 'form' ], true ) || ! is_readable( $file ) ) {
    fwrite( STDERR, "usage: php extract.php <frontend|admin|form> <file.html>\n" );
    exit( 2 );
}

$doc = new DOMDocument();
libxml_use_internal_errors( true );
$doc->loadHTML( file_get_contents( $file ), LIBXML_NOERROR | LIBXML_NOWARNING );
libxml_clear_errors();
$xpath = new DOMXPath( $doc );

/**
 * Mask values that legitimately change between requests (nonces).
 */
function cbqr_mask( $html ) {
    return preg_replace( '/("nonce"\s*:\s*")[a-f0-9]+(")/', '$1NONCE$2', $html );
}

function cbqr_dump( DOMDocument $doc, DOMNodeList $nodes, $label ) {
    echo '## ' . $label . ' (' . $nodes->length . ")\n";
    foreach ( $nodes as $node ) {
        echo cbqr_mask( $doc->saveHTML( $node ) ) . "\n";
    }
}

$asset_query = "//link[contains(@id,'cbqrcode') or contains(@href,'cb-qr-code')]"
    . " | //script[contains(@id,'cbqrcode') or contains(@src,'cb-qr-code')]";

if ( 'frontend' === $mode ) {
    cbqr_dump( $doc, $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' cbqrcode-qr ')]" ), 'qr blocks' );
    cbqr_dump( $doc, $xpath->query( $asset_query ), 'assets' );
    exit( 0 );
}

if ( 'admin' === $mode ) {
    cbqr_dump( $doc, $xpath->query( "//div[contains(concat(' ', normalize-space(@class), ' '), ' cbqrcode-dashboard ')]" ), 'dashboard' );
    cbqr_dump( $doc, $xpath->query( $asset_query ), 'assets' );
    exit( 0 );
}

// form
$form = $xpath->query( "//form[@id='cbqrcode-appearance-form']" )->item( 0 );
if ( ! $form ) {
    fwrite( STDERR, "appearance form not found\n" );
    exit( 1 );
}

$pairs = [];
foreach ( $xpath->query( './/input | .//select', $form ) as $field ) {
    $name = $field->getAttribute( 'name' );
    if ( '' === $name || in_array( $name, [ 'action', 'tab' ], true ) ) {
        continue;
    }
    if ( 'select' === $field->nodeName ) {
        $value    = null;
        $options  = $xpath->query( './/option', $field );
        foreach ( $options as $option ) {
            if ( $option->hasAttribute( 'selected' ) ) {
                $value = $option->getAttribute( 'value' );
            }
        }
        if ( null === $value && $options->length ) {
            $value = $options->item( 0 )->getAttribute( 'value' );
        }
    } else {
        $value = $field->getAttribute( 'value' );
    }
    $pairs[] = rawurlencode( $name ) . '=' . rawurlencode( (string) $value );
}

echo implode( '&', $pairs );
