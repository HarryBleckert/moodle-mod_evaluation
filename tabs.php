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
 * prints the tabbed bar
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 * patched by Harry.Bleckert@ASH-Berlin.eu, added urlparams courseid to view.php
 */
defined('MOODLE_INTERNAL') or die('not allowed');

global $USER;

$tabs = array();
$row = array();
$inactive = array();
$activated = array();

//some pages deliver the cmid instead the id
if (isset($cmid) and intval($cmid) and $cmid > 0) {
    $usedid = $cmid;
} else {
    $usedid = $id;
}

$context = context_module::instance($usedid);
$courseid = optional_param('courseid', false, PARAM_INT);
// $current_tab = $SESSION->evaluation->current_tab;
if (!isset($current_tab)) {
    $current_tab = '';
}

$urlparams = $urlparamsID = $urlparamsIDT = ['id' => $usedid];
if (defined("SiteEvaluation")) {
    if ($courseid) {
        $urlparams['courseid'] = $courseid;
    }
} else {
    $courseid = $evaluation->course;
    $urlparams['courseid'] = $courseid;
}

if (isset($teacherid) and $teacherid) {
    $urlparams['teacherid'] = $teacherid;
} else if (isset($teacheridSaved)) {
    $urlparams['teacherid'] = $teacheridSaved;
}
if (isset($course_of_studiesID) and $course_of_studiesID) {
    $urlparams['course_of_studiesID'] = $course_of_studiesID;
}

if (isset($department) AND $department) {
    $urlparams['department'] = $department;
}

if (!isset($isPermitted)) {
    list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) =
            evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm);
}
// allow role change for privileged users
evaluation_LoginAs();

if (!isset($completed_responses)) {
    if ($courseid) {
        if (!isset($evaluationstructure)) {
            $evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $courseid, null, 0, $teacherid);
        }
        $completed_responses = $evaluationstructure->count_completed_responses();
    } else {
        $completed_responses = evaluation_countCourseEvaluations($evaluation);
    }
}


$privGlobalUser = (is_siteadmin() OR isset($_SESSION["privileged_global_users"][$USER->username])
                ?!empty($_SESSION["privileged_global_users"][$USER->username]) :false);


$viewurl = new moodle_url('/mod/evaluation/view.php', $urlparams);
$row[] = new tabobject('view', $viewurl->out(), get_string('overview', 'evaluation'));

$completed = $courseid and isEvaluationCompleted($evaluation, $courseid, $USER->id);
$is_open = evaluation_is_open($evaluation);
$can_view = false;
$isTeacher = false;
if (!$isTeacher and isset($_SESSION["myEvaluations"])) {
    $isTeacher = evaluation_is_teacher($evaluation, $_SESSION["myEvaluations"]) and
    !evaluation_is_student($evaluation, $_SESSION["myEvaluations"], $courseid);
}

if ($isTeacher) {
    $can_view = true;
} else if (!$is_open and $evaluation->timeclose < time() and $evaluation->publish_stats) {
    if (isset($isStudent) and $isStudent and !evaluation_has_user_participated($evaluation, $USER->id)) {
        $can_view = false;
    } else {
        $can_view = true;
    }
}

if (true) //defined( "SiteEvaluation") )
{
    if ($courseid) {
        $urlparamsIDT['courseid'] = $courseid;
    }
    if ($isTeacher) {
        $urlparamsIDT['teacherid'] = $USER->id;
    }
    if (defined('EVALUATION_OWNER')) {
        if ($teacherid) {
            $urlparamsIDT['teacherid'] = $teacherid;
        }
        if ($course_of_studiesID) {
            $urlparamsIDT['course_of_studiesID'] = $course_of_studiesID;
        }
        if (isset($department) AND $department) {
            $urlparamsIDT['department'] = $department;
        }
    }

}

