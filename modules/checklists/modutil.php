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

function renderChecklist($check, $level = 0)
{
    $user = auth()->getUser();
    $enabled = $check->assignedUsers ? $check->assignedUsers->pluck('id')->search($user->id) !== false : true;

    $result = '
<li id="check_'.$check->id.'" class="check-item'.(!empty($check->checked_at) ? ' done' : '').'" '.(!$enabled ? 'style="opacity: 0.4"' : '').' data-id="'.$check->id.'">
    <input type="checkbox" value="'.(!empty($check->checked_at) ? '1' : '0').'" '.(!empty($check->checked_at) ? 'checked' : '').'>

    <span class="text">'.$check->content.'</span>';

    if (empty($check->user) || $check->user->id == $user->id) {
        $result .= '
    <div class="tools">
        <i class="fa fa-trash-o check-delete"></i>
    </div>';
    }

    if ($level == 0) {
        $result .= '
    <span class="handle pull-right">
        <i class="fa fa-ellipsis-v"></i>
        <i class="fa fa-ellipsis-v"></i>
    </span>';
    }

    $result .= '
    <span class="badge pull-right" style="margin-right:5px">'.(!empty($check->checked_at) ? tr('Verificato da _NAME_ il _DATE_', [
        '_NAME_' => $check->checkUser->username,
        '_DATE_' => timestampFormat($check->checked_at),
    ]) : '').'</span>';

    $result .= '
    <ul class="todo-list">';

    $children = $check->children;
    foreach ($children as $child) {
        $result .= renderChecklist($child, $level + 1);
    }

    $result .= '
    </ul>
</li>';

    return $result;
}

function renderChecklistHtml($check, $level = 0)
{
    $user = auth()->getUser();
    $enabled = $check->assignedUsers ? $check->assignedUsers->pluck('id')->search($user->id) !== false : true;

    $width = 10+20*$level;

    $result = '
    <tr>
        <td class="text-center" style="width:30px;">
            '.(!empty($check->checked_at)?'<img src="'.ROOTDIR.'/templates/interventi/check.png" style="width:10px;">':'').'
        </td>
        <td style="padding-left:'.$width.'px;">
            <span class="text">'.$check->content.'</span>
        </td>
    </tr>';

    $children = $check->children;
    foreach ($children as $child) {
        $result .= renderChecklistHtml($child, $level + 1);
    }

    return $result;
}