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
$PAGE->set_title(get_string('assignedpagetitle', 'block_kronostmrequest'));
$PAGE->set_heading(get_string('assignedpageheading', 'block_kronostmrequest'));

require_login();

echo $OUTPUT->header();
if (kronostmrequest_has_role($USER->id)) {
    $userset = new stdClass();
    // Set userset name.
    $userset->solutionid = 'solution';
    $userset->wwwroot = $CFG->wwwroot;
    echo html_writer::tag('p', get_string('assignedpagedescription', 'block_kronostmrequest', $userset));
} else {
    print_error('nopermissiontoshow');
}
echo $OUTPUT->footer();
