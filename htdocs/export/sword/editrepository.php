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
define('TITLE', get_string('editexistingrepositorytitle', 'export.sword'));
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

$elements['editingrepository'] = array(
    'type' => 'hidden',
    'value' => $repoid,
    'sesskey' =>  $USER->get('sesskey'),
);
$elements['action'] = array(
    'type' => 'hidden',
    'value' => $action,
    'sesskey' =>  $USER->get('sesskey'),
);

$collectionoptions = array(0 => 'No default collection selected', '');

$elements['editrepository'] = array(
        'type' => 'fieldset',
        'legend' => get_string('editrepository', 'export.sword'),
        'elements' => array(
                'addrepositorydescription' => array(
                        'value' => '<tr><td colspan="2">' . get_string('editrepositorydescription', 'export.sword') . '</td></tr>'
                ),
                'title' => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string('repositorytitle', 'export.sword'),
                        'defaultvalue' => $repo->title,
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'servicedocumenturi' => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string('repositorysduri', 'export.sword'),
                        'defaultvalue' => $repo->servicedocumenturi,
                        'rules' => array(
                                'required' => true,
                        )
                ),
                'username' => array(
                        'type' => 'text',
                        'size' => 25,
                        'title' => get_string('repositoryuser', 'export.sword'),
                        'defaultvalue' => $repo->username,
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'password' => array(
                        'type' => 'password',
                        'size' => 25,
                        'title' => get_string('editrepositorypassword', 'export.sword'),
                        'defaultvalue' => $repo->password,
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'onbehalfof' => array(
                        'type' => 'text',
                        'size' => 25,
                        'title' => get_string('repositoryobo', 'export.sword'),
                        'defaultvalue' => $repo->onbehalfof,
                        'rules' => array(
                                'maxlength' => 100,
                        )
                ),
                'setdefaultcollection' => array(
                        'type' => 'checkbox',
                        'size' => 25,
                        'title' => get_string('setdefaultcollection', 'export.sword'),
                        'defaultvalue' => false,
                ),
                'defaultcollection' => array(
                        'title' => get_string('defaultcollection', 'export.sword'),
                        'type' => 'select',
                        'options' => $collectionoptions,
                        'defaultvalue' => 0,
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

$elements['editrepository']['hasdefaultrepository'] = array(
        'type' => 'hidden',
        'value' => 0,
        'sesskey' =>  $USER->get('sesskey'),
);

$form = pieform(array(
    'name' => 'editrepository',
    'autofocus' => false,
    'elements' => $elements
));

function editrepository_submit(Pieform $form, $values) {

    $action = $values['action'];

    if ($action == 'edit') {

        require_once('sword1/swordappclient.php');
        $versionoptions = array('1.3', '2.0');
        $sac = new SWORDAPPClient();
        $sd = $sac->servicedocument($values['servicedocumenturi'], $values['username'], $values['password'], '');
        // The HTTP status code returned
        $status = $sd->sac_status;
        // The human readable status code
        $message = $sd->sac_statusmessage;
        // The version of the SWORD server
        $versionXML = $sd->sac_version;
        $version = (string)$versionXML;

        $repo = new stdClass();
        $repo->repository = $values['editingrepository'];
        $repo->title = $values['title'];
        $repo->servicedocumenturi = $values['servicedocumenturi'];
        $repo->username = $values['username'];
        if (!empty($values['password'])) {
            $repo->password = $values['password'];
        }
        $repo->onbehalfof = $values['onbehalfof'];
        $repo->swordversion = $version;
        $repo->hasdefaultcollection = ($values['setdefaultcollection']=='true')?1:0;
        if ($repo->hasdefaultcollection) {
            $repo->defaultcollection = (isset($values['defaultcollection']))? $values['defaultcollection']:'';
        } else {
            $repo->defaultcollection = '';
        }

        $success = update_record('export_sword_repository', $repo, 'repository');
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
    else if ($action == 'delete') {
        $success = delete_records('export_sword_repository', 'repository', $values['editingrepository']);
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
}

function editrepository_validate(PieForm $form, $values) {

    $success = true;
    if (!isset($values['editingrepository']) || $values['editingrepository'] == 0) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('formerror', 'export.sword'),
                'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                );
    }
    if (!isset($values['action'])) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('formerror', 'export.sword'),
                'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                );
    }
    else {
        $action = $values['action'];
    }

    if ($action == 'edit') {
        require_once('sword1/swordappclient.php');
        $versionoptions = array('1.3', '2.0');
        $sac = new SWORDAPPClient();
        $password = trim($values['password']);
        if (empty($password)) {
            $password = get_field('export_sword_repository', 'password', 'repository', $values['editingrepository']);
        }
        $sd = $sac->servicedocument($values['servicedocumenturi'], $values['username'], $password, '');
        // The HTTP status code returned
        $status = $sd->sac_status;
        // The human readable status code
        $message = $sd->sac_statusmessage;
        // The version of the SWORD server
        $versionXML = $sd->sac_version;
        $version = (string)$versionXML;

        if ($message != 'OK') {
            $form->reply(PIEFORM_ERR, array('message' => get_string('servicedocumenterror', 'export.sword'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
        }

        if (!in_array($version, $versionoptions)) {
            $form->reply(PIEFORM_ERR, array('message' => get_string('swordversionerror', 'export.sword'),
                    'goto' => get_config('wwwroot') . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                    );
        }
    }
    return true;
}

$smarty = smarty(
    array('jquery','export/sword/js/editrepository.js'),
    array('<link rel="stylesheet" type="text/css" href="' . get_config('wwwroot') . 'theme/views.css">'),
    array(),
    array('stylesheets' => array('style/views.css'))
);
$smarty->assign('PAGEHEADING', TITLE);
$smarty->assign('form', $form);
$smarty->assign('wwwroot', $wwwroot);
$smarty->display('export:sword:editresource.tpl');