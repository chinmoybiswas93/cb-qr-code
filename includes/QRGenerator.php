<?php
namespace ChinmoyBiswas\CBQRCode;

if ( ! defined( 'ABSPATH' ) ) exit; 

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

            if (empty($text) || !is_string($text)) {
                return '';
            }
            

            $text = sanitize_text_field($text);
            

            $size = isset($options['size']) ? max(50, min(1000, absint($options['size']))) : 300;
            $margin = isset($options['margin']) ? max(0, min(20, absint($options['margin']))) : 10;
            $foregroundColor = isset($options['foreground']) && is_array($options['foreground']) ? $options['foreground'] : [0, 0, 0];
            $backgroundColor = isset($options['background']) && is_array($options['background']) ? $options['background'] : [255, 255, 255];
            $logoPath = isset($options['logo']) ? sanitize_text_field($options['logo']) : '';
            $logoSize = isset($options['logo_size']) ? max(10, min(100, absint($options['logo_size']))) : 50;


            foreach (['foregroundColor', 'backgroundColor'] as $color_var) {
                $color = $$color_var;
                if (!is_array($color) || count($color) !== 3) {
                    $$color_var = ($color_var === 'foregroundColor') ? [0, 0, 0] : [255, 255, 255];
                } else {

                    $$color_var = array_map(function($val) {
                        return max(0, min(255, absint($val)));
                    }, $color);
                }
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


            if (!empty($logoPath) && file_exists($logoPath) && is_readable($logoPath)) {

                $image_info = @getimagesize($logoPath);
                if ($image_info !== false) {
                    $builder->logoPath($logoPath)
                            ->logoResizeToWidth($logoSize);
                }
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

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        

        $parsed_url = wp_parse_url($url);
        if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], ['http', 'https'], true)) {
            return '';
        }
        
        $upload_dir = wp_upload_dir();
        if (is_wp_error($upload_dir) || !isset($upload_dir['basedir'])) {
            return '';
        }
        
        $logo_dir = $upload_dir['basedir'] . '/qr-logos/';
        

        $filename = sanitize_file_name(basename(wp_parse_url($url, PHP_URL_PATH)));
        if (empty($filename)) {
            $filename = 'logo_' . md5($url) . '.png';
        }
        
        $local_path = $logo_dir . $filename;
        

        if (!file_exists($logo_dir)) {
            if (!wp_mkdir_p($logo_dir)) {
                return '';
            }
        }
        

        if (!file_exists($local_path)) {
            $response = wp_remote_get(esc_url_raw($url), [
                'timeout' => 30,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
                'headers' => [
                    'Accept' => 'image/*'
                ]
            ]);
            
            if (is_wp_error($response)) {
                return '';
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 200) {
                return '';
            }
            
            $body = wp_remote_retrieve_body($response);
            if (empty($body)) {
                return '';
            }
            

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->buffer($body);
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mime_type, $allowed_types, true)) {
                return '';
            }
            

            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . '/wp-admin/includes/file.php';
                WP_Filesystem();
            }
            
            if (!$wp_filesystem->put_contents($local_path, $body, FS_CHMOD_FILE)) {
                return '';
            }
        }
        
        return file_exists($local_path) ? $local_path : '';
    }

    public static function get_logo_path_from_attachment($attachment_id)
    {

        if (!is_numeric($attachment_id) || $attachment_id <= 0) {
            return '';
        }
        
        $attachment_id = absint($attachment_id);
        

        if (!wp_attachment_is_image($attachment_id)) {
            return '';
        }
        

        $file_path = get_attached_file($attachment_id);
        

        if (!$file_path || !file_exists($file_path) || !is_readable($file_path)) {
            return '';
        }
        

        $image_info = @getimagesize($file_path);
        if ($image_info === false) {
            return '';
        }
        

        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        if (!in_array($image_info[2], $allowed_types, true)) {
            return '';
        }
        
        return $file_path;
    }
}
