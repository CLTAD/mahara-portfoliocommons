<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2010 Catalyst IT Ltd and others; see:
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
 * @subpackage lib
 * @author     Catalyst IT Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2006-2010 Catalyst IT Ltd http://catalyst.net.nz
 *
 */

// Every function which outputs to a page outside of a template should be in this file
// so that it's easier to review for security purposes

function print_export_head($stylesheets) {
?>
<html>
    <head>
        <title></title>
<?php foreach ($stylesheets as $stylesheet) { ?>
        <link rel="stylesheet" type="text/css" href="<?php echo hsc($stylesheet); ?>">
<?php } ?>
        <style type="text/css">
            html, body {
                margin: 0;
                padding: 0;
                background-color: #FFF;
            }
        </style>
    </head>
    <body>
    <div style="width: 100%; background-color: #808080;" class="progress-bar"></div>
    <p class="progress-text"><?php echo get_string('Starting', 'export'); ?></p>
<?php
}

function print_export_iframe_die($message, $link=null) {
    $message = hsc($message);
    if (isset($link)) {
        $message .= ' <a target="_top" href="' . hsc($link) . '">' . get_string('continue', 'admin') . '</a>';
    }
    echo '<div class="progress-bar" style="width: 100%;"><p>' . $message . '</p></div></body></html>';
}

function print_iframe_progress_handler($percent, $status, $finalreport="") {
    // "Erase" the current output with a new background div
    echo '<div style="width: 100%; background-color: #808080;" class="progress-bar"></div>';
    // The progress bar itself
    echo '<div class="progress-bar" style="width: ' . intval($percent) . '%;"></div>' . "\n";
    // The status text
    echo '<p class="progress-text">' . hsc($status) . "</p>\n";
    // The final status report
    echo '<div class="progress-final-report">' . $finalreport . '</div>';
}

function print_export_footer($strexportgenerated, $continueurl, $continueurljs, $jsmessages=array(), $newlocation) {
?>
        <script type="text/javascript">
            document.write('<div class="progress-bar" style="width: 100%;"><p><?php echo $strexportgenerated . ' <a href="' . $continueurljs . '" target="_top">' . get_string('continue', 'export') . '</a>'; ?></p></div>');
            if (!window.opera && $newlocation !== false) {
                // Opera can't handle this for some reason - it vomits out the
                // download inline in the iframe
                document.location = '<?php echo $newlocation; ?>';
            }
            var messages = <?php echo json_encode($jsmessages); ?>;
            if (messages) {
                for (var i = 0; i < messages.length; i++) {
                    parent.displayMessage(messages[i].msg, messages[i].type, false);
                }
            }
        </script>
        <div class="progress-bar" style="width: 100%;">
            <p>
            <?php
                // a bit of a cludge here...
                echo $strexportgenerated . ' <a href="' . $continueurl . '" target="_top">' . (($continueurl == 'download.php')? get_string('clickheretodownload', 'export') : get_string('continue', 'export')) . '</a>';
            ?>
            </p>
        </div>
    </body>
</html>
<?php
}

function print_extractprogress_head($stylesheets, $artefacts) {
?>
<html>
    <head>
        <title></title>
<?php foreach ($stylesheets as $stylesheet) { ?>
        <link rel="stylesheet" type="text/css" href="<?php echo hsc($stylesheet); ?>">
<?php } ?>
        <style type="text/css">
            html, body {
                margin: 0;
                padding: 0;
                background-color: #808080;
            }
        </style>
    </head>
    <body>
    <div style="width: 100%; background-color: #808080;" class="progress-bar"></div>
    <p class="progress-text"><?php echo get_string('unzipprogress', 'artefact.file', '0/' . $artefacts); ?></p>
<?php
}

function print_extractprogress_footer($message, $next) {
?>
        <div class="progress-bar" style="width: 100%;">
        <p><?php echo $message; ?> <a href="<?php echo $next; ?>" target="_top"><?php echo get_string('Continue', 'artefact.file'); ?></a></p>
        </div>
    </body>
</html>
<?php
}

function execute_javascript_and_close($js='') {
    echo '<html>
    <head>
        <title>You may close this window</title>
        <script language="Javascript">
            function closeMe() {
                '.$js.'
                window.close();
            }
        </script>
    </head>
    <body onLoad="closeMe();" style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; text-align: center;">This window should close automatically</body>'.
    "\n</html>";
    exit;
}

function print_meta_redirect($url) {
    print '<html><head><meta http-equiv="Refresh" content="0; url=' . $url . '">';
    print '</head><body><p>Please follow <a href="'.$url.'">link</a>!</p></body></html>';
}

function print_auth_frame() {
    $frame = '<html><head></head><body onload="parent.show_login_form(\'ajaxlogin_iframe\')"></body></html>';
    echo $frame;
}
