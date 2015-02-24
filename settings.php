<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Kronos training manager request block.
 *
 * @package    block_kronostmrequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $result = $DB->get_recordset_select('role', null);
    $systemroles = array();
    foreach ($result as $role) {
        $systemroles[$role->id] = format_string(empty($role->name) ? $role->shortname : $role->shortname.' - '.$role->name);
    }
    $langfile = 'block_kronostmrequest';
    $title = get_string('systemrole', $langfile);
    $desc = get_string('systemrole_desc', $langfile);
    $setting = new \admin_setting_configselect('systemrole', $title, $desc, null, $systemroles);
    $setting->plugin = $langfile;
    $settings->add($setting);

    $title = get_string('usersetrole', $langfile);
    $desc = get_string('usersetrole_desc', $langfile);
    $setting = new \admin_setting_configselect('usersetrole', $title, $desc, null, $systemroles);
    $setting->plugin = $langfile;
    $settings->add($setting);

    $title = get_string('adminuser', $langfile);
    $desc = get_string('adminuser_desc', $langfile);
    $setting = new \admin_setting_configtext('adminuser', $title, $desc, $USER->username, PARAM_USERNAME);
    $setting->plugin = $langfile;
    $settings->add($setting);

    $title = get_string('subject', $langfile);
    $desc = get_string('subject_desc', $langfile);
    $subject = get_string('defaultsubject', 'block_kronostmrequest');
    $setting = new \admin_setting_configtext('subject', $title, $desc, $subject, PARAM_TEXT);
    $setting->plugin = $langfile;
    $settings->add($setting);

    $title = get_string('body', $langfile);
    $desc = get_string('body_desc', $langfile);
    $bodydefault = get_string('bodydefault', 'block_kronostmrequest');
    $setting = new \admin_setting_confightmleditor('body', $title, $desc, $bodydefault,
            PARAM_RAW, '60', '20');
    $setting->plugin = $langfile;
    $settings->add($setting);
}
