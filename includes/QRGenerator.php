<?php
namespace CBQRCode;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;

class QRGenerator
{
    /**
     * Generate QR code and return data URI
     *
     * @param string $text Text to encode
     * @param array $options Generation options
     * @return string Data URI of the generated QR code
     */
    public static function generate($text, $options = [])
    {
        try {
            $size = $options['size'] ?? 300;
            $margin = $options['margin'] ?? 10;
            $foregroundColor = $options['foreground'] ?? [0, 0, 0];
            $backgroundColor = $options['background'] ?? [255, 255, 255];
            $logoPath = $options['logo'] ?? '';
            $logoSize = $options['logo_size'] ?? 50;

            // Ensure we have a valid text
            if (empty($text)) {
                return '';
            }

            $builder = Builder::create()
                ->writer(new PngWriter())
                ->data($text)
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelLow())
                ->size($size)
                ->margin($margin)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->foregroundColor(new Color($foregroundColor[0], $foregroundColor[1], $foregroundColor[2]))
                ->backgroundColor(new Color($backgroundColor[0], $backgroundColor[1], $backgroundColor[2]));

            if (!empty($logoPath) && file_exists($logoPath)) {
                $builder->logoPath($logoPath)
                        ->logoResizeToWidth($logoSize);
            }

            // Note: Label is handled in HTML, not in QR code generation

            $result = $builder->build();
            $dataUri = $result->getDataUri();
            
            return $dataUri;
        } catch (\Exception $e) {
            // Log error for debugging
            error_log('QR Code Generation Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Convert hex color to RGB array
     *
     * @param string $hex Hex color code
     * @return array RGB values
     */
    public static function hex_to_rgb($hex)
    {
        $hex = ltrim($hex, '#');
        
        // Ensure we have a 6-character hex code
        if (strlen($hex) === 3) {
            // Convert 3-char hex to 6-char (e.g., 'f0f' -> 'ff00ff')
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        } elseif (strlen($hex) !== 6) {
            // Invalid hex, default to black
            $hex = '000000';
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Download and cache logo from URL
     *
     * @param string $url Logo URL
     * @return string Local path to logo or empty string
     */
    public static function download_logo($url)
    {
        if (empty($url)) return '';
        
        $upload_dir = wp_upload_dir();
        $logo_dir = $upload_dir['basedir'] . '/qr-logos/';
        $filename = sanitize_file_name(basename($url));
        $local_path = $logo_dir . $filename;
        
        // Create directory if it doesn't exist
        if (!file_exists($logo_dir)) {
            wp_mkdir_p($logo_dir);
        }
        
        // Download logo if not cached
        if (!file_exists($local_path)) {
            $response = wp_remote_get($url, ['timeout' => 30]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $body = wp_remote_retrieve_body($response);
                if (!empty($body)) {
                    file_put_contents($local_path, $body);
                }
            }
        }
        
        return file_exists($local_path) ? $local_path : '';
    }
}
