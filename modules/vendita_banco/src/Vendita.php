<?php

namespace Modules\VenditaBanco;

use Carbon\Carbon;
use Common\Document;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Pagamenti\Pagamento;
use Traits\RecordTrait;
use Traits\ReferenceTrait;
use Util\Generator;

class Vendita extends Document
{
    use SoftDeletes;
    use ReferenceTrait;
    use RecordTrait;

    protected $table = 'vb_venditabanco';

    /**
     * Crea un nuovo documento di vendita al banco.
     */
    public static function build()
    {
        $model = new static();

        // Stato predefinito: Aperto
        $stato = Stato::where('descrizione', 'Aperto')->first();
        $model->stato()->associate($stato);

        $model->data = Carbon::now();
        $model->numero = isset($numero) ? $numero : self::getNextNumero($model->data);
        $model->numero_esterno = self::getNextNumeroEsterno($model->data, post('id_segment'));
        $model->id_segment = post('id_segment');

        $model->idpagamento = setting('Pagamento predefinito');

        return $model;
    }

    // Attributi Eloquent

    /**
     * Restituisce il nome del modulo a cui l'oggetto è collegato.
     *
     * @return string
     */
    public function getModuleAttribute()
    {
        return 'Vendita al banco';
    }

    public function getDirezioneAttribute()
    {
        return 'entrata';
    }

    public function pagamento()
    {
        return $this->belongsTo(Pagamento::class, 'idpagamento');
    }

    public function stato()
    {
        return $this->belongsTo(Stato::class, 'idstato');
    }

    public function isPagato()
    {
        return $this->stato->descrizione == 'Pagato';
    }

    public function articoli()
    {
        return $this->hasMany(Components\Articolo::class, 'idvendita');
    }

    public function righe()
    {
        return $this->hasMany(Components\Riga::class, 'idvendita');
    }

    public function sconti()
    {
        return $this->hasMany(Components\Sconto::class, 'idvendita');
    }

    public function descrizioni()
    {
        return $this->hasMany(Components\Descrizione::class, 'idvendita');
    }

