<?php
/**
 * Interfaccia LLM Query Builder per EbookECM
 * Permette di interrogare il database MySQL usando linguaggio naturale
 */

// Configurazione
require_once 'config_llm_query.php';

session_start();

$model_tk = explode("/", LLM_MODEL, 2);
$model = (count($model_tk) == 2) ? ucwords($model_tk[1]) : ucwords($model_tk[0]);

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Query Builder AI - EbookECM</title>
    <link href="llm_query_builder.css" rel="stylesheet" />
</head>
<body>
    <div class="container">
        <div class="header">
            <picture>
                <source type="image/svg+xml" srcset="https://d13noyg176odx.cloudfront.net/assets/ebookecmlogo/logo.svg">
                <source type="image/png" srcset="
                    https://d13noyg176odx.cloudfront.net/assets/ebookecmlogo/logo_1x.png,
                    https://d13noyg176odx.cloudfront.net/assets/ebookecmlogo/logo_2x.png 2x">
                <img src="https://d13noyg176odx.cloudfront.net/assets/ebookecmlogo/logo_1x.png" width="80" height="60" alt="Logo ebookecm" class="logo">
            </picture>
            <div class="header-content">
                <h1>🤖 Query Builder AI</h1>
                <p class="subtitle">Interroga il database usando linguaggio naturale - Powered by <?= $model; ?></p>
            </div>
        </div>

        <div class="main-content">
            <section id="tools">
                <div class="left-panel">
                    <div class="info-box" id="infoBox">
                        <button class="info-box-close" id="closeInfoBox" title="Chiudi">×</button>
                        <strong>💡 Esempio:</strong> "Dammi le email dei professionisti che hanno acquistato nel 2025" oppure "Mostrami i 10 ebook più venduti questo mese"
                    </div>

                    <div class="query-section">
                        <label for="naturalQuery">Descrivi cosa vuoi sapere:</label>
                        <textarea
                            id="naturalQuery"
                            rows="3"
                            placeholder="Es: Da queste fatture recuperami le email: AB4558, AB4614, AB4673..."
                        ></textarea>

                        <div class="button-group">
                            <button class="btn-primary" id="generateBtn">
                                🚀 Genera e Esegui Query
                            </button>
                            <button class="btn-secondary" id="generateOnlyBtn">
                                📝 Solo Genera Query (non eseguire)
                            </button>
                            <button class="btn-danger" id="clearBtn">
                                🗑️ Pulisci
                            </button>
                        </div>
                    </div>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p>Sto elaborando la tua richiesta...</p>
                    </div>

                    <div id="messageContainer"></div>
                </div>

                <div class="right-panel">
                    <button class="sidebar-toggle" id="toggleResults" title="Mostra/Nascondi Query SQL">👁️ Nascondi</button>
                    <div class="result-section" id="resultSection">
                        <div class="sql-display" id="sqlDisplay">
                            <h3>📋 Query SQL Generata:</h3>
                            <pre class="sql-code" id="sqlCode" contenteditable="true"></pre>
                            <div class="button-group" style="margin-top: 15px;">
                                <button class="btn-secondary" id="executeBtn" style="display: none;">
                                    ▶️ Esegui Query
                                </button>
                                <button class="btn-secondary" id="copyBtn">
                                    📋 Copia Query
                                </button>
                            </div>
                        </div>


                    </div>
                </div>

        </section>
        <div id="resizeHandle" class="resize-handle">
            <div class="resize-handle-line"></div>
            <div class="resize-handle-line"></div>
        </div>
        <section id="results">
            <div id="tableContainer"></div>
        </section>
        </div>
    </div>

    <script>
        const generateBtn = document.getElementById('generateBtn');
        const generateOnlyBtn = document.getElementById('generateOnlyBtn');
        const executeBtn = document.getElementById('executeBtn');
        const clearBtn = document.getElementById('clearBtn');
        const copyBtn = document.getElementById('copyBtn');
        const naturalQuery = document.getElementById('naturalQuery');
        const loading = document.getElementById('loading');
        const resultSection = document.getElementById('resultSection');
        const sqlCode = document.getElementById('sqlCode');
        const tableContainer = document.getElementById('tableContainer');
        const messageContainer = document.getElementById('messageContainer');
        const toggleResults = document.getElementById('toggleResults');
        const sqlDisplay = document.getElementById('sqlDisplay');
        const infoBox = document.getElementById('infoBox');
        const closeInfoBox = document.getElementById('closeInfoBox');
        const resizeHandle = document.getElementById('resizeHandle');
        const toolsSection = document.getElementById('tools');

        let currentSql = '';
        let query_history = [];
        let history_index = -1;
        let current_results = null;
        let is_resizing = false;
        let start_y = 0;
        let start_height = 0;

        // Inizializzazione: carica history e ultima query
        function init_app() {
            // Carica history da localStorage
            const saved_history = localStorage.getItem('query_history');
            if (saved_history) {
                try {
                    query_history = JSON.parse(saved_history);
                } catch (e) {
                    query_history = [];
                }
            }

            // Carica ultima query
            const last_query = localStorage.getItem('last_query');
            if (last_query) {
                naturalQuery.value = last_query;
            }

            // Ripristina stato toggle SQL display
            const sql_display_hidden = localStorage.getItem('sql_display_hidden') === 'true';
            if (sql_display_hidden && sqlDisplay) {
                sqlDisplay.style.display = 'none';
                toggleResults.textContent = '👁️ Mostra';
            } else {
                if (sqlDisplay) {
                    sqlDisplay.style.display = 'block';
                }
                toggleResults.textContent = '👁️ Nascondi';
            }

            // Ripristina stato info-box
            const info_box_closed = localStorage.getItem('info_box_closed') === 'true';
            if (info_box_closed && infoBox) {
                infoBox.classList.add('hidden');
            }
        }

        // Salva query nella history
        function save_to_history(query) {
            if (!query.trim()) return;

            // Rimuovi duplicati recenti
            const index = query_history.indexOf(query);
            if (index !== -1) {
                query_history.splice(index, 1);
            }

            // Aggiungi all'inizio
            query_history.unshift(query);

            // Limita a 50 query
            if (query_history.length > 50) {
                query_history = query_history.slice(0, 50);
            }

            // Salva in localStorage
            localStorage.setItem('query_history', JSON.stringify(query_history));
            localStorage.setItem('last_query', query);

            // Reset index
            history_index = -1;
        }

        // Navigazione history con frecce
        naturalQuery.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (query_history.length === 0) return;

                history_index++;
                if (history_index >= query_history.length) {
                    history_index = query_history.length - 1;
                }

                naturalQuery.value = query_history[history_index];
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (query_history.length === 0) return;

                history_index--;
                if (history_index < 0) {
                    history_index = -1;
                    naturalQuery.value = '';
                } else {
                    naturalQuery.value = query_history[history_index];
                }
            }
        });

        // Toggle SQL display
        toggleResults.addEventListener('click', () => {
            if (!sqlDisplay) return;
            const is_hidden = sqlDisplay.style.display === 'none';
            sqlDisplay.style.display = is_hidden ? 'block' : 'none';
            toggleResults.textContent = is_hidden ? '👁️ Nascondi' : '👁️ Mostra';
            localStorage.setItem('sql_display_hidden', !is_hidden);
        });

        // Chiudi info-box
        closeInfoBox.addEventListener('click', () => {
            if (!infoBox) return;
            infoBox.classList.add('hidden');
            localStorage.setItem('info_box_closed', 'true');
        });

        // Inizializza app al caricamento
        init_app();

        // === RESIZE HANDLE ===
        resizeHandle.addEventListener('mousedown', (e) => {
            is_resizing = true;
            start_y = e.clientY;
            start_height = toolsSection.offsetHeight;
            document.body.style.cursor = 'ns-resize';
            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', (e) => {
            if (!is_resizing) return;

            const delta = e.clientY - start_y;
            const new_height = start_height + delta;

            // Limita altezza minima e massima
            const min_height = 200;
            const max_height = window.innerHeight - 300;

            if (new_height >= min_height && new_height <= max_height) {
                toolsSection.style.flex = `0 0 ${new_height}px`;
            }
        });

        document.addEventListener('mouseup', () => {
            if (is_resizing) {
                is_resizing = false;
                document.body.style.cursor = '';
                document.body.style.userSelect = '';

                // Salva altezza in localStorage
                const current_height = toolsSection.offsetHeight;
                localStorage.setItem('tools_height', current_height);
            }
        });

        // Ripristina altezza salvata
        const saved_height = localStorage.getItem('tools_height');
        if (saved_height) {
            toolsSection.style.flex = `0 0 ${saved_height}px`;
        }

        // Genera e esegui
        generateBtn.addEventListener('click', () => executeQuery(true));
        
        // Solo genera
        generateOnlyBtn.addEventListener('click', () => executeQuery(false));
        
        // Esegui query già generata
        executeBtn.addEventListener('click', () => {
            if (currentSql) {
                executeGeneratedQuery(currentSql);
            }
        });
        
        // Pulisci tutto
        clearBtn.addEventListener('click', () => {
            naturalQuery.value = '';
            sqlCode.textContent = '';
            tableContainer.innerHTML = '';
            messageContainer.innerHTML = '';
            currentSql = '';
        });
        
        // Copia query
        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(currentSql).then(() => {
                showMessage('Query copiata negli appunti!', 'success');
            });
        });

        // Aggiorna currentSql quando l'utente modifica il codice SQL
        sqlCode.addEventListener('input', () => {
            currentSql = sqlCode.textContent;
        });
        
        async function executeQuery(shouldExecute) {
            const query = naturalQuery.value.trim();

            if (!query) {
                showMessage('Inserisci una richiesta!', 'error');
                return;
            }

            // Salva nella history
            save_to_history(query);

            setLoading(true);
            messageContainer.innerHTML = '';
            
            try {
                const response = await fetch('api_llm_query.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'generate',
                        natural_query: query,
                        execute: shouldExecute
                    })
                });
                
                const data = await response.json();

                // Mostra SQL appena disponibile, prima di tutto
                if (data.sql_query) {
                    // Fix reserved word aliases
                    const fixed_sql = fix_reserved_word_aliases(data.sql_query);
                    currentSql = fixed_sql;
                    sqlCode.textContent = fixed_sql;

                    // Assicura che .sql-display sia visibile
                    if (sqlDisplay) {
                        sqlDisplay.style.display = 'block';
                        toggleResults.textContent = '👁️ Nascondi';
                    }

                    // Mostra sempre il pulsante Esegui quando c'è una query
                    executeBtn.style.display = 'inline-block';
                }

                // Poi gestisci risultati o errori
                if (data.success) {
                    if (shouldExecute && data.results) {
                        displayResults(data.results, data.row_count);
                    } else {
                        tableContainer.innerHTML = '';
                    }

                    if (data.llm_explanation) {
                        showMessage('💬 ' + data.llm_explanation, 'info');
                    }
                } else {
                    // Mostra errore ma la SQL è già stata mostrata sopra
                    showMessage('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                showMessage('Errore di connessione: ' + error.message, 'error');
            } finally {
                setLoading(false);
            }
        }
        
        async function executeGeneratedQuery(sql) {
            setLoading(true);
            messageContainer.innerHTML = '';
            
            try {
                const response = await fetch('api_llm_query.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'execute',
                        sql_query: sql
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.results, data.row_count);
                } else {
                    showMessage('Errore: ' + data.error, 'error');
                }
            } catch (error) {
                showMessage('Errore di connessione: ' + error.message, 'error');
            } finally {
                setLoading(false);
            }
        }
        
        function displayResults(results, rowCount) {
            if (!results || results.length === 0) {
                tableContainer.innerHTML = '<div class="info-box">Nessun risultato trovato</div>';
                current_results = null;
                return;
            }

            // Salva risultati per export
            current_results = results;

            const columns = Object.keys(results[0]);
            
            let html = '<div class="row-count"><div>📊 Trovati ' + rowCount + ' risultati</div>';
            
            html += '<div class="export-buttons">';
            html += '<button class="btn-default btn-mini" onclick="export_to_csv()">📥 Esporta CSV</button>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="results-table-container">';
            html += '<table><thead><tr>';
            
            columns.forEach(col => {
                html += '<th>' + escapeHtml(col) + '</th>';
            });
            
            html += '</tr></thead><tbody>';
            
            results.forEach(row => {
                html += '<tr>';
                columns.forEach(col => {
                    if(row[col] !== null) {
                        html += '<td>' + escapeHtml(String(row[col])) + '</td>';
                    }
                    else {
                        html += '<td><em>NULL</em></td>';
                    }
                    
                });
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            tableContainer.innerHTML = html;
        }
        
        function showMessage(message, type) {
            const className = type === 'error' ? 'error-message' : 
                            type === 'success' ? 'success-message' : 'info-box';
            messageContainer.innerHTML = '<div class="' + className + '">' + message + '</div>';
        }
        
        function setLoading(isLoading) {
            loading.classList.toggle('active', isLoading);
            generateBtn.disabled = isLoading;
            generateOnlyBtn.disabled = isLoading;
            executeBtn.disabled = isLoading;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function fix_reserved_word_aliases(sql) {
            // Lista di reserved words MySQL comuni che potrebbero essere usate come alias
            const mysql_reserved_words = new Set([
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
            ]);

            // Pattern per trovare alias dopo FROM o JOIN
            // Cattura: (FROM|JOIN) `table_name` alias oppure (FROM|JOIN) table_name AS alias
            const alias_pattern = /\b(FROM|JOIN|LEFT\s+JOIN|RIGHT\s+JOIN|INNER\s+JOIN|OUTER\s+JOIN|CROSS\s+JOIN)\s+`?(\w+)`?\s+(?:AS\s+)?(\w+)(?=\s|,|\)|$)/gi;

            return sql.replace(alias_pattern, (match, join_type, table_name, alias) => {
                // Se l'alias è una reserved word, NON è tutto maiuscolo (quindi è un vero alias)
                // e non è già tra backtick
                if (alias.toUpperCase() !== alias
                    && mysql_reserved_words.has(alias.toUpperCase())
                    && !match.includes('`' + alias + '`')) {
                    // Ricostruisci il match con l'alias tra backtick
                    const has_as = match.toUpperCase().includes(' AS ');
                    if (has_as) {
                        return `${join_type} \`${table_name}\` AS \`${alias}\``;
                    } else {
                        return `${join_type} \`${table_name}\` \`${alias}\``;
                    }
                }
                return match;
            });
        }

        function export_to_csv() {
            if (!current_results || current_results.length === 0) {
                showMessage('Nessun dato da esportare', 'error');
                return;
            }

            try {
                // Ottieni le colonne
                const columns = Object.keys(current_results[0]);

                // Crea header CSV
                let csv = columns.map(col => `"${col}"`).join(',') + '\n';

                // Aggiungi righe
                current_results.forEach(row => {
                    const values = columns.map(col => {
                        const value = row[col];
                        // Gestisci NULL
                        if (value === null || value === undefined) {
                            return '';
                        }
                        // Escape delle virgolette e wrapping
                        const stringValue = String(value).replace(/"/g, '""');
                        return `"${stringValue}"`;
                    });
                    csv += values.join(',') + '\n';
                });

                // Crea blob e scarica
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);

                // Nome file con timestamp
                const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
                const filename = `query_results_${timestamp}.csv`;

                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showMessage('CSV esportato con successo!', 'success');
            } catch (error) {
                showMessage('Errore durante l\'esportazione: ' + error.message, 'error');
            }
        }
    </script>
</body>
</html>
