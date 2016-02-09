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
 * Kronos training manager request page.
 *
 * @package    mod_kronostmrequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/blocks/kronostmrequest/lib.php');
require_once($CFG->dirroot.'/blocks/kronostmrequest/request_form.php');
require_login();

$PAGE->set_pagelayout('admin');

$action = optional_param('action', 'request', PARAM_TEXT);
$auth = optional_param('action', 0, PARAM_INT);
$PAGE->set_url('/blocks/kronostmrequest/request.php');

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('requestpagetitle', 'block_kronostmrequest'));
$PAGE->set_heading(get_string('requestpageheading', 'block_kronostmrequest'));

$canassign = kronostmrequest_can_assign($USER->id);
// If you cannot assign display an error.
if ($canassign == "valid") {
    $requestform = new block_kronostmrequest_request_form($PAGE->url);
    if ($data = $requestform->get_data()) {
        if (empty($data->auth)) {
            // User has NOT confirmed they have authority to request training manager role.
            echo $OUTPUT->header();
            echo html_writer::tag('h3', get_string('requestrole', 'block_kronostmrequest'));
            echo html_writer::tag('p', get_string('requestroleinstructions', 'block_kronostmrequest'));
            echo html_writer::tag('p', get_string('requestroleinstructionsconfirm', 'block_kronostmrequest'), array('style' => 'color: red'));
            $requestform->display();
        } else {
            // User has confirmed they have authority to request training manager role.
            kronostmrequest_role_assign($USER->id);
            $rolevalid = kronostmrequest_validate_role($USER->id);
            if ($rolevalid == 'valid') {
                kronostmrequest_send_notification($USER->id);
                redirect($CFG->wwwroot.'/blocks/kronostmrequest/assigned.php');
            } else {
                echo $OUTPUT->header();
                $data = new stdClass();
                $data->wwwroot = $CFG->wwwroot;
                echo html_writer::tag('h3', get_string('validation_error_'.$rolevalid, 'block_kronostmrequest', $data));
            }
        }
    } else {
        echo $OUTPUT->header();
        echo html_writer::tag('h3', get_string('requestrole', 'block_kronostmrequest'));
        echo html_writer::tag('p', get_string('requestroleinstructions', 'block_kronostmrequest'));
        $requestform->display();
    }
} else {
    // User is already assigned the training manager role.
    $rolevalid = kronostmrequest_validate_role($USER->id);
    if ($rolevalid == "valid") {
        echo $OUTPUT->header();
        echo html_writer::tag('h3', get_string('roleassigned', 'block_kronostmrequest'));
    } else {
        echo $OUTPUT->header();
        $data = new stdClass();
        $data->wwwroot = $CFG->wwwroot;
        echo html_writer::tag('h3', get_string('canassign_error_'.$canassign, 'block_kronostmrequest', $data));
    }
}
echo $OUTPUT->footer();