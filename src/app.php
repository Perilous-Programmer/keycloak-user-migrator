<?php
require __DIR__ . '/../bootstrap/app.php';
use App\Services\KeycloakUserImporter;

function fetchUsersFromOldDatabase($batchStart = 0, $batchSize = 10)
{
    $pdo = new PDO('mysql:host='.env("DB_HOST").';dbname='.env("DB_NAME"), env("DB_USER"), env("DB_PASS"));
    $stmt = $pdo->prepare("SELECT uid as id, first_name, last_name, username, email, mobile FROM users ORDER BY modified DESC LIMIT :start, :size");
    $stmt->bindValue(':start', (int)$batchStart, PDO::PARAM_INT);
    $stmt->bindValue(':size', (int)$batchSize, PDO::PARAM_INT);
    $stmt->execute();

    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = $row;
    }

    return $users;
}

// Get batch details from env
$totalUsers = (int)env('TOTAL_USERS', 100);
$batchSize = (int)env('BATCH_SIZE', 10);
$batchStart = (int)env('BATCH_START', 0);
$delay = (int)env('DELAY_BETWEEN_BATCHES', 5);

$importer = new KeycloakUserImporter(
    env('KEYCLOAK_URL'),
    env('KEYCLOAK_REALM'),
    env('KEYCLOAK_CLIENT_ID'), 
    env('KEYCLOAK_CLIENT_SECRET')
);

$failed = [];
for ($start = $batchStart; $start < $totalUsers; $start += $batchSize) {
    $users = fetchUsersFromOldDatabase($start, $batchSize);
    logger()->info("Fetched batch starting at $start: " . json_encode($users, JSON_PRETTY_PRINT));

    $results = $importer->importUsersBatch($users);

    foreach ($results as $result) {
        if ($result['result']['success'] !== true) {
            $failed[] = $result['user'] ?? null;
        }
    }

    logger()->info("Batch results: " . json_encode($results, JSON_PRETTY_PRINT));

    if ($delay > 0 && ($start + $batchSize) < $totalUsers) {
        logger()->info("Sleeping for {$delay} seconds before next batch...");
        sleep($delay);
    }
}

if (count($failed) > 0) {
    logFailingUsersToFile($failed);
}
