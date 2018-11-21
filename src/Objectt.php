<?php
namespace Pmaxs\Crawler;

/**
 * Class Objectt
 */
abstract class Objectt
{
    /**
     * @var array
     */
    public $options;

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * Returns option.
     *
     * @param string $name option name
     * @return mixed option value
     */
    public function getOption($name)
    {
        if (isset($this->options[$name])) return $this->options[$name];

        return null;
    }

    /**
     * Returns options.
     *
     * @return mixed options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets option.
     *
     * @param string $name option name
     * @param string $value option value
     * @return $this
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Sets options.
     *
     * @param array $options options
     * @return $this
     */
    public function setOptions(array $options = array())
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns current time.
     *
     * @return float
     */
    public static function getTime()
    {
        $mtime = \microtime();
        $mtime = \explode(' ', $mtime);
        return ((float)$mtime[1] + (float)$mtime[0]);
    }
}
