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
 * Contains class mod_evaluation_structure
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Stores and manipulates the structure of the evaluation or template (items, pages, etc.)
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_structure {
    /** @var stdClass record from 'evaluation' table.
     * Reliably has fields: id, course, timeopen, timeclose, anonymous, completionsubmit.
     * For full object or to access any other field use $this->get_evaluation()
     */
    protected $evaluation;
    /** @var cm_info */
    protected $cm;
    /** @var int course where the evaluation is filled. For evaluations that are NOT on the front page this is 0 */
    protected $courseid = 0;
    /** @var int */
    protected $templateid;
    /** @var array */
    protected $allitems;
    /** @var array */
    protected $allcourses;
    protected $allstudies;

    protected $alldepartments;
    protected $allteachers;
    /** @var int */
    protected $userid;
    //** @var int teacherid for whom the evaluation is filled.  */
    protected $teacherid = 0;
    protected $course_of_studies = "";
    protected $course_of_studiesID = 0;
    protected $department = 0;
    protected $analysisCoS = 0;

    /**
     * Constructor
     *
     * @param stdClass $evaluation evaluation object, in case of the template
     *     this is the current evaluation the template is accessed from
     * @param stdClass|cm_info $cm course module object corresponding to the $evaluation
     *     (at least one of $evaluation or $cm is required)
     * @param int $courseid current course (for site evaluations only)
     * @param int $templateid template id if this class represents the template structure
     * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
     * @param int $teacherid for selected teacher (for teamteaching only). Set to 0 as default.
     */
    public function __construct($evaluation, $cm = false, $courseid = 0, $templateid = null, $userid = 0, $teacherid = 0,
            $course_of_studies = "", $course_of_studiesID = 0, $department = 0, $analysisCoS = 0) {
        global $USER;
        if ((empty($evaluation->id) || empty($evaluation->course)) && (empty($cm->instance) || empty($cm->course))) {
            throw new coding_exception('Either $evaluation or $cm must be passed to constructor');
        }
        $this->evaluation = $evaluation ?: (object) ['id' => $cm->instance, 'course' => $cm->course];
        if (!isset($this->evaluation->teamteaching)) {
            $this->evaluation = $this->get_evaluation();
        }
        if (!$cm) {
            $cm = get_evaluation_cm_from_id($evaluation);
        }
        $this->cm = $cm;
        $this->templateid = $templateid;
        $this->courseid = 0;
        $this->teacherid = 0;
        $this->course_of_studies = "";
        $this->course_of_studiesID = 0;
        $this->department = 0;
        $this->analysisCoS = $analysisCoS ?: 0;
        if ($this->evaluation->course == SITEID) {
            $this->courseid = $courseid ?: 0;
            $this->teacherid = $teacherid ?: 0;
            $this->course_of_studies = $course_of_studies ?: "";
            $this->course_of_studiesID = $course_of_studiesID ?: 0;
            $this->department = $department ?: 0;
        }
        //if ( $this->courseid AND empty($this->course_of_studies) )
        //{	$this->course_of_studies = evaluation_get_course_of_studies( $this->courseid, false ); }

        if (empty($userid)) {
            $this->userid = $USER->id;
        } else {
            $this->userid = $userid;
        }

        if (!$evaluation) {
            // If evaluation object was not specified, populate object with fields required for the most of methods.
            // These fields were added to course module cache in evaluation_get_coursemodule_info().
            // Full instance record can be retrieved by calling mod_evaluation_structure::get_evaluation().
            $customdata =
                    ($this->cm->customdata ?: []) + ['timeopen' => 0, 'timeclose' => 0, 'anonymous' => 0, 'teamteaching' => 0];
            $this->evaluation->timeopen = $customdata['timeopen'];
            $this->evaluation->timeclose = $customdata['timeclose'];
            $this->evaluation->anonymous = $customdata['anonymous'];
            $this->evaluation->teamteaching = $customdata['teamteaching'];
            $this->evaluation->completionsubmit = empty($this->cm->customdata['customcompletionrules']['completionsubmit']) ? 0 : 1;
        }
    }

    /**
     * Current evaluation
     *
     * @return stdClass
     */
    public function get_evaluation() {
        global $DB;
        if (!isset($this->evaluation->publish_stats) || !isset($this->evaluation->name) ||
                !isset($this->evaluation->teamteaching)) {
            // Make sure the full object is retrieved.
            $this->evaluation = $DB->get_record('evaluation', ['id' => $this->evaluation->id], '*', MUST_EXIST);
        }
        return $this->evaluation;
    }

    public function get_course_of_studiesID() {
        return $this->course_of_studiesID;
    }

    /**
     * Template id
     *
     * @return int
     */
    public function get_templateid() {
        return $this->templateid;
    }

    /**
     * Is this evaluation open (check timeopen and timeclose)
     *
     * @return bool
     */
    public function is_open() {
        $checktime = time();
        return (!$this->evaluation->timeopen || $this->evaluation->timeopen <= $checktime) &&
                (!$this->evaluation->timeclose || $this->evaluation->timeclose >= $checktime);
    }

    /**
     * Is the items list empty?
     *
     * @return bool
     */
    public function is_empty() {
        $items = $this->get_items();
        $displayeditems = array_filter($items, function($item) {
            return $item->typ !== 'pagebreak';
        });
        return !$displayeditems;
    }

    /**
     * Get all items in this evaluation or this template
     *
     * @param bool $hasvalueonly only count items with a value.
     * @return array of objects from evaluation_item with an additional attribute 'itemnr'
     */
    public function get_items($hasvalueonly = false) {
        global $DB;
        if ($this->allitems === null) {
            if ($this->templateid) {
                $this->allitems = $DB->get_records('evaluation_item', ['template' => $this->templateid], 'position');
            } else {
                $this->allitems = $DB->get_records('evaluation_item', ['evaluation' => $this->evaluation->id], 'position');
            }
            $idx = 1;
            foreach ($this->allitems as $id => $item) {
                $this->allitems[$id]->itemnr = $item->hasvalue ? ($idx++) : null;
            }
        }
        if ($hasvalueonly && $this->allitems) {
            return array_filter($this->allitems, function($item) {
                return $item->hasvalue;
            });
        }
        return $this->allitems;
    }

    /**
     * Is this evaluation anonymous?
     *
     * @return bool
     */
    public function is_anonymous() {
        return $this->evaluation->anonymous == EVALUATION_ANONYMOUS_YES;
    }

    /**
     * Returns the formatted text of the page after submit or null if it is not set
     *
     * @return string|null
     */
    public function page_after_submit() {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $pageaftersubmit = $this->get_evaluation()->page_after_submit;
        if (empty($pageaftersubmit)) {
            return null;
        }
        $pageaftersubmitformat = $this->get_evaluation()->page_after_submitformat;

        $context = context_module::instance($this->get_cm()->id);
        $output = file_rewrite_pluginfile_urls($pageaftersubmit,
                'pluginfile.php', $context->id, 'mod_evaluation', 'page_after_submit', 0);

        return format_text($output, $pageaftersubmitformat, array('overflowdiv' => true));
    }

    /**
     * Current course module
     *
     * @return stdClass
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Checks if current user is able to view evaluation on this course.
     *
     * @return bool
     */
    public function can_view_analysis() {
        global $USER;

        $context = context_module::instance($this->cm->id);
        if (has_capability('mod/evaluation:viewreports', $context, $this->userid)) {
            return true;
        }

        if (intval($this->get_evaluation()->publish_stats) != 1 ||
                !has_capability('mod/evaluation:viewanalysepage', $context, $this->userid)) {
            return false;
        }

        if ((!isloggedin() && $USER->id == $this->userid) || isguestuser($this->userid)) {
            // There is no tracking for the guests, assume that they can view analysis if condition above is satisfied.
            return $this->evaluation->course == SITEID;
        }

        return $this->is_already_submitted(true);
    }

    /**
     * check for multiple_submit = false.
     * if the evaluation is global so the courseid must be given
     *
     * @param bool $anycourseid if true checks if this evaluation was submitted in any course, otherwise checks $this->courseid .
     *     Applicable to frontpage evaluations only
     * @return bool true if the evaluation already is submitted otherwise false
     */
    public function is_already_submitted($anycourseid = false, $teacherid = false) {
        global $DB, $USER;

        if ((!isloggedin() && $USER->id == $this->userid) || isguestuser($this->userid)) {
            return false;
        }

        $params = array('userid' => $this->userid, 'evaluation' => $this->evaluation->id);
        if (!$anycourseid && $this->courseid) {
            $params['courseid'] = $this->courseid;
            if ($this->courseid and $this->get_evaluation()->teamteaching) {
                evaluation_get_course_teachers($this->courseid);
                $teachers = $_SESSION["allteachers"][$this->courseid];
                $completed = $DB->get_records_sql("select id,evaluation,courseid,course_of_studies,userid,teacherid from {evaluation_completed} 
							 WHERE evaluation=" . $this->evaluation->id . " AND userid=" . $this->userid . " AND courseid=" .
                        $this->courseid);
                return (count($completed) == count($teachers));
            }
        }
        return $DB->record_exists('evaluation_completed', $params);
    }

    /**
     * Check whether the evaluation is mapped to the given courseid.
     */
    public function check_course_is_mapped() {
        global $DB;
        if ($this->evaluation->course != SITEID) {
            return true;
        }
        if ($DB->get_records('evaluation_sitecourse_map', array('evaluationid' => $this->evaluation->id))) {
            $params = array('evaluationid' => $this->evaluation->id, 'courseid' => $this->courseid);
            if (!$DB->get_record('evaluation_sitecourse_map', $params)) {
                return false;
            }
        }
        // No mapping means any course is mapped.
        return true;
    }

    /**
     * If there are any new responses to the anonymous evaluation, re-shuffle all
     * responses and assign response number to each of them.
     */
    public function shuffle_anonym_responses() {
        global $DB;
        $params = array('evaluation' => $this->evaluation->id,
                'random_response' => 0,
                'anonymous_response' => EVALUATION_ANONYMOUS_YES);
        if ($DB->count_records('evaluation_completed', $params, 'random_response')) {
            // Get all of the anonymous records, go through them and assign a response id.
            print "<script>document.getElementById('evaluation_showinfo').innerHTML='<b>Reshuffling anonymous responses</b>...';</script>";
            @ob_flush();
            @ob_end_flush();
            @flush();
            @ob_start();
            unset($params['random_response']);
            $evaluationcompleteds = $DB->get_records('evaluation_completed', $params, 'id');
            shuffle($evaluationcompleteds);
            $num = 1;
            foreach ($evaluationcompleteds as $compl) {
                $compl->random_response = $num++;
                $DB->update_record('evaluation_completed', $compl);
            }
        }
    }

    /**
     * Counts records from {evaluation_completed} table for a given evaluation
     *
     * If $groupid or $this->courseid is set, the records are filtered by the group/course
     *
     * @param int $groupid
     * @return mixed array of found completeds otherwise false
     */
    public function count_completed_responses($groupid = 0, $today = false) {
        global $DB;
        $cosPrivileged_filter = "";
        if ($this->analysisCoS) {
            $cosPrivileged_filter = evaluation_get_cosPrivileged_filter($this->evaluation, "completed");
        }
        $params = ['evaluation' => $this->evaluation->id, 'groupid' => $groupid, 'courseid' => $this->courseid];
        $filter = $fstudies = $fteacher = $ftoday = $fcourseid = "";
        if ($this->get_course_of_studies()) {
            $filter .= " AND course_of_studies = '" . $this->get_course_of_studies() . "'";
            $fstudies = " AND completed.course_of_studies = :course_of_studies";
            $params['course_of_studies'] = $this->get_course_of_studies();
        }
        if ($this->get_teacherid()) {
            $filter .= " AND teacherid = " . $this->get_teacherid();
            $fteacher = " AND completed.teacherid = :teacherid";
            $params['teacherid'] = $this->get_teacherid();
        }
        $filterD = $this->get_department_filter();
        if (!empty($filterD)) {
            $filter .= $filterD;
        }
        if ($today) {
            $today = date("d M Y");
            $midnight = strtotime("$today 0:00");
            $dayend = strtotime("$today 23:59:60");
            $ftoday = " AND timemodified >= $midnight AND timemodified <= $dayend";
            $filter .= $ftoday;
        }
        if (intval($groupid) > 0) {
            $query = "SELECT COUNT(DISTINCT fbc.id)
                        FROM {evaluation_completed} completed, {groups_members} gm
                        WHERE completed.evaluation = :evaluation
                            AND gm.groupid = :groupid
                            AND completed.userid = gm.userid $fteacher $fstudies $filterD $ftoday";
            $filter .= " AND true";
        } else if ($this->courseid) {
            $query = "SELECT COUNT(completed.id)
                        FROM {evaluation_completed} completed
                        WHERE completed.evaluation = :evaluation
						AND completed.courseid = :courseid $fteacher $ftoday";
            $filter .= " AND courseid=" . $this->courseid;
        } else {
            $query = "SELECT COUNT(completed.id) FROM {evaluation_completed} completed 
						WHERE completed.evaluation = :evaluation $fteacher $fstudies $filterD $ftoday $cosPrivileged_filter";
        }
        $duplicated = 0;
        if (!$this->evaluation->teamteaching and empty($filter) and !$cosPrivileged_filter) {
            $duplicated = evaluation_count_duplicated_replies($this->evaluation);
        }
        return $DB->get_field_sql($query, $params) - $duplicated;
    }

    /**
     * current course of studies functions (for site evaluations only)
     *
     * @return stdClass
     */
    public function get_course_of_studies() {
        return $this->course_of_studies;
    }

    public function set_course_of_studies($studies) {
        $this->course_of_studies = $studies;
        return $this->course_of_studies;
    }


    public function set_department($studies) {
        if ( isset($_SESSION['CoS_department']) AND safeCount($_SESSION['CoS_department']) ) {
            $keys = array_keys($_SESSION['CoS_department']);
            $dept = array_searchi($studies, $keys);
            if ($dept AND isset($_SESSION['CoS_department'][$keys[$dept]]) ) {
                $this->$department = $_SESSION['CoS_department'][$keys[$dept]];
                return $this->$department;
            }
        }
    }


    public function get_department() {
        // if ( empty($this->department) AND !empty($this->course_of_studies;)){
        return $this->department;
    }

    public function get_department_filter() {
        $department = $this->get_department();
        if ($department AND isset($_SESSION['CoS_department']) AND safeCount($_SESSION['CoS_department'])) {
            $CoS = "'" . implode("','", array_keys($_SESSION['CoS_department'], $department)) . "'";
            return " AND completed.course_of_studies IN($CoS)";
        }
        return "";
    }
    /**
     * Id of the current teacher
     *
     * @return stdClass
     */
    public function get_teacherid() {
        return $this->teacherid;
    }

    /**
     * For the frontpage evaluation returns the list of courses with at least one completed evaluation
     *
     * @return array id=>name pairs of courses
     */
    public function get_completed_courses() {
        global $DB;

        if ($this->get_evaluation()->course != SITEID) {
            return [];
        }

        if ($this->allcourses !== null) {
            return $this->allcourses;
        }
        $is_open = evaluation_is_open($this->evaluation);
        $filter = $CoS_Filter = "";
        if ($this->get_teacherid()) {
            $filter .= " AND completed.teacherid=" . $this->get_teacherid();
        }
        if ($this->get_course_of_studies()) {
            $filter .= " AND completed.course_of_studies='" . $this->get_course_of_studies() . "'";
        }

        // handle CoS privileged users
        if ($this->analysisCoS) {
            $CoS_Filter = evaluation_get_cosPrivileged_filter($this->get_evaluation(), "completed");
        }
        if ($CoS_Filter) {
            $filter .= $CoS_Filter;
        }

        $filterD = $this->get_department_filter();
        if (!empty($filterD)) {
            $filter .= $filterD;
        }

        $sql = "SELECT DISTINCT ON (completed.courseid) completed.courseid, c.fullname, c.shortname
				FROM {evaluation_completed} completed, {"
                .($is_open ?"course" :"evaluation_enrolments")
                ."} c
				WHERE completed.evaluation = :evaluationid $filter AND completed.courseid=c."
                .($is_open ?"id" :"courseid and completed.evaluation=c.evaluation")
				. " ORDER BY completed.courseid ASC";
        $list = $DB->get_records_sql($sql, ['evaluationid' => $this->evaluation->id]);
        $this->allcourses = array();
        foreach ($list as $course) {
            $label = $course->fullname . ($course->fullname !== $course->shortname ? " (" . $course->shortname . ")" : "");
            //if ( strlen($label) > 60 )
            //{	$label = wordwrap( $label, 60, " <br>",false ); }
            $this->allcourses[$course->courseid] = $label;
        }
        natcasesort($this->allcourses);
        return $this->allcourses;
    }

    /**
     * For the frontpage evaluation returns the list of course_of_studies with at least one completed evaluation
     *
     * @return array id=>name pairs of courses
     */
    public function get_completed_course_of_studies() {
        global $DB;

        if ($this->get_evaluation()->course != SITEID) {
            return [];
        }

        if ($this->allstudies !== null) {
            return $this->allstudies;
        }

        $filter = $CoS_Filter = "";
        if ($this->get_teacherid()) {
            $filter .= " AND completed.teacherid=" . $this->get_teacherid();
        }
        if ($this->get_courseid()) {
            $filter .= " AND completed.courseid='" . $this->get_courseid() . "'";
        }

        // handle CoS privileged users
        if ($this->analysisCoS) {
            $CoS_Filter = evaluation_get_cosPrivileged_filter($this->get_evaluation());
        }
        if ($CoS_Filter) {
            $filter .= $CoS_Filter;
        }

        $filterD = $this->get_department_filter();
        if (!empty($filterD)) {
            $filter .= $filterD;
        }
        $sql = "SELECT DISTINCT ON (completed.course_of_studies) completed.course_of_studies, completed.id
				FROM {evaluation_completed} AS completed
				WHERE completed.evaluation = :evaluationid $filter ORDER BY completed.course_of_studies ASC";

        $list = $DB->get_records_sql($sql, ['evaluationid' => $this->get_evaluation()->id]);

        $this->allstudies = array();
        foreach ($list as $completed) {
            $label = $completed->course_of_studies;
            if (!empty($label)) {
                $this->allstudies[$completed->id] = $label;
            }
        }
        return $this->allstudies;
    }

    /**
     * Id of the current course (for site evaluations only)
     *
     * @return stdClass
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * For the frontpage evaluation returns the list of course_of_studies with at least one completed evaluation
     *
     * @return array id=>name pairs of courses
     */
    public function get_completed_teachers() {
        global $DB;

        if ($this->get_evaluation()->course != SITEID) {
            return [];
        }

        if ($this->allteachers !== null) {
            return $this->allteachers;
        }

        $is_open = evaluation_is_open($this->evaluation);
        $filter = $CoS_Filter = "";
        if ($this->get_courseid()) {
            $filter .= " AND completed.courseid=" . $this->get_courseid();
        } else if ($this->get_course_of_studies()) {
            $filter .= " AND completed.course_of_studies='" . $this->get_course_of_studies() . "'";
        }

        // handle CoS privileged users
        if ($this->analysisCoS) {
            $CoS_Filter = evaluation_get_cosPrivileged_filter($this->get_evaluation(), "completed");
        }
        if ($CoS_Filter) {
            $filter .= $CoS_Filter;
        }

        $filterD = $this->get_department_filter();
        if (!empty($filterD)) {
            $filter .= $filterD;
        }

        $sql = "SELECT DISTINCT ON (completed.teacherid) completed.teacherid, u.firstname, u.lastname 
				FROM {evaluation_completed} completed, {"
                .($is_open ?"user" :"evaluation_users")
                ."} u
				WHERE completed.evaluation = :evaluationid $filter  AND completed.teacherid=u.".($is_open ?"" :"user") ."id 
				ORDER BY completed.teacherid ASC";

        $list = $DB->get_records_sql($sql, ['evaluationid' => $this->get_evaluation()->id]);

        $this->allteachers = array();
        foreach ($list as $teacher) {
            $label = $teacher->firstname . " " . $teacher->lastname;
            $this->allteachers[$teacher->teacherid] = $label;
        }
        natcasesort($this->allteachers);
        return $this->allteachers;
    }

    public function get_completed_departments() {
        global $DB;

        if ($this->get_evaluation()->course != SITEID) {
            return [];
        }

        if ($this->alldepartments !== null) {
            return $this->alldepartments;
        }
        /*
        $filter = $CoS_Filter = "";
        if ($this->get_courseid()) {
            $filter .= " AND completed.courseid=" . $this->get_courseid();
        } else if ($this->get_course_of_studies()) {
            $filter .= " AND completed.course_of_studies='" . $this->get_course_of_studies() . "'";
        }
        if ($this->get_teacherid()) {
            $filter .= " AND completed.teacherid=" . $this->get_teacherid();
        }

        // handle CoS privileged users
        if ($this->analysisCoS) {
            $CoS_Filter = evaluation_get_cosPrivileged_filter($this->get_evaluation(), "completed");
        }
        if ($CoS_Filter) {
            $filter .= $CoS_Filter;
        }

        $sql = "SELECT DISTINCT completed.course_of_studies, completed.id
				FROM {evaluation_completed} completed
				WHERE completed.evaluation = :evaluationid $filter 
				ORDER bY completed.course_of_studies ASC";
        $list = $DB->get_records_sql($sql, ['evaluationid' => $this->get_evaluation()->id]);
        */
        $list = $_SESSION['CoS_department']; //$_SESSION['CoS_department'][$CoS]

        $this->alldepartments = array();
        foreach ($list as $department) {
            $this->alldepartments[$department] = $department;
        }
        asort($this->alldepartments);
        return $this->alldepartments;
    }

}

