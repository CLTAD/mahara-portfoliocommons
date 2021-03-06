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
 * @subpackage artefact-resume
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2009 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

define('INTERNAL', true);
define('MENUITEM', 'content/resume');
define('SECTION_PLUGINTYPE', 'artefact');
define('SECTION_PLUGINNAME', 'resume');
define('SECTION_PAGE', 'index');
define('RESUME_SUBPAGE', 'index');

require_once(dirname(dirname(dirname(__FILE__))) . '/init.php');
define('TITLE', get_string('resume', 'artefact.resume'));
require_once('pieforms/pieform.php');
safe_require('artefact', 'resume');

$defaults = array(
    'coverletter' => array(
        'default' => '',
        'fshelp' => true,
    ),
);
$coverletterform = pieform(simple_resumefield_form($defaults, 'artefact/resume/index.php'));

// load up all the artefacts this user already has....
$personalinformation = null;
try {
    $personalinformation = artefact_instance_from_type('personalinformation');
}
catch (Exception $e) { }

$personalinformationform = pieform(array(
    'name'        => 'personalinformation',
    'plugintype'  => 'artefact',
    'pluginname'  => 'resume',
    'jsform'      => true,
    'method'      => 'post',
    'elements'    => array(
        'personalinfomation' => array(
            'type' => 'fieldset',
            'legend' => get_string('personalinformation', 'artefact.resume'),
            'elements' => array(
                'dateofbirth' => array(
                    'type'       => 'calendar',
                    'caloptions' => array(
                        'showsTime'      => false,
                        'ifFormat'       => get_string('strfdateofbirth', 'langconfig')
                        ),
                    'defaultvalue' => (
                            (!empty($personalinformation) && null !== $personalinformation->get_composite('dateofbirth'))
                            ? $personalinformation->get_composite('dateofbirth')+3600
                            : null
                    ),
                    'title' => get_string('dateofbirth', 'artefact.resume'),
                    'description' => get_string('dateofbirthformatguide'),
                ),
                'placeofbirth' => array(
                    'type' => 'text',
                    'defaultvalue' => ((!empty($personalinformation)) 
                        ? $personalinformation->get_composite('placeofbirth') : null),
                    'title' => get_string('placeofbirth', 'artefact.resume'),
                    'size' => 30,
                ),  
                'citizenship' => array(
                    'type' => 'text',
                    'defaultvalue' => ((!empty($personalinformation))
                        ? $personalinformation->get_composite('citizenship') : null),
                    'title' => get_string('citizenship', 'artefact.resume'),
                    'size' => 30,
                ),
                'visastatus' => array(
                    'type' => 'text', 
                    'defaultvalue' => ((!empty($personalinformation))
                        ? $personalinformation->get_composite('visastatus') : null),
                    'title' => get_string('visastatus', 'artefact.resume'),
                    'help'  => true,
                    'size' => 30,
                ),
                'gender' => array(
                    'type' => 'radio', 
                    'defaultvalue' => ((!empty($personalinformation))
                        ? $personalinformation->get_composite('gender') : null),
                    'options' => array(
                        'female' => get_string('female', 'artefact.resume'),
                        'male'   => get_string('male', 'artefact.resume'),
                    ),
                    'title' => get_string('gender', 'artefact.resume'),
                ),
                'maritalstatus' => array(
                    'type' => 'text',
                    'defaultvalue' => ((!empty($personalinformation))
                        ? $personalinformation->get_composite('maritalstatus') :  null),
                    'title' => get_string('maritalstatus', 'artefact.resume'),
                    'size' => 30,
                ),
                'save' => array(
                    'type' => 'submit',
                    'value' => get_string('save'),
                ),
            ),
        ),
    ),
));

$smarty = smarty(array('artefact/resume/js/simpleresumefield.js'));
$smarty->assign('coverletterform', $coverletterform);
$smarty->assign('personalinformationform',$personalinformationform);
$smarty->assign('INLINEJAVASCRIPT', '$j(simple_resumefield_init);');
$smarty->assign('PAGEHEADING', TITLE);
$smarty->assign('SUBPAGENAV', PluginArtefactResume::submenu_items());
$smarty->display('artefact:resume:index.tpl');

function personalinformation_submit(Pieform $form, $values) {
    global $personalinformation, $USER;
    $userid = $USER->get('id');
    $errors = array();

    try {
        if (empty($personalinformation)) {
            $personalinformation = new ArtefactTypePersonalinformation(0, array(
                'owner' => $userid,
                'title' => get_string('personalinformation', 'artefact.resume'),
            ));
        }
        foreach (array_keys(ArtefactTypePersonalInformation::get_composite_fields()) as $field) {
            $personalinformation->set_composite($field, $values[$field]);
        }
        $personalinformation->commit();
    }
    catch (Exception $e) {
        $errors['personalinformation'] = true;
    }

    if (empty($errors)) {
        $form->json_reply(PIEFORM_OK, get_string('resumesaved','artefact.resume'));
    }
    else {
        $message = '';
        foreach (array_keys($errors) as $key) {
            $message .= get_string('resumesavefailed', 'artefact.resume')."\n";
        }
        $form->json_reply(PIEFORM_ERR, $message);
    }
}
