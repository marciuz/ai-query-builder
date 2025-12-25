<?php
/**
 * API Backend per LLM Query Builder
 * Gestisce la generazione di query SQL tramite LLM e la loro esecuzione
 */

require_once 'config_llm_query.php';

// Headers per JSON API
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Verifica autenticazione
session_start();

// Leggi input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Richiesta non valida']);
    exit;
}

$action = $input['action'];

try {
    switch ($action) {
        case 'generate':
            // Genera query SQL da linguaggio naturale
            $naturalQuery = $input['natural_query'] ?? '';
            $shouldExecute = $input['execute'] ?? false;
            
            if (empty($naturalQuery)) {
                throw new Exception('Query vuota');
            }
            
            $result = generateSQLQuery($naturalQuery);
            
            if ($shouldExecute && $result['success']) {
                $executeResult = executeSQLQuery($result['sql_query']);
                $result = array_merge($result, $executeResult);
            }
            
            echo json_encode($result);
            break;
            
        case 'execute':
            // Esegui query SQL già generata
            $sqlQuery = $input['sql_query'] ?? '';
            
            if (empty($sqlQuery)) {
                throw new Exception('Query SQL vuota');
            }
            
            $result = executeSQLQuery($sqlQuery);
            echo json_encode($result);
            break;
            
        default:
            throw new Exception('Azione non valida');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Genera query SQL usando LLM
 */
function generateSQLQuery($naturalQuery) {
    // Carica schema database
    $dbSchema = getDBSchema();
    
    if (empty($dbSchema)) {
        return [
            'success' => false,
            'error' => 'Schema database non disponibile'
        ];
    }

    $DB_TYPE = (defined('DB_TYPE')) ? DB_TYPE : 'MySQL 8';
    
    // Costruisci prompt per LLM
    $systemPrompt = <<<PROMPT
You are an SQL expert who converts natural language requests into SQL queries for $DB_TYPE.
You have access to the complete database schema below.

IMPORTANT:
- Generate ONLY SELECT queries (no INSERT, UPDATE, DELETE, DROP, etc.)
- Use $DB_TYPE syntax
- Optimize queries with appropriate indexes
- Use JOIN instead of subqueries when possible
- Limit results to a maximum of 1000 rows with LIMIT
- Return ONLY the SQL code, without additional explanations in the code itself
- You can add a comment before the query to explain your reasoning
- Add backticks to the table names (`table`)

DDL statements:
{$dbSchema}

Answer with the SQL query and optionally a brief explanation before the code.
PROMPT;

    $userPrompt = $naturalQuery;
    
    // Chiama API LLM
    $llmResponse = callLLM($systemPrompt, $userPrompt);
    
    if (!$llmResponse['success']) {
        return $llmResponse;
    }
    
    // Estrai SQL dalla risposta
    $extracted = extractSQLFromResponse($llmResponse['content']);

    // Fix reserved word aliases
    $extracted['sql'] = fix_reserved_word_aliases($extracted['sql']);

    // Valida la query
    $validation = validateSQLQuery($extracted['sql']);
    
    if (!$validation['valid']) {
        return [
            'success' => false,
            'error' => 'Query non valida: ' . $validation['error'],
            'raw_response' => $llmResponse['content']
        ];
    }
    
    // Log della query (se abilitato)
    if (ENABLE_QUERY_LOG) {
        $user_id = $_SESSION['user_id'] ?? 'unknown';
        $usage_info = $llmResponse['usage'] ?? [];
        logQuery($naturalQuery, $extracted['sql'], $user_id, $usage_info);
    }

    return [
        'success' => true,
        'sql_query' => $extracted['sql'],
        'llm_explanation' => $extracted['explanation'],
        'raw_response' => $llmResponse['content']
    ];
}

/**
 * Esegue una query SQL e restituisce i risultati
 */
function executeSQLQuery($sqlQuery) {
    // Fix reserved word aliases
    $sqlQuery = fix_reserved_word_aliases($sqlQuery);

    // Ri-valida la query prima dell'esecuzione
    $validation = validateSQLQuery($sqlQuery);
    
    if (!$validation['valid']) {
        return [
            'success' => false,
            'error' => 'Query non valida: ' . $validation['error']
        ];
    }
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => QUERY_TIMEOUT
            ]
        );
        
        // Esegui query con timeout
        $stmt = $pdo->prepare($sqlQuery);
        $stmt->execute();
        
        // Recupera risultati
        $results = $stmt->fetchAll();
        $rowCount = count($results);
        
        // Limita risultati se troppi
        if ($rowCount > MAX_ROWS) {
            $results = array_slice($results, 0, MAX_ROWS);
        }
        
        return [
            'success' => true,
            'results' => $results,
            'row_count' => $rowCount,
            'limited' => $rowCount > MAX_ROWS
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => 'Errore database: ' . $e->getMessage()
        ];
    }
}

