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
define('ADMIN', 1);
require(dirname(dirname(dirname(__FILE__))) . '/init.php');
require_once('pieforms/pieform.php');
define('TITLE', get_string('deleteexistinglicence', 'export.sword'));
$wwwroot = get_config('wwwroot');
global $USER;
$elements = array();
$customlicences = get_records_array('export_sword_customlicence');
$licenceid = param_integer('id');
$action = param_alpha('action');
$currentlicence = false;

if ($customlicences) {
    foreach ($customlicences as $licence) {
        if ($licence->licence == $licenceid) {
            $currentlicence = $licence;
            break;
        }
    }
}

if (!isset($licenceid) || !isset($action) || !$currentlicence) {
    throw new Exception('Licence not found. Please contact system adminstrator.');
}

$elements['deletingcustomlicence'] = array(
    'type' => 'hidden',
    'value' => $licenceid,
);
$elements['action'] = array(
    'type' => 'hidden',
    'value' => $action,
);
$elements['sesskey'] = array(
        'type' => 'hidden',
        'value' => $USER->get('sesskey')
);
$elements['deletecustomlicence'] = array(
        'type' => 'fieldset',
        'legend' => get_string('deletecustomlicence', 'export.sword'),
        'elements' => array(
                'deletecustomlicencedescription' => array(
                        'value' => '<tr><td colspan="2">' . get_string('deletecustomlicencedescription', 'export.sword') . '</td></tr>'
                ),
                'licencedetails' => array(
                    'type' => 'html',
                    'value' => "<strong class='fl'>$currentlicence->title</strong>
                    <ul class='fl cl'>
                    <li>Url: $currentlicence->uri</li>
                    </ul>"
                ),
                'save' => array(
                        'type'  => 'submitcancel',
                        'value' => array(get_string('delete'),
                                        get_string('cancel')
                                        ),
                        'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword',
                )
        ),
);

$form = pieform(array(
    'name' => 'deletecustomlicence',
    'autofocus' => false,
    'elements' => $elements
));

function deletecustomlicence_submit(Pieform $form, $values) {
    $success = delete_records('export_sword_customlicence', 'licence', $values['deletingcustomlicence']);
    if ($success) {
        $form->reply(PIEFORM_OK, array('message' => get_string('settingssaved'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
    } else {
        $form->reply(PIEFORM_ERR, array('message' => get_string('sworddeleteerror', 'export.sword'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
    }
}

function deletecustomlicence_validate(PieForm $form, $values) {
    form_validate($values['sesskey']);

    if (empty($values['deletingcustomlicence'])) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('formerror', 'export.sword'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
    }
    if (!isset($values['action']) || $values['action'] != 'delete') {
        $form->reply(PIEFORM_ERR, array('message' => get_string('formerror', 'export.sword'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
    }
    return true;
}

$smarty = smarty(
    array(),
    array('<link rel="stylesheet" type="text/css" href="' . get_config('wwwroot') . 'theme/views.css">'),
    array(),
    array('stylesheets' => array('style/views.css'))
);
$smarty->assign('PAGEHEADING', TITLE);
$smarty->assign('wwwroot', $wwwroot);
$smarty->assign('form', $form);
$smarty->display('export:sword:editresource.tpl');