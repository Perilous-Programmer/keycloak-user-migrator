<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;

class KeycloakUserImporter {
    private $client;
    private $accessToken;
    private $realm;
    private $keycloakUrl;
    
    public function __construct($keycloakUrl, $realm, $clientId, $clientSecret) {
        $this->keycloakUrl = $keycloakUrl;
        $this->realm = $realm;
        $this->client = new Client(['base_uri' => $keycloakUrl]);
        
        // Get access token
        $this->authenticate($clientId, $clientSecret);
    }
    
    private function authenticate($clientId, $clientSecret) {
        $response = $this->client->post("/realms/{$this->realm}/protocol/openid-connect/token", [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret
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
            'emailVerified' => false,
            'credentials' => [
                [
                    'type' => 'password',
                    'value' => $userData['password_hash'], // Or temporary password
                    'temporary' => true // Force password change on first login
                ]
            ],
            'attributes' => [
                'legacy_user_id' => $userData['id'] // Store old ID for reference
            ]
        ];
        
        try {
            $response = $this->client->post("/admin/realms/{$this->realm}/users", [
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
            $results[] = [
                'user' => $user['username'],
                'result' => $this->importUser($user)
            ];
        }
        return $results;
    }
}
?>