/**
 * Chiama API LLM
 */
function callLLM($systemPrompt, $userPrompt) {
    
    // Controlla cache
    if (ENABLE_LLM_CACHE) {
        $cacheKey = md5($systemPrompt . $userPrompt);
        $cacheFile = LLM_CACHE_DIR . $cacheKey . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            return $cached;
        }
    }
    
    // Prepara richiesta API (formato OpenAI-compatible)
    $requestData = [
        'model' => LLM_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => $userPrompt
            ]
        ],
        'temperature' => 0.1,  // Bassa per output deterministico
        'max_tokens' => 2000,
        
    ];
    
    // Inizializza cURL
    $ch = curl_init(LLM_API_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . LLM_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return [
            'success' => false,
            'error' => 'Errore API: ' . $error
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'success' => false,
            'error' => 'Errore API HTTP ' . $httpCode . ': ' . $response
        ];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['choices'][0]['message']['content'])) {
        return [
            'success' => false,
            'error' => 'Risposta API non valida'
        ];
    }
    
    // Estrai informazioni sui token e cache
    $usage_info = [];
    if (isset($data['usage'])) {
        $usage_info = $data['usage'];
    }

    $result = [
        'success' => true,
        'content' => $data['choices'][0]['message']['content'],
        'usage' => $usage_info,
        'debug' => $data,
    ];

    // Salva in cache
    if (ENABLE_LLM_CACHE) {
        file_put_contents($cacheFile, json_encode($result));
    }

    return $result;
}

/**
 * Estrae SQL e spiegazione dalla risposta LLM
 */
function extractSQLFromResponse($response) {
    $explanation = '';
    $sql = '';
    
    // Pattern per trovare SQL in markdown code blocks
    if (preg_match('/```sql\s*(.*?)\s*```/is', $response, $matches)) {
        $sql = trim($matches[1]);
    } elseif (preg_match('/```\s*(SELECT.*?)\s*```/is', $response, $matches)) {
        $sql = trim($matches[1]);
    } else {
        // Cerca SELECT senza code block
        if (preg_match('/(SELECT\s+.*?;)/is', $response, $matches)) {
            $sql = trim($matches[1]);
        } else {
            $sql = trim($response);
        }
    }
    
    // Estrai spiegazione (testo prima del codice SQL)
    $parts = preg_split('/```|SELECT/i', $response, 2);
    if (count($parts) > 1 && !empty(trim($parts[0]))) {
        $explanation = trim($parts[0]);
    }
    
    // Rimuovi il punto e virgola finale se presente
    $sql = rtrim($sql, ';');
    
    return [
        'sql' => $sql,
        'explanation' => $explanation
    ];
}

/**
 * Valida query SQL per sicurezza
 */
function validateSQLQuery($sql) {
    $sql = trim($sql);
    
    // Deve iniziare con SELECT
    if (!preg_match('/^\s*SELECT\s+/i', $sql)) {
        return [
            'valid' => false,
            'error' => 'Solo query SELECT sono permesse'
        ];
    }
    
    // Controlla parole chiave pericolose
    foreach (FORBIDDEN_SQL_KEYWORDS as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
            return [
                'valid' => false,
                'error' => "Parola chiave non permessa: {$keyword}"
            ];
        }
    }
    
    // Controlla limiti su tabelle permesse (se configurato)
    if (!empty(ALLOWED_TABLE_PREFIXES[0])) {
        $hasValidPrefix = false;
        foreach (ALLOWED_TABLE_PREFIXES as $prefix) {
            if (stripos($sql, $prefix) !== false) {
                $hasValidPrefix = true;
                break;
            }
        }
        if (!$hasValidPrefix) {
            return [
                'valid' => false,
                'error' => 'Tabella non permessa'
            ];
        }
    }
    
    // Assicura che ci sia LIMIT (aggiungi se manca)
    if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
        // Non aggiungere automaticamente, lascia che LLM lo gestisca
        // oppure puoi decommentare questa riga:
        // $sql .= ' LIMIT ' . MAX_ROWS;
    }
    
    return [
        'valid' => true,
        'sql' => $sql
    ];
}

