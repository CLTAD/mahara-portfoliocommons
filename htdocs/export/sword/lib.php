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
safe_require('export', 'html');

/**
 * SWORD - HTML export plugin
 */
class PluginExportSword extends PluginExportHTML {

    /**
     * name of resultant zipfile
     */
    protected $zipfile;

    /**
     * The name of the directory under which all the other directories and
     * files will be placed in the export
     */
    protected $rootdir;

    /**
     * List of directories of static files provided by artefact plugins
     */
    private $pluginstaticdirs = array();

    /**
     * List of stylesheets to include in the export.
     *
     * This is keyed by artefact plugin name, the empty string key contains
     * stylesheets that will be included on all pages.
     */
    private $stylesheets = array('' => array());

    /**
     * Whether the user requested to export just one view. In this case,
     * the generated export doesn't have the home page - just the View is
     * exported (plus artefacts of course)
     */
    protected $exportingoneview = false;

    /**
     *
     * id of repository selected for deposit
     */
    private $repositoryid;

    /**
     *
     * uri of repository collection selected for deposit
     */
    private $collectionuri;

    /**
     *
     * title for exported package
     */
    private $metadatatitle;

    /**
     *
     * abstract for exported package
     */
    private $metadataabstract;

    /**
     *
     * id of licence selected for deposit
     */
    private $licenceid;

    /**
    * constructor.  overrides the parent class
    * to set up smarty and the attachment directory
    */
    public function __construct(User $user, $views, $artefacts, $progresscallback=null, $targetinfo) {
        global $THEME;
        parent::__construct($user, $views, $artefacts, $progresscallback);
        $this->rootdir = 'portfolio-for-' . self::text_to_path($user->get('username'));
        $this->repositoryid = $targetinfo['repositoryid'];
        $this->collectionuri = $targetinfo['collectionuri'];
        $this->metadatatitle = $targetinfo['metadata']['metadatatitle'];
        $this->metadataabstract = $targetinfo['metadata']['metadataabstract'];
        $this->licenceid = $targetinfo['licenceid'];

        // Create basic required directories
        foreach (array('files', 'views', 'static', 'static/smilies', 'static/profileicons') as $directory) {
            $directory = "{$this->exportdir}/{$this->rootdir}/{$directory}/";
            if (!check_dir_exists($directory)) {
                throw new SystemException("Couldn't create the temporary export directory $directory");
            }
        }
        $this->zipfile = 'mahara-export-sword-html-user'
            . $this->get('user')->get('id') . '-' . $this->exporttime . '.zip';

        // Find what stylesheets need to be included
        $themedirs = $THEME->get_path('', true, 'export/html');
        $stylesheets = array('style.css', 'print.css', 'jquery.rating.css');
        foreach ($themedirs as $theme => $themedir) {
            foreach ($stylesheets as $stylesheet) {
                if (is_readable($themedir . 'style/' . $stylesheet)) {
                    array_unshift($this->stylesheets[''], 'theme/' . $theme . '/static/style/' . $stylesheet);
                }
            }
        }

        // Don't export the dashboard
        foreach (array_keys($this->views) as $i) {
            if ($this->views[$i]->get('type') == 'dashboard') {
                unset($this->views[$i]);
            }
        }

        $this->exportingoneview = (
            $this->viewexportmode == PluginExport::EXPORT_LIST_OF_VIEWS &&
            $this->artefactexportmode == PluginExport::EXPORT_ARTEFACTS_FOR_VIEWS &&
            count($this->views) == 1
        );

        $this->notify_progress_callback(15, get_string('setupcomplete', 'export'));
    }

    public static function get_title() {
        return get_string('title', 'export.sword');
    }

    public static function get_description() {
        return get_string('description', 'export.sword');
    }

    public static function has_remote_target() {
        return true;
    }

    public static function get_repository_collections($repository) {
        $collections = array();
        $repo = get_record('export_sword_repository', 'repository', $repository);
        if (!$repo) {
            return false;
        }
        $swordversion = ($repo->swordversion == '2.0')? 2 : 1;
        require_once('sword' . $swordversion . '/swordappclient.php');
        $sac = new SWORDAPPClient();
        $sd = $sac->servicedocument($repo->servicedocumenturi, $repo->username, $repo->password, $repo->onbehalfof);
        $status = $sd->sac_status;
        $message = $sd->sac_statusmessage;
        if ($message == 'OK') {
            foreach ($sd->sac_workspaces as $key => $workspace) {
                $title = (string)$workspace->sac_workspacetitle;
                $collections['data'][$key]['workspacetitle'] = $title;
                $collections['data'][$key]['collections'] = $workspace->sac_collections;
                foreach ($workspace->sac_collections as $collkey => $coll) {
                    $href = (array)$coll->sac_href;
                    if ($repo->hasdefaultcollection == 1 && $repo->defaultcollection == $href[0]) {
                        $collections['defaultcollection'] = $href[0];
                    }
                }
            }
            return $collections;
        } else {
            return false;
        }
    }

    public static function get_new_repository_collections($data) {
        $collections = array();
        require_once('sword1/swordappclient.php');
        $sac = new SWORDAPPClient();
        $sd = $sac->servicedocument($data->servicedocumenturi, $data->username, $data->password, $data->onbehalfof);
        $status = $sd->sac_status;
        $message = $sd->sac_statusmessage;
        if ($message == 'OK') {
            foreach ($sd->sac_workspaces as $key => $workspace) {
                $title = (string)$workspace->sac_workspacetitle;
                $collections['data'][$key]['workspacetitle'] = $title;
                $collections['data'][$key]['collections'] = $workspace->sac_collections;
            }
            return $collections;
        } else {
            return false;
        }
    }

