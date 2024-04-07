<?php

namespace Pmaxs\Crawler;

class Response extends Objectt
{
    public ?string $url = null;
    public ?string $result = null;
    public ?string $error = null;
    public ?string $remoteIp = null;
    public ?int $remotePort = null;
    public ?int $code = null;
    public ?string $header = null;
    public ?int $headerContentLength = null;
    public ?array $headers = null;
    public ?string $body = null;
    public ?string $output = null;
    public ?float $timeStart = null;
    public ?float $timeWrite = null;
    public ?float $timeRead = null;
    public ?float $timeClose = null;
    public ?float $timeFinish = null;

    public function __construct(string $url, array $options = [])
    {
        parent::__construct($options);

        $this->url = $url;
    }
}
