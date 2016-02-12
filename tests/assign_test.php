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
// Test without events.
define('KRONOS_PHPUNIT_SCRIPT', true);

require_once(dirname(__FILE__).'/testlib.php');

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
class block_kronostmrequest_assign_testcase extends block_kronostmrequest_test {
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

    /**
     * Test kronostmrequest_can_assign with user with no system role, no system role and solution userset role.
     */
    public function test_kronostmrequest_canassign_role_nosystemrole() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("systemrole", kronostmrequest_can_assign($this->user->id));
        $this->assertTrue(kronostmrequest_unassign_system_role($this->user->id));
        $this->assertEquals("solutionusersetroleassigned", kronostmrequest_can_assign($this->user->id));
    }

    /**
     * Test kronostmrequest_can_assign with user with no solution id assigned.
     */
    public function test_kronostmrequest_canassign_role_nosolutionid() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->setcustomfielddata('customerid', $this->user->id, '');
        $this->assertEquals("nousersolutionid", kronostmrequest_can_assign($this->user->id));
    }

    /**
     * Test kronostmrequest_can_assign with no solution userset or more than one solution userset.
     */
    public function test_kronostmrequest_canassign_role_usersets() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        $this->setcustomfielddata('customerid', $this->user->id, 'othersolutionid');
        $this->assertEquals("nosolutionusersets", kronostmrequest_can_assign($this->user->id));
        $this->create_solution_userset('testsolutionid name new', 'othersolutionid');
        $this->create_solution_userset('testsolutionid name new', 'othersolutionid');
        $this->assertEquals("morethanonesolutionuserset", kronostmrequest_can_assign($this->user->id));
    }

    /**
     * Test kronostmrequest_can_assign with role all ready assigned.
     */
    public function test_kronostmrequest_canassign_role_usersets_role() {
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
        // Similaute a manual role assignement.
        $auth = get_auth_plugin('kronosportal');
        // Test training manager role can be assigned.
        $this->assertEquals("valid", kronostmrequest_can_assign($this->user->id));
        $newsolution = $this->create_solution_userset('testsolutionid name new', 'testsolutionidnew');
        $contextidname = $auth->userset_solutionid_exists('testsolutionidnew');
        $context = context::instance_by_id($contextidname->id);
        $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
        role_assign($usersetroleid, $this->user->id, $context);
        $this->assertEquals("solutionusersetroleassigned", kronostmrequest_can_assign($this->user->id));
    }
}