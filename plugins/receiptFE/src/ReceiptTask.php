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

namespace Plugins\ReceiptFE;

use Tasks\Manager;

/**
 * Task dedicata all'importazione di tutte le ricevute individuate dal sistema automatico di gestione Fatture Elettroniche.
 *
 * @see MissingReceiptTask Gestione ricevute non individuate per malfunzionamenti.
 */
class ReceiptTask extends Manager
{
    public function execute()
    {
        if (!Interaction::isEnabled()) {
            return;
        }

        $list = Interaction::getReceiptList();

        // Esecuzione dell'importazione
        foreach ($list as $element) {
            $name = $element['name'];

            Ricevuta::process($name);
        }
    }
}
