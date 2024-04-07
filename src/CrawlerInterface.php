<?php

namespace Pmaxs\Crawler;

interface CrawlerInterface
{
    public function request(Request $request, $callback);

    public function remove(Task $task);

    public function process();
}
