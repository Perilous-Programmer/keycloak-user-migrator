<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

// Redirect all uncaught exceptions and errors to the logger
set_exception_handler(function ($e) {
    logger()->error('Uncaught Exception: ' . $e->getMessage(), [
        'exception' => $e
    ]);
    exit(1);
});
set_error_handler(function ($severity, $message, $file, $line) {
    logger()->error("Error: $message in $file on line $line", [
        'severity' => $severity
    ]);
    // Convert error to exception for consistency
    throw new ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logger()->critical("Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}");
    }
});

logger()->info('Loading the environment variables...');
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
logger()->info('Completed loading the environment variables...');