    /**
     * Main export routine
     */
    public function export() {
        global $THEME, $USER;
        raise_memory_limit('128M');

        $summaries = array();
        $plugins = plugins_installed('artefact', true);
        $exportplugins = array();
        $progressstart = 15;
        $progressend   = 25;
        $plugincount   = count($plugins);

        // First pass: find out which plugins are exporting like us, and ask
        // them about the static data they want to include in every template
        $i = 0;
        foreach ($plugins as $plugin) {
            $plugin = $plugin->name;
            $this->notify_progress_callback(intval($progressstart + (++$i / $plugincount) * ($progressend - $progressstart)), get_string('preparing', 'export.html', $plugin));

            if (safe_require('export', 'html/' . $plugin, 'lib.php', 'require_once', true)) {
                $exportplugins[] = $plugin;

                $classname = 'HtmlExport' . ucfirst($plugin);
                if (!is_subclass_of($classname, 'HtmlExportArtefactPlugin')) {
                    throw new SystemException("Class $classname does not extend HtmlExportArtefactPlugin as it should");
                }

                safe_require('artefact', $plugin);

                // Find out whether the plugin has static data for us
                $themestaticdirs = array_reverse($THEME->get_path('', true, 'artefact/' . $plugin . '/export/html'));
                foreach ($themestaticdirs as $dir) {
                    $staticdir = substr($dir, strlen(get_config('docroot') . 'artefact/'));
                    $this->pluginstaticdirs[] = $staticdir;
                    foreach (array('style.css', 'print.css') as $stylesheet) {
                        if (is_readable($dir . 'style/' . $stylesheet)) {
                            $this->stylesheets[$plugin][] = str_replace('export/html/', '', $staticdir) . 'style/' . $stylesheet;
                        }
                    }
                }
            }
        }

        // Second pass: actually dump data for active export plugins
        $progressstart = 25;
        $progressend   = 50;
        $i = 0;
        foreach ($exportplugins as $plugin) {
            $this->notify_progress_callback(intval($progressstart + (++$i / $plugincount) * ($progressend - $progressstart)), get_string('exportingdatafor', 'export.html', $plugin));
            $classname = 'HtmlExport' . ucfirst($plugin);
            $artefactexporter = new $classname($this);
            $artefactexporter->dump_export_data();
            // If just exporting a list of views, we don't care about the summaries for each artefact plugin
            if (!($this->viewexportmode == PluginExport::EXPORT_LIST_OF_VIEWS && $this->artefactexportmode == PluginExport::EXPORT_ARTEFACTS_FOR_VIEWS)) {
                $summaries[$plugin] = array($artefactexporter->get_summary_weight(), $artefactexporter->get_summary());
            }
        }

        // Views in collections
        if (!$this->exportingoneview && $this->collections) {
            $viewlist = join(',', array_keys($this->views));
            $collectionlist = join(',', array_keys($this->collections));
            $records = get_records_select_array(
                'collection_view',
                "view IN ($viewlist) AND collection IN ($collectionlist)"
            );
            if ($records) {
                foreach ($records as &$r) {
                    $this->collectionview[$r->collection][] = $r->view;
                    $this->viewcollection[$r->view] = $r->collection;
                }
            }
        }

        // Get the view data
        $this->notify_progress_callback(55, get_string('exportingviews', 'export'));
        $this->dump_view_export_data();

        if (!$this->exportingoneview) {
            $summaries['view'] = array(100, $this->get_view_summary());

            // Sort by weight (then drop the weight information)
            $this->notify_progress_callback(75, get_string('buildingindexpage', 'export.sword'));
            uasort($summaries, create_function('$a, $b', 'return $a[0] > $b[0];'));
            foreach ($summaries as &$summary) {
                $summary = $summary[1];
            }

            // Build index.html
            $this->build_index_page($summaries);
        }

        // Copy all static files into the export
        $this->notify_progress_callback(80, get_string('copyingextrafiles', 'export.sword'));
        $this->copy_static_files();

        // Copy all resized images that were found while rewriting the HTML
        $copyproxy = SwordExportCopyProxy::singleton();
        $copydata = $copyproxy->get_copy_data();
        foreach ($copydata as $from => $to) {
            $to = $this->get('exportdir') . '/' . $this->get('rootdir') . $to;
            if (!check_dir_exists(dirname($to))) {
                throw new SystemException("Could not create directory $todir");
            }
            if (!copy($from, $to)) {
                throw new SystemException("Could not copy static file $from");
            }
        }

        // create xml manifest
        $repo = get_record('export_sword_repository', 'repository', $this->repositoryid);
        if (!empty($repo)) {
            $swordversion = ($repo->swordversion == '2.0')? 2 : 1;
        } else {
            // can't export without connection details
            throw new ExportException("Could not retrieve repository details - please contact the system administrator.");
        }
        require_once('sword' . $swordversion . '/packager_mets_swap.php');
        // The location of the files (without final directory)
        $exportdir = $this->exportdir;
        // The location of the files
        $rootdir = $this->rootdir;
        // The location to write the package out to
        $rootout =  $this->rootdir;
        // The filename to save the package as
        $fileout = $this->zipfile;
        $type = 'http://purl.org/eprint/entityType/ScholarlyWork';
        reset($this->views);
        $firstkey = key($this->views);

        if (!empty($this->metadatatitle) && strlen($this->metadatatitle) > 0) {
            $title = $this->metadatatitle;
        } else {
            if (!$this->exportingoneview) {
                $title = display_name($USER) . ' - ' . date("Y-m-d");
            } else {
                $title = $this->views[$firstkey]->get('title');
            }
        }
        // The abstract of the item
        if (!empty($this->metadataabstract) && strlen($this->metadataabstract) > 0) {
            $abstract = $this->metadataabstract;
        } else {
            // look for view description
            $description = $this->views[$firstkey]->get('description');
            if (!empty($description)) {
                $abstract = 'Page description: ' .$description;
            } else {
                $abstract = 'No abstract provided.';
            }
        }

        // The licence for the item
        $customlicences = get_records_assoc('export_sword_customlicence');
        if ($customlicences) {
            $licencetitle = $customlicences[$this->licenceid]->title;
            $licenceuri = $customlicences[$this->licenceid]->uri;
        } else {
            throw new ExportException("No licences found. Please contact your system administrator.");
        }

        // Creators
        $creators = array();
        $creators[] = $USER->get('lastname') . ', ' . $USER->get('firstname');
        // Citation
        $citation = '';
        // Identifier
        $identifier = get_config('wwwroot'); // base url of Mahara site deposit is made from
        // Date made available
        $dateavailable = date("Y-m-d");
        // Copyright holder
        $copyrightholder = display_name($USER);
        // Custodian
        $custodian = display_name($USER);
        // Status statement
        $statusstatement = '';

        $filestoadd = array();
        $offset = strlen($this->rootdir) + 1;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->exportdir . $this->rootdir)) as $filename) {
            $filepath = substr($filename->getPath(), (strpos($filename->getPath(), $this->rootdir) + $offset));
            if (strlen($filepath)) {
                $filepath .= '/';
            }
            $filepathandname = $filepath . $filename->getFilename();
            // We'll add index.html later so that it's the first entry
            if ($filepathandname != 'index.html') {
                $filestoadd[] = array('filename' => $filepathandname, 'mimetype' => file_mime_type($filename->getFilename()) );
            }
        }
        $mets_packager = new PackagerMetsSwap($exportdir, $rootdir, $rootout, $fileout);
        $mets_packager->setCustodian($custodian);
        $mets_packager->setType($type);
        $mets_packager->setTitle($title);
        $mets_packager->setAbstract($abstract);
        foreach ($creators as $creator) {
            $mets_packager->addCreator($creator);
        }
        $mets_packager->setIdentifier($identifier);
        $mets_packager->setDateAvailable($dateavailable);
        $mets_packager->setStatusStatement($statusstatement);
        $mets_packager->setCopyrightHolder($copyrightholder);
        $mets_packager->addRights($licencetitle);
        $mets_packager->setAccessRights($licenceuri);
        $mets_packager->setCitation($citation);
        $mets_packager->addFile('index.html', 'text/html');
        foreach ($filestoadd as $file) {
            $mets_packager->addFile($file['filename'], $file['mimetype']);
        }

        $this->notify_progress_callback(90, get_string('creatingzipfile', 'export'));
        // Write the metadata (mets) file
        $fh = @fopen($mets_packager->sac_root_in . '/' . $mets_packager->sac_dir_in . '/' . $mets_packager->sac_metadata_filename, 'w');
        if (!$fh) {
            throw new ExportException("Error writing metadata file (" . $mets_packager->sac_root_in . '/' . $mets_packager->sac_dir_in . '/' . $mets_packager->sac_metadata_filename . ")");
        }
        $mets_packager->writeHeader($fh);
        $mets_packager->writeDmdSec($fh);
        $mets_packager->writeFileGrp($fh);
        $mets_packager->writeStructMap($fh);
        $mets_packager->writeFooter($fh);
        fclose($fh);

        // zip everything up
        $cwd = getcwd();
        $command = sprintf('%s %s %s %s',
            get_config('pathtozip'),
            get_config('ziprecursearg'),
            escapeshellarg($this->exportdir . $this->rootdir . '/' . $this->zipfile),
            escapeshellarg('.')
        );
        $output = array();
        chdir($this->exportdir . $this->rootdir);
        exec($command, $output, $returnvar);
        chdir($cwd);
        if ($returnvar != 0) {
            throw new SystemException('Failed to zip the export file');
        }

        $this->notify_progress_callback(95, 'Zipped up package');
        require_once('sword' . $swordversion . '/swordappclient.php');

        $sac = new SWORDAPPClient();
        $repouser = $repo->username;
        $repopass = $repo->password;
        $repoobo = $repo->onbehalfof;

        $contenttype = "application/zip";
        //$contenttype = "text/plain";
        //$format = "http://purl.org/net/sword/package/Binary";
        //$format = "http://purl.org/net/sword/package/SimpleZip";
        if ($swordversion == 1) {
            $format = "http://purl.org/net/sword-types/METSDSpaceSIP";
        } else {
            $format = "http://purl.org/net/sword/package/METSDSpaceSIP";
        }
        $deposit = $sac->deposit($this->collectionuri, $repouser, $repopass, $repoobo, $this->exportdir . $this->rootdir . '/' . $this->zipfile , $format, $contenttype, false);
        $status = ($deposit->sac_status <= 202)? 'Operation succeeded. ' : 'Operation failed. ';
        $status .= '<a href="remote.php" target="_top">Continue</a>';
        $finalreport = '<ul><li class="final-report-title"><label>Status code:</label> ' . $deposit->sac_status . '</li>';
        $finalreport .= '<li class="final-report-title"><label>Status Message:</label> ' . $deposit->sac_statusmessage . '</li>';

        $resourcelink = "No link returned";
        $sac_xml = @new SimpleXMLElement($deposit->sac_xml);
        foreach ($sac_xml->xpath("atom:link") as $sac_link) {
            if(isset($sac_link['rel'])) {
                $rel = (string) $sac_link['rel'];
                if ($rel == "alternate") {
                    $resourcelink = '<a target="_parent" href="' . sac_clean($sac_link['href']) . '">' . sac_clean($sac_link['href']) . '</a>';
                }
            }
        }

        $finalreport .= '<li class="final-report-title"><label>View item in repository:</label> ' . $resourcelink. '</li></ul>';;
        $this->notify_progress_callback(100, $status, $finalreport);

        return false;
    }

    public function cleanup() {
        // @todo remove temporary files and directories
        // @todo maybe move the zip file somewhere else - like to files/export or something
    }

    public function get_smarty($rootpath='', $section='') {
        if ($section && isset($this->stylesheets[$section])) {
            $stylesheets = array_merge($this->stylesheets[''], $this->stylesheets[$section]);
        }
        else {
            $stylesheets = $this->stylesheets[''];
        }
        $smarty = smarty_core();
        $smarty->assign('user', $this->get('user'));
        $smarty->assign('rootpath', $rootpath);
        $smarty->assign('export_time', $this->exporttime);
        $smarty->assign('sitename', get_config('sitename'));
        $smarty->assign('stylesheets', $stylesheets);
        $smarty->assign('maharalogo', $rootpath . $this->theme_path('images/logo.png'));

        return $smarty;
    }

    /**
     * Converts a relative path to a static file that the HTML export theme
     * should have, to a path in the static export where the file will reside.
     *
     * This returns the path in the most appropriate theme.
     */
    private function theme_path($path) {
        global $THEME;
        $themestaticdirs = $THEME->get_path('', true, 'export/html');
        foreach ($themestaticdirs as $theme => $dir) {
            if (is_readable($dir . $path)) {
                return 'static/theme/' . $theme . '/static/' . $path;
            }
        }
    }

    /**
     * Converts the passed text into a a form that could be used in a URL.
     *
     * @param string $text The text to convert
     * @return string      The converted text
     */
    public static function text_to_path($text) {
        return substr(preg_replace('#[^a-zA-Z0-9_-]+#', '-', $text), 0, 255);
    }

    /**
     * Sanitises a string meant to be used as a filesystem path.
     *
     * Mahara allows file/folder artefact names to have slashes in them, which
     * aren't legal on most real filesystems.
     */
    public static function sanitise_path($path) {
        return trim(substr(str_replace('/', '_', $path), 0, 255));
    }


    private function build_index_page($summaries) {
        $smarty = $this->get_smarty();
        $smarty->assign('page_heading', full_name($this->get('user')));
        $smarty->assign('summaries', $summaries);
        $content = $smarty->fetch('export:sword:index.tpl');
        if (!file_put_contents($this->exportdir . '/' . $this->rootdir . '/index.html', $content)) {
            throw new SystemException("Could not create index.html for the export");
        }
    }

    private function collection_menu($collectionid) {
        static $menus = array();
        if (!isset($menus[$collectionid])) {
            $menus[$collectionid] = array();
            foreach ($this->collectionview[$collectionid] as $viewid) {
                $title = $this->views[$viewid]->get('title');
                $menus[$collectionid][] = array(
                    'id'   => $viewid,
                    'url'  => self::text_to_path($title) . '/index.html',
                    'text' => $title,
                );
            }
        }
        return $menus[$collectionid];
    }

    /**
     * Dumps all views into the HTML export
     */
    private function dump_view_export_data() {
        safe_require('artefact', 'comment');
        $progressstart = 55;
        $progressend   = 75;
        $i = 0;
        $viewcount = count($this->views);
        $rootpath = ($this->exportingoneview) ? './' : '../../';
        $smarty = $this->get_smarty($rootpath);
        foreach ($this->views as $viewid => $view) {
            $this->notify_progress_callback(intval($progressstart + (++$i / $viewcount) * ($progressend - $progressstart)), get_string('exportingviewsprogress', 'export', $i, $viewcount));
            $smarty->assign('page_heading', $view->get('title'));
            $smarty->assign('viewdescription', $view->get('description'));

            if ($this->exportingoneview) {
                $smarty->assign('nobreadcrumbs', true);
                $directory = $this->exportdir . '/' . $this->rootdir;
            }
            else {
                $smarty->assign('breadcrumbs', array(
                    array('text' => get_string('Views', 'view')),
                    array('text' => $view->get('title'), 'path' => 'index.html'),
                ));
                $directory = $this->exportdir . '/' . $this->rootdir . '/views/' . self::text_to_path($view->get('title'));
                if (!check_dir_exists($directory)) {
                    throw new SystemException("Could not create directory for view $viewid");
                }
            }

            // Collection menu data
            if (isset($this->viewcollection[$viewid])) {
                $smarty->assign_by_ref('collectionname', $this->collections[$this->viewcollection[$viewid]]->get('name'));
                $smarty->assign_by_ref('collectionmenu', $this->collection_menu($this->viewcollection[$viewid]));
                $smarty->assign('viewid', $viewid);
            }

            $outputfilter = new SwordExportOutputFilter($rootpath, $this);

            // Include comments
            if ($this->includefeedback) {
                $feedback = null;
                $artefact = null;
                if ($feedback = ArtefactTypeComment::get_comments(0, 0, null, $view, $artefact, true)) {
                    $feedback->tablerows = $outputfilter->filter($feedback->tablerows);
                }
                $smarty->assign('feedback', $feedback);
            }

            $smarty->assign('view', $outputfilter->filter($view->build_columns()));
            $content = $smarty->fetch('export:sword:view.tpl');
            if (!file_put_contents("$directory/index.html", $content)) {
                throw new SystemException("Could not write view page for view $viewid");
            }
        }
    }

    private function get_view_summary() {
        $smarty = $this->get_smarty('../');

        $list = array();
        foreach ($this->collections as $id => $collection) {
            $list['c' . $id] = array(
                'title' => $collection->get('name'),
                'views' => array(),
            );
        }

        $nviews = 0;
        foreach ($this->views as $id => $view) {
            if ($view->get('type') != 'profile') {
                $item = array(
                    'title' => $view->get('title'),
                    'folder' => self::text_to_path($view->get('title')),
                );
                if (isset($this->viewcollection[$id])) {
                    $list['c' . $this->viewcollection[$id]]['views'][] = $item;
                }
                else {
                    $list[$id] = $item;
                }
                $nviews++;
            }
        }
        function sort_by_title($a, $b) {
            return strnatcasecmp($a['title'], $b['title']);
        }
        foreach (array_keys($this->collections) as $id) {
            usort($list['c' . $id]['views'], 'sort_by_title');
        }
        usort($list, 'sort_by_title');
        $smarty->assign('list', $list);

        if ($list) {
            $stryouhaveviews = ($nviews == 1)
                ? get_string('youhaveoneview', 'view')
                : get_string('youhaveviews', 'view', $nviews);
        }
        else {
            $stryouhaveviews = get_string('youhavenoviews', 'view');
        }
        $smarty->assign('stryouhaveviews', $stryouhaveviews);

        return array(
            'title' => get_string('Views', 'view'),
            'description' => $smarty->fetch('export:sword:viewsummary.tpl'),
        );
    }

    /**
     * Copies the static files (stylesheets etc.) into the export
     */
    private function copy_static_files() {
        global $THEME;
        require_once('file.php');
        $staticdir = $this->get('exportdir') . '/' . $this->get('rootdir') . '/static/';
        $directoriestocopy = array();

        // Get static directories from each theme for HTML export
        $themestaticdirs = $THEME->get_path('', true, 'export/html');
        foreach ($themestaticdirs as $theme => $dir) {
            $themedir = $staticdir . 'theme/' . $theme . '/static/';
            $directoriestocopy[$dir] = $themedir;
            if (!check_dir_exists($themedir)) {
                throw new SystemException("Could not create theme directory for theme $theme");
            }
        }

        // Smilies
        $directoriestocopy[get_config('docroot') . 'js/tinymce/plugins/emotions/img'] = $staticdir . 'smilies/';

        $filestocopy = array(
            get_config('docroot') . 'theme/views.css' => $staticdir . 'views.css',
        );

        foreach ($this->pluginstaticdirs as $dir) {
            $destinationdir = str_replace('export/html/', '', $dir);
            if (!check_dir_exists($staticdir . $destinationdir)) {
                throw new SystemException("Could not create static directory $destinationdir");
            }
            $directoriestocopy[get_config('docroot') . 'artefact/' . $dir] = $staticdir . $destinationdir;
        }

        foreach ($directoriestocopy as $from => $to) {
            if (!copyr($from, $to)) {
                throw new SystemException("Could not copy $from to $to");
            }
        }

        foreach ($filestocopy as $from => $to) {
            if (!copy($from, $to)) {
                throw new SystemException("Could not copy static file $from");
            }
        }
    }

    public static function has_config() {
        return true;
    }

    public static function get_config_options() {
        $elements = array();
        $wwwroot = get_config('wwwroot');

        $repos = get_records_array('export_sword_repository');
        $customlicences = get_records_array('export_sword_customlicence');
        $defaultlicence = get_config('swordexportdefaultlicence');

        $elements['addrepository'] = array(
                'type' => 'html',
                'value' => '<a href="' . $wwwroot . 'export/sword/addrepository.php" class="fl"><input class="button" type="button" value="' . get_string('addrepository', 'export.sword') . '"></a>'
        );

        $elements['addcustomlicence'] = array(
                'type' => 'html',
                'value' => '<a href="' . $wwwroot . 'export/sword/addcustomlicence.php"><input class="button" type="button" value="' . get_string('addcustomlicence', 'export.sword') . '"></a>'
        );

        $elements['existingrepositories'] = array(
                'type' => 'fieldset',
                'legend' => get_string('existingrepositories', 'export.sword'),
                'elements' => array(
                    'existingreposdescription' => array (
                     	'type' => 'html',
                        'value' => ($repos != false)? '': get_string('norepositoriesfound', 'export.sword')
                    ),
                ),
                'collapsible' => false,
        );

        if ($repos) {
            foreach ($repos as $repo) {
                $hasdef = ($repo->hasdefaultcollection == 1)?'Yes' : 'No';
                $elements['existingrepositories']['elements']['repo' . $repo->repository] = array(
                        'type' => 'html',
                        'value' => "<strong class='fl'>$repo->title</strong>
                        <ul class='fl cl'>
                        <li>Service document url: $repo->servicedocumenturi</li>
                        <li>Username: $repo->username</li>
                        <li>On-behalf-of value: $repo->onbehalfof</li>
                        <li>Sword version supported: $repo->swordversion</li>
                        <li>Default collection defined: $hasdef</li>
                        </ul>"
                );
                $elements['existingrepositories']['elements']['repodelete' . $repo->repository] = array(
                        'type' => 'html',
                        'value' => '<a href="' . $wwwroot . 'export/sword/deleterepository.php?action=delete&id=' . $repo->repository . '"><input class="button fr" type="button" value="' . get_string('delete') . '"></a>'
                );
                $elements['existingrepositories']['elements']['repoedit' . $repo->repository] = array(
                        'type' => 'html',
                        'value' => '<a href="' . $wwwroot . 'export/sword/editrepository.php?action=edit&id=' . $repo->repository . '"><input class="button fr" type="button" value="' . get_string('edit') . '"></a>'
                );
                $elements['existingrepositories']['elements']['clear' . $repo->repository] = array (
                        'type' => 'html',
                        'class' => 'cb',
                        'value' => '<div><hr /></div>',
                );
            }
       }

        $elements['existinglicences'] = array(
                'type' => 'fieldset',
                'legend' => get_string('existinglicences', 'export.sword'),
                'elements' => array(
                        'existinglicencesdescription' => array (
                                'type' => 'html',
                                'value' => ($customlicences != false)? '': get_string('nocustomlicencesfound', 'export.sword')
                        ),
                ),
                'collapsible' => false,
        );

        if ($customlicences) {
            foreach ($customlicences as $customlicence) {
                $defaulttext = '';
                if ($defaultlicence == $customlicence->licence) {
                    $defaulttext = ' (DEFAULT)';
                }
                $elements['existinglicences']['elements']['licence' . $customlicence->licence] = array(
                        'type' => 'html',
                        'value' => "<strong class='fl cb'>$customlicence->title $defaulttext</strong>
                        <ul class='fl cl'>
                        <li>Document url: $customlicence->uri</li>
                        </ul>"
                );
                $elements['existinglicences']['elements']['licencedelete' . $customlicence->licence] = array(
                        'type' => 'html',
                        'value' => '<a href="' . $wwwroot . 'export/sword/deletecustomlicence.php?action=delete&id=' . $customlicence->licence . '"><input class="button fr" type="button" value="' . get_string('delete') . '"></a>'
                );
                $elements['existinglicences']['elements']['licenceedit' . $customlicence->licence] = array(
                        'type' => 'html',
                        'value' => '<a href="' . $wwwroot . 'export/sword/editcustomlicence.php?action=edit&id=' . $customlicence->licence . '"><input class="button fr" type="button" value="' . get_string('edit') . '"></a>'
                );
                $elements['existinglicences']['elements']['clear' . $customlicence->licence] = array (
                        'type' => 'html',
                        'class' => 'cb',
                        'value' => '<div><hr /></div>',
                );
            }
        }

        return array(
                'elements' => $elements,
                'renderer' => 'div'
        );
    }

    public static function save_config_options($values) {
    }

    public static function validate_config_options(Pieform $form, $values) {
    }

    public static function postinst($prevversion) {
        if ($prevversion == 0) {
            $cclicences = array(1 => array('title' => 'Creative Commons Attribution', 'uri' => 'http://creativecommons.org/licenses/by/3.0/'),
                                2 => array('title' => 'Creative Commons ShareAlike', 'uri' => 'http://creativecommons.org/licenses/by-sa/3.0'),
                                3 => array('title' => 'Creative Commons Attribution-NoDerivs', 'uri' => 'http://creativecommons.org/licenses/by-nd/3.0'),
                                4 => array('title' => 'Creative Commons Attribution-NonCommercial', 'uri' => 'http://creativecommons.org/licenses/by-nc/3.0'),
                                5 => array('title' => 'Creative Commons Attribution-NonCommercial-ShareAlike', 'uri' => 'http://creativecommons.org/licenses/by-nc-sa/3.0'),
                                6 => array('title' => 'Creative Commons Attribution-NonCommercial-NoDerivs', 'uri' => 'http://creativecommons.org/licenses/by-nc-nd/3.0'),
                                );
            foreach ($cclicences as $cclicence) {
                $licence = new stdClass();
                $licence->title = $cclicence['title'];
                $licence->uri = $cclicence['uri'];
                insert_record('export_sword_customlicence', $licence);
            }
        }
    }

}


