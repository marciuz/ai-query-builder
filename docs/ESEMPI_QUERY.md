# 📚 Esempi di Query - LLM Query Builder

Questa è una raccolta di esempi di richieste in linguaggio naturale e le corrispondenti query SQL generate dal sistema.

## 🎯 Query Base

### Email professionisti

**Richiesta:**
```
Dammi tutte le email dei professionisti
```

**SQL Generato:**
```sql
SELECT email, nome, cognome, id_prof 
FROM professionista 
WHERE email IS NOT NULL 
ORDER BY cognome, nome 
LIMIT 1000
```

---

### Fatture specifiche

**Richiesta:**
```
Da queste fatture recuperami le email dei professionisti: AB4558, AB4614, AB4673, AB4686
```

**SQL Generato:**
```sql
SELECT 
    of.numero,
    of.sezionale,
    of.anno,
    p.email,
    p.cognome,
    p.nome,
    p.id_prof
FROM ordine_fattura of
JOIN __o o ON of.id_ordine = o.id_ordine
JOIN professionista p ON o.id_prof = p.id_prof
WHERE of.numero IN (4558, 4614, 4673, 4686)
    AND of.sezionale = 'AB'
    AND of.anno = 2025
LIMIT 1000
```

---

## 📊 Query Analytics

### Vendite per periodo

**Richiesta:**
```
Quanti ordini abbiamo avuto nel 2025?
```

**SQL Generato:**
```sql
SELECT 
    COUNT(*) as totale_ordini,
    SUM(totale) as fatturato_totale,
    MONTH(data_ordine) as mese
FROM __o
WHERE YEAR(data_ordine) = 2025
GROUP BY MONTH(data_ordine)
ORDER BY mese
LIMIT 1000
```

---

### Top prodotti

**Richiesta:**
```
Mostrami i 10 ebook più venduti questo mese
```

**SQL Generato:**
```sql
SELECT 
    e.titolo,
    COUNT(od.id_ordine) as num_vendite,
    SUM(od.prezzo) as fatturato
FROM ebook e
JOIN __ordine_dettaglio od ON e.id_ebook = od.id_prodotto
JOIN __o o ON od.id_ordine = o.id_ordine
WHERE o.data_ordine >= DATE_FORMAT(NOW(), '%Y-%m-01')
    AND od.tipo_prodotto = 'ebook'
GROUP BY e.id_ebook, e.titolo
ORDER BY num_vendite DESC
LIMIT 10
```

---

## 🔍 Query con Filtri

### Professionisti attivi

**Richiesta:**
```
Lista professionisti che hanno comprato almeno un ebook negli ultimi 3 mesi
```

**SQL Generato:**
```sql
SELECT DISTINCT
    p.email,
    p.nome,
    p.cognome,
    COUNT(DISTINCT o.id_ordine) as num_ordini,
    MAX(o.data_ordine) as ultimo_acquisto
FROM professionista p
JOIN __o o ON p.id_prof = o.id_prof
JOIN __ordine_dettaglio od ON o.id_ordine = od.id_ordine
WHERE o.data_ordine >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    AND od.tipo_prodotto = 'ebook'
GROUP BY p.id_prof, p.email, p.nome, p.cognome
ORDER BY ultimo_acquisto DESC
LIMIT 1000
```

---

### Email specifiche

**Richiesta:**
```
Professionisti con email Gmail che hanno comprato nel 2025
```

**SQL Generato:**
```sql
SELECT 
    p.email,
    p.nome,
    p.cognome,
    COUNT(o.id_ordine) as ordini_2025
FROM professionista p
JOIN __o o ON p.id_prof = o.id_prof
WHERE p.email LIKE '%@gmail.com'
    AND YEAR(o.data_ordine) = 2025
GROUP BY p.id_prof, p.email, p.nome, p.cognome
ORDER BY ordini_2025 DESC
LIMIT 1000
```

---

## 💰 Query Finanziarie

### Fatturato mensile

**Richiesta:**
```
Fatturato totale per ogni mese del 2025
```

**SQL Generato:**
```sql
SELECT 
    MONTH(of.data_fattura) as mese,
    MONTHNAME(of.data_fattura) as nome_mese,
    COUNT(*) as num_fatture,
    SUM(of.totale) as fatturato,
    SUM(of.iva) as iva_totale
FROM ordine_fattura of
WHERE YEAR(of.data_fattura) = 2025
    AND of.stato = 'emessa'
GROUP BY MONTH(of.data_fattura), MONTHNAME(of.data_fattura)
ORDER BY mese
LIMIT 12
```

---

### Fatture per sezionale

**Richiesta:**
```
Numero di fatture per ogni sezionale nell'ultimo anno
```

**SQL Generato:**
```sql
SELECT 
    sezionale,
    COUNT(*) as num_fatture,
    MIN(numero) as prima_fattura,
    MAX(numero) as ultima_fattura,
    SUM(totale) as fatturato_totale
FROM ordine_fattura
WHERE data_fattura >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
GROUP BY sezionale
ORDER BY num_fatture DESC
LIMIT 100
```

