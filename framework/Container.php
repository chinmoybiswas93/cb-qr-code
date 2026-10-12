<?php
/**
 * Dependency container.
 *
 * @package CBQRCode
 */

namespace ChinmoyBiswas\CBQRCode\Framework;

use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolves bindings by string key or class name.
 *
 * Deliberately smaller than Omnio's container: there is no reflection
 * auto-wiring, so anything resolved through make() has to be registered with
 * bind(), singleton() or instance() first. A handful of classes don't need
 * more; add auto-wiring when a feature does.
 */
class Container
{
    /**
     * Registered factories, keyed by abstract name.
     *
     * @var array<string, callable>
     */
    private $bindings = [];

    /**
     * Resolved shared instances, keyed by abstract name.
     *
     * @var array<string, mixed>
     */
    private $instances = [];

    /**
     * Abstract names that resolve once and are cached.
     *
     * @var array<string, true>
     */
    private $shared = [];

    /**
     * Alias => abstract map.
     *
     * @var array<string, string>
     */
    private $aliases = [];

    /**
     * Register a factory binding.
     *
     * @param string   $abstract Binding key.
     * @param callable $concrete Factory returning the concrete instance; receives the container.
     * @param bool     $shared   Whether to cache the resolved instance.
     * @return void
     */
    public function bind( $abstract, callable $concrete, $shared = false )
    {
        $this->bindings[ $abstract ] = $concrete;

        if ( $shared ) {
            $this->shared[ $abstract ] = true;
        }

        unset( $this->instances[ $abstract ] );
    }

    /**
     * Register a factory binding that resolves once and is cached.
     *
     * @param string   $abstract Binding key.
     * @param callable $concrete Factory returning the concrete instance.
     * @return void
     */
    public function singleton( $abstract, callable $concrete )
    {
        $this->bind( $abstract, $concrete, true );
    }

    /**
     * Register an already-built instance.
     *
     * @param string $abstract Binding key.
     * @param mixed  $instance Instance to store.
     * @return void
     */
    public function instance( $abstract, $instance )
    {
        $this->instances[ $abstract ] = $instance;
        $this->shared[ $abstract ]    = true;
    }

    /**
     * Point a short name at an existing binding, so both resolve identically.
     *
     * @param string $abstract Binding key.
     * @param string $alias    Short name.
     * @return void
     */
    public function alias( $abstract, $alias )
    {
        $this->aliases[ $alias ] = $abstract;
    }

    /**
     * Resolve a binding.
     *
     * @param string $abstract Binding key, alias or class name.
     * @return mixed
     *
     * @throws RuntimeException When nothing is registered under $abstract.
     */
    public function make( $abstract )
    {
        if ( isset( $this->aliases[ $abstract ] ) ) {
            $abstract = $this->aliases[ $abstract ];
        }

        if ( array_key_exists( $abstract, $this->instances ) ) {
            return $this->instances[ $abstract ];
        }

        if ( ! isset( $this->bindings[ $abstract ] ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Developer-facing; the key comes from code, never request data.
            throw new RuntimeException( "Nothing bound for '{$abstract}'." );
        }

        $object = call_user_func( $this->bindings[ $abstract ], $this );

        if ( isset( $this->shared[ $abstract ] ) ) {
            $this->instances[ $abstract ] = $object;
        }

        return $object;
    }

    /**
     * Whether a binding or resolved instance exists for $abstract.
     *
     * @param string $abstract Binding key or alias.
     * @return bool
     */
    public function bound( $abstract )
    {
        if ( isset( $this->aliases[ $abstract ] ) ) {
            $abstract = $this->aliases[ $abstract ];
        }

        return isset( $this->bindings[ $abstract ] ) || array_key_exists( $abstract, $this->instances );
    }
}
