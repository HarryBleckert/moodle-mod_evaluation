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
 * Library of functions and constants for module evaluation
 * includes the main-part of evaluation-functions
 *
 * @package mod_evaluation
 * @copyright Andreas Grabs for mod_evaluation
 * @copyright by Harry.Bleckert@ASH-Berlin.eu for ASH Berlin
 * + forked from mod_feedback 12/2021
 *
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/evaluation/locallib.php');


// Include forms lib.
require_once($CFG->libdir . '/formslib.php');

define('EVALUATION_ANONYMOUS_YES', 1);
define('EVALUATION_ANONYMOUS_NO', 2);
define('EVALUATION_MIN_ANONYMOUS_COUNT_IN_GROUP', 2);
define('EVALUATION_DECIMAL', '.');
define('EVALUATION_THOUSAND', ',');
define('EVALUATION_RESETFORM_RESET', 'evaluation_reset_data_');
define('EVALUATION_RESETFORM_DROP', 'evaluation_drop_evaluation_');
define('EVALUATION_MAX_PIX_LENGTH', '400'); //max. Breite des grafischen Balkens in der Auswertung
define('EVALUATION_DEFAULT_PAGE_COUNT', 20);

// Event types.
define('EVALUATION_EVENT_TYPE_OPEN', 'open');
define('EVALUATION_EVENT_TYPE_CLOSE', 'close');

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 */
function evaluation_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * this will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $evaluation the object given by mod_evaluation_mod_form
 * @return int
 * @global object
 */
function evaluation_add_instance($evaluation) {
    global $CFG, $DB;

    $evaluation->timemodified = time();
    $evaluation->id = '';

    if (empty($evaluation->site_after_submit)) {
        $evaluation->site_after_submit = '';
    }
    if (!isset($evaluation->min_results) or empty($evaluation->min_results)) {
        $evaluation->min_results = 3;
    }
    if (!isset($evaluation->privileged_users) or empty($evaluation->privileged_users)) {
        $evaluation->privileged_users = '';
    }
    if (!isset($evaluation->filter_course_of_studies) or empty($evaluation->filter_course_of_studies)) {
        $evaluation->filter_course_of_studies = '';
    }
    if (!isset($evaluation->filter_courses) or empty($evaluation->filter_courses)) {
        $evaluation->filter_courses = '';
    }
    /*if (empty($evaluation->autoreminders)) {
        $evaluation->autoreminders = 1;
    }*/
    if (empty($evaluation->semester)) {
        $evaluation->semester = evaluation_get_current_semester();
    }
    if ($CFG->ash) {
        if (empty($evaluation->sort_tag)) {
            $evaluation->sort_tag = "ASH";
        }
        if (empty($evaluation->sendermail)) {
            $evaluation->sendermail = "khayat@ash-berlin.eu";
        }
        if (empty($evaluation->sendername)) {
            $evaluation->sendername = "ASH Berlin (Qualitätsmanagement)";
        }
        if (empty($evaluation->signature)) {
            $evaluation->signature = "Berthe Khayat und Harry Bleckert für das Evaluationsteam";
        }
    }
    //saving the evaluation in db
    $evaluationid = $DB->insert_record("evaluation", $evaluation);

    $evaluation->id = $evaluationid;

    evaluation_set_events($evaluation);

    if (!isset($evaluation->coursemodule)) {
        $cm = get_coursemodule_from_id('evaluation', $evaluation->id);
        $evaluation->coursemodule = $cm->id;
    }
    $context = context_module::instance($evaluation->coursemodule);

    if (!empty($evaluation->completionexpected)) {
        \core_completion\api::update_completion_date_event($evaluation->coursemodule, 'evaluation', $evaluation->id,
                $evaluation->completionexpected);
    }

    $editoroptions = evaluation_get_editor_options();

    // process the custom wysiwyg editor in page_after_submit
    if ($draftitemid = $evaluation->page_after_submit_editor['itemid']) {
        $evaluation->page_after_submit = file_save_draft_area_files($draftitemid, $context->id,
                'mod_evaluation', 'page_after_submit',
                0, $editoroptions,
                $evaluation->page_after_submit_editor['text']);

        $evaluation->page_after_submitformat = $evaluation->page_after_submit_editor['format'];
    }
    $DB->update_record('evaluation', $evaluation);

    return $evaluationid;
}

/**
 * this will update a given instance
 *
 * @param object $evaluation the object given by mod_evaluation_mod_form
 * @return boolean
 * @global object
 */
function evaluation_update_instance($evaluation) {
    global $CFG,$DB;

    $evaluation->timemodified = time();
    $evaluation->id = $evaluation->instance;

    if (empty($evaluation->site_after_submit)) {
        $evaluation->site_after_submit = '';
    }
    if (!isset($evaluation->filter_course_of_studies) or empty($evaluation->filter_course_of_studies)) {
        $evaluation->filter_course_of_studies = '';
    }
    if (!isset($evaluation->privileged_users) or empty($evaluation->privileged_users)) {
        $evaluation->privileged_users = '';
    }
    /*if (empty($evaluation->autoreminders)) {
        $evaluation->autoreminders = 1;
    }*/
    if (empty($evaluation->semester)) {
        $evaluation->semester = evaluation_get_current_semester();
    }
    if ($CFG->ash) {
        if (empty($evaluation->sort_tag)) {
            $evaluation->sort_tag = "ASH";
        }
        if (empty($evaluation->sendermail)) {
            $evaluation->sendermail = "khayat@ash-berlin.eu";
        }
        if (empty($evaluation->sendername)) {
            $evaluation->sendername = "ASH Berlin (Qualitätsmanagement)";
        }
        if (empty($evaluation->signature)) {
            $evaluation->signature = "Berthe Khayat und Harry Bleckert für das Evaluationsteam";
        }
    }
    //save the evaluation into the db
    $DB->update_record("evaluation", $evaluation);

    //create or update the new events
    evaluation_set_events($evaluation);
    $completionexpected = (!empty($evaluation->completionexpected)) ? $evaluation->completionexpected : null;
    \core_completion\api::update_completion_date_event($evaluation->coursemodule, 'evaluation', $evaluation->id,
            $completionexpected);

    $context = context_module::instance($evaluation->coursemodule);

    $editoroptions = evaluation_get_editor_options();

    // process the custom wysiwyg editor in page_after_submit
    if ($draftitemid = $evaluation->page_after_submit_editor['itemid']) {
        $evaluation->page_after_submit = file_save_draft_area_files($draftitemid, $context->id,
                'mod_evaluation', 'page_after_submit',
                0, $editoroptions,
                $evaluation->page_after_submit_editor['text']);

        $evaluation->page_after_submitformat = $evaluation->page_after_submit_editor['format'];
    }
    $DB->update_record('evaluation', $evaluation);
    return true;
}

/**
 * Serves the files included in evaluation items like label. Implements needed access control ;-)
 *
 * There are two situations in general where the files will be sent.
 * 1) filearea = item, 2) filearea = template
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 * @package  mod_evaluation
 * @category files
 */
function evaluation_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    if ($filearea === 'item' or $filearea === 'template') {
        $itemid = (int) array_shift($args);
        //get the item what includes the file
        if (!$item = $DB->get_record('evaluation_item', array('id' => $itemid))) {
            return false;
        }
        $evaluationid = $item->evaluation;
        $templateid = $item->template;
    }

    if ($filearea === 'page_after_submit' or $filearea === 'item') {
        if (!$evaluation = $DB->get_record("evaluation", array("id" => $cm->instance))) {
            return false;
        }

        $evaluationid = $evaluation->id;

        //if the filearea is "item" so we check the permissions like view/complete the evaluation
        $canload = false;
        //first check whether the user has the complete capability
        if (has_capability('mod/evaluation:complete', $context)) {
            $canload = true;
        }

        //now we check whether the user has the view capability
        if (has_capability('mod/evaluation:view', $context)) {
            $canload = true;
        }

        //if the evaluation is on frontpage and anonymous and the fullanonymous is allowed
        //so the file can be loaded too.
        if (isset($CFG->evaluation_allowfullanonymous)
                and $CFG->evaluation_allowfullanonymous
                and $course->id == SITEID
                and $evaluation->anonymous == EVALUATION_ANONYMOUS_YES) {
            $canload = true;
        }

        if (!$canload) {
            return false;
        }
    } else if ($filearea === 'template') { //now we check files in templates
        if (!$template = $DB->get_record('evaluation_template', array('id' => $templateid))) {
            return false;
        }

        //if the file is not public so the capability edititems has to be there
        if (!$template->ispublic) {
            if (!has_capability('mod/evaluation:edititems', $context)) {
                return false;
            }
        } else { //on public templates, at least the user has to be logged in
            if (!isloggedin()) {
                return false;
            }
        }
    } else {
        return false;
    }

    if ($context->contextlevel == CONTEXT_MODULE) {
        if ($filearea !== 'item' and $filearea !== 'page_after_submit') {
            return false;
        }
    }

    if ($context->contextlevel == CONTEXT_COURSE || $context->contextlevel == CONTEXT_SYSTEM) {
        if ($filearea !== 'template') {
            return false;
        }
    }

    $relativepath = implode('/', $args);
    if ($filearea === 'page_after_submit') {
        $fullpath = "/{$context->id}/mod_evaluation/$filearea/$relativepath";
    } else {
        $fullpath = "/{$context->id}/mod_evaluation/$filearea/{$item->id}/$relativepath";
    }

    $fs = get_file_storage();

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!

    return false;
}

/**
 * this will delete a given instance.
 * all referenced data also will be deleted
 *
 * @param int $id the instanceid of evaluation
 * @return boolean
 * @global object
 */
function evaluation_delete_instance($id) {
    global $DB;

    //get all referenced items
    $evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $id));

    //deleting all referenced items and values
    if (is_array($evaluationitems)) {
        foreach ($evaluationitems as $evaluationitem) {
            $DB->delete_records("evaluation_value", array("item" => $evaluationitem->id));
            $DB->delete_records("evaluation_valuetmp", array("item" => $evaluationitem->id));
        }
        if ($delitems = $DB->get_records("evaluation_item", array("evaluation" => $id))) {
            foreach ($delitems as $delitem) {
                evaluation_delete_item($delitem->id, false);
            }
        }
    }

    //deleting the completeds
    $DB->delete_records("evaluation_completed", array("evaluation" => $id));

    //deleting the unfinished completeds
    $DB->delete_records("evaluation_completedtmp", array("evaluation" => $id));

    //deleting old events
    $DB->delete_records('event', array('modulename' => 'evaluation', 'instance' => $id));

    // deleting evaluation_users_la
    return $DB->delete_records("evaluation_users_la", array("id" => $id));

    // deleting evaluation_enrolment
    return $DB->delete_records("evaluation_enrolment", array("id" => $id));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $evaluation
 * @return stdClass
 */
