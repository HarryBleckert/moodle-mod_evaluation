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
 * @package    mod_evaluation
 * @subpackage backup-moodle2
 * @copyright 2010 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_evaluation_activity_task
 */

/**
 * Structure step to restore one evaluation activity
 */
class restore_evaluation_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('evaluation', '/activity/evaluation');
        $paths[] = new restore_path_element('evaluation_item', '/activity/evaluation/items/item');
        if ($userinfo) {
            $paths[] = new restore_path_element('evaluation_completed', '/activity/evaluation/completeds/completed');
            $paths[] = new restore_path_element('evaluation_value', '/activity/evaluation/completeds/completed/values/value');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_evaluation($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $data->timeopen = $this->apply_date_offset($data->timeopen);
        $data->timeclose = $this->apply_date_offset($data->timeclose);

        // insert the evaluation record
        $newitemid = $DB->insert_record('evaluation', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_evaluation_item($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->evaluation = $this->get_new_parentid('evaluation');

        $newitemid = $DB->insert_record('evaluation_item', $data);
        $this->set_mapping('evaluation_item', $oldid, $newitemid, true); // Can have files
    }

    protected function process_evaluation_completed($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->evaluation = $this->get_new_parentid('evaluation');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if ($this->task->is_samesite() && !empty($data->courseid)) {
            $data->courseid = $data->courseid;
        } else if ($this->get_courseid() == SITEID) {
            $data->courseid = SITEID;
        } else {
            $data->courseid = 0;
        }

        $newitemid = $DB->insert_record('evaluation_completed', $data);
        $this->set_mapping('evaluation_completed', $oldid, $newitemid);
    }

    protected function process_evaluation_value($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->completed = $this->get_new_parentid('evaluation_completed');
        $data->item = $this->get_mappingid('evaluation_item', $data->item);
        if ($this->task->is_samesite() && !empty($data->courseid)) {
            $data->courseid = $data->courseid;
        } else if ($this->get_courseid() == SITEID) {
            $data->courseid = SITEID;
        } else {
            $data->courseid = 0;
        }

        $newitemid = $DB->insert_record('evaluation_value', $data);
        $this->set_mapping('evaluation_value', $oldid, $newitemid);
    }

    protected function after_execute() {
        global $DB;
        // Add evaluation related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_evaluation', 'intro', null);
        $this->add_related_files('mod_evaluation', 'page_after_submit', null);
        $this->add_related_files('mod_evaluation', 'item', 'evaluation_item');

        // Once all items are restored we can set their dependency.
        if ($records = $DB->get_records('evaluation_item', array('evaluation' => $this->task->get_activityid()))) {
            foreach ($records as $record) {
                // Get new id for dependitem if present. This will also reset dependitem if not found.
                $record->dependitem = $this->get_mappingid('evaluation_item', $record->dependitem);
                $DB->update_record('evaluation_item', $record);
            }
        }
    }
}
