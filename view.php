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
 * the first page to view the evaluation
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_valuation
 * patched by Harry.Bleckert@ASH-Berlin.eu to allow course teachers view results
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/evaluation/lib.php');
global $DB, $USER;

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);
$course_of_studiesID = optional_param('course_of_studiesID', false, PARAM_INT);
$course_of_studies = optional_param('course_of_studies', false, PARAM_TEXT);
$teacherid = optional_param('teacherid', false, PARAM_INT);
$department = optional_param('department', false, PARAM_TEXT);
$urlparams = ['id' => $id];  //$usedid];

$teacheridSaved = $teacherid;
if ($teacherid) {
    $urlparams['teacherid'] = $teacherid;
    $teacherid = 0;
}
// $course_of_studies = false;
if ($course_of_studiesID) {
    $urlparams['course_of_studiesID'] = $course_of_studiesID;
}
if ($department) {
    $urlparams['department'] = $department;
}

// show loading spinner
//evaluation_showLoading();  

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
require_course_login($course, true, $cm);
$evaluation = $PAGE->activityrecord;
$context = context_module::instance($cm->id);

if ($evaluation->course == SITEID and $courseid) {
    $urlparams['courseid'] = $courseid;
} else if ($evaluation->course != SITEID and !$courseid) {
    $courseid = $evaluation->course;
    $urlparams['courseid'] = $courseid;
}


evaluation_get_all_teachers($evaluation);
list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) =
        evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm, true);
list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation);

// Check access to the given courseid.
if ($courseid and $course->id !== SITEID and !defined('EVALUATION_OWNER')) {
    require_course_login(get_course($courseid));
}  // This overwrites the object $COURSE
//if ( $course->id == SITEID && ( !$isPermitted || defined('EVALUATION_OWNER') ) ) {
//$PAGE->set_pagelayout('incourse');  // disabled 2022-01-12
//}

// set PAGE layout and print the page header
$url = new moodle_url('/mod/evaluation/view.php', array('id' => $id));
evSetPage($url);

$minResults = evaluation_min_results($evaluation);
$minResultsText = min_results_text($evaluation);
$minResultsPriv = min_results_priv($evaluation);

$privGlobalUser = (is_siteadmin() OR isset($_SESSION["privileged_global_users"][$USER->username]));
if ($privGlobalUser) {
    $minResults = $minResultsText = $minResultsPriv;
}

$evaluation_semester = get_evaluation_semester($evaluation);
if (empty($evaluation_semester)){
    $evaluation_semester = "Alle Semester";
}
else{
    $syear = substr($evaluation_semester, 0, 4);
    $semester = ev_get_string('summer_semester') . " " . $syear;
    if (substr($evaluation_semester, -1,1) == "2"){
        $syear2 = intval(substr($evaluation_semester, 2, 2)) + 1;
        $semester = ev_get_string('winter_semester') . " " . $syear . "/" . $syear2;
    }
    $evaluation_semester = $semester;
}


$ev_name = ev_get_tr($evaluation->name);

//$previewimg = '<i style="color:blue;" class="fa fa-search-plus fa-fw fa-2x" title="'.get_string('preview').'">';
$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = new moodle_url('/mod/evaluation/print.php', array("id" => $id, "courseid" => $courseid));
if ($courseid) {
    $previewlnk->param('courseid', $courseid);
}
$preview = $previewQ = html_writer::link($previewlnk, $previewimg);
$icon = '<img src="pix/icon120.png" height="30" alt="' . $ev_name . '">';
$msg = "";
//$context = context_module::instance($id);
// if ( is_siteadmin() OR has_capability('moodle/course:update', $context) )
if (has_capability('mod/evaluation:edititems', $context)) {
    $jssn = "document.getElementsByClassName('secondary-navigation')[0].style.display='inline';";
    // $msg = '&nbsp;<a href="/course/modedit.php?update='.$id.'&return=1"><i class="fa fa-cog fa-solid" style="color:blue;" aria-hidden="true"></i></a>';
    $msg = '&nbsp;<span onclick="' . $jssn . '"><i class="fa fa-cog fa-solid" style="color:blue;" aria-hidden="true"></i></span>';
}
echo $OUTPUT->heading($icon . "&nbsp;" . format_string($ev_name) . "&nbsp;" . $preview . $msg);

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('course_of_studies_list', 'evaluation'));
$previewlnk->param('showCourses_of_studies', 1);
$view_courses_of_studies = html_writer::link($previewlnk, $previewimg);
$previewimg = $OUTPUT->pix_icon('t/preview', get_string('courses_list', 'evaluation'));
$previewlnk->param('showCourses', 1);
$view_courses = html_writer::link($previewlnk, $previewimg);

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('daily_progress', 'evaluation'));
$previewlnk->param('daily_progress', 1);
$view_daily_progress = html_writer::link($previewlnk, $previewimg);
$previewimg = $OUTPUT->pix_icon('t/preview', get_string('usageReport', "evaluation"));
$usagelink = new moodle_url('/mod/evaluation/print.php', array("id" => $id, "logViews" => 1));
$view_usageReport = html_writer::link($usagelink, $previewimg);
$resultslnk = new moodle_url('/mod/evaluation/print.php', array("id" => $id, "showResults" => 3, "goBack" => "view"));
$view_course_results = html_writer::link($resultslnk, $previewimg);

$is_open = evaluation_is_open($evaluation);
$is_closed = evaluation_is_closed($evaluation);
$evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $courseid, false, null, null, 0, $teacherid);
$cosPrivileged = evaluation_cosPrivileged($evaluation);

// Check whether the evaluation is mapped to the given courseid.
if (!has_capability('mod/evaluation:edititems', $context) && !$evaluationcompletion->check_course_is_mapped()) {
    echo $OUTPUT->notification(get_string('cannotaccess', 'mod_evaluation'));
    echo $OUTPUT->footer();
    exit;
}

// Trigger module viewed event.
//$evaluationcompletion->trigger_module_viewed(); // error with 4.2
evaluation_trigger_module_viewed($evaluation, $cm, $courseid);

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

if (!isset($_SESSION["myEvaluations"])) {
    $_SESSION["myEvaluations"] = get_evaluation_participants($evaluation, $USER->id);
}

$evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $courseid, null, 0, $teacherid,
        null,null , null,1);

