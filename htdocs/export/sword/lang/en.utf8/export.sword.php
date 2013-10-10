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

defined('INTERNAL') || die();

$string['title'] = 'HTML - SWORD';
$string['description'] = 'Deposit HTML content in a repository using the SWORD protocol. You cannot import this again, but it\'s readable in a standard web browser.';
$string['usersportfolio'] = '%s - Portfolio';

$string['preparing'] = 'Preparing %s';
$string['exportingdatafor'] = 'Exporting data for %s';
$string['buildingindexpage'] = 'Building index page';
$string['copyingextrafiles'] = 'Copying extra files';

$string['existingrepositories'] = 'Existing repositories';
$string['editexistingrepositoriestitle'] = 'SWORD - HTML : Edit existing repositories';
$string['editexistingrepositorytitle'] = 'SWORD - HTML : Edit existing repository';
$string['editexistingrepositories'] = 'Edit existing repositories';
$string['deleteexistingrepositorytitle'] = 'SWORD - HTML : Delete existing repository';
$string['repositoriesfound'] = 'The following repositories have been added:';
$string['norepositoriesfound'] = 'No repositories have been added.';

$string['existinglicences'] = 'Existing custom licences';
$string['editexistinglicence'] = 'Edit existing custom licence';
$string['deleteexistinglicence'] = 'Delete existing custom licence';
$string['editcustomlicence'] = 'Edit custom licence';
$string['setdefaultlicence'] = 'Set as default licence';
$string['setdefaultlicencedescription'] = 'If you check this box, this will be the default licence option presented to end users. <br />(They will still be able to select other options.)';
$string['deletecustomlicence'] = 'Delete custom licence';
$string['editcustomlicencedescription'] = 'Add details of a custom licence you would like to be assignable to the exported package. <br />You should check that the target repository can do something meaningful with the licence details. <br />At the time of writing, e-prints and d-space should support the selection of custom licences.';
$string['deletecustomlicencedescription'] = 'Custom licence to be deleted:';
$string['nocustomlicencesfound'] = 'No custom licences have been added.';

$string['addrepository'] = 'Add a repository';
$string['addanotherrepository'] = 'Add another repository';
$string['addrepositorydescription'] = 'Add details of a repository which supports SWORD deposits. <br />You should have set up a user account with the appropriate permissions in advance. <br />The repository will not be saved if a connection cannot be made.';
$string['editrepository'] = 'Edit repository';
$string['editrepositorydescription'] = 'Edit details of your repository. <br />You should have set up a user account with the appropriate permissions in advance. <br />The repository will not be saved if a connection cannot be made.';
$string['deleterepositorydescription'] = 'Repository to be deleted:';
$string['deleterepository'] = 'Delete repository';
$string['choosealicence'] = 'Select licence for your package';
$string['choosearepository'] = 'Select repository to deposit to';
$string['chooseacollection'] = 'Select repository collection to deposit to';
$string['repositorytitle'] = 'Title for the repository';
$string['repositorysduri'] = 'Full URI for the repository\'s Service Document';
$string['repositoryuser'] = 'The username for the repository';
$string['repositorypassword'] = 'The password for the repository';
$string['editrepositorypassword'] = 'The password for the repository (leave empty to retain previously entered password)';
$string['repositoryobo'] = 'The \'on-behalf-of\' value for the repository';
$string['repositoryswordversion'] = 'The version of SWORD supported by the repository';
$string['defaultcollection'] = 'Default collection for the repository';
$string['setdefaultcollection'] = 'Set a default collection for the repository? (If not the end user must choose a collection.)';

$string['addcustomlicence'] = 'Add a custom licence';
$string['addcustomlicencedescription'] = 'Add details of a custom licence you would like to be assignable to the exported package. <br />You should check that the target repository can do something meaningful with the licence details. <br />At the time of writing, e-prints and d-space should support the selection of custom licences.';
$string['customlicencetitle'] = 'Title string for licence';
$string['customlicenceuri'] = 'The uri of a web page with full details of the licence';

$string['swordinserterror'] = 'There was an error in inserting the new record.';
$string['servicedocumenterror'] = 'There was an error in communicating with the repository. Repository details were not saved.';
$string['swordversionerror'] = 'There was an error in retrieving the SWORD version supported by the repository. Repository details were not saved.';
$string['swordupdateerror'] = 'There was an error in updating the record.';
$string['sworddeleteerror'] = 'There was an error in deleting the record.';
$string['formerror'] = 'There was an error in retreiving the record details. Please try again or contact the administrator quoting this message.';
$string['collectionsretrievalerror'] = 'No collections were available for that repository. The connection to the repository may have failed, or may not have been correctly set up. Please contact the system administrator.';