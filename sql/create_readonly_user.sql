-- Script per creare un utente MySQL READ-ONLY per il Query Builder AI
-- IMPORTANTE: Questo utente avrà SOLO permessi di lettura (SELECT)

-- 1. Crea l'utente (cambia la password!)
CREATE USER IF NOT EXISTS 'llm_readonly'@'localhost' 
IDENTIFIED BY 'PASSWORD_SICURA_QUI';

-- 2. Concedi SOLO permessi SELECT sul database
GRANT SELECT ON yourdb.* TO 'llm_readonly'@'localhost';

-- 3. Nega esplicitamente permessi pericolosi (ridondante ma sicuro)
-- Questo è automatico: senza GRANT non può fare INSERT/UPDATE/DELETE/DROP

-- 4. Imposta limiti di sicurezza per prevenire abuse
ALTER USER 'llm_readonly'@'localhost' 
WITH 
    MAX_QUERIES_PER_HOUR 1000           -- Max 1000 query all'ora
    MAX_UPDATES_PER_HOUR 0              -- Nessun UPDATE permesso
    MAX_CONNECTIONS_PER_HOUR 100        -- Max 100 connessioni all'ora
    MAX_USER_CONNECTIONS 5;             -- Max 5 connessioni simultanee

-- 5. Applica i permessi
FLUSH PRIVILEGES;

-- 6. Verifica i permessi (esegui per controllare)
-- SHOW GRANTS FOR 'llm_readonly'@'localhost';

-- 7. Test di connessione (da terminale)
-- mysql -u llm_readonly -p -h localhost ebookecm

-- ============================================
-- CONFIGURAZIONE OPZIONALE: Tabelle specifiche
-- ============================================
-- Se vuoi limitare l'accesso solo ad alcune tabelle:

-- REVOKE SELECT ON ebookecm.* FROM 'llm_readonly'@'localhost';

-- Concedi accesso solo a tabelle specifiche:
-- GRANT SELECT ON ebookecm.professionista TO 'llm_readonly'@'localhost';
-- GRANT SELECT ON ebookecm.ordine_fattura TO 'llm_readonly'@'localhost';
-- GRANT SELECT ON ebookecm.__o TO 'llm_readonly'@'localhost';
-- ... aggiungi altre tabelle necessarie

-- ============================================
-- SICUREZZA AGGIUNTIVA
-- ============================================

-- Limita l'accesso da specifici IP (opzionale)
-- CREATE USER 'llm_readonly'@'192.168.1.100' IDENTIFIED BY 'password';
-- GRANT SELECT ON ebookecm.* TO 'llm_readonly'@'192.168.1.100';

-- ============================================
-- MONITORAGGIO
-- ============================================

-- Query per vedere l'uso dell'utente:
-- SELECT * FROM information_schema.user_statistics WHERE USER = 'llm_readonly';

-- Query per vedere le connessioni attive:
-- SELECT * FROM information_schema.processlist WHERE USER = 'llm_readonly';

-- ============================================
-- RIMOZIONE (se necessario)
-- ============================================
-- Per rimuovere l'utente:
-- DROP USER IF EXISTS 'llm_readonly'@'localhost';

-- ============================================
-- NOTE IMPORTANTI
-- ============================================
/*
1. L'utente 'llm_readonly' può SOLO fare SELECT
2. Non può modificare, cancellare o creare dati
3. Ha limiti di rate per prevenire abuse
4. Cambia SEMPRE la password di default!
5. Monitora l'uso tramite information_schema

DOPO LA CREAZIONE:
- Aggiorna config_llm_query.php con:
  define('DB_USER', 'llm_readonly');
  define('DB_PASS', 'PASSWORD_SICURA_QUI_123!');
*/
