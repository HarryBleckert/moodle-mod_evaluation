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
 * print the form to add or edit a evaluation-instance
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

//It must be included from a Moodle page
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_evaluation_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $DB;

        $editoroptions = evaluation_get_editor_options();

        $mform =& $this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name', 'evaluation'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('description', 'evaluation'));



        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'timinghdr', get_string('availability'));

        $mform->addElement('date_time_selector', 'timeopen', get_string('evaluationopen', 'evaluation'),
                array('optional' => true));

        $mform->addElement('date_time_selector', 'timeclose', get_string('evaluationclose', 'evaluation'),
                array('optional' => true));



        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'evaluationhdr', get_string('questionandsubmission', 'evaluation'));


        // get participant roles
        $roles = $DB->get_records_sql("SELECT id,shortname FROM {role} WHERE id NOT IN(1,2) ORDER BY id asc");
        $participant_roles = array();
        foreach ( $roles as $role ){
            $participant_roles[$role->id] = ucfirst(trim($role->shortname));
        }
        // Add the multi-select element
        $select = $mform->addElement('select', 'participant_roles',
                ev_get_string('participant_roles'),
                $participant_roles,
                array('size' => '6')
        );
        // Make it a multi-select
        $select->setMultiple(true);
        // $mform->setType('participant_roles', PARAM_CHAR);
        // Add help button
        $mform->addHelpButton('participant_roles', 'participant_roles', 'evaluation');

        // Set default values (optional)
        $mform->setDefault('participant_roles', array("5"));

        // Add validation rules (optional)
        //$mform->addRule('participant_roles', get_string('required'), 'required', null, 'client');


        $options = array();
        $options[1] = ev_get_string('anonymous');
        $options[2] = ev_get_string('non_anonymous');
        $mform->addElement('select',
                'anonymous',
                get_string('anonymous_edit', 'evaluation'),
                $options);

        // check if there is existing responses to this evaluation
        if (is_numeric($this->_instance) and
                $this->_instance and
                $evaluation = $DB->get_record("evaluation", array("id" => $this->_instance))) {

            $completed_evaluation_count = evaluation_get_completeds_group_count($evaluation);
        } else {
            $completed_evaluation_count = false;
        }

        if ($completed_evaluation_count) {
            $multiple_submit_value = $evaluation->multiple_submit ? get_string('yes') : get_string('no');
            $mform->addElement('text',
                    'multiple_submit_static',
                    get_string('multiplesubmit', 'evaluation'),
                    array('size' => '4',
                            'disabled' => 'disabled',
                            'value' => $multiple_submit_value));
            $mform->setType('multiple_submit_static', PARAM_RAW);

            $mform->addElement('hidden', 'multiple_submit', '');
            $mform->setType('multiple_submit', PARAM_INT);
            $mform->addHelpButton('multiple_submit_static', 'multiplesubmit', 'evaluation');
        } else {
            $mform->addElement('selectyesno',
                    'multiple_submit',
                    get_string('multiplesubmit', 'evaluation'));

            $mform->addHelpButton('multiple_submit', 'multiplesubmit', 'evaluation');
        }

        $mform->addElement('selectyesno', 'email_notification', get_string('email_notification', 'evaluation'));
        $mform->addHelpButton('email_notification', 'email_notification', 'evaluation');
        $mform->setDefault('autonumbering', 1);
        $mform->addElement('selectyesno', 'autonumbering', get_string('autonumbering', 'evaluation'));
        $mform->addHelpButton('autonumbering', 'autonumbering', 'evaluation');

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'aftersubmithdr', get_string('after_submit', 'evaluation'));

        $mform->addElement('text', 'min_results', get_string('min_results_desc', 'evaluation'), 'size="5"');
        $mform->addElement('text', 'min_results_text', get_string('min_results_text_desc', 'evaluation'), 'size="5"');
        $mform->setType('min_results', PARAM_INT);
        $mform->setType('min_results_text', PARAM_INT);
        $mform->addElement('selectyesno', 'publish_stats', get_string('show_analysepage_after_submit', 'evaluation'));

        $mform->addElement('selectyesno', 'show_on_index', get_string('show_on_index', 'evaluation'));

        $mform->setType('sort_tag', PARAM_TEXT);
        $mform->addElement('text', 'sort_tag', get_string('sort_tag', 'evaluation'));
        $mform->setDefault('sort_tag','ASH');

        $mform->setType('autoreminders', PARAM_TEXT);
        $mform->setDefault('autoreminders','1');
        $mform->addElement('selectyesno', 'autoreminders', get_string('autoreminders', 'evaluation'));
        $mform->addHelpButton('autoreminders', 'autoreminders', 'evaluation');

        $mform->setType('sendername', PARAM_TEXT);
        $mform->addElement('text', 'sendername', get_string('sendername', 'evaluation'));
        $mform->setDefault('sendername','ASH Berlin (Qualitätsmanagement)');

        $mform->setType('sendermail', PARAM_TEXT);
        $mform->addElement('text', 'sendermail', get_string('sendermail', 'evaluation'));
        $mform->setDefault('sendermail','khayat@ash-berlin.eu');

        $mform->setType('signature', PARAM_TEXT);
        $mform->addElement('text', 'signature', get_string('signature', 'evaluation'));
        $mform->setDefault('signature','Das Evaluationsteam');

        $mform->addElement('editor',
                'page_after_submit_editor',
                get_string("page_after_submit", "evaluation"),
                null,
                $editoroptions);

        $mform->setType('page_after_submit_editor', PARAM_RAW);

        $mform->addElement('text',
                'site_after_submit',
                get_string('url_for_continue', 'evaluation'),
                array('size' => '64', 'maxlength' => '255'));

        $mform->setType('site_after_submit', PARAM_TEXT);
        $mform->addHelpButton('site_after_submit', 'url_for_continue', 'evaluation');

        if ($this->_course->id == SITEID) {
            $mform->addElement('header', 'globalevaluationheader', get_string('global_evaluations', 'evaluation'), 'evaluation');
            $mform->addElement('text', 'semester', "Semester", 'size="5"');
            $mform->setType('semester', PARAM_INT);
            $mform->setType('min_results_priv', PARAM_INT);
            $mform->addElement('text', 'min_results_priv', get_string('min_results_priv_desc', 'evaluation'), 'size="5"');
            $mform->addElement('textarea', 'privileged_users', get_string('privileged_users_desc', 'evaluation'),
                    'wrap="virtual" rows="8" cols="17"');
            $mform->addElement('textarea', 'filter_course_of_studies', get_string('filter_course_of_studies_desc', 'evaluation'),
                    'wrap="virtual" rows="12" cols="150"');
            $mform->addElement('textarea', 'filter_courses', get_string('filter_courses_desc', 'evaluation'),
                    'wrap="virtual" rows="12" cols="30"');
            $mform->setExpanded('globalevaluationheader');
        } else {
            $mform->addElement('hidden', 'privileged_users', '');
            $mform->addElement('hidden', 'filter_course_of_studies', '');
            $mform->addElement('hidden', 'filter_courses', '');
        }
        $mform->setType('privileged_users', PARAM_TEXT);
        $mform->setType('filter_course_of_studies', PARAM_TEXT);
        $mform->setType('filter_courses', PARAM_TEXT);
        $mform->setDefault('privileged_users', "");
        $mform->setDefault('filter_course_of_studies', "");
        $mform->setDefault('filter_courses', "");
        //$mform->addElement('header', 'globalevaluationheader', get_string('teamteaching','evaluation'), 'evaluation');
        if ($completed_evaluation_count > 3) {
            $teamteaching_value = $evaluation->teamteaching ? get_string('yes') : get_string('no');
            $mform->addElement('text',
                    'teamteaching_static',
                    get_string('teamteaching', 'evaluation'),
                    array('size' => '4',
                            'disabled' => 'disabled',
                            'value' => $teamteaching_value));
            $mform->setType('teamteaching_static', PARAM_RAW);
            $mform->addElement('hidden', 'teamteaching', '');
            $mform->setType('teamteaching', PARAM_INT);
            $mform->addHelpButton('teamteaching_static', 'teamteaching', 'evaluation');
        } else {
            $mform->setDefault('teamteaching',"1");
            $mform->addElement('selectyesno',
                    'teamteaching',
                    get_string('teamteaching', 'evaluation'));

            $mform->addHelpButton('teamteaching', 'teamteaching', 'evaluation');
        }

        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$default_values) {
        global $CFG;
        $editoroptions = evaluation_get_editor_options();

        if ($this->current->instance) {
            // editing an existing evaluation - let us prepare the added editor elements (intro done automatically)
            $draftitemid = file_get_submitted_draft_itemid('page_after_submit');
            $default_values['page_after_submit_editor']['text'] =
                    file_prepare_draft_area($draftitemid, $this->context->id,
                            'mod_evaluation', 'page_after_submit', false,
                            $editoroptions,
                            $default_values['page_after_submit']);

            $default_values['page_after_submit_editor']['format'] = $default_values['page_after_submitformat'];
            $default_values['page_after_submit_editor']['itemid'] = $draftitemid;
        } else {
            // adding a new evaluation instance
            $draftitemid = file_get_submitted_draft_itemid('page_after_submit_editor');
            $default_values['min_results'] = 3;
            $default_values['min_results_text'] = 6;
            $default_values['min_results_priv'] = 0;
            $default_values['show_on_index'] = 1;
            $default_values['semester'] = evaluation_get_current_semester();
            if ( empty($CFG->ash)) {
                $default_values['sort_tag'] = "ASH";
                $default_values['sendermail'] = "khayat@ash-berlin.eu";
                $default_values['sendername'] = "ASH Berlin (Qualitätsmanagement)";
                $default_values['autoreminders'] = 1;
                $default_values['signature'] = "Berthe Khayat und Harry Bleckert für das Evaluationsteam";
            }
            $default_values['semester'] = evaluation_get_current_semester();
            // no context yet, itemid not used
            file_prepare_draft_area($draftitemid, null, 'mod_evaluation', 'page_after_submit', false);
            $default_values['page_after_submit_editor']['text'] = '';
            $default_values['page_after_submit_editor']['format'] = editors_get_preferred_format();
            $default_values['page_after_submit_editor']['itemid'] = $draftitemid;
        }

    }

    /**
     * Allows module to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data the form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        /* unset $_SESSION["EvaluationsID"] to reset stored evaluation data */
        unset($_SESSION["EvaluationsID"]);

        if (isset($data->page_after_submit_editor)) {
            $data->page_after_submitformat = $data->page_after_submit_editor['format'];
            $data->page_after_submit = $data->page_after_submit_editor['text'];

            if (!empty($data->completionunlocked)) {
                // Turn off completion settings if the checkboxes aren't ticked
                $autocompletion = !empty($data->completion) &&
                        $data->completion == COMPLETION_TRACKING_AUTOMATIC;
                if (!$autocompletion || empty($data->completionsubmit)) {
                    $data->completionsubmit = 0;
                }
            }
        }
        // patched by Harry
        if (isset($data->filter_course_of_studies) and !empty($data->filter_course_of_studies)) {
            $data->filter_course_of_studies = str_replace("\r", "", $data->filter_course_of_studies);
            $selected = explode("\n", $data->filter_course_of_studies);
            sort($selected);
            $sorted = array();
            foreach ($selected as $filter_course_of_studies) {
                if (!empty($filter_course_of_studies)) {
                    $sorted[] = $filter_course_of_studies;
                }
            }
            $data->filter_course_of_studies = implode("\n", $sorted);
        } else {
            $data->filter_course_of_studies = "";
        }
        if (isset($data->filter_courses) and !empty($data->filter_courses)) {
            $data->filter_courses = str_replace("\r", "", $data->filter_courses);
            $selected = explode("\n", $data->filter_courses);
            sort($selected);
            $sorted = array();
            foreach ($selected as $filter_courses) {
                if (!empty($filter_courses)) {
                    $sorted[] = $filter_courses;
                }
            }
            $data->filter_courses = implode("\n", $sorted);
        } else {
            $data->filter_courses = "";
        }

        if (isset($data->privileged_users) and !empty($data->privileged_users)) {
            $data->privileged_users = str_replace("\r", "", $data->privileged_users);
            $selected = explode("\n", $data->privileged_users);
            sort($selected);
            $sorted = array();
            foreach ($selected as $privileged_user) {
                if (!empty($privileged_user)) {
                    $sorted[] = $privileged_user;
                }
            }
            $data->privileged_users = implode("\n", $sorted);
        } else {
            $data->privileged_users = "";
        }
        // end patch
    }

    /**
     * Enforce validation rules here
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array
     **/
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check open and close times are consistent.
        if ($data['timeopen'] && $data['timeclose'] &&
                $data['timeclose'] < $data['timeopen']) {
            $errors['timeclose'] = get_string('closebeforeopen', 'evaluation');
        }
        if (count($data['participant_roles']) < 2) {
            $errors['participant_roles'] = get_string('rols_is_required', 'evaluation');
        }
        return $errors;
    }

    public function add_completion_rules() {
        $mform =& $this->_form;

        $mform->addElement('checkbox',
                'completionsubmit',
                '',
                get_string('completionsubmit', 'evaluation'));
        // Enable this completion rule by default.
        $mform->setDefault('completionsubmit', 1);
        return array('completionsubmit');
    }

    public function completion_rule_enabled($data) {
        return !empty($data['completionsubmit']);
    }
}
