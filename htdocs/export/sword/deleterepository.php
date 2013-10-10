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
define('TITLE', get_string('deleteexistingrepositorytitle', 'export.sword'));
$wwwroot = get_config('wwwroot');
global $USER;
$elements = array();
$versionoptions = array('2.0' => '2.0', '1.3' => '1.3');
$repoid = param_integer('id');
$action = param_alpha('action');
$repos = get_records_array('export_sword_repository');
$currentrepo = false;

if ($repos) {
    foreach ($repos as $repo) {
        if ($repo->repository == $repoid) {
            $currentrepo = $repo;
            break;
        }
    }
}

if (!isset($repoid) || !isset($action) || !$currentrepo) {
    throw new Exception('Repository not found. Please contact system adminstrator.');
}

$elements['deletingrepository'] = array(
    'type' => 'hidden',
    'value' => $repoid,
);
$elements['action'] = array(
    'type' => 'hidden',
    'value' => $action,
);
$elements['sesskey'] = array(
        'type' => 'hidden',
        'value' => $USER->get('sesskey')
);
$collectionoptions = array(0 => 'No default collection selected', '');

$elements['deleterepository'] = array(
        'type' => 'fieldset',
        'legend' => get_string('deleterepository', 'export.sword'),
        'elements' => array(
                'deleterepositorydescription' => array(
                        'value' => '<tr><td colspan="2">' . get_string('deleterepositorydescription', 'export.sword') . '</td></tr>'
                ),
                'repositorydetails' => array(
                    'type' => 'html',
                    'value' => "<strong class='fl'>$currentrepo->title</strong>
                    <ul class='fl cl'>
                    <li>Service document url: $currentrepo->servicedocumenturi</li>
                    </ul>"
                ),
                'save' => array(
                        'type'  => 'submitcancel',
                        'value' => array( $action == 'delete'? get_string('delete') : get_string('save'),
                                        get_string('cancel')
                                        ),
                        'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword',
                )
        ),
);

$form = pieform(array(
    'name' => 'deleterepository',
    'autofocus' => false,
    'elements' => $elements
));

function deleterepository_submit(Pieform $form, $values) {
    $success = delete_records('export_sword_repository', 'repository', $values['deletingrepository']);
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

function deleterepository_validate(PieForm $form, $values) {
    form_validate($values['sesskey']);

    if (empty($values['deletingrepository'])) {
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
$smarty->assign('form', $form);
$smarty->assign('wwwroot', $wwwroot);
$smarty->display('export:sword:editresource.tpl');