if ($courseid) {
    $completed_responses = $evaluationstructure->count_completed_responses();
} else {
    $completed_responses = evaluation_countCourseEvaluations($evaluation);
}
$evaluation_has_user_participated = evaluation_has_user_participated($evaluation, $USER->id);
$course_has_user_participated = evaluation_has_user_participated($evaluation, $USER->id, $courseid);
$completed_all = hasUserEvaluationCompleted($evaluation, $USER->id);
$isEvaluationCompleted = isEvaluationCompleted($evaluation, $courseid, $USER->id);
$teamteaching = $evaluation->teamteaching;
$teamteachingTxt = ($teamteaching ? ev_get_string('teamteachingtxt')
        . "<br>\n" : "");
$isTeacher = defined('isTeacher');
$isStudent = defined('isStudent');

$a = new stdClass(); // for translations

if (!empty($_SESSION["myEvaluations"])) {
    if (!$isTeacher) {
        $isTeacher = evaluation_is_teacher($evaluation, $_SESSION["myEvaluations"]);
    }
    if (!$isStudent) {
        $isStudent = evaluation_is_student($evaluation, $_SESSION["myEvaluations"]);
    }
}

$isNonResponStudent = false;
if ($is_open) {
    $isNonResponStudent = defined('isStudent') and !$evaluation_has_user_participated and $evaluationcompletion->is_open()
    and $evaluationcompletion->can_complete();
    if ($isNonResponStudent and !$evaluationcompletion->can_submit()) {
        $isNonResponStudent = false;
    }
}

if (!$isNonResponStudent) {    // Print the tabs.
    $current_tab = 'view';
    require('tabs.php');
} else {
    print "<br>\n";
}
$showPrivDocu = '<a href="print.php?id='.$id.'&showPrivUsers=1">'
. "<b>" . ev_get_string('privileged_users_overview') . "</b></a> - "
. '<a href="/downloads/Evaluationen mit ASH Moodle -Dokumentation.pdf" target="doku">'
. "<b>" . ev_get_string('docu_download') . "</b></a><br>\n";
$good_day = ev_get_string('good_day');

$q_translink = '';
$lang = (!empty($_GET["lang"]) ?$_GET["lang"] :current_language());
if (strtolower(substr($lang,0,2)) != 'de' AND $evaluation->id == 24) {
    // Hier ist eine englische Übersetzung des Fragebogens.
    $q_translink = '<a title="' . ev_get_string('questionaireenglish') . '" target="translation"
                        href="https://moodle.ash-berlin.eu/downloads/Evaluation%20of%20Courses%20WiSe%202024-25%20EN.pdf">'
            .ev_get_string('clickquestionaireenglish'). "</a><br>\n"; // <b>Click here</b> to open an English translation of the questionnaire
}


if (defined('EVALUATION_OWNER') and $evaluation->course == SITEID) {
    $a->siteadmintxt = $a->andrawdata = $a->yourcos = $a->viewanddownload = $a->is_WM_disabled = $a->privilegestxt = "";
    IF (evaluation_is_WM_disabled($evaluation)){
        $a->is_WM_disabled = ev_get_string('is_WM_disabled'); // 'Ausgenommen sind Weiterbildende Master Studiengänge.';
    }
    if (is_siteadmin()) {
        $a->siteadmintxt = ev_get_string('siteadmintxt'); // 'Administrator und daher'
    }
    $a->andrawdata  = ev_get_string('andrawdata'); // und Rohdaten
    $a->yourcos = ev_get_string('yourcos'); // Ihrer Studiengänge
    $a->minresultspriv = (is_siteadmin() ?1 :$minResultsPriv);
    $a->viewanddownload = ev_get_string('viewanddownload',$a); // einsehen und herunterladen.
    $a->privilegestxt = ev_get_string('privilegestxt',$a); // Als {$a->siteadmintxt für diese Evaluation privilegierte Person können Sie alle Auswertungen {$a->andrawdata}
    $msg_privPersons = $good_day . " " . trim($USER->firstname) . " " . trim($USER->lastname) . "<br>\n"
            . $a->privilegestxt
            . (!empty($_SESSION['CoS_privileged'][$USER->username])
                    ? ' <span style="font-weight:600;white-space:pre-line;text-decoration:underline;" title="'
                    . implode("\n", $_SESSION['CoS_privileged'][$USER->username])
                    . "\">" .$a->yourcos. "</span>\n"
                    : ""
                )
            . " " . $a->viewanddownload . " " . $a->is_WM_disabled
            . "<br>\n" . $showPrivDocu . $q_translink;
    echo $msg_privPersons;
}

$all_courses = false;
$Studiengang = $showTeachers = "";

