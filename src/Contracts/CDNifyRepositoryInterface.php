<?php

namespace Metrique\CDNify\Contracts;

interface CDNifyRepositoryInterface
{

    /**
     * Set the settings back to the config defaults.
     * @return [type] [description]
     */
    public function defaults();

    /**
     * Helper utility combining the path, elixir and toString methods.
     * @param  string  $path
     * @param  boolean $elixir
     * @return string
     */
    public function get($path, $elixir = true);

    /**
     * Returns the CDN path as a string.
     * @return string|false
     */
    public function toString();

    /**
     * Returns a CDN path, if roundRobin is set to true then it will roundRobin the list of CDN's
     */
    public function cdn();

    /**
     * Set the path to be CDNified.
     * @param  string $path
     * @return $this
     */
    public function path($path);

    /**
     * Set the environments where the path should be CDNified, if null defaults will be used.
     * @param  array $environments
     * @return $this             
     */
    public function environments($environments);

    /**
     * Set whether elixir should be used if available.
     * @param  bool $bool
     * @return $this    
     */
    public function elixir($bool);

    /**
     * Enables round robin of the cdn list
     * @param  bool $bool
     * @return $this
     */
    public function roundRobin($bool);
}