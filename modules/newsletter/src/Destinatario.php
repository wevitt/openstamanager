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

namespace Modules\Newsletter;

use Common\SimpleModelTrait;
use Illuminate\Database\Eloquent\Model;

class Destinatario extends Model
{
    use SimpleModelTrait;

    protected $table = 'em_newsletter_receiver';
    protected $origine = null;

    public static function build(Newsletter $newsletter, $origine)
    {
        $model = new static();
        $model->id_newsletter = $newsletter->id;

        $model->record_type = get_class($origine);
        $model->record_id = $origine->id;

        $model->save();

        return $model;
    }

    public function getEmailAttribute()
    {
        return $this->getOrigine()->email;
    }

    // Relazione Eloquent

    public function getOrigine()
    {
        if (isset($this->origine)) {
            return $this->origine;
        }

        $this->origine = ($this->record_type)::find($this->record_id);

        return $this->origine;
    }

    public function newsletter()
    {
        return $this->belongsTo(Newsletter::class, 'id_newsletter');
    }
}
