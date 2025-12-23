# LLM Query Builder

Interfaccia web per interrogare database MySQL usando linguaggio naturale con AI.

## Caratteristiche

- Conversione linguaggio naturale → SQL automatica
- Esecuzione sicura query READ-ONLY
- Visualizzazione risultati in tabella
- Cache delle risposte LLM
- Log audit delle query
- Validazione e sanitizzazione SQL
- Timeout e limiti di sicurezza

## Struttura Directory

```
ai-query-builder/
├── llm_query_interface.php      # Interfaccia principale
├── api_llm_query.php            # API backend
├── config_llm_query.php         # Configurazione
├── config_llm_query-example.php # Esempio configurazione
├── generate_schema.php          # Helper per schema DB
├── test_installation.php        # Test installazione
├── llm_query_builder.css        # Stili CSS
├── maintenance.sh               # Script manutenzione
├── docs/                        # Documentazione
│   ├── INDEX.md
│   ├── QUICK_START.md
│   ├── ESEMPI_QUERY.md
│   ├── LLM_PROVIDERS_CONFIG.md
│   └── SECURITY_CHECKLIST.md
├── sql/                         # Script SQL
│   ├── create_readonly_user.sql
│   └── db_schema.sql
└── logs/                        # Log applicazione
```

## Installazione

### 1. Copia i file

### 2. Crea utente database READ-ONLY

```bash
mysql -u root -p < sql/create_readonly_user.sql
```

**IMPORTANTE:** Modifica la password nel file SQL prima di eseguirlo.

### 3. Genera lo schema del database

```bash
php generate_schema.php
```

Oppure manualmente:
```bash
mysqldump -u root -p --no-data yourdb > sql/db_schema.sql
```

### 4. Configura `config_llm_query.php`

Copia `config_llm_query-example.php` in `config_llm_query.php` e modifica le impostazioni.

Consulta `docs/LLM_PROVIDERS_CONFIG.md` per la configurazione dei vari provider AI.

### 5. Testa l'installazione

```bash
php test_installation.php
```

## Uso

### Esempi di richieste

**Semplici:**
- "Dammi tutte le email dei professionisti"
- "Quanti ebook abbiamo venduto oggi?"
- "Lista degli ultimi 10 ordini"

**Con filtri:**
- "Email dei professionisti che hanno comprato nel 2025"
- "Top 10 ebook più venduti questo mese"
- "Fatture emesse questa settimana"

**Con dati specifici:**
- "Da queste fatture recupera le email: AB4558, AB4614, AB4673"
- "Professionisti con email che finisce con @gmail.com"

### Modalità di utilizzo

1. **Genera e Esegui**: Genera la query SQL e la esegue immediatamente
2. **Solo Genera**: Genera solo la query senza eseguirla (per revisione)
3. **Esegui**: Esegue una query già generata dopo revisione

## Sicurezza

### Misure implementate

- **Utente DB read-only**: Solo permessi SELECT
- **Validazione SQL**: Blocca INSERT/UPDATE/DELETE/DROP
- **Rate limiting**: Max query/ora configurabile
- **Query timeout**: Previene query lunghe
- **Sanitizzazione input**: Previene SQL injection
- **Limite risultati**: Max 1000 righe
- **Audit log**: Traccia tutte le query

### Best Practices

1. **NON esporre pubblicamente** - Solo nel backoffice
2. **Usa HTTPS** per tutte le connessioni
3. **Limita accesso** solo ad admin fidati
4. **Monitora i log** regolarmente
5. **Backup regolari** del database
6. **Testa le query** in staging prima

## Troubleshooting

### Errore: "Schema database non disponibile"

```bash
php generate_schema.php
# oppure
mysqldump -u root -p --no-data ebookecm > db_schema.sql
```

### Errore: "Non autorizzato"

Verifica l'autenticazione in `config_llm_query.php`:
```php
var_dump($_SESSION); // Debug
```

### Errore: "Errore API LLM"

1. Verifica API key in `config_llm_query.php`
2. Controlla endpoint API corretto
3. Verifica credito API rimanente
4. Controlla logs: `tail -f logs/llm_queries.log`

### Query lenta o timeout

1. Aumenta timeout: `define('QUERY_TIMEOUT', 60);`
2. Ottimizza query (aggiungi indici)
3. Riduci LIMIT risultati

### Cache non funziona

```bash
# Verifica permessi directory
chmod 755 cache/
chmod 755 logs/
```

## Monitoraggio

### Log delle query

```bash
tail -f logs/llm_queries.log
```

### Uso database

```sql
-- Connessioni attive
SELECT * FROM information_schema.processlist 
WHERE USER = 'llm_readonly';

-- Statistiche uso
SELECT * FROM information_schema.user_statistics 
WHERE USER = 'llm_readonly';
```

## Personalizzazione

### Cambia modello LLM

Nel file `config_llm_query.php`:

```php
// Per modelli più piccoli/veloci
define('LLM_MODEL', 'qwen/qwen-2.5-coder-7b-instruct');

// Per modelli più potenti
define('LLM_MODEL', 'qwen/qwen-2.5-coder-32b-instruct');
```

### Aggiungi export CSV/Excel

Nel file `llm_query_interface.php`, implementa le funzioni:

```javascript
function exportToCSV() {
    // Converti risultati in CSV
}

function exportToExcel() {
    // Usa libreria come PHPSpreadsheet
}
```

### Personalizza UI

Modifica gli stili CSS in `llm_query_interface.php` nella sezione `<style>`.

## Aggiornamenti

### Aggiorna schema database

```bash
php generate_schema.php
```

### Pulisci cache

```bash
rm -rf cache/llm/*
```



## Licenza

Uso interno - Tutti i diritti riservati

## Disclaimer

Questo sistema esegue query SQL generate da AI. Anche con tutte le misure di sicurezza:
- Usa sempre in ambiente protetto
- Limita accesso solo ad admin
- Monitora regolarmente l'uso
- Testa in staging prima di produzione
- Mantieni backup aggiornati

---

AI Query Builder - Natural Language to SQL Interface
