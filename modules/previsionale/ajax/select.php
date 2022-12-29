<?php

include_once __DIR__.'/../../../core.php';

switch ($resource) {

    case 'conti_budget':
        $query = 'SELECT co_pianodeiconti2.* FROM co_pianodeiconti2 LEFT JOIN co_pianodeiconti3 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where| GROUP BY co_pianodeiconti2.id';

        if ($search != '') {
            $wh = 'WHERE (co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT( co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero ) LIKE ".prepare('%'.$search.'%').')';
        } else {
            $wh = 'WHERE 1=1';
        }
        $wh .= ' AND NOT co_pianodeiconti2.dir = ""';
        $query = str_replace('|where|', $wh, $query);

        $rs = $dbo->fetchArray($query);
        foreach ($rs as $r) {
            $results[] = ['text' => $r['numero'].' '.$r['descrizione'], 'children' => []];

            $subquery = 'SELECT co_pianodeiconti3.* FROM co_pianodeiconti3 INNER JOIN co_pianodeiconti2 ON co_pianodeiconti3.idpianodeiconti2=co_pianodeiconti2.id |where|';

            $where = [];
            $filter = [];
            $search_fields = [];

            foreach ($elements as $element) {
                $filter[] = 'co_pianodeiconti3.id='.prepare($element);
            }
            if (!empty($filter)) {
                $where[] = '('.implode(' OR ', $filter).')';
            }

            $where[] = 'idpianodeiconti2='.prepare($r['id']);

            if (!empty($search)) {
                $search_fields[] = '(co_pianodeiconti3.descrizione LIKE '.prepare('%'.$search.'%')." OR CONCAT(co_pianodeiconti2.numero, '.', co_pianodeiconti3.numero) LIKE ".prepare('%'.$search.'%').')';
            }
            if (!empty($search_fields)) {
                $where[] = '('.implode(' OR ', $search_fields).')';
            }

            $wh = '';
            if (count($where) != 0) {
                $wh = 'WHERE '.implode(' AND ', $where);
            }
            $subquery = str_replace('|where|', $wh, $subquery);

            $rs2 = $dbo->fetchArray($subquery);
            foreach ($rs2 as $r2) {
                $results[count($results) - 1]['children'][] = ['id' => $r2['id'], 'text' => $r['numero'].'.'.$r2['numero'].' '.$r2['descrizione']];
            }
        }

        break;
}
