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
// Test with events.
define('KRONOS_PHPUNIT_SCRIPT', false);

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
class block_kronostmrequest_event_testcase extends block_kronostmrequest_test {
    /**
     * Setup training manager.
     */
    public function setup() {
        parent::setup();
        set_config('systemrole', $this->roleid, 'block_kronostmrequest');
        set_config('usersetrole', $this->usersetroleid, 'block_kronostmrequest');
    }

    /**
     * Test has system role. System role should be allowed to be assigned and unassigned so long as it is
     * assigned to a valid configuration and before a userset role.
     */
    public function test_has_system_role() {
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
        $context = context_system::instance();
        role_assign($this->roleid, $this->user->id, $context);
        $this->assertTrue(kronostmrequest_has_system_role($this->user->id));
        role_unassign($this->roleid, $this->user->id, $context->id);
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
    }


    /**
     * Test only assigning userset role, event handler should auto remove userset role due to missing system role.
     */
    public function test_has_userset_role() {
        global $DB;
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
        $auth = get_auth_plugin('kronosportal');
        $contextidname = $auth->userset_solutionid_exists('testsolutionid');
        $context = context::instance_by_id($contextidname->id);
        role_assign($this->usersetroleid, $this->user->id, $context);
        // Event handler should intervene and unassign the invalid configuration.
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
    }

    /**
     * Test assign valid userset role.
     */
    public function test_assign_system_userset_role() {
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
        $this->assertTrue(kronostmrequest_assign_system_role($this->user->id));
        $this->assertTrue(kronostmrequest_assign_userset_role($this->user->id));
        $this->assertTrue(kronostmrequest_has_system_role($this->user->id));
        $this->assertTrue(kronostmrequest_has_userset_role($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test update solution id custom profile field.
     */
    public function test_profile_update() {
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
        $this->setcustomfielddata('customerid', $this->user->id, 'deletedsolution');
        // With events enabled the userset and system role are unassigned automatically.
        $usersetsolution = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $this->assertCount(0, $usersetsolution);
        $this->assertFalse(kronostmrequest_has_system_role($this->user->id));
        $this->assertEquals("nosystemrole", kronostmrequest_validate_role($this->user->id));
    }

    /**
     * Test if solution userset role is automatically removed if system role is removed.
     */
    public function test_userset_auto_unassign_userset() {
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        kronostmrequest_unassign_system_role($this->user->id);
        $this->assertFalse(kronostmrequest_has_userset_role($this->user->id));
    }

    /**
     * Test if solution userset role is automatically removed if invalid solution userset role is assigned.
     */
    public function test_userset_auto_unassign_invalid_userset() {
        $this->assertTrue(kronostmrequest_role_assign($this->user->id));
        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        $newsolution = $this->create_solution_userset('testsolutionid name new', 'testsolutionidnew');
        $auth = get_auth_plugin('kronosportal');
        $contextidname = $auth->userset_solutionid_exists('testsolutionidnew');
        $context = context::instance_by_id($contextidname->id);
        $usersetroleid = get_config('block_kronostmrequest', 'usersetrole');
        // Assign a second solution userset.
        role_assign($usersetroleid, $this->user->id, $context);
        $usersetsolutions = kronostmrequest_get_solution_usersets_roles($this->user->id);
        // The invalid solution userset should of been auto unassigned leaving only one.
        $this->assertCount(1, $usersetsolutions);
        $this->assertEquals("valid", kronostmrequest_validate_role($this->user->id));
    }
}