    /**
     * Salva la fattura, impostando i campi dipendenti dai singoli parametri.
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        // Informazioni sul cambio dei valori
        $stato_precedente = Stato::find($this->original['idstato']);

        // Generazione numero fattura se non presente
        if ($stato_precedente->descrizione != 'Pagato' && $this->stato['descrizione'] == 'Pagato') {
            $this->registraMovimenti();
        } elseif ($stato_precedente->descrizione == 'Pagato' && $this->stato['descrizione'] != 'Pagato') {
            $this->rimuoviMovimenti();
        }

        return parent::save($options);
    }

    public function delete()
    {
        $this->rimuoviMovimenti();

        return parent::delete();
    }

    public function registraMovimenti()
    {
        $dbo = database();
        $descrizione = $this->getReference();
        $idmastrino = get_new_idmastrino();

        // Totali
        $imponibile = $this->totale_imponibile;
        $iva = $this->iva;
        $totale = $this->totale;

        // Aggiungo il movimento con il totale sul conto "Riepilogativo clienti"
        $conto_controparte = $dbo->fetchOne("SELECT id FROM co_pianodeiconti3 WHERE descrizione='Riepilogativo clienti'");
        // Movimento per il totale (non primanota)
        $this->registraMovimento($idmastrino, $this->data, $descrizione, $conto_controparte['id'], $totale, 0);

        // Movimento per il totale (primanota)
        $this->registraMovimento($idmastrino, $this->data, $descrizione, $conto_controparte['id'], -$totale, 1);

        // Aggiungo il movimento con l'imponibile nel conto definito dal metodo di pagamento (primanota)
        $conto_pagamento = $dbo->fetchOne('SELECT idconto_vendite as id FROM co_pagamenti INNER JOIN vb_venditabanco ON co_pagamenti.id = vb_venditabanco.idpagamento WHERE vb_venditabanco.id='.prepare($this->id));
        $this->registraMovimento($idmastrino, $this->data, $descrizione, $conto_pagamento['id'], $totale, 1);

        // Registrazione del movimento come "Ricavi vendita al banco"
        $conto_ricavi_vendita = $dbo->fetchOne("SELECT id FROM co_pianodeiconti3 WHERE descrizione='Ricavi vendita al banco'");
        $this->registraMovimento($idmastrino, $this->data, $descrizione, $conto_ricavi_vendita['id'], -$imponibile, 0);

        // Aggiungo il movimento con il totale dell'IVA
        if ($iva != 0) {
            $iva_vendite = $dbo->fetchOne("SELECT id, descrizione FROM co_pianodeiconti3 WHERE descrizione='Iva su vendite'");

            $this->registraMovimento($idmastrino, $this->data, $descrizione, $iva_vendite['id'], -$iva, 0);
        }
    }

    public function rimuoviMovimenti()
    {
        $database = database();

        $database->query('DELETE FROM co_movimenti WHERE id IN (SELECT idmovimento FROM vb_venditabanco_movimenti WHERE idvendita = '.prepare($this->id).')');

        $database->query('DELETE FROM vb_venditabanco_movimenti WHERE idvendita = '.prepare($this->id).' AND idmovimento NOT IN (SELECT id FROM co_movimenti)');
    }

    // Metodi statici

    public function registraMovimento($id_mastrino, $data, $descrizione, $id_conto_controparte, $totale, $is_primanota)
    {
        $database = database();

        $database->insert('co_movimenti', [
            'idmastrino' => $id_mastrino,
            'data' => $data,
            'data' => $this->data,
            'iddocumento' => 0,
            'id_anagrafica' => null,
            'descrizione' => $descrizione,
            'idconto' => $id_conto_controparte,
            'totale' => $totale,
            'primanota' => $is_primanota,
        ]);

        $id_movimento = $database->lastInsertedId();
        $database->insert('vb_venditabanco_movimenti', [
            'idvendita' => $this->id,
            'idmovimento' => $id_movimento,
        ]);
    }

    /**
     * Calcola il nuovo numero di preventivo.
     *
     * @return string
     */
    public static function getNextNumero($data)
    {
        $maschera = '#';

        $ultimo = Generator::getPreviousFrom($maschera, 'vb_venditabanco', 'numero', [
            'YEAR(data) = '.prepare(date('Y', strtotime($data))),
        ]);

        $numero = Generator::generate($maschera, $ultimo);

        return $numero;
    }

    /**
     * Calcola il nuovo numero esterno di vendita banco.
     *
     * @param string $data Data di riferimento
     * @param int $id_segment ID del segmento
     *
     * @return string
     */
    public static function getNextNumeroEsterno($data, $id_segment)
    {
        $maschera = Generator::getMaschera($id_segment);

        $ultimo = Generator::getPreviousFrom($maschera, 'vb_venditabanco', 'numero_esterno', [
            'YEAR(data) = ' . prepare(date('Y', strtotime($data))),
            'id_segment = ' . prepare($id_segment),
        ]);
        $numero = Generator::generate($maschera, $ultimo, 1, Generator::dateToPattern($data));

        return $numero;
    }

    /**
     * Metodo temporaneo
     * Permette di aggiornare i numeri esterni delle vendite al banco, in caso di movimenti generati prima
     * dell'introduzione del campo numero_esterno.
     *
     * @return void
     */
    public static function fixMissingNumeroEsterno()
    {
        $database = database();
        $vendite = $database->fetchArray('SELECT id, numero, idmagazzino, data, numero_esterno, data FROM vb_venditabanco');

        foreach ($vendite as $vendita) {
            $id_segment = $vendita['idmagazzino'] == '1' ? 38 : 39;
            $maschera = Generator::getMaschera($id_segment);

            $numero = str_pad($vendita['numero'], 4, '0', STR_PAD_LEFT);
            $numero = str_replace('####', $numero, $maschera);

            $database->update('vb_venditabanco', [
                'numero_esterno' => $numero,
                'id_segment' => $id_segment,
            ], [
                'id' => $vendita['id'],
            ]);
        }
    }

    // Opzioni di riferimento

    public function getReferenceName()
    {
        return 'Vendita al banco';
    }

    public function getReferenceNumber()
    {
        return $this->numero;
    }

    public function getReferenceDate()
    {
        return $this->data;
    }

    public function getReferenceRagioneSociale()
    {
        return $this->anagrafica->ragione_sociale;
    }
}
