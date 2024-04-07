<?php

namespace Pmaxs\Crawler;

abstract class Objectt
{
    public ?array $options = [];
    
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        return null;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function setOptions(array $options = [])
    {
        $this->options = $options;

        return $this;
    }

    public static function getTime()
    {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        return ((float)$mtime[1] + (float)$mtime[0]);
    }
}