/**
 * Provides a mechanism for converting the HTML generated by views and
 * artefacts for the HTML export.
 *
 * Mostly, this means rewriting links to artefacts to point the correct place
 * in the export.
 */
class SwordExportOutputFilter {

    /**
     * The relative path to the root of the generated export - used for link munging
     */
    private $basepath = '';

    /**
     * A cache of view titles. See replace_view_link()
     */
    private $viewtitles = array();

    /**
     * A cache of folder data. See get_folder_path_for_file()
     */
    private $folderdata = null;

    /**
     */
    private $swordexportcopyproxy = null;

    /**
     */
    private $exporter = null;

    /**
     */
    private $owner = null;

    /**
     * @param string $basepath The relative path to the root of the generated export
     */
    public function __construct($basepath, &$exporter=null) {
        $this->basepath = preg_replace('#/$#', '', $basepath);
        $this->swordexportcopyproxy = SwordExportCopyProxy::singleton();
        $this->exporter = $exporter;
        $this->owner = $exporter->get('user')->get('id');
    }

    /**
     * Filters the given HTML for HTML export purposes
     *
     * @param string $html The HTML to filter
     * @return string      The filtered HTML
     */
    public function filter($html) {
        $wwwroot = preg_quote(get_config('wwwroot'));
        $html = preg_replace(
            array(
                // We don't care about javascript
                '#<script[^>]*>.*?</script>#si',
                // Fix simlies from tinymce
                '#<img ([^>]*)src="(' . $wwwroot . ')?/?js/tinymce/plugins/emotions/img/([^"]+)"([^>]+)>#',
                // No forms
                '#<form[^>]*>.*?</form>#si',
                // Gratuitous hack for the RSS blocktype
                '#<div id="blocktype_externalfeed_lastupdate">[^<]*</div>#',
            ),
            array(
                '',
                '<img $1src="' . $this->basepath . '/static/smilies/$3"$4>',
                '',
                '',
            ),
            $html
        );

        // Links to views
        $html = preg_replace_callback(
            '#' . $wwwroot . 'view/view\.php\?id=(\d+)#',
            array($this, 'replace_view_link'),
            $html
        );

        // Links to artefacts
        $html = preg_replace_callback(
            '#<a[^>]+href="(' . $wwwroot . ')?/?view/artefact\.php\?artefact=(\d+)(&amp;view=\d+)?(&amp;offset=\d+)?"[^>]*>([^<]*)</a>#',
            array($this, 'replace_artefact_link'),
            $html
        );

        // Links to image artefacts
        $html = preg_replace_callback(
            '#<a[^>]+href="(' . $wwwroot . ')?/?view/artefact\.php\?artefact=(\d+)(&amp;view=\d+)?(&amp;offset=\d+)?"[^>]*>(<img[^>]+>)</a>#',
            array($this, 'replace_artefact_link'),
            $html
        );

        // Links to download files
        $html = preg_replace_callback(
            '#(?<=[\'"])(' . $wwwroot . ')?/?artefact/file/download\.php\?file=(\d+)(?:(?:&amp;|&|%26)([a-z]+=[x0-9]+)+)*#',
            array($this, 'replace_download_link'),
            $html
        );

        // Thumbnails
        require_once('file.php');
        $html = preg_replace_callback(
            '#(?<=[\'"])(' . $wwwroot . ')?/?thumb\.php\?type=([a-z]+)((?:(?:&amp;|&|%26)[a-z]+=[x0-9]+)+)*#',
            array($this, 'replace_thumbnail_link'),
            $html
        );

        // Images out of the theme directory
        $html = preg_replace_callback(
            '#(?<=[\'"])(' . $wwwroot . '|/)?((?:[a-z]+/)*)theme/([a-zA-Z0-9_.-]+)/static/images/([a-z0-9_.-]+)#',
            array($this, 'replace_theme_image_link'),
            $html
        );

        return $html;
    }

