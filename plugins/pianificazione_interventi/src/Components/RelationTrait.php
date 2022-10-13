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

namespace Plugins\PianificazioneInterventi\Components;

use Plugins\PianificazioneInterventi\Promemoria;

trait RelationTrait
{
    protected $disableOrder = true;

    public function getDocumentID()
    {
        return 'id_promemoria';
    }

    public function document()
    {
        return $this->belongsTo(Promemoria::class, $this->getDocumentID());
    }

    public function contratto()
    {
        return $this->document();
    }

    public function fixIvaIndetraibile()
    {
    }

    public function getQtaEvasaAttribute()
    {
        return 0;
    }

    public function setQtaEvasaAttribute($value)
    {
    }

    /**
     * Effettua i conti per il subtotale della riga.
     */
    protected function fixSubtotale()
    {
        $this->fixIva();
    }
}
