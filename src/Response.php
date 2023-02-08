<?php
namespace Pmaxs\Crawler;

/**
 * Class Response
 */
class Response extends Objectt
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $result;

    /**
     * @var string
     */
    public $error;

    /**
     * @var string
     */
    public $remoteIp;

    /**
     * @var int
     */
    public $remotePort;

    /**
     * @var int
     */
    public $code;

    /**
     * @var string
     */
    public $header;

    /**
     * @var int
     */
    public $headerContentLength;

    /**
     * @var array
     */
    public $headers;

    /**
     * @var string
     */
    public $body;

    /**
     * @var string
     */
    public $output;

    /**
     * @var float
     */
    public $timeStart;

    /**
     * @var float
     */
    public $timeWrite;

    /**
     * @var float
     */
    public $timeRead;

    /**
     * @var float
     */
    public $timeClose;

    /**
     * @var float
     */
    public $timeFinish;

    /**
     * Construcor.
     *
     * @param string $url
     * @param array $options
     */
    public function __construct($url, array $options = [])
    {
        parent::__construct($options);

        $this->url = $url;
    }
}