    /**
     * Callback to replace links to views to point to the correct location in
     * the HTML export
     */
    private function replace_view_link($matches) {
        $viewid = $matches[1];
        // Don't rewrite links to views that are not going to be included in the export
        if (!isset($this->exporter->views[$viewid])) {
            return $matches[0];
        }
        if (!isset($this->viewtitles[$viewid])) {
            $this->viewtitles[$viewid] = PluginExportSword::text_to_path(get_field('view', 'title', 'id', $viewid));
        }
        return $this->basepath . '/views/' . $this->viewtitles[$viewid] . '/index.html';
    }

    /**
     * Callback to replace links to artefact to point to the correct location
     * in the HTML export
     */
    private function replace_artefact_link($matches) {
        $artefactid = $matches[2];
        try {
            $artefact = artefact_instance_from_id($artefactid);
        }
        catch (ArtefactNotFoundException $e) {
            return $matches[5];
        }

        $artefacttype = $artefact->get('artefacttype');
        switch ($artefacttype) {
        case 'blog':
        case 'plan':
            $dir = $artefacttype == 'plan' ? 'plans' : $artefacttype;
            $offset = ($matches[4]) ? intval(substr($matches[4], strlen('&amp;offset='))) : 0;
            $offset = ($offset == 0) ? 'index' : $offset;
            return '<a href="' . $this->basepath . "/files/$dir/" . PluginExportSword::text_to_path($artefact->get('title')) . '/' . $offset . '.html">' . $matches[5] . '</a>';
        case 'file':
        case 'folder':
        case 'image':
        case 'profileicon':
        case 'archive':
        case 'video':
        case 'audio':
            return '<a href="' . $this->get_export_path_for_file($artefact, array()) . '">' . $matches[5] . '</a>';
        default:
            return $matches[5];
        }
    }

