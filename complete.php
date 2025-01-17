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
 * prints the form so the user can fill out the evaluation
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once("../../config.php");
require_once("lib.php");

evaluation_init_evaluation_session();

$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$teacherid = optional_param('teacherid', null, PARAM_INT);
$gopage = optional_param('gopage', 0, PARAM_INT);
$gopreviouspage = optional_param('gopreviouspage', null, PARAM_RAW);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
$evaluation = $DB->get_record("evaluation", array("id" => $cm->instance), '*', MUST_EXIST);

list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) =
        evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm, true);
evaluation_get_course_teachers($courseid);
if ($courseid) {
    if (!$teacherid and isset($_SESSION["allteachers"][$courseid]) and count($_SESSION["allteachers"][$courseid]) < 2) {
        foreach ($_SESSION["allteachers"][$courseid] as $teacher) {
            $teacherid = $teacher['id'];
            break;
        }
    }
    $course_of_studies = evaluation_get_course_of_studies($courseid);
}
$ev_name = ev_get_tr($evaluation->name);
$context = context_module::instance($cm->id);
$evaluationcompletion =
        new mod_evaluation_completion($evaluation, $cm, $courseid, false, null, null, 0, $teacherid, $course_of_studies);
$courseid = $evaluationcompletion->get_courseid();
$teacherid = $_SESSION['teacherid'] = $evaluationcompletion->get_teacherid();

$urlparams = array('id' => $cm->id, 'gopage' => $gopage, 'courseid' => $courseid, 'teacherid' => $teacherid);
$PAGE->set_url('/mod/evaluation/complete.php', $urlparams);

require_course_login($course, true, $cm);
$PAGE->set_activity_record($evaluation);

// Check whether the evaluation is mapped to the given courseid.
if (!has_capability('mod/evaluation:edititems', $context) and !$evaluationcompletion->check_course_is_mapped()) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('cannotaccess', 'mod_evaluation'));
    echo $OUTPUT->continue_button(course_get_url($courseid ?: $evaluation->course));
    echo $OUTPUT->footer();
    exit;
}

//check whether the given courseid exists
if ($courseid and $courseid != SITEID) {
    require_course_login(get_course($courseid)); // This overwrites the object $COURSE .
}

if (!$evaluationcompletion->can_complete() or !defined("EVALUATION_ALLOWED")) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($ev_name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification('Sie können an dieser Evaluation nicht teilnehmen!');
    $url = "/mod/evaluation/view.php?id=$id";
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

$PAGE->navbar->add(get_string('evaluation:complete', 'evaluation'));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($ev_name);
$PAGE->set_pagelayout('incourse');

// Check if the evaluation is open (timeopen, timeclose) and couresid supplied for global evaluation
$courseMissing =
        ($evaluation->course == SITEID and (!$courseid or $courseid == SITEID));  //($course->id == SITEID AND !$courseid) ;
if (!$evaluationcompletion->is_open() || $courseMissing) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(format_string($ev_name));
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    if ($courseMissing) {
        echo $OUTPUT->notification(get_string('no_course_selected', 'evaluation'));
    } else {
        echo $OUTPUT->notification(get_string('evaluation_is_not_open', 'evaluation'));
    }
    echo $OUTPUT->continue_button(course_get_url($courseid ?: $evaluation->course));
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}

// Mark activity viewed for completion-tracking.
if (isloggedin() && !isguestuser()) {
    $evaluationcompletion->set_module_viewed();
}

// Check if user is prevented from re-submission.
$cansubmit = $evaluationcompletion->can_submit();

// Initialise the form processing evaluation completion.
if (!$evaluationcompletion->is_empty() && $cansubmit) {
    // Process the page via the form.
    $urltogo = $evaluationcompletion->process_page($gopage, $gopreviouspage);

    if ($urltogo !== null) {
        redirect($urltogo);
    }
}

// Print the page header
$strevaluations = get_string("modulenameplural", "evaluation");
$strevaluation = get_string("modulename", "evaluation");

echo $OUTPUT->header();
// hide settings menu in Moodle 4
evHideSettings();

$icon = '<img src="pix/icon120.png" height="30" alt="' . $ev_name . '">';
echo $OUTPUT->heading($icon . "&nbsp;" . format_string($ev_name));

if ($courseid and $courseid !== SITEID) {
    $Studiengang = $course_of_studies = evaluation_get_course_of_studies($courseid, false);
} // get Studiengang with link
if (!empty($Studiengang)) {
    $Studiengang =
            get_string("course_of_studies", "evaluation") . ": <span style=\"font-size:12pt;font-weight:bold;display:inline;\">"
            . $Studiengang . "</span><br>\n";
}
echo $Studiengang . $CourseTitle;