if ($SiteEvaluation and !$courseid) {
    $all_courses = true;
    $CourseTitle = "\n<span style=\"font-size:12pt;font-weight:bold;display:inline;\">"
            . get_string('all_courses', "evaluation") .
            "</span><br>\n";
}
if ($courseid) {
    if ($evaluation->course == SITEID) {
        $Studiengang = evaluation_get_course_of_studies($courseid, true);  // get Studiengang with link
        $semester = evaluation_get_course_of_studies($courseid, true, true);  // get Semester with link
        if (!empty($Studiengang)) {
            $Studiengang = get_string("course_of_studies", "evaluation") .
                    ": <span style=\"font-size:12pt;font-weight:bold;display:inline;\">"
                    . $Studiengang .
                    (empty($semester) ? "" : " <span style=\"font-size:10pt;font-weight:normal;\">(" . $semester . ")</span>") .
                    "</span><br>\n";
        }
        echo $Studiengang . $CourseTitle;
    } else {
        echo $CourseTitle;
    }
    echo ' (<a href="?id='
            . $id
            . '&course_of_studiesID=' . $course_of_studiesID
            . '&teacherid' .$teacheridSaved . '">' . ev_get_string('remove_filter') . '</a>)';
    if (defined("showTeachers")) {
        echo showTeachers;
        if ($teacheridSaved){
            echo ' (<a href="?id='
                    . $id . '&courseid=' . $courseid
                    . '&course_of_studiesID=' . $course_of_studiesID
                    . '">' . ev_get_string('remove_filter') . '</a>)';
        }
    }

    if (!$is_open) {
        $numTeachers = safeCount($_SESSION["allteachers"][$courseid]);
        $divisor = (!$teacherid) ? $numTeachers : 1;
        $students = get_evaluation_participants($evaluation, false, $courseid, false, true);
        $participated = $completed = 0;

        foreach ($students as $participant) {
            if (evaluation_has_user_participated($evaluation, $participant["id"], $courseid)) {
                $participated++;
            }
            if (true) //$evaluation->teamteaching )
            {
                if (isEvaluationCompleted($evaluation, $courseid, $participant["id"])) {
                    $completed++;
                }
            }
        }
        $numStudents = safeCount($students );
        // $numStudents = evaluation_count_students($evaluation, $courseid);
        print '<br><span style="font-size:12pt;font-weight:normal;">';
        if ($numStudents) {
            $numToDo = $numStudents * $divisor;
            $evaluated = round(($participated / $numStudents) * 100, 1) . "%";
            $a->numteachers = $numTeachers;
            $a->numstudents = $numStudents;
            $a->evaluated = $evaluated;
            // Dieser Kurs hat {$a->numteachers} Dozent_in und {$a->numstudents} studentische Teilnehmer_innen.
            print ev_get_string('courseparticipants',$a) . "<br>\n";
            // Teilnehmer_innen haben sich an dieser Evaluation beteiligt. Das entspricht einer Beteiligung von {$a->evaluated}.
            print $participated . " " . ev_get_string('participantsandquota',$a);
            if ($evaluation->teamteaching) {
                if ($numTeachers > 1) {
                    $completed = round(($completed / $numStudents) * 100, 1) . "%";
                    print $completed . " " . ev_get_string('quotaevaluatedall'); // der Teilnehmer_innen haben alle Dozent_innen bewertet.
                    if (!empty($teacheridSaved)) {
                        $completed = round(($completed_responses / $numStudents) * 100, 1) . "%";
                        print "<br>" . $completed . ev_get_string('quotaevaluatedteacher'); // der Teilnehmer_innen haben diese Dozent_in bewertet.
                    }
                    $a->quote = round(($completed_responses / $numToDo) * 100, 1) . "%";
                    $a->completed_responses = evaluation_number_format($completed_responses);
                    $a->numtodo = $numToDo;
                    // Es wurden {$a->completed_responses} von maximal {$a->numToDo} Abgaben gemacht. Die Abgabequote beträgt {$a->quote}.
                    print ev_get_string('coursequota');
                }
            }
        } else {
            print ev_get_string('nostudentsincourse');
        }
        echo "</span><br>\n";
    } else {
        print "<br>\n";
    }

}

$fullname = ($USER->alternatename ?: $USER->firstname) . " " . $USER->lastname;

$a->also = $a->foryourcourses = "";
$a->minresults = $minResults;
$a->minresultstxt = $minResultsText;
if ($evaluation_has_user_participated) {
    $a->also = ev_get_string('also');
    $a->foryourcourses = ev_get_string('foryourcourses');
}

$msg_student_all_courses = $good_day . " " . $fullname . "<br>\n"
        . ev_get_string('msg_student_all_courses',$a)
        . "<br>" . $teamteachingTxt
        . ev_get_string('yourevaluationhelps') . "<br>\n"
        . ev_get_string('resultconditions',$a). "<br>\n"
        . $q_translink . "\n";
if ($is_open){
    $yourpartcourses = ev_get_string('yourpartcourses');
}
else{
    $yourpartcourses = ev_get_string('yourpastpartcourses'); // Sie haben Kurse, die an dieser Evaluation teilgenommen haben
}
$msg_teachers = $good_day . " " . $fullname . "<br>" . $yourpartcourses . "<br>\n"
        . ev_get_string('teachersviewconditions',$a)
        . "<br>\n" . $q_translink ."\n";
if ($is_open) {
    $days = remaining_evaluation_days($evaluation);
    $alert = "";
    if ($days < 7 and $days >= 0) {
        $a->daysleft = $days;
        $alert = '<b style="color:red;">' . ev_get_string('evaluationalert',$a) . "</b>\n<br>\n";
    }

    if ($isStudent and $is_open and !$completed_all) {
        echo $msg_student_all_courses . $alert;
    } else if (!$isStudent and $isTeacher and $SiteEvaluation) {
        echo $msg_teachers . $alert;
    }
}
else if($isStudent||$isTeacher){
    print $good_day . " " . $fullname . "<br>\n";

}


if (defined('EVALUATION_OWNER') and !$cosPrivileged) {
    $minResults = $minResultsText = $minResultsPriv;
}

//show loading spinner - not working at this stage as page
//if ( true OR $SiteEvaluation )
//{	evaluation_showLoading();  }
//echo "\n".'<div style="display:none;" id="evView">';

if (!isset($_SESSION["questions"])) {
    $_SESSION["questions"] = safeCount($evaluationstructure->get_items(true));
}

