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
 * mod_evaluation data generator.
 *
 * @package    mod_evaluation
 * @category   test
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * mod_evaluation data generator class.
 *
 * @package    mod_evaluation
 * @category   test
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/evaluation/lib.php');
        $record = (object) (array) $record;

        if (!isset($record->anonymous)) {
            $record->anonymous = EVALUATION_ANONYMOUS_YES;
        }
        if (!isset($record->email_notification)) {
            $record->email_notification = 0;
        }
        if (!isset($record->multiple_submit)) {
            $record->multiple_submit = 0;
        }
        if (!isset($record->autonumbering)) {
            $record->autonumbering = 0;
        }
        if (!isset($record->site_after_submit)) {
            $record->site_after_submit = '';
        }
        if (!isset($record->page_after_submit)) {
            $record->page_after_submit = 'This is page after submit';
        }
        if (!isset($record->page_after_submitformat)) {
            $record->page_after_submitformat = FORMAT_MOODLE;
        }
        if (!isset($record->publish_stats)) {
            $record->publish_stats = 0;
        }
        if (!isset($record->timeopen)) {
            $record->timeopen = 0;
        }
        if (!isset($record->timeclose)) {
            $record->timeclose = 0;
        }
        if (!isset($record->timemodified)) {
            $record->timemodified = time();
        }
        if (!isset($record->completionsubmit)) {
            $record->completionsubmit = 0;
        }

        // Hack to bypass draft processing of evaluation_add_instance.
        $record->page_after_submit_editor['itemid'] = false;

        return parent::create_instance($record, (array) $options);
    }

    /**
     * Create info question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_info($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('info');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'Evaluation question item ' . $position,
                        'label' => 'Evaluation label ' . $position,
                        'presentation' => $itemobj::MODE_COURSE,
                        'typ' => 'info',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                );

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create label question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_label($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('label');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'label',
                        'label' => '',
                        'presentation' => '',
                        'typ' => 'label',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                );

        if (!isset($record['presentation_editor'])) {
            $record['presentation_editor'] = array(
                    'text' => "The label $position text goes here",
                    'format' => FORMAT_HTML,
                    'itemid' => 0
            );
        }

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create multichoice question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_multichoice($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('multichoice');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'Evaluation question item ' . $position,
                        'label' => 'Evaluation label ' . $position,
                        'presentation' => '',
                        'typ' => 'multichoice',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                        'subtype' => 'r',
                        'horizontal' => 0,
                        'hidenoselect' => 1,
                        'ignoreempty' => 0,
                        'values' => "a\nb\nc\nd\ne"
                );

        $presentation = str_replace("\n", EVALUATION_MULTICHOICE_LINE_SEP, trim($record['values']));

        if ($record['horizontal'] == 1 and $record['subtype'] != 'd') {
            $presentation .= EVALUATION_MULTICHOICE_ADJUST_SEP . '1';
        }
        $record['presentation'] = $record['subtype'] . EVALUATION_MULTICHOICE_TYPE_SEP . $presentation;

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create multichoicerated question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_multichoicerated($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('multichoicerated');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'Evaluation question item ' . $position,
                        'label' => 'Evaluation label ' . $position,
                        'presentation' => '',
                        'typ' => 'multichoicerated',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                        'subtype' => 'r',
                        'horizontal' => 0,
                        'hidenoselect' => 1,
                        'ignoreempty' => 0,
                        'values' => "0/a\n1/b\n2/c\n3/d\n4/e"
                );

        $itemobj = new evaluation_item_multichoicerated();
        $presentation = $itemobj->prepare_presentation_values_save(trim($record['values']),
                EVALUATION_MULTICHOICERATED_VALUE_SEP2, EVALUATION_MULTICHOICERATED_VALUE_SEP);

        if ($record['horizontal'] == 1 and $record['subtype'] != 'd') {
            $presentation .= EVALUATION_MULTICHOICERATED_ADJUST_SEP . '1';
        }
        $record['presentation'] = $record['subtype'] . EVALUATION_MULTICHOICERATED_TYPE_SEP . $presentation;

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create numeric question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_numeric($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('numeric');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'Evaluation question item ' . $position,
                        'label' => 'Evaluation label ' . $position,
                        'presentation' => '',
                        'typ' => 'numeric',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                        'rangefrom' => '-',
                        'rangeto' => '-',
                );

        if ($record['rangefrom'] === '-' or $record['rangeto'] === '-') {
            $record['presentation'] = $record['rangefrom'] . '|' . $record['rangeto'];
        } else if ($record['rangefrom'] > $record['rangeto']) {
            $record['presentation'] = $record['rangeto'] . '|' . $record['rangefrom'];
        } else {
            $record['presentation'] = $record['rangefrom'] . '|' . $record['rangeto'];
        }

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create textarea question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_textarea($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('textarea');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'Evaluation question item ' . $position,
                        'label' => 'Evaluation label ' . $position,
                        'presentation' => '',
                        'typ' => 'textarea',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                        'itemwidth' => '40',
                        'itemheight' => '20',
                );

        $record['presentation'] = $record['itemwidth'] . '|' . $record['itemheight'];

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create textfield question item.
     *
     * @param object $evaluation evaluation record
     * @param array $record (optional) to override default values
     * @return int
     */
    public function create_item_textfield($evaluation, $record = array()) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $itemobj = evaluation_get_item_class('textfield');
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id)) + 1;

        $record = (array) $record + array(
                        'id' => 0,
                        'evaluation' => $evaluation->id,
                        'template' => 0,
                        'name' => 'Evaluation question item ' . $position,
                        'label' => 'Evaluation label ' . $position,
                        'presentation' => '',
                        'typ' => 'textfield',
                        'hasvalue' => 0,
                        'position' => $position,
                        'required' => 0,
                        'dependitem' => 0,
                        'dependvalue' => '',
                        'options' => '',
                        'itemsize' => '20',
                        'itemmaxlength' => '30',
                );

        $record['presentation'] = $record['itemsize'] . '|' . $record['itemmaxlength'];

        $itemobj->set_data((object) $record);
        return $itemobj->save_item();
    }

    /**
     * Create pagebreak.
     *
     * @param object $evaluation evaluation record
     * @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
     */
    public function create_item_pagebreak($evaluation) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        return evaluation_create_pagebreak($evaluation->id);
    }
}

