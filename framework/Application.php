<?php
/**
 * Plugin application kernel.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\Framework;

use ChinmoyBiswas\CBQRCode\App\App;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Boots the plugin: resolves paths, loads config/, binds the view renderer,
 * points the App facade at itself and requires the hook manifest.
 *
 * Every add_action/add_filter the plugin owns is registered from
 * app/Hooks/actions.php, so that file is the one place to read to see how the
 * plugin attaches to WordPress.
 */
class Application extends Container
{
    /**
     * Absolute path to the plugin directory, with trailing slash.
     *
     * @var string
     */
    private $basePath;

    /**
     * Plugin directory URL, with trailing slash.
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Absolute path to the main plugin file.
     *
     * @var string
     */
    private $pluginFile;

    /**
     * Anchor the application to the main plugin file.
     *
     * @param string $file Absolute path to the main plugin file.
     */
    public function __construct( $file )
    {
        $this->pluginFile = $file;
        $this->basePath   = plugin_dir_path( $file );
        $this->baseUrl    = plugin_dir_url( $file );

        $this->instance( self::class, $this );
        $this->alias( self::class, 'app' );

        $this->loadConfig();
        $this->bindCoreComponents();

        // The facade has to point at this instance before the manifest runs,
        // since anything it registers may resolve through App::.
        App::setInstance( $this );

        $this->requireHookManifest();
    }

    /**
     * Absolute path inside the plugin directory.
     *
     * @param string $path Path relative to the plugin root.
     * @return string
     */
    public function path( $path = '' )
    {
        return $this->basePath . ltrim( $path, '/' );
    }

    /**
     * URL inside the plugin directory.
     *
     * @param string $path Path relative to the plugin root.
     * @return string
     */
    public function url( $path = '' )
    {
        return $this->baseUrl . ltrim( $path, '/' );
    }

    /**
     * Absolute path to the main plugin file.
     *
     * @return string
     */
    public function pluginFile()
    {
        return $this->pluginFile;
    }

    /**
     * The config repository.
     *
     * @return Config
     */
    public function config()
    {
        return $this->make( 'config' );
    }

    /**
     * The view renderer.
     *
     * @return View
     */
    public function view()
    {
        return $this->make( 'view' );
    }

    /**
     * Load every config/*.php file into the container.
     *
     * @return void
     */
    private function loadConfig()
    {
        $this->instance( 'config', Config::fromDirectory( $this->path( 'config' ) ) );
    }

    /**
     * Bind the components the rest of the plugin resolves by name.
     *
     * @return void
     */
    private function bindCoreComponents()
    {
        $this->singleton(
            'view',
            function () {
                return new View( $this->path( 'app/Views' ) );
            }
        );
    }

    /**
     * Require app/Hooks/actions.php, the plugin's registration manifest.
     *
     * @return void
     */
    private function requireHookManifest()
    {
        $path = $this->path( 'app/Hooks/actions.php' );

        if ( is_readable( $path ) ) {
            $app = $this; // Made available to the required file.

            require_once $path;
        }
    }
}
