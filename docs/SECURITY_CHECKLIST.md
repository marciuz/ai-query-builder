# 🔐 Security Checklist - LLM Query Builder

## ⚠️ IMPORTANTE: Leggi prima di mettere in produzione!

Questo sistema esegue query SQL generate da AI. Anche con tutte le protezioni implementate, è fondamentale seguire queste best practices.

---

## ✅ Checklist Pre-Produzione

### 1. Autenticazione e Autorizzazione

- [ ] **Autenticazione obbligatoria**
  - Sistema di login attivo e testato
  - Sessioni sicure con timeout appropriato
  - No accesso anonimo

- [ ] **Solo amministratori**
  - Accesso limitato a utenti con ruolo admin
  - Verifica permessi su ogni richiesta (PHP + DB)
  - Log di accesso per audit

- [ ] **Rate limiting**
  - Limite query per utente/ora configurato
  - Protezione contro abuse
  - Sistema di ban automatico per tentativi sospetti

```php
// Esempio implementazione rate limiting
function checkRateLimit($userId) {
    $redis = new Redis();
    $key = "query_limit_{$userId}";
    $count = $redis->incr($key);
    if ($count === 1) {
        $redis->expire($key, 3600); // 1 ora
    }
    if ($count > 100) { // Max 100 query/ora
        throw new Exception("Rate limit exceeded");
    }
}
```

### 2. Database Security

- [ ] **Utente READ-ONLY creato**
  ```sql
  -- Verifica permessi
  SHOW GRANTS FOR 'llm_readonly'@'localhost';
  -- Deve mostrare SOLO: GRANT SELECT ON ebookecm.*
  ```

- [ ] **Password robusta**
  - Minimo 16 caratteri
  - Mix di maiuscole, minuscole, numeri, simboli
  - Non riutilizzata da altri sistemi

- [ ] **Limiti connessione configurati**
  ```sql
  ALTER USER 'llm_readonly'@'localhost' WITH
      MAX_QUERIES_PER_HOUR 1000
      MAX_CONNECTIONS_PER_HOUR 100
      MAX_USER_CONNECTIONS 5;
  ```

- [ ] **Test permessi**
  ```bash
  # Testa che INSERT fallisca
  mysql -u llm_readonly -p -e "INSERT INTO test VALUES (1);"
  # Deve dare errore!
  ```

### 3. API LLM Security

- [ ] **API Key sicura**
  - Salvata in config file, non in codice
  - File config NON accessibile via web (.htaccess)
  - Rotazione periodica delle chiavi

- [ ] **Limiti API configurati**
  - Timeout request: 60 secondi max
  - Rate limiting lato API provider
  - Monitoring uso e costi

- [ ] **Gestione errori sicura**
  ```php
  // NON fare questo:
  echo "API Error: " . $apiResponse['error'];
  
  // Fai questo:
  error_log("API Error: " . $apiResponse['error']);
  echo "Errore temporaneo, riprova";
  ```

### 4. File System Security

- [ ] **.htaccess configurato**
  ```apache
  # Blocca accesso a:
  - config_llm_query.php
  - db_schema.sql
  - *.log
  - cache/*
  - logs/*
  ```

- [ ] **Permessi corretti**
  ```bash
  # File PHP
  chmod 644 *.php
  
  # Directory
  chmod 755 cache/ logs/
  
  # Config (solo owner)
  chmod 600 config_llm_query.php
  ```

- [ ] **Directory fuori da DocumentRoot** (ideale)
  ```
  /var/www/
  ├── html/backoffice/
  │   ├── llm_query_interface.php  (pubblico)
  │   └── api_llm_query.php       (pubblico)
  └── secure/
      ├── config_llm_query.php     (privato)
      ├── db_schema.sql            (privato)
      └── logs/                    (privato)
  ```

### 5. Network Security