if ($evaluation->course == SITEID) {
    if (defined('EVALUATION_OWNER') || $can_view
            || (((defined("isStudent") and $completed) || ($isPermitted and $courseid))
                    and (has_capability('mod/evaluation:viewreports', $context) || $evaluation->publish_stats))) {
        if ($completed_responses) {
            if ($evaluation->course == SITEID) {
                $analysisurl = new moodle_url('/mod/evaluation/analysis_course.php', $urlparams);
            } else {
                $analysisurl = new moodle_url('/mod/evaluation/analysis.php', $urlparams);
            }

            if ((defined("SiteEvaluation")) and ($isTeacher or !$is_open))  // AND !defined('EVALUATION_OWNER')
            {
                if ($courseid) {
                    $row[] = new tabobject('analysis', $analysisurl->out(), "Auswertung Kurs");
                }

                // show all own evaluations to teacher
                if ($isTeacher) //evaluation_is_teacher( $evaluation, $_SESSION["myEvaluations"] ) )
                {
                    $urlparamsIDT['teacherid'] = $USER->id;
                    unset($urlparamsIDT['courseid']);
                    $analysisurl = new moodle_url('/mod/evaluation/analysis_course.php', $urlparamsIDT);
                    $row[] = new tabobject('analysisTeacher', $analysisurl->out(), "Auswertung eigene Kurse");
                    if (!$is_open) {
                        $analysisurl = new moodle_url('/mod/evaluation/analysis_course.php', $urlparamsID);
                        $row[] = new tabobject('analysisASH', $analysisurl->out(), "Auswertung aller Kurse");
                    }
                } else {
                    if (defined('EVALUATION_OWNER')) {
                        $analysisurl = new moodle_url('/mod/evaluation/analysis_course.php', $urlparams);
                    } else {
                        $analysisurl = new moodle_url('/mod/evaluation/analysis_course.php', $urlparamsID);
                    }
                    $row[] = new tabobject('analysisASH', $analysisurl->out(), "Auswertung aller Kurse");
                }
            } else {
                $row[] = new tabobject('analysis', $analysisurl->out(), get_string('analysis', 'evaluation'));
            }
            //$txt = ($courseid||$teacherid) ?" mit Vergleich" :"";
            if (!$is_open or $isTeacher OR defined('EVALUATION_OWNER')) {
                $urlparamsIDT['showCompare'] = 1;
                $statsurl = new moodle_url('/mod/evaluation/print.php', $urlparamsIDT);
                $row[] = new tabobject('statistics', $statsurl->out(), "Statistik");
            }

            $cosPrivileged = evaluation_cosPrivileged($evaluation);
            //if ( defined('EVALUATION_OWNER') ?!$cosPrivileged :false )
            //if ( is_siteadmin() OR isset($_SESSION["privileged_global_users"][$USER->username]) )
            if (is_siteadmin() or isset($_SESSION["privileged_global_users"][$USER->username])) {
                $reporturl = new moodle_url('/mod/evaluation/show_entries.php', $urlparams);
                $row[] = new tabobject('showentries',
                        $reporturl->out(),
                        get_string('show_entries', 'evaluation'));
            }
        }
    }
} else //if ( $evaluation->course !== SITEID )
{
    if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO) {
        $nonrespondenturl = new moodle_url('/mod/evaluation/show_nonrespondents.php', $urlparams);
        $row[] = new tabobject('nonrespondents',
                $nonrespondenturl->out(),
                get_string('show_nonrespondents', 'evaluation'));
    }
    if ($completed_responses) {
        if (has_capability('mod/evaluation:viewreports', $context) || $evaluation->publish_stats) {
            $analysisurl = new moodle_url('/mod/evaluation/analysis.php', $urlparams);
            $row[] = new tabobject('analysis', $analysisurl->out(), get_string('analysis', 'evaluation'));
            if (has_capability('mod/evaluation:edititems', $context)) {
                $reporturl = new moodle_url('/mod/evaluation/show_entries.php', $urlparams);
                $row[] = new tabobject('showentries',
                        $reporturl->out(),
                        get_string('show_entries', 'evaluation'));
            }
            if (!$is_open) {
                $statsurl = new moodle_url('/mod/evaluation/print.php', $urlparamsIDT);
                $row[] = new tabobject('statistics', $statsurl->out(), "Statistik");
            }
        }
    }
}

//if ( is_siteadmin() OR has_capability('moodle/course:update', $context) )
if (has_capability('mod/evaluation:edititems', $context) OR
        ($evaluation->course != SITEID AND $isPermitted)) {
    $editurl = new moodle_url('/mod/evaluation/edit.php', $urlparams + ['do_show' => 'edit']);
    $row[] = new tabobject('edit', $editurl->out(), get_string('edit_items', 'evaluation'));

    $templateurl = new moodle_url('/mod/evaluation/edit.php', $urlparams + ['do_show' => 'templates']);
    $row[] = new tabobject('templates', $templateurl->out(), get_string('templates', 'evaluation'));
}

if ($evaluation->course != SITEID AND
        ($isPermitted OR has_capability('mod/evaluation:mapcourse', $context))) {
    $mapurl = new moodle_url('/mod/evaluation/mapcourse.php', $urlparams);
    $row[] = new tabobject('mapcourse', $mapurl->out(), get_string('mappedcourses', 'evaluation'));
}
//}

if (safeCount($row) > 0) {
    $tabs[] = $row;
    echo "\n" . '<div style="inline;" class="d-print-none">';
    print_tabs($tabs, $current_tab, $inactive, $activated);
    echo "</div>\n";
}

