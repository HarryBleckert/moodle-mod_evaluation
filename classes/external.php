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
 * Evaluation external API
 *
 * @package    mod_evaluation
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

use mod_evaluation\external\evaluation_summary_exporter;
use mod_evaluation\external\evaluation_completedtmp_exporter;
use mod_evaluation\external\evaluation_item_exporter;
use mod_evaluation\external\evaluation_valuetmp_exporter;
use mod_evaluation\external\evaluation_value_exporter;
use mod_evaluation\external\evaluation_completed_exporter;

/**
 * Evaluation external functions
 *
 * @package    mod_evaluation
 * @category   external
 * @copyright  2017 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.3
 */
class mod_evaluation_external extends external_api {

    /**
     * Describes the parameters for get_evaluations_by_courses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_evaluations_by_courses_parameters() {
        return new external_function_parameters (
            array(
                'courseids' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_DEFAULT, array()
                ),
            )
        );
    }

    /**
     * Returns a list of evaluations in a provided list of courses.
     * If no list is provided all evaluations that the user can view will be returned.
     *
     * @param array $courseids course ids
     * @return array of warnings and evaluations
     * @since Moodle 3.3
     */
    public static function get_evaluations_by_courses($courseids = array()) {
        global $PAGE;

        $warnings = array();
        $returnedevaluations = array();

        $params = array(
            'courseids' => $courseids,
        );
        $params = self::validate_parameters(self::get_evaluations_by_courses_parameters(), $params);

        $mycourses = array();
        if (empty($params['courseids'])) {
            $mycourses = enrol_get_my_courses();
            $params['courseids'] = array_keys($mycourses);
        }

        // Ensure there are courseids to loop through.
        if (!empty($params['courseids'])) {

            list($courses, $warnings) = external_util::validate_courses($params['courseids'], $mycourses);
            $output = $PAGE->get_renderer('core');

            // Get the evaluations in this course, this function checks users visibility permissions.
            // We can avoid then additional validate_context calls.
            $evaluations = get_all_instances_in_courses("evaluation", $courses);
            foreach ($evaluations as $evaluation) {

                $context = context_module::instance($evaluation->coursemodule);

                // Remove fields that are not from the evaluation (added by get_all_instances_in_courses).
                unset($evaluation->coursemodule, $evaluation->context, $evaluation->visible, $evaluation->section, $evaluation->groupmode,
                        $evaluation->groupingid);

                // Check permissions.
                if (!has_capability('mod/evaluation:edititems', $context)) {
                    // Don't return the optional properties.
                    $properties = evaluation_summary_exporter::properties_definition();
                    foreach ($properties as $property => $config) {
                        if (!empty($config['optional'])) {
                            unset($evaluation->{$property});
                        }
                    }
                }
                $exporter = new evaluation_summary_exporter($evaluation, array('context' => $context));
                $returnedevaluations[] = $exporter->export($output);
            }
        }

        $result = array(
            'evaluations' => $returnedevaluations,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_evaluations_by_courses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_evaluations_by_courses_returns() {
        return new external_single_structure(
            array(
                'evaluations' => new external_multiple_structure(
                    evaluation_summary_exporter::get_read_structure()
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Utility function for validating a evaluation.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $courseid courseid course where user completes the evaluation (for site evaluations only)
     * @return array containing the evaluation, evaluation course, context, course module and the course where is being completed.
     * @throws moodle_exception
     * @since  Moodle 3.3
     */
    protected static function validate_evaluation($evaluationid, $courseid = 0) {
        global $DB, $USER;

        // Request and permission validation.
        $evaluation = $DB->get_record('evaluation', array('id' => $evaluationid), '*', MUST_EXIST);
        list($evaluationcourse, $cm) = get_course_and_cm_from_instance($evaluation, 'evaluation');

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Set default completion course.
        $completioncourse = (object) array('id' => 0);
        if ($evaluationcourse->id == SITEID && $courseid) {
            $completioncourse = get_course($courseid);
            self::validate_context(context_course::instance($courseid));

            $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $courseid);
            if (!$evaluationcompletion->check_course_is_mapped()) {
                throw new moodle_exception('cannotaccess', 'mod_evaluation');
            }
        }

        return array($evaluation, $evaluationcourse, $cm, $context, $completioncourse);
    }

    /**
     * Utility function for validating access to evaluation.
     *
     * @param  stdClass   $evaluation evaluation object
     * @param  stdClass   $course   course where user completes the evaluation (for site evaluations only)
     * @param  stdClass   $cm       course module
     * @param  stdClass   $context  context object
     * @throws moodle_exception
     * @return mod_evaluation_completion evaluation completion instance
     * @since  Moodle 3.3
     */
    protected static function validate_evaluation_access($evaluation, $course, $cm, $context, $checksubmit = false) {
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $course->id);

        if (!$evaluationcompletion->can_complete()) {
            throw new required_capability_exception($context, 'mod/evaluation:complete', 'nopermission', '');
        }

        if (!$evaluationcompletion->is_open()) {
            throw new moodle_exception('evaluation_is_not_open', 'evaluation');
        }

        if ($evaluationcompletion->is_empty()) {
            throw new moodle_exception('no_items_available_yet', 'evaluation');
        }

        if ($checksubmit && !$evaluationcompletion->can_submit()) {
            throw new moodle_exception('this_evaluation_is_already_submitted', 'evaluation');
        }
        return $evaluationcompletion;
    }

    /**
     * Describes the parameters for get_evaluation_access_information.
     *
     * @return external_external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_evaluation_access_information_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id.'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Return access information for a given evaluation.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and the access information
     * @since Moodle 3.3
     * @throws  moodle_exception
     */
    public static function get_evaluation_access_information($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array(
            'evaluationid' => $evaluationid,
            'courseid' => $courseid,
        );
        $params = self::validate_parameters(self::get_evaluation_access_information_parameters(), $params);

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        $result = array();
        // Capabilities first.
        $result['canviewanalysis'] = $evaluationcompletion->can_view_analysis();
        $result['cancomplete'] = $evaluationcompletion->can_complete();
        $result['cansubmit'] = $evaluationcompletion->can_submit();
        $result['candeletesubmissions'] = has_capability('mod/evaluation:deletesubmissions', $context);
        $result['canviewreports'] = has_capability('mod/evaluation:viewreports', $context);
        $result['canedititems'] = has_capability('mod/evaluation:edititems', $context);

        // Status information.
        $result['isempty'] = $evaluationcompletion->is_empty();
        $result['isopen'] = $evaluationcompletion->is_open();
        $anycourse = ($course->id == SITEID);
        $result['isalreadysubmitted'] = $evaluationcompletion->is_already_submitted($anycourse);
        $result['isanonymous'] = $evaluationcompletion->is_anonymous();

        $result['warnings'] = [];
        return $result;
    }

    /**
     * Describes the get_evaluation_access_information return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_evaluation_access_information_returns() {
        return new external_single_structure(
            array(
                'canviewanalysis' => new external_value(PARAM_BOOL, 'Whether the user can view the analysis or not.'),
                'cancomplete' => new external_value(PARAM_BOOL, 'Whether the user can complete the evaluation or not.'),
                'cansubmit' => new external_value(PARAM_BOOL, 'Whether the user can submit the evaluation or not.'),
                'candeletesubmissions' => new external_value(PARAM_BOOL, 'Whether the user can delete submissions or not.'),
                'canviewreports' => new external_value(PARAM_BOOL, 'Whether the user can view the evaluation reports or not.'),
                'canedititems' => new external_value(PARAM_BOOL, 'Whether the user can edit evaluation items or not.'),
                'isempty' => new external_value(PARAM_BOOL, 'Whether the evaluation has questions or not.'),
                'isopen' => new external_value(PARAM_BOOL, 'Whether the evaluation has active access time restrictions or not.'),
                'isalreadysubmitted' => new external_value(PARAM_BOOL, 'Whether the evaluation is already submitted or not.'),
                'isanonymous' => new external_value(PARAM_BOOL, 'Whether the evaluation is anonymous or not.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for view_evaluation.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function view_evaluation_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'moduleviewed' => new external_value(PARAM_BOOL, 'If we need to mark the module as viewed for completion',
                    VALUE_DEFAULT, false),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Trigger the course module viewed event and update the module completion status.
     *
     * @param int $evaluationid evaluation instance id
     * @param bool $moduleviewed If we need to mark the module as viewed for completion
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function view_evaluation($evaluationid, $moduleviewed = false, $courseid = 0) {

        $params = array('evaluationid' => $evaluationid, 'moduleviewed' => $moduleviewed, 'courseid' => $courseid);
        $params = self::validate_parameters(self::view_evaluation_parameters(), $params);
        $warnings = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        // Trigger module viewed event.
        $evaluationcompletion->trigger_module_viewed();
        if ($params['moduleviewed']) {
            if (!$evaluationcompletion->is_open()) {
                throw new moodle_exception('evaluation_is_not_open', 'evaluation');
            }
            // Mark activity viewed for completion-tracking.
            $evaluationcompletion->set_module_viewed();
        }

        $result = array(
            'status' => true,
            'warnings' => $warnings,
        );
        return $result;
    }

    /**
     * Describes the view_evaluation return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function view_evaluation_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_current_completed_tmp.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_current_completed_tmp_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Returns the temporary completion record for the current user.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and status result
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_current_completed_tmp($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_current_completed_tmp_parameters(), $params);
        $warnings = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        if ($completed = $evaluationcompletion->get_current_completed_tmp()) {
            $exporter = new evaluation_completedtmp_exporter($completed);
            return array(
                'evaluation' => $exporter->export($PAGE->get_renderer('core')),
                'warnings' => $warnings,
            );
        }
        throw new moodle_exception('not_started', 'evaluation');
    }

    /**
     * Describes the get_current_completed_tmp return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_current_completed_tmp_returns() {
        return new external_single_structure(
            array(
                'evaluation' => evaluation_completedtmp_exporter::get_read_structure(),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_items.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_items_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Returns the items (questions) in the given evaluation.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and evaluations
     * @since Moodle 3.3
     */
    public static function get_items($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_items_parameters(), $params);
        $warnings = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);

        $evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $completioncourse->id);
        $returneditems = array();
        if ($items = $evaluationstructure->get_items()) {
            foreach ($items as $item) {
                $itemnumber = empty($item->itemnr) ? null : $item->itemnr;
                unset($item->itemnr);   // Added by the function, not part of the record.
                $exporter = new evaluation_item_exporter($item, array('context' => $context, 'itemnumber' => $itemnumber));
                $returneditems[] = $exporter->export($PAGE->get_renderer('core'));
            }
        }

        $result = array(
            'items' => $returneditems,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_items return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_items_returns() {
        return new external_single_structure(
            array(
                'items' => new external_multiple_structure(
                    evaluation_item_exporter::get_read_structure()
                ),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for launch_evaluation.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function launch_evaluation_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Starts or continues a evaluation submission
     *
     * @param array $evaluationid evaluation instance id
     * @param int $courseid course where user completes a evaluation (for site evaluations only).
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function launch_evaluation($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::launch_evaluation_parameters(), $params);
        $warnings = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        // Check we can do a new submission (or continue an existing).
        $evaluationcompletion = self::validate_evaluation_access($evaluation, $completioncourse, $cm, $context, true);

        $gopage = $evaluationcompletion->get_resume_page();
        if ($gopage === null) {
            $gopage = -1; // Last page.
        }

        $result = array(
            'gopage' => $gopage,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the launch_evaluation return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function launch_evaluation_returns() {
        return new external_single_structure(
            array(
                'gopage' => new external_value(PARAM_INT, 'The next page to go (-1 if we were already in the last page). 0 for first page.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_page_items.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_page_items_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'page' => new external_value(PARAM_INT, 'The page to get starting by 0'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Get a single evaluation page items.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $page the page to get starting by 0
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function get_page_items($evaluationid, $page, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'page' => $page, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_page_items_parameters(), $params);
        $warnings = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);

        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        $page = $params['page'];
        $pages = $evaluationcompletion->get_pages();
        $pageitems = $pages[$page];
        $hasnextpage = $page < count($pages) - 1; // Until we complete this page we can not trust get_next_page().
        $hasprevpage = $page && ($evaluationcompletion->get_previous_page($page, false) !== null);

        $returneditems = array();
        foreach ($pageitems as $item) {
            $itemnumber = empty($item->itemnr) ? null : $item->itemnr;
            unset($item->itemnr);   // Added by the function, not part of the record.
            $exporter = new evaluation_item_exporter($item, array('context' => $context, 'itemnumber' => $itemnumber));
            $returneditems[] = $exporter->export($PAGE->get_renderer('core'));
        }

        $result = array(
            'items' => $returneditems,
            'hasprevpage' => $hasprevpage,
            'hasnextpage' => $hasnextpage,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_page_items return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_page_items_returns() {
        return new external_single_structure(
            array(
                'items' => new external_multiple_structure(
                    evaluation_item_exporter::get_read_structure()
                ),
                'hasprevpage' => new external_value(PARAM_BOOL, 'Whether is a previous page.'),
                'hasnextpage' => new external_value(PARAM_BOOL, 'Whether there are more pages.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for process_page.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function process_page_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id.'),
                'page' => new external_value(PARAM_INT, 'The page being processed.'),
                'responses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_NOTAGS, 'The response name (usually type[index]_id).'),
                            'value' => new external_value(PARAM_RAW, 'The response value.'),
                        )
                    ), 'The data to be processed.', VALUE_DEFAULT, array()
                ),
                'goprevious' => new external_value(PARAM_BOOL, 'Whether we want to jump to previous page.', VALUE_DEFAULT, false),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Process a jump between pages.
     *
     * @param array $evaluationid evaluation instance id
     * @param array $page the page being processed
     * @param array $responses the responses to be processed
     * @param bool $goprevious whether we want to jump to previous page
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function process_page($evaluationid, $page, $responses = [], $goprevious = false, $courseid = 0) {
        global $USER, $SESSION;

        $params = array('evaluationid' => $evaluationid, 'page' => $page, 'responses' => $responses, 'goprevious' => $goprevious,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::process_page_parameters(), $params);
        $warnings = array();
        $siteaftersubmit = $completionpagecontents = '';

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        // Check we can do a new submission (or continue an existing).
        $evaluationcompletion = self::validate_evaluation_access($evaluation, $completioncourse, $cm, $context, true);

        // Create the $_POST object required by the evaluation question engine.
        $_POST = array();
        foreach ($responses as $response) {
            // First check if we are handling array parameters.
            if (preg_match('/(.+)\[(.+)\]$/', $response['name'], $matches)) {
                $_POST[$matches[1]][$matches[2]] = $response['value'];
            } else {
                $_POST[$response['name']] = $response['value'];
            }
        }
        // Force fields.
        $_POST['id'] = $cm->id;
        $_POST['courseid'] = $courseid;
        $_POST['gopage'] = $params['page'];
        $_POST['_qf__mod_evaluation_complete_form'] = 1;

        // Determine where to go, backwards or forward.
        if (!$params['goprevious']) {
            $_POST['gonextpage'] = 1;   // Even if we are saving values we need this set.
            if ($evaluationcompletion->get_next_page($params['page'], false) === null) {
                $_POST['savevalues'] = 1;   // If there is no next page, it means we are finishing the evaluation.
            }
        }

        // Ignore sesskey (deep in some APIs), the request is already validated.
        $USER->ignoresesskey = true;
        evaluation_init_evaluation_session();
        $SESSION->evaluation->is_started = true;

        $evaluationcompletion->process_page($params['page'], $params['goprevious']);
        $completed = $evaluationcompletion->just_completed();
        if ($completed) {
            $jumpto = 0;
            if ($evaluation->page_after_submit) {
                $completionpagecontents = $evaluationcompletion->page_after_submit();
            }

            if ($evaluation->site_after_submit) {
                $siteaftersubmit = evaluation_encode_target_url($evaluation->site_after_submit);
            }
        } else {
            $jumpto = $evaluationcompletion->get_jumpto();
        }

        $result = array(
            'jumpto' => $jumpto,
            'completed' => $completed,
            'completionpagecontents' => $completionpagecontents,
            'siteaftersubmit' => $siteaftersubmit,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the process_page return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function process_page_returns() {
        return new external_single_structure(
            array(
                'jumpto' => new external_value(PARAM_INT, 'The page to jump to.'),
                'completed' => new external_value(PARAM_BOOL, 'If the user completed the evaluation.'),
                'completionpagecontents' => new external_value(PARAM_RAW, 'The completion page contents.'),
                'siteaftersubmit' => new external_value(PARAM_RAW, 'The link (could be relative) to show after submit.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_analysis.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_analysis_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'groupid' => new external_value(PARAM_INT, 'Group id, 0 means that the function will determine the user group',
                                                VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves the evaluation analysis.
     *
     * @param array $evaluationid evaluation instance id
     * @param int $groupid group id, 0 means that the function will determine the user group
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function get_analysis($evaluationid, $groupid = 0, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'groupid' => $groupid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_analysis_parameters(), $params);
        $warnings = $itemsdata = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);

        // Check permissions.
        $evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $completioncourse->id);
        if (!$evaluationstructure->can_view_analysis()) {
            throw new required_capability_exception($context, 'mod/evaluation:viewanalysepage', 'nopermission', '');
        }

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        // Summary data.
        $summary = new mod_evaluation\output\summary($evaluationstructure, $groupid);
        $summarydata = $summary->export_for_template($PAGE->get_renderer('core'));

        $checkanonymously = true;
        if ($groupid > 0 AND $evaluation->anonymous == EVALUATION_ANONYMOUS_YES) {
            $completedcount = $evaluationstructure->count_completed_responses($groupid);
            if ($completedcount < EVALUATION_MIN_ANONYMOUS_COUNT_IN_GROUP) {
                $checkanonymously = false;
            }
        }

        if ($checkanonymously) {
            // Get the items of the evaluation.
            $items = $evaluationstructure->get_items(true);
            foreach ($items as $item) {
                $itemobj = evaluation_get_item_class($item->typ);
                $itemnumber = empty($item->itemnr) ? null : $item->itemnr;
                unset($item->itemnr);   // Added by the function, not part of the record.
                $exporter = new evaluation_item_exporter($item, array('context' => $context, 'itemnumber' => $itemnumber));

                $itemsdata[] = array(
                    'item' => $exporter->export($PAGE->get_renderer('core')),
                    'data' => $itemobj->get_analysed_for_external($item, $groupid),
                );
            }
        } else {
            $warnings[] = array(
                'item' => 'evaluation',
                'itemid' => $evaluation->id,
                'warningcode' => 'insufficientresponsesforthisgroup',
                'message' => s(get_string('insufficient_responses_for_this_group', 'evaluation'))
            );
        }

        $result = array(
            'completedcount' => $summarydata->completedcount,
            'itemscount' => $summarydata->itemscount,
            'itemsdata' => $itemsdata,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_analysis return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_analysis_returns() {
        return new external_single_structure(
            array(
            'completedcount' => new external_value(PARAM_INT, 'Number of completed submissions.'),
            'itemscount' => new external_value(PARAM_INT, 'Number of items (questions).'),
            'itemsdata' => new external_multiple_structure(
                new external_single_structure(
                    array(
                        'item' => evaluation_item_exporter::get_read_structure(),
                        'data' => new external_multiple_structure(
                            new external_value(PARAM_RAW, 'The analysis data (can be json encoded)')
                        ),
                    )
                )
            ),
            'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_unfinished_responses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_unfinished_responses_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id.'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves responses from the current unfinished attempt.
     *
     * @param array $evaluationid evaluation instance id
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and launch information
     * @since Moodle 3.3
     */
    public static function get_unfinished_responses($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_unfinished_responses_parameters(), $params);
        $warnings = $itemsdata = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        $responses = array();
        $unfinished = $evaluationcompletion->get_unfinished_responses();
        foreach ($unfinished as $u) {
            $exporter = new evaluation_valuetmp_exporter($u);
            $responses[] = $exporter->export($PAGE->get_renderer('core'));
        }

        $result = array(
            'responses' => $responses,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_unfinished_responses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_unfinished_responses_returns() {
        return new external_single_structure(
            array(
            'responses' => new external_multiple_structure(
                evaluation_valuetmp_exporter::get_read_structure()
            ),
            'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_finished_responses.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_finished_responses_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id.'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves responses from the last finished attempt.
     *
     * @param array $evaluationid evaluation instance id
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and the responses
     * @since Moodle 3.3
     */
    public static function get_finished_responses($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_finished_responses_parameters(), $params);
        $warnings = $itemsdata = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        $responses = array();
        // Load and get the responses from the last completed evaluation.
        $evaluationcompletion->find_last_completed();
        $unfinished = $evaluationcompletion->get_finished_responses();
        foreach ($unfinished as $u) {
            $exporter = new evaluation_value_exporter($u);
            $responses[] = $exporter->export($PAGE->get_renderer('core'));
        }

        $result = array(
            'responses' => $responses,
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_finished_responses return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_finished_responses_returns() {
        return new external_single_structure(
            array(
            'responses' => new external_multiple_structure(
                evaluation_value_exporter::get_read_structure()
            ),
            'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_non_respondents.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_non_respondents_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'groupid' => new external_value(PARAM_INT, 'Group id, 0 means that the function will determine the user group.',
                                                VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_ALPHA, 'Sort param, must be firstname, lastname or lastaccess (default).',
                                                VALUE_DEFAULT, 'lastaccess'),
                'page' => new external_value(PARAM_INT, 'The page of records to return.', VALUE_DEFAULT, 0),
                'perpage' => new external_value(PARAM_INT, 'The number of records to return per page.', VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves a list of students who didn't submit the evaluation.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $groupid Group id, 0 means that the function will determine the user group'
     * @param str $sort sort param, must be firstname, lastname or lastaccess (default)
     * @param int $page the page of records to return
     * @param int $perpage the number of records to return per page
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and users ids
     * @since Moodle 3.3
     */
    public static function get_non_respondents($evaluationid, $groupid = 0, $sort = 'lastaccess', $page = 0, $perpage = 0,
            $courseid = 0) {

        global $CFG;
        require_once($CFG->dirroot . '/mod/evaluation/lib.php');

        $params = array('evaluationid' => $evaluationid, 'groupid' => $groupid, 'sort' => $sort, 'page' => $page,
            'perpage' => $perpage, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_non_respondents_parameters(), $params);
        $warnings = $nonrespondents = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);
        $completioncourseid = $evaluationcompletion->get_courseid();

        if ($evaluation->anonymous != EVALUATION_ANONYMOUS_NO || $evaluation->course == SITEID) {
            throw new moodle_exception('anonymous', 'evaluation');
        }

        // Check permissions.
        require_capability('mod/evaluation:viewreports', $context);

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        if ($params['sort'] !== 'firstname' && $params['sort'] !== 'lastname' && $params['sort'] !== 'lastaccess') {
            throw new invalid_parameter_exception('Invalid sort param, must be firstname, lastname or lastaccess.');
        }

        // Check if we are page filtering.
        if ($params['perpage'] == 0) {
            $page = $params['page'];
            $perpage = EVALUATION_DEFAULT_PAGE_COUNT;
        } else {
            $perpage = $params['perpage'];
            $page = $perpage * $params['page'];
        }
        $users = evaluation_get_incomplete_users($cm, $groupid, $params['sort'], $page, $perpage, true);
        foreach ($users as $user) {
            $nonrespondents[] = [
                'courseid' => $completioncourseid,
                'userid'   => $user->id,
                'fullname' => fullname($user),
                'started'  => $user->evaluationstarted
            ];
        }

        $result = array(
            'users' => $nonrespondents,
            'total' => evaluation_count_incomplete_users($cm, $groupid),
            'warnings' => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_non_respondents return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_non_respondents_returns() {
        return new external_single_structure(
            array(
                'users' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid' => new external_value(PARAM_INT, 'Course id'),
                            'userid' => new external_value(PARAM_INT, 'The user id'),
                            'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                            'started' => new external_value(PARAM_BOOL, 'If the user has started the attempt'),
                        )
                    )
                ),
                'total' => new external_value(PARAM_INT, 'Total number of non respondents'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_responses_analysis.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_responses_analysis_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'groupid' => new external_value(PARAM_INT, 'Group id, 0 means that the function will determine the user group',
                                                VALUE_DEFAULT, 0),
                'page' => new external_value(PARAM_INT, 'The page of records to return.', VALUE_DEFAULT, 0),
                'perpage' => new external_value(PARAM_INT, 'The number of records to return per page', VALUE_DEFAULT, 0),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Return the evaluation user responses.
     *
     * @param int $evaluationid evaluation instance id
     * @param int $groupid Group id, 0 means that the function will determine the user group
     * @param int $page the page of records to return
     * @param int $perpage the number of records to return per page
     * @param int $courseid course where user completes the evaluation (for site evaluations only)
     * @return array of warnings and users attemps and responses
     * @throws moodle_exception
     * @since Moodle 3.3
     */
    public static function get_responses_analysis($evaluationid, $groupid = 0, $page = 0, $perpage = 0, $courseid = 0) {

        $params = array('evaluationid' => $evaluationid, 'groupid' => $groupid, 'page' => $page, 'perpage' => $perpage,
            'courseid' => $courseid);
        $params = self::validate_parameters(self::get_responses_analysis_parameters(), $params);
        $warnings = $itemsdata = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);

        // Check permissions.
        require_capability('mod/evaluation:viewreports', $context);

        if (!empty($params['groupid'])) {
            $groupid = $params['groupid'];
            // Determine is the group is visible to user.
            if (!groups_group_visible($groupid, $course, $cm)) {
                throw new moodle_exception('notingroup');
            }
        } else {
            // Check to see if groups are being used here.
            if ($groupmode = groups_get_activity_groupmode($cm)) {
                $groupid = groups_get_activity_group($cm);
                // Determine is the group is visible to user (this is particullary for the group 0 -> all groups).
                if (!groups_group_visible($groupid, $course, $cm)) {
                    throw new moodle_exception('notingroup');
                }
            } else {
                $groupid = 0;
            }
        }

        $evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $completioncourse->id);
        $responsestable = new mod_evaluation_responses_table($evaluationstructure, $groupid);
        // Ensure responses number is correct prior returning them.
        $evaluationstructure->shuffle_anonym_responses();
        $anonresponsestable = new mod_evaluation_responses_anon_table($evaluationstructure, $groupid);

        $result = array(
            'attempts'          => $responsestable->export_external_structure($params['page'], $params['perpage']),
            'totalattempts'     => $responsestable->get_total_responses_count(),
            'anonattempts'      => $anonresponsestable->export_external_structure($params['page'], $params['perpage']),
            'totalanonattempts' => $anonresponsestable->get_total_responses_count(),
            'warnings'       => $warnings
        );
        return $result;
    }

    /**
     * Describes the get_responses_analysis return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_responses_analysis_returns() {
        $responsestructure = new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Response id'),
                    'name' => new external_value(PARAM_RAW, 'Response name'),
                    'printval' => new external_value(PARAM_RAW, 'Response ready for output'),
                    'rawval' => new external_value(PARAM_RAW, 'Response raw value'),
                )
            )
        );

        return new external_single_structure(
            array(
                'attempts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Completed id'),
                            'courseid' => new external_value(PARAM_INT, 'Course id'),
                            'userid' => new external_value(PARAM_INT, 'User who responded'),
                            'timemodified' => new external_value(PARAM_INT, 'Time modified for the response'),
                            'fullname' => new external_value(PARAM_TEXT, 'User full name'),
                            'responses' => $responsestructure
                        )
                    )
                ),
                'totalattempts' => new external_value(PARAM_INT, 'Total responses count.'),
                'anonattempts' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Completed id'),
                            'courseid' => new external_value(PARAM_INT, 'Course id'),
                            'number' => new external_value(PARAM_INT, 'Response number'),
                            'responses' => $responsestructure
                        )
                    )
                ),
                'totalanonattempts' => new external_value(PARAM_INT, 'Total anonymous responses count.'),
                'warnings' => new external_warnings(),
            )
        );
    }

    /**
     * Describes the parameters for get_last_completed.
     *
     * @return external_function_parameters
     * @since Moodle 3.3
     */
    public static function get_last_completed_parameters() {
        return new external_function_parameters (
            array(
                'evaluationid' => new external_value(PARAM_INT, 'Evaluation instance id'),
                'courseid' => new external_value(PARAM_INT, 'Course where user completes the evaluation (for site evaluations only).',
                    VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Retrieves the last completion record for the current user.
     *
     * @param int $evaluationid evaluation instance id
     * @return array of warnings and the last completed record
     * @since Moodle 3.3
     * @throws moodle_exception
     */
    public static function get_last_completed($evaluationid, $courseid = 0) {
        global $PAGE;

        $params = array('evaluationid' => $evaluationid, 'courseid' => $courseid);
        $params = self::validate_parameters(self::get_last_completed_parameters(), $params);
        $warnings = array();

        list($evaluation, $course, $cm, $context, $completioncourse) = self::validate_evaluation($params['evaluationid'],
            $params['courseid']);
        $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $completioncourse->id);

        if ($evaluationcompletion->is_anonymous()) {
             throw new moodle_exception('anonymous', 'evaluation');
        }
        if ($completed = $evaluationcompletion->find_last_completed()) {
            $exporter = new evaluation_completed_exporter($completed);
            return array(
                'completed' => $exporter->export($PAGE->get_renderer('core')),
                'warnings' => $warnings,
            );
        }
        throw new moodle_exception('not_completed_yet', 'evaluation');
    }

    /**
     * Describes the get_last_completed return value.
     *
     * @return external_single_structure
     * @since Moodle 3.3
     */
    public static function get_last_completed_returns() {
        return new external_single_structure(
            array(
                'completed' => evaluation_completed_exporter::get_read_structure(),
                'warnings' => new external_warnings(),
            )
        );
    }
}
