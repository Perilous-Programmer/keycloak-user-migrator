<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

function logger(): Logger
{
    static $logger = null;
    if ($logger === null) {
        $logger = new Logger('app');
        $logPath = __DIR__ . '/../../logs/app.log';
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }
        $handler = new StreamHandler($logPath, Logger::DEBUG);
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, null, true, false);
        $handler->setFormatter($formatter);

        $logger->pushHandler($handler);
    }
    return $logger;
}