- [ ] **HTTPS obbligatorio**
  ```apache
  RewriteCond %{HTTPS} off
  RewriteRule ^(.*)$ https://%{HTTP_HOST}/$1 [R=301,L]
  ```

- [ ] **Headers di sicurezza**
  ```apache
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
  Header set X-Content-Type-Options "nosniff"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
  ```

- [ ] **Firewall configurato**
  - Solo porte necessarie aperte (80, 443)
  - Accesso SSH limitato a IP fidati
  - Fail2ban attivo per protezione brute-force

### 6. Input Validation

- [ ] **Validazione query SQL**
  - Solo SELECT permesso (implementato ✅)
  - Blacklist parole chiave pericolose (implementato ✅)
  - Timeout query configurato (implementato ✅)
  - Limite righe risultati (implementato ✅)

- [ ] **Sanitizzazione output**
  - HTML escape nei risultati
  - Nessun echo di dati raw
  - JSON encoding sicuro

```php
// Esempio validazione addizionale
function validateQuery($sql) {
    // Blocca commenti SQL
    if (preg_match('/(--|#|\/\*)/', $sql)) {
        return false;
    }
    
    // Blocca caratteri sospetti
    if (preg_match('/[<>"`]/', $sql)) {
        return false;
    }
    
    // Solo una query
    if (substr_count($sql, ';') > 1) {
        return false;
    }
    
    return true;
}
```

### 7. Logging & Monitoring

- [ ] **Log tutto**
  - Ogni query eseguita
  - User ID
  - Timestamp
  - Risultati (count)
  - Errori

- [ ] **Monitoring attivo**
  ```bash
  # Controlla log giornalmente
  tail -n 100 logs/llm_queries.log | grep -i error
  
  # Alert per pattern sospetti
  grep -i "DROP\|DELETE\|INSERT" logs/llm_queries.log && alert_admin
  ```

- [ ] **Rotazione log**
  ```bash
  # Crontab: rotazione settimanale
  0 0 * * 0 mv logs/llm_queries.log logs/llm_queries.log.$(date +\%Y\%m\%d)
  ```

### 8. Cache Security

- [ ] **Cache isolata**
  - Directory cache/ non accessibile via web
  - Permessi 700 (solo owner)
  - Pulizia automatica cache vecchia

- [ ] **No dati sensibili in cache**
  - Caching solo delle query SQL generate
  - NO caching di risultati con dati personali
  - Scadenza cache: max 1 ora

```php
// Configurazione sicura cache
define('LLM_CACHE_DIR', '/var/www/secure/cache/');
define('CACHE_EXPIRE', 3600); // 1 ora

// Non cachare se contiene dati sensibili
if (stripos($sql, 'email') || stripos($sql, 'password')) {
    return callLLM($prompt); // No cache
}
```

### 9. Error Handling

- [ ] **No info disclosure**
  ```php
  // NON fare:
  catch (Exception $e) {
      echo $e->getMessage(); // Espone dettagli sistema
  }
  
  // Fai:
  catch (Exception $e) {
      error_log("Error: " . $e->getMessage());
      echo "Si è verificato un errore. Contatta il supporto.";
  }
  ```

- [ ] **Display errors OFF in produzione**
  ```php
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
  error_reporting(E_ALL);
  ```

### 10. Backup & Recovery

- [ ] **Backup database regolari**
  ```bash
  # Crontab: backup giornaliero
  0 2 * * * mysqldump -u root -p ebookecm > backup_$(date +\%Y\%m\%d).sql
  ```

- [ ] **Backup configurazioni**
  ```bash
  tar -czf config_backup.tar.gz config_llm_query.php db_schema.sql
  ```

- [ ] **Piano di recovery testato**
  - Procedura ripristino documentata
  - Test recovery mensile
  - Backup offsite

---

## 🚨 Red Flags da Monitorare

### Pattern Sospetti

Controlla giornalmente per questi pattern:

```bash
# 1. Tentativi SQL injection
grep -i "union\|concat\|char(" logs/llm_queries.log

# 2. Tentativi di bypass autenticazione
grep -i "' or 1=1\|admin'\|--" logs/llm_queries.log

# 3. Query inusuali
grep -i "information_schema\|mysql\." logs/llm_queries.log

# 4. Accessi multipli rapidi
awk '{print $1}' logs/access.log | sort | uniq -c | sort -rn | head

# 5. Errori ripetuti
grep "ERROR" logs/llm_queries.log | wc -l
```

### Alert Email Automatici

```php
// Invia email se troppi errori
$errorCount = count(file('logs/llm_queries.log', FILE_IGNORE_NEW_LINES));
if ($errorCount > 50) {
    mail('admin@ebookecm.it', 
         'ALERT: Troppi errori LLM Query',
         "Rilevati $errorCount errori in logs/llm_queries.log");
}
```

---

## 🔒 Hardening Aggiuntivo (Opzionale ma Raccomandato)

### WAF (Web Application Firewall)
- ModSecurity per Apache
- Cloudflare WAF
- AWS WAF

### IP Whitelisting
```apache
<Location /backoffice/llm_query_interface.php>
    Order Deny,Allow
    Deny from all
    Allow from 192.168.1.0/24  # Rete interna
    Allow from YOUR_OFFICE_IP
</Location>
```

### Two-Factor Authentication
```php
// Richiedi 2FA per admin
if (!verify_2fa_token($userId, $_POST['2fa_token'])) {
    die('2FA verification failed');
}
```

### Database Encryption
```sql
-- Cripta dati sensibili a riposo
ALTER TABLE professionista 
MODIFY email VARCHAR(255) 
ENCRYPTED;
```

---

## 📋 Checklist Go-Live

Prima di mettere in produzione:

- [ ] Tutti i test di sicurezza passati
- [ ] Penetration test eseguito
- [ ] Backup verificato e funzionante
- [ ] Monitoring configurato
- [ ] Team informato su procedure
- [ ] Documentazione completa
- [ ] Piano di incident response pronto
- [ ] Contatti di emergenza definiti

---

## 🆘 Incident Response Plan

### In caso di breach sospetto:

1. **IMMEDIATO (0-5 minuti)**
   ```bash
   # Disabilita sistema
   mv llm_query_interface.php llm_query_interface.php.disabled
   
   # Cambia password DB
   mysql -u root -p -e "ALTER USER 'llm_readonly'@'localhost' IDENTIFIED BY 'NEW_PASS';"
   ```

2. **ANALISI (5-30 minuti)**
   ```bash
   # Analizza log
   tail -n 1000 logs/llm_queries.log > incident_$(date +%s).log
   
   # Controlla accessi
   tail -n 1000 /var/log/apache2/access.log > access_$(date +%s).log
   ```

3. **REMEDIATION**
   - Identifica vettore d'attacco
   - Applica patch
   - Resetta credenziali
   - Notifica utenti se necessario

4. **POST-MORTEM**
   - Documenta incidente
   - Aggiorna procedure
   - Implementa controlli aggiuntivi

---

## 📞 Contatti Emergenza

**Security Team:**
- Email: security@ebookecm.it
- Telefono: XXX-XXX-XXXX
- On-call: [Nome responsabile]

**Escalation:**
1. Admin sistema → 2. CTO → 3. CEO

---

## 📚 Risorse Sicurezza

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)
- [MySQL Security](https://dev.mysql.com/doc/refman/8.0/en/security.html)

---

**⚠️ Ricorda: La sicurezza è un processo continuo, non un risultato!**

Rivedi questa checklist:
- [ ] Mensilmente per aggiornamenti
- [ ] Dopo ogni modifica al sistema
- [ ] Dopo ogni incidente di sicurezza
- [ ] Prima di ogni audit

**Ultima revisione:** {{ date }}
**Prossima revisione:** {{ date + 1 month }}
