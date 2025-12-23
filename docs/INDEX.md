# 📚 LLM Query Builder - Indice Completo

Sistema completo per interrogare database MySQL usando linguaggio naturale con AI.

---

## 📦 Pacchetto Completo

**Download:** `llm_query_builder.zip` (21 KB)

Contiene tutti i file necessari per l'installazione.

---

## 🎯 Quick Start

**Leggi prima:** `QUICK_START.md` (5.1 KB)
- Installazione in 5 minuti
- Configurazione rapida
- Primo test
- Troubleshooting comuni

**Per utenti esperti:**
```bash
unzip llm_query_builder.zip
mysql -u root -p < create_readonly_user.sql
php generate_schema.php
# Configura config_llm_query.php
php test_installation.php
```

---

## 📖 Documentazione

### 1. README.md (6.8 KB)
**Documentazione principale completa**
- Caratteristiche sistema
- Installazione dettagliata
- Configurazione avanzata
- Troubleshooting
- Monitoraggio
- TODO e roadmap

### 2. QUICK_START.md (5.1 KB)
**Guida installazione rapida**
- Setup in 5 passi
- Configurazioni ready-to-use
- Checklist installazione
- Primi test

### 3. SECURITY_CHECKLIST.md (9.7 KB)
**⚠️ CRITICO - Leggi prima della produzione**
- Checklist sicurezza pre-produzione
- Best practices
- Hardening sistema
- Incident response plan
- Monitoring sicurezza
- Red flags da controllare

### 4. LLM_PROVIDERS_CONFIG.md (9.7 KB)
**Configurazioni provider LLM**
- OpenRouter (raccomandato)
- Together AI
- DeepSeek
- Anthropic (Claude)
- OpenAI
- Confronto costi
- Test comparativo

### 5. ESEMPI_QUERY.md (7.3 KB)
**Esempi di utilizzo**
- Query base
- Query analytics
- Query complesse
- Tips per query migliori
- Pattern comuni
- Query di test

---

## 💻 File PHP - Core Sistema

### 1. llm_query_interface.php (16 KB)
**Interfaccia web principale**
- Frontend HTML/CSS/JavaScript
- Form input query naturale
- Visualizzazione risultati in tabella
- Gestione loading e errori
- Pulsanti esporta (da implementare)

**Accesso:** `https://www.ebookecm.it/backoffice/llm_query_interface.php`

### 2. api_llm_query.php (11 KB)
**Backend API REST**
- Endpoint `/api_llm_query.php`
- Actions: `generate`, `execute`
- Chiamata API LLM
- Validazione SQL
- Esecuzione query
- Cache management
- Error handling

**Metodi:**
```javascript
POST /api_llm_query.php
{
    "action": "generate",
    "natural_query": "...",
    "execute": true
}
```

### 3. config_llm_query.php (3.9 KB)
**⚙️ File configurazione centrale**
- Credenziali database
- API LLM configuration
- Parametri sicurezza
- Limiti e timeout
- Path file e directory
- Funzioni utility

**DA CONFIGURARE:**
- Database credentials
- API keys
- Limiti sistema

### 4. generate_schema.php (2.2 KB)
**Script generazione schema DB**
- Estrae struttura database
- Genera file `db_schema.sql`
- Include esempi dati
- Statistiche tabelle

**Uso:**
```bash
php generate_schema.php
```

### 5. test_installation.php (6.2 KB)
**Script test sistema**
- 7 test automatici
- Verifica configurazione
- Test database
- Test API LLM
- Test permessi
- Report completo

**Uso:**
```bash
php test_installation.php
```

---

## 🗄️ File Database

### 1. create_readonly_user.sql (3.0 KB)
**Script creazione utente sicuro**
- Crea utente `llm_readonly`
- Solo permessi SELECT
- Limiti rate/connessioni
- Istruzioni sicurezza

**⚠️ IMPORTANTE:** Cambia la password prima di eseguire!

**Uso:**
```bash
mysql -u root -p < create_readonly_user.sql
```

### 2. db_schema.sql (da generare)
**Schema database completo**
- Struttura tutte le tabelle
- CREATE TABLE statements
- Indici e chiavi
- Esempi record (opzionale)

**Generazione:**
```bash
php generate_schema.php
# oppure
mysqldump -u root -p --no-data ebookecm > db_schema.sql
```

---

## 🔧 Script Manutenzione

### maintenance.sh (11 KB) ⭐
**Script bash manutenzione automatica**

**Funzionalità:**
- ✅ Backup configurazioni e log
- ✅ Pulizia file vecchi
- ✅ Monitoraggio errori
- ✅ Verifica spazio disco
- ✅ Test database
- ✅ Aggiornamento schema
- ✅ Report statistiche uso

**Uso interattivo:**
```bash
chmod +x maintenance.sh
./maintenance.sh
```

**Uso da comando:**
```bash
./maintenance.sh backup      # Solo backup
./maintenance.sh cleanup     # Solo pulizia
./maintenance.sh health      # Solo check
./maintenance.sh full        # Tutto
./maintenance.sh auto        # Per cron
```

**Automazione (crontab):**
```bash
# Backup giornaliero
0 2 * * * /path/to/maintenance.sh backup

# Pulizia settimanale
0 3 * * 0 /path/to/maintenance.sh cleanup

# Health check ogni ora
0 * * * * /path/to/maintenance.sh health

# Manutenzione completa mensile
0 1 1 * * /path/to/maintenance.sh full
```

---

## 🔒 File Sicurezza

### .htaccess (1.8 KB)
**Configurazione Apache per sicurezza**
- Blocca accesso file config
- Blocca accesso log/cache
- Security headers
- Limiti upload
- Timeout PHP

**Posizionamento:**
```
/backoffice/.htaccess
```

---

## 📊 Struttura Directory

```
/backoffice/
├── llm_query_interface.php     # ← Interfaccia web (accesso pubblico backoffice)
├── api_llm_query.php          # ← API backend (accesso pubblico backoffice)
├── config_llm_query.php       # ← Config (protetto da .htaccess)
├── generate_schema.php        # ← Helper (protetto da .htaccess)
├── test_installation.php      # ← Test (protetto da .htaccess)
├── maintenance.sh             # ← Script manutenzione
├── .htaccess                  # ← Sicurezza Apache
├── db_schema.sql             # ← Schema DB (generato, protetto)
├── create_readonly_user.sql  # ← SQL setup (protetto)
├── logs/                     # ← Directory log (auto-creata, protetta)
│   └── llm_queries.log
└── cache/                    # ← Directory cache (auto-creata, protetta)
    └── llm/
```

---

## 🎯 Workflow Installazione

1. **Download e estrazione**
   ```bash
   cd /var/www/html/backoffice/
   unzip llm_query_builder.zip
   ```

2. **Setup database**
   ```bash
   # Modifica password in create_readonly_user.sql
   mysql -u root -p < create_readonly_user.sql
   ```

3. **Genera schema**
   ```bash
   php generate_schema.php
   ```

4. **Configura**
   - Apri `config_llm_query.php`
   - Configura DB credentials
   - Configura API LLM
   - Salva

5. **Test**
   ```bash
   php test_installation.php
   ```

6. **Sicurezza**
   - Leggi `SECURITY_CHECKLIST.md`
   - Applica tutti i controlli
   - Verifica .htaccess

7. **Go Live**
   - Accedi all'interfaccia web
   - Fai primo test
   - Monitora log

---

## 📚 Ordine di Lettura Consigliato

### Per installazione rapida:
1. `QUICK_START.md` - Setup veloce
2. `LLM_PROVIDERS_CONFIG.md` - Scegli provider
3. `ESEMPI_QUERY.md` - Primi test

### Per installazione completa:
1. `README.md` - Panoramica completa
2. `SECURITY_CHECKLIST.md` - **CRITICO**
3. `LLM_PROVIDERS_CONFIG.md` - Configurazione API
4. `QUICK_START.md` - Installazione
5. `ESEMPI_QUERY.md` - Test e utilizzo

### Per manutenzione:
1. `maintenance.sh` - Script automatico
2. `SECURITY_CHECKLIST.md` - Monitoring
3. Log files - Controllo errori

---

## 🔍 Riferimenti Rapidi

### File da configurare OBBLIGATORIAMENTE:
- ✅ `config_llm_query.php` - Database + API LLM
- ✅ `create_readonly_user.sql` - Password DB

### File da eseguire una volta:
- ✅ `create_readonly_user.sql` - Setup DB user
- ✅ `generate_schema.php` - Crea schema
- ✅ `test_installation.php` - Verifica setup

### File da usare regolarmente:
- ✅ `llm_query_interface.php` - Interfaccia principale
- ✅ `maintenance.sh` - Manutenzione
- ✅ `logs/llm_queries.log` - Monitoring

### File di solo riferimento:
- 📖 `README.md`
- 📖 `QUICK_START.md`
- 📖 `SECURITY_CHECKLIST.md`
- 📖 `LLM_PROVIDERS_CONFIG.md`
- 📖 `ESEMPI_QUERY.md`

---

## 🆘 Troubleshooting Quick Links

**Problema:** Sistema non funziona
→ Esegui: `php test_installation.php`

**Problema:** Errori database
→ Leggi: `SECURITY_CHECKLIST.md` → Sezione Database

**Problema:** Errori API LLM
→ Leggi: `LLM_PROVIDERS_CONFIG.md`

**Problema:** Query non generata correttamente
→ Leggi: `ESEMPI_QUERY.md` → Tips

**Problema:** Sicurezza
→ Leggi: `SECURITY_CHECKLIST.md` (tutto!)

**Problema:** Logs pieni / Cache enorme
→ Esegui: `./maintenance.sh cleanup`

---

## 📊 Dimensioni File

```
llm_query_builder.zip         21 KB   ← Download unico
llm_query_interface.php       16 KB   ← Interfaccia
api_llm_query.php             11 KB   ← Backend
maintenance.sh                11 KB   ← Manutenzione
SECURITY_CHECKLIST.md        9.7 KB   ← Sicurezza
LLM_PROVIDERS_CONFIG.md      9.7 KB   ← Config LLM
ESEMPI_QUERY.md              7.3 KB   ← Esempi
README.md                    6.8 KB   ← Doc principale
test_installation.php        6.2 KB   ← Test
QUICK_START.md               5.1 KB   ← Quick start
config_llm_query.php         3.9 KB   ← Config
create_readonly_user.sql     3.0 KB   ← Setup DB
generate_schema.php          2.2 KB   ← Helper
.htaccess                    1.8 KB   ← Sicurezza

TOTALE: ~115 KB (completo)
```

---

## ✅ Checklist Finale

- [ ] Download `llm_query_builder.zip`
- [ ] Estratto tutti i file
- [ ] Letto `QUICK_START.md`
- [ ] Creato utente DB readonly
- [ ] Generato schema DB
- [ ] Configurato `config_llm_query.php`
- [ ] Eseguito `test_installation.php` → Tutto ✅
- [ ] Letto `SECURITY_CHECKLIST.md`
- [ ] Copiato `.htaccess`
- [ ] Testato interfaccia web
- [ ] Configurato `maintenance.sh` in cron
- [ ] Sistema in produzione 🎉

---

## 🚀 Go Live!

Una volta completata la checklist:

**URL:** `https://www.ebookecm.it/backoffice/llm_query_interface.php`

**Test query:** "Dammi 5 email di professionisti"

**Se funziona:** 🎉 Sei pronto!

---

## 📞 Supporto

- **Documentazione:** Tutti i file `.md`
- **Test:** `php test_installation.php`
- **Log:** `logs/llm_queries.log`
- **Manutenzione:** `./maintenance.sh`

---

**Sistema sviluppato per EbookECM**
**Powered by Qwen 3-coder & Claude Sonnet 4.5**
**Versione:** 1.0.0
**Data:** Dicembre 2025