---

## 📝 Query Complesse

### Analisi comportamento utenti

**Richiesta:**
```
Analisi completa professionisti: email, numero ordini, ultimo accesso, ebook preferiti
```

**SQL Generato:**
```sql
SELECT 
    p.email,
    p.nome,
    p.cognome,
    COUNT(DISTINCT o.id_ordine) as tot_ordini,
    MAX(o.data_ordine) as ultimo_ordine,
    SUM(o.totale) as spesa_totale,
    GROUP_CONCAT(DISTINCT e.categoria SEPARATOR ', ') as categorie_interesse
FROM professionista p
LEFT JOIN __o o ON p.id_prof = o.id_prof
LEFT JOIN __ordine_dettaglio od ON o.id_ordine = od.id_ordine
LEFT JOIN ebook e ON od.id_prodotto = e.id_ebook AND od.tipo_prodotto = 'ebook'
GROUP BY p.id_prof, p.email, p.nome, p.cognome
HAVING tot_ordini > 0
ORDER BY tot_ordini DESC, spesa_totale DESC
LIMIT 1000
```

---

### Report ECM

**Richiesta:**
```
Report crediti ECM: professionista, ebook, crediti acquisiti, data completamento
```

**SQL Generato:**
```sql
SELECT 
    p.email,
    p.cognome,
    p.nome,
    e.titolo as ebook,
    e.crediti_ecm,
    ce.data_completamento,
    ce.superato,
    ce.punteggio
FROM completamento_ecm ce
JOIN professionista p ON ce.id_prof = p.id_prof
JOIN ebook e ON ce.id_ebook = e.id_ebook
WHERE ce.superato = 1
    AND ce.data_completamento >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
ORDER BY ce.data_completamento DESC
LIMIT 1000
```

---

## 🚨 Query di Monitoraggio

### Ordini sospetti

**Richiesta:**
```
Trova ordini con anomalie: totale 0, email duplicate, stessa carta di credito
```

**SQL Generato:**
```sql
SELECT 
    o.id_ordine,
    p.email,
    o.totale,
    o.data_ordine,
    o.metodo_pagamento,
    COUNT(*) OVER (PARTITION BY p.email ORDER BY o.data_ordine) as ordini_stesso_utente
FROM __o o
JOIN professionista p ON o.id_prof = p.id_prof
WHERE 
    (o.totale = 0 OR o.totale IS NULL)
    OR (
        SELECT COUNT(*) 
        FROM __o o2 
        WHERE o2.id_prof = o.id_prof 
            AND DATE(o2.data_ordine) = DATE(o.data_ordine)
    ) > 3
ORDER BY o.data_ordine DESC
LIMIT 500
```

---

## 💡 Tips per Query Migliori

### ✅ Buone Pratiche

1. **Sii specifico:**
   ```
   ❌ "Dammi i dati"
   ✅ "Dammi email, nome e cognome dei professionisti attivi"
   ```

2. **Usa periodi temporali:**
   ```
   ❌ "Vendite"
   ✅ "Vendite degli ultimi 3 mesi"
   ```

3. **Specifica l'ordinamento:**
   ```
   ❌ "Top ebook"
   ✅ "Top 10 ebook più venduti ordinati per numero vendite"
   ```

4. **Fornisci contesto:**
   ```
   ❌ "AB4558"
   ✅ "Dalla fattura AB4558 del 2025 recupera l'email del professionista"
   ```

### 🎯 Pattern Comuni

**Conteggio:**
- "Quanti ordini..."
- "Conta il numero di..."
- "Totale di..."

**Lista:**
- "Lista dei..."
- "Mostra tutti..."
- "Elenca i..."

**Top N:**
- "Top 10..."
- "I primi 5..."
- "Migliori 20..."

**Filtri temporali:**
- "Nell'ultimo mese"
- "Nel 2025"
- "Negli ultimi 3 giorni"
- "Questa settimana"

**Aggregazioni:**
- "Raggruppa per..."
- "Somma totale..."
- "Media di..."

---

## 🧪 Query di Test

### Test Base
```
Dammi 5 professionisti qualsiasi
```

### Test Join
```
Professionisti con almeno un ordine
```

### Test Date
```
Ordini di oggi
```

### Test Aggregazioni
```
Numero totale di ordini per ogni professionista
```

### Test Filtri Complessi
```
Professionisti con email Gmail che hanno speso più di 100€ nel 2025
```

---

## 📌 Note

- Tutte le query hanno LIMIT per sicurezza
- Le date usano fuso orario del server
- NULL values sono gestiti correttamente
- JOIN ottimizzati automaticamente
- Indici utilizzati quando disponibili

**Pro tip:** Se la query non è perfetta, riformula la richiesta con più dettagli!