    /**
     * Callback to replace links to artefact/file/download.php to point to the
     * correct file in the HTML export
     */
    private function replace_download_link($matches) {
        $artefactid = $matches[2];
        try {
            $artefact = artefact_instance_from_id($artefactid);
        }
        catch (ArtefactNotFoundException $e) {
            return '';
        }

        // If artefact type not something that would be served by download.php,
        // replace link with nothing
        if ($artefact->get_plugin_name() != 'file') {
            return '';
        }

        $options = array();
        for ($i = 3; $i < count($matches); $i++) {
            list($key, $value) = explode('=', $matches[$i]);
            $options[$key] = $value;
        }

        return $this->get_export_path_for_file($artefact, $options);
    }

    /**
     * Callback to replace links to thumb.php to point to the correct file in
     * the HTML export
     */
    private function replace_thumbnail_link($matches) {
        if (isset($matches[3])) {
            $type = $matches[2];

            $parts = preg_split('/(&amp;|&|%26)/', $matches[3]);
            array_shift($parts);
            foreach ($parts as $part) {
                list($key, $value) = explode('=', $part);
                $options[$key] = $value;
            }

            if (!isset($options['id'])) {
                return '';
            }

            switch ($type) {
            case 'profileicon':
                // Convert the user ID to a profile icon ID
                if (!$options['id'] = get_field_sql('SELECT profileicon FROM {usr} WHERE id = ?', array($options['id']))) {
                    // No profile icon, get the default one
                    list($size, $prefix) = $this->get_size_from_options($options);
                    if ($from = get_dataroot_image_path('artefact/file/profileicons/no_userphoto/' . get_config('theme'), 0, $size)) {
                        $to = '/static/profileicons/0-' . $prefix . 'no_userphoto.png';
                        $this->swordexportcopyproxy->add($from, $to);
                    }
                    return $this->basepath . $to;
                }
            case 'profileiconbyid':
                try {
                    $icon = artefact_instance_from_id($options['id']);
                }
                catch (ArtefactNotFoundException $e) {
                    return '';
                }
                if ($icon->get_plugin_name() != 'file') {
                    return '';
                }
                return $this->get_export_path_for_file($icon, $options, '/static/profileicons/');
            default:
                return '';
            }
        }

        return '';
    }

