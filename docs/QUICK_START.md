# 🚀 Quick Start Guide - LLM Query Builder

## ⚡ Installazione Rapida (5 minuti)

### 1. Estrai i file
```bash
cd /var/www/html/backoffice/
unzip llm_query_builder.zip
```

### 2. Crea utente database READ-ONLY
```bash
mysql -u root -p < create_readonly_user.sql
```

**⚠️ IMPORTANTE:** Prima di eseguire, apri `create_readonly_user.sql` e **cambia la password**!

### 3. Genera schema database
```bash
php generate_schema.php
```

Questo crea il file `db_schema.sql` con la struttura completa del tuo database.

### 4. Configura l'API LLM

Apri `config_llm_query.php` e configura:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'ebookecm');
define('DB_USER', 'llm_readonly');
define('DB_PASS', 'LA_TUA_PASSWORD_SICURA');

// API LLM - Scegli il provider che usi
```

**Provider supportati:**

#### OpenRouter (consigliato per Qwen)
```php
define('LLM_API_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
define('LLM_API_KEY', 'sk-or-v1-...');
define('LLM_MODEL', 'qwen/qwen-2.5-coder-32b-instruct');
```

#### Together AI
```php
define('LLM_API_ENDPOINT', 'https://api.together.xyz/v1/chat/completions');
define('LLM_API_KEY', '...');
define('LLM_MODEL', 'Qwen/Qwen2.5-Coder-32B-Instruct');
```

#### DeepSeek (alternativa economica)
```php
define('LLM_API_ENDPOINT', 'https://api.deepseek.com/v1/chat/completions');
define('LLM_API_KEY', '...');
define('LLM_MODEL', 'deepseek-coder');
```

### 5. Test installazione
```bash
php test_installation.php
```

Se tutto è ✅, sei pronto!

### 6. Accedi all'interfaccia

Vai su: `https://www.ebookecm.it/backoffice/llm_query_interface.php`

---

## 🎯 Primo Test

Prova questa query:
```
Dammi 5 email di professionisti
```

Dovresti vedere:
1. La query SQL generata
2. Una tabella con i risultati
3. Il conteggio delle righe

---

## 🔐 Sicurezza Checklist

- [ ] Password database cambiata
- [ ] File `.htaccess` copiato
- [ ] Permessi directory corretti (755 per directory, 644 per file)
- [ ] Accesso limitato solo ad admin nel backoffice
- [ ] HTTPS attivo
- [ ] API key LLM configurata e funzionante

---

## 🆘 Problemi Comuni

### "Schema database non disponibile"
```bash
php generate_schema.php
```

### "Errore connessione database"
Verifica in `config_llm_query.php`:
- Host corretto
- Nome database corretto
- Utente e password corretti
- Utente ha permessi SELECT

### "Errore API LLM"
- Verifica API key valida
- Controlla endpoint corretto
- Verifica credito API disponibile

### Pagina bianca / 500 Error
```bash
# Controlla log errori Apache
tail -f /var/log/apache2/error.log

# Oppure log PHP
tail -f logs/llm_queries.log
```

---

## 📝 Adattamento al tuo sistema di autenticazione

Nei file `llm_query_interface.php` e `api_llm_query.php`, trova:

```php
session_start();
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    // ...
}
```

Sostituisci con il tuo sistema di auth, ad esempio:
```php
require_once 'your_auth_system.php';
if (!is_logged_in() || !is_admin()) {
    redirect_to_login();
}
```

---

## 🎨 Personalizzazione Interfaccia

### Cambia colori tema
Nel file `llm_query_interface.php`, sezione `<style>`:
```css
.btn-primary {
    background: #TUO_COLORE;
}
```

### Cambia limite righe
In `config_llm_query.php`:
```php
define('MAX_ROWS', 1000);  // Cambia questo valore
```

### Abilita/disabilita cache
In `config_llm_query.php`:
```php
define('ENABLE_LLM_CACHE', true);  // false per disabilitare
```

---

## 📊 Monitoraggio

### Log delle query
```bash
tail -f logs/llm_queries.log
```

### Uso database
```sql
SELECT * FROM information_schema.processlist 
WHERE USER = 'llm_readonly';
```

---

## 🔄 Aggiornamento Schema

Dopo modifiche al database:
```bash
php generate_schema.php
```

Lo schema viene automaticamente ricaricato al prossimo utilizzo (se cache disabilitata).

---

## 💰 Costi API

**Stima per query:**
- Input: ~500-2000 tokens (schema DB)
- Output: ~100-300 tokens (query SQL)
- Costo per query: ~$0.001-0.003 (varia per provider)

**Con cache abilitata:**
- Query identiche non consumano API calls
- Risparmio fino al 70% su query ripetute

---

## 📚 Risorse

- **Documentazione completa:** README.md
- **Esempi query:** ESEMPI_QUERY.md
- **Test sistema:** `php test_installation.php`
- **Genera schema:** `php generate_schema.php`

---

## ✅ Checklist Installazione

- [ ] File estratti in `/backoffice/`
- [ ] Utente DB readonly creato e testato
- [ ] Schema database generato (`db_schema.sql` esiste)
- [ ] `config_llm_query.php` configurato
- [ ] API LLM testata e funzionante
- [ ] `.htaccess` copiato per sicurezza
- [ ] `php test_installation.php` ritorna tutto ✅
- [ ] Interfaccia web accessibile
- [ ] Prima query di test funziona
- [ ] Autenticazione backoffice integrata

---

## 🎉 Fatto!

Ora puoi interrogare il tuo database in linguaggio naturale!

**Esempio:**
```
"Fammi un report dei professionisti che hanno comprato ebook di cardiologia 
negli ultimi 6 mesi con relative email"
```

E il sistema genererà automaticamente la query SQL ottimizzata!

---

**Need help?** 
- Controlla i log: `logs/llm_queries.log`
- Riesegui test: `php test_installation.php`
- Leggi README.md per troubleshooting avanzato