function evaluation_user_outline($course, $user, $mod, $evaluation) {
    global $DB;
    $outline = (object) ['info' => '', 'time' => 0];
    if ($evaluation->anonymous != EVALUATION_ANONYMOUS_NO) {
        // Do not disclose any user info if evaluation is anonymous.
        return $outline;
    }
    $params = array('userid' => $user->id, 'evaluation' => $evaluation->id,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO);
    $status = null;
    $context = context_module::instance($mod->id);
    if ($completed = $DB->get_record('evaluation_completed', $params)) {
        // User has completed evaluation.
        $outline->info = get_string('completed', 'evaluation');
        $outline->time = $completed->timemodified;
    } else if ($completedtmp = $DB->get_record('evaluation_completedtmp', $params)) {
        // User has started but not completed evaluation.
        $outline->info = get_string('started', 'evaluation');
        $outline->time = $completedtmp->timemodified;
    } else if (has_capability('mod/evaluation:complete', $context, $user)) {
        // User has not started evaluation but has capability to do so.
        $outline->info = get_string('not_started', 'evaluation');
    }

    return $outline;
}

/**
 * Returns all users who has completed a specified evaluation since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @param array $activities Passed by reference
 * @param int $index Passed by reference
 * @param int $timemodified Timestamp
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return void
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 */
function evaluation_get_recent_mod_activity(&$activities, &$index,
        $timemodified, $courseid,
        $cmid, $userid = "", $groupid = "") {

    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $sqlargs = array();

    $userfields = user_picture::fields('u', null, 'useridagain');
    $sql = " SELECT fk . * , fc . * , $userfields
                FROM {evaluation_completed} fc
                    JOIN {evaluation} fk ON fk.id = fc.evaluation
                    JOIN {user} u ON u.id = fc.userid ";

    if ($groupid) {
        $sql .= " JOIN {groups_members} gm ON  gm.userid=u.id ";
    }

    $sql .= " WHERE fc.timemodified > ?
                AND fk.id = ?
                AND fc.anonymous_response = ?";
    $sqlargs[] = $timemodified;
    $sqlargs[] = $cm->instance;
    $sqlargs[] = EVALUATION_ANONYMOUS_NO;

    if ($userid) {
        $sql .= " AND u.id = ? ";
        $sqlargs[] = $userid;
    }

    if ($groupid) {
        $sql .= " AND gm.groupid = ? ";
        $sqlargs[] = $groupid;
    }

    if (!$evaluationitems = $DB->get_records_sql($sql, $sqlargs)) {
        return;
    }

    $cm_context = context_module::instance($cm->id);

    if (!has_capability('mod/evaluation:view', $cm_context)) {
        return;
    }

    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames = has_capability('moodle/site:viewfullnames', $cm_context);
    $groupmode = groups_get_activity_groupmode($cm, $course);

    $aname = format_string($cm->name, true);
    foreach ($evaluationitems as $evaluationitem) {
        if ($evaluationitem->userid != $USER->id) {

            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
                $usersgroups = groups_get_all_groups($course->id,
                        $evaluationitem->userid,
                        $cm->groupingid);
                if (!is_array($usersgroups)) {
                    continue;
                }
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();

        $tmpactivity->type = 'evaluation';
        $tmpactivity->cmid = $cm->id;
        $tmpactivity->name = $aname;
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp = $evaluationitem->timemodified;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->evaluationid = $evaluationitem->id;
        $tmpactivity->content->evaluationuserid = $evaluationitem->userid;

        $tmpactivity->user = user_picture::unalias($evaluationitem, null, 'useridagain');
        $tmpactivity->user->fullname = fullname($evaluationitem, $viewfullnames);

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Prints all users who has completed a specified evaluation since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @param object $activity
 * @param int $courseid
 * @param string $detail
 * @param array $modnames
 * @return void Output is echo'd
 * @global object
 */
function evaluation_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user, array('courseid' => $courseid));
    echo "</td><td>";

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, $activity->type);
        echo "<a href=\"$CFG->wwwroot/mod/evaluation/view.php?id={$activity->cmid}\">{$activity->name}</a>";
        echo '</div>';
    }

    echo '<div class="title">';
    echo '</div>';

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
            . "{$activity->user->fullname}</a> - " . userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";

    return;
}

/**
 * Obtains the automatic completion state for this evaluation based on the condition
 * in evaluation settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function evaluation_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get evaluation details
    $evaluation = $DB->get_record('evaluation', array('id' => $cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false
    if ($evaluation->completionsubmit) {
        $params = array('userid' => $userid, 'evaluation' => $evaluation->id);
        return $DB->record_exists('evaluation_completed', $params);
    } else {
        // Completion option is not enabled so just return $type
        return $type;
    }
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $evaluation
 */
function evaluation_user_complete($course, $user, $mod, $evaluation) {
    global $DB;
    if ($evaluation->anonymous != EVALUATION_ANONYMOUS_NO) {
        // Do not disclose any user info if evaluation is anonymous.
        return;
    }
    $params = array('userid' => $user->id, 'evaluation' => $evaluation->id,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO);
    $url = $status = null;
    $context = context_module::instance($mod->id);
    if ($completed = $DB->get_record('evaluation_completed', $params)) {
        // User has completed evaluation.
        if (has_capability('mod/evaluation:viewreports', $context)) {
            $url = new moodle_url('/mod/evaluation/show_entries.php',
                    ['id' => $mod->id, 'userid' => $user->id,
                            'showcompleted' => $completed->id]);
        }
        $status = get_string('completedon', 'evaluation', userdate($completed->timemodified));
    } else if ($completedtmp = $DB->get_record('evaluation_completedtmp', $params)) {
        // User has started but not completed evaluation.
        $status = get_string('startedon', 'evaluation', userdate($completedtmp->timemodified));
    } else if (has_capability('mod/evaluation:complete', $context, $user)) {
        // User has not started evaluation but has capability to do so.
        $status = get_string('not_started', 'evaluation');
    }

    if ($url && $status) {
        echo html_writer::link($url, $status);
    } else if ($status) {
        echo html_writer::div($status);
    }
}

/**
 * @return bool true
 */
function evaluation_cron() {
    return true;
}

/**
 * @deprecated since Moodle 3.8
 */
function evaluation_scale_used() {
    throw new coding_exception('evaluation_scale_used() can not be used anymore. Plugins can implement ' .
            '<modname>_scale_used_anywhere, all implementations of <modname>_scale_used are now ignored');
}

/**
 * Checks if scale is being used by any instance of evaluation
 *
 * This is used to find out if scale used anywhere
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any assignment
 */
function evaluation_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function evaluation_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function evaluation_get_post_actions() {
    return array('submit');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all responses from the specified evaluation
 * and clean up any related data.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 * @uses EVALUATION_RESETFORM_RESET
 * @uses EVALUATION_RESETFORM_DROP
 * @global object
 * @global object
 */
function evaluation_reset_userdata($data) {
    global $CFG, $DB;

    $resetevaluations = array();
    $dropevaluations = array();
    $status = array();
    $componentstr = get_string('modulenameplural', 'evaluation');

    //get the relevant entries from $data
    foreach ($data as $key => $value) {
        switch (true) {
            case substr($key, 0, strlen(EVALUATION_RESETFORM_RESET)) == EVALUATION_RESETFORM_RESET:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $resetevaluations[] = intval($templist[3]);
                    }
                }
                break;
            case substr($key, 0, strlen(EVALUATION_RESETFORM_DROP)) == EVALUATION_RESETFORM_DROP:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $dropevaluations[] = intval($templist[3]);
                    }
                }
                break;
        }
    }

    //reset the selected evaluations
    foreach ($resetevaluations as $id) {
        $evaluation = $DB->get_record('evaluation', array('id' => $id));
        evaluation_delete_all_completeds($evaluation);
        $status[] = array('component' => $componentstr . ':' . $evaluation->name,
                'item' => get_string('resetting_data', 'evaluation'),
                'error' => false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $shifterror = !shift_course_mod_dates('evaluation', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => $shifterror);
    }

    return $status;
}

/**
 * Called by course/reset.php
 *
 * @param object $mform form passed by reference
 * @uses EVALUATION_RESETFORM_RESET
 * @global object
 */
function evaluation_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'evaluationheader', get_string('modulenameplural', 'evaluation'));

    if (!$evaluations = $DB->get_records('evaluation', array('course' => $COURSE->id), 'name')) {
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetting_data', 'evaluation'));
    foreach ($evaluations as $evaluation) {
        $mform->addElement('checkbox', EVALUATION_RESETFORM_RESET . $evaluation->id, $evaluation->name);
    }
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @uses EVALUATION_RESETFORM_RESET
 * @global object
 */
function evaluation_reset_course_form_defaults($course) {
    global $DB;

    $return = array();
    if (!$evaluations = $DB->get_records('evaluation', array('course' => $course->id), 'name')) {
        return;
    }
    foreach ($evaluations as $evaluation) {
        $return[EVALUATION_RESETFORM_RESET . $evaluation->id] = true;
    }
    return $return;
}

/**
 * Called by course/reset.php and shows the formdata by coursereset.
 * it prints checkboxes for each evaluation available at the given course
 * there are two checkboxes:
 * 1) delete userdata and keep the evaluation
 * 2) delete userdata and drop the evaluation
 *
 * @param object $course
 * @return void
 * @uses EVALUATION_RESETFORM_DROP
 * @global object
 * @uses EVALUATION_RESETFORM_RESET
 */
function evaluation_reset_course_form($course) {
    global $DB, $OUTPUT;

    echo get_string('resetting_evaluations', 'evaluation');
    echo ':<br />';
    if (!$evaluations = $DB->get_records('evaluation', array('course' => $course->id), 'name')) {
        return;
    }

    foreach ($evaluations as $evaluation) {
        echo '<p>';
        echo get_string('name', 'evaluation') . ': ' . $evaluation->name . '<br />';
        echo html_writer::checkbox(EVALUATION_RESETFORM_RESET . $evaluation->id,
                1, true,
                get_string('resetting_data', 'evaluation'));
        echo '<br />';
        echo html_writer::checkbox(EVALUATION_RESETFORM_DROP . $evaluation->id,
                1, false,
                get_string('drop_evaluation', 'evaluation'));
        echo '</p>';
    }
}

/**
 * This gets an array with default options for the editor
 *
 * @return array the options
 */
function evaluation_get_editor_options() {
    return array('maxfiles' => EDITOR_UNLIMITED_FILES,
            'trusttext' => true);
}

/**
 * this function is called by {@link evaluation_delete_userdata()}
 * it drops the evaluation-instance from the course_module table
 *
 * @param int $id the id from the coursemodule
 * @return boolean
 * @global object
 */
function evaluation_delete_course_module($id) {
    global $DB;

    if (!$cm = $DB->get_record('course_modules', array('id' => $id))) {
        return true;
    }
    return $DB->delete_records('course_modules', array('id' => $cm->id));
}

////////////////////////////////////////////////
//functions to handle capabilities
////////////////////////////////////////////////

/**
 * @deprecated since 3.1
 */
function evaluation_get_context() {
    throw new coding_exception('evaluation_get_context() can not be used anymore.');
}

/**
 *  returns true if the current role is faked by switching role feature
 *
 * @return boolean
 * @global object
 */
function evaluation_check_is_switchrole() {
    global $USER;
    if (isset($USER->switchrole) and
            is_array($USER->switchrole) and
            safeCount($USER->switchrole) > 0) {

        return true;
    }
    return false;
}

/**
 * count users which have not completed the evaluation
 *
 * @param cm_info $cm Course-module object
 * @param int $group single groupid
 * @param string $sort
 * @param int $startpage
 * @param int $pagecount
 * @param bool $includestatus to return if the user started or not the evaluation among the complete user record
 * @return array array of user ids or user objects when $includestatus set to true
 * @uses CONTEXT_MODULE
 * @global object
 */
function evaluation_get_incomplete_users(cm_info $cm,
        $group = false,
        $sort = '',
        $startpage = false,
        $pagecount = false,
        $includestatus = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    //first get all user who can complete this evaluation
    $cap = 'mod/evaluation:complete';
    $allnames = get_all_user_name_fields(true, 'u');
    $fields = 'u.id, ' . $allnames . ', u.picture, u.email, u.imagealt';
    if (!$allusers = get_users_by_capability($context,
            $cap,
            $fields,
            $sort,
            '',
            '',
            $group,
            '',
            true)) {
        return false;
    }
    // Filter users that are not in the correct group/grouping.
    $info = new \core_availability\info_module($cm);
    $allusersrecords = $info->filter_user_list($allusers);

    $allusers = array_keys($allusersrecords);

    //now get all completeds
    $params = array('evaluation' => $cm->instance);
    if ($completedusers = $DB->get_records_menu('evaluation_completed', $params, '', 'id, userid')) {
        // Now strike all completedusers from allusers.
        $allusers = array_diff($allusers, $completedusers);
    }

    //for paging I use array_slice()
    if ($startpage !== false and $pagecount !== false) {
        $allusers = array_slice($allusers, $startpage, $pagecount);
    }

    // Check if we should return the full users objects.
    if ($includestatus) {
        $userrecords = [];
        $startedusers = $DB->get_records_menu('evaluation_completedtmp', ['evaluation' => $cm->instance], '', 'id, userid');
        $startedusers = array_flip($startedusers);
        foreach ($allusers as $userid) {
            $allusersrecords[$userid]->evaluationstarted = isset($startedusers[$userid]);
            $userrecords[] = $allusersrecords[$userid];
        }
        return $userrecords;
    } else {    // Return just user ids.
        return $allusers;
    }
}

/**
 * count users which have not completed the evaluation
 *
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 * @global object
 */
function evaluation_count_incomplete_users($cm, $group = false) {
    if ($allusers = evaluation_get_incomplete_users($cm, $group)) {
        return safeCount($allusers);
    }
    return 0;
}

/**
 * count users which have completed a evaluation
 *
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 * @uses EVALUATION_ANONYMOUS_NO
 * @global object
 */
function evaluation_count_complete_users($cm, $group = false) {
    global $DB;

    $params = array(EVALUATION_ANONYMOUS_NO, $cm->instance);

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = ? AND g.userid = c.userid';
        $params[] = $group;
    }

    $sql = 'SELECT COUNT(u.id) FROM {user} u, {evaluation_completed} c' . $fromgroup . '
              WHERE anonymous_response = ? AND u.id = c.userid AND c.evaluation = ?
              ' . $wheregroup;

    return $DB->count_records_sql($sql, $params);

}

/**
 * get users which have completed a evaluation
 *
 * @param object $cm
 * @param int $group single groupid
 * @param string $where a sql where condition (must end with " AND ")
 * @param array parameters used in $where
 * @param string $sort a table field
 * @param int $startpage
 * @param int $pagecount
 * @return object the userrecords
 * @global object
 * @uses CONTEXT_MODULE
 * @uses EVALUATION_ANONYMOUS_NO
 */
function evaluation_get_complete_users($cm,
        $group = false,
        $where = '',
        array $params = null,
        $sort = '',
        $startpage = false,
        $pagecount = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    $params = (array) $params;

    $params['anon'] = EVALUATION_ANONYMOUS_NO;
    $params['instance'] = $cm->instance;

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = :group AND g.userid = c.userid';
        $params['group'] = $group;
    }

    if ($sort) {
        $sortsql = ' ORDER BY ' . $sort;
    } else {
        $sortsql = '';
    }

    $ufields = user_picture::fields('u');
    $sql = 'SELECT DISTINCT ' . $ufields . ', c.timemodified as completed_timemodified
            FROM {user} u, {evaluation_completed} c ' . $fromgroup . '
            WHERE ' . $where . ' anonymous_response = :anon
                AND u.id = c.userid
                AND c.evaluation = :instance
              ' . $wheregroup . $sortsql;

    if ($startpage === false or $pagecount === false) {
        $startpage = false;
        $pagecount = false;
    }
    return $DB->get_records_sql($sql, $params, $startpage, $pagecount);
}

/**
 * get users which have the viewreports-capability
 *
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 * @uses CONTEXT_MODULE
 */
function evaluation_get_viewreports_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
            'mod/evaluation:viewreports',
            '',
            'lastname',
            '',
            '',
            $groups,
            '',
            false);
}

/**
 * get users which have the receivemail-capability
 *
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 * @uses CONTEXT_MODULE
 */
function evaluation_get_receivemail_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
            'mod/evaluation:receivemail',
            '',
            'lastname',
            '',
            '',
            $groups,
            '',
            false);
}

////////////////////////////////////////////////
//functions to handle the templates
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * creates a new template-record.
 *
 * @param int $courseid
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return int the new templateid
 * @global object
 */
function evaluation_create_template($courseid, $name, $ispublic = 0) {
    global $DB;

    $templ = new stdClass();
    $templ->course = ($ispublic ? 0 : $courseid);
    $templ->name = $name;
    $templ->ispublic = $ispublic;

    $templid = $DB->insert_record('evaluation_template', $templ);
    return $DB->get_record('evaluation_template', array('id' => $templid));
}

/**
 * creates new template items.
 * all items will be copied and the attribute evaluation will be set to 0
 * and the attribute template will be set to the new templateid
 *
 * @param object $evaluation
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return boolean
 * @uses CONTEXT_MODULE
 * @uses CONTEXT_COURSE
 * @global object
 */
