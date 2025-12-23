# 🤖 Configurazioni Provider LLM

Esempi di configurazione per diversi provider di API LLM compatibili.

---

## 🎯 Provider Raccomandati per SQL Generation

### Qwen Coder Models
- ✅ Specializzato per coding/SQL
- ✅ Ottimo rapporto qualità/prezzo
- ✅ Veloce e accurato
- ✅ Supporta fino a 480B parametri

### DeepSeek Coder
- ✅ Eccellente per SQL
- ✅ Molto economico
- ✅ Veloce
- ✅ Ben documentato

### Claude (Anthropic)
- ✅ Qualità massima
- ✅ Comprensione contesto superiore
- ⚠️ Più costoso
- ✅ Molto sicuro

---

## 📝 Configurazioni Ready-to-Use

Copia una di queste configurazioni nel tuo `config_llm_query.php`

---

### 1. OpenRouter (Raccomandato - Accesso a tutti i modelli)

```php
// === OPENROUTER ===
define('LLM_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('LLM_API_KEY', 'sk-or-v1-YOUR_KEY_HERE');

// Opzioni modelli:

// Qwen 2.5 Coder 32B (ottimo per SQL, economico)
define('LLM_MODEL', 'qwen/qwen-2.5-coder-32b-instruct');
// Costo: ~$0.14/1M tokens input, ~$0.14/1M output
// Velocità: ⚡⚡⚡⚡

// Qwen 2.5 Coder 7B (più economico, ancora buono)
define('LLM_MODEL', 'qwen/qwen-2.5-coder-7b-instruct');
// Costo: ~$0.06/1M tokens
// Velocità: ⚡⚡⚡⚡⚡

// DeepSeek V3 (ottimo rapporto qualità/prezzo)
define('LLM_MODEL', 'deepseek/deepseek-chat');
// Costo: ~$0.27/1M tokens input, ~$1.10/1M output
// Velocità: ⚡⚡⚡⚡

// Claude Sonnet 4 (massima qualità)
define('LLM_MODEL', 'anthropic/claude-sonnet-4');
// Costo: ~$3/1M tokens input, ~$15/1M output
// Velocità: ⚡⚡⚡
```

**Registrazione:** https://openrouter.ai/keys

---

### 2. Together AI

```php
// === TOGETHER AI ===
define('LLM_API_ENDPOINT', 'https://api.together.xyz/v1/chat/completions');
define('LLM_API_KEY', 'YOUR_TOGETHER_API_KEY');

// Qwen 2.5 Coder 32B
define('LLM_MODEL', 'Qwen/Qwen2.5-Coder-32B-Instruct');
// Costo: ~$0.30/1M tokens
// Velocità: ⚡⚡⚡⚡

// DeepSeek Coder V2
define('LLM_MODEL', 'deepseek-ai/deepseek-coder-33b-instruct');
// Costo: ~$0.20/1M tokens
// Velocità: ⚡⚡⚡⚡
```

**Registrazione:** https://api.together.xyz/signup

---

### 3. DeepSeek (Direct API)

```php
// === DEEPSEEK DIRECT ===
define('LLM_API_ENDPOINT', 'https://api.deepseek.com/v1/chat/completions');
define('LLM_API_KEY', 'YOUR_DEEPSEEK_API_KEY');

// DeepSeek Coder V2.5
define('LLM_MODEL', 'deepseek-coder');
// Costo: Molto economico ~$0.14/1M tokens
// Velocità: ⚡⚡⚡⚡⚡
```

**Registrazione:** https://platform.deepseek.com/

---

### 4. Anthropic (Direct API) - Claude

