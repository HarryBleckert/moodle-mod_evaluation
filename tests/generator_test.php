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
 * Genarator tests.
 *
 * @package    mod_evaluation
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

/**
 * Genarator tests class.
 *
 * @package    mod_evaluation
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('evaluation', array('course' => $course->id)));
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course->id));
        $this->assertEquals(1, $DB->count_records('evaluation', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('evaluation', array('course' => $course->id)));
        $this->assertTrue($DB->record_exists('evaluation', array('id' => $evaluation->id)));

        $params = array('course' => $course->id, 'name' => 'One more evaluation');
        $evaluation = $this->getDataGenerator()->create_module('evaluation', $params);
        $this->assertEquals(2, $DB->count_records('evaluation', array('course' => $course->id)));
        $this->assertEquals('One more evaluation', $DB->get_field_select('evaluation', 'name', 'id = :id',
                array('id' => $evaluation->id)));
    }

    public function test_create_item_info() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_info($evaluation);
        $item2 = $evaluationgenerator->create_item_info($evaluation, array('name' => 'Custom name'));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals('Custom name', $records[$item2->id]->name);
        $this->assertEquals('info', $records[$item1->id]->typ);
    }

    public function test_create_item_label() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_label($evaluation);

        $editor = array(
                'text' => "Custom editor",
                'format' => FORMAT_HTML,
                'itemid' => 0
        );
        $item2 = $evaluationgenerator->create_item_label($evaluation, array('presentation_editor' => $editor));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals($editor['text'], $records[$item2->id]->presentation);
        $this->assertEquals('label', $records[$item1->id]->typ);
    }

    public function test_create_item_multichoice() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_multichoice($evaluation);
        $item2 = $evaluationgenerator->create_item_multichoice($evaluation, array('values' => "1\n2\n3\n4\n5", 'horizontal' => 1));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals('r>>>>>a|b|c|d|e', $records[$item1->id]->presentation);
        $this->assertEquals('r>>>>>1|2|3|4|5<<<<<1', $records[$item2->id]->presentation);
        $this->assertEquals('multichoice', $records[$item1->id]->typ);
    }

    public function test_create_item_multichoicerated() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_multichoicerated($evaluation);
        $item2 = $evaluationgenerator->create_item_multichoicerated($evaluation, array(
                'values' => "0/1\n1/2\n2/3\n3/4\n4/5", 'horizontal' => 1));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals('r>>>>>0####a|1####b|2####c|3####d|4####e', $records[$item1->id]->presentation);
        $this->assertEquals('r>>>>>0####1|1####2|2####3|3####4|4####5<<<<<1', $records[$item2->id]->presentation);
        $this->assertEquals('multichoicerated', $records[$item1->id]->typ);
    }

    public function test_create_item_numeric() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_numeric($evaluation);
        $item2 = $evaluationgenerator->create_item_numeric($evaluation, array('rangefrom' => '0', 'rangeto' => '10'));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals('-|-', $records[$item1->id]->presentation);
        $this->assertEquals('0|10', $records[$item2->id]->presentation);
        $this->assertEquals('numeric', $records[$item1->id]->typ);
    }

    public function test_create_item_textarea() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_textarea($evaluation);
        $item2 = $evaluationgenerator->create_item_textarea($evaluation, array('itemwidth' => '20', 'itemheight' => '10'));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals('40|20', $records[$item1->id]->presentation);
        $this->assertEquals('20|10', $records[$item2->id]->presentation);
        $this->assertEquals('textarea', $records[$item1->id]->typ);
    }

    public function test_create_item_textfield() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        $item1 = $evaluationgenerator->create_item_textfield($evaluation);
        $item2 = $evaluationgenerator->create_item_textfield($evaluation, array('itemsize' => '20', 'itemmaxlength' => '10'));
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
        $this->assertEquals($item1->id, $records[$item1->id]->id);
        $this->assertEquals($item2->id, $records[$item2->id]->id);
        $this->assertEquals('20|30', $records[$item1->id]->presentation);
        $this->assertEquals('20|10', $records[$item2->id]->presentation);
        $this->assertEquals('textfield', $records[$item1->id]->typ);
    }

    public function test_create_item_pagebreak() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $evaluation = $this->getDataGenerator()->create_module('evaluation', array('course' => $course));
        $evaluationgenerator = $this->getDataGenerator()->get_plugin_generator('mod_evaluation');

        // Try to create a page break in an empty evaluation (no items).
        $evaluationgenerator->create_item_pagebreak($evaluation);
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(0, $records);

        // Create at least one item before the pagebreak.
        $evaluationgenerator->create_item_textfield($evaluation);

        // Now, create one pagebreak.
        $item1 = $evaluationgenerator->create_item_pagebreak($evaluation);
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);

        // This pagebreak won't be created (there is already one in the last position).
        $item2 = $evaluationgenerator->create_item_pagebreak($evaluation);
        $this->assertFalse($item2);
        $records = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'id');
        $this->assertCount(2, $records);
    }
}

