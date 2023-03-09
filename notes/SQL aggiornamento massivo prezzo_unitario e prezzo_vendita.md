# SQL - aggiornamento massivo prezzo_unitario e prezzo_vendita

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 1)
SET prezzo_vendita = ROUND((prezzo_vendita+(prezzo_vendita/100*6)),3),
prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 2)
SET prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 3)
SET prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 4)
SET prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 1)
SET prezzo_vendita = ROUND((prezzo_vendita+(prezzo_vendita/100*7)),3),
prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*7)),3)
WHERE id_sottocategoria = 178;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 2)
SET prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*7)),3)
WHERE id_sottocategoria = 178;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 3)
SET prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*7)),3)
WHERE id_sottocategoria = 178;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 4)
SET prezzo_unitario = ROUND((prezzo_unitario +(prezzo_unitario /100*7)),3)
WHERE id_sottocategoria = 178;




UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 1)
SET prezzo_vendita_ivato = ROUND((prezzo_vendita_ivato+(prezzo_vendita_ivato/100*6)),3),
prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 2)
SET prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 3)
SET prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 4)
SET prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*6)),3)
WHERE id_sottocategoria = 285;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 1)
SET prezzo_vendita_ivato = ROUND((prezzo_vendita_ivato+(prezzo_vendita_ivato/100*7)),3),
prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*7)),3)
WHERE id_sottocategoria = 178;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 2)
SET prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*7)),3)
WHERE id_sottocategoria = 178;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 3)
SET prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*7)),3)
WHERE id_sottocategoria = 178;

UPDATE mg_articoli
INNER JOIN mg_categorie ON mg_articoli.id_sottocategoria = mg_categorie.id
LEFT JOIN mg_listini_articoli ON (mg_articoli.id = mg_listini_articoli.id_articolo AND mg_listini_articoli.id_listino = 4)
SET prezzo_unitario_ivato = ROUND((prezzo_unitario_ivato +(prezzo_unitario_ivato /100*7)),3)
WHERE id_sottocategoria = 178;