//show some infos to the evaluation
//echo $OUTPUT->heading(get_string('overview', 'evaluation'), 3);
if (defined('EVALUATION_OWNER') or $isPermitted or has_capability('mod/evaluation:edititems', $context)) {
    //get the groupid
    $groupselect = groups_print_activity_menu($cm, $CFG->wwwroot . '/mod/evaluation/view.php?id=' . $cm->id, true);
    $mygroupid = groups_get_activity_group($cm);
    echo $groupselect . '<div class="clearer">&nbsp;</div>';

    if (!$courseid and defined('EVALUATION_OWNER')) {
        $timeopen = ($evaluation->timeopen > 0) ? $evaluation->timeopen : (time() - 80600);

        if (!isset($_SESSION["teamteaching_courses"]) or !isset($_SESSION["num_courses_of_studies"])) {
            $_SESSION["num_courses_of_studies"] = safeCount(evaluation_get_course_studies($evaluation,false,false));
            $_SESSION["duplicated"] = evaluation_count_duplicated_replies($evaluation);
            $_SESSION["teamteaching_courses"] = evaluation_count_teamteaching_courses($evaluation);
            $_SESSION["distinct_users"] =
                    $DB->get_record_sql("select count( distinct userid) from mdl_evaluation_completed where evaluation=$evaluation->id")->count;
            $_SESSION["evaluated_teachers"] =
                    $DB->get_record_sql("select count( distinct teacherid) from mdl_evaluation_completed where evaluation=$evaluation->id")->count;
            $_SESSION["evaluated_courses"] =
                    $DB->get_record_sql("select count( distinct courseid) from mdl_evaluation_completed where evaluation=$evaluation->id")->count;
            //print "\n<script>document.getElementById('loader').style.display='none';</script>\n";
            //print "<br><hr>called as open EV (set)<br>";
        }

        $get_from_table = false;
        if (!$is_open and $evaluation->timeopen < time() and $is_closed ){ // AND $completed_responses) {
            if (evaluation_set_results($evaluation)) {
                $evaluation = $DB->get_record_sql("SELECT * from {evaluation} WHERE id=$evaluation->id");
            }
            if (!empty($evaluation->participating_students)) {
                $get_from_table = true;
                $courses_of_studies = $evaluation->courses_of_studies;
                $duplicated_replies = $evaluation->duplicated_replies;
                $teamteaching_courses = $evaluation->teamteaching_courses;
                $participating_students = $evaluation->participating_students;
                $participating_active_students = $evaluation->participating_active_students;
                $participating_teachers = $evaluation->participating_teachers;
                $participating_active_teachers = $evaluation->participating_active_teachers;
                //$_SESSION["distinct_t_active"];
                $participating_courses = $evaluation->participating_courses;
                $participating_active_courses = $evaluation->participating_active_courses;
                $possible_evaluations = $evaluation->possible_evaluations;
                $possible_active_evaluations = $evaluation->possible_active_evaluations;
            }
        }
        if (!$get_from_table) {
            if (!isset($_SESSION['distinct_t']) or empty($_SESSION['distinct_s'])) {
                list($_SESSION["participating_courses"], $_SESSION["participating_empty_courses"],
                        $_SESSION["distinct_s"], $_SESSION["distinct_s_active"], $_SESSION["students"],
                        $_SESSION["students_active"],
                        $_SESSION["distinct_t"], $_SESSION["distinct_t_active"], $_SESSION["Teachers"],
                        $_SESSION["Teachers_active"]
                        )
                        = get_evaluation_participants($evaluation);
            }
            //print "<br><hr>called as open EV<br>";
            $courses_of_studies = $_SESSION["num_courses_of_studies"];
            $duplicated_replies = $_SESSION["duplicated"];
            $teamteaching_courses = $_SESSION["teamteaching_courses"];
            $participating_students = $_SESSION["distinct_s"];
            $participating_active_students = $_SESSION["distinct_s_active"];
            $participating_teachers = $_SESSION["distinct_t"];
            $participating_active_teachers = $_SESSION["distinct_t_active"];
            $participating_courses = $_SESSION["participating_courses"];
            $participating_active_courses = $_SESSION["participating_courses"] - $_SESSION["participating_empty_courses"];
            $possible_evaluations = $_SESSION["students"]; // possible_evaluations( $evaluation );
            $possible_active_evaluations = $_SESSION["students_active"];
            if ($teamteaching and $participating_courses) {
                $possible_evaluations = possible_evaluations($evaluation);
                // WHEN Open: MUST divide by all  teachers and all courses because teachers of empty courses and empty courses may get evaluated!
                $possible_active_evaluations = possible_active_evaluations($evaluation);
                //$possible_active_evaluations = 	round($_SESSION["students_active"] 	* ($_SESSION["Teachers_active"]/$participating_active_courses), 0);
            }
        }

        // $teamteaching_courses = $_SESSION["teamteaching_courses"];
        if (!isset($_SESSION["participating_courses_of_studies"])) {
            $_SESSION["participating_courses_of_studies"] = $courses_of_studies;
            if (!empty($sg_filter)) {
                $_SESSION["participating_courses_of_studies"] = safeCount($sg_filter);
            } else if (!empty($courses_filter)) {
                $_SESSION["participating_courses_of_studies"] =
                        safeCount(evaluation_get_course_of_studies_from_courseids($courses_filter));
            }
        }

        $days = total_evaluation_days($evaluation);
        $remaining = remaining_evaluation_days($evaluation);
        if ($remaining > 0) {
            $days = $days - $remaining;
        }

        $view_daily_link = html_writer::link($previewlnk, "<b>" . get_string('completed_evaluations', "evaluation") . "</b>: ");

        $cosStudies = safeCount($evaluationstructure->get_completed_course_of_studies());
        if ($cosPrivileged and $_SESSION["participating_courses_of_studies"] > 1
                and $cosStudies < $_SESSION["participating_courses_of_studies"]){
            if (!isset($_SESSION['cos_distinct_t']) or empty($_SESSION['cos_distinct_s'])) {
                list($_SESSION["cos_participating_courses"], $_SESSION["cos_participating_empty_courses"],
                        $_SESSION["cos_distinct_s"], $_SESSION["cos_distinct_s_active"], $_SESSION["cos_students"],
                        $_SESSION["cos_students_active"],
                        $_SESSION["cos_distinct_t"], $_SESSION["cos_distinct_t_active"], $_SESSION["cos_Teachers"],
                        $_SESSION["cos_Teachers_active"]
                        )
                        = get_evaluation_participants($evaluation,false,false,false,false, true);
            }

            $cos_completed_responses = $evaluationstructure->count_completed_responses();
            $CoS_Filter = evaluation_get_cosPrivileged_filter($evaluation);
            $_SESSION["cos_distinct_users"] =
                    $DB->get_record_sql("select count( distinct userid) from mdl_evaluation_completed where evaluation=$evaluation->id $CoS_Filter")->count;
            $cos_get_completed_teachers = safeCount($evaluationstructure->get_completed_teachers());
            $cos_get_completed_courses = safeCount($evaluationstructure->get_completed_courses());
            $cos_possible_evaluations = $_SESSION["cos_students"];
            $cos_possible_active_evaluations = $_SESSION["cos_students_active"];
            if ($teamteaching and !empty($_SESSION["cos_participating_courses"])) {
                $cos_possible_evaluations = possible_evaluations($evaluation,false,false,true);
                // WHEN Open: MUST divide by all  teachers and all courses because teachers of empty courses and empty courses may get evaluated!
                $cos_possible_active_evaluations = possible_active_evaluations($evaluation,true);
                //$possible_active_evaluations = 	round($_SESSION["students_active"] 	* ($_SESSION["Teachers_active"]/$participating_active_courses), 0);
            }
            echo '<div style="text-align:center;font-weight:bold;">' . ev_get_string('your') . ' '
                    . ev_get_string('courses_of_studies'). "</div>\n";

            echo "<b>" . get_string('completed_evaluations', "evaluation") . "</b>: "
                    . evaluation_number_format($cos_completed_responses)
                    . "/" . evaluation_number_format($cos_possible_evaluations)
                    . evaluation_calc_perc($cos_completed_responses, $cos_possible_evaluations);
            echo ' <b title="' . ev_get_string('show_active_only') . '">'
                    . ev_get_string('active_only') . "</b>: " . evaluation_number_format($cos_possible_active_evaluations)
                    . "<b>" . evaluation_calc_perc($cos_completed_responses, $cos_possible_active_evaluations) . "</b> ";
            echo '<span style="font-size:20px;"> &#248;</span>: '
                    . ($days ? round($cos_completed_responses / $days) : 0) . "/" . get_string("day");
            if ($is_open) {
                echo " - " . get_string("today") . ": " . $evaluationstructure->count_completed_responses($groupid = 0, $today = true);
            }
            echo "<br>\n";


            echo "<b>" . get_string('participants', "evaluation") . "</b>: " .
                    evaluation_number_format($_SESSION["cos_distinct_users"]) . "/" .
                    evaluation_number_format($_SESSION["cos_distinct_s"]) .
                    evaluation_calc_perc($_SESSION["cos_distinct_users"], $_SESSION["cos_distinct_s"])
                    . " <b " . 'title="' . ev_get_string('show_active_only') .'">'
                    . ev_get_string('active_only') . "</b>: " . evaluation_number_format($_SESSION["cos_distinct_s_active"])
                    . "<b>" . evaluation_calc_perc($_SESSION["cos_distinct_users"], $_SESSION["cos_distinct_s_active"]) . "</b>"
                    . "<br>\n";


            echo "<b>". get_string('evaluated_teachers', "evaluation") . "</b>: "
                    . evaluation_number_format(safeCount($cos_get_completed_teachers))
                    . "/" . $_SESSION["cos_distinct_t"]
                    . evaluation_calc_perc($cos_get_completed_teachers, $_SESSION["cos_distinct_t"]);
            echo " <b " . 'title="' . ev_get_string('show_active_only') .'">'
                    . ev_get_string('active_only') . "</b>: " . evaluation_number_format($_SESSION["cos_distinct_t_active"])
                    . "<b>" . evaluation_calc_perc($_SESSION["cos_distinct_t"], $_SESSION["cos_distinct_t_active"])
                    . "</b>"
                    . "<br>\n";

            $cos_participating_active_courses = $_SESSION["cos_participating_courses"] - $_SESSION["cos_participating_empty_courses"];
            echo "<b>" . get_string('evaluated_courses', "evaluation") . "</b>: "
                    . evaluation_number_format($cos_get_completed_courses)
                    . "/" . evaluation_number_format($_SESSION["cos_participating_courses"])
                    . evaluation_calc_perc($cos_get_completed_courses, $_SESSION["cos_participating_courses"])
                    . " <b " . 'title="' . ev_get_string('show_active_only') .'">'
                    . ev_get_string('courses_with_content_only') . "</b>: " . evaluation_number_format($cos_participating_active_courses)
                    . "<b>" . evaluation_calc_perc($cos_get_completed_courses, $cos_participating_active_courses)
                    . "</b>"
                    . "<br>\n";

            echo "<b>Evaluierte Studiengänge</b>: "
                    . evaluation_number_format($cosStudies) . "<br>\n";

            echo '<div style="text-align:center;font-weight:bold;">'
            .ev_get_string('this_evaluation') . '</div>';
        }

        // show overview to privileged persons
        echo $view_daily_link . evaluation_number_format($completed_responses) . "/" .
                evaluation_number_format($possible_evaluations)
                . evaluation_calc_perc($completed_responses, $possible_evaluations);
        echo ' <b title="' . ev_get_string('show_active_only') . '">'
                . ev_get_string('active_only') . "</b>: " . evaluation_number_format($possible_active_evaluations)
                . "<b>" . evaluation_calc_perc($completed_responses, $possible_active_evaluations) . "</b> ";
        echo '<span style="font-size:20px;"> &#248;</span>: '
                . ($days ? round($completed_responses / $days) : 0) . "/" . get_string("day");
        if ($is_open) {
            echo " - " . get_string("today") . ": " . $evaluationstructure->count_completed_responses($groupid = 0, $today = true);
        } else if (!$teamteaching and $duplicated_replies) {
            echo " - (" . evaluation_number_format($completed_responses + $duplicated_replies) . " inkl. duplizierter Abgaben)";
        }
        echo " " . $view_daily_progress . "<br>\n";

        if ($evaluation->course == SITEID) {
            echo "<b>" . get_string('participants', "evaluation") . "</b>: " .
                    evaluation_number_format($_SESSION["distinct_users"]) . "/" .
                    evaluation_number_format($participating_students) .
                    evaluation_calc_perc($_SESSION["distinct_users"], $participating_students)
                    . " <b " . 'title="' . ev_get_string('show_active_only') .'">'
                    . ev_get_string('active_only') . "</b>: " . evaluation_number_format($participating_active_students)
                    . "<b>" . evaluation_calc_perc($_SESSION["distinct_users"], $participating_active_students) . "</b>"
                    . "<br>\n";

            if (!$is_open or $teamteaching) {
                $teacherslnk = new moodle_url('/mod/evaluation/print.php',
                        array("id" => $id, "showTeacherResults" => 3, "goBack" => "view"));
                $view_teacher_results = html_writer::link($teacherslnk, $previewimg);
                $evaluated_teachers =
                        html_writer::link($teacherslnk, "<b>" . get_string('evaluated_teachers', "evaluation") . "</b>: ");
                echo $evaluated_teachers;
                echo evaluation_number_format($_SESSION["evaluated_teachers"]) . "/" . $participating_teachers
                        . evaluation_calc_perc($_SESSION["evaluated_teachers"], $participating_teachers);
                if (true) //$is_open )
                {
                    echo " <b " . 'title="' . ev_get_string('show_active_only') .'">'
                            . ev_get_string('active_only') . "</b>: " . evaluation_number_format($participating_active_teachers)
                            . "<b>" . evaluation_calc_perc($_SESSION["evaluated_teachers"], $participating_active_teachers) .
                            "</b>";
                }
                echo " " . $view_teacher_results . "<br>\n";
            } else {
                echo "<b>" . get_string('teachers_in_courses', "evaluation") . "</b>: " .
                        evaluation_number_format($participating_teachers)
                        . "<br>\n";
            }

            $evaluated_courses = html_writer::link($resultslnk, "<b>" . get_string('evaluated_courses', "evaluation") . "</b>: ");
            echo $evaluated_courses;
            echo evaluation_number_format($_SESSION["evaluated_courses"]) . "/" . evaluation_number_format($participating_courses)
                    . evaluation_calc_perc($_SESSION["evaluated_courses"], $participating_courses)
                    . " <b " . 'title="' . ev_get_string('show_active_only') .'">'
                    . ev_get_string('courses_with_content_only') . "</b>: " . evaluation_number_format($participating_active_courses)
                    . "<b>" . evaluation_calc_perc($_SESSION["evaluated_courses"], $participating_active_courses)
                    . "</b>";
            echo " " . $view_course_results . "<br>\n";

            if (true) //!$is_open )
            {
                echo "<b title=\"" . ev_get_string('onefeedbackperteacher') ."\">" . get_string("teamteaching", "evaluation") .
                        "</b>: "
                        . get_string(($teamteaching ? "yes" : "no"))
                        . ". "; //"<br>\n";
                echo ev_get_string('teamteachingcourses') . ": " . evaluation_number_format($teamteaching_courses)
                        . evaluation_calc_perc($teamteaching_courses, $participating_courses)
                        . ((!$teamteaching and $duplicated_replies) ?
                                " - " . ev_get_string('duplicatedfeedbacks') . ": " . evaluation_number_format($duplicated_replies) : "")
                        . "<br>\n";
            }

            $previewlnk->remove_params("daily_progress");
            $participating_courses_of_studies =
                    html_writer::link($previewlnk, "<b>" . get_string('participating_courses_of_studies', "evaluation") . "</b>: ");
            echo $participating_courses_of_studies;
            if ($_SESSION["participating_courses_of_studies"] < 1) {
                echo get_string("all") . " (" . $courses_of_studies . "/" . $courses_of_studies . ")";
            } else {
                echo $_SESSION["participating_courses_of_studies"] . "/" . $courses_of_studies
                        . evaluation_calc_perc($_SESSION["participating_courses_of_studies"], $courses_of_studies);
            }
            echo $view_courses_of_studies . "<br>\n";

            $a->loglifetime = get_config('logstore_standard')->loglifetime;
            echo html_writer::link($usagelink, "<b>" . get_string('usageReport', "evaluation") . "</b> ",
                            array('title' => ev_get_string('logananalysis',$a)))
                    . " " . $view_usageReport . "<br>\n";
        }
    } else {
        echo "<b>" . ev_get_string('completed_evaluations') . "</b>: "
                . evaluation_number_format($completed_responses) .
                "<br>\n";
        if (!$courseid) {
            echo "<b title=\"" . ev_get_string('onefeedbackperteacher',$a) ."\">"
                    . get_string("teamteaching", "evaluation") . "</b>: "
                    . get_string(($teamteaching ? "yes" : "no")) . "<br>\n";
            //echo "<b>".get_string("questions","evaluation")."</b>: " .$_SESSION["questions"]. " " .$previewQ ."<br>\n";
        }
    }
} else {
    echo "<br>\n";
}

