<?php

namespace Pmaxs\Crawler;

class Request extends Objectt
{
    public ?string $url = null;
    public ?string $method = null;
    public mixed $post = null;
    public mixed $headers = null;

    /**
     * @param array $options
     * - user_agent
     * - referer
     * - cookie
     * - time_limit
     * - proxy
     * - header_only
     */
    public function __construct(string $url, string $method = 'GET', $post = null, $headers = null, array $options = [])
    {
        $this->url = $url;
        $this->method = strtoupper($method);
        $this->post = $post;
        $this->headers = $headers;

        parent::__construct($options);
    }
}
