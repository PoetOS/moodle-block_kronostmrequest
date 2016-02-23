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
 * @package    block_kronoshtml
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2014 Remote Learner.net Inc http://www.remote-learner.net
 */
$string['block_kronostmrequest'] = 'Kronos training manager request';
$string['pluginname'] = 'Kronos training manager request';
$string['newkronostmrequest'] = 'Request training manager role';
$string['configtitle'] = 'Block title';
$string['configcontent'] = 'Content';
$string['kronostmrequest:makerequest'] = 'Make request to be assigned training manager role';
$string['kronostmrequest:addinstance'] = 'Add block instance';
$string['systemrole'] = 'Select training manager system role';
$string['systemrole_desc'] = 'Select the training manage role to be assigned at the system context level.';
$string['usersetrole'] = 'Select training manager userset role';
$string['usersetrole_desc'] = 'Select the training manage role to be assigned at the userset context level.';
$string['adminuser'] = 'Adminstrative user';
$string['adminuser_desc'] = 'User which notifications are sent to when a training manager role is assigned.';
$string['subject'] = 'Subject';
$string['subject_desc'] = 'Subject of message';
$string['body'] = 'Body';
$string['body_desc'] = 'Body of notification message to send.<br><b>The following placeholders are available:</b><ul><li><b>%%sitename%%</b>: The site\'s name.</li><li><b>%%siteurl%%</b>: A URL to the site</li><li><b>%%username%%</b>: The user\'s username.</li><li><b>%%firstname%%</b>: The user\'s first name.</li><li><b>%%lastname%%</b>: The user\'s last name.</li><li><b>%%email%%</b>: The user\'s email address.</li><li><b>%%solutionid%%</b>: The user\'s solutionid.</li></ul>';
$string['bodydefault'] = '<b>The following user has been assigned the training manager role:</b><ul><li><b>Username:</b> %%username%%</li><li><b>Name:</b> %%firstname%% %%lastname%%</li><li><b>Email:</b> %%email%%</li><li><b>Solutionid:</b> %%solutionid%%</b></li></ul>';
$string['authority'] = 'Yes, I have the authority to make this request';
$string['newblockcontent'] = '<a href="{$a->wwwroot}/blocks/kronostmrequest/request.php">Request training manager role</a>';
$string['requestpagetitle'] = 'Request training manager role';
$string['requestpageheading'] = 'Request training manager role';
$string['submitrequest'] = 'Submit request';
$string['requestrole'] = 'Request training manager role';
$string['requestroleinstructions'] = 'When you request the training manager role you will be given access to upload new users and enrol them into courses.';
$string['requestroleinstructionsconfirm'] = 'To assign the training manager role you must confirm you have the authority to do so.';
$string['assignedpagetitle'] = 'Training manager role assigned';
$string['assignedpageheading'] = 'Training manager role assigned';
$string['assignedpagetitle'] = 'Training manager role assigned';
$string['assignedpagedescription'] = 'You have been assigned the training manager role for the solution id {$a->solutionid}.<br> As a training manager you now have the ability to <a href="{$a->wwwroot}/blocks/importqueue/importusers.php">import new users</a> and enrol students in courses.';
$string['kronostmrequest:notifyassignment'] = 'Training manager role assigned notification';
$string['defaultsubject'] = 'New training manager assigned';
$string['roleassigned'] = 'The training manager role has been assigned to you.';
$string['missingsolutionid'] = 'Your account is missing a solution id, please contact customer service';
$string['restrictby'] = 'Field to restrict by';
$string['restrictby_help'] = 'Select field to restrict by and enter value it must be equal to for training manager block to be visible.';
$string['no_field_selected'] = 'No field selected';
$string['restrictbyvalue'] = 'Restricted value';
$string['restrictbyvalue_help'] = 'Set the value which the user profile field is required to be equal to for the training manager block to display.';
$string['validation_error_valid'] = 'The training manager configuration is valid.';
$string['validation_error_nosystemrole'] = 'The user is not assigned a system role';
$string['validation_error_nousersolutionid'] = 'The user does not have a solution id set';
$string['validation_error_nosolutionusersets'] = 'The there is no solution userset found for the user';
$string['validation_error_morethanonesolutionuserset'] = 'There is more than one solution userset found for the user';
$string['validation_error_invalid'] = 'The training manager configuration is invalid';
$string['canassign_error_systemrole'] = 'There is a problem with your configuration: A system role is incorrectly assigned';
$string['canassign_error_nousersolutionid'] = 'There is a problem with your configuration: No user solution id is set';
$string['canassign_error_nosolutionusersets'] = 'There is a problem with your configuration: No solution userset is found';
$string['canassign_error_morethanonesolutionuserset'] = 'There is a problem with your configuration: More than one solution userset has been found';
$string['canassign_error_solutionusersetroleassigned'] = 'There is a problem with your configuration: A solution userset role is currently assigned';
$string['canassign_error_guestuser'] = 'Guest users cannot request a training manager role';
