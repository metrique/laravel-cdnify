<?php

namespace Metrique\CDNify;

use Metrique\CDNify\Contracts\CDNifyRepositoryInterface;

class CDNifyRepository implements CDNifyRepositoryInterface
{
    private $cdn;
    private $path;
    private $environments;
    private $elixir;

    private $roundRobin;
    private $roundRobinIndex = -1;
    private $roundRobinLength = 0;

    public function __construct()
    {
        $this->defaults();
    }

    /**
     * {@inheritdoc}
     */
    public function defaults()
    {
        $this->cdn = array_values(config('cdnify.cdn', []));
        $this->roundRobinLength = count($this->cdn);

        $this->elixir(config('cdnify.elixir', false));
        $this->environments(config('cdnify.environments', []));
        $this->roundRobin(config('cdnify.round_robin'));
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, $params = [])
    {
        if (is_bool($params)) {
            $params = [
                'elixir' => $params
            ];
        }

        $resets = collect([
            'elixir' => $this->elixir,
            'environments' => $this->environments,
            'roundRobin' => $this->roundRobin,
        ]);

        $params = $resets->merge($params)->only($resets->keys()->all());

        $params->each(function ($key, $item) {
            $this->{$item}($key);
        });

        $path = $this->path($path)->toString();

        $resets->each(function ($key, $item) {
            $this->{$item}($key);
        });

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        $path = $this->path ?: false;

        if ($path === false) {
            return false;
        }

        if ($this->elixir === true) {
            $path = elixir($path);
        }

        if (in_array(env('APP_ENV'), $this->environments)) {
            return $this->cdn().$path;
        }

        return $path;
    }

    /**
     * {@inheritdoc}
     */
    public function cdn()
    {
        if (!$this->roundRobin) {
            return $this->cdn[0];
        }

        if (++$this->roundRobinIndex > ($this->roundRobinLength - 1)) {
            $this->roundRobinIndex = 0;
        }

        return $this->cdn[$this->roundRobinIndex];
    }

    /**
     * {@inheritdoc}
     */
    public function path($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function environments($environments)
    {
        if (is_array($environments)) {
            $this->environments = $environments;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function elixir($bool)
    {
        if (is_bool($bool)) {
            $this->elixir = $bool;
        }

        if (!function_exists('elixir')) {
            $this->elixir = false;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function roundRobin($bool)
    {
        if (is_bool($bool)) {
            $this->roundRobin = $bool;
        }

        return $this;
    }
}
