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
 * Contains class mod_evaluation_course_map_form
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Form for mapping courses to the evaluation
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_department_select_form extends moodleform {
    /** @var moodle_url */
    protected $action;
    /** @var mod_evaluation_structure $evaluationstructure */
    protected $evaluationstructure;

    /**
     * Constructor
     *
     * @param string|moodle_url $action the action attribute for the form
     * @param mod_evaluation_structure $evaluationstructure
     * @param bool $editable
     */
    public function __construct($action, mod_evaluation_structure $evaluationstructure, $editable = true) {
        $this->action = new moodle_url($action, ['department' => null]);
        $this->evaluationstructure = $evaluationstructure;
        parent::__construct($action, null, 'post', '', ['id' => 'evaluation_departments_filter'], $editable);
    }

    /**
     * Definition of the form
     */
    public function definition() {
        $mform = $this->_form;
        $evaluationstructure = $this->evaluationstructure;

        $mform->disable_form_change_checker();
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (!$this->_form->_freezeAll && ($departments = $evaluationstructure->get_completed_departments()) && count($departments)) {
            $elements = [];
            $elements[] = $mform->createElement('autocomplete', 'department',
                    get_string('filter_by_department', 'evaluation'),
                    ['' => get_string('fulllistofdepartments', 'evaluation')] + $departments);
            $elements[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
            if ($evaluationstructure->get_department()) {
                $elements[] = $mform->createElement('static', 'showall', '',
                        html_writer::link($this->action, get_string('show_all', 'evaluation')));
            }
            $mform->addGroup($elements, 'departmentfilter', get_string('filter_by_department',
                    'evaluation'), array(' '), false);
        }
        $this->set_data(['department' => $evaluationstructure->get_department(),
                'id' => $evaluationstructure->get_cm()->id]);
    }
}
