# crawler

HTTP crawler on non-blocking sockets.

Installation
------------

    composer require pmaxs/crawler

Usage
-----

```php
<?php
require '../vendor/autoload.php';

use Pmaxs\Crawler\Request;
use Pmaxs\Crawler\Response;
use Pmaxs\Crawler\Crawler;

function process(Request $request, Response $response)
{
    echo $response->url." (".$response->remoteIp.":".$response->remotePort.")\n";

    echo "code: ".$response->code."; "
        ."start: ".$response->timeStart."; "
        ."finish: ".$response->timeFinish."; "
        ."time: ".($response->timeFinish - $response->timeStart)
        ."\n";

    echo $response->body."\n";
}

$Crawler = new Crawler(array(
    'time_limit'=>30,
    'rps'=>4,
));

for ($i=1; $i<=20; $i++)
    $Crawler->request(
        new Request('http://example.com/p/'.$i),
        '\process'
    );

$Crawler->process();
```

Output:
```
http://example.com/p/1 (xxx.xxx.xxx.xxx:xx)
code: 200; start: 1453652698.8725; finish: 1453652699.8008; time: 0.92827606201172
p: 1

http://example.com/p/2 (xxx.xxx.xxx.xxx:xx)
code: 200; start: 1453652698.8725; finish: 1453652699.8008; time: 0.92827606201172
p: 2

http://example.com/p/3 (xxx.xxx.xxx.xxx:xx)
code: 200; start: 1453652698.8725; finish: 1453652699.8008; time: 0.92827606201172
p: 3

...

```