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

define('EVALUATION_MULTICHOICE_TYPE_SEP', '>>>>>');
define('EVALUATION_MULTICHOICE_LINE_SEP', '|');
define('EVALUATION_MULTICHOICE_ADJUST_SEP', '<<<<<');
define('EVALUATION_MULTICHOICE_IGNOREEMPTY', 'i');
define('EVALUATION_MULTICHOICE_HIDENOSELECT', 'h');

class evaluation_item_multichoice extends evaluation_item_base {
    protected $type = "multichoice";

    public function build_editform($item, $evaluation, $cm) {
        global $DB, $CFG;
        require_once('multichoice_form.php');

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
        $info = $this->get_info($item);

        $item->ignoreempty = $this->ignoreempty($item);
        $item->hidenoselect = $this->hidenoselect($item);

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
                            'position' => $position,
                            'info' => $info);

        $this->item_form = new evaluation_multichoice_form('edit_item.php', $customdata);
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

        $this->set_ignoreempty($item, $item->ignoreempty);
        $this->set_hidenoselect($item, $item->hidenoselect);

        $item->hasvalue = $this->get_hasvalue();
        if (!$item->id) {
            $item->id = $DB->insert_record('evaluation_item', $item);
        } else {
            $DB->update_record('evaluation_item', $item);
        }