function evaluation_save_as_template($evaluation, $name, $ispublic = 0) {
    global $DB;
    $fs = get_file_storage();

    if (!$evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id))) {
        return false;
    }

    if (!$newtempl = evaluation_create_template($evaluation->course, $name, $ispublic)) {
        return false;
    }

    //files in the template_item are in the context of the current course or
    //if the template is public the files are in the system context
    //files in the evaluation_item are in the evaluation_context of the evaluation
    if ($ispublic) {
        $s_context = context_system::instance();
    } else {
        $s_context = context_course::instance($newtempl->course);
    }
    $cm = get_coursemodule_from_instance('evaluation', $evaluation->id);
    $f_context = context_module::instance($cm->id);

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($evaluationitems as $item) {

        $t_item = clone($item);

        unset($t_item->id);
        $t_item->evaluation = 0;
        $t_item->template = $newtempl->id;
        $t_item->id = $DB->insert_record('evaluation_item', $t_item);
        //copy all included files to the evaluation_template filearea
        $itemfiles = $fs->get_area_files($f_context->id,
                'mod_evaluation',
                'item',
                $item->id,
                "id",
                false);
        if ($itemfiles) {
            foreach ($itemfiles as $ifile) {
                $file_record = new stdClass();
                $file_record->contextid = $s_context->id;
                $file_record->component = 'mod_evaluation';
                $file_record->filearea = 'template';
                $file_record->itemid = $t_item->id;
                $fs->create_file_from_storedfile($file_record, $ifile);
            }
        }

        $itembackup[$item->id] = $t_item->id;
        if ($t_item->dependitem) {
            $dependitemsmap[$t_item->id] = $t_item->dependitem;
        }

    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('evaluation_item', array('id' => $key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('evaluation_item', $newitem);
    }

    return true;
}

/**
 * deletes all evaluation_items related to the given template id
 *
 * @param object $template the template
 * @return void
 * @global object
 * @uses CONTEXT_COURSE
 */
function evaluation_delete_template($template) {
    global $DB;

    //deleting the files from the item is done by evaluation_delete_item
    if ($t_items = $DB->get_records("evaluation_item", array("template" => $template->id))) {
        foreach ($t_items as $t_item) {
            evaluation_delete_item($t_item->id, false, $template);
        }
    }
    $DB->delete_records("evaluation_template", array("id" => $template->id));
}

/**
 * creates new evaluation_item-records from template.
 * if $deleteold is set true so the existing items of the given evaluation will be deleted
 * if $deleteold is set false so the new items will be appanded to the old items
 *
 * @param object $evaluation
 * @param int $templateid
 * @param boolean $deleteold
 * @global object
 * @uses CONTEXT_COURSE
 * @uses CONTEXT_MODULE
 */
function evaluation_items_from_template($evaluation, $templateid, $deleteold = false) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/completionlib.php');

    $fs = get_file_storage();

    if (!$template = $DB->get_record('evaluation_template', array('id' => $templateid))) {
        return false;
    }
    //get all templateitems
    if (!$templitems = $DB->get_records('evaluation_item', array('template' => $templateid))) {
        return false;
    }

    //files in the template_item are in the context of the current course
    //files in the evaluation_item are in the evaluation_context of the evaluation
    if ($template->ispublic) {
        $s_context = context_system::instance();
    } else {
        $s_context = context_course::instance($evaluation->course);
    }
    $course = $DB->get_record('course', array('id' => $evaluation->course));
    $cm = get_coursemodule_from_instance('evaluation', $evaluation->id);
    $f_context = context_module::instance($cm->id);

    //if deleteold then delete all old items before
    //get all items
    if ($deleteold) {
        if ($evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id))) {
            //delete all items of this evaluation
            foreach ($evaluationitems as $item) {
                evaluation_delete_item($item->id, false);
            }

            $params = array('evaluation' => $evaluation->id);
            if ($completeds = $DB->get_records('evaluation_completed', $params)) {
                $completion = new completion_info($course);
                foreach ($completeds as $completed) {
                    $DB->delete_records('evaluation_completed', array('id' => $completed->id));
                    // Update completion state
                    if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC &&
                            $evaluation->completionsubmit) {
                        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
                    }
                }
            }
            $DB->delete_records('evaluation_completedtmp', array('evaluation' => $evaluation->id));
        }
        $positionoffset = 0;
    } else {
        //if the old items are kept the new items will be appended
        //therefor the new position has an offset
        $positionoffset = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id));
    }

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($templitems as $t_item) {
        $item = clone($t_item);
        unset($item->id);
        $item->evaluation = $evaluation->id;
        $item->template = 0;
        $item->position = $item->position + $positionoffset;

        $item->id = $DB->insert_record('evaluation_item', $item);

        //moving the files to the new item
        $templatefiles = $fs->get_area_files($s_context->id,
                'mod_evaluation',
                'template',
                $t_item->id,
                "id",
                false);
        if ($templatefiles) {
            foreach ($templatefiles as $tfile) {
                $file_record = new stdClass();
                $file_record->contextid = $f_context->id;
                $file_record->component = 'mod_evaluation';
                $file_record->filearea = 'item';
                $file_record->itemid = $item->id;
                $fs->create_file_from_storedfile($file_record, $tfile);
            }
        }

        $itembackup[$t_item->id] = $item->id;
        if ($item->dependitem) {
            $dependitemsmap[$item->id] = $item->dependitem;
        }
    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('evaluation_item', array('id' => $key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('evaluation_item', $newitem);
    }
}

/**
 * get the list of available templates.
 * if the $onlyown param is set true so only templates from own course will be served
 * this is important for droping templates
 *
 * @param object $course
 * @param string $onlyownorpublic
 * @return array the template recordsets
 * @global object
 */
function evaluation_get_template_list($course, $onlyownorpublic = '') {
    global $DB, $CFG;

    switch ($onlyownorpublic) {
        case '':
            $templates = $DB->get_records_select('evaluation_template',
                    'course = ? OR ispublic = 1',
                    array($course->id),
                    'name');
            break;
        case 'own':
            $templates = $DB->get_records('evaluation_template',
                    array('course' => $course->id),
                    'name');
            break;
        case 'public':
            $templates = $DB->get_records('evaluation_template', array('ispublic' => 1), 'name');
            break;
    }
    return $templates;
}

////////////////////////////////////////////////
//Handling der Items
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * load the lib.php from item-plugin-dir and returns the instance of the itemclass
 *
 * @param string $typ
 * @return evaluation_item_base the instance of itemclass
 */
function evaluation_get_item_class($typ) {
    global $CFG;

    //get the class of item-typ
    $itemclass = 'evaluation_item_' . $typ;
    //get the instance of item-class
    if (!class_exists($itemclass)) {
        require_once($CFG->dirroot . '/mod/evaluation/item/' . $typ . '/lib.php');
    }
    return new $itemclass();
}

/**
 * load the available item plugins from given subdirectory of $CFG->dirroot
 * the default is "mod/evaluation/item"
 *
 * @param string $dir the subdir
 * @return array pluginnames as string
 * @global object
 */
function evaluation_load_evaluation_items($dir = 'mod/evaluation/item') {
    global $CFG;
    $names = get_list_of_plugins($dir);
    $ret_names = array();

    foreach ($names as $name) {
        require_once($CFG->dirroot . '/' . $dir . '/' . $name . '/lib.php');
        if (class_exists('evaluation_item_' . $name)) {
            $ret_names[] = $name;
        }
    }
    return $ret_names;
}

/**
 * load the available item plugins to use as dropdown-options
 *
 * @return array pluginnames as string
 * @global object
 */
function evaluation_load_evaluation_items_options() {
    global $CFG;

    $evaluation_options = array("pagebreak" => get_string('add_pagebreak', 'evaluation'));

    if (!$evaluation_names = evaluation_load_evaluation_items('mod/evaluation/item')) {
        return array();
    }

    foreach ($evaluation_names as $fn) {
        $evaluation_options[$fn] = get_string($fn, 'evaluation');
    }
    asort($evaluation_options);
    return $evaluation_options;
}

/**
 * load the available items for the depend item dropdown list shown in the edit_item form
 *
 * @param object $evaluation
 * @param object $item the item of the edit_item form
 * @return array all items except the item $item, labels and pagebreaks
 * @global object
 */
function evaluation_get_depend_candidates_for_item($evaluation, $item) {
    global $DB;
    //all items for dependitem
    $where = "evaluation = ? AND typ != 'pagebreak' AND hasvalue = 1";
    $params = array($evaluation->id);
    if (isset($item->id) and $item->id) {
        $where .= ' AND id != ?';
        $params[] = $item->id;
    }
    $dependitems = array(0 => get_string('choose'));
    $evaluationitems = $DB->get_records_select_menu('evaluation_item',
            $where,
            $params,
            'position',
            'id, label');

    if (!$evaluationitems) {
        return $dependitems;
    }
    //adding the choose-option
    foreach ($evaluationitems as $key => $val) {
        if (trim(strval($val)) !== '') {
            $dependitems[$key] = format_string($val);
        }
    }
    return $dependitems;
}

/**
 * @deprecated since 3.1
 */
function evaluation_create_item() {
    throw new coding_exception('evaluation_create_item() can not be used anymore.');
}

/**
 * save the changes of a given item.
 *
 * @param object $item
 * @return boolean
 * @global object
 */
function evaluation_update_item($item) {
    global $DB;
    return $DB->update_record("evaluation_item", $item);
}

/**
 * deletes an item and also deletes all related values
 *
 * @param int $itemid
 * @param boolean $renumber should the kept items renumbered Yes/No
 * @param object $template if the template is given so the items are bound to it
 * @return void
 * @global object
 * @uses CONTEXT_MODULE
 */
function evaluation_delete_item($itemid, $renumber = true, $template = false) {
    global $DB;

    $item = $DB->get_record('evaluation_item', array('id' => $itemid));

    //deleting the files from the item
    $fs = get_file_storage();

    if ($template) {
        if ($template->ispublic) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($template->course);
        }
        $templatefiles = $fs->get_area_files($context->id,
                'mod_evaluation',
                'template',
                $item->id,
                "id",
                false);

        if ($templatefiles) {
            $fs->delete_area_files($context->id, 'mod_evaluation', 'template', $item->id);
        }
    } else {
        if (!$cm = get_coursemodule_from_instance('evaluation', $item->evaluation)) {
            return false;
        }
        $context = context_module::instance($cm->id);

        $itemfiles = $fs->get_area_files($context->id,
                'mod_evaluation',
                'item',
                $item->id,
                "id", false);

        if ($itemfiles) {
            $fs->delete_area_files($context->id, 'mod_evaluation', 'item', $item->id);
        }
    }

    $DB->delete_records("evaluation_value", array("item" => $itemid));
    $DB->delete_records("evaluation_valuetmp", array("item" => $itemid));

    //remove all depends
    $DB->set_field('evaluation_item', 'dependvalue', '', array('dependitem' => $itemid));
    $DB->set_field('evaluation_item', 'dependitem', 0, array('dependitem' => $itemid));

    $DB->delete_records("evaluation_item", array("id" => $itemid));
    if ($renumber) {
        evaluation_renumber_items($item->evaluation);
    }
}

/**
 * deletes all items of the given evaluationid
 *
 * @param int $evaluationid
 * @return void
 * @global object
 */
function evaluation_delete_all_items($evaluationid) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    if (!$evaluation = $DB->get_record('evaluation', array('id' => $evaluationid))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('evaluation', $evaluation->id)) {
        return false;
    }

    if (!$course = $DB->get_record('course', array('id' => $evaluation->course))) {
        return false;
    }

    if (!$items = $DB->get_records('evaluation_item', array('evaluation' => $evaluationid))) {
        return;
    }
    foreach ($items as $item) {
        evaluation_delete_item($item->id, false);
    }
    if ($completeds = $DB->get_records('evaluation_completed', array('evaluation' => $evaluation->id))) {
        $completion = new completion_info($course);
        foreach ($completeds as $completed) {
            $DB->delete_records('evaluation_completed', array('id' => $completed->id));
            // Update completion state
            if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC &&
                    $evaluation->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
            }
        }
    }

    $DB->delete_records('evaluation_completedtmp', array('evaluation' => $evaluationid));

}

/**
 * this function toggled the item-attribute required (yes/no)
 *
 * @param object $item
 * @return boolean
 * @global object
 */
function evaluation_switch_item_required($item) {
    global $DB, $CFG;

    $itemobj = evaluation_get_item_class($item->typ);

    if ($itemobj->can_switch_require()) {
        $new_require_val = (int) !(bool) $item->required;
        $params = array('id' => $item->id);
        $DB->set_field('evaluation_item', 'required', $new_require_val, $params);
    }
    return true;
}

/**
 * renumbers all items of the given evaluationid
 *
 * @param int $evaluationid
 * @return void
 * @global object
 */
function evaluation_renumber_items($evaluationid) {
    global $DB;

    $items = $DB->get_records('evaluation_item', array('evaluation' => $evaluationid), 'position');
    $pos = 1;
    if ($items) {
        foreach ($items as $item) {
            $DB->set_field('evaluation_item', 'position', $pos, array('id' => $item->id));
            $pos++;
        }
    }
}

/**
 * this decreases the position of the given item
 *
 * @param object $item
 * @return bool
 * @global object
 */
function evaluation_moveup_item($item) {
    global $DB;

    if ($item->position == 1) {
        return true;
    }

    $params = array('evaluation' => $item->evaluation);
    if (!$items = $DB->get_records('evaluation_item', $params, 'position')) {
        return false;
    }

    $itembefore = null;
    foreach ($items as $i) {
        if ($i->id == $item->id) {
            if (is_null($itembefore)) {
                return true;
            }
            $itembefore->position = $item->position;
            $item->position--;
            evaluation_update_item($itembefore);
            evaluation_update_item($item);
            evaluation_renumber_items($item->evaluation);
            return true;
        }
        $itembefore = $i;
    }
    return false;
}

/**
 * this increased the position of the given item
 *
 * @param object $item
 * @return bool
 * @global object
 */
function evaluation_movedown_item($item) {
    global $DB;

    $params = array('evaluation' => $item->evaluation);
    if (!$items = $DB->get_records('evaluation_item', $params, 'position')) {
        return false;
    }

    $movedownitem = null;
    foreach ($items as $i) {
        if (!is_null($movedownitem) and $movedownitem->id == $item->id) {
            $movedownitem->position = $i->position;
            $i->position--;
            evaluation_update_item($movedownitem);
            evaluation_update_item($i);
            evaluation_renumber_items($item->evaluation);
            return true;
        }
        $movedownitem = $i;
    }
    return false;
}

/**
 * here the position of the given item will be set to the value in $pos
 *
 * @param object $moveitem
 * @param int $pos
 * @return boolean
 * @global object
 */
