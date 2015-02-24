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

require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->dirroot.'/auth/kronosportal/auth.php');
require_once($CFG->dirroot.'/auth/kronosportal/lib.php');

/**
 * Kronos training manager request block.
 *
 * @package    block_kronostmrequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

/**
 * Assign training manager roles to a user.
 *
 * @param int $userid User id of user to assig role to.
 * @return boolean True on has rule, false if user does not have training manager role.
 */
function kronostmrequest_role_assign($userid) {
    // Check if user has system role.
    if (!kronostmrequest_assign_system_role($userid)) {
        return false;
    }
    // Check if user has userset role.
    if (!kronostmrequest_assign_userset_role($userid)) {
        return false;
    }
    return true;
}

/**
 * Send notification of training manager roles being assigned to user.
 *
 * @param int $userid User id of user with training manager role.
 */
function kronostmrequest_send_notification($userid) {
    global $DB;
    $tmuser = $DB->get_record('user', array('id' => $userid));
    profile_load_data($tmuser);
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();
    if (!empty($tmuser->$solutionfield)) {
        $tmuser->solutionid = $tmuser->$solutionfield;
    } else {
        $tmuser->solutionid = get_string('missingsolutionid', 'block_kronostmrequest');
    }
    $subject = get_config('block_kronostmrequest', 'subject');
    $body = get_config('block_kronostmrequest', 'body');
    foreach ((array)$tmuser as $name => $value) {
        $body = preg_replace("/%%$name%%/", $value, $body);
    }
    $adminemail = get_config('block_kronostmrequest', 'adminuser');
    $touser = $DB->get_record('user', array('username' => $adminemail));
    if (empty($touser)) {
        return;
    }
    $eventdata = new stdClass();
    $eventdata->component         = 'block_kronostmrequest';
    $eventdata->name              = 'notifyassignment';
    $eventdata->userfrom          = $tmuser;
    $eventdata->userto            = $touser;
    $eventdata->subject           = $subject;
    $eventdata->fullmessage       = '';
    $eventdata->fullmessageformat = FORMAT_HTML;
    $eventdata->fullmessagehtml   = $body;
    $eventdata->smallmessage      = '';
    $eventdata->notification      = 1;
    message_send($eventdata);
}

/**
 * Check if user has training manager role assigned for both userset and system context.
 *
 * @param int $userid User id of user to check role assignment.
 * @return boolean True on has rule, false if user does not have training manager role.
 */
function kronostmrequest_has_role($userid) {
    // Check if user has system role.
    if (!kronostmrequest_has_system_role($userid)) {
        return false;
    }
    // Check if user has userset role.
    if (!kronostmrequest_has_userset_role($userid)) {
        return false;
    }
    return true;
}

/**
 * Check if user has training manager system role assigned.
 *
 * @param int $userid User id of user to check role assignment.
 * @return boolean True on has rule, false if user does not have training manager role.
 */
function kronostmrequest_has_system_role($userid) {
    // Check if user has system role.
    $context = context_system::instance();
    $systemrole = get_config('block_kronostmrequest', 'systemrole');
    $roles = get_user_roles($context, $userid);
    foreach ($roles as $role) {
        if ($role->roleid == $systemrole) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has training manager userset role assigned.
 *
 * @param int $userid User id of user to check role assignment.
 * @return boolean True on has rule, false if user does not have training manager role.
 */
function kronostmrequest_has_userset_role($userid) {
    global $DB;
    // Check if user has userset role.
    $user = $DB->get_record('user', array('id' => $userid));
    profile_load_data($user);
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();
    $auth = get_auth_plugin('kronosportal');
    if (empty($user->$solutionfield)) {
        return false;
    }
    $contextidname = $auth->userset_solutionid_exists($user->$solutionfield);
    if (empty($contextidname)) {
        return false;
    }
    $context = context::instance_by_id($contextidname->id);
    $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
    if (empty($usersetroleid)) {
        return false;
    }
    $roles = get_user_roles($context, $userid);
    foreach ($roles as $role) {
        if ($role->roleid == $usersetroleid) {
            return true;
        }
    }
    return false;
}

/**
 * Assign userset role to userset that user belongs to.
 *
 * @param int $userid User id of user to assign role to.
 * @return boolean True on success, false on failure.
 */
function kronostmrequest_assign_userset_role($userid) {
    global $DB;
    // Load custom feild values.
    $user = $DB->get_record('user', array('id' => $userid));
    profile_load_data($user);
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();
    $auth = get_auth_plugin('kronosportal');
    if (empty($user->$solutionfield)) {
        return false;
    }
    $contextidname = $auth->userset_solutionid_exists($user->$solutionfield);
    if (empty($contextidname)) {
        return false;
    }
    $context = context::instance_by_id($contextidname->id);
    $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
    if (empty($usersetroleid)) {
        return false;
    }
    role_assign($usersetroleid, $userid, $context);
    return true;
}

/**
 * Assign system role to user.
 *
 * @param int $userid User id of user to assign role to.
 * @return boolean True on success, false on failure.
 */
function kronostmrequest_assign_system_role($userid) {
    $context = context_system::instance();
    $systemrole = get_config('block_kronostmrequest', 'systemrole');
    if (empty($systemrole)) {
        return false;
    }
    role_assign($systemrole, $userid, $context);
    return true;
}
