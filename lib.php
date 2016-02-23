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
 * @copyright  (C) 2016 Remote Learner.net Inc http://www.remote-learner.net
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
    $usersetsolutions = kronostmrequest_get_solution_usersets_roles($userid);
    if (!empty($usersetsolutions)) {
        // A userset role is already assigned.
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

/**
 * Unassign system and all solution userset roles.
 *
 * @param int $userid User id of user to assign role to.
 */
function kronostmrequest_unassign_all_roles($userid) {
    if (kronostmrequest_has_system_role($userid)) {
        kronostmrequest_unassign_system_role($userid);
    }
    kronostmrequest_unassign_all_solutionuserset_roles($userid);
}

/**
 * Unassign system roles.
 *
 * @param int $userid User id of user to assign role to.
 * @return boolean True on success, false on failure.
 */
function kronostmrequest_unassign_system_role($userid) {
    $context = context_system::instance();
    $systemrole = get_config('block_kronostmrequest', 'systemrole');
    if (empty($systemrole)) {
        return false;
    }
    role_unassign($systemrole, $userid, $context->id);
    return true;
}

/**
 * Unassign all userset solution roles.
 *
 * @param int $userid User id of user to assign role to.
 * @return boolean True on success, false on failure.
 */
function kronostmrequest_unassign_all_solutionuserset_roles($userid) {
    $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
    $usersetsolutions = kronostmrequest_get_solution_usersets_roles($userid);
    foreach ($usersetsolutions as $userset) {
        role_unassign($usersetroleid, $userid, $userset->contextid);
    }
    return true;
}

/**
 * Unassign userset solution role.
 *
 * @param int $userid User id of user to assign role to.
 * @return boolean True on success, false on failure.
 */
function kronostmrequest_unassign_userset_role($userid, $usersetcontextid) {
    $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
    role_unassign($usersetroleid, $userid, $usersetcontextid);
    return true;
}

/**
 * This function searches for a User Sets with a matching Solution ID. The User Set Solution ID needs to be defined as
 * a custom field in the User Set conext.
 * @param int $usersolutionid The user's Solution ID.
 * @return array Array of objects ('id' -> Context id, 'name' -> User Set name, 'usersetid' -> User Set id).  Otherwise false.
 */
function kronostmrequest_get_solution_usersets($solutionid) {
    global $DB;
    $cleansolutionid = clean_param(trim($solutionid), PARAM_ALPHANUMEXT);
    $config = get_config('auth_kronosportal');
    $sql = "SELECT ctx.id contextid, uset.name, uset.id AS usersetid
              FROM {local_elisprogram_uset} uset
              JOIN {local_eliscore_field_clevels} fldctx on fldctx.fieldid = ?
              JOIN {context} ctx ON ctx.instanceid = uset.id AND ctx.contextlevel = fldctx.contextlevel
              JOIN {local_eliscore_fld_data_char} fldchar ON fldchar.contextid = ctx.id AND fldchar.fieldid = ?
             WHERE uset.depth = 2
                   AND fldchar.data = ?";
    return $DB->get_records_sql($sql, array($config->solutionid, $config->solutionid, $cleansolutionid));
}

/**
 * This function searches for training manager roles assigned a User Sets Solution.
 * @param int $userid The user ID.
 * @return array Array of objects ('id' -> Context id, 'name' -> User Set name, 'usersetid' -> User Set id).  Otherwise false.
 */
function kronostmrequest_get_solution_usersets_roles($userid) {
    global $DB;
    $sql = "SELECT c.id contextid, c.instanceid usersetid, a.roleid
              FROM {context} c,
                   {role_assignments} a
             WHERE a.roleid = ?
                   AND a.contextid = c.id
                   AND c.contextlevel = ?
                   AND a.userid = ?";
    $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
    return $DB->get_records_sql($sql, array($usersetroleid, CONTEXT_ELIS_USERSET, $userid));
}

/**
 * Check if a training manager roles are valid for both userset and system context.
 *
 * @param int $userid User id of user to check role assignment.
 * @return string "valid" when valid or "nosystemrole", "nousersolutionid", "nosolutionusersets", "morethanonesolutionuserset",
 *                "invalidsolutionusersetrole", "nosolutionusersetroles", "morethanonesolutionuserset" and "invalid".
 */
function kronostmrequest_validate_role($userid) {
    global $DB;
    if (!kronostmrequest_has_system_role($userid)) {
        // There is no system role assigned.
        return "nosystemrole";
    }

    // Retrieve solution id from custom user profile field.
    $user = $DB->get_record('user', array('id' => $userid));
    profile_load_data($user);
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();

    if (empty($user->$solutionfield)) {
        // There is no solution userset id, the configuration is invalid.
        return "nousersolutionid";
    }

    $solutionid = $user->$solutionfield;
    // Validate userset solution configuration.
    $usersetsolutions = kronostmrequest_get_solution_usersets($solutionid);
    if (empty($usersetsolutions)) {
        // There is no valid userset solutions.
        return "nosolutionusersets";
    }

    if (count($usersetsolutions) != 1) {
        // There is more than one userset solution or none, this is an invalid configuration. Unassign all roles.
        return "morethanonesolutionuserset";
    }

    // Retrieve valid userset id.
    $userset = array_pop($usersetsolutions);
    $solutionusersetid = $userset->usersetid;

    // Retrieve what roles are assigned to training manager and solutions usersets.
    $usersetsolutions = kronostmrequest_get_solution_usersets_roles($userid);
    foreach ($usersetsolutions as $usersetsolution) {
        if ($usersetsolution->usersetid != $solutionusersetid) {
            // Invalid role assignment.
            return "invalidsolutionusersetrole";
        }
    }

    // After unassigning invalid roles, ensure only one role is assigned.
    $usersetsolutions = kronostmrequest_get_solution_usersets_roles($userid);
    if (empty($usersetsolutions) || count($usersetsolutions) == 0) {
        // No solution userset roles are assigned.
        return "nosolutionusersetroles";
    }

    if (count($usersetsolutions) > 1) {
        // More than one solution userset is assigned.
        return "morethanonesolutionuserset";
    }

    $userset = array_pop($usersetsolutions);
    if ($userset->usersetid == $solutionusersetid) {
        // User has training manager system role and one solution user set role assigned. This configuration is valid.
        return "valid";
    }

    // Valid configuration was not found.
    return "invalid";
}

/**
 * Check if a training manager role can be assigned.
 *
 * @param int $userid User id of user to check role assignment.
 * @return string "valid" when valid or "systemrole", "nousersolutionid", "nosolutionusersets", "morethanonesolutionuserset",
 *                "solutionusersetroleassigned".
 */
function kronostmrequest_can_assign($userid) {
    global $DB;
    if (isguestuser($userid)) {
        // User is a guest user.
        return "guestuser";
    }
    if (kronostmrequest_has_system_role($userid)) {
        // There is a system role assigned.
        return "systemrole";
    }

    // Retrieve solution id from custom user profile field.
    $user = $DB->get_record('user', array('id' => $userid));
    profile_load_data($user);
    $solutionfield = "profile_field_".kronosportal_get_solutionfield();

    if (empty($user->$solutionfield)) {
        // There is no solution userset id, the configuration is invalid.
        return "nousersolutionid";
    }

    $solutionid = $user->$solutionfield;
    // Validate userset solution configuration.
    $usersetsolutions = kronostmrequest_get_solution_usersets($solutionid);
    if (empty($usersetsolutions)) {
        // There is no valid userset solutions.
        return "nosolutionusersets";
    }

    if (count($usersetsolutions) != 1) {
        // There is more than one userset solution or none, this is an invalid configuration.
        return "morethanonesolutionuserset";
    }

    // Retrieve what roles are assigned to training manager and solutions usersets.
    $usersetsolutionroles = kronostmrequest_get_solution_usersets_roles($userid);
    if (!empty($usersetsolutionroles)) {
        // Invalid role assignment.
        return "solutionusersetroleassigned";
    }

    return "valid";
}

/**
 * Unassign any invalid solution usersets, these solution usersets would be unassigned if it does not have a matching solution id
 * to the solution id stored in the custom profile field.
 *
 * @param int $userid User id of user to check for invalid usersets.
 * @return boolean True on successfully processing, false on error.
 */
function kronostmrequest_unassign_invalid_usersets($userid) {
    global $DB;
    // Retrieve solution id.
    $user = $DB->get_record('user', array('id' => $userid));
    if (empty($user)) {
        return false;
    }
    profile_load_data($user);

    $solutionfield = "profile_field_".kronosportal_get_solutionfield();

    if (empty($user->$solutionfield)) {
        // There is no solution userset id, the configuration is invalid.
        kronostmrequest_unassign_all_roles($userid);
        return false;
    }

    // Unassign usersets that are not valid.
    $solutionid = $user->$solutionfield;
    $usersetsolutions = kronostmrequest_get_solution_usersets($solutionid);
    // No solution userset or more than one should result in no roles being assigned.
    if (empty($usersetsolutions) || count($usersetsolutions) > 1) {
        kronostmrequest_unassign_all_roles($userid);
        return false;
    }

    // Check userset roles.
    $userset = array_pop($usersetsolutions);
    $usersetid = $userset->usersetid;
    $usersetroles = kronostmrequest_get_solution_usersets_roles($userid);
    foreach ($usersetroles as $role) {
        if ($usersetid != $role->usersetid) {
            kronostmrequest_unassign_userset_role($userid, $role->contextid);
        }
    }
    return true;
}


/**
 * Handler for role unassign to validate training manager roles.
 *
 * @param object $eventdata Event data.
 * @return boolean True on event handled successfully.
 */
function kronostmrequest_notify_role_unassigned_handler($eventdata) {
    if (defined('KRONOS_PHPUNIT_SCRIPT') && KRONOS_PHPUNIT_SCRIPT) {
        return true;
    }

    if ((empty($eventdata->relateduserid) || empty($eventdata->contextid) || empty($eventdata->objectid)) && method_exists($eventdata, 'trigger')) {
        $eventdata = (object)$eventdata->other;
    }

    $userid = $eventdata->relateduserid;
    $roleid = $eventdata->objectid;

    $systemrole = get_config('block_kronostmrequest', 'systemrole');
    $usersetrole = get_config('block_kronostmrequest', 'usersetrole');

    // Ignore all othe roles.
    if (!in_array($roleid, array($systemrole, $usersetrole))) {
        return true;
    }

    // If unassigning system role than all the training manager roles are unassigned.
    if ($eventdata->objectid == $systemrole) {
        kronostmrequest_unassign_all_roles($eventdata->relateduserid);
        return true;
    }

    // Unassign up any invalid usersets.
    if (!kronostmrequest_unassign_invalid_usersets($eventdata->relateduserid)) {
        // There was an issue with the configuration, unassign all.
        kronostmrequest_unassign_all_roles($eventdata->relateduserid);
        return true;
    }
    // Has a role, validate.
    if (kronostmrequest_has_system_role($userid) && kronostmrequest_validate_role($userid) != 'valid') {
        // Configuration is invalid, unassign all roles.
        kronostmrequest_unassign_all_roles($userid);
    }
    return true;
}

/**
 * Handler for profile update to validate training manager roles.
 *
 * @param object $eventdata Event data.
 * @return boolean True on event handled successfully.
 */
function kronostmrequest_notify_user_updated_handler($eventdata) {
    if (defined('KRONOS_PHPUNIT_SCRIPT') && KRONOS_PHPUNIT_SCRIPT) {
        return true;
    }
    $userid = $eventdata->objectid;
    $roles = kronostmrequest_get_solution_usersets_roles($userid);
    // Check if user has system or userset role, if so they may be a training manager.
    if (!(kronostmrequest_has_system_role($userid) || !empty($roles))) {
        // Not a training manager.
        return true;
    }

    // Has a role, validate.
    if (kronostmrequest_validate_role($userid) != 'valid') {
        // Configuration is invalid, unassign all roles.
        kronostmrequest_unassign_all_roles($userid);
    }
    return true;
}

/**
 * Handler for role assign to validate training manager roles.
 * If a system role is being assigned, ensure any usersets that are assigned are valid.
 * If a userset role is being assigned, use full validation check.
 * The order of this check is dictated by kronostmrequest_role_assign, an event for the system role will be
 * fired before the userset role is assigned.
 *
 * @param object $eventdata Event data.
 * @return boolean True on event handled successfully.
 */
function kronostmrequest_notify_role_assigned_handler($eventdata) {
    global $DB;
    if (defined('KRONOS_PHPUNIT_SCRIPT') && KRONOS_PHPUNIT_SCRIPT) {
        return true;
    }

    if ((empty($eventdata->relateduserid) || empty($eventdata->contextid) || empty($eventdata->objectid)) && method_exists($eventdata, 'trigger')) {
        $eventdata = (object)$eventdata->other;
    }

    $systemrole = get_config('block_kronostmrequest', 'systemrole');
    $usersetrole = get_config('block_kronostmrequest', 'usersetrole');
    $userid = $eventdata->relateduserid;

    if ($eventdata->objectid == $systemrole || $eventdata->objectid == $usersetrole) {
        // Unassign up any invalid usersets.
        if (!kronostmrequest_unassign_invalid_usersets($eventdata->relateduserid)) {
            // There was an issue with the configuration, unassign all.
            kronostmrequest_unassign_all_roles($eventdata->relateduserid);
            return true;
        }
    }

    // If system role is being assigned than the training manager role may not be complete yet.
    if ($eventdata->objectid == $systemrole) {
        return true;
    }

    // If a userset role is being checked than the training manager configuration should be complete.
    if ($eventdata->objectid == $usersetrole) {
        // If attempts to clean up invalid solution userset role assigments have failed to create a valid assigment, unassign all roles.
        if (kronostmrequest_validate_role($userid) != 'valid') {
            // Configuration is invalid, unassign all roles.
            kronostmrequest_unassign_all_roles($userid);
        }
    }
    return true;
}