if ($evaluation->timeopen and $evaluation->timeclose) {
    $total = total_evaluation_days($evaluation);
    $dayC = current_evaluation_day($evaluation);
    echo "<b>" . get_string('evaluation_period', 'evaluation') . "</b>: " . date("d.m.Y", $evaluation->timeopen) . " - " .
            date("d.m.Y", $evaluation->timeclose) .
            " (" . $total . " " . get_string("days") . ")";
    if ($is_open) {
        $a->currentday =  str_replace(".", ",", $dayC);
        $a->currentday_percent =  evaluation_calc_perc($dayC - 0.1, $total);
        echo ". ";
        echo ev_get_string('currentday',$a) . "</b>";
    }
    echo "<br>\n";
}
echo "<b>Semester:</b> " . $evaluation_semester . " - <b>" . get_string('mode', 'evaluation') . "</b>: "
        . ($evaluation->anonymous ? ev_get_string('anonymous') : ev_get_string('not_anonymous'))
        . " - " . "<b>" . get_string("questions", "evaluation") . "</b>: " . $_SESSION["questions"] . " " . $previewQ . "<br>\n";

if (!$courseid AND ($privGlobalUser OR !$is_open)) {
    print ev_get_reminders($evaluation,$id);
}

if ($evaluationcompletion->can_complete()) {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    if (!$evaluationcompletion->is_open() and !($isPermitted or defined('EVALUATION_OWNER'))) {
        // Evaluation is not yet open or is already closed.
        $a->timeopen = date("d.m.Y", $evaluation->timeopen);
        $a->timeclose = date("d.m.Y", $evaluation->timeclose);
        $a->is_or_was =($is_open ?ev_get_string("is") :ev_get_string("was"));
        echo "<b>" . ev_get_string('evaluationperiod',$a) . "</b><br>\n";
        if ($is_open) {
            echo $OUTPUT->continue_button(course_get_url($courseid ?: $course->id));
        }
    } else if ($evaluationcompletion->can_submit()) {
        // teachers are not allowed to submit Evaluation, sudents can only submit from inside courses
        if (!defined('isStudent') and ($isPermitted or ($SiteEvaluation and (!$courseid or !defined('isStudent'))))) {
            if ($isPermitted) {    /*if ( $evaluationcompletion->is_open() )
				{	echo "<p style=\"color:red;font-weight:bold;align:center;\">".get_string('students_only', 'evaluation')."</p>"; }
				*/
            }
        } else {
            if (!$SiteEvaluation or ($courseid)) {    // Display a link to complete evaluation or resume.
                $completeurl = new moodle_url('/mod/evaluation/complete.php',
                        ['id' => $id, 'courseid' => $courseid]);
                //['id' => $id, 'courseid' => $courseid, 'teacherid' => $teacheridSaved]);
                if ($startpage = $evaluationcompletion->get_resume_page()) {
                    $completeurl->param('gopage', $startpage);
                    $label = get_string('continue_the_form', 'evaluation');
                } else {
                    $label = get_string("evaluate_now", "evaluation");
                }
                echo html_writer::div(html_writer::link($completeurl, $label, array('class' => 'btn btn-secondary')),
                        'complete-evaluation');
            }
        }
    } else {
        // Evaluation was already submitted.
        if ($SiteEvaluation) {
            if ($isStudent) {
                if ($completed_all){
                    echo "$good_day $fullname<br>" . '<b style="color:darkgreen;">' . ev_get_string('thxforcompletingall') . "</b><br>";
                } else if ($courseid and evaluation_has_user_participated($evaluation, $USER->id, $courseid)) {
                    echo "$good_day $fullname<br>" . '<b style="color:darkgreen;">' . ev_get_string('thxforcompletingcourse') . "</b><br>";
                }
            }
        } else {
            echo $OUTPUT->notification(get_string('this_evaluation_is_already_submitted', 'evaluation'));
            echo $OUTPUT->continue_button(course_get_url($courseid ?: $course->id));
        }
    }
    echo $OUTPUT->box_end();
} else if ($evaluationcompletion->is_open()) {
    echo "<p style=\"color:red;font-weight:bold;align:center;\">" . get_string('cannot_participate', 'evaluation') . "</p>";
}