```php
// === ANTHROPIC DIRECT ===
define('LLM_API_ENDPOINT', 'https://api.anthropic.com/v1/messages');
define('LLM_API_KEY', 'sk-ant-YOUR_KEY_HERE');

// Claude Sonnet 4.5
define('LLM_MODEL', 'claude-sonnet-4-5-20250929');
// Costo: $3/$15 per 1M tokens
// Velocità: ⚡⚡⚡
// Qualità: ⭐⭐⭐⭐⭐

// IMPORTANTE: Claude usa formato API leggermente diverso
// Richiede modifiche in api_llm_query.php (vedi sotto)
```

**Nota:** Per Claude, devi modificare la funzione `callLLM()` in `api_llm_query.php`:

```php
// Modifica per Claude API
$requestData = [
    'model' => LLM_MODEL,
    'messages' => [
        [
            'role' => 'user',
            'content' => $systemPrompt . "\n\n" . $userPrompt
        ]
    ],
    'max_tokens' => 2000
];

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: ' . LLM_API_KEY,
        'anthropic-version: 2023-06-01'
    ],
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_TIMEOUT => 60
]);
```

**Registrazione:** https://console.anthropic.com/

---

### 5. OpenAI (GPT-4 - Buono ma costoso)

```php
// === OPENAI ===
define('LLM_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions');
define('LLM_API_KEY', 'sk-YOUR_OPENAI_KEY');

// GPT-4 Turbo (buono per SQL)
define('LLM_MODEL', 'gpt-4-turbo-preview');
// Costo: $10/$30 per 1M tokens
// Velocità: ⚡⚡⚡

// GPT-4o (più veloce, economico)
define('LLM_MODEL', 'gpt-4o');
// Costo: $2.50/$10 per 1M tokens
// Velocità: ⚡⚡⚡⚡

// GPT-4o-mini (economico per test)
define('LLM_MODEL', 'gpt-4o-mini');
// Costo: $0.15/$0.60 per 1M tokens
// Velocità: ⚡⚡⚡⚡⚡
```

**Registrazione:** https://platform.openai.com/

---

### 6. Google AI (Gemini)

```php
// === GOOGLE AI ===
define('LLM_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent');
define('LLM_API_KEY', 'YOUR_GOOGLE_AI_KEY');
define('LLM_MODEL', 'gemini-pro');

// NOTA: Gemini usa formato API diverso, richiede modifiche sostanziali
```

**Registrazione:** https://makersuite.google.com/app/apikey

---

## 💰 Confronto Costi (per 1000 query medie)

Assumendo:
- Input medio: 1500 tokens (schema DB)
- Output medio: 200 tokens (query SQL)
- 1000 query al mese

| Provider | Modello | Costo/1000 query | Note |
|----------|---------|------------------|------|
| DeepSeek Direct | deepseek-coder | **$0.25** | ⭐ Migliore rapporto qualità/prezzo |
| Together AI | Qwen 2.5 Coder 7B | $0.15 | Molto veloce |
| OpenRouter | Qwen 2.5 Coder 32B | $0.28 | Bilanciato |
| DeepSeek | DeepSeek V3 | $0.62 | Ottima qualità |
| OpenRouter | Claude Sonnet 4 | $7.50 | Massima qualità |
| OpenAI | GPT-4o-mini | $0.35 | Buono per iniziare |
| OpenAI | GPT-4o | $5.75 | Costoso |
| OpenAI | GPT-4 Turbo | $19.00 | Molto costoso |

**Raccomandazione:** Inizia con **DeepSeek** o **Qwen 2.5 Coder 32B** su OpenRouter.

---

## 🧪 Test Comparativo

Usa questo script per testare diversi provider:

