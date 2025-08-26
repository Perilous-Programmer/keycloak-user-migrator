<?php
require __DIR__ . '/../bootstrap/app.php';
use App\Services\KeycloakUserImporter;

function fetchUsersFromOldDatabase()
{
    $pdo = new PDO('mysql:host='.env("DB_HOST").';dbname='.env("DB_NAME"), env("DB_USER"), env("DB_PASS"));
    $stmt = $pdo->query("SELECT uid as id, first_name, last_name, username, email, mobile FROM users");

    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = $row;
    }

    return $users;
}

// Fetch users from old database
$oldUsers = fetchUsersFromOldDatabase();

logger()->info('Fetched ' . json_encode($oldUsers,JSON_PRETTY_PRINT) . ' users from the old database.');

// Usage example
$importer = new KeycloakUserImporter(
    env('KEYCLOAK_URL'),
    env('KEYCLOAK_REALM'),
    env('KEYCLOAK_CLIENT_ID'), 
    env('KEYCLOAK_CLIENT_SECRET')
);


// Import users
$results = $importer->importUsersBatch($oldUsers);
// log the results in a failed.json file for retry again
$failed = [];
foreach ($results as $result) {
    if ($result['result']['success'] !== true) {
        $failed[] = $result['user'] ?? null;
    }
}

if (count($failed) > 0) {
    logFailingUsersToFile($failed);
}
logger()->info(json_encode($results, JSON_PRETTY_PRINT));