// if student who did not participate yet
if ($isNonResponStudent) {
    if ($evaluationcompletion->is_open()) {
        echo "<b style=\"color:blue;\">" . ev_get_string('view_after_participating') . "</b>\n";
    } else {
        echo "<b style=\"color:red;\">" . ev_get_string('no_participation_no_view') . "</b>\n";
    }
}

// show current user all participating courses
// print print_r($_SESSION["myEvaluations"]);
$isEnrolled = !empty(evaluation_is_user_enrolled($evaluation, $USER->id));
if ((!$isPermitted AND !defined('EVALUATION_OWNER')) and empty($_SESSION["myEvaluations"])) {
    if (!$is_open and $isEnrolled) {
        echo "<p style=\"color:red;font-weight:bold;align:center;\">";
        if ($SiteEvaluation){
            echo ev_get_string('no_part_no_results_site');
        }
        else{
            echo ev_get_string('no_part_no_results');
        }
        echo "</p>\n";
    } else if ($SiteEvaluation and !$isEnrolled) {
        echo "<p style=\"color:red;font-weight:bold;align:center;\">";
        if ($evaluation->timeclose < time()){
            echo ev_get_string('no_course_participated');
        } else {
            echo ev_get_string('no_course_participing');
        }
        echo "</p>\n";
    }
} 
elseif ($SiteEvaluation) {
    if (($isTeacher and !$isStudent) or ($teacheridSaved > 0 and defined('EVALUATION_OWNER'))) {
        $showTeacher = $USER->id;
        $tEvaluations = $_SESSION["myEvaluations"];
        if ($teacheridSaved and $teacheridSaved != $showTeacher) {
            $showTeacher = $teacheridSaved;
            $tEvaluations = get_evaluation_participants($evaluation, $showTeacher);
        }

        if (!empty($tEvaluations)) {
            print show_user_evaluation_courses($evaluation, $tEvaluations, $id, true, true, true);
            $teacherEvaluations = evaluation_countCourseEvaluations($evaluation, $tEvaluations, "teacher", $showTeacher);
            $courseEvaluations = evaluation_countCourseEvaluations($evaluation, $tEvaluations, false, false);
            if ($teacherEvaluations < $courseEvaluations and safeCount($tEvaluations)) {
                print "<h3>" . ev_get_string('results_all_evaluated_teachers') . "</h3>\n";
                print show_user_evaluation_courses($evaluation, $tEvaluations, $id, true, false, false);
            }
        }
    } else if (!empty($_SESSION["myEvaluations"])) {
        $showMycourses = show_user_evaluation_courses($evaluation, $_SESSION["myEvaluations"], $id, true, false, true);
        print $showMycourses;
    }
}