if ($evaluationcompletion->is_empty()) {
    \core\notification::error(get_string('no_items_available_yet', 'evaluation'));
} else if ($cansubmit) {
    if ($evaluationcompletion->just_completed()) {
        // Display information after the submit.
        if ($evaluation->page_after_submit) {
            echo $OUTPUT->box($evaluationcompletion->page_after_submit(), 'generalbox boxaligncenter');
        }
        if (!$SiteEvaluation and $evaluationcompletion->can_view_analysis()) {
            echo '<p align="center">';
            $analysisurl = new moodle_url('/mod/evaluation/analysis.php', array('id' => $cm->id, 'courseid' => $courseid));
            echo html_writer::link($analysisurl, get_string('completed_evaluations', 'evaluation'));
            echo '</p>';
        }

        if ($evaluation->site_after_submit) {
            $url = evaluation_encode_target_url($evaluation->site_after_submit);
        } else {
            if ($courseid) {
                $url = "/mod/evaluation/view.php?id=$id";
            } else {
                $url = course_get_url($courseid ?: $course->id);
            }
        }
        unset($_SESSION["myEvaluations"]);
        echo $OUTPUT->continue_button($url);
    } else {
        if ($evaluation->teamteaching and $courseid) {
            if (!$teacherid and count($_SESSION["allteachers"][$courseid]) > 1) {
                $teacherid = $_SESSION['teacherid'] = get_teachers_selection($_SESSION["allteachers"][$courseid]);
                redirect(new moodle_url("/mod/evaluation/complete.php",
                        ['id' => $id, 'teacherid' => $teacherid, 'courseid' => $courseid]));
            }
            print "<br><h3><b>" .
                    get_string('evaluate_teacher', 'evaluation', $_SESSION["allteachers"][$courseid][$teacherid]['fullname']) .
                    "</b></h3>\n";
            //print nl2br(var_export($_SESSION["allteachers"][$courseid],true));
        } else {
            if ($courseid and $courseid !== SITEID) {
                if (defined("showTeachers")) //  AND ( defined( "isStudent") || defined('EVALUATION_OWNER') )
                {
                    print showTeachers;
                }
            }
        }
        // Display the form with the questions.
        echo $evaluationcompletion->render_items();
        echo get_string('save_entries_help', 'evaluation') . "<br>\n";
    }
} else {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
    echo $OUTPUT->notification(get_string('this_evaluation_is_already_submitted', 'evaluation'));
    if ($SiteEvaluation and $courseid) {
        $url = "/mod/evaluation/view.php?id=$id";
    } else {
        $url = course_get_url($courseid ?: $course->id);
    }
    echo $OUTPUT->continue_button($url);
    echo $OUTPUT->box_end();
}
unset($_SESSION["myEvaluations"]);
echo $OUTPUT->footer();

function get_teachers_selection($teachers) {
    global $evaluation, $id, $courseid, $DB, $USER;
    $options = "";
    $sql = "select id,evaluation,courseid,userid,teacherid from {evaluation_completed} 
			WHERE evaluation=" . $evaluation->id . " AND userid=" . $USER->id . " AND courseid=$courseid";
    $completed = $DB->get_records_sql($sql);
    foreach ($completed as $complete) {
        if (isset($teachers[$complete->teacherid])) {
            unset($teachers[$complete->teacherid]);
        }
    }
    //print "\n<hr>SQL: $sql<br>Completed: ".count($completed)." - complete->teacherid: $complete->teacherid<br>";var_dump($teachers); echo "<hr>\n";
    $size = count($teachers);
    foreach ($teachers as $teacher) {
        $options .= '<option style="font-weight:bold;font-size:16px;" value="' . $teacher['id'] . '">' . $teacher['fullname'] .
                "</option>\n";
        if ($size == 1) {
            return $teacher['id'];
        }
    }
    print "<br><br><h4><b>" . get_string("select_teacher", "evaluation") . "</b></h4><br>";
    ?>
    <style>input[text], select, select[option], submit, button {
            width: 40%;
            font-size: 16px;
        }</style>
    <form name="teachers" method="GET">
        <input name="id" type="hidden" value="<?php echo $id; ?>">
        <input name="courseid" type="hidden" value="<?php echo $courseid; ?>">
        <select name="teacherid" size="<?php echo $size; ?>">
            <?php echo $options; ?>
        </select><br>
        <button><?php echo "<b>" . get_string('evaluate_now', 'evaluation') . "</b>"; ?></button>
    </form>
    <?php
    exit;
}