```php
<?php
// test_providers.php

$providers = [
    [
        'name' => 'DeepSeek',
        'endpoint' => 'https://api.deepseek.com/v1/chat/completions',
        'model' => 'deepseek-coder',
        'api_key' => 'YOUR_KEY'
    ],
    [
        'name' => 'Qwen via OpenRouter',
        'endpoint' => 'https://openrouter.ai/api/v1/chat/completions',
        'model' => 'qwen/qwen-2.5-coder-32b-instruct',
        'api_key' => 'YOUR_KEY'
    ]
];

$testPrompt = "Convert to SQL: Get emails of all professionals who purchased in 2025";

foreach ($providers as $provider) {
    echo "Testing {$provider['name']}...\n";
    
    $start = microtime(true);
    $response = testProvider($provider, $testPrompt);
    $time = round((microtime(true) - $start) * 1000);
    
    echo "  Time: {$time}ms\n";
    echo "  Quality: " . rateQuality($response) . "/5\n";
    echo "  SQL: {$response}\n\n";
}

function testProvider($config, $prompt) {
    $data = [
        'model' => $config['model'],
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'max_tokens' => 500
    ];
    
    $ch = curl_init($config['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $config['api_key']
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? 'ERROR';
}

function rateQuality($sql) {
    $score = 3; // Base
    if (stripos($sql, 'JOIN') !== false) $score++;
    if (stripos($sql, 'WHERE') !== false) $score++;
    if (stripos($sql, 'LIMIT') !== false) $score++;
    if (stripos($sql, '--') !== false) $score--; // Commenti extra
    return min(5, max(1, $score));
}
```

---

## 🎯 Raccomandazione Finale

**Per iniziare:**
```php
// OpenRouter + Qwen 2.5 Coder 32B
define('LLM_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('LLM_API_KEY', 'sk-or-v1-...');
define('LLM_MODEL', 'qwen/qwen-2.5-coder-32b-instruct');
```

**Perché:**
- ✅ Ottimo per SQL
- ✅ Economico (~$0.28/1000 query)
- ✅ Veloce
- ✅ Una sola API key per tutti i modelli
- ✅ Facile switch tra modelli
- ✅ Billing unificato

**Alternative:**
- **Budget limitato:** DeepSeek Direct
- **Massima qualità:** Claude Sonnet 4 (via OpenRouter)
- **Già cliente:** OpenAI GPT-4o-mini

---

## 📊 Monitoring Costi

Aggiungi questo in `api_llm_query.php` per tracciare costi:

```php
function logCost($provider, $inputTokens, $outputTokens) {
    $costs = [
        'qwen-2.5-coder-32b' => ['input' => 0.14, 'output' => 0.14],
        'deepseek-coder' => ['input' => 0.14, 'output' => 0.28],
        'claude-sonnet-4' => ['input' => 3.00, 'output' => 15.00],
    ];
    
    $cost = ($inputTokens / 1000000 * $costs[$provider]['input']) +
            ($outputTokens / 1000000 * $costs[$provider]['output']);
    
    file_put_contents('logs/costs.log', 
        date('Y-m-d H:i:s') . " | $provider | $cost USD\n", 
        FILE_APPEND
    );
}
```

---

## 🔑 Gestione API Keys

### Security Best Practices

```bash
# NON committare in Git
echo "config_llm_query.php" >> .gitignore

# Usa variabili d'ambiente (opzionale)
export LLM_API_KEY="your-key-here"
```

```php
// In config_llm_query.php
define('LLM_API_KEY', getenv('LLM_API_KEY') ?: 'fallback-key');
```

### Rotazione Chiavi

```php
// Sistema multi-key con rotazione
$apiKeys = [
    'key1' => 'sk-or-v1-...',
    'key2' => 'sk-or-v1-...',
    'key3' => 'sk-or-v1-...'
];

$currentKey = $apiKeys[array_rand($apiKeys)]; // Random load balancing
```

---

## 📚 Documentazione Provider

- **OpenRouter:** https://openrouter.ai/docs
- **Together AI:** https://docs.together.ai/
- **DeepSeek:** https://platform.deepseek.com/api-docs/
- **Anthropic:** https://docs.anthropic.com/
- **OpenAI:** https://platform.openai.com/docs/

---

**Ultima aggiornamento:** Dicembre 2025
**Prezzi subject to change** - Verifica sempre sui siti ufficiali
