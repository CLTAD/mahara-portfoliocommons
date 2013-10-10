<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage export-sword
 * @author     Mike Kelly UAL m.f.kelly@arts.ac.uk / Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 */

define('INTERNAL', 1);
define('JSON', 1);

require(dirname(dirname(dirname(__FILE__))) . '/init.php');
safe_require('export', 'sword');

$repository = param_integer('repository', 0);
if ($repository) {
    $collections = PluginExportSword::get_repository_collections($repository);
} else {
    $data = new stdClass();
    $data->servicedocumenturi = param_variable('servicedocumenturi'); 
    $data->username = param_variable('username');
    $data->password = param_variable('password');
    $data->onbehalfof = param_variable('onbehalfof');
    $collections = PluginExportSword::get_new_repository_collections($data);
}

if (!$collections) {
    json_reply(true, get_string('collectionsretrievalerror', 'export.sword'));
} else {
    json_reply(false, (object) array('message' => false, 'data' => $collections));
}