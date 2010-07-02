<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * On this page administrator can change hub settings
 * @package   localhub
 * @copyright 2010 Moodle Pty Ltd (http://moodle.com)
 * @author    Jerome Mouneyrac
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/hub/admin/forms.php');

admin_externalpage_setup('hubsettings');

$hubsettingsform = new hub_settings_form();

$fromform = $hubsettingsform->get_data();

echo $OUTPUT->header();

//check that the PHP xmlrpc extension is enabled
if (!extension_loaded('xmlrpc')) {
    $xmlrpcnotification = $OUTPUT->doc_link('admin/environment/php_extension/xmlrpc', '');
    $xmlrpcnotification .= get_string('xmlrpcdisabled', 'local_hub');
    echo $OUTPUT->notification($xmlrpcnotification);
    echo $OUTPUT->footer();
    die();
}

if (!empty($fromform)) {

    if ($fromform->privacy != HUBPRIVATE and !empty($fromform->password)) {
        throw new moodle_exception('cannotsetpasswordforpublichub', 'local_hub', new moodle_url('/local/hub/admin/settings.php'));
    }

    //Save settings
    set_config('name', $fromform->name, 'local_hub');
    set_config('hubenabled', $fromform->enabled, 'local_hub');
    set_config('description', $fromform->desc, 'local_hub');
    set_config('contactname', $fromform->contactname, 'local_hub');
    set_config('contactemail', $fromform->contactemail, 'local_hub');
    set_config('privacy', $fromform->privacy, 'local_hub');
    set_config('language', $fromform->lang, 'local_hub');
    set_config('password', $fromform->password, 'local_hub');

    //save the image
    if (empty($fromform->keepcurrentimage)) {
        $fs = get_file_storage();
        $files = $fs->get_area_files(get_context_instance(CONTEXT_USER, $USER->id)->id, 'user_draft', $fromform->hubimage);

        foreach ($files as $file) {
            if ($file->is_valid_image()) {

                $userdir = "hub/0/";

                //create directory if doesn't exist
                $directory = make_upload_directory($userdir);

                //save the image into the directory
                $fp = fopen($directory . '/hublogo', 'w');
                fwrite($fp, $file->get_content());
                fclose($fp);

                set_config('hublogo', true, 'local_hub');
                $updatelogo = true;
            }
        }
    }

    if (empty($updatelogo) and empty($fromform->keepcurrentimage)) {
        set_config('hublogo', false, 'local_hub');
    }

    //reload the form
    $hubsettingsform = new hub_settings_form();

    //display confirmation
    echo $OUTPUT->notification(get_string('settingsupdated', 'local_hub'), 'notifysuccess');
}

$hubsettingsform->display();
echo $OUTPUT->footer();

