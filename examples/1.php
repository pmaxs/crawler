<?php
set_time_limit(300);

require '../vendor/autoload.php';

use Pmaxs\Crawler\Request;
use Pmaxs\Crawler\Response;
use Pmaxs\Crawler\Crawler;

function process(Request $request, Response $response)
{
    echo $response->url . " (" . $response->remoteIp . ":" . $response->remotePort . ")\n";

    echo "code: " . $response->code . "; "
        . "start: " . $response->timeStart . "; "
        . "finish: " . $response->timeFinish . "; "
        . "time: " . ($response->timeFinish - $response->timeStart)
        . "\n";

    if ($response->error) echo "<b style=\"color:red\">error: " . $response->error . "</b>\n";

    echo htmlspecialchars($response->body) . "\n";

    echo "<hr />\n";
}

echo "<pre>";

//
$Crawler = new Crawler(array(
    'time_limit' => 30,
));

$Crawler->request(
    new Request('http://example.com/'),
    '\process'
);

$Crawler->process();

//
$Crawler = new Crawler(array(
    'time_limit' => 30,
    'rps' => 4,
    'concurrency' => 10,
));

for ($i = 1; $i <= 10; $i++)
    $Crawler->request(
        new Request('http://example.com/tests/crawler.php?p=' . $i),
        '\process'
    );

$Crawler->process();

echo "</pre>";

