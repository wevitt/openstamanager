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

namespace Traits;

use Models\Module;
use Models\Plugin;

trait RecordTrait
{
    abstract public function getModuleAttribute();

    public function getModule()
    {
        return !empty($this->module) ? Module::pool($this->module) : null;
    }

    public function getPlugin()
    {
        return !empty($this->plugin) ? Plugin::pool($this->plugin) : null;
    }

    /**
     * @param string $name
     */
    public function customField($name)
    {
        $field = database()->table('zz_fields')
            ->leftJoin('zz_field_record', 'zz_fields.id', '=', 'zz_field_record.id_field')
                ->where('zz_fields.name', '=', $name)
                ->where('zz_fields.id_module', '=', $this->getModule()->id)
                ->where('zz_field_record.id_record', '=', $this->id)
            ->first();

        return $field->value;
    }

    public function uploads()
    {
        $module = $this->getModule();
        $plugin = $this->getPlugin();

        if (!empty($module)) {
            return $module->uploads($this->id);
        }

        if (!empty($plugin)) {
            return $plugin->uploads($this->id);
        }

        return collect();
    }
}
