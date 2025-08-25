<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

function logger(): Logger
{
    static $logger = null;
    if ($logger === null) {
        $logger = new Logger('app');
        $logPath = __DIR__ . '/../../logs/app.log';
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }
        $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }
    return $logger;
}