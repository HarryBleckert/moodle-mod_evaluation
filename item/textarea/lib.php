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

defined('MOODLE_INTERNAL') OR die('not allowed');
require_once($CFG->dirroot.'/mod/evaluation/item/evaluation_item_class.php');

class evaluation_item_textarea extends evaluation_item_base {
    protected $type = "textarea";

    public function build_editform($item, $evaluation, $cm) {
        global $DB, $CFG;
        require_once('textarea_form.php');

        //get the lastposition number of the evaluation_items
        $position = $item->position;
        $lastposition = $DB->count_records('evaluation_item', array('evaluation'=>$evaluation->id));
        if ($position == -1) {
            $i_formselect_last = $lastposition + 1;
            $i_formselect_value = $lastposition + 1;
            $item->position = $lastposition + 1;
        } else {
            $i_formselect_last = $lastposition;
            $i_formselect_value = $item->position;
        }
        //the elements for position dropdownlist
        $positionlist = array_slice(range(0, $i_formselect_last), 1, $i_formselect_last, true);

        $item->presentation = empty($item->presentation) ? '' : $item->presentation;

        $width_and_height = explode('|', $item->presentation);

        if (isset($width_and_height[0]) AND $width_and_height[0] >= 5) {
            $itemwidth = $width_and_height[0];
        } else {
            $itemwidth = 30;
        }

        if (isset($width_and_height[1])) {
            $itemheight = $width_and_height[1];
        } else {
            $itemheight = 5;
        }
        $item->itemwidth = $itemwidth;
        $item->itemheight = $itemheight;

        //all items for dependitem
        $evaluationitems = evaluation_get_depend_candidates_for_item($evaluation, $item);
        $commonparams = array('cmid'=>$cm->id,
                             'id'=>isset($item->id) ? $item->id : null,
                             'typ'=>$item->typ,
                             'items'=>$evaluationitems,
                             'evaluation'=>$evaluation->id);

        //build the form
        $customdata = array('item' => $item,
                            'common' => $commonparams,
                            'positionlist' => $positionlist,
                            'position' => $position);

        $this->item_form = new evaluation_textarea_form('edit_item.php', $customdata);
    }

    public function save_item() {
        global $DB;

        if (!$this->get_data()) {
            return false;
        }
        $item = $this->item;

        if (isset($item->clone_item) AND $item->clone_item) {
            $item->id = ''; //to clone this item
            $item->position++;
        }

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('evaluation_item', $item);
        } else {
            $DB->update_record('evaluation_item', $item);
        }

        return $DB->get_record('evaluation_item', array('id'=>$item->id));
    }

    /**
     * Helper function for collected data for exporting to excel
     *
     * @param stdClass $item the db-object from evaluation_item
     * @param int $groupid
     * @param int $courseid
     * @return stdClass
     */
    protected function get_analysed($item, $groupid = false, $courseid = false, $teacherid=false, $course_of_studies=false ) 
	{
        global $DB;

        $analysed_val = new stdClass();
        $analysed_val->data = array();
        $analysed_val->name = $item->name;

        $values = evaluation_get_group_values($item, $groupid, $courseid, $teacherid, $course_of_studies );
        if ($values) {
            $data = array();
            foreach ($values as $value) {
                $data[] = str_replace("\n", '<br />', $value->value);
            }
            $analysed_val->data = $data;
        }
        return $analysed_val;
    }

    public function get_printval($item, $value) {

        if (!isset($value->value)) {
            return '';
        }

        return $value->value;
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false, $teacherid=false, $course_of_studies=false, $Chart="bar" ) {
        $values = evaluation_get_group_values($item, $groupid, $courseid, $teacherid, $course_of_studies);
        if ($values) {
            echo "<table class=\"analysis itemtype_{$item->typ}\">";
            echo '<tr><th colspan="2" align="left">';
            echo $itemnr . ' ';
            if (strval($item->label) !== '') {
                echo '('. format_string($item->label).') ';
            }
            echo format_text($item->name, FORMAT_HTML, array('noclean' => true, 'para' => false));
            echo '</th></tr>';
            foreach ($values as $value) {
                $class = strlen(trim($value->value)) ? '' : ' class="isempty"';
                echo '<tr'.$class.'>';
                echo '<td colspan="2" class="singlevalue">';
                echo str_replace("\n", '<br />', $value->value);
                echo '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false, $teacherid=false, $course_of_studies=false) {

        $analysed_item = $this->get_analysed($item, $groupid, $courseid, $teacherid, $course_of_studies);

        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $item->name, $xls_formats->head2);
        $data = $analysed_item->data;
        if (is_array($data)) {
            if (isset($data[0])) {
                $worksheet->write_string($row_offset, 2, htmlspecialchars_decode($data[0], ENT_QUOTES), $xls_formats->value_bold);
            }
            $row_offset++;
            $sizeofdata = count($data);
            for ($i = 1; $i < $sizeofdata; $i++) {
                $worksheet->write_string($row_offset, 2, htmlspecialchars_decode($data[$i], ENT_QUOTES), $xls_formats->default);
                $row_offset++;
            }
        }
        $row_offset++;
        return $row_offset;
    }

    /**
     * Adds an input element to the complete form
     *
     * @param stdClass $item
     * @param mod_evaluation_complete_form $form
     */
    public function complete_form_element($item, $form) {
        $name = $this->get_display_name($item);
        $inputname = $item->typ . '_' . $item->id;
        list($cols, $rows) = explode ("|", $item->presentation);
        $form->add_form_element($item,
            ['textarea', $inputname, $name, array('rows' => $rows, 'cols' => $cols)]);
        $form->set_element_type($inputname, PARAM_NOTAGS);
    }

    public function create_value($data) {
        return s($data);
    }

    /**
     * Return the analysis data ready for external functions.
     *
     * @param stdClass $item     the item (question) information
     * @param int      $groupid  the group id to filter data (optional)
     * @param int      $courseid the course id (optional)
     * @return array an array of data with non scalar types json encoded
     * @since  Moodle 3.3
     */
     public function get_analysed_for_external($item, $groupid = false, $courseid = false, $teacherid=false, $course_of_studies=false) {

        $externaldata = array();
        $data = $this->get_analysed($item, $groupid, $courseid, $teacherid, $course_of_studies );

        if (is_array($data->data)) {
            return $data->data; // No need to json, scalar type.
        }
        return $externaldata;
    }
}
