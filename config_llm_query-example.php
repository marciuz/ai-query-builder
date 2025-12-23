<?php
/**
 * Configurazione LLM Query Builder
 */

// === CONFIGURAZIONE DATABASE ===
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'yourdb');
define('DB_USER', 'yourpassw');  // IMPORTANTE: Usa un utente con SOLO permessi SELECT
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// === CONFIGURAZIONE LLM API ===
// Qwen 3-coder via API (adatta secondo il tuo provider)
//define('LLM_API_ENDPOINT', 'https://api.tokenfactory.nebius.com/v1/chat/completions');
//define('LLM_API_KEY', 'v1.CmMKH....');
//define('LLM_MODEL', 'Qwen/Qwen3-Coder-480B-A35B-Instruct');  // o il nome del modello che usi

//define('LLM_API_ENDPOINT', 'https://api.cerebras.ai/v1/chat/completions');
//define('LLM_API_KEY', 'csk-mm5d5t...');
//define('LLM_MODEL', 'gpt-oss-120b');  // o il nome del modello che usi

// Alternative se usi OpenRouter, Together AI, o altro provider:
define('LLM_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('LLM_API_KEY', 'sk-or-v1-4777...');
define('LLM_MODEL', 'qwen/qwen3-coder');

// === CONFIGURAZIONE SICUREZZA ===
// Timeout query (secondi)
define('QUERY_TIMEOUT', 30);

// Limite massimo righe restituite
define('MAX_ROWS', 1000);

// Prefissi tabelle permesse (lascia vuoto per permettere tutte)
define('ALLOWED_TABLE_PREFIXES', ['']); // es: ['ebk_', 'ordine_']

// Parole chiave SQL pericolose da bloccare
define('FORBIDDEN_SQL_KEYWORDS', [
    'DROP', 'DELETE', 'TRUNCATE', 'INSERT', 'UPDATE', 
    'ALTER', 'CREATE', 'GRANT', 'REVOKE', 'EXEC',
    'EXECUTE', 'SCRIPT', 'JAVASCRIPT', '<script'
]);

// === CONFIGURAZIONE SISTEMA ===
// Log delle query (per debug e audit)
define('ENABLE_QUERY_LOG', true);
define('QUERY_LOG_FILE', __DIR__ . '/logs/llm_queries.log');

// Cache delle risposte LLM (per risparmiare API calls)
define('ENABLE_LLM_CACHE', true);
define('LLM_CACHE_DIR', __DIR__ . '/cache/llm/');

// === PATH SCHEMA DATABASE ===
// File contenente il dump dello schema del DB
define('DB_SCHEMA_FILE', __DIR__ . '/db_schema.sql');


// === FUNZIONI UTILITY ===

/**
 * Carica lo schema del database da file o genera al volo
 */
function getDBSchema() {
    // Se hai il file dello schema, caricalo
    if (file_exists(DB_SCHEMA_FILE)) {
        return file_get_contents(DB_SCHEMA_FILE);
    }
    
    // Altrimenti, generalo al volo (solo struttura, no dati)
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $schema = "-- Schema Database: " . DB_NAME . "\n\n";
        
        // Ottieni lista tabelle
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $schema .= $createTable['Create Table'] . ";\n\n";
        }
        
        return $schema;
    } catch (PDOException $e) {
        error_log("Errore caricamento schema: " . $e->getMessage());
        return "";
    }
}

/**
 * Crea le directory necessarie se non esistono
 */
function ensureDirectories() {
    $dirs = [
        dirname(QUERY_LOG_FILE),
        LLM_CACHE_DIR
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

ensureDirectories();

// === ESEMPIO DI SCHEMA DATABASE ===
// Se non hai ancora generato lo schema, puoi farlo con:
// mysqldump -u root -p --no-data ebookecm > db_schema.sql

/*
OPPURE crea manualmente un file db_schema.sql con le CREATE TABLE:

CREATE TABLE `professionista` (
  `id_prof` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `cognome` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_prof`)
);

CREATE TABLE `ordine_fattura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `numero` int DEFAULT NULL,
  `sezionale` varchar(10) DEFAULT NULL,
  `anno` int DEFAULT NULL,
  `id_ordine` int DEFAULT NULL,
  PRIMARY KEY (`id`)
);

... altre tabelle ...
*/
