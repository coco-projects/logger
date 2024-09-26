<?php

    use Coco\logger\Logger;
    use Monolog\Handler\StreamHandler;

    require '../vendor/autoload.php';

    class Demo
    {
        use Logger;
    }

    $demo = new Demo();
    $demo->setStandardLogger('test');

    $demo->addStdoutHandler(callback: function(StreamHandler $handler, Demo $_this) {
        $handler->setFormatter(new \Coco\logger\MyFormatter());
    });

    $demo->addRedisHandler();
    $demo->addFileHandler(path: 'log.log', callback: function(StreamHandler $handler, Demo $_this) {
        $handler->setFormatter(new \Coco\logger\MyFormatter());
    });

    $demo->logDebug('test log1');

    $demo->logAlert('test log2', [
        "key" => "value",
    ]);


/*

[2024-09-26 19:39:46] test.DEBUG: test log1 []
[2024-09-26 19:39:46] test.ALERT: test log2 {"key":"value"}

 */