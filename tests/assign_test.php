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
class block_kronostmrequest_assign_testcase extends advanced_testcase {

    /**
     * @var array $users Array of custom field ids.
     */
    private $customfields = null;
    /**
     * @var object $user User object.
     */
    private $user = null;
    /**
     * @var int $roleid Role id.
     */
    private $roleid = null;
    /**
     * @var int $usersetroleid Role id.
     */
    private $usersetroleid = null;

    /**
     * Setup custom field.
     */
    public function setupcustomfield() {
        global $DB;
        // Add a custom field customerid of text type.
        $this->customfields = array();
        $this->customfields['customerid'] = $DB->insert_record('user_info_field', array(
                'shortname' => 'customerid', 'name' => 'Description of customerid', 'categoryid' => 1,
                'datatype' => 'text'));
        $this->customfields['learningpath'] = $DB->insert_record('user_info_field', array(
                'shortname' => 'learningpath', 'name' => 'Description of learning path', 'categoryid' => 1,
                'datatype' => 'text'));
    }

    /**
     * Set custom field data.
     *
     * @param int $fieldid Id of field to set data.
     * @param int $userid User id to set the field on.
     * @param string $value Value to set field to.
     */
    public function setcustomfielddata($fieldid, $userid, $value) {
        global $DB;
        // Set up data.
        $record = new stdClass;
        $record->fieldid = $fieldid;
        $record->userid = $userid;
        $record->data = $value;
        $DB->insert_record('user_info_data', $record);
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

        $this->resetAfterTest();
        $this->enable_plugin();

        $this->setupcustomfield();

        $this->users = array();
        // Valid solution id.
        $this->user = $this->getDataGenerator()->create_user();
        $this->setcustomfielddata($this->customfields['customerid'], $this->user->id, 'testsolutionid');
        $this->setcustomfielddata($this->customfields['learningpath'], $this->user->id, 'testlearningdata');

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

        // Setup configuration.
        set_config('expiry', $this->customfields['userset_expiry'], 'auth_kronosportal');
        set_config('extension', $this->customfields['userset_extension'], 'auth_kronosportal');
        set_config('solutionid', $this->customfields['userset_solutionid'], 'auth_kronosportal');
        set_config('user_field_solutionid', $this->customfields['customerid'], 'auth_kronosportal');

        $this->roleid = create_role('Training manager role', 'trainingmanager', '');
        $this->usersetroleid = create_role('Training manager userset role', 'trainingmanageruserset', '');
    }

    /**
     * Test has system role.
     */
    public function test_has_system_role() {
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
        $context = context_system::instance();
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        role_assign($this->roleid, $this->user->id, $context);
        $this->assertTrue(kronostmrequest_has_system_role($this->user->id));
        role_unassign($this->roleid, $this->user->id, $context->id);
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
    }

    /**
     * Test has userset role.
     */
    public function test_has_userset_role() {
        global $DB;
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
        $auth = get_auth_plugin('kronosportal');
        $contextidname = $auth->userset_solutionid_exists('testsolutionid');
        $context = context::instance_by_id($contextidname->id);
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        role_assign($this->usersetroleid, $this->user->id, $context);
        $this->assertTrue(kronostmrequest_has_userset_role($this->user->id));
        role_unassign($this->usersetroleid, $this->user->id, $context->id);
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
    }

    /**
     * Test assign userset role.
     */
    public function test_assign_userset_role() {
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
        $this->assertTrue(kronostmrequest_assign_userset_role($this->user->id));
        $this->assertTrue(kronostmrequest_has_userset_role($this->user->id));
    }

    /**
     * Test assign system role.
     */
    public function test_assign_system_role() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
        $this->assertTrue(kronostmrequest_assign_system_role($this->user->id));
        $this->assertTrue(kronostmrequest_has_system_role($this->user->id));
    }

    /**
     * Test has role.
     */
    public function test_has_role() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertFalse(kronostmrequest_has_role($this->user->id));
        $this->assertTrue(kronostmrequest_assign_system_role($this->user->id));
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
        $this->assertFalse(kronostmrequest_has_role($this->user->id));
        $this->assertTrue(kronostmrequest_assign_userset_role($this->user->id));
        $this->assertTrue(kronostmrequest_has_role($this->user->id));
    }

    /**
     * Test assign role.
     */
    public function test_role_assign() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertFalse(kronostmrequest_has_role($this->user->id));
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertTrue(kronostmrequest_has_role($this->user->id));
    }
}
