<?php
/**
 * Script per generare lo schema del database
 * Esegui questo script una volta per creare il file db_schema.sql
 * 
 * Uso: php generate_schema.php
 */

require_once 'config_llm_query.php';

echo "=== Generatore Schema Database ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connesso al database: " . DB_NAME . "\n";
    echo "Estrazione schema in corso...\n\n";
    
    $schema = "-- Schema Database: " . DB_NAME . "\n";
    $schema .= "-- Generato: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Ottieni lista tabelle
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Trovate " . count($tables) . " tabelle:\n";
    
    foreach ($tables as $table) {
        echo "  - {$table}\n";
        
        // CREATE TABLE
        $createTable = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
        $schema .= "-- Tabella: {$table}\n";
        $schema .= $createTable['Create Table'] . ";\n\n";
        
        // Aggiungi commenti con esempi di dati (opzionale)
        $sampleData = $pdo->query("SELECT * FROM `{$table}` LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($sampleData)) {
            $schema .= "-- Esempio record:\n";
            foreach ($sampleData as $row) {
                $schema .= "-- " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
            }
            $schema .= "\n";
        }
    }
    
    // Salva il file
    $schemaFile = DB_SCHEMA_FILE;
    file_put_contents($schemaFile, $schema);
    
    echo "\n✅ Schema salvato in: {$schemaFile}\n";
    echo "Dimensione: " . number_format(strlen($schema)) . " bytes\n";
    
    // Statistiche
    echo "\n=== Statistiche ===\n";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo sprintf("  %-30s %10s record\n", $table, number_format($count));
    }
    
} catch (PDOException $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✨ Completato!\n";