/**
 * Log delle query eseguite
 */
function logQuery($naturalQuery, $sqlQuery, $userId, $usageInfo = []) {
    $usage_json = '';
    if (!empty($usageInfo)) {
        $usage_json = json_encode($usageInfo, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    $logEntry = sprintf(
        "[%s] User: %s | Natural: %s | SQL: %s | Usage: %s\n",
        date('Y-m-d H:i:s'),
        $userId,
        $naturalQuery,
        $sqlQuery,
        $usage_json
    );

    file_put_contents(QUERY_LOG_FILE, $logEntry, FILE_APPEND);
}

/**
 * Corregge gli alias di tabella che sono reserved words MySQL
 * aggiungendo backtick
 */
function fix_reserved_word_aliases($sql) {
    // Lista di reserved words MySQL comuni
    $mysql_reserved_words = [
        'ACCESSIBLE', 'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC',
        'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH',
        'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK',
        'COLLATE', 'COLUMN', 'CONDITION', 'CONSTRAINT', 'CONTINUE', 'CONVERT',
        'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP',
        'CURRENT_USER', 'CURSOR', 'DATABASE', 'DATABASES', 'DAY_HOUR',
        'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL',
        'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE',
        'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP',
        'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS',
        'EXIT', 'EXPLAIN', 'FALSE', 'FETCH', 'FLOAT', 'FLOAT4', 'FLOAT8',
        'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GRANT', 'GROUP',
        'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE',
        'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER',
        'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3',
        'INT4', 'INT8', 'INTEGER', 'INTERVAL', 'INTO', 'IS', 'ITERATE',
        'JOIN', 'KEY', 'KEYS', 'KILL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE',
        'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP',
        'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY',
        'MASTER_SSL_VERIFY_SERVER_CERT', 'MATCH', 'MAXVALUE', 'MEDIUMBLOB',
        'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND',
        'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG',
        'NULL', 'NUMERIC', 'OF', 'ON', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR',
        'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 'PRIMARY', 'PROCEDURE',
        'PURGE', 'RANGE', 'READ', 'READS', 'READ_WRITE', 'REAL', 'REFERENCES',
        'REGEXP', 'RELEASE', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESIGNAL',
        'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE', 'SCHEMA', 'SCHEMAS',
        'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 'SHOW',
        'SIGNAL', 'SMALLINT', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION',
        'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS',
        'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE',
        'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO',
        'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK',
        'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME',
        'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARCHARACTER',
        'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH', 'WRITE', 'XOR',
        'YEAR_MONTH', 'ZEROFILL'
    ];

    // Converti in array associativo per lookup veloce
    $reserved_words_map = array_flip(array_map('strtoupper', $mysql_reserved_words));

    // Pattern per trovare alias dopo FROM o JOIN
    $pattern = '/\b(FROM|JOIN|LEFT\s+JOIN|RIGHT\s+JOIN|INNER\s+JOIN|OUTER\s+JOIN|CROSS\s+JOIN)\s+`?(\w+)`?\s+(?:AS\s+)?(\w+)(?=\s|,|\)|$)/i';

    return preg_replace_callback($pattern, function($matches) use ($reserved_words_map) {
        $join_type = $matches[1];
        $table_name = $matches[2];
        $alias = $matches[3];

        // Se l'alias è una reserved word e non è già tra backtick
        if (strtoupper($alias) != $alias 
                && isset($reserved_words_map[strtoupper($alias)]) 
                && strpos($matches[0], '`' . $alias . '`') === false) {
            // Ricostruisci il match con l'alias tra backtick
            $has_as = stripos($matches[0], ' AS ') !== false;
            if ($has_as) {
                return "$join_type `$table_name` AS `$alias`";
            } else {
                return "$join_type `$table_name` `$alias`";
            }
        }
        return $matches[0];
    }, $sql);
}
