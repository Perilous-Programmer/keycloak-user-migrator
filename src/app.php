<?php
require_once __DIR__ . '/../bootstrap/app.php';
use App\Services\KeycloakUserImporter;

class Application
{
    private $importer;
    private $totalUsers;
    private $batchSize;
    private $batchStart;
    private $delay;

        /**
     * Application constructor.
     * @param int|null $totalUsers
     * @param int|null $batchSize
     * @param int|null $batchStart
     * @param int|null $delay
     */
    public function __construct(
        ?int $totalUsers = null,
        ?int $batchSize = null,
        ?int $batchStart = null,
        ?int $delay = null
    ) {
        $this->totalUsers = $totalUsers;
        $this->batchSize = $batchSize;
        $this->batchStart = $batchStart;
        $this->delay = $delay;

        $this->importer = new KeycloakUserImporter(
            env('KEYCLOAK_URL'),
            env('KEYCLOAK_REALM'),
            env('KEYCLOAK_CLIENT_ID'),
            env('KEYCLOAK_CLIENT_SECRET')
        );
    }

    private function fetchUsersFromOldDatabase($batchStart = 0, $batchSize = 10)
    {
        $pdo = new PDO(
            'mysql:host=' . env("DB_HOST") . ';dbname=' . env("DB_NAME"),
            env("DB_USER"),
            env("DB_PASS"),
            array(
                PDO::ATTR_TIMEOUT => env("PDO_TIMEOUT", 5), // in seconds
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            )
        );
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

    public function run()
    {
        $failed = [];
        for ($start = $this->batchStart; $start < $this->totalUsers; $start += $this->batchSize) {
            $users = $this->fetchUsersFromOldDatabase($start, $this->batchSize);
            // logger()->info("Fetched batch starting at $start: " . json_encode($users, JSON_PRETTY_PRINT));

            $results = $this->importer->importUsersBatch($users);

            foreach ($results as $result) {
                if ($result['result']['success'] !== true) {
                    $failed[] = $result['user'] ?? null;
                }
            }

            // logger()->info("Batch results: " . json_encode($results, JSON_PRETTY_PRINT));

            if ($this->delay > 0 && ($start + $this->batchSize) < $this->totalUsers) {
                logger()->info("Sleeping for {$this->delay} seconds before next batch...");
                sleep($this->delay);
            }
        }

        if (count($failed) > 0) {
            logFailingUsersToFile($failed);
        }
    }
} 