        return $DB->get_record('evaluation_item', array('id'=>$item->id));
    }


    //gets an array with three values(typ, name, XXX)
    //XXX is an object with answertext, answercount and quotient

    /**
     * Helper function for collected data, both for analysis page and export to excel
     *
     * @param stdClass $item the db-object from evaluation_item
     * @param int $groupid
     * @param int $courseid
     * @return array
     */
    protected function get_analysed($item, $groupid = false, $courseid = false, $teacherid=false, $course_of_studies=false ) 
	{
        $info = $this->get_info($item);

        $analysed_item = array();
        $analysed_item[] = $item->typ;
        $analysed_item[] = format_string($item->name);

        //get the possible answers
        $answers = null;
        $answers = explode (EVALUATION_MULTICHOICE_LINE_SEP, $info->presentation);
        if (!is_array($answers)) {
            return null;
        }

        //get the values
        $values = evaluation_get_group_values($item, $groupid, $courseid, $teacherid, $course_of_studies, $this->ignoreempty($item));
        if (!$values) {
            return null;
        }

        //get answertext, answercount and quotient for each answer
        $analysed_answer = array();
        if ($info->subtype == 'c') {
            $sizeofanswers = count($answers);
            for ($i = 1; $i <= $sizeofanswers; $i++) {
                $ans = new stdClass();
                $ans->answertext = $answers[$i-1];
                $ans->answercount = 0;
                foreach ($values as $value) {
                    //ist die Antwort gleich dem index der Antworten + 1?
                    $vallist = explode(EVALUATION_MULTICHOICE_LINE_SEP, $value->value);
                    foreach ($vallist as $val) {
                        if ($val == $i) {
                            $ans->answercount++;
                        }
                    }
                }
                $ans->quotient = $ans->answercount / count($values);
                $analysed_answer[] = $ans;
            }
        } else {
            $sizeofanswers = count($answers);
            for ($i = 1; $i <= $sizeofanswers; $i++) {
                $ans = new stdClass();
                $ans->answertext = $answers[$i-1];
                $ans->answercount = 0;
                foreach ($values as $value) {
                    //ist die Antwort gleich dem index der Antworten + 1?
                    if ($value->value == $i) {
                        $ans->answercount++;
                    }
                }
                $ans->quotient = $ans->answercount / count($values);
                $analysed_answer[] = $ans;
            }
        }
        $analysed_item[] = $analysed_answer;
        return $analysed_item;
    }

    public function get_printval($item, $value) {
        $info = $this->get_info($item);

        $printval = '';

        if (!isset($value->value)) {
            return $printval;
        }

        $presentation = explode (EVALUATION_MULTICHOICE_LINE_SEP, $info->presentation);

        if ($info->subtype == 'c') {
            $vallist = array_values(explode (EVALUATION_MULTICHOICE_LINE_SEP, $value->value));
            $sizeofvallist = count($vallist);
            $sizeofpresentation = count($presentation);
            for ($i = 0; $i < $sizeofvallist; $i++) {
                for ($k = 0; $k < $sizeofpresentation; $k++) {
                    if ($vallist[$i] == ($k + 1)) {//Die Werte beginnen bei 1, das Array aber mit 0
                        $printval .= trim(format_string($presentation[$k])) . chr(10);
                        break;
                    }
                }
            }
        } else {
            $index = 1;
            foreach ($presentation as $pres) {
                if ($value->value == $index) {
                    $printval = format_string($pres);
                    break;
                }
                $index++;
            }
        }
        return $printval;
    }

    public function print_analysed($item, $itemnr = '', $groupid = false, $courseid = false, $teacherid=false, $course_of_studies=false, $Chart="bar") 
	{   global $OUTPUT;
        $analysed_item = $this->get_analysed($item, $groupid, $courseid, $teacherid, $course_of_studies);
		$analysed_itemC = array();
		
        if ($analysed_item) 
		{	$itemname = $analysed_item[1];
			echo '<div style="font-size:16px;color:darkblue;">';
            echo $itemnr . ' ';
            if (strval($item->label) !== '') {
                echo '('. format_string($item->label).') ';
            }
            echo "\n".'<b>' . format_string($itemname) . "</b>\n";
			echo "</div>\n";
            $analysed_vals = $analysed_item[2];
            $count = 0;
            $data = [];
            foreach ($analysed_vals as $val) 
			{	$quotient = format_float($val->quotient * 100, 2);
                $strquotient = '';
                if ($val->quotient > 0) {
                    $strquotient = ' ('. $quotient . ' %)';
                }
                $answertext = format_text(trim($val->answertext), FORMAT_HTML,
                        array('noclean' => true, 'para' => false));

                $data['labels'][$count] = $answertext;
                $data['series'][$count] = $val->answercount;
                $data['series_labels'][$count] = $val->answercount . $strquotient;
				$count++;	
			}
			
			// draw line chart
			if ( $Chart == "line")
			{	$chart = new \core\chart_line(); 
				$chart->set_smooth(true);
			}
			elseif ( $Chart == "pie")
			{	$chart = new \core\chart_pie();
			}
			elseif ( $Chart == "doughnut")
			{	$chart = new \core\chart_pie();
				$chart->set_doughnut(true);
			}
			// draw stacked bar chart
			elseif ( $Chart == "stacked")
			{	$chart = new core\chart_bar();
				$chart->set_stacked(true);
			}
			// draw bar chart - DEFAULT
			else  //if ( $Chart == "bar")
			{	$chart = new \core\chart_bar();
				$chart->set_horizontal(true);
			}
            $series = new \core\chart_series(format_string(get_string("responses", "evaluation")), $data['series']);
            $series->set_labels($data['series_labels']);
            $chart->add_series($series);
            $chart->set_labels($data['labels']);
			echo $OUTPUT->render_chart($chart); 
        }
    }

    public function excelprint_item(&$worksheet, $row_offset,
                             $xls_formats, $item,
                             $groupid, $courseid = false, $teacherid=false, $course_of_studies=false) {

        $analysed_item = $this->get_analysed($item, $groupid, $courseid, $teacherid, $course_of_studies);

        $data = $analysed_item[2];

        //frage schreiben
        $worksheet->write_string($row_offset, 0, $item->label, $xls_formats->head2);
        $worksheet->write_string($row_offset, 1, $analysed_item[1], $xls_formats->head2);
        if (is_array($data)) {
            $sizeofdata = count($data);
            for ($i = 0; $i < $sizeofdata; $i++) {
                $analysed_data = $data[$i];

                $worksheet->write_string($row_offset,
                                         $i + 2,
                                         trim($analysed_data->answertext),
                                         $xls_formats->head2);

                $worksheet->write_number($row_offset + 1,
                                         $i + 2,
                                         $analysed_data->answercount,
                                         $xls_formats->default);

                $worksheet->write_number($row_offset + 2,
                                         $i + 2,
                                         $analysed_data->quotient,
                                         $xls_formats->procent);
            }
        }
        $row_offset += 3;
        return $row_offset;
    }

    /**
     * Options for the multichoice element
     * @param stdClass $item
     * @return array
     */
    protected function get_options($item) {
        $info = $this->get_info($item);
        $presentation = explode (EVALUATION_MULTICHOICE_LINE_SEP, $info->presentation);
        $options = array();
        foreach ($presentation as $idx => $optiontext) {
            $options[$idx + 1] = format_text($optiontext, FORMAT_HTML, array('noclean' => true, 'para' => false));
        }
        if ($info->subtype === 'r' && !$this->hidenoselect($item)) {
            $options = array(0 => get_string('not_selected', 'evaluation')) + $options;
        }

        return $options;
    }

    /**
     * Adds an input element to the complete form
     *
     * This element has many options - it can be displayed as group or radio elements,
     * group of checkboxes or a dropdown list.
     *
     * @param stdClass $item
     * @param mod_evaluation_complete_form $form
     */
    public function complete_form_element($item, $form) {
        $info = $this->get_info($item);
        $name = $this->get_display_name($item);
        $class = 'multichoice-' . $info->subtype;
        $inputname = $item->typ . '_' . $item->id;
        $options = $this->get_options($item);
        $separator = !empty($info->horizontal) ? ' ' : '<br>';
        $tmpvalue = $form->get_item_value($item) ?? 0; // Used for element defaults, so must be a valid value (not null).

        // Subtypes:
        // r = radio
        // c = checkbox
        // d = dropdown.
        if ($info->subtype === 'd' || ($info->subtype === 'r' && $form->is_frozen())) {
            // Display as a dropdown in the complete form or a single value in the response view.
            $element = $form->add_form_element($item,
                    ['select', $inputname, $name, array(0 => '') + $options, array('class' => $class)],
                    false, false);
            $form->set_element_default($inputname, $tmpvalue);
            $form->set_element_type($inputname, PARAM_INT);
        } else if ($info->subtype === 'c' && $form->is_frozen()) {
            // Display list of checkbox values in the response view.
            $objs = [];
            foreach (explode(EVALUATION_MULTICHOICE_LINE_SEP, $form->get_item_value($item)) as $v) {
                $objs[] = ['static', $inputname."[$v]", '', isset($options[$v]) ? $options[$v] : ''];
            }
            $element = $form->add_form_group_element($item, 'group_'.$inputname, $name, $objs, $separator, $class);
        } else {
            // Display group or radio or checkbox elements.
            $class .= ' multichoice-' . ($info->horizontal ? 'horizontal' : 'vertical');
            $objs = [];
            if ($info->subtype === 'c') {
                // Checkboxes.
                $objs[] = ['hidden', $inputname.'[0]', 0];
                $form->set_element_type($inputname.'[0]', PARAM_INT);
                foreach ($options as $idx => $label) {
                    $objs[] = ['advcheckbox', $inputname.'['.$idx.']', '', $label, null, array(0, $idx)];
                    $form->set_element_type($inputname.'['.$idx.']', PARAM_INT);
                }
                // Span to hold the element id. The id is used for drag and drop reordering.
                $objs[] = ['static', '', '', html_writer::span('', '', ['id' => 'evaluation_item_' . $item->id])];
                $element = $form->add_form_group_element($item, 'group_'.$inputname, $name, $objs, $separator, $class);
                if ($tmpvalue) {
                    foreach (explode(EVALUATION_MULTICHOICE_LINE_SEP, $tmpvalue) as $v) {
                        $form->set_element_default($inputname.'['.$v.']', $v);
                    }
                }
            } else {
                // Radio.
                if (!array_key_exists(0, $options)) {
                    // Always add a hidden element to the group to guarantee we get a value in the submit data.
                    $objs[] = ['hidden', $inputname, 0];
                }
                foreach ($options as $idx => $label) {
                    $objs[] = ['radio', $inputname, '', $label, $idx];
                }
                // Span to hold the element id. The id is used for drag and drop reordering.
                $objs[] = ['static', '', '', html_writer::span('', '', ['id' => 'evaluation_item_' . $item->id])];
                $element = $form->add_form_group_element($item, 'group_'.$inputname, $name, $objs, $separator, $class);
                $form->set_element_default($inputname, $tmpvalue);
                $form->set_element_type($inputname, PARAM_INT);
            }
        }

        // Process 'required' rule.
        if ($item->required) {
            $elementname = $element->getName();
            $form->add_validation_rule(function($values) use ($elementname, $item) {
                $inputname = $item->typ . '_' . $item->id;
                return empty($values[$inputname]) || (is_array($values[$inputname]) && !array_filter($values[$inputname])) ?
                    array($elementname => get_string('required')) : true;
            });
        }
    }

    /**
     * Prepares value that user put in the form for storing in DB
     * @param array $value
     * @return string
     */
    public function create_value($value) {
        // Could be an array (multichoice checkbox) or single value (multichoice radio or dropdown).
        $value = is_array($value) ? $value : [$value];

        $value = array_unique(array_filter($value));
        return join(EVALUATION_MULTICHOICE_LINE_SEP, $value);
    }

    /**
     * Compares the dbvalue with the dependvalue
     *
     * @param stdClass $item
     * @param string $dbvalue is the value input by user in the format as it is stored in the db
     * @param string $dependvalue is the value that it needs to be compared against
     */
    public function compare_value($item, $dbvalue, $dependvalue) {

        if (is_array($dbvalue)) {
            $dbvalues = $dbvalue;
        } else {
            $dbvalues = explode(EVALUATION_MULTICHOICE_LINE_SEP, $dbvalue);
        }

        $info = $this->get_info($item);
        $presentation = explode (EVALUATION_MULTICHOICE_LINE_SEP, $info->presentation);
        $index = 1;
        foreach ($presentation as $pres) {
            foreach ($dbvalues as $dbval) {
                if ($dbval == $index AND trim($pres) == $dependvalue) {
                    return true;
                }
            }
            $index++;
        }
        return false;
    }

    public function get_info($item) {
        $presentation = empty($item->presentation) ? '' : $item->presentation;

        $info = new stdClass();
        //check the subtype of the multichoice
        //it can be check(c), radio(r) or dropdown(d)
        // Subtypes:
        // r = radio
        // c = checkbox
        // d = dropdown.

        $info->subtype = '';
        $info->presentation = '';
        $info->horizontal = false;

        $parts = explode(EVALUATION_MULTICHOICE_TYPE_SEP, $item->presentation);
        @list($info->subtype, $info->presentation) = $parts;
        if (!isset($info->subtype)) {
            $info->subtype = 'r';
        }

        if ($info->subtype != 'd') {
            $parts = explode(EVALUATION_MULTICHOICE_ADJUST_SEP, $info->presentation);
            @list($info->presentation, $info->horizontal) = $parts;
            if (isset($info->horizontal) AND $info->horizontal == 1) {
                $info->horizontal = true;
            } else {
                $info->horizontal = false;
            }
        }
        return $info;
    }

    public function set_ignoreempty($item, $ignoreempty=true) {
        $item->options = str_replace(EVALUATION_MULTICHOICE_IGNOREEMPTY, '', $item->options);
        if ($ignoreempty) {
            $item->options .= EVALUATION_MULTICHOICE_IGNOREEMPTY;
        }
    }

    public function ignoreempty($item) {
        if (strstr($item->options, EVALUATION_MULTICHOICE_IGNOREEMPTY)) {
            return true;
        }
        return false;
    }

    public function set_hidenoselect($item, $hidenoselect=true) {
        $item->options = str_replace(EVALUATION_MULTICHOICE_HIDENOSELECT, '', $item->options);
        if ($hidenoselect) {
            $item->options .= EVALUATION_MULTICHOICE_HIDENOSELECT;
        }
    }

    public function hidenoselect($item) {
        if (strstr($item->options, EVALUATION_MULTICHOICE_HIDENOSELECT)) {
            return true;
        }
        return false;
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

        if (!empty($data[2]) && is_array($data[2])) {
            foreach ($data[2] as $d) {
                $externaldata[] = json_encode($d);
            }
        }
        return $externaldata;
    }
}

/*
<table class="analysis itemtype_multichoice"><tr><td style="text-align:left;"> Geschlecht</td></tr></table>
<div class="chart-area" id="chart-area-61e3f07ab067961e3f07aa275e21">
    <div class="chart-image" role="presentation" aria-describedby="chart-table-data-61e3f07ab067961e3f07aa275e21"></div>
    <div class="chart-table ">
        <p class="chart-table-expand">
            <a href="#" aria-controls="chart-table-data-61e3f07ab067961e3f07aa275e21" role="button">
                Grafikdaten anzeigen
            </a>
        </p>
        <div class="chart-table-data" id="chart-table-data-61e3f07ab067961e3f07aa275e21" role="complementary" aria-expanded="false"></div>
    </div>
</div>
*/