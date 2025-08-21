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
    public static function generate($text, $options = [])
    {
        try {
            $size = $options['size'] ?? 300;
            $margin = $options['margin'] ?? 10;
            $foregroundColor = $options['foreground'] ?? [0, 0, 0];
            $backgroundColor = $options['background'] ?? [255, 255, 255];
            $logoPath = $options['logo'] ?? '';
            $logoSize = $options['logo_size'] ?? 50;

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

            $result = $builder->build();
            $dataUri = $result->getDataUri();
            
            return $dataUri;
        } catch (\Exception $e) {
            return '';
        }
    }

    public static function hex_to_rgb($hex)
    {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        } elseif (strlen($hex) !== 6) {
            $hex = '000000';
        }
        
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    public static function download_logo($url)
    {
        if (empty($url)) return '';
        
        $upload_dir = wp_upload_dir();
        $logo_dir = $upload_dir['basedir'] . '/qr-logos/';
        $filename = sanitize_file_name(basename($url));
        $local_path = $logo_dir . $filename;
        
        if (!file_exists($logo_dir)) {
            wp_mkdir_p($logo_dir);
        }
        
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
