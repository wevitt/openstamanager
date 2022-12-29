<?php

include_once __DIR__.'/../../core.php';

switch (post('op')) {
    case 'add_previsione':

        $tipo = $dbo->fetchOne("SELECT co_pianodeiconti2.dir FROM co_pianodeiconti2 INNER JOIN co_pianodeiconti3 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 WHERE co_pianodeiconti3.id=".prepare(post('id_conto')))['dir'];
        $sezione = post('sezione');
        $totale = ($tipo=='entrata' ? ABS(post('importo')) : -ABS(post('importo')));
        $codice = get_next_codice();

        foreach (post('ricorrenza') as $r) {
            $data = date("Y-m-t", strtotime($r.'-01'));

            $dati = [
                'codice' => $codice,
                'tipo' => $tipo,
                'sezione' => $sezione,
                'totale' => $totale,
                'data' => $data,
                'id_conto' => post('id_conto'),
                'descrizione' => post('descrizione'),
                'id_anagrafica' => post('id_anagrafica'),
            ];

            $dbo->insert('bu_previsionale', [$dati]);
        }

        flash()->info(tr('Previsione aggiunta!'));

        break;

    case 'manage_previsioni':

        $dbo->query("DELETE FROM bu_previsionale WHERE bu_previsionale.data BETWEEN ".prepare($_SESSION['period_start'])." AND ".prepare($_SESSION['period_end'])." AND id_movimento IS NULL ");
        for ($i=0; $i<count(post('descrizione')); $i++) {
            $tipo = $dbo->fetchOne("SELECT co_pianodeiconti2.dir FROM co_pianodeiconti2 INNER JOIN co_pianodeiconti3 ON co_pianodeiconti2.id=co_pianodeiconti3.idpianodeiconti2 WHERE co_pianodeiconti3.id=".prepare(post('id_conto')[$i]))['dir'];
            $sezione = post('sezione')[$i];
            $totale = ($tipo=='entrata' ? ABS(post('importo')[$i]) : -ABS(post('importo')[$i]));
            $codice = get_next_codice();

            foreach (post('ricorrenza')[$i] as $r) {
                $data = date("Y-m-t", strtotime($r.'-01'));
                $dati = [
                    'codice' => $codice,
                    'tipo' => $tipo,
                    'sezione' => $sezione,
                    'totale' => $totale,
                    'data' => $data,
                    'id_conto' => post('id_conto')[$i],
                    'descrizione' => post('descrizione')[$i],
                    'id_anagrafica' => post('id_anagrafica')[$i],
                ];
                $dbo->insert('bu_previsionale', [$dati]);
            }
        }
        flash()->info(tr('Informazioni salvate correttamente!'));

        break;

    case 'delete_previsione':
        
        $dbo->query("DELETE FROM bu_previsionale WHERE codice=".prepare(post('codice')));
        flash()->info(tr('Previsione eliminata!'));

        break;

    case 'copy_previsione':
        if(is_numeric(post('year'))){
            $year = (int)post('year');
            $date_start = $_SESSION['period_start'];
            $date_end = $_SESSION['period_end'];

            $previsioni = $dbo->fetchArray('SELECT * FROM bu_previsionale WHERE data BETWEEN '.prepare($date_start).' AND '.prepare($date_end));
    
            foreach ($previsioni as $previsione) {
                
                if(array_key_exists($previsione['codice'], $codici)){
                    $codice = $codici[$previsione['codice']];
                } else{
                    $codice = get_next_codice();
                    $codici[$previsione['codice']] = $codice;
                }

                $data = date('Y-m-d', strtotime($previsione['data'].' +'.post('year').' year'));
                $dati = [
                    'codice' => $codice,
                    'tipo' => $previsione['tipo'],
                    'sezione' => $previsione['sezione'],
                    'totale' => $previsione['totale'],
                    'data' => $data,
                    'id_conto' => $previsione['id_conto'],
                    'descrizione' => $previsione['descrizione'],
                    'id_anagrafica' => $previsione['id_anagrafica'],
                ];
    
                $dbo->insert('bu_previsionale', [$dati]);
            }
    
            flash()->info(tr('Previsioni aggiunte!'));
        } else{
            flash()->error(tr('Errore nell\'inserimento dell\'anno'));
        }

        break;

}
