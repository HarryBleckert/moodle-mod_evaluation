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
 * Evaluation module external functions tests
 *
 * @package    mod_evaluation
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/evaluation/lib.php');

use mod_evaluation\external\evaluation_summary_exporter;

/**
 * Evaluation module external functions tests
 *
 * @package    mod_evaluation
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class mod_evaluation_external_testcase extends externallib_advanced_testcase {

    /**
     * Set up for every test
     */
    public function setUp() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $this->course = $this->getDataGenerator()->create_course();
        $this->evaluation = $this->getDataGenerator()->create_module('evaluation',
            array('course' => $this->course->id, 'email_notification' => 1));
        $this->context = context_module::instance($this->evaluation->cmid);
        $this->cm = get_coursemodule_from_instance('evaluation', $this->evaluation->id);

        // Create users.
        $this->student = self::getDataGenerator()->create_user();
        $this->teacher = self::getDataGenerator()->create_user();

        // Users enrolments.
        $this->studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->teacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
        $this->getDataGenerator()->enrol_user($this->student->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->getDataGenerator()->enrol_user($this->teacher->id, $this->course->id, $this->teacherrole->id, 'manual');
    }

    /**
     * Helper method to add items to an existing evaluation.
     *
     * @param stdClass  $evaluation evaluation instance
     * @param integer $pagescount the number of pages we want in the evaluation
     * @return array list of items created
     */
    public function populate_evaluation($evaluation, $pagescount = 1) {
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');
        $itemscreated = [];

        // Create at least one page.
        $itemscreated[] = $evaluationgenerator->create_item_label($evaluation);
        $itemscreated[] = $evaluationgenerator->create_item_info($evaluation);
        $itemscreated[] = $evaluationgenerator->create_item_numeric($evaluation);

        // Check if we want more pages.
        for ($i = 1; $i < $pagescount; $i++) {
            $itemscreated[] = $evaluationgenerator->create_item_pagebreak($evaluation);
            $itemscreated[] = $evaluationgenerator->create_item_multichoice($evaluation);
            $itemscreated[] = $evaluationgenerator->create_item_multichoicerated($evaluation);
            $itemscreated[] = $evaluationgenerator->create_item_textarea($evaluation);
            $itemscreated[] = $evaluationgenerator->create_item_textfield($evaluation);
            $itemscreated[] = $evaluationgenerator->create_item_numeric($evaluation);
        }
        return $itemscreated;
    }


    /**
     * Test test_mod_evaluation_get_evaluations_by_courses
     */
    public function test_mod_evaluation_get_evaluations_by_courses() {
        global $DB;

        // Create additional course.
        $course2 = self::getDataGenerator()->create_course();

        // Second evaluation.
        $record = new stdClass();
        $record->course = $course2->id;
        $evaluation2 = self::getDataGenerator()->create_module('evaluation', $record);

        // Execute real Moodle enrolment as we'll call unenrol() method on the instance later.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course2->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance2 = $courseenrolinstance;
                break;
            }
        }
        $enrol->enrol_user($instance2, $this->student->id, $this->studentrole->id);

        self::setUser($this->student);

        $returndescription = mod_evaluation_external::get_evaluations_by_courses_returns();

        // Create what we expect to be returned when querying the two courses.
        // First for the student user.
        $expectedfields = array('id', 'coursemodule', 'course', 'name', 'intro', 'introformat', 'introfiles', 'anonymous',
            'multiple_submit', 'autonumbering', 'page_after_submitformat', 'publish_stats', 'completionsubmit');

        $properties = evaluation_summary_exporter::read_properties_definition();

        // Add expected coursemodule and data.
        $evaluation1 = $this->evaluation;
        $evaluation1->coursemodule = $evaluation1->cmid;
        $evaluation1->introformat = 1;
        $evaluation1->introfiles = [];

        $evaluation2->coursemodule = $evaluation2->cmid;
        $evaluation2->introformat = 1;
        $evaluation2->introfiles = [];

        foreach ($expectedfields as $field) {
            if (!empty($properties[$field]) && $properties[$field]['type'] == PARAM_BOOL) {
                $evaluation1->{$field} = (bool) $evaluation1->{$field};
                $evaluation2->{$field} = (bool) $evaluation2->{$field};
            }
            $expected1[$field] = $evaluation1->{$field};
            $expected2[$field] = $evaluation2->{$field};
        }

        $expectedevaluations = array($expected2, $expected1);

        // Call the external function passing course ids.
        $result = mod_evaluation_external::get_evaluations_by_courses(array($course2->id, $this->course->id));
        $result = external_api::clean_returnvalue($returndescription, $result);

        $this->assertEquals($expectedevaluations, $result['evaluations']);
        $this->assertCount(0, $result['warnings']);

        // Call the external function without passing course id.
        $result = mod_evaluation_external::get_evaluations_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedevaluations, $result['evaluations']);
        $this->assertCount(0, $result['warnings']);

        // Unenrol user from second course and alter expected evaluations.
        $enrol->unenrol_user($instance2, $this->student->id);
        array_shift($expectedevaluations);

        // Call the external function without passing course id.
        $result = mod_evaluation_external::get_evaluations_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedevaluations, $result['evaluations']);

        // Call for the second course we unenrolled the user from, expected warning.
        $result = mod_evaluation_external::get_evaluations_by_courses(array($course2->id));
        $this->assertCount(1, $result['warnings']);
        $this->assertEquals('1', $result['warnings'][0]['warningcode']);
        $this->assertEquals($course2->id, $result['warnings'][0]['itemid']);

        // Now, try as a teacher for getting all the additional fields.
        self::setUser($this->teacher);

        $additionalfields = array('email_notification', 'site_after_submit', 'page_after_submit', 'timeopen', 'timeclose',
            'timemodified', 'pageaftersubmitfiles');

        $evaluation1->pageaftersubmitfiles = [];

        foreach ($additionalfields as $field) {
            if (!empty($properties[$field]) && $properties[$field]['type'] == PARAM_BOOL) {
                $evaluation1->{$field} = (bool) $evaluation1->{$field};
            }
            $expectedevaluations[0][$field] = $evaluation1->{$field};
        }
        $expectedevaluations[0]['page_after_submitformat'] = 1;

        $result = mod_evaluation_external::get_evaluations_by_courses();
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedevaluations, $result['evaluations']);

        // Admin also should get all the information.
        self::setAdminUser();

        $result = mod_evaluation_external::get_evaluations_by_courses(array($this->course->id));
        $result = external_api::clean_returnvalue($returndescription, $result);
        $this->assertEquals($expectedevaluations, $result['evaluations']);
    }

    /**
     * Test get_evaluation_access_information function with basic defaults for student.
     */
    public function test_get_evaluation_access_information_student() {

        self::setUser($this->student);
        $result = mod_evaluation_external::get_evaluation_access_information($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_evaluation_access_information_returns(), $result);

        $this->assertFalse($result['canviewanalysis']);
        $this->assertFalse($result['candeletesubmissions']);
        $this->assertFalse($result['canviewreports']);
        $this->assertFalse($result['canedititems']);
        $this->assertTrue($result['cancomplete']);
        $this->assertTrue($result['cansubmit']);
        $this->assertTrue($result['isempty']);
        $this->assertTrue($result['isopen']);
        $this->assertTrue($result['isanonymous']);
        $this->assertFalse($result['isalreadysubmitted']);
    }

    /**
     * Test get_evaluation_access_information function with basic defaults for teacher.
     */
    public function test_get_evaluation_access_information_teacher() {

        self::setUser($this->teacher);
        $result = mod_evaluation_external::get_evaluation_access_information($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_evaluation_access_information_returns(), $result);

        $this->assertTrue($result['canviewanalysis']);
        $this->assertTrue($result['canviewreports']);
        $this->assertTrue($result['canedititems']);
        $this->assertTrue($result['candeletesubmissions']);
        $this->assertFalse($result['cancomplete']);
        $this->assertTrue($result['cansubmit']);
        $this->assertTrue($result['isempty']);
        $this->assertTrue($result['isopen']);
        $this->assertTrue($result['isanonymous']);
        $this->assertFalse($result['isalreadysubmitted']);

        // Add some items to the evaluation and check is not empty any more.
        self::populate_evaluation($this->evaluation);
        $result = mod_evaluation_external::get_evaluation_access_information($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_evaluation_access_information_returns(), $result);
        $this->assertFalse($result['isempty']);
    }

    /**
     * Test view_evaluation invalid id.
     */
    public function test_view_evaluation_invalid_id() {
        // Test invalid instance id.
        $this->expectException('moodle_exception');
        mod_evaluation_external::view_evaluation(0);
    }
    /**
     * Test view_evaluation not enrolled user.
     */
    public function test_view_evaluation_not_enrolled_user() {
        $usernotenrolled = self::getDataGenerator()->create_user();
        $this->setUser($usernotenrolled);
        $this->expectException('moodle_exception');
        mod_evaluation_external::view_evaluation(0);
    }
    /**
     * Test view_evaluation no capabilities.
     */
    public function test_view_evaluation_no_capabilities() {
        // Test user with no capabilities.
        // We need a explicit prohibit since this capability is allowed for students by default.
        assign_capability('mod/evaluation:view', CAP_PROHIBIT, $this->studentrole->id, $this->context->id);
        accesslib_clear_all_caches_for_unit_testing();
        $this->expectException('moodle_exception');
        mod_evaluation_external::view_evaluation(0);
    }
    /**
     * Test view_evaluation.
     */
    public function test_view_evaluation() {
        // Test user with full capabilities.
        $this->setUser($this->student);
        // Trigger and capture the event.
        $sink = $this->redirectEvents();
        $result = mod_evaluation_external::view_evaluation($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::view_evaluation_returns(), $result);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);
        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_evaluation\event\course_module_viewed', $event);
        $this->assertEquals($this->context, $event->get_context());
        $moodledata = new \moodle_url('/mod/evaluation/view.php', array('id' => $this->cm->id));
        $this->assertEquals($moodledata, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test get_current_completed_tmp.
     */
    public function test_get_current_completed_tmp() {
        global $DB;

        // Force non anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_NO, array('id' => $this->evaluation->id));
        // Add a completed_tmp record.
        $record = [
            'evaluation' => $this->evaluation->id,
            'userid' => $this->student->id,
            'guestid' => '',
            'timemodified' => time() - DAYSECS,
            'random_response' => 0,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO,
            'courseid' => $this->course->id,
        ];
        $record['id'] = $DB->insert_record('evaluation_completedtmp', (object) $record);

        // Test user with full capabilities.
        $this->setUser($this->student);

        $result = mod_evaluation_external::get_current_completed_tmp($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_current_completed_tmp_returns(), $result);
        $this->assertEquals($record['id'], $result['evaluation']['id']);
    }

    /**
     * Test get_items.
     */
    public function test_get_items() {
        // Test user with full capabilities.
        $this->setUser($this->student);

        // Add questions to the evaluation, we are adding 2 pages of questions.
        $itemscreated = self::populate_evaluation($this->evaluation, 2);

        $result = mod_evaluation_external::get_items($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_items_returns(), $result);
        $this->assertCount(count($itemscreated), $result['items']);
        $index = 1;
        foreach ($result['items'] as $key => $item) {
            if (is_numeric($itemscreated[$key])) {
                continue; // Page break.
            }
            // Cannot compare directly the exporter and the db object (exporter have more fields).
            $this->assertEquals($itemscreated[$key]->id, $item['id']);
            $this->assertEquals($itemscreated[$key]->typ, $item['typ']);
            $this->assertEquals($itemscreated[$key]->name, $item['name']);
            $this->assertEquals($itemscreated[$key]->label, $item['label']);

            if ($item['hasvalue']) {
                $this->assertEquals($index, $item['itemnumber']);
                $index++;
            }
        }
    }

    /**
     * Test launch_evaluation.
     */
    public function test_launch_evaluation() {
        global $DB;

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Add questions to the evaluation, we are adding 2 pages of questions.
        $itemscreated = self::populate_evaluation($this->evaluation, 2);

        // First try a evaluation we didn't attempt.
        $result = mod_evaluation_external::launch_evaluation($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::launch_evaluation_returns(), $result);
        $this->assertEquals(0, $result['gopage']);

        // Now, try a evaluation that we attempted.
        // Force non anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_NO, array('id' => $this->evaluation->id));
        // Add a completed_tmp record.
        $record = [
            'evaluation' => $this->evaluation->id,
            'userid' => $this->student->id,
            'guestid' => '',
            'timemodified' => time() - DAYSECS,
            'random_response' => 0,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO,
            'courseid' => $this->course->id,
        ];
        $record['id'] = $DB->insert_record('evaluation_completedtmp', (object) $record);

        // Add a response to the evaluation for each question type with possible values.
        $response = [
            'courseid' => $this->course->id,
            'item' => $itemscreated[1]->id, // First item is the info question.
            'completed' => $record['id'],
            'tmp_completed' => $record['id'],
            'value' => 'A',
        ];
        $DB->insert_record('evaluation_valuetmp', (object) $response);
        $response = [
            'courseid' => $this->course->id,
            'item' => $itemscreated[2]->id, // Second item is the numeric question.
            'completed' => $record['id'],
            'tmp_completed' => $record['id'],
            'value' => 5,
        ];
        $DB->insert_record('evaluation_valuetmp', (object) $response);

        $result = mod_evaluation_external::launch_evaluation($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::launch_evaluation_returns(), $result);
        $this->assertEquals(1, $result['gopage']);
    }

    /**
     * Test get_page_items.
     */
    public function test_get_page_items() {
        // Test user with full capabilities.
        $this->setUser($this->student);

        // Add questions to the evaluation, we are adding 2 pages of questions.
        $itemscreated = self::populate_evaluation($this->evaluation, 2);

        // Retrieve first page.
        $result = mod_evaluation_external::get_page_items($this->evaluation->id, 0);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_page_items_returns(), $result);
        $this->assertCount(3, $result['items']);    // The first page has 3 items.
        $this->assertTrue($result['hasnextpage']);
        $this->assertFalse($result['hasprevpage']);

        // Retrieve second page.
        $result = mod_evaluation_external::get_page_items($this->evaluation->id, 1);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_page_items_returns(), $result);
        $this->assertCount(5, $result['items']);    // The second page has 5 items (page break doesn't count).
        $this->assertFalse($result['hasnextpage']);
        $this->assertTrue($result['hasprevpage']);
    }

    /**
     * Test process_page.
     */
    public function test_process_page() {
        global $DB;

        // Test user with full capabilities.
        $this->setUser($this->student);
        $pagecontents = 'You finished it!';
        $DB->set_field('evaluation', 'page_after_submit', $pagecontents, array('id' => $this->evaluation->id));

        // Add questions to the evaluation, we are adding 2 pages of questions.
        $itemscreated = self::populate_evaluation($this->evaluation, 2);

        $data = [];
        foreach ($itemscreated as $item) {

            if (empty($item->hasvalue)) {
                continue;
            }

            switch ($item->typ) {
                case 'textarea':
                case 'textfield':
                    $value = 'Lorem ipsum';
                    break;
                case 'numeric':
                    $value = 5;
                    break;
                case 'multichoice':
                    $value = '1';
                    break;
                case 'multichoicerated':
                    $value = '1';
                    break;
                case 'info':
                    $value = format_string($this->course->shortname, true, array('context' => $this->context));
                    break;
                default:
                    $value = '';
            }
            $data[] = ['name' => $item->typ . '_' . $item->id, 'value' => $value];
        }

        // Process first page.
        $firstpagedata = [$data[0], $data[1]];
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $firstpagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertEquals(1, $result['jumpto']);
        $this->assertFalse($result['completed']);

        // Now, process the second page. But first we are going back to the first page.
        $secondpagedata = [$data[2], $data[3], $data[4], $data[5], $data[6]];
        $result = mod_evaluation_external::process_page($this->evaluation->id, 1, $secondpagedata, true);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertFalse($result['completed']);
        $this->assertEquals(0, $result['jumpto']);  // We jumped to the first page.
        // Check the values were correctly saved.
        $tmpitems = $DB->get_records('evaluation_valuetmp');
        $this->assertCount(7, $tmpitems);   // 2 from the first page + 5 from the second page.

        // Go forward again (sending the same data).
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $firstpagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertEquals(1, $result['jumpto']);
        $this->assertFalse($result['completed']);
        $tmpitems = $DB->get_records('evaluation_valuetmp');
        $this->assertCount(7, $tmpitems);   // 2 from the first page + 5 from the second page.

        // And finally, save everything! We are going to modify one previous recorded value.
        $messagessink = $this->redirectMessages();
        $data[2]['value'] = 2; // 2 is value of the option 'b'.
        $secondpagedata = [$data[2], $data[3], $data[4], $data[5], $data[6]];
        $result = mod_evaluation_external::process_page($this->evaluation->id, 1, $secondpagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);
        $this->assertTrue(strpos($result['completionpagecontents'], $pagecontents) !== false);
        // Check all the items were saved.
        $items = $DB->get_records('evaluation_value');
        $this->assertCount(7, $items);
        // Check if the one we modified was correctly saved.
        $itemid = $itemscreated[4]->id;
        $itemsaved = $DB->get_field('evaluation_value', 'value', array('item' => $itemid));
        $mcitem = new evaluation_item_multichoice();
        $itemval = $mcitem->get_printval($itemscreated[4], (object) ['value' => $itemsaved]);
        $this->assertEquals('b', $itemval);

        // Check that the answers are saved for course 0.
        foreach ($items as $item) {
            $this->assertEquals(0, $item->courseid);
        }
        $completed = $DB->get_record('evaluation_completed', []);
        $this->assertEquals(0, $completed->courseid);

        // Test notifications sent.
        $messages = $messagessink->get_messages();
        $messagessink->close();
        // Test customdata.
        $customdata = json_decode($messages[0]->customdata);
        $this->assertEquals($this->evaluation->id, $customdata->instance);
        $this->assertEquals($this->evaluation->cmid, $customdata->cmid);
        $this->assertObjectHasAttribute('notificationiconurl', $customdata);
    }

    /**
     * Test process_page for a site evaluation.
     */
    public function test_process_page_site_evaluation() {
        global $DB;
        $pagecontents = 'You finished it!';
        $this->evaluation = $this->getDataGenerator()->create_module('evaluation',
            array('course' => SITEID, 'page_after_submit' => $pagecontents));

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Add questions to the evaluation, we are adding 2 pages of questions.
        $itemscreated = self::populate_evaluation($this->evaluation, 2);

        $data = [];
        foreach ($itemscreated as $item) {

            if (empty($item->hasvalue)) {
                continue;
            }

            switch ($item->typ) {
                case 'textarea':
                case 'textfield':
                    $value = 'Lorem ipsum';
                    break;
                case 'numeric':
                    $value = 5;
                    break;
                case 'multichoice':
                    $value = '1';
                    break;
                case 'multichoicerated':
                    $value = '1';
                    break;
                case 'info':
                    $value = format_string($this->course->shortname, true, array('context' => $this->context));
                    break;
                default:
                    $value = '';
            }
            $data[] = ['name' => $item->typ . '_' . $item->id, 'value' => $value];
        }

        // Process first page.
        $firstpagedata = [$data[0], $data[1]];
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $firstpagedata, false, $this->course->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertEquals(1, $result['jumpto']);
        $this->assertFalse($result['completed']);

        // Process second page.
        $data[2]['value'] = 2; // 2 is value of the option 'b';
        $secondpagedata = [$data[2], $data[3], $data[4], $data[5], $data[6]];
        $result = mod_evaluation_external::process_page($this->evaluation->id, 1, $secondpagedata, false, $this->course->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);
        $this->assertTrue(strpos($result['completionpagecontents'], $pagecontents) !== false);
        // Check all the items were saved.
        $items = $DB->get_records('evaluation_value');
        $this->assertCount(7, $items);
        // Check if the one we modified was correctly saved.
        $itemid = $itemscreated[4]->id;
        $itemsaved = $DB->get_field('evaluation_value', 'value', array('item' => $itemid));
        $mcitem = new evaluation_item_multichoice();
        $itemval = $mcitem->get_printval($itemscreated[4], (object) ['value' => $itemsaved]);
        $this->assertEquals('b', $itemval);

        // Check that the answers are saved for the correct course.
        foreach ($items as $item) {
            $this->assertEquals($this->course->id, $item->courseid);
        }
        $completed = $DB->get_record('evaluation_completed', []);
        $this->assertEquals($this->course->id, $completed->courseid);
    }

    /**
     * Test get_analysis.
     */
    public function test_get_analysis() {
        // Test user with full capabilities.
        $this->setUser($this->student);

        // Create a very simple evaluation.
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');
        $numericitem = $evaluationgenerator->create_item_numeric($this->evaluation);
        $textfielditem = $evaluationgenerator->create_item_textfield($this->evaluation);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 5],
            ['name' => $textfielditem->typ .'_'. $textfielditem->id, 'value' => 'abc'],
        ];
        // Process the evaluation, there is only one page so the evaluation will be completed.
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);

        // Retrieve analysis.
        $this->setUser($this->teacher);
        $result = mod_evaluation_external::get_analysis($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_analysis_returns(), $result);
        $this->assertEquals(1, $result['completedcount']);  // 1 evaluation completed.
        $this->assertEquals(2, $result['itemscount']);  // 2 items in the evaluation.
        $this->assertCount(2, $result['itemsdata']);
        $this->assertCount(1, $result['itemsdata'][0]['data']); // There are 1 response per item.
        $this->assertCount(1, $result['itemsdata'][1]['data']);
        // Check we receive the info the students filled.
        foreach ($result['itemsdata'] as $data) {
            if ($data['item']['id'] == $numericitem->id) {
                $this->assertEquals(5, $data['data'][0]);
            } else {
                $this->assertEquals('abc', $data['data'][0]);
            }
        }

        // Create another user / response.
        $anotherstudent = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($anotherstudent->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->setUser($anotherstudent);

        // Process the evaluation, there is only one page so the evaluation will be completed.
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);

        // Retrieve analysis.
        $this->setUser($this->teacher);
        $result = mod_evaluation_external::get_analysis($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_analysis_returns(), $result);
        $this->assertEquals(2, $result['completedcount']);  // 2 evaluation completed.
        $this->assertEquals(2, $result['itemscount']);
        $this->assertCount(2, $result['itemsdata'][0]['data']); // There are 2 responses per item.
        $this->assertCount(2, $result['itemsdata'][1]['data']);
    }

    /**
     * Test get_unfinished_responses.
     */
    public function test_get_unfinished_responses() {
        // Test user with full capabilities.
        $this->setUser($this->student);

        // Create a very simple evaluation.
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');
        $numericitem = $evaluationgenerator->create_item_numeric($this->evaluation);
        $textfielditem = $evaluationgenerator->create_item_textfield($this->evaluation);
        $evaluationgenerator->create_item_pagebreak($this->evaluation);
        $labelitem = $evaluationgenerator->create_item_label($this->evaluation);
        $numericitem2 = $evaluationgenerator->create_item_numeric($this->evaluation);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 5],
            ['name' => $textfielditem->typ .'_'. $textfielditem->id, 'value' => 'abc'],
        ];
        // Process the evaluation, there are two pages so the evaluation will be unfinished yet.
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertFalse($result['completed']);

        // Retrieve the unfinished responses.
        $result = mod_evaluation_external::get_unfinished_responses($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_unfinished_responses_returns(), $result);
        // Check that ids and responses match.
        foreach ($result['responses'] as $r) {
            if ($r['item'] == $numericitem->id) {
                $this->assertEquals(5, $r['value']);
            } else {
                $this->assertEquals($textfielditem->id, $r['item']);
                $this->assertEquals('abc', $r['value']);
            }
        }
    }

    /**
     * Test get_finished_responses.
     */
    public function test_get_finished_responses() {
        // Test user with full capabilities.
        $this->setUser($this->student);

        // Create a very simple evaluation.
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');
        $numericitem = $evaluationgenerator->create_item_numeric($this->evaluation);
        $textfielditem = $evaluationgenerator->create_item_textfield($this->evaluation);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 5],
            ['name' => $textfielditem->typ .'_'. $textfielditem->id, 'value' => 'abc'],
        ];

        // Process the evaluation, there is only one page so the evaluation will be completed.
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);

        // Retrieve the responses.
        $result = mod_evaluation_external::get_finished_responses($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_finished_responses_returns(), $result);
        // Check that ids and responses match.
        foreach ($result['responses'] as $r) {
            if ($r['item'] == $numericitem->id) {
                $this->assertEquals(5, $r['value']);
            } else {
                $this->assertEquals($textfielditem->id, $r['item']);
                $this->assertEquals('abc', $r['value']);
            }
        }
    }

    /**
     * Test get_non_respondents (student trying to get this information).
     */
    public function test_get_non_respondents_no_permissions() {
        $this->setUser($this->student);
        $this->expectException('moodle_exception');
        mod_evaluation_external::get_non_respondents($this->evaluation->id);
    }

    /**
     * Test get_non_respondents from an anonymous evaluation.
     */
    public function test_get_non_respondents_from_anonymous_evaluation() {
        $this->setUser($this->student);
        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('anonymous', 'evaluation'));
        mod_evaluation_external::get_non_respondents($this->evaluation->id);
    }

    /**
     * Test get_non_respondents.
     */
    public function test_get_non_respondents() {
        global $DB;

        // Force non anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_NO, array('id' => $this->evaluation->id));

        // Create another student.
        $anotherstudent = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($anotherstudent->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->setUser($anotherstudent);

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Create a very simple evaluation.
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');
        $numericitem = $evaluationgenerator->create_item_numeric($this->evaluation);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 5],
        ];

        // Process the evaluation, there is only one page so the evaluation will be completed.
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);

        // Retrieve the non-respondent users.
        $this->setUser($this->teacher);
        $result = mod_evaluation_external::get_non_respondents($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_non_respondents_returns(), $result);
        $this->assertCount(0, $result['warnings']);
        $this->assertCount(1, $result['users']);
        $this->assertEquals($anotherstudent->id, $result['users'][0]['userid']);

        // Create another student.
        $anotherstudent2 = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($anotherstudent2->id, $this->course->id, $this->studentrole->id, 'manual');
        $this->setUser($anotherstudent2);
        $this->setUser($this->teacher);
        $result = mod_evaluation_external::get_non_respondents($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_non_respondents_returns(), $result);
        $this->assertCount(0, $result['warnings']);
        $this->assertCount(2, $result['users']);

        // Test pagination.
        $result = mod_evaluation_external::get_non_respondents($this->evaluation->id, 0, 'lastaccess', 0, 1);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_non_respondents_returns(), $result);
        $this->assertCount(0, $result['warnings']);
        $this->assertCount(1, $result['users']);
    }

    /**
     * Helper function that completes the evaluation for two students.
     */
    protected function complete_basic_evaluation() {
        global $DB;

        $generator = $this->getDataGenerator();
        // Create separated groups.
        $DB->set_field('course', 'groupmode', SEPARATEGROUPS);
        $DB->set_field('course', 'groupmodeforce', 1);
        assign_capability('moodle/site:accessallgroups', CAP_PROHIBIT, $this->teacherrole->id, $this->context);
        accesslib_clear_all_caches_for_unit_testing();

        $group1 = $generator->create_group(array('courseid' => $this->course->id));
        $group2 = $generator->create_group(array('courseid' => $this->course->id));

        // Create another students.
        $anotherstudent1 = self::getDataGenerator()->create_user();
        $anotherstudent2 = self::getDataGenerator()->create_user();
        $generator->enrol_user($anotherstudent1->id, $this->course->id, $this->studentrole->id, 'manual');
        $generator->enrol_user($anotherstudent2->id, $this->course->id, $this->studentrole->id, 'manual');

        $generator->create_group_member(array('groupid' => $group1->id, 'userid' => $this->student->id));
        $generator->create_group_member(array('groupid' => $group1->id, 'userid' => $this->teacher->id));
        $generator->create_group_member(array('groupid' => $group1->id, 'userid' => $anotherstudent1->id));
        $generator->create_group_member(array('groupid' => $group2->id, 'userid' => $anotherstudent2->id));

        // Test user with full capabilities.
        $this->setUser($this->student);

        // Create a very simple evaluation.
        $evaluationgenerator = $generator->get_plugin_generator('mod_evaluation');
        $numericitem = $evaluationgenerator->create_item_numeric($this->evaluation);
        $textfielditem = $evaluationgenerator->create_item_textfield($this->evaluation);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 5],
            ['name' => $textfielditem->typ .'_'. $textfielditem->id, 'value' => 'abc'],
        ];

        // Process the evaluation, there is only one page so the evaluation will be completed.
        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);

        $this->setUser($anotherstudent1);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 10],
            ['name' => $textfielditem->typ .'_'. $textfielditem->id, 'value' => 'def'],
        ];

        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);

        $this->setUser($anotherstudent2);

        $pagedata = [
            ['name' => $numericitem->typ .'_'. $numericitem->id, 'value' => 10],
            ['name' => $textfielditem->typ .'_'. $textfielditem->id, 'value' => 'def'],
        ];

        $result = mod_evaluation_external::process_page($this->evaluation->id, 0, $pagedata);
        $result = external_api::clean_returnvalue(mod_evaluation_external::process_page_returns(), $result);
        $this->assertTrue($result['completed']);
    }

    /**
     * Test get_responses_analysis for anonymous evaluation.
     */
    public function test_get_responses_analysis_anonymous() {
        self::complete_basic_evaluation();

        // Retrieve the responses analysis.
        $this->setUser($this->teacher);
        $result = mod_evaluation_external::get_responses_analysis($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_responses_analysis_returns(), $result);
        $this->assertCount(0, $result['warnings']);
        $this->assertEquals(0, $result['totalattempts']);
        $this->assertEquals(2, $result['totalanonattempts']);   // Only see my groups.

        foreach ($result['attempts'] as $attempt) {
            $this->assertEmpty($attempt['userid']); // Is anonymous.
        }
    }

    /**
     * Test get_responses_analysis for non-anonymous evaluation.
     */
    public function test_get_responses_analysis_non_anonymous() {
        global $DB;

        // Force non anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_NO, array('id' => $this->evaluation->id));

        self::complete_basic_evaluation();
        // Retrieve the responses analysis.
        $this->setUser($this->teacher);
        $result = mod_evaluation_external::get_responses_analysis($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_responses_analysis_returns(), $result);
        $this->assertCount(0, $result['warnings']);
        $this->assertEquals(2, $result['totalattempts']);
        $this->assertEquals(0, $result['totalanonattempts']);   // Only see my groups.

        foreach ($result['attempts'] as $attempt) {
            $this->assertNotEmpty($attempt['userid']);  // Is not anonymous.
        }
    }

    /**
     * Test get_last_completed for evaluation anonymous not completed.
     */
    public function test_get_last_completed_anonymous_not_completed() {
        global $DB;

        // Force anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_YES, array('id' => $this->evaluation->id));

        // Test user with full capabilities that didn't complete the evaluation.
        $this->setUser($this->student);

        $this->expectExceptionMessage(get_string('anonymous', 'evaluation'));
        $this->expectException('moodle_exception');
        mod_evaluation_external::get_last_completed($this->evaluation->id);
    }

    /**
     * Test get_last_completed for evaluation anonymous and completed.
     */
    public function test_get_last_completed_anonymous_completed() {
        global $DB;

        // Force anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_YES, array('id' => $this->evaluation->id));
        // Add one completion record..
        $record = [
            'evaluation' => $this->evaluation->id,
            'userid' => $this->student->id,
            'timemodified' => time() - DAYSECS,
            'random_response' => 0,
            'anonymous_response' => EVALUATION_ANONYMOUS_YES,
            'courseid' => $this->course->id,
        ];
        $record['id'] = $DB->insert_record('evaluation_completed', (object) $record);

        // Test user with full capabilities.
        $this->setUser($this->student);

        $this->expectExceptionMessage(get_string('anonymous', 'evaluation'));
        $this->expectException('moodle_exception');
        mod_evaluation_external::get_last_completed($this->evaluation->id);
    }

    /**
     * Test get_last_completed for evaluation not anonymous and completed.
     */
    public function test_get_last_completed_not_anonymous_completed() {
        global $DB;

        // Force non anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_NO, array('id' => $this->evaluation->id));
        // Add one completion record..
        $record = [
            'evaluation' => $this->evaluation->id,
            'userid' => $this->student->id,
            'timemodified' => time() - DAYSECS,
            'random_response' => 0,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO,
            'courseid' => $this->course->id,
        ];
        $record['id'] = $DB->insert_record('evaluation_completed', (object) $record);

        // Test user with full capabilities.
        $this->setUser($this->student);
        $result = mod_evaluation_external::get_last_completed($this->evaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_last_completed_returns(), $result);
        $this->assertEquals($record, $result['completed']);
    }

    /**
     * Test get_last_completed for evaluation not anonymous and not completed.
     */
    public function test_get_last_completed_not_anonymous_not_completed() {
        global $DB;

        // Force anonymous.
        $DB->set_field('evaluation', 'anonymous', EVALUATION_ANONYMOUS_NO, array('id' => $this->evaluation->id));

        // Test user with full capabilities that didn't complete the evaluation.
        $this->setUser($this->student);

        $this->expectExceptionMessage(get_string('not_completed_yet', 'evaluation'));
        $this->expectException('moodle_exception');
        mod_evaluation_external::get_last_completed($this->evaluation->id);
    }

    /**
     * Test get_evaluation_access_information for site evaluation.
     */
    public function test_get_evaluation_access_information_for_site_evaluation() {

        $siteevaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => SITEID));
        $this->setUser($this->student);
        // Access the site evaluation via the site activity.
        $result = mod_evaluation_external::get_evaluation_access_information($siteevaluation->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_evaluation_access_information_returns(), $result);
        $this->assertTrue($result['cancomplete']);
        $this->assertTrue($result['cansubmit']);

        // Access the site evaluation via course where I'm enrolled.
        $result = mod_evaluation_external::get_evaluation_access_information($siteevaluation->id, $this->course->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_evaluation_access_information_returns(), $result);
        $this->assertTrue($result['cancomplete']);
        $this->assertTrue($result['cansubmit']);

        // Access the site evaluation via course where I'm not enrolled.
        $othercourse = $this->getDataGenerator()->create_course();

        $this->expectException('moodle_exception');
        mod_evaluation_external::get_evaluation_access_information($siteevaluation->id, $othercourse->id);
    }

    /**
     * Test get_evaluation_access_information for site evaluation mapped.
     */
    public function test_get_evaluation_access_information_for_site_evaluation_mapped() {
        global $DB;

        $siteevaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => SITEID));
        $this->setUser($this->student);
        $DB->insert_record('evaluation_sitecourse_map', array('evaluationid' => $siteevaluation->id, 'courseid' => $this->course->id));

        // Access the site evaluation via course where I'm enrolled and mapped.
        $result = mod_evaluation_external::get_evaluation_access_information($siteevaluation->id, $this->course->id);
        $result = external_api::clean_returnvalue(mod_evaluation_external::get_evaluation_access_information_returns(), $result);
        $this->assertTrue($result['cancomplete']);
        $this->assertTrue($result['cansubmit']);

        // Access the site evaluation via course where I'm enrolled but not mapped.
        $othercourse = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($this->student->id, $othercourse->id, $this->studentrole->id, 'manual');

        $this->expectException('moodle_exception');
        $this->expectExceptionMessage(get_string('cannotaccess', 'mod_evaluation'));
        mod_evaluation_external::get_evaluation_access_information($siteevaluation->id, $othercourse->id);
    }
}
