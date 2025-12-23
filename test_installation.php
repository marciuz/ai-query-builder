<?php
/**
 * Script di test per verificare l'installazione del sistema LLM Query Builder
 * Esegui: php test_installation.php
 */

echo "🧪 Test Installazione LLM Query Builder\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];

// Test 1: File di configurazione
echo "1️⃣  Verifica file configurazione... ";
if (file_exists('config_llm_query.php')) {
    require_once 'config_llm_query.php';
    echo "✅\n";
} else {
    echo "❌\n";
    $errors[] = "File config_llm_query.php non trovato";
}

// Test 2: Directory necessarie
echo "2️⃣  Verifica directory... ";
$dirs = [
    dirname(QUERY_LOG_FILE ?? './logs'),
    LLM_CACHE_DIR ?? './cache/llm'
];
$dirsOk = true;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "⚠️  Creata directory: $dir\n";
    }
    if (!is_writable($dir)) {
        $warnings[] = "Directory non scrivibile: $dir";
        $dirsOk = false;
    }
}
echo $dirsOk ? "✅\n" : "⚠️\n";

// Test 3: Connessione database
echo "3️⃣  Test connessione database... ";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅\n";
    
    // Test permessi
    echo "   └─ Verifica permessi SELECT... ";
    try {
        $pdo->query("SELECT 1")->fetch();
        echo "✅\n";
    } catch (PDOException $e) {
        echo "❌\n";
        $errors[] = "Utente non può eseguire SELECT: " . $e->getMessage();
    }
    
    // Test blocco INSERT (deve fallire se readonly)
    echo "   └─ Verifica blocco INSERT... ";
    try {
        $pdo->query("CREATE TEMPORARY TABLE test_readonly (id INT)");
        $pdo->query("INSERT INTO test_readonly VALUES (1)");
        echo "⚠️  ATTENZIONE: Utente può fare INSERT!\n";
        $warnings[] = "Utente database non è READ-ONLY!";
    } catch (PDOException $e) {
        echo "✅ (correttamente bloccato)\n";
    }
    
} catch (PDOException $e) {
    echo "❌\n";
    $errors[] = "Connessione database fallita: " . $e->getMessage();
}

// Test 4: Schema database
echo "4️⃣  Verifica schema database... ";
$schemaFile = DB_SCHEMA_FILE ?? './db_schema.sql';
if (file_exists($schemaFile)) {
    $schemaSize = filesize($schemaFile);
    if ($schemaSize > 100) {
        echo "✅ ($schemaSize bytes)\n";
    } else {
        echo "⚠️  File troppo piccolo ($schemaSize bytes)\n";
        $warnings[] = "Schema database potrebbe essere incompleto";
    }
} else {
    echo "❌\n";
    $errors[] = "File db_schema.sql non trovato. Esegui: php generate_schema.php";
}

// Test 5: Configurazione API LLM
echo "5️⃣  Verifica configurazione API LLM... ";
if (defined('LLM_API_ENDPOINT') && defined('LLM_API_KEY') && defined('LLM_MODEL')) {
    if (LLM_API_KEY === 'your-api-key-here' || empty(LLM_API_KEY)) {
        echo "⚠️\n";
        $warnings[] = "API key non configurata in config_llm_query.php";
    } else {
        echo "✅\n";
        echo "   └─ Endpoint: " . LLM_API_ENDPOINT . "\n";
        echo "   └─ Model: " . LLM_MODEL . "\n";
    }
} else {
    echo "❌\n";
    $errors[] = "Configurazione API LLM mancante";
}

// Test 6: Estensioni PHP richieste
echo "6️⃣  Verifica estensioni PHP... ";
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}
if (empty($missingExtensions)) {
    echo "✅\n";
} else {
    echo "❌\n";
    $errors[] = "Estensioni PHP mancanti: " . implode(', ', $missingExtensions);
}

// Test 7: Test API LLM (opzionale)
echo "7️⃣  Test API LLM (opzionale)... ";
if (defined('LLM_API_KEY') && LLM_API_KEY !== 'your-api-key-here' && !empty(LLM_API_KEY)) {
    try {
        $testPrompt = [
            'model' => LLM_MODEL,
            'messages' => [
                ['role' => 'user', 'content' => 'Say "test ok" if you receive this']
            ],
            'max_tokens' => 10
        ];
        
        $ch = curl_init(LLM_API_ENDPOINT);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . LLM_API_KEY
            ],
            CURLOPT_POSTFIELDS => json_encode($testPrompt),
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅\n";
        } else {
            echo "❌ (HTTP $httpCode)\n";
            $warnings[] = "API LLM risponde ma con errori. Verifica credenziali.";
        }
    } catch (Exception $e) {
        echo "❌\n";
        $warnings[] = "Test API LLM fallito: " . $e->getMessage();
    }
} else {
    echo "⏭️  (skipped - API key non configurata)\n";
}

// Riepilogo
echo "\n========================================\n";
echo "📊 RIEPILOGO TEST\n";
echo "========================================\n\n";

if (empty($errors) && empty($warnings)) {
    echo "✅ ✅ ✅ TUTTO OK! Sistema pronto all'uso!\n\n";
    echo "👉 Accedi a: https://www.ebookecm.it/backoffice/llm_query_interface.php\n";
} else {
    if (!empty($errors)) {
        echo "❌ ERRORI CRITICI (" . count($errors) . "):\n";
        foreach ($errors as $i => $error) {
            echo "   " . ($i + 1) . ". $error\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "⚠️  AVVISI (" . count($warnings) . "):\n";
        foreach ($warnings as $i => $warning) {
            echo "   " . ($i + 1) . ". $warning\n";
        }
        echo "\n";
    }
    
    echo "🔧 AZIONI NECESSARIE:\n";
    if (!empty($errors)) {
        echo "   1. Risolvi gli errori critici sopra\n";
        echo "   2. Ri-esegui: php test_installation.php\n";
    }
    if (!empty($warnings) && empty($errors)) {
        echo "   1. Gli avvisi non bloccano l'uso ma vanno verificati\n";
        echo "   2. Controlla la documentazione: README.md\n";
    }
}

echo "\n";
