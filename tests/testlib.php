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

/**
 * Tests for Kronos training manager request block.
 *
 * @package    block_kronostmrequest
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2015 Remote Learner.net Inc http://www.remote-learner.net
 */

/**
 * Test kronostmrequest assignment functions.
 */
abstract class block_kronostmrequest_test extends advanced_testcase {

    /**
     * @var array $users Array of custom field ids.
     */
    protected $customfields = array();
    /**
     * @var object $user User object.
     */
    protected $user = 0;
    /**
     * @var int $roleid Role id.
     */
    protected $roleid = 0;
    /**
     * @var int $usersetroleid Role id.
     */
    protected $usersetroleid = 0;
    /**
     * @var array $usersets Array of userset ids.
     */
    protected $usersets = array();
    /**
     * @var int $parentusersetid Id of Audience userset.
     */
    protected $parentusersetid = 0;

    /**
     * Setup custom field.
     */
    public function setupcustomfield() {
        global $DB;
        // Add a custom field customerid of text type.
        $this->customfields['customerid'] = $DB->insert_record('user_info_field', array(
            'shortname' => 'customerid',
            'name' => 'Description of customerid',
            'categoryid' => 1,
            'datatype' => 'text',
            'descriptionformat' => 1,
            'visible' => 2,
            'signup' => 0,
            'defaultdata' => ''
        ));
        $this->customfields['learningpath'] = $DB->insert_record('user_info_field', array(
            'shortname' => 'learningpath',
            'name' => 'Description of learning path',
            'categoryid' => 1,
            'datatype' => 'text',
            'descriptionformat' => 1,
            'visible' => 2,
            'signup' => 0,
            'defaultdata' => ''
        ));
    }

    /**
     * Set custom field data.
     *
     * @param string $field Field to set data.
     * @param int $userid User id to set the field on.
     * @param string $value Value to set field to.
     */
    public function setcustomfielddata($field, $userid, $value) {
        global $DB;
        // Set up data.
        $user = $DB->get_record('user', array('id' => $userid));
        $field = "profile_field_".$field;
        $user->$field = $value;
        // Save profile field data with Moodle core functions.
        profile_save_data($user);
        // Save user data to trigger event.
        user_update_user($user, false, true);
    }

    /**
     * Enable auth plugin.
     */
    protected function enable_plugin() {
        $auths = get_enabled_auth_plugins(true);
        if (! in_array('kronosportal', $auths)) {
            $auths [] = 'kronosportal';
        }
        set_config('auth', implode(',', $auths));
    }

    /**
     * Setup training manager.
     */
    public function setup() {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot.'/local/elisprogram/lib/setup.php');
        require_once(elispm::lib('data/userset.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usermoodle.class.php'));
        require_once($CFG->dirroot.'/blocks/kronostmrequest/lib.php');

        $this->usersets = array();

        $this->resetAfterTest();
        $this->enable_plugin();

        $this->setupcustomfield();

        $this->users = array();
        // Valid solution id.
        $this->user = $this->getDataGenerator()->create_user();
        $this->setcustomfielddata('customerid', $this->user->id, 'testsolutionid');
        $this->setcustomfielddata('learningpath', $this->user->id, 'testlearningdata');

        // Setup custom userset field.
        // Create custom field.
        $fieldcat = new field_category;
        $fieldcat->name = 'Kronos';
        $fieldcat->save();

        $categorycontext = new field_category_contextlevel();
        $categorycontext->categoryid = $fieldcat->id;
        $categorycontext->contextlevel = CONTEXT_ELIS_USERSET;
        $categorycontext->save();

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'extension';
        $field->name = 'Extention';
        $field->datatype = 'int';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->customfields['userset_extension'] = $field->id;

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'expiry';
        $field->name = 'Expiry';
        $field->datatype = 'int';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->customfields['userset_expiry'] = $field->id;

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'customerid';
        $field->name = 'SolutionID';
        $field->datatype = 'char';
        $field->save();

        $this->customfields['userset_solutionid'] = $field->id;

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        // Create parent userset.
        $userset = array(
            'name' => 'Customer Audience',
            'display' => 'test userset description',
        );

        $us = new userset($userset);
        $us->save();

        $this->parentusersetid = $us->id;

        // Create valid solutionid userset.
        $userset = array(
            'name' => 'testuserset',
            'display' => 'test userset description',
            'field_customerid' => 'testsolutionid',
            'field_expiry' => time() + 3600,
            'field_extension' => time() + 3600,
            'parent' => $us->id
        );

        $usvalid = new userset();
        $usvalid->set_from_data((object)$userset);
        $usvalid->save();

        $this->usersets['testsolutionid'] = $usvalid->id;

        // Create expired solutionid userset.
        $userset = array(
            'name' => 'expiredsolution name',
            'display' => 'test userset description',
            'field_customerid' => 'expiredsolution',
            'field_expiry' => time() - 3600,
            'field_extension' => time() - 3600,
            'parent' => $us->id
        );

        $usinvalid = new userset();
        $usinvalid->set_from_data((object)$userset);
        $usinvalid->save();

        $this->usersets['expiredsolution'] = $usinvalid->id;

        // Create solutionid with extension.
        $userset = array(
            'name' => 'solutionextension name',
            'display' => 'test userset description',
            'field_customerid' => 'extensionsolution',
            'field_expiry' => time() - 3600,
            'field_extension' => time() + 3600,
            'parent' => $us->id
        );

        $usinvalid = new userset();
        $usinvalid->set_from_data((object)$userset);
        $usinvalid->save();

        $this->usersets['extensionsolution'] = $usinvalid->id;

        // Setup configuration.
        set_config('expiry', $this->customfields['userset_expiry'], 'auth_kronosportal');
        set_config('extension', $this->customfields['userset_extension'], 'auth_kronosportal');
        set_config('solutionid', $this->customfields['userset_solutionid'], 'auth_kronosportal');
        set_config('user_field_solutionid', $this->customfields['customerid'], 'auth_kronosportal');

        $this->roleid = create_role('Training manager role', 'trainingmanager', '');
        $this->usersetroleid = create_role('Training manager userset role', 'trainingmanageruserset', '');
    }

    /**
     * Create solution userset.
     * @param string $solution Name of solution.
     * @param string $solutionid Solution id string.
     * @return object Userset object.
     */
    protected function create_solution_userset($solution, $solutionid) {
        // Create valid solutionid userset.
        $userset = array(
            'name' => $solution,
            'display' => $solution,
            'field_customerid' => $solutionid,
            'field_expiry' => time() + 3600,
            'field_extension' => time() + 3600,
            'parent' => $this->parentusersetid
        );

        $usvalid = new userset();
        $usvalid->set_from_data((object)$userset);
        $usvalid->save();
        return $usvalid;
    }

}
