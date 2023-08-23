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
class mod_evaluation_filters_select_form extends moodleform {
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
        $this->action = new moodle_url($action, ['course_of_studies' => null]);
        $this->evaluationstructure = $evaluationstructure;
        parent::__construct($action, null, 'post', '', ['id' => 'evaluation_filters'], $editable);
    }

    /**
     * Definition of the form
     */
    public function definition() {
        $mform = $this->_form;
        $evaluationstructure = $this->evaluationstructure;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        if (!$this->_form->_freezeAll ) 
		{   
			if ( !$evaluationstructure->get_courseid() AND ($courses = $evaluationstructure->get_completed_course_of_studies()) && count($courses) ) 
            {	$elements = [];
				$elements[] = $mform->createElement('autocomplete', 'course_of_studies', get_string('filter_by_course_of_studies', 'evaluation'),
					['' => get_string('fulllistofstudies','evaluation')] + $courses);
				
				if ($evaluationstructure->get_course_of_studies()) {
					$elements[] = $mform->createElement('static', 'showall', '',
						html_writer::link($this->action, get_string('show_all', 'evaluation')));
				}
				if (1|| defined('BEHAT_SITE_RUNNING')) 
				{	// TODO MDL-53734 remove this - behat does not recognise autocomplete element inside a group.
					foreach ($elements as $element) {
						$mform->addElement($element);
					}
				} else {
					$mform->addGroup($elements, 'studiesfilter', get_string('filter_by_course_of_studies', 'evaluation'), array(' '), false);
				}
			}
			if ( !$evaluationstructure->get_course_of_studies() AND ($courses = $evaluationstructure->get_completed_courses()) && count($courses) )
			{	$elements = [];
				$elements[] = $mform->createElement('autocomplete', 'courseid', get_string('filter_by_course', 'evaluation'),
					['' => get_string('fulllistofcourses')] + $courses);          
				if ($evaluationstructure->get_courseid()) 
				{	$elements[] = $mform->createElement('static', 'showall', '',
					html_writer::link($this->action, get_string('show_all', 'evaluation')));
				}
				if (1|| defined('BEHAT_SITE_RUNNING')) 
				{	// TODO MDL-53734 remove this - behat does not recognise autocomplete element inside a group.
					foreach ($elements as $element) {
						$mform->addElement($element);
					}
				} else {
					$mform->addGroup($elements, 'studiesfilter', get_string('filter_by_course_of_studies', 'evaluation'), array(' '), false);
				}
			}
			if ( ($courses = $evaluationstructure->get_completed_teachers()) && count($courses) ) 
			{	$elements = [];
				$elements[] = $mform->createElement('autocomplete', 'teacherid', get_string('filter_by_teacher', 'evaluation'),
					['' => get_string('fulllistofteachers','evaluation')] + $courses);
				$elements[] = $mform->createElement('submit', 'submitbutton', get_string('filter'));
				if ($evaluationstructure->get_teacherid()) {
					$elements[] = $mform->createElement('static', 'showall', '',
						html_writer::link($this->action, get_string('show_all', 'evaluation')));
				}
				if (1|| defined('BEHAT_SITE_RUNNING')) {
					// TODO MDL-53734 remove this - behat does not recognise autocomplete element inside a group.
					foreach ($elements as $element) {
						$mform->addElement($element);
					}
				} else {
					$mform->addGroup($elements, 'teacherfilter', get_string('filter_by_teacher', 'evaluation'), array(' '), false);
				}
			}
			$mform->addElement($mform->createElement('submit', 'submitbutton', get_string('filter')));
        }
		$this->set_data(['id' => $evaluationstructure->get_cm()->id, 'course_of_studies' => $evaluationstructure->get_course_of_studies(),  //]);
						 'courseid' => $evaluationstructure->get_courseid, 'teacherid' => $evaluationstructure->get_teacherid()]);
    }
}
