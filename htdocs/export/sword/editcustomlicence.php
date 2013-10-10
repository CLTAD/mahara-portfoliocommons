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
define('TITLE', get_string('editexistinglicence', 'export.sword'));
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

$elements['editingcustomlicence'] = array(
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
$elements['editcustomlicence'] = array(
        'type' => 'fieldset',
        'legend' => get_string('editcustomlicence', 'export.sword'),
        'elements' => array(
                'addcustomlicencedescription' => array(
                        'value' => '<tr><td colspan="2">' . get_string('editcustomlicencedescription', 'export.sword') . '</td></tr>'
                ),
                'title' => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string('customlicencetitle', 'export.sword'),
                        'defaultvalue' => $currentlicence->title,
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'customlicenceuri' => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string('customlicenceuri', 'export.sword'),
                        'defaultvalue' => $currentlicence->uri,
                        'rules' => array(
                                'required' => true,
                        )
                ),
                'setasdefaultlicence' => array(
                        'type' => 'checkbox',
                        'title' => get_string('setdefaultlicence', 'export.sword'),
                        'description' => get_string('setdefaultlicencedescription', 'export.sword'),
                        'defaultvalue' => false,
                ),
                'save' => array(
                        'type'  => 'submitcancel',
                        'value' => array(get_string('save'),
                                        get_string('cancel')
                                        ),
                        'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword',
                )
        ),
);

$form = pieform(array(
    'name' => 'editcustomlicence',
    'autofocus' => false,
    'elements' => $elements
));

function editcustomlicence_submit(Pieform $form, $values) {
        $customlicence = new stdClass();
        $customlicence->licence = $values['editingcustomlicence'];
        $customlicence->title = $values['title'];
        $customlicence->uri = $values['customlicenceuri'];

        if ($values['setasdefaultlicence'] == 'true') {
            set_config('swordexportdefaultlicence', $values['editingcustomlicence']);
        }

        $success = update_record('export_sword_customlicence', $customlicence, 'licence');
        if ($success) {
            $form->reply(PIEFORM_OK, array('message' => get_string('settingssaved'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
        } else {
            $form->reply(PIEFORM_ERR, array('message' => get_string('swordupdateerror', 'export.sword'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
        }
}

function editcustomlicence_validate(PieForm $form, $values) {
    form_validate($values['sesskey']);

    if (empty($values['editingcustomlicence'])) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('formerror', 'export.sword'),
                'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                );
    }
    if (!isset($values['action']) || $values['action'] != 'edit') {
        $form->reply(PIEFORM_ERR, array('message' => get_string('formerror', 'export.sword'),
                 'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                );
    }

    $title = trim($values['title']);
    if (empty($title) ) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('namedfieldempty','title'),'error') );
    }
    $customlicenceuri = trim($values['customlicenceuri']);
    if (empty($customlicenceuri) ) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('namedfieldempty','customlicenceuri'),'error') );
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