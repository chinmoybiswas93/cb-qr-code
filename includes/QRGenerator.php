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

    public static function is_supported_image_format($attachment_id)
    {
        if (!is_numeric($attachment_id) || $attachment_id <= 0) {
            return false;
        }
        
        $attachment_id = absint($attachment_id);
        
        if (!wp_attachment_is_image($attachment_id)) {
            return false;
        }
        
        $file_path = get_attached_file($attachment_id);
        
        if (!$file_path || !file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }
        
        $image_info = @getimagesize($file_path);
        if ($image_info === false) {
            return false;
        }
        
        $allowed_types = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
        return in_array($image_info[2], $allowed_types, true);
    }

    public static function get_image_format_name($attachment_id)
    {
        if (!is_numeric($attachment_id) || $attachment_id <= 0) {
            return '';
        }
        
        $file_path = get_attached_file(absint($attachment_id));
        
        if (!$file_path || !file_exists($file_path)) {
            return '';
        }
        
        $image_info = @getimagesize($file_path);
        if ($image_info === false || !isset($image_info['mime'])) {
            return '';
        }
        
        return $image_info['mime'];
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