if ($isPermitted)
{    // Show intro and page_after_submit.
    echo $OUTPUT->heading(get_string('welcome_text', 'evaluation') ." "
    . ev_get_string('for_participants'), 3);
    if ( !empty($msg_student_all_courses)){
        print $msg_student_all_courses;
    }
    else{
        print "$good_day $fullname<br>\n";
        print $evaluation->intro;
    }
    echo $OUTPUT->heading(get_string('page_after_submit', 'evaluation'), 3);
    print $evaluation->page_after_submit;
    if (!empty($msg_teachers) AND defined('EVALUATION_OWNER')){
        echo $OUTPUT->heading(get_string('welcome_text', 'evaluation') ." "
                . ev_get_string('for_teachers'), 3);
        print $msg_teachers;
    }

}

    // print "<hr>CoS_privileged: "
    //    . nl2br(var_export($_SESSION['CoS_privileged'][$USER->username],true)) ."<hr>\n";
if (is_siteadmin()) {
    print "<br>Evaluation->id: $evaluation->id - Lang: $USER->lang";

    $pluginfo = ev_get_plugin_version();
    $info = "\n<hr>\nPlugin: ".$pluginfo->component.". Version: "
            .$pluginfo->release ." (Build: ".$pluginfo->version.")"
            . "<hr>\n";
    print $info;

    if (isset($_GET['activityrecord'])) {
        print nl2br(var_export($evaluation));
    }

    if ($SiteEvaluation and (!isset($_SESSION["make_block_evaluation_visible"]) or
                    $_SESSION["make_block_evaluation_visible"] !== $evaluation->id)) {
        $_SESSION["make_block_evaluation_visible"] = $evaluation->id;
        print make_block_evaluation_visible($evaluation);
    }
    if (isset($_GET['shuffle'])) {
        ev_shuffle_completed_userids($evaluation, true);
    }

    if (isset($_GET['renumber'])) {
        evaluation_renumber_items($evaluation->id);
    }

    if (isset($_GET['cron'])) {
        ev_cron(false);
    }
    
    //if( !safeCount( $DB->get_records_sql("SELECT * FROM {evaluation_item}
    //									WHERE name ILIKE '%studiengang%' AND evaluation=$evaluation->id and typ='multichoice'")) )
    //{	evaluation_autofill_item_studiengang( $evaluation ); }
    //print show_user_evaluation_courses( $evaluation, $_SESSION["myEvaluations"], $id, true, true, true );
    //unset( $_SESSION['allteachers'] );
    //evaluation_get_all_teachers( $evaluation, false, true);
    if (isset($_GET['set_results'])) {
        unset($_SESSION['set_results_' . $evaluation->id]);
        evaluation_set_results($evaluation, true, true, true);
        ///evaluation_set_results( $evaluation);
    }

    if (isset($_GET['empty_courses'])) {
       /*print "<hr>Date empty_courses: ".$_GET['empty_courses']."("
               .strtotime($_GET['empty_courses']).")"
                ." - " . strtotime("2017-12-31"). "<hr>";*/
       evaluation_get_empty_courses($_GET['empty_courses']);
    }

    // $assign = $DB->get_record("assign", array("id" => 10171));
    // print "<hr>assign: <br>";print var_dump($assign). "<hr>\n\n";
    // print "Submission:<br>" . var_export($submission,true);

    if ( false AND isset($_GET['send_reminders'])) {
       $test = true;
       $noreplies = false;
       if (isset($_GET['test'])){
           $test = $_GET['test'];
       }
       if (isset($_GET['noreplies'])){
           $noreplies = $_GET['noreplies'];
       }
       ev_send_reminders($evaluation, "teacher", $noreplies, $test);
       ev_send_reminders($evaluation, "student", $noreplies, $test);
       unset($_SESSION["EvaluationsID"]);
       validate_evaluation_sessions($evaluation);
    }

    if (isset($_GET['getPrivEmails'])) {
        print "\n<hr>Privilegierte Personen:<br>" .ev_set_privileged_users(true, true) ."<hr>\n";
    }

    if (isset($_GET['course_of_studies'])) {
        $keys = array();
        $dept = $departmentF = "";
        if ( $dept AND isset($_SESSION['CoS_department']) AND safeCount($_SESSION['CoS_department']) ) {
            $keys = array_keys($_SESSION['CoS_department']);
            $dept = array_searchi($course_of_studies, $keys);
            if (isset($_SESSION['CoS_department'][$keys[$dept]]) ) {
                $departmentF = $_SESSION['CoS_department'][$keys[$dept]];
                //return $department;
            }
        }

        $department = get_department_from_cos($course_of_studies);
        print "<hr>course_of_studies: $course_of_studies - key: $dept - F: $departmentF - department: $department
                - ".$_SESSION['CoS_department'][$keys[$dept]]."<hr>";
        print nl2br(var_export($_SESSION['CoS_department'], true));
        print nl2br(var_export(array_keys($_SESSION['CoS_department']), true));
    }

    /*$sg_filter = $courses_filter = array();
    list( $sg_filter, $courses_filter ) = get_evaluation_filters( $evaluation );
    echo "<hr>evaluation:\n" . nl2br(var_export($evaluation,true)) . "<hr>sg_filter:\n" . nl2br(var_export($sg_filter,true))
        . "<hr>filter_courses:\n" . var_export($courses_filter,true);
    if ( !empty($courses_filter) AND in_array( 16654, $courses_filter ))
    { echo "<hr><br>filter_courses:16654  in\n" . var_export($courses_filter,true);
    }
    */
    if (false) // $DB->count_records_sql("SELECT COUNT(*) from {evaluation_enrolments} WHERE evaluation=$evaluation->id AND shortname IS NULL") )
    {    //echo "<br><hr>evaluation_set_results( evaluation, false, true ) ...<hr>";
        echo "<br><hr>evaluation_get_course_studies(\$evaluation): " . safeCount(evaluation_get_course_studies($evaluation));
        echo "<br>possible_evaluations(\$evaluation): " . safeCount(possible_evaluations($evaluation));
        echo "<br>possible_active_evaluations(\$evaluation): " . safeCount(possible_active_evaluations($evaluation));
        $enrolments = $DB->get_records_sql("SELECT * from {evaluation_enrolments} WHERE evaluation=" . $evaluation->id);
        $possible_evaluations = $possible_active_evaluations = 0;
        foreach ($enrolments as $enrolment) {
            if ($enrolment->students and !empty($enrolment->teacherids)) {
                $possible_evaluations += ($enrolment->students * count(explode(",", $enrolment->teacherids)));
            }
            if ($enrolment->active_students and !empty($enrolment->active_teachers)) {
                $possible_active_evaluations += $enrolment->active_students * $enrolment->active_teachers;
            }
        }
        echo "<br>possible_evaluations(enrolments): " . $possible_evaluations;
        echo "<br>possible_active_evaluations(enrolments): " . $possible_active_evaluations;

        $evaluation_participating_courses = safeCount(evaluation_participating_courses($evaluation));
        echo "<br>\$evaluation_participating_courses: $evaluation_participating_courses - \$evaluation_has_user_participated: $evaluation_has_user_participated";
        echo "<br>isEvaluationCompleted( $evaluation->id, $courseid, $USER->id ): " .
                var_export(isEvaluationCompleted($evaluation, $courseid, $USER->id), true);
        //echo "\n\$PAGE->activityrecord: " . var_export($PAGE->activityrecord,true)."<br>\n";
        //echo "<br>\$_SESSION['filter_course_of_studies']: " . (!isset($_SESSION['filter_course_of_studies']) ?"Not set" :var_export($_SESSION['filter_course_of_studies'],true));
        //echo "<br>\$sg_filter: " . var_export($sg_filter,true);
        //echo "<br>implode-explode filter_course_of_studies: " . implode("<br>\n",explode( "\n", $evaluation->filter_course_of_studies));

        //@ob_flush();@ob_end_flush();@flush();@ob_start();
        //evaluation_set_results( $evaluation, false, true );
        //print nl2br(var_export($_SESSION, true));
    }
    /*echo "<hr>_SESSION[loggedInAs]: ".$_SESSION["loggedInAs"]."ID: $USER->id - Name: $USER->firstname $USER->lastname - lastaccess: $USER->lastaccess
            - _SESSION[EVALUATION_OWNER]: ".$_SESSION["EVALUATION_OWNER"]
            . " - _SESSION['REALUSER']: ".$_SESSION['REALUSER']->id." - GLOBALS['USER']->realuser: ".$GLOBALS['USER']->realuser."<hr>";
    */
    //echo (stristr($semester,'semester')?"Yes":"No"  );
    //echo "<hr>\$_SESSION['privileged_global_users']: ".var_export($_SESSION['privileged_global_users'],true) . "<hr>";
    //unset($_SESSION['CoS_privileged']);
    // echo "<hr>Owner: " . (defined('EVALUATION_OWNER') AND !$cosPrivileged ?"Ja":"Nein")."<hr>";
    // echo nl2br(var_export(isset($_SESSION["privileged_global_users"][$USER->username]),true));
}



echo $OUTPUT->footer();
require_once("print.js.php");
evaluation_trigger_module_viewed($evaluation, $cm, $courseid);
