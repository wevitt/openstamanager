<?php
/*
 * OpenSTAManager: il software gestionale open source per l'assistenza tecnica e la fatturazione
 * Copyright (C) DevCode s.r.l.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Centralino3CX;

use Carbon\Carbon;
use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\Anagrafiche\Anagrafica;
use Modules\Anagrafiche\Referente;
use Modules\Anagrafiche\Sede;
use Modules\Interventi\Intervento;

class Chiamata extends Model
{
    use SimpleModelTrait;

    protected $table = '3cx_chiamate';
    protected $collegamento_numero = null;

    protected $dates = [
        'inizio',
        'fine',
    ];

    /**
     * @param string $numero
     * @param bool   $in_entrata
     *
     * @return Chiamata
     */
    public static function build($numero, $in_entrata)
    {
        $model = new self();

        // Salvataggio informazioni di base
        $model->numero = $numero;
        $model->in_entrata = $in_entrata;
        $model->inizio = new Carbon();
        $model->durata = 0;
        $model->durata_visibile = '00:00';

        // Collegamento automatico all'angrafica sulla base del numero
        $collegamento = $model->trovaNumero();
        $model->associaCollegamento($collegamento);

        $oggetto = $in_entrata ? 'Chiamata in entrata di _NOME_ (_NUMERO_)' : 'Chiamata in uscita per _NOME_ (_NUMERO_)';
        $model->oggetto = replace($oggetto, [
            '_NOME_' => $model->anagrafica ? $model->anagrafica->ragione_sociale : 'Contatto sconosciuto',
            '_NUMERO_' => $numero,
        ]);

        $model->save();

        return $model;
    }

    public function associaCollegamento($collegamento)
    {
        if ($collegamento instanceof Anagrafica) {
            $this->anagrafica()->associate($collegamento);
        } elseif (!empty($collegamento)) {
            $this->anagrafica()->associate($collegamento->anagrafica);

            if ($collegamento instanceof Sede) {
                $this->sede()->associate($collegamento);
            } else {
                $this->referente()->associate($collegamento);
            }
        }
    }

    /**
     * @return Anagrafica|Referente|Sede|null
     */
    public function trovaNumero()
    {
        if (isset($this->collegamento_numero)) {
            return $this->collegamento_numero;
        }

        $numero_ricerca = '%'.$this->numero;

        // Ricerca tra le anagrafiche
        $anagrafica = Anagrafica::selectRaw('*, '.self::getNumberQuery('telefono').' AS telefono, '.self::getNumberQuery('cellulare').' AS cellulare')
            ->having('telefono', 'like', $numero_ricerca)
            ->orHaving('cellulare', 'like', $numero_ricerca)
            ->first();
        $this->collegamento_numero = $anagrafica;

        // Ricerca tra le sedi
        if (empty($this->collegamento_numero)) {
            $sede = Sede::selectRaw('*, '.self::getNumberQuery('telefono').' AS telefono, '.self::getNumberQuery('cellulare').' AS cellulare')
                ->having('telefono', 'like', $numero_ricerca)
                ->orHaving('cellulare', 'like', $numero_ricerca)
                ->first();
            $this->collegamento_numero = $sede;
        }

        // Ricerca tra referenti
        if (empty($this->collegamento_numero)) {
            $referente = Referente::selectRaw('*, '.self::getNumberQuery('telefono').' AS telefono')
                ->having('telefono', 'like', $numero_ricerca)
                ->first();
            $this->collegamento_numero = $referente;
        }

        return $this->collegamento_numero;
    }

    public function setInternoAttribute($interno)
    {
        // Ricerca operatore attivo all'interno indicato
        $operatore = Operatore::where('interno', '=', $interno)
            ->where('created_at', '<=', $this->created_at)
            ->where(function ($query) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '>=', $this->created_at);
            })->first();

        $this->attributes['interno'] = $interno;
        $this->attributes['id_tecnico'] = $operatore->anagrafica->id;
    }

    /**
     * Imposta il filtro per chiamate senza risposta.
     *
     * @return Chiamata|Model|\Illuminate\Database\Query\Builder|object|null
     */
    public static function senzaRisposta()
    {
        return self::whereNull('id_tecnico')
            ->where('is_gestito', '=', 0)
            ->where('created_at', '<=', Carbon::now()->subDays(1));
    }

    public function anagrafica()
    {
        return $this->belongsTo(Anagrafica::class, 'id_anagrafica')->withTrashed();
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class, 'id_sede');
    }

    public function referente()
    {
        return $this->belongsTo(Referente::class, 'id_referente');
    }

    public function tecnico()
    {
        return $this->belongsTo(Anagrafica::class, 'id_tecnico')->withTrashed();
    }

    public function intervento()
    {
        return $this->belongsTo(Intervento::class, 'id_intervento');
    }

    /**
     * Filtra il numero di telefono, rimuovendo caratteri non numerici e prefisso internazionale.
     *
     * @param string $numero
     *
     * @return string
     */
    public static function filtraNumero($numero)
    {
        $numero = preg_replace(
            '/^(\+|00)(?:998|996|995|994|993|992|977|976|975|974|973|972|971|970|968|967|966|965|964|963|962|961|960|886|880|856|855|853|852|850|692|691|690|689|688|687|686|685|683|682|681|680|679|678|677|676|675|674|673|672|670|599|598|597|595|593|592|591|590|509|508|507|506|505|504|503|502|501|500|423|421|420|389|387|386|385|383|382|381|380|379|378|377|376|375|374|373|372|371|370|359|358|357|356|355|354|353|352|351|350|299|298|297|291|290|269|268|267|266|265|264|263|262|261|260|258|257|256|255|254|253|252|251|250|249|248|246|245|244|243|242|241|240|239|238|237|236|235|234|233|232|231|230|229|228|227|226|225|224|223|222|221|220|218|216|213|212|211|98|95|94|93|92|91|90|86|84|82|81|66|65|64|63|62|61|60|58|57|56|55|54|53|52|51|49|48|47|46|45|44\D?1624|44\D?1534|44\D?1481|44|43|41|40|39|36|34|33|32|31|30|27|20|7|1\D?939|1\D?876|1\D?869|1\D?868|1\D?849|1\D?829|1\D?809|1\D?787|1\D?784|1\D?767|1\D?758|1\D?721|1\D?684|1\D?671|1\D?670|1\D?664|1\D?649|1\D?473|1\D?441|1\D?345|1\D?340|1\D?284|1\D?268|1\D?264|1\D?246|1\D?242|1)\D?/',
            '',
            $numero
        );
        $numero = preg_replace('/[^0-9]/', '', $numero);

        return $numero;
    }

    /**
     * Genera la query SQL responsabile per rimuovere caratteri non numerici da un campo di numero telefonico.
     *
     * @param $campo
     *
     * @return string
     */
    protected static function getNumberQuery($campo)
    {
        return 'REPLACE(REPLACE(REPLACE(REPLACE('.$campo.", '-', ''), ' ', ''),'.', ''),',', '')";
    }
}
