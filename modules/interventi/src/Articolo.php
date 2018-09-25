<?php

namespace Modules\Interventi;

use Modules\Articoli\Articolo as Original;
use Base\Article;

class Articolo extends Article
{
    protected $table = 'mg_articoli_interventi';

    public static function new(Intervento $intervento, Original $articolo, $id_automezzo = null)
    {
        $model = parent::new($articolo);

        $model->intervento()->associate($intervento);

        $model->prezzo_acquisto = $articolo->prezzo_acquisto;
        $model->prezzo_vendita = $articolo->prezzo_vendita;

        $model->save();

        return $model;
    }

    public function movimenta($qta)
    {
        $articolo = $this->articolo()->first();

        // Movimento l'articolo
        if (!empty($this->idautomezzo)) {
            $rs = $dbo->fetchArray("SELECT CONCAT_WS(' - ', nome, targa) AS nome FROM dt_automezzi WHERE id=".prepare($this->idautomezzo));
            $nome = $rs[0]['nome'];

            $descrizione = ($qta < 0) ? tr("Carico sull'automezzo _NAME_", [
                '_NAME_' => $nome,
            ]) : tr("Scarico dall'automezzo _NAME_", [
                '_NAME_' => $nome,
            ]);

            $dbo->query('UPDATE mg_articoli_automezzi SET qta = qta + '.$qta.' WHERE idarticolo = '.prepare($articolo->id).' AND idautomezzo = '.prepare($this->idautomezzo));
            $data = date('Y-m-d');

            $articolo->registra(-$qta, $descrizione, $data, false, [
                'idautomezzo' => $this->idautomezzo,
                'idintervento' => $this->idintervento,
            ]);
        } else {
            $intervento = $this->intervento()->first();

            $numero = $intervento->codice;
            $data = database()->fetchOne('SELECT MAX(orario_fine) AS data FROM in_interventi_tecnici WHERE idintervento = :id_intervento', [
                ':id_intervento' => $intervento->id,
            ])['data'];

            $data = $data ?: $intervento->data_richiesta;

            $descrizione = ($qta < 0) ? tr('Ripristino articolo da intervento _NUM_', [
                '_NUM_' => $numero,
            ]) : tr('Scarico magazzino per intervento _NUM_', [
                '_NUM_' => $numero,
            ]);

            $articolo->movimenta(-$qta, $descrizione, $data, false, [
                'idintervento' => $intervento->id,
            ]);
        }
    }

    protected function serialID()
    {
        return 'intervento';
    }

    public function getSubtotaleAttribute()
    {
        return $this->prezzo_vendita * $this->qta;
    }

    public function intervento()
    {
        return $this->belongsTo(Intervento::class, 'idintervento');
    }
}
