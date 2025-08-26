# Keycloak User Importer

This project migrates users from an old database into Keycloak using PHP. It uses Guzzle for HTTP requests, Monolog for logging, and vlucas/phpdotenv for environment variable management.

## Prerequisites

- PHP 7.4 or newer
- Composer
- MySQL (for the old database)
- Keycloak instance
- Git (optional, for cloning)

## Setup

1. **Clone the repository**
   ```sh
   git clone git@github.com:Perilous-Programmer/keycloak-user-migrator.git keycloak
   cd keycloak
   ```

2. **Install dependencies**
   ```sh
   composer install
   ```

3. **Copy and configure environment variables**
   ```sh
   cp .env.example .env
   ```
   Edit `.env` and set your database and Keycloak credentials:

   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`: Your old database connection details.
   - `KEYCLOAK_URL`, `KEYCLOAK_REALM`, `KEYCLOAK_CLIENT_ID`, `KEYCLOAK_CLIENT_SECRET`, `KEYCLOAK_USERNAME`, `KEYCLOAK_PASSWORD`, `KEYCLOAK_GRANT_TYPE`: Your Keycloak instance details.
   - **Batch settings:**  
     - `TOTAL_USERS`: Total number of users to migrate  
     - `BATCH_SIZE`: Number of users per batch  
     - `BATCH_START`: Starting offset for migration  
     - `DELAY_BETWEEN_BATCHES`: Seconds to wait between batches  
     - `COCURRRENT_REQUESTS`: Number of concurrent requests (if supported)

4. **Ensure log directories exist**
   The script will auto-create `logs/` and `logs/failed-imports/` as needed.

## Usage

Run the importer with:

```sh
composer start
```
or
```sh
php src/app.php
```

## What it does

- Connects to your old MySQL database and fetches users in batches according to `.env` settings.
- Imports users into Keycloak using the Keycloak Admin REST API.
- Logs all actions and errors to `logs/app.log`.
- Failed user imports are saved as JSON in `logs/failed-imports/`.

## Customization

- Update the SQL query in `src/app.php` if your user table structure is different.
- Adjust Keycloak endpoints or payloads in `src/Services/KeycloakUserImporter.php` if needed.
- Tune batch and concurrency settings in your `.env` file for optimal performance.

## Troubleshooting

- Check `logs/app.log` for errors and details.
- Ensure your `.env` values are correct.
- Make sure your Keycloak instance is reachable from your server.
- Review failed user imports in `logs/failed-imports/` for specific error messages and reasons.