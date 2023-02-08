<?php
namespace Pmaxs\Crawler;

/**
 * Class Request
 */
class Request extends Objectt
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $method;

    /**
     * @var mixed
     */
    public $post;

    /**
     * @var mixed
     */
    public $headers;

    /**
     * Constructor.
     *
     * @param string $url
     * @param string $method
     * @param mixed $post
     * @param mixed $headers
     * @param array $options
     * - user_agent
     * - referer
     * - cookie
     * - time_limit
     * - proxy
     * - header_only
     */
    public function __construct($url, $method = 'GET', $post = null, $headers = null, array $options = [])
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->post = $post;
        $this->headers = $headers;

        parent::__construct($options);
    }
}
