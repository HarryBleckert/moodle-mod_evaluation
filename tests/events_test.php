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
 * Tests for evaluation events.
 *
 * @package    mod_evaluation
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */

global $CFG;

/**
 * Class mod_evaluation_events_testcase
 *
 * Class for tests related to evaluation events.
 *
 * @package    mod_evaluation
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class mod_evaluation_events_testcase extends advanced_testcase {

    /** @var  stdClass A user who likes to interact with evaluation activity. */
    private $eventuser;

    /** @var  stdClass A course used to hold evaluation activities for testing. */
    private $eventcourse;

    /** @var  stdClass A evaluation activity used for evaluation event testing. */
    private $eventevaluation;

    /** @var  stdClass course module object . */
    private $eventcm;

    /** @var  stdClass A evaluation item. */
    private $eventevaluationitem;

    /** @var  stdClass A evaluation activity response submitted by user. */
    private $eventevaluationcompleted;

    /** @var  stdClass value associated with $eventevaluationitem . */
    private $eventevaluationvalue;

    public function setUp() {
        global $DB;

        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $this->eventuser = $gen->create_user(); // Create a user.
        $course = $gen->create_course(); // Create a course.
        // Assign manager role, so user can see reports.
        role_assign(1, $this->eventuser->id, context_course::instance($course->id));

        // Add a evaluation activity to the created course.
        $record = new stdClass();
        $record->course = $course->id;
        $evaluation = $gen->create_module('evaluation', $record);
        $this->eventevaluation = $DB->get_record('evaluation', array('id' => $evaluation->id), '*', MUST_EXIST); // Get exact copy.
        $this->eventcm = get_coursemodule_from_instance('evaluation', $this->eventevaluation->id, false, MUST_EXIST);

        // Create a evaluation item.
        $item = new stdClass();
        $item->evaluation = $this->eventevaluation->id;
        $item->type = 'numeric';
        $item->presentation = '0|0';
        $itemid = $DB->insert_record('evaluation_item', $item);
        $this->eventevaluationitem = $DB->get_record('evaluation_item', array('id' => $itemid), '*', MUST_EXIST);

        // Create a response from a user.
        $response = new stdClass();
        $response->evaluation = $this->eventevaluation->id;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = EVALUATION_ANONYMOUS_YES;
        $completedid = $DB->insert_record('evaluation_completed', $response);
        $this->eventevaluationcompleted = $DB->get_record('evaluation_completed', array('id' => $completedid), '*', MUST_EXIST);

        $value = new stdClass();
        $value->courseid = $course->id;
        $value->item = $this->eventevaluationitem->id;
        $value->completed = $this->eventevaluationcompleted->id;
        $value->value = 25; // User response value.
        $valueid = $DB->insert_record('evaluation_value', $value);
        $this->eventevaluationvalue = $DB->get_record('evaluation_value', array('id' => $valueid), '*', MUST_EXIST);
        // Do this in the end to get correct sortorder and cacherev values.
        $this->eventcourse = $DB->get_record('course', array('id' => $course->id), '*', MUST_EXIST);

    }

    /**
     * Tests for event response_deleted.
     */
    public function test_response_deleted_event() {
        global $USER, $DB;
        $this->resetAfterTest();

        // Create and delete a module.
        $sink = $this->redirectEvents();
        evaluation_delete_completed($this->eventevaluationcompleted->id);
        $events = $sink->get_events();
        $event = array_pop($events); // Delete evaluation event.
        $sink->close();

        // Validate event data.
        $this->assertInstanceOf('\mod_evaluation\event\response_deleted', $event);
        $this->assertEquals($this->eventevaluationcompleted->id, $event->objectid);
        $this->assertEquals($USER->id, $event->userid);
        $this->assertEquals($this->eventuser->id, $event->relateduserid);
        $this->assertEquals('evaluation_completed', $event->objecttable);
        $this->assertEquals(null, $event->get_url());
        $this->assertEquals($this->eventevaluationcompleted, $event->get_record_snapshot('evaluation_completed', $event->objectid));
        $this->assertEquals($this->eventcourse, $event->get_record_snapshot('course', $event->courseid));
        $this->assertEquals($this->eventevaluation, $event->get_record_snapshot('evaluation', $event->other['instanceid']));

        // Test legacy data.
        $arr = array($this->eventcourse->id, 'evaluation', 'delete', 'view.php?id=' . $this->eventcm->id,
                $this->eventevaluation->id,
                $this->eventevaluation->id);
        $this->assertEventLegacyLogData($arr, $event);
        $this->assertEventContextNotUsed($event);

        // Test can_view() .
        $this->setUser($this->eventuser);
        $this->assertFalse($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();

        // Create a response, with anonymous set to no and test can_view().
        $response = new stdClass();
        $response->evaluation = $this->eventcm->instance;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = EVALUATION_ANONYMOUS_NO;
        $completedid = $DB->insert_record('evaluation_completed', $response);
        $DB->get_record('evaluation_completed', array('id' => $completedid), '*', MUST_EXIST);
        $value = new stdClass();
        $value->courseid = $this->eventcourse->id;
        $value->item = $this->eventevaluationitem->id;
        $value->completed = $completedid;
        $value->value = 25; // User response value.
        $DB->insert_record('evaluation_valuetmp', $value);

        // Save the evaluation.
        $sink = $this->redirectEvents();
        evaluation_delete_completed($completedid);
        $events = $sink->get_events();
        $event = array_pop($events); // Response submitted evaluation event.
        $sink->close();

        // Test can_view() .
        $this->setUser($this->eventuser);
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Tests for event validations related to evaluation response deletion.
     */
    public function test_response_deleted_event_exceptions() {

        $this->resetAfterTest();

        $context = context_module::instance($this->eventcm->id);

        // Test not setting other['anonymous'].
        try {
            \mod_evaluation\event\response_submitted::create(array(
                    'context' => $context,
                    'objectid' => $this->eventevaluationcompleted->id,
                    'relateduserid' => 2,
            ));
            $this->fail("Event validation should not allow \\mod_evaluation\\event\\response_deleted to be triggered without
                    other['anonymous']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'anonymous' value must be set in other.", $e->getMessage());
        }
    }

    /**
     * Tests for event response_submitted.
     */
    public function test_response_submitted_event() {
        global $USER, $DB;
        $this->resetAfterTest();
        $this->setUser($this->eventuser);

        // Create a temporary response, with anonymous set to yes.
        $response = new stdClass();
        $response->evaluation = $this->eventcm->instance;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = EVALUATION_ANONYMOUS_YES;
        $completedid = $DB->insert_record('evaluation_completedtmp', $response);
        $completed = $DB->get_record('evaluation_completedtmp', array('id' => $completedid), '*', MUST_EXIST);
        $value = new stdClass();
        $value->courseid = $this->eventcourse->id;
        $value->item = $this->eventevaluationitem->id;
        $value->completed = $completedid;
        $value->value = 25; // User response value.
        $DB->insert_record('evaluation_valuetmp', $value);

        // Save the evaluation.
        $sink = $this->redirectEvents();
        $id = evaluation_save_tmp_values($completed, false);
        $events = $sink->get_events();
        $event = array_pop($events); // Response submitted evaluation event.
        $sink->close();

        // Validate event data. Evaluation is anonymous.
        $this->assertInstanceOf('\mod_evaluation\event\response_submitted', $event);
        $this->assertEquals($id, $event->objectid);
        $this->assertEquals($USER->id, $event->userid);
        $this->assertEquals($USER->id, $event->relateduserid);
        $this->assertEquals('evaluation_completed', $event->objecttable);
        $this->assertEquals(1, $event->anonymous);
        $this->assertEquals(EVALUATION_ANONYMOUS_YES, $event->other['anonymous']);
        $this->setUser($this->eventuser);
        $this->assertFalse($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();

        // Test legacy data.
        $this->assertEventLegacyLogData(null, $event);

        // Create a temporary response, with anonymous set to no.
        $response = new stdClass();
        $response->evaluation = $this->eventcm->instance;
        $response->userid = $this->eventuser->id;
        $response->anonymous_response = EVALUATION_ANONYMOUS_NO;
        $completedid = $DB->insert_record('evaluation_completedtmp', $response);
        $completed = $DB->get_record('evaluation_completedtmp', array('id' => $completedid), '*', MUST_EXIST);
        $value = new stdClass();
        $value->courseid = $this->eventcourse->id;
        $value->item = $this->eventevaluationitem->id;
        $value->completed = $completedid;
        $value->value = 25; // User response value.
        $DB->insert_record('evaluation_valuetmp', $value);

        // Save the evaluation.
        $sink = $this->redirectEvents();
        evaluation_save_tmp_values($completed, false);
        $events = $sink->get_events();
        $event = array_pop($events); // Response submitted evaluation event.
        $sink->close();

        // Test legacy data.
        $arr = array($this->eventcourse->id, 'evaluation', 'submit', 'view.php?id=' . $this->eventcm->id,
                $this->eventevaluation->id,
                $this->eventcm->id, $this->eventuser->id);
        $this->assertEventLegacyLogData($arr, $event);

        // Test can_view().
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->setAdminUser();
        $this->assertTrue($event->can_view());
        $this->assertDebuggingCalled();
        $this->assertEventContextNotUsed($event);
    }

    /**
     * Tests for event validations related to evaluation response submission.
     */
    public function test_response_submitted_event_exceptions() {

        $this->resetAfterTest();

        $context = context_module::instance($this->eventcm->id);

        // Test not setting instanceid.
        try {
            \mod_evaluation\event\response_submitted::create(array(
                    'context' => $context,
                    'objectid' => $this->eventevaluationcompleted->id,
                    'relateduserid' => 2,
                    'anonymous' => 0,
                    'other' => array('cmid' => $this->eventcm->id, 'anonymous' => 2)
            ));
            $this->fail("Event validation should not allow \\mod_evaluation\\event\\response_deleted to be triggered without
                    other['instanceid']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'instanceid' value must be set in other.", $e->getMessage());
        }

        // Test not setting cmid.
        try {
            \mod_evaluation\event\response_submitted::create(array(
                    'context' => $context,
                    'objectid' => $this->eventevaluationcompleted->id,
                    'relateduserid' => 2,
                    'anonymous' => 0,
                    'other' => array('instanceid' => $this->eventevaluation->id, 'anonymous' => 2)
            ));
            $this->fail("Event validation should not allow \\mod_evaluation\\event\\response_deleted to be triggered without
                    other['cmid']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'cmid' value must be set in other.", $e->getMessage());
        }

        // Test not setting anonymous.
        try {
            \mod_evaluation\event\response_submitted::create(array(
                    'context' => $context,
                    'objectid' => $this->eventevaluationcompleted->id,
                    'relateduserid' => 2,
                    'other' => array('cmid' => $this->eventcm->id, 'instanceid' => $this->eventevaluation->id)
            ));
            $this->fail("Event validation should not allow \\mod_evaluation\\event\\response_deleted to be triggered without
                    other['anonymous']");
        } catch (coding_exception $e) {
            $this->assertContains("The 'anonymous' value must be set in other.", $e->getMessage());
        }
    }

    /**
     * Test that event observer is executed on course deletion and the templates are removed.
     */
    public function test_delete_course() {
        global $DB;
        $this->resetAfterTest();
        evaluation_save_as_template($this->eventevaluation, 'my template', 0);
        $courseid = $this->eventcourse->id;
        $this->assertNotEmpty($DB->get_records('evaluation_template', array('course' => $courseid)));
        delete_course($this->eventcourse, false);
        $this->assertEmpty($DB->get_records('evaluation_template', array('course' => $courseid)));
    }
}

