<?php

namespace Metrique\CDNify;

use Metrique\CDNify\Contracts\CDNifyRepositoryInterface;

class CDNifyRepository implements CDNifyRepositoryInterface
{
    private $cdn;
    private $config;
    private $path;
    private $environments;
    private $elixir;

    private $roundRobin;
    private $roundRobinIndex = -1;
    private $roundRobinLength = 0;

    public function __construct(\Illuminate\Contracts\Config\Repository $config)
    {
        $this->config = $config;

        $this->defaults = $this->config->get('cdnify');
        $this->cdn = array_values($this->defaults['cdn']);
        $this->roundRobinLength = count($this->defaults['cdn']);
        
        $this->defaults();
    }

    /**
     * {@inheritdocs}
     */
    public function defaults()
    {
        $this->environments = $this->defaults['environments'];
        $this->elixir = $this->defaults['elixir'];
        $this->roundRobin = $this->defaults['round_robin'];
    }

    /**
     * {@inheritdocs}
     */
    public function get($path, $elixir = true)
    {
        return $this->path($path)->elixir($elixir)->toString();
    }

    /**
     * {@inheritdocs}
     */
    public function toString()
    {
        // Copy
        $elixir = $this->elixir;
        $environments = $this->environments;
        $path = $this->path ?: false;

        if($path === false)
        {
            return false;
        }

        if($elixir === true)
        {
            $path = elixir($path);
        }

        if(in_array(env('APP_ENV'), $environments))
        {
            return $this->cdn() . $path;
        }

        return $path;
    }

    /**
     * {@inheritdocs}
     */
    public function cdn()
    {   
        if(!$this->roundRobin)
        {
            return $this->cdn[0];
        }

        if(++$this->roundRobinIndex > ($this->roundRobinLength - 1))
        {
            $this->roundRobinIndex = 0;
        }

        return $this->cdn[$this->roundRobinIndex];
    }

    /**
     * {@inheritdocs}
     */
    public function path($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritdocs}
     */
    public function environments($environments)
    {
        if(is_array($environments))
        {
            $this->environments = $environments;
        }

        return $this;
    }

    /**
     * {@inheritdocs}
     */
    public function elixir($bool)
    {
        if(is_bool($bool))
        {
            $this->elixir = $bool;
        }

        return $this;
    }
    
    /**
     * {@inheritdocs}
     */
    public function roundRobin($bool)
    {
        if(is_bool($bool))
        {
            $this->roundRobin = $bool;
        }

        return $this;
    }
}