<?php

require_once __DIR__ . '/src/app.php';

$app = new Application(
    (int)env('TOTAL_USERS', 100),
    (int)env('BATCH_SIZE', 10),
    (int)env('BATCH_START', 0),
    (int)env('DELAY_BETWEEN_BATCHES', 5),
    env('DB_TABLE', 'users')
);
logger()->info("Starting user import process...");
$app->run();
logger()->info("User import process completed.");
