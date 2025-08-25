<?php
declare(strict_types=1);
use App\Services\KeycloakUserImporter;
require __DIR__ . '/../vendor/autoload.php';

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
    'https://your-keycloak-domain.com',
    'your-realm',
    'admin-cli', // Client ID with admin privileges
    'your-client-secret'
);

// Fetch users from old database
$oldUsers = fetchUsersFromOldDatabase();

// Import users
$results = $importer->importUsersBatch($oldUsers);

print_r($results);



// $app = new Application();
// $app->run();