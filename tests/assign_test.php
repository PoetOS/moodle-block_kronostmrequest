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
     * @var array $usersets Array of userset ids.
     */
    private $usersets = null;
    /**
     * @var int $parentusersetid Id of Audience userset.
     */
    private $parentusersetid = null;

    /**
     * Setup custom field.
     */
    public function setupcustomfield() {
        global $DB;
        // Add a custom field customerid of text type.
        $this->customfields = array();
        $this->customfields['customerid'] = $DB->insert_record('user_info_field', array(
                'shortname' => 'customerid', 'name' => 'Description of customerid', 'categoryid' => 1,
                'datatype' => 'text', 'descriptionformat' => 1, 'visible' => 2, 'signup' => 0, 'defaultdata' => ''));
        $this->customfields['learningpath'] = $DB->insert_record('user_info_field', array(
                'shortname' => 'learningpath', 'name' => 'Description of learning path', 'categoryid' => 1,
                'datatype' => 'text', 'descriptionformat' => 1, 'visible' => 2, 'signup' => 0, 'defaultdata' => ''));
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

    /**
     * Test kronostmrequest_get_solution_usersets_roles.
     */
    public function test_kronostmrequest_get_solution_usersets_roles() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $auth = get_auth_plugin('kronosportal');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolution);
        // Assign a second role to a userset solution.
        $contextidname = $auth->userset_solutionid_exists('extensionsolution');
        $context = \local_elisprogram\context\userset::instance($contextidname->usersetid);
        role_assign($this->usersetroleid, $this->user->id, $context);
        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(2, $usersetsolutions);
        // Build list of userset id's that should be assigned.
        $usersets = array($this->usersets['testsolutionid'], $this->usersets['extensionsolution']);
        // Assert all are present.
        foreach ($usersetsolutions as $userset) {
            $this->assertTrue(in_array($userset->usersetid, $usersets));
        }
    }

    /**
     * Test kronostmrequest_unassign_all_solutionuserset_roles by ensuring all roles are unassigned to usersets.
     */
    public function test_kronostmrequest_unassign_all_solutionuserset_roles() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $auth = get_auth_plugin('kronosportal');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolution);
        // Assign a second role to a userset solution.
        $contextidname = $auth->userset_solutionid_exists('extensionsolution');
        $context = \local_elisprogram\context\userset::instance($contextidname->usersetid);
        role_assign($this->usersetroleid, $this->user->id, $context);
        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        // Assert two roles are assigned to solution usersets.
        $this->assertCount(2, $usersetsolutions);

        // Remote all userset roles.
        $this->assertTrue(kronostmrequest_unassign_all_solutionuserset_roles($this->user->id));

        // Assert no roles are assigned to solution usersets.
        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(0, $usersetsolutions);
    }

    /**
     * Test kronostmrequest_get_solution_usersets_roles.
     */
    public function test_kronostmrequest_unassign_userset_role() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $auth = get_auth_plugin('kronosportal');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolution);
        $userset = array_pop($usersetsolution);
        $this->assertTrue(kronostmrequest_unassign_userset_role($this->user->id, $userset->contextid));
        // Assert there is no solution userset assigned.
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(0, $usersetsolution);
    }

    /**
     * Test unassigning of all roles.
     */
    public function test_kronostmrequest_unassign_all_roles() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $auth = get_auth_plugin('kronosportal');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertTrue(kronostmrequest_has_system_role($this->user->id));
        // Assert system role is unassgined.
        $this->assertTrue(kronostmrequest_unassign_system_role($this->user->id));
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));

        // Assert userset role is unassigned.
        $roles = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $roles);
        kronostmrequest_unassign_all_solutionuserset_roles($this->user->id);
        $roles = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(0, $roles);

        // Test unassigning of userset role.
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $roles = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $roles);
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));

        // Unassign all roles.
        kronostmrequest_unassign_all_roles($this->user->id);
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
        $roles = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(0, $roles);
    }

    /**
     * Create solution userset.
     * @param string $solution Name of solution.
     * @param string $solutionid Solution id string.
     * @return object Userset object.
     */
    private function create_solution_userset($solution, $solutionid) {
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

    /**
     * Test kronostmrequest_get_solution_usersets.
     */
    public function test_kronostmrequest_get_solution_usersets() {
        $usersetsolutions = kronostmrequest_get_solution_usersets('testsolutionid');
        $this->assertCount(1, $usersetsolutions);
        $userset = array_pop($usersetsolutions);
        $this->assertEquals($this->usersets['testsolutionid'], $userset->usersetid);
        $newsolution = $this->create_solution_userset('testsolutionid name', 'testsolutionid');
        $usersetsolutions = kronostmrequest_get_solution_usersets('testsolutionid');
        // Assert two usersets are returned, this is testing an invalid configuration.
        $this->assertCount(2, $usersetsolutions);
        // Assert both usersets are returned.
        $validids = array($newsolution->id, $this->usersets['testsolutionid']);
        $userset = array_pop($usersetsolutions);
        $this->assertTrue(in_array($userset->usersetid, $validids));
        $userset = array_pop($usersetsolutions);
        $this->assertTrue(in_array($userset->usersetid, $validids));
    }

    /**
     * Test test_kronostmrequest_validate_role with user with no solution id.
     */
    public function test_kronostmrequest_validate_role_nousersolutionid() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        kronostmrequest_assign_system_role($this->user->id);
        $this->setcustomfielddata('customerid', $this->user->id, '');
        $this->assertEquals("nousersolutionid", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test test_kronostmrequest_validate_role with user with no system role.
     */
    public function test_kronostmrequest_validate_role_nosystemrole() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
        $this->assertTrue(kronostmrequest_unassign_system_role($this->user->id));
        $this->assertEquals("nosystemrole", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test test_kronostmrequest_validate_role with user with no solution userset roles.
     */
    public function test_kronostmrequest_validate_role_nosolutionusersetroles() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolution);
        $userset = array_pop($usersetsolution);
        $this->assertTrue(kronostmrequest_unassign_userset_role($this->user->id, $userset->contextid));
        $this->assertEquals("nosolutionusersetroles", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test test_kronostmrequest_validate_role with user with no solution usersets. Moving user from one solution id to a non existant.
     */
    public function test_kronostmrequest_validate_role_nosolutionusersets() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
        $this->setcustomfielddata('customerid', $this->user->id, 'deletedsolution');
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolution);
        $userset = array_pop($usersetsolution);
        $this->assertTrue(kronostmrequest_unassign_userset_role($this->user->id, $userset->contextid));
        $this->assertEquals("nosolutionusersets", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test test_kronostmrequest_validate_role with user with no solution usersets. Moving user from one solution id to another.
     */
    public function test_kronostmrequest_validate_role_nosolutionusersets_valid() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $newsolution = $this->create_solution_userset('testsolutionid name new', 'testsolutionidnew');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
        $this->setcustomfielddata('customerid', $this->user->id, 'testsolutionidnew');
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolution);
        $userset = array_pop($usersetsolution);
        $this->assertTrue(kronostmrequest_unassign_userset_role($this->user->id, $userset->contextid));
        $this->assertEquals("nosolutionusersetroles", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test kronostmrequest_validate_role with user with no solution usersets. Moving user from one solution id to another
     * with an invalid manually assigned solution userset.
     */
    public function test_kronostmrequest_validate_role_invalidsolutionusersetrole() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $newsolution = $this->create_solution_userset('testsolutionid name new', 'testsolutionidnew');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
        $this->setcustomfielddata('customerid', $this->user->id, 'testsolutionidnew');

        // Ensure a second userset is not assigned a role.
        $this->assertFalse(kronostmrequest_assign_userset_role($this->user->id));

        // Similaute a manual role assignement.
        $auth = get_auth_plugin('kronosportal');
        $contextidname = $auth->userset_solutionid_exists('testsolutionidnew');
        $context = context::instance_by_id($contextidname->id);
        $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
        role_assign($usersetroleid, $this->user->id, $context);

        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(2, $usersetsolutions);
        $this->assertEquals("invalidsolutionusersetrole", kronostmrequest_validate_role($this->user->id));

        // Test kronostmrequest_unassign_userset_role function.
        foreach ($usersetsolutions as $usersetsolution) {
            if ($usersetsolution->usersetid != $newsolution->id) {
                kronostmrequest_unassign_userset_role($this->user->id, $usersetsolution->contextid);
            }
        }
        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(1, $usersetsolutions);

        // Test the training manager role is now valid.
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
    }
}