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

function logFailingUsersToFile(array $failedUsers): void
{
    $filePath = __DIR__ . "/../../logs/failed-imports/failed-".date('Y-m-dTH:i:s').".json";
    if (!is_dir(dirname($filePath))) {
        mkdir(dirname($filePath), 0777, true);
    }
    file_put_contents($filePath, json_encode($failedUsers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    logger()->info("Logged " . count($failedUsers) . " failed users to $filePath");
}