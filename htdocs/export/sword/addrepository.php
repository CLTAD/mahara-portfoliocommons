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
define('TITLE', get_string('addrepository', 'export.sword'));
$wwwroot = get_config('wwwroot');
global $USER;
$elements = array();

$collectionoptions = array(0 => 'No default collection selected','');

$elements['addrepository'] = array(
        'type' => 'fieldset',
        'legend' => get_string('addrepository', 'export.sword'),
        'elements' => array(
                'addrepositorydescription' => array(
                        'value' => '<tr><td colspan="2">' . get_string('addrepositorydescription', 'export.sword') . '</td></tr>'
                ),
                'title' => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string('repositorytitle', 'export.sword'),
                        'defaultvalue' => '',
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'servicedocumenturi' => array(
                        'type' => 'text',
                        'size' => 50,
                        'title' => get_string('repositorysduri', 'export.sword'),
                        'defaultvalue' => '',
                        'rules' => array(
                                'required' => true,
                        )
                ),
                'username' => array(
                        'type' => 'text',
                        'size' => 25,
                        'title' => get_string('repositoryuser', 'export.sword'),
                        'defaultvalue' => '',
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'password' => array(
                        'type' => 'password',
                        'size' => 25,
                        'title' => get_string('repositorypassword', 'export.sword'),
                        'defaultvalue' => '',
                        'rules' => array(
                                'required' => true,
                                'maxlength' => 100,
                        )
                ),
                'onbehalfof' => array(
                        'type' => 'text',
                        'size' => 25,
                        'title' => get_string('repositoryobo', 'export.sword'),
                        'defaultvalue' => '',
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
        'collapsible' => false,
);

$form = pieform(array(
    'name' => 'addrepository',
    'autofocus' => false,
    'elements' => $elements
));

function addrepository_submit(Pieform $form, $values) {
    $wwwroot = get_config('wwwroot');
    $data = new StdClass;
    $data->title                  = $values['title'];
    $data->servicedocumenturi     = $values['servicedocumenturi'];
    $data->username               = $values['username'];
    $data->password               = $values['password'];
    $data->onbehalfof             = $values['onbehalfof'];
    $data->swordversion           = $version;
    $data->hasdefaultcollection = ($values['setdefaultcollection']=='true')?1:0;
    if ($data->hasdefaultcollection) {
        $data->defaultcollection = (isset($values['defaultcollection']))? $values['defaultcollection']:'';
    } else {
        $data->defaultcollection = '';
    }

    $success = insert_record('export_sword_repository', $data);
    if ($success) {
        $form->reply(PIEFORM_OK, array(
                'message' => get_string('settingssaved'),
                'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword',
        ));
    } else {
        $form->reply(PIEFORM_ERR, array(
                'message' => get_string('settingssavefailed'),
                'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword',
        ));
    }
}

function addrepository_validate(PieForm $form, $values) {
    $wwwroot = get_config('wwwroot');
    require_once('sword2/swordappclient.php');
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

    if ($message != 'OK') {
        $form->reply(PIEFORM_ERR, array('message' => get_string('servicedocumenterror', 'export.sword'),
                'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                );
    }

    if (!in_array($version, $versionoptions)) {
        $form->reply(PIEFORM_ERR, array('message' => get_string('swordversionerror', 'export.sword'),
                 'goto' => $wwwroot . 'admin/extensions/pluginconfig.php?plugintype=export&pluginname=sword')
                );
    }
    return true;
}

$smarty = smarty(
    array('jquery','export/sword/js/addrepository.js'),
    array('<link rel="stylesheet" type="text/css" href="' . get_config('wwwroot') . 'theme/views.css">'),
    array(),
    array('stylesheets' => array('style/views.css'))
);
$smarty->assign('PAGEHEADING', TITLE);
$smarty->assign('form', $form);
$smarty->assign('wwwroot', $wwwroot);
$smarty->display('export:sword:editresource.tpl');