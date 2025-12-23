#!/bin/bash

#############################################
# LLM Query Builder - Maintenance Script
# Backup, pulizia, monitoraggio automatico
#############################################

# Configurazione
INSTALL_DIR="/var/www/html/backoffice"
BACKUP_DIR="/var/backups/llm_query_builder"
DB_NAME="ebookecm"
DB_USER="llm_readonly"
LOG_DIR="$INSTALL_DIR/logs"
CACHE_DIR="$INSTALL_DIR/cache"
DAYS_TO_KEEP_LOGS=30
DAYS_TO_KEEP_BACKUPS=7

# Colori output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funzioni utility
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Crea directory backup se non esiste
mkdir -p "$BACKUP_DIR"

#############################################
# 1. BACKUP CONFIGURAZIONI
#############################################
backup_configs() {
    log_info "Backup configurazioni..."
    
    BACKUP_FILE="$BACKUP_DIR/config_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    
    tar -czf "$BACKUP_FILE" \
        -C "$INSTALL_DIR" \
        config_llm_query.php \
        db_schema.sql \
        2>/dev/null
    
    if [ $? -eq 0 ]; then
        log_info "Backup salvato: $BACKUP_FILE"
        BACKUP_SIZE=$(du -h "$BACKUP_FILE" | cut -f1)
        log_info "Dimensione: $BACKUP_SIZE"
    else
        log_error "Backup fallito!"
        return 1
    fi
}

#############################################
# 2. BACKUP LOG
#############################################
backup_logs() {
    log_info "Backup log..."
    
    if [ -d "$LOG_DIR" ]; then
        LOG_BACKUP="$BACKUP_DIR/logs_backup_$(date +%Y%m%d).tar.gz"
        tar -czf "$LOG_BACKUP" -C "$INSTALL_DIR" logs/ 2>/dev/null
        
        if [ $? -eq 0 ]; then
            log_info "Log backup: $LOG_BACKUP"
        fi
    fi
}

#############################################
# 3. PULIZIA LOG VECCHI
#############################################
cleanup_old_logs() {
    log_info "Pulizia log vecchi (>$DAYS_TO_KEEP_LOGS giorni)..."
    
    if [ -d "$LOG_DIR" ]; then
        DELETED=$(find "$LOG_DIR" -name "*.log" -mtime +$DAYS_TO_KEEP_LOGS -delete -print | wc -l)
        log_info "Eliminati $DELETED file log vecchi"
    fi
}

#############################################
# 4. PULIZIA CACHE
#############################################
cleanup_cache() {
    log_info "Pulizia cache..."
    
    if [ -d "$CACHE_DIR" ]; then
        # Elimina file cache più vecchi di 24 ore
        DELETED=$(find "$CACHE_DIR" -type f -mtime +1 -delete -print | wc -l)
        log_info "Eliminati $DELETED file cache vecchi"
        
        # Calcola dimensione cache rimanente
        CACHE_SIZE=$(du -sh "$CACHE_DIR" 2>/dev/null | cut -f1)
        log_info "Dimensione cache attuale: $CACHE_SIZE"
    fi
}

#############################################
# 5. PULIZIA BACKUP VECCHI
#############################################
cleanup_old_backups() {
    log_info "Pulizia backup vecchi (>$DAYS_TO_KEEP_BACKUPS giorni)..."
    
    DELETED=$(find "$BACKUP_DIR" -name "*.tar.gz" -mtime +$DAYS_TO_KEEP_BACKUPS -delete -print | wc -l)
    log_info "Eliminati $DELETED backup vecchi"
}

#############################################
# 6. VERIFICA PERMESSI
#############################################
check_permissions() {
    log_info "Verifica permessi file..."
    
    # Directory devono essere 755
    find "$INSTALL_DIR" -type d ! -perm 755 -exec chmod 755 {} \; 2>/dev/null
    
    # File PHP devono essere 644
    find "$INSTALL_DIR" -name "*.php" ! -perm 644 -exec chmod 644 {} \; 2>/dev/null
    
    # Config deve essere 600 (solo owner)
    if [ -f "$INSTALL_DIR/config_llm_query.php" ]; then
        chmod 600 "$INSTALL_DIR/config_llm_query.php"
    fi
    
    log_info "Permessi verificati e corretti"
}

#############################################
# 7. MONITORAGGIO ERRORI
#############################################
check_errors() {
    log_info "Controllo errori recenti..."
    
    if [ -f "$LOG_DIR/llm_queries.log" ]; then
        # Conta errori nelle ultime 24 ore
        ERROR_COUNT=$(grep -c "ERROR\|FAILED" "$LOG_DIR/llm_queries.log" 2>/dev/null || echo "0")
        
        if [ "$ERROR_COUNT" -gt 50 ]; then
            log_warn "⚠️  ATTENZIONE: $ERROR_COUNT errori trovati nel log!"
            log_warn "Controlla: $LOG_DIR/llm_queries.log"
            
            # Invia email alert (opzionale)
            # echo "Trovati $ERROR_COUNT errori" | mail -s "Alert: Errori LLM Query Builder" admin@ebookecm.it
        else
            log_info "Errori trovati: $ERROR_COUNT (normale)"
        fi
    fi
}

#############################################
# 8. VERIFICA SPAZIO DISCO
#############################################
check_disk_space() {
    log_info "Verifica spazio disco..."
    
    DISK_USAGE=$(df -h "$INSTALL_DIR" | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$DISK_USAGE" -gt 90 ]; then
        log_error "⚠️  SPAZIO DISCO CRITICO: ${DISK_USAGE}% utilizzato!"
    elif [ "$DISK_USAGE" -gt 80 ]; then
        log_warn "Spazio disco: ${DISK_USAGE}% utilizzato"
    else
        log_info "Spazio disco: ${DISK_USAGE}% utilizzato (OK)"
    fi
}

#############################################
# 9. TEST CONNESSIONE DATABASE
#############################################
test_database() {
    log_info "Test connessione database..."
    
    mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        log_info "✅ Database: Connessione OK"
    else
        log_error "❌ Database: Connessione FALLITA"
        return 1
    fi
}

#############################################
# 10. REPORT STATISTICHE USO
#############################################
usage_stats() {
    log_info "Statistiche utilizzo..."
    
    if [ -f "$LOG_DIR/llm_queries.log" ]; then
        # Conta query oggi
        TODAY=$(date +%Y-%m-%d)
        QUERIES_TODAY=$(grep "$TODAY" "$LOG_DIR/llm_queries.log" | wc -l)
        
        # Conta query questa settimana
        WEEK_AGO=$(date -d '7 days ago' +%Y-%m-%d)
        QUERIES_WEEK=$(grep -E "$(date +%Y-%m-(0[1-9]|[12][0-9]|3[01]))" "$LOG_DIR/llm_queries.log" | wc -l)
        
        log_info "Query oggi: $QUERIES_TODAY"
        log_info "Query questa settimana: $QUERIES_WEEK"
    fi
}

#############################################
# 11. AGGIORNA SCHEMA DATABASE
#############################################
update_schema() {
    log_info "Aggiornamento schema database..."
    
    if [ -f "$INSTALL_DIR/generate_schema.php" ]; then
        php "$INSTALL_DIR/generate_schema.php" > /dev/null 2>&1
        
        if [ $? -eq 0 ]; then
            log_info "✅ Schema aggiornato"
        else
            log_error "❌ Aggiornamento schema fallito"
        fi
    fi
}

#############################################
# MENU PRINCIPALE
#############################################

show_menu() {
    echo ""
    echo "========================================="
    echo "  LLM Query Builder - Manutenzione"
    echo "========================================="
    echo ""
    echo "1) Backup completo"
    echo "2) Pulizia (log, cache, backup vecchi)"
    echo "3) Controllo salute sistema"
    echo "4) Aggiorna schema database"
    echo "5) Report statistiche"
    echo "6) Manutenzione completa (1+2+3+4)"
    echo "0) Esci"
    echo ""
    echo -n "Scegli opzione: "
}

# Parsing argomenti comando
case "$1" in
    "backup")
        backup_configs
        backup_logs
        ;;
    "cleanup")
        cleanup_old_logs
        cleanup_cache
        cleanup_old_backups
        ;;
    "health")
        check_errors
        check_disk_space
        test_database
        ;;
    "update")
        update_schema
        ;;
    "stats")
        usage_stats
        ;;
    "full")
        log_info "=== MANUTENZIONE COMPLETA ==="
        backup_configs
        backup_logs
        cleanup_old_logs
        cleanup_cache
        cleanup_old_backups
        check_permissions
        check_errors
        check_disk_space
        test_database
        update_schema
        usage_stats
        log_info "=== COMPLETATO ==="
        ;;
    "auto")
        # Modalità automatica (per cron)
        backup_configs >/dev/null
        cleanup_old_logs >/dev/null
        cleanup_cache >/dev/null
        check_errors
        ;;
    *)
        # Menu interattivo
        while true; do
            show_menu
            read choice
            
            case $choice in
                1)
                    backup_configs
                    backup_logs
                    ;;
                2)
                    cleanup_old_logs
                    cleanup_cache
                    cleanup_old_backups
                    ;;
                3)
                    check_errors
                    check_disk_space
                    test_database
                    check_permissions
                    ;;
                4)
                    update_schema
                    ;;
                5)
                    usage_stats
                    ;;
                6)
                    log_info "=== MANUTENZIONE COMPLETA ==="
                    backup_configs
                    backup_logs
                    cleanup_old_logs
                    cleanup_cache
                    cleanup_old_backups
                    check_permissions
                    check_errors
                    check_disk_space
                    test_database
                    update_schema
                    usage_stats
                    log_info "=== COMPLETATO ==="
                    ;;
                0)
                    echo "Uscita..."
                    exit 0
                    ;;
                *)
                    log_error "Opzione non valida"
                    ;;
            esac
            
            echo ""
            read -p "Premi INVIO per continuare..."
        done
        ;;
esac

#############################################
# CRONTAB ESEMPIO
#############################################

# Aggiungi a crontab per manutenzione automatica:
# crontab -e

# Backup giornaliero alle 2 AM
# 0 2 * * * /path/to/maintenance.sh backup >> /var/log/llm_maintenance.log 2>&1

# Pulizia settimanale (domenica alle 3 AM)
# 0 3 * * 0 /path/to/maintenance.sh cleanup >> /var/log/llm_maintenance.log 2>&1

# Controllo salute ogni ora
# 0 * * * * /path/to/maintenance.sh health >> /var/log/llm_maintenance.log 2>&1

# Aggiornamento schema settimanale
# 0 4 * * 0 /path/to/maintenance.sh update >> /var/log/llm_maintenance.log 2>&1

# Manutenzione completa mensile (1° del mese alle 1 AM)
# 0 1 1 * * /path/to/maintenance.sh full >> /var/log/llm_maintenance.log 2>&1