    /**
     * Callback
     */
    private function replace_theme_image_link($matches) {
        $file = $matches[2] . 'theme/' . $matches[3] . '/static/images/' . $matches[4];
        $this->swordexportcopyproxy->add(
            get_config('docroot') . $file,
            '/static/' . $file
        );
        return $this->basepath . '/static/' . $file;
    }

    /**
     * Given a file, returns the folder path for it in the Mahara files area
     *
     * The path is pre-sanitised so it can be used when generating the export
     *
     * @param  $file The file or folder to get the folder path for
     * @return string
     */
    private function get_folder_path_for_file($file) {
        if ($this->folderdata === null) {
            $this->folderdata = get_records_select_assoc('artefact', "artefacttype = 'folder' AND owner = ?", array($file->get('owner')));
            if ($this->folderdata) {
                foreach ($this->folderdata as &$folder) {
                    $folder->title = PluginExportSword::sanitise_path($folder->title);
                }
            }
        }
        $folderpath = ArtefactTypeFileBase::get_full_path($file->get('parent'), $this->folderdata);
        return $folderpath;
    }

    /**
     * Generates a path, relative to the root of the export, that the given
     * file will appear in the export.
     *
     * If the file is a thumbnail, the copy proxy is informed about it so that
     * the image can later be copied in to place.
     *
     * @param ArtefactTypeFileBase $file The file to get the exported path for
     * @param array $options             Options from the URL that was linking
     *                                   to the image - most importantly, size
     *                                   related options about how the image
     *                                   was thumbnailed, if it was.
     * @param string $basefolder         What folder in the export to dump the
     *                                   file in
     * @return string                    The relative path to where the file
     *                                   will be placed
     */
    private function get_export_path_for_file(ArtefactTypeFileBase $file, array $options, $basefolder=null) {
        if (is_null($basefolder)) {
            if ($file->get('owner') == $this->owner) {
                $basefolder = '/files/file/' . $this->get_folder_path_for_file($file);
            }
            else {
                $basefolder = '/files/extra/';
            }
        }

        unset($options['view']);
        $prefix = '';
        $title = PluginExportSword::sanitise_path($file->get('title'));

        if ($options) {
            list($size, $prefix) = $this->get_size_from_options($options);
            $from = $file->get_path($size);

            $to = $basefolder . $file->get('id') . '-' . $prefix . $title;
            $this->swordexportcopyproxy->add($from, $to);
        }
        else {
            if ($basefolder == '/files/extra/') {
                $title = $file->get('id') . '-' . $title;
            }
            $to = $basefolder . $title;
        }

        return $this->basepath . $to;
    }

    /**
     * Helper method
     */
    private function get_size_from_options($options) {
        $prefix = '';
        foreach (array('size', 'width', 'height', 'maxsize', 'maxwidth', 'maxheight') as $param) {
            if (isset($options[$param])) {
                $$param = $options[$param];
                $prefix .= $param . '-' . $options[$param] . '-';
            }
            else {
                $$param = null;
            }
        }

        return array(imagesize_data_to_internal_form($size, $width, $height, $maxsize, $maxwidth, $maxheight), $prefix);
    }

}

/**
 * Gathers a list of files that need to be copied into the export, as they're
 * found by the SwordExportOutputFilter
 */
class SwordExportCopyProxy {

    private static $instance = null;
    private $copy = array();

    private function __construct() {
    }

    public static function singleton() {
        if (is_null(self::$instance)) {
            self::$instance = new SwordExportCopyProxy();
        }
        return self::$instance;
    }

    public function add($from, $to) {
        $this->copy[$from] = $to;
    }

    public function get_copy_data() {
        return $this->copy;
    }
}
