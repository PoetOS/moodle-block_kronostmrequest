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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Kronos training manager request form.
 *
 * @package    mod_kronostmrequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */
class block_kronostmrequest_request_form extends moodleform {

    /**
     * Define request button and checkbox.
     */
    public function definition() {
        $mform =& $this->_form;
        $mform->addElement('checkbox', 'auth', get_string('authority', 'block_kronostmrequest'));
        $mform->setType('checkbox', PARAM_INT);
        $mform->setDefault('checkbox', 0);
        $mform->addElement('submit', 'submitbutton', get_string('submitrequest', 'block_kronostmrequest'));
    }
}
