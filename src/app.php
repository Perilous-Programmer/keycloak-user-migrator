<?php
require __DIR__ . '/../bootstrap/app.php';
use App\Services\KeycloakUserImporter;

function fetchUsersFromOldDatabase()
{
    $pdo = new PDO('mysql:host=localhost;dbname=old_db', 'username', 'password');
    $stmt = $pdo->query("SELECT id, username, email, password_hash, first_name, last_name FROM users");

    $users = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users[] = $row;
    }

    return $users;
}

// Usage example
$importer = new KeycloakUserImporter(
    env('KEYCLOAK_URL'),
    env('KEYCLOAK_REALM'),
    env('KEYCLOAK_CLIENT_ID'), 
    env('KEYCLOAK_CLIENT_SECRET')
);

// Fetch users from old database
$oldUsers = fetchUsersFromOldDatabase();

// Import users
$results = $importer->importUsersBatch($oldUsers);

logger()->info(json_encode($results, JSON_PRETTY_PRINT));