function evaluation_move_item($moveitem, $pos) {
    global $DB;

    $params = array('evaluation' => $moveitem->evaluation);
    if (!$allitems = $DB->get_records('evaluation_item', $params, 'position')) {
        return false;
    }
    if (is_array($allitems)) {
        $index = 1;
        foreach ($allitems as $item) {
            if ($index == $pos) {
                $index++;
            }
            if ($item->id == $moveitem->id) {
                $moveitem->position = $pos;
                evaluation_update_item($moveitem);
                continue;
            }
            $item->position = $index;
            evaluation_update_item($item);
            $index++;
        }
        return true;
    }
    return false;
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_print_item_preview() {
    throw new coding_exception('evaluation_print_item_preview() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_print_item_complete() {
    throw new coding_exception('evaluation_print_item_complete() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_print_item_show_value() {
    throw new coding_exception('evaluation_print_item_show_value() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * if the user completes a evaluation and there is a pagebreak so the values are saved temporary.
 * the values are not saved permanently until the user click on save button
 *
 * @param object $evaluationcompleted
 * @return object temporary saved completed-record
 * @global object
 */
function evaluation_set_tmp_values($evaluationcompleted) {
    global $DB;

    //first we create a completedtmp
    $tmpcpl = new stdClass();
    foreach ($evaluationcompleted as $key => $value) {
        $tmpcpl->{$key} = $value;
    }
    unset($tmpcpl->id);
    $tmpcpl->timemodified = time();
    $tmpcpl->id = $DB->insert_record('evaluation_completedtmp', $tmpcpl);
    //get all values of original-completed
    if (!$values = $DB->get_records('evaluation_value', array('completed' => $evaluationcompleted->id))) {
        return;
    }
    foreach ($values as $value) {
        unset($value->id);
        $value->completed = $tmpcpl->id;
        $DB->insert_record('evaluation_valuetmp', $value);
    }
    return $tmpcpl;
}

/**
 * this saves the temporary saved values permanently
 *
 * @param object $evaluationcompletedtmp the temporary completed
 * @param object $evaluationcompleted the target completed
 * @return int the id of the completed
 * @global object
 */
function evaluation_save_tmp_values($evaluationcompletedtmp, $evaluationcompleted) {
    global $DB;

    $tmpcplid = $evaluationcompletedtmp->id;
    if ($evaluationcompleted) {
        //first drop all existing values
        $DB->delete_records('evaluation_value', array('completed' => $evaluationcompleted->id));
        //update the current completed
        $evaluationcompleted->timemodified = time();
        $DB->update_record('evaluation_completed', $evaluationcompleted);
    } else {
        $evaluationcompleted = clone($evaluationcompletedtmp);
        $evaluationcompleted->id = '';
        $evaluationcompleted->timemodified = time();
        $evaluationcompleted->id = $DB->insert_record('evaluation_completed', $evaluationcompleted);
    }

    $allitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluationcompleted->evaluation));

    //save all the new values from evaluation_valuetmp
    //get all values of tmp-completed
    $params = array('completed' => $evaluationcompletedtmp->id);
    $values = $DB->get_records('evaluation_valuetmp', $params);
    foreach ($values as $value) {
        //check if there are depend items
        $item = $DB->get_record('evaluation_item', array('id' => $value->item));
        if ($item->dependitem > 0 && isset($allitems[$item->dependitem])) {
            $ditem = $allitems[$item->dependitem];
            while ($ditem !== null) {
                $check = evaluation_compare_item_value($tmpcplid,
                        $ditem,
                        $item->dependvalue,
                        true);
                if (!$check) {
                    break;
                }
                if ($ditem->dependitem > 0 && isset($allitems[$ditem->dependitem])) {
                    $item = $ditem;
                    $ditem = $allitems[$ditem->dependitem];
                } else {
                    $ditem = null;
                }
            }

        } else {
            $check = true;
        }
        if ($check) {
            unset($value->id);
            $value->completed = $evaluationcompleted->id;
            $DB->insert_record('evaluation_value', $value);
        }
    }
    //drop all the tmpvalues
    $DB->delete_records('evaluation_valuetmp', array('completed' => $tmpcplid));
    $DB->delete_records('evaluation_completedtmp', array('id' => $tmpcplid));

    // Trigger event for the delete action we performed.
    $cm = get_coursemodule_from_instance('evaluation', $evaluationcompleted->evaluation);
    $event = \mod_evaluation\event\response_submitted::create_from_record($evaluationcompleted, $cm);
    $event->trigger();
    return $evaluationcompleted->id;

}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_delete_completedtmp() {
    throw new coding_exception('evaluation_delete_completedtmp() can not be used anymore.');

}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the pagebreaks
////////////////////////////////////////////////

/**
 * this creates a pagebreak.
 * a pagebreak is a special kind of item
 *
 * @param int $evaluationid
 * @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
 * @global object
 */
function evaluation_create_pagebreak($evaluationid) {
    global $DB;

    //check if there already is a pagebreak on the last position
    $lastposition = $DB->count_records('evaluation_item', array('evaluation' => $evaluationid));
    if ($lastposition == evaluation_get_last_break_position($evaluationid)) {
        return false;
    }

    $item = new stdClass();
    $item->evaluation = $evaluationid;

    $item->template = 0;

    $item->name = '';

    $item->presentation = '';
    $item->hasvalue = 0;

    $item->typ = 'pagebreak';
    $item->position = $lastposition + 1;

    $item->required = 0;

    return $DB->insert_record('evaluation_item', $item);
}

/**
 * get all positions of pagebreaks in the given evaluation
 *
 * @param int $evaluationid
 * @return array all ordered pagebreak positions
 * @global object
 */
function evaluation_get_all_break_positions($evaluationid) {
    global $DB;

    $params = array('typ' => 'pagebreak', 'evaluation' => $evaluationid);
    $allbreaks = $DB->get_records_menu('evaluation_item', $params, 'position', 'id, position');
    if (!$allbreaks) {
        return false;
    }
    return array_values($allbreaks);
}

/**
 * get the position of the last pagebreak
 *
 * @param int $evaluationid
 * @return int the position of the last pagebreak
 */
function evaluation_get_last_break_position($evaluationid) {
    if (!$allbreaks = evaluation_get_all_break_positions($evaluationid)) {
        return false;
    }
    return $allbreaks[safeCount($allbreaks) - 1];
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_get_page_to_continue() {
    throw new coding_exception('evaluation_get_page_to_continue() can not be used anymore.');
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the values
////////////////////////////////////////////////

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_clean_input_value() {
    throw new coding_exception('evaluation_clean_input_value() can not be used anymore. '
            . 'Items must implement complete_form_element().');

}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_save_values() {
    throw new coding_exception('evaluation_save_values() can not be used anymore.');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_save_guest_values() {
    throw new coding_exception('evaluation_save_guest_values() can not be used anymore.');
}

/**
 * get the value from the given item related to the given completed.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp
 *
 * @param int $completeid
 * @param int $itemid
 * @param boolean $tmp
 * @return mixed the value, the type depends on plugin-definition
 * @global object
 */
function evaluation_get_item_value($completedid, $itemid, $tmp = false) {
    global $DB;

    $tmpstr = $tmp ? 'tmp' : '';
    $params = array('completed' => $completedid, 'item' => $itemid);
    return $DB->get_field('evaluation_value' . $tmpstr, 'value', $params);
}

/**
 * compares the value of the itemid related to the completedid with the dependvalue.
 * this is used if a depend item is set.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp.
 *
 * @param int $completedid
 * @param stdClass|int $item
 * @param mixed $dependvalue
 * @param bool $tmp
 * @return bool
 */
function evaluation_compare_item_value($completedid, $item, $dependvalue, $tmp = false) {
    global $DB;

    if (is_int($item)) {
        $item = $DB->get_record('evaluation_item', array('id' => $item));
    }

    $dbvalue = evaluation_get_item_value($completedid, $item->id, $tmp);

    $itemobj = evaluation_get_item_class($item->typ);
    return $itemobj->compare_value($item, $dbvalue, $dependvalue); //true or false
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_check_values() {
    throw new coding_exception('evaluation_check_values() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_create_values() {
    throw new coding_exception('evaluation_create_values() can not be used anymore.');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_update_values() {
    throw new coding_exception('evaluation_update_values() can not be used anymore.');
}

/**
 * get the values of an item depending on the given groupid.
 * if the evaluation is anonymous so the values are shuffled
 *
 * @param object $item
 * @param int $groupid
 * @param int $courseid
 * @param bool $ignore_empty if this is set true so empty values are not delivered
 * @return array the value-records
 * @global object
 * @global object
 */
function evaluation_get_group_values($item,
        $groupid = false,
        $courseid = false,
        $teacherid = false,
        $course_of_studies = false,
        $department = false,
        $subquery = "",
        $ignore_empty = false
        ) {
    global $CFG, $DB;

    //if the groupid is given?
    if (intval($groupid) > 0) {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('fbv.value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        } else {
            $ignore_empty_select = "";
        }

        $query = 'SELECT fbv .  *
                    FROM {evaluation_value} fbv, {evaluation_completed} fbc, {groups_members} gm
                   WHERE fbv.item = :itemid
                         AND fbv.completed = fbc.id
                         AND fbc.userid = gm.userid
                         ' . $ignore_empty_select . '
                         AND gm.groupid = :groupid
                ORDER BY fbc.timemodified';
        $params += array('itemid' => $item->id, 'groupid' => $groupid);
        $values = $DB->get_records_sql($query, $params);

    } else {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        } else {
            $ignore_empty_select = "";
        }

        $select = "item = :itemid " . $ignore_empty_select;
        $params += array('itemid' => $item->id);
        if ($courseid) {
            $select .= " AND courseid = :courseid ";
            $params += array('courseid' => $courseid);
        }
        if ($teacherid) {
            $select .= " AND teacherid = :teacherid ";
            $params += array('teacherid' => $teacherid);
        }
        if ($course_of_studies) {
            $select .= " AND course_of_studies = :course_of_studies ";
            $params += array('course_of_studies' => $course_of_studies);
        }
        if ($department) {
            global $evaluationstructure;
            $filterD = str_ireplace("completed.", "",$evaluationstructure->get_department_filter());
            $select .= "$filterD ";
        }
        if ($subquery){
            $select .= " $subquery ";
        }
        if (isset($_SESSION['studentid'])) {

            $query = "SELECT completed.id,completed.courseid
                        FROM {evaluation_completed} completed
                        WHERE completed.evaluation = :evaluation
						AND completed.userid = :userid";
            $params = array('evaluation' => $item->evaluation, 'userid' => $_SESSION['studentid']);
            if ($myCourses = $DB->get_records_sql($query, $params)) {
                $courses = array();
                foreach ($myCourses as $myCourse) {
                    $courses[] = $myCourse->courseid;
                }
                $courses = implode(",", $courses);
            } else {
                $courses = "0";
            }
            $query = "SELECT *
                        FROM {evaluation_value} WHERE itemid => $item->id AND courseid IN ($courses)";
            $values = $DB->get_records_sql($query);
        }
        else {
            $values = $DB->get_records_select('evaluation_value', $select, $params);
        }
    }

    if (false AND is_siteadmin()){
        echo nl2br("<hr>Params: ".var_export($params,true));
        echo nl2br("<hr>Select: ".var_export($select,true));
        echo nl2br("<hr>Values: ".var_export($values,true));
    }
    $params = array('id' => $item->evaluation);
    if ($DB->get_field('evaluation', 'anonymous', $params) == EVALUATION_ANONYMOUS_YES) {
        if (is_array($values)) {
            shuffle($values);
        }
    }

    return $values;
}

/**
 * check for multiple_submit = false.
 * if the evaluation is global so the courseid must be given
 *
 * @param int $evaluationid
 * @param int $courseid
 * @return boolean true if the evaluation already is submitted otherwise false
 * @global object
 * @global object
 */
function evaluation_is_already_submitted($evaluationid, $courseid = false) {
    global $USER, $DB;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    $params = array('userid' => $USER->id, 'evaluation' => $evaluationid);
    if ($courseid) {
        $params['courseid'] = $courseid;
    }
    return $DB->record_exists('evaluation_completed', $params);
}

/**
 * @deprecated since Moodle 3.1. Use evaluation_get_current_completed_tmp() or evaluation_get_last_completed.
 */
function evaluation_get_current_completed() {
    throw new coding_exception('evaluation_get_current_completed() can not be used anymore. Please ' .
            'use either evaluation_get_current_completed_tmp() or evaluation_get_last_completed()');
}

/**
 * get the completeds depending on the given groupid.
 *
 * @param object $evaluation
 * @param int $groupid
 * @param int $courseid
 * @return mixed array of found completeds otherwise false
 * @global object
 * @global object
 */
function evaluation_get_completeds_group_count($evaluation, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if (intval($groupid) > 0) {
        $query = "SELECT fbc.*
                    FROM {evaluation_completed} fbc, {groups_members} gm
                   WHERE fbc.evaluation = ?
                         AND gm.groupid = ?
                         AND fbc.userid = gm.userid";
        if ($values = $DB->get_records_sql($query, array($evaluation->id, $groupid))) {
            return $values;
        } else {
            return false;
        }
    } else {
        if ($courseid) {
            $query = "SELECT DISTINCT fbc.*
                        FROM {evaluation_completed} fbc, {evaluation_value} fbv
                        WHERE fbc.id = fbv.completed
                            AND fbc.evaluation = ?
                            AND fbv.courseid = ?
                        ORDER BY random_response";
            if ($values = $DB->get_records_sql($query, array($evaluation->id, $courseid))) {
                return $values;
            } else {
                return false;
            }
        } else {
            if ($values = $DB->get_records('evaluation_completed', array('evaluation' => $evaluation->id))) {
                return $values;
            } else {
                return false;
            }
        }
    }
}

/**
 * get the count of completeds depending on the given groupid.
 *
 * @param object $evaluation
 * @param int $groupid
 * @param int $courseid
 * @return mixed count of completeds or false
 * @global object
 * @global object
 */
function evaluation_get_completeds_group_safeCount($evaluation, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if ($courseid > 0 and !$groupid <= 0) {
        $sql = "SELECT id, COUNT(item) AS ci
                  FROM {evaluation_value}
                 WHERE courseid  = ?
              GROUP BY item ORDER BY ci DESC";
        if ($foundrecs = $DB->get_records_sql($sql, array($courseid))) {
            $foundrecs = array_values($foundrecs);
            return $foundrecs[0]->ci;
        }
        return false;
    }
    if ($values = evaluation_get_completeds_group($evaluation, $groupid)) {
        return safeCount($values);
    } else {
        return false;
    }
}

/**
 * deletes all completed-recordsets from a evaluation.
 * all related data such as values also will be deleted
 *
 * @param stdClass|int $evaluation
 * @param stdClass|cm_info $cm
 * @param stdClass $course
 * @return void
 */
function evaluation_delete_all_completeds($evaluation, $cm = null, $course = null) {
    global $DB;

    if (is_int($evaluation)) {
        $evaluation = $DB->get_record('evaluation', array('id' => $evaluation));
    }

    if (!$completeds = $DB->get_records('evaluation_completed', array('evaluation' => $evaluation->id))) {
        return;
    }

    if (!$course && !($course = $DB->get_record('course', array('id' => $evaluation->course)))) {
        return false;
    }

    if (!$cm && !($cm = get_coursemodule_from_instance('evaluation', $evaluation->id))) {
        return false;
    }

    foreach ($completeds as $completed) {
        evaluation_delete_completed($completed, $evaluation, $cm, $course);
    }
}

/**
 * deletes a completed given by completedid.
 * all related data such values or tracking data also will be deleted
 *
 * @param int|stdClass $completed
 * @param stdClass $evaluation
 * @param stdClass|cm_info $cm
 * @param stdClass $course
 * @return boolean
 */
function evaluation_delete_completed($completed, $evaluation = null, $cm = null, $course = null) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    if (!isset($completed->id)) {
        if (!$completed = $DB->get_record('evaluation_completed', array('id' => $completed))) {
            return false;
        }
    }

    if (!$evaluation && !($evaluation = $DB->get_record('evaluation', array('id' => $completed->evaluation)))) {
        return false;
    }

    if (!$course && !($course = $DB->get_record('course', array('id' => $evaluation->course)))) {
        return false;
    }

    if (!$cm && !($cm = get_coursemodule_from_instance('evaluation', $evaluation->id))) {
        return false;
    }

    //first we delete all related values
    $DB->delete_records('evaluation_value', array('completed' => $completed->id));

    // Delete the completed record.
    $return = $DB->delete_records('evaluation_completed', array('id' => $completed->id));

    // Update completion state
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC && $evaluation->completionsubmit) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
    }
    // Trigger event for the delete action we performed.
    $event = \mod_evaluation\event\response_deleted::create_from_record($completed, $cm, $evaluation);
    $event->trigger();

    return $return;
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle sitecourse mapping
////////////////////////////////////////////////

/**
 * @deprecated since 3.1
 */
function evaluation_is_course_in_sitecourse_map() {
    throw new coding_exception('evaluation_is_course_in_sitecourse_map() can not be used anymore.');
}

/**
 * @deprecated since 3.1
 */
function evaluation_is_evaluation_in_sitecourse_map() {
    throw new coding_exception('evaluation_is_evaluation_in_sitecourse_map() can not be used anymore.');
}

/**
 * gets the evaluations from table evaluation_sitecourse_map.
 * this is used to show the global evaluations on the evaluation block
 * all evaluations with the following criteria will be selected:<br />
 *
 * 1) all evaluations which id are listed together with the courseid in sitecoursemap and<br />
 * 2) all evaluations which not are listed in sitecoursemap
 *
 * @param int $courseid
 * @return array the evaluation-records
 * @global object
 */
function evaluation_get_evaluations_from_sitecourse_map($courseid) {
    global $DB;

    //first get all evaluations listed in sitecourse_map with named courseid
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.course AS course,
                   f.name AS name,
                   f.timeopen AS timeopen,
                   f.timeclose AS timeclose,
				   f.semester AS semester,
				   f.min_results AS min_results,				   
                   f.privileged_users AS privileged_users,
                   f.filter_course_of_studies AS filter_course_of_studies,
				   f.filter_courses AS filter_courses,
				   f.teamteaching AS teamteaching
            FROM {evaluation} f, {course_modules} cm, {evaluation_sitecourse_map} sm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '" . SITEID . "'
                   AND m.id = cm.module
                   AND m.name = 'evaluation'
                   AND sm.courseid = ?
                   AND sm.evaluationid = f.id
				   ORDER BY f.id DESC";

    if (!$evaluations1 = $DB->get_records_sql($sql, array($courseid))) {
        $evaluations1 = array();
    }

    //second get all evaluations not listed in sitecourse_map
    $evaluations2 = array();
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.course AS course,
                   f.name AS name,
                   f.timeopen AS timeopen,
                   f.timeclose AS timeclose,
				   f.semester AS semester,
				   f.min_results AS min_results,				   
                   f.privileged_users AS privileged_users,
                   f.filter_course_of_studies AS filter_course_of_studies,
				   f.filter_courses AS filter_courses,
				   f.teamteaching AS teamteaching
            FROM {evaluation} f, {course_modules} cm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '" . SITEID . "'
                   AND m.id = cm.module
                   AND m.name = 'evaluation'
				   ORDER BY f.id DESC";
    if (!$allevaluations = $DB->get_records_sql($sql)) {
        $allevaluations = array();
    }
    foreach ($allevaluations as $a) {
        if (!$DB->record_exists('evaluation_sitecourse_map', array('evaluationid' => $a->id))) {
            $evaluations2[] = $a;
        }
    }

    $evaluations = array_merge($evaluations1, $evaluations2);
    $modinfo = get_fast_modinfo(SITEID);
    return array_filter($evaluations, function($f) use ($modinfo) {
        return ($cm = $modinfo->get_cm($f->cmid)) && $cm->uservisible;
    });

}

/**
 * Gets the courses from table evaluation_sitecourse_map
 *
 * @param int $evaluationid
 * @return array the course-records
 */
function evaluation_get_courses_from_sitecourse_map($evaluationid) {
    global $DB;

    $sql = "SELECT c.id, c.fullname, c.shortname
              FROM {evaluation_sitecourse_map} f, {course} c
             WHERE c.id = f.courseid
                   AND f.evaluationid = ?
          ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array($evaluationid));

}

/**
 * Updates the course mapping for the evaluation
 *
 * @param stdClass $evaluation
 * @param array $courses array of course ids
 */
function evaluation_update_sitecourse_map($evaluation, $courses) {
    global $DB;
    if (empty($courses)) {
        $courses = array();
    }
    $currentmapping = $DB->get_fieldset_select('evaluation_sitecourse_map', 'courseid', 'evaluationid=?', array($evaluation->id));
    foreach (array_diff($courses, $currentmapping) as $courseid) {
        $DB->insert_record('evaluation_sitecourse_map', array('evaluationid' => $evaluation->id, 'courseid' => $courseid));
    }
    foreach (array_diff($currentmapping, $courses) as $courseid) {
        $DB->delete_records('evaluation_sitecourse_map', array('evaluationid' => $evaluation->id, 'courseid' => $courseid));
    }
    // TODO MDL-53574 add events.
}

/**
 * @deprecated since 3.1
 */
function evaluation_clean_up_sitecourse_map() {
    throw new coding_exception('evaluation_clean_up_sitecourse_map() can not be used anymore.');
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//not relatable functions
////////////////////////////////////////////////

/**
 * @deprecated since 3.1
 */
function evaluation_print_numeric_option_list() {
    throw new coding_exception('evaluation_print_numeric_option_list() can not be used anymore.');
}

/**
 * sends an email to the teachers of the course where the given evaluation is placed.
 *
 * @param object $cm the coursemodule-record
 * @param object $evaluation
 * @param object $course
 * @param stdClass|int $user
 * @param stdClass $completed record from evaluation_completed if known
 * @return void
 * @global object
 * @global object
 * @uses EVALUATION_ANONYMOUS_NO
 * @uses FORMAT_PLAIN
 */
function evaluation_send_email($cm, $evaluation, $course, $user, $completed = null) {
    global $CFG, $DB, $PAGE;

    if ($evaluation->email_notification == 0) {  // No need to do anything
        return;
    }

    if (!is_object($user)) {
        $user = $DB->get_record('user', array('id' => $user));
    }

    if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
        $groupmode = $cm->groupmode;
    } else {
        $groupmode = $course->groupmode;
    }

    if ($groupmode == SEPARATEGROUPS) {
        $groups = $DB->get_records_sql_menu("SELECT g.name, g.id
                                               FROM {groups} g, {groups_members} m
                                              WHERE g.courseid = ?
                                                    AND g.id = m.groupid
                                                    AND m.userid = ?
                                           ORDER BY name ASC", array($course->id, $user->id));
        $groups = array_values($groups);

        $teachers = evaluation_get_receivemail_users($cm->id, $groups);
    } else {
        $teachers = evaluation_get_receivemail_users($cm->id);
    }

    if ($teachers) {

        $strevaluations = get_string('modulenameplural', 'evaluation');
        $strevaluation = get_string('modulename', 'evaluation');

        if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO) {
            $printusername = fullname($user);
        } else {
            $printusername = get_string('anonymous_user', 'evaluation');
        }

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->evaluation = format_string(ev_get_tr($evaluation->name), true);
            $info->url = $CFG->wwwroot . '/mod/evaluation/show_entries.php?' .
                    'id=' . $cm->id . '&' .
                    'userid=' . $user->id;
            if ($completed) {
                $info->url .= '&showcompleted=' . $completed->id;
                if ($evaluation->course == SITEID) {
                    // Course where evaluation was completed (for site evaluations only).
                    $info->url .= '&courseid=' . $completed->courseid;
                }
            }

            $a = array('username' => $info->username, 'evaluationname' => ev_get_tr($evaluation->name));

            $postsubject = get_string('evaluationcompleted', 'evaluation', $a);
            $posttext = evaluation_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = evaluation_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            $customdata = [
                    'cmid' => $cm->id,
                    'instance' => $evaluation->id,
            ];
            if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO) {
                $eventdata = new \core\message\message();
                $eventdata->anonymous = false;
                $eventdata->courseid = $course->id;
                $eventdata->name = 'submission';
                $eventdata->component = 'mod_evaluation';
                $eventdata->userfrom = $user;
                $eventdata->userto = $teacher;
                $eventdata->subject = $postsubject;
                $eventdata->fullmessage = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = $posthtml;
                $eventdata->smallmessage = '';
                $eventdata->courseid = $course->id;
                $eventdata->contexturl = $info->url;
                $eventdata->contexturlname = $info->evaluation;
                // User image.
                $userpicture = new user_picture($user);
                $userpicture->size = 1; // Use f1 size.
                $userpicture->includetoken = $teacher->id; // Generate an out-of-session token for the user receiving the message.
                $customdata['notificationiconurl'] = $userpicture->get_url($PAGE)->out(false);
                $eventdata->customdata = $customdata;
                message_send($eventdata);
            } else {
                $eventdata = new \core\message\message();
                $eventdata->anonymous = true;
                $eventdata->courseid = $course->id;
                $eventdata->name = 'submission';
                $eventdata->component = 'mod_evaluation';
                $eventdata->userfrom = $teacher;
                $eventdata->userto = $teacher;
                $eventdata->subject = $postsubject;
                $eventdata->fullmessage = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = $posthtml;
                $eventdata->smallmessage = '';
                $eventdata->courseid = $course->id;
                $eventdata->contexturl = $info->url;
                $eventdata->contexturlname = $info->evaluation;
                // Evaluation icon if can be easily reachable.
                $customdata['notificationiconurl'] = ($cm instanceof cm_info) ? $cm->get_icon_url()->out() : '';
                $eventdata->customdata = $customdata;
                message_send($eventdata);
            }
        }
    }
}

/**
 * sends an email to the teachers of the course where the given evaluation is placed.
 *
 * @param object $cm the coursemodule-record
 * @param object $evaluation
 * @param object $course
 * @return void
 * @global object
 * @uses FORMAT_PLAIN
 */
function evaluation_send_email_anonym($cm, $evaluation, $course) {
    global $CFG;

    if ($evaluation->email_notification == 0) { // No need to do anything
        return;
    }

    $teachers = evaluation_get_receivemail_users($cm->id);

    if ($teachers) {

        $strevaluations = get_string('modulenameplural', 'evaluation');
        $strevaluation = get_string('modulename', 'evaluation');
        $printusername = get_string('anonymous_user', 'evaluation');

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->evaluation = format_string(ev_get_tr($evaluation->name), true);
            $info->url = $CFG->wwwroot . '/mod/evaluation/show_entries.php?id=' . $cm->id;

            $a = array('username' => $info->username, 'evaluationname' => ev_get_tr($evaluation->name));

            $postsubject = get_string('evaluationcompleted', 'evaluation', $a);
            $posttext = evaluation_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = evaluation_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            $eventdata = new \core\message\message();
            $eventdata->anonymous = true;
            $eventdata->courseid = $course->id;
            $eventdata->name = 'submission';
            $eventdata->component = 'mod_evaluation';
            $eventdata->userfrom = $teacher;
            $eventdata->userto = $teacher;
            $eventdata->subject = $postsubject;
            $eventdata->fullmessage = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = $posthtml;
            $eventdata->smallmessage = '';
            $eventdata->courseid = $course->id;
            $eventdata->contexturl = $info->url;
            $eventdata->contexturlname = $info->evaluation;
            $eventdata->customdata = [
                    'cmid' => $cm->id,
                    'instance' => $evaluation->id,
                    'notificationiconurl' => ($cm instanceof cm_info) ? $cm->get_icon_url()->out() : '',  // Performance wise.
            ];

            message_send($eventdata);
        }
    }
}

/**
 * send the text-part of the email
 *
 * @param object $info includes some infos about the evaluation you want to send
 * @param object $course
 * @return string the text you want to post
 */
function evaluation_send_email_text($info, $course) {
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $posttext = $courseshortname . ' -> ' . get_string('modulenameplural', 'evaluation') . ' -> ' .
            $info->evaluation . "\n";
    $posttext .= '---------------------------------------------------------------------' . "\n";
    $posttext .= get_string("emailteachermail", "evaluation", $info) . "\n";
    $posttext .= '---------------------------------------------------------------------' . "\n";
    return $posttext;
}

/**
 * send the html-part of the email
 *
 * @param object $info includes some infos about the evaluation you want to send
 * @param object $course
 * @return string the text you want to post
 * @global object
 */
function evaluation_send_email_html($info, $course, $cm) {
    global $CFG;
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $course_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
    $evaluation_all_url = $CFG->wwwroot . '/mod/evaluation/index.php?id=' . $course->id;
    $evaluation_url = $CFG->wwwroot . '/mod/evaluation/view.php?id=' . $cm->id;

    $posthtml = '<p><font face="sans-serif">' .
            '<a href="' . $course_url . '">' . $courseshortname . '</a> ->' .
            '<a href="' . $evaluation_all_url . '">' . get_string('modulenameplural', 'evaluation') . '</a> ->' .
            '<a href="' . $evaluation_url . '">' . $info->evaluation . '</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif">';
    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'evaluation', $info) . '</p>';
    $posthtml .= '</font><hr />';
    return $posthtml;
}

/**
 * @param string $url
 * @return string
 */
function evaluation_encode_target_url($url) {
    if (strpos($url, '?')) {
        list($part1, $part2) = explode('?', $url, 2); //maximal 2 parts
        return $part1 . '?' . htmlentities($part2);
    } else {
        return $url;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $evaluationnode The node to add module settings to
 */
function evaluation_extend_settings_navigation(settings_navigation $settings,
        navigation_node $evaluationnode) {

    global $PAGE;

    if (!$context = context_module::instance($PAGE->cm->id, IGNORE_MISSING)) {
        throw new moodle_exception('badcontext');
    }

    if (has_capability('mod/evaluation:edititems', $context)) {
        $questionnode = $evaluationnode->add(get_string('questions', 'evaluation'));

        $questionnode->add(get_string('edit_items', 'evaluation'),
                new moodle_url('/mod/evaluation/edit.php',
                        array('id' => $PAGE->cm->id,
                                'do_show' => 'edit')));

        $questionnode->add(get_string('export_questions', 'evaluation'),
                new moodle_url('/mod/evaluation/export.php',
                        array('id' => $PAGE->cm->id,
                                'action' => 'exportfile')));

        $questionnode->add(get_string('import_questions', 'evaluation'),
                new moodle_url('/mod/evaluation/import.php',
                        array('id' => $PAGE->cm->id)));

        $questionnode->add(get_string('templates', 'evaluation'),
                new moodle_url('/mod/evaluation/edit.php',
                        array('id' => $PAGE->cm->id,
                                'do_show' => 'templates')));
    }

    if (has_capability('mod/evaluation:mapcourse', $context) && $PAGE->course->id == SITEID) {
        $evaluationnode->add(get_string('mappedcourses', 'evaluation'),
                new moodle_url('/mod/evaluation/mapcourse.php',
                        array('id' => $PAGE->cm->id)));
    }

    if (has_capability('mod/evaluation:viewreports', $context)) {
        $evaluation = $PAGE->activityrecord;
        if ($evaluation->course == SITEID) {
            $evaluationnode->add(get_string('analysis', 'evaluation'),
                    new moodle_url('/mod/evaluation/analysis_course.php',
                            array('id' => $PAGE->cm->id)));
        } else {
            $evaluationnode->add(get_string('analysis', 'evaluation'),
                    new moodle_url('/mod/evaluation/analysis.php',
                            array('id' => $PAGE->cm->id)));
        }

        $evaluationnode->add(get_string('show_entries', 'evaluation'),
                new moodle_url('/mod/evaluation/show_entries.php',
                        array('id' => $PAGE->cm->id)));

        if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO and $evaluation->course != SITEID) {
            $evaluationnode->add(get_string('show_nonrespondents', 'evaluation'),
                    new moodle_url('/mod/evaluation/show_nonrespondents.php',
                            array('id' => $PAGE->cm->id)));
        }
    }
}

function evaluation_init_evaluation_session() {
    //initialize the evaluation-Session - not nice at all!!
    global $SESSION;
    if (!empty($SESSION)) {
        if (!isset($SESSION->evaluation) or !is_object($SESSION->evaluation)) {
            $SESSION->evaluation = new stdClass();
        }
    }
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function evaluation_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-evaluation-*' => get_string('page-mod-evaluation-x', 'evaluation'));
    return $module_pagetype;
}

/**
 * Move save the items of the given $evaluation in the order of $itemlist.
 *
 * @param string $itemlist a comma separated list with item ids
 * @param stdClass $evaluation
 * @return bool true if success
 */
function evaluation_ajax_saveitemorder($itemlist, $evaluation) {
    global $DB;

    $result = true;
    $position = 0;
    foreach ($itemlist as $itemid) {
        $position++;
        $result = $result && $DB->set_field('evaluation_item',
                        'position',
                        $position,
                        array('id' => $itemid, 'evaluation' => $evaluation->id));
    }
    return $result;
}

/**
 * Checks if current user is able to view evaluation on this course.
 *
 * @param stdClass $evaluation
 * @param context_module $context
 * @param int $courseid
 * @return bool
 */
function evaluation_can_view_analysis($evaluation, $context, $courseid = false) {
    if (has_capability('mod/evaluation:viewreports', $context)) {
        return true;
    }

    if (intval($evaluation->publish_stats) != 1 ||
            !has_capability('mod/evaluation:viewanalysepage', $context)) {
        return false;
    }

    if (!isloggedin() || isguestuser()) {
        // There is no tracking for the guests, assume that they can view analysis if condition above is satisfied.
        return $evaluation->course == SITEID;
    }

    return evaluation_is_already_submitted($evaluation->id, $courseid);
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_evaluation_get_fontawesome_icon_map() {
    return [
            'mod_evaluation:required' => 'fa-exclamation-circle',
            'mod_evaluation:notrequired' => 'fa-question-circle-o',
    ];
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param cm_info $cm course module data
 * @param int $from the time to check updates from
 * @param array $filter if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.3
 */
function evaluation_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check for new attempts.
    $updates->attemptsfinished = (object) array('updated' => false);
    $updates->attemptsunfinished = (object) array('updated' => false);
    $select = 'evaluation = ? AND userid = ? AND timemodified > ?';
    $params = array($cm->instance, $USER->id, $from);

    $attemptsfinished = $DB->get_records_select('evaluation_completed', $select, $params, '', 'id');
    if (!empty($attemptsfinished)) {
        $updates->attemptsfinished->updated = true;
        $updates->attemptsfinished->itemids = array_keys($attemptsfinished);
    }
    $attemptsunfinished = $DB->get_records_select('evaluation_completedtmp', $select, $params, '', 'id');
    if (!empty($attemptsunfinished)) {
        $updates->attemptsunfinished->updated = true;
        $updates->attemptsunfinished->itemids = array_keys($attemptsunfinished);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/evaluation:viewreports', $cm->context)) {
        $select = 'evaluation = ? AND timemodified > ?';
        $params = array($cm->instance, $from);

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->userattemptsfinished = (object) array('updated' => false);
        $attemptsfinished = $DB->get_records_select('evaluation_completed', $select, $params, '', 'id');
        if (!empty($attemptsfinished)) {
            $updates->userattemptsfinished->updated = true;
            $updates->userattemptsfinished->itemids = array_keys($attemptsfinished);
        }

        $updates->userattemptsunfinished = (object) array('updated' => false);
        $attemptsunfinished = $DB->get_records_select('evaluation_completedtmp', $select, $params, '', 'id');
        if (!empty($attemptsunfinished)) {
            $updates->userattemptsunfinished->updated = true;
            $updates->userattemptsunfinished->itemids = array_keys($attemptsunfinished);
        }
    }

    return $updates;
}

/**
 * Add a get_coursemodule_info function in case any evaluation type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function evaluation_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionsubmit, timeopen, timeclose, teamteaching, semester, anonymous, course';
    if (!$evaluation = $DB->get_record('evaluation', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = ev_get_tr($evaluation->name);

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = "$evaluation->intro";  //format_module_intro('evaluation', $evaluation, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $evaluation->completionsubmit;
    }
    // Populate some other values that can be used in calendar or on dashboard.
    if ($evaluation->timeopen) {
        $result->customdata['timeopen'] = $evaluation->timeopen;
    }
    if ($evaluation->timeclose) {
        $result->customdata['timeclose'] = $evaluation->timeclose;
    }
    if ($evaluation->anonymous) {
        $result->customdata['anonymous'] = $evaluation->anonymous;
    }
    // patched by harry
    //$result->customdata['name'] = ev_get_tr($evaluation->name);
    //$result->customdata['course'] = $evaluation->course;
    // end patch
    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_evaluation_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
            || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'evaluation');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the sbumission start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_evaluation_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == EVALUATION_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the choice activity.
        if (!empty($instance->timeclose)) {
            $maxdate = [
                    $instance->timeclose,
                    get_string('openafterclose', 'evaluation')
            ];
        }
    } else if ($event->eventtype == EVALUATION_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the choice activity.
        if (!empty($instance->timeopen)) {
            $mindate = [
                    $instance->timeopen,
                    get_string('closebeforeopen', 'evaluation')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This creates new events given as timeopen and closeopen by $evaluation.
 *
 * @param object $evaluation
 * @return void
 * @global object
 */
function evaluation_set_events($evaluation) {
    global $DB, $CFG;

    // Include calendar/lib.php.
    require_once($CFG->dirroot . '/calendar/lib.php');

    // Get CMID if not sent as part of $evaluation.
    if (!isset($evaluation->coursemodule)) {
        $cm = get_coursemodule_from_instance('evaluation', $evaluation->id, $evaluation->course);
        $evaluation->coursemodule = $cm->id;
    }

    // Evaluation start calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => 'evaluation', 'instance' => $evaluation->id, 'eventtype' => EVALUATION_EVENT_TYPE_OPEN));

    // by harry 20210610 - only temp usage
    /*if ( $evaluation->course == SITEID )
    {   // Evaluation is global Evaluation
        if ($eventid) {
			$calendarevent = calendar_event::load($eventid);
			$calendarevent->delete();
		}
		return;
    }*/

    if (isset($evaluation->timeopen) && $evaluation->timeopen > 0) //if ( false )
    {
        $event = new stdClass();
        $event->eventtype = EVALUATION_EVENT_TYPE_OPEN;
        $event->type = empty($evaluation->timeclose) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->name = get_string('calendarstart', 'evaluation', ev_get_tr($evaluation->name));
        $event->description =
                "$evaluation->intro"; //format_module_intro('evaluation', $evaluation, $evaluation->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->timestart = $evaluation->timeopen;
        $event->timesort = $evaluation->timeopen;
        $event->visible = instance_is_visible('evaluation', $evaluation);
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Event doesn't exist so create one.
            $event->courseid = $evaluation->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'evaluation';
            $event->instance = $evaluation->id;
            $event->eventtype = EVALUATION_EVENT_TYPE_OPEN;
            calendar_event::create($event, false);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    // Evaluation close calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => 'evaluation', 'instance' => $evaluation->id, 'eventtype' => EVALUATION_EVENT_TYPE_CLOSE));

    if (isset($evaluation->timeclose) && $evaluation->timeclose > 0) //if ( false )
    {
        $event = new stdClass();
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = EVALUATION_EVENT_TYPE_CLOSE;
        $event->name = get_string('calendarend', 'evaluation', ev_get_tr($evaluation->name));
        $event->description =
                "$evaluation->intro"; // format_module_intro('evaluation', $evaluation, $evaluation->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->timestart = $evaluation->timeclose;
        $event->timesort = $evaluation->timeclose;
        $event->visible = instance_is_visible('evaluation', $evaluation);
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Event doesn't exist so create one.
            $event->courseid = $evaluation->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'evaluation';
            $event->instance = $evaluation->id;
            calendar_event::create($event, false);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every evaluation event in the site is checked, else
 * only evaluation events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance Evaluation module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function evaluation_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('evaluation', array('id' => $instance), '*', MUST_EXIST);
        }
        evaluation_set_events($instance);
        return true;
    }

    if ($courseid) {
        if (!$evaluations = $DB->get_records("evaluation", array("course" => $courseid))) {
            return true;
        }
    } else {
        if (!$evaluations = $DB->get_records("evaluation")) {
            return true;
        }
    }

    foreach ($evaluations as $evaluation) {
        evaluation_set_events($evaluation);
    }
    return true;
}

/**
 * This function will update the evaluation module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the evaluation instance
 * according to the type of event provided.
 *
 * @param \calendar_event $event
 * @param stdClass $evaluation The module instance to get the range from
 * @throws \moodle_exception
 */
function mod_evaluation_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $evaluation) {
    global $CFG, $DB;

    if (empty($event->instance) || $event->modulename != 'evaluation') {
        return;
    }

    if ($event->instance != $evaluation->id) {
        return;
    }

    if (!in_array($event->eventtype, [EVALUATION_EVENT_TYPE_OPEN, EVALUATION_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == EVALUATION_EVENT_TYPE_OPEN) {
        // If the event is for the evaluation activity opening then we should
        // set the start time of the evaluation activity to be the new start
        // time of the event.
        if ($evaluation->timeopen != $event->timestart) {
            $evaluation->timeopen = $event->timestart;
            $evaluation->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == EVALUATION_EVENT_TYPE_CLOSE) {
        // If the event is for the evaluation activity closing then we should
        // set the end time of the evaluation activity to be the new start
        // time of the event.
        if ($evaluation->timeclose != $event->timestart) {
            $evaluation->timeclose = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $evaluation->timemodified = time();
        $DB->update_record('evaluation', $evaluation);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Is the event visible?
 *
 * This is used to determine global visibility of an event in all places throughout Moodle. For example,
 * the calendar event will be shown only to evaluation participants on their calendar
 *
 * @param calendar_event $event
 * @return bool Returns true if the event is visible to the current user, false otherwise.
 */
function mod_evaluation_core_calendar_is_event_visible(calendar_event $event) {
    global $DB, $USER;
    $evaluation = $DB->get_record('evaluation', ['id' => $event->instance], '*', MUST_EXIST);
    if (!empty($evaluation) and !empty(evaluation_is_user_enrolled($evaluation, $USER->id))) {
        return true;
    }
    return false;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_evaluation_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory,
        int $userid = 0) {
    global $USER, $DB;
    if (empty($userid)) {
        $userid = $USER->id;
    }
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['evaluation'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    $evaluationcompletion = new mod_evaluation_completion(null, $cm, 0, false, null, null, $userid);

    if (!empty($cm->customdata['timeclose']) && $cm->customdata['timeclose'] < time()) {
        // Evaluation is already closed, do not display it even if it was never submitted.
        return null;
    }

    if (!$evaluationcompletion->can_complete()) {    // The user can't complete the evaluation so there is no action for them.
        return null;
    }

    // The evaluation is actionable if it does not have timeopen or timeopen is in the past.
    $actionable = $evaluationcompletion->is_open();

    if ($actionable && $evaluationcompletion->is_already_submitted(false)) {
        // There is no need to display anything if the user has already submitted the evaluation.
        return null;
    }

    $anker = get_string('answerquestions', 'evaluation');
    //  global Evaluation
    $evaluation = $evaluationcompletion->get_evaluation();
    if ($evaluation->course == SITEID) {    // Don't show if user not participates
        //print "<br><br><br><hr>Course: ".$evaluation->course . " - is enrolled: "
        //.(evaluation_is_user_enrolled($evaluation, $userid ) ?"Ja":"Nein")."<br\n";exit;
        if (empty(evaluation_is_user_enrolled($evaluation, $userid))) {
            return null;
        }
        $anker = get_string('open_evaluation', 'evaluation');
    }

    return $factory->create_instance(
            $anker,
            new \moodle_url('/mod/evaluation/view.php', ['id' => $cm->id]),
            1,
            $actionable
    );
}
