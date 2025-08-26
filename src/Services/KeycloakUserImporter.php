<?php
namespace App\Services;

use Exception;
use GuzzleHttp\Client;


class KeycloakUserImporter {
    private $client;
    private $accessToken;
    private $realm;
    private $keycloakUrl;
    private $clientId; 
    private $clientSecret;

    const DEFAULT_PASSWORD = 'ChangeMe123!';
    
    public function __construct($keycloakUrl, $realm, $clientId, $clientSecret) {
        $this->keycloakUrl = $keycloakUrl;
        $this->realm = $realm;
        $this->client = new Client(['base_uri' => $keycloakUrl]);
        $this->clientId = $clientId; 
        $this->clientSecret = $clientSecret;
        
        // Get access token
        $this->authenticate($clientId, $clientSecret);
    }
    
    private function authenticate($clientId, $clientSecret) {
        $response = $this->client->post("/realms/master/protocol/openid-connect/token", [
            'form_params' => [
                'grant_type' => env('KEYCLOAK_GRANT_TYPE'),
                'client_id' => env('KEYCLOAK_CLIENT_ID'),
                'username' => env('KEYCLOAK_USERNAME'),
                'password' => env('KEYCLOAK_PASSWORD')                
            ]
        ]);
        
        $tokenData = json_decode($response->getBody(), true);
        $this->accessToken = $tokenData['access_token'];
    }
    
    public function importUser($userData) {
        $userPayload = [
            'username' => $userData['username'],
            'email' => $userData['email'],
            'firstName' => $userData['first_name'] ?? '',
            'lastName' => $userData['last_name'] ?? '',
            'enabled' => true,
            'emailVerified' => true,
            'credentials' => [
                [
                    'type' => 'password',
                    'value' => $userData['password_hash'] ?? self::DEFAULT_PASSWORD, // Or temporary password
                    'temporary' => true // Force password change on first login
                ]
            ],
            'attributes' => [
                'legacy_user_id' => $userData['id'] // Store old ID for reference
            ]
        ];
        
        try {
            $response = $this->client->post("admin/realms/{$this->realm}/users", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $userPayload
            ]);
            
            return ['success' => true, 'status' => $response->getStatusCode()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function importUsersBatch($users) {
        $results = [];
        foreach ($users as $user) {
            $user['email'] = "{$user['mobile']}@jott.com";
            $user['username'] = $user['mobile'];
            $user['last_name'] = empty($user['last_name']) ? "NA" : $user['last_name'];
            $result = [
                'user' => $user,
                'result' => $this->importUser($user)
            ];
            if ($result['result']['success'] == true) {
                    logger()->info("Imported user: id > {$user['id']}, mobile > {$user['mobile']}" );
                } else {
                    logger()->error("Failed to import user: id > {$user['id']}, mobile > {$user['mobile']}. Error: " . $result['result']['error']);
                    if (strpos($result['result']['error'], '409 Conflict') == false) {
                        $this->authenticate($this->clientId, $this->clientSecret);
                        break;
                    }
                }
            $results[] = $result;
        }
        return $results;
    }
}