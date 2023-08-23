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
 * print the single entries
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 * patched by Harry.Bleckert@ASH-Berlin.eu to allow course teachers view results
 */

require_once("../../config.php");
require_once("lib.php");

global $USER;

////////////////////////////////////////////////////////
//get the params
////////////////////////////////////////////////////////
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', false, PARAM_INT);
$showcompleted = optional_param('showcompleted', false, PARAM_INT);
$deleteid = optional_param('delete', null, PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);
$course_of_studiesID = optional_param('course_of_studiesID', false, PARAM_INT);
$teacherid = optional_param('teacherid', false, PARAM_INT);
$downloading = optional_param('adownload', false, PARAM_TEXT);  // 
//$urlparams = ['id' => $userid];

$goBack = '<div style="display:block;text-align:center;">' .
        html_writer::tag('button', "Zurück", array('style' => 'color:white;background-color:black;text-align:right;',
                'type' => 'button', 'onclick' => '(window.history.back()?window.history.back():window.close());')) . "</div>\n";

////////////////////////////////////////////////////////
//get the objects
////////////////////////////////////////////////////////

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
//evaluation_set_module_viewed($cm);
$context = context_module::instance($cm->id);
require_login($course, true, $cm);
$evaluation = $PAGE->activityrecord;

$url = new moodle_url('/mod/evaluation/show_entries.php', array('id' => $cm->id));

// handle CoS privileged user
$cosPrivileged = evaluation_cosPrivileged($evaluation);
if ($cosPrivileged) {
    if ($teacherid and !ev_is_user_in_CoS($evaluation, $teacherid)) {
        $teacherid = false;
    }
    if ($courseid and !ev_is_course_in_CoS($evaluation, $courseid)) {
        $courseid = false;
    }
    if ($course_of_studiesID) {
        $course_of_studies = evaluation_get_course_of_studies_from_evc($course_of_studiesID, $evaluation);
        if (!in_array($course_of_studies, $_SESSION['CoS_privileged'][$USER->username])) {
            $course_of_studiesID = false;
        }
    }
}

$course_of_studies = false;
if ($course_of_studiesID) {
    $course_of_studies = evaluation_get_course_of_studies_from_evc($course_of_studiesID, $evaluation);
    $url->param('course_of_studiesID', $course_of_studiesID);
    $urlparams['course_of_studiesID'] = $course_of_studiesID;
}

if ($courseid and $evaluation->course == SITEID) {
    $url->param('courseid', $courseid);
    $urlparams['courseid'] = $courseid;
}
if ($teacherid) {
    $url->param('teacherid', $teacherid);
    $urlparams['teacherid'] = $teacherid;
}

// set PAGE layout and print the page header
$evurl = new moodle_url('/mod/evaluation/show_entries.php', array('id' => $cm->id));
evSetPage($url, $evurl, get_string("show_entries", "evaluation"));
$PAGE->set_url(new moodle_url($url, array('userid' => $userid, 'showcompleted' => $showcompleted, 'delete' => $deleteid)));

list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) =
        evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm);
if (!isset($_SESSION['myEvaluations'])) {
    $_SESSION["myEvaluations"] = get_evaluation_participants($evaluation, $USER->id);
    $_SESSION["myEvaluationsName"] = $evaluation->name;
}

defined('EVALUATION_OWNER') || require_capability('mod/evaluation:viewreports', $context);

if (!$downloading) {    //echo $OUTPUT->header();
    // handle CoS priveleged user
    if (!empty($_SESSION['CoS_privileged'][$USER->username])) {
        print "Auswertungen der Studiengänge: " . '<span style="font-weight:600;white-space:pre-line;">'
                . implode(", ", $_SESSION['CoS_privileged'][$USER->username]) . "</span><br>\n";
    }

    $icon = '<img src="pix/icon120.png" height="30" alt="' . $evaluation->name . '">';
    echo $OUTPUT->heading($icon . "&nbsp;" . format_string($evaluation->name));
    // fix for bootstrap 4 media declaration conflicting with chrome + Edge printing
    echo '<style type="text/css"> @page { size: auto; } </style>';

    $current_tab = 'showentries';
    require('tabs.php');
}

if ($SiteEvaluation and !$courseid and !defined('EVALUATION_OWNER')) {
    $CourseTitle = "\n<span style=\"font-size:12pt;font-weight:bold;display:inline;\">" . get_string("all_courses", "evaluation") .
            "</span>";
}
$Studiengang = "";
if (!$downloading and $courseid and $courseid !== SITEID) {
    $Studiengang = evaluation_get_course_of_studies($courseid, true);  // get Studiengang with link
    $semester = evaluation_get_course_of_studies($courseid, true, true);  // get Semester with link
    if (!empty($Studiengang)) {
        $Studiengang =
                get_string("course_of_studies", "evaluation") . ": <span style=\"font-size:12pt;font-weight:bold;display:inline;\">"
                . $Studiengang .
                (empty($semester) ? "" : " <span style=\"font-size:10pt;font-weight:normal;\">(" . $semester . ")</span>") .
                "</span><br>\n";
    }
    echo $Studiengang . $CourseTitle;
    if ($courseid and defined("showTeachers")) {
        echo showTeachers;
    }
}

// check access rights
if ($SiteEvaluation and (!defined('EVALUATION_OWNER') ? true : $cosPrivileged)) {
    print "<p style=\"color:red;font-weight:bold;align:center;\">" . get_string('you_have_no_permission', 'evaluation') . "</p>";
    echo $OUTPUT->continue_button("/mod/evaluation/view.php?id=$id");
    if (!is_siteadmin()) {
        print $OUTPUT->footer();
        exit;
    }
}

if (!$downloading) // show loading spinner
{
    evaluation_showLoading();
}

if ($deleteid) {
    // This is a request to delete a reponse.
    require_capability('mod/evaluation:deletesubmissions', $context);
    require_sesskey();
    $evaluationstructure = new mod_evaluation_completion($evaluation, $cm, 0, true, $deleteid);
    evaluation_delete_completed($evaluationstructure->get_completed(), $evaluation, $cm);
    redirect($url);
} else if ($showcompleted || $userid) {
    // Viewing individual response.
    $evaluationstructure = new mod_evaluation_completion($evaluation, $cm, 0, true, $showcompleted, $userid, 0,
            $teacherid, $course_of_studies, $course_of_studiesID);
} else {
    // Viewing list of reponses.
    $evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $courseid, null, 0, $teacherid, $course_of_studies,
            $course_of_studiesID);
}

$responsestable = new mod_evaluation_responses_table($evaluationstructure);
$anonresponsestable = new mod_evaluation_responses_anon_table($evaluationstructure);

// download MUST start before any output is buffered
if ($responsestable->is_downloading()) {
    $responsestable->download();
}
if ($anonresponsestable->is_downloading()) {
    $anonresponsestable->download();
}

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

if ($userid || $showcompleted) {
    // Print the response of the given user.
    $completedrecord = $evaluationstructure->get_completed();

    if ($userid) {
        $usr = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
        $responsetitle = userdate($completedrecord->timemodified) . ' (' . fullname($usr) . ')';
    } else {
        $responsetitle = get_string('response_nr', 'evaluation') . ': ' .
                $completedrecord->random_response . ' (' . get_string('anonymous', 'evaluation') . ')';
    }

    // show return button
    echo $goBack;
    //echo "<br>\n";

    echo $OUTPUT->heading($responsetitle, 4);

    if (!empty($completedrecord->courseid)) {
        $eCourse = get_course($completedrecord->courseid);
        echo get_string('course', 'evaluation') . ': <span style="font-size:12pt;font-weight:bold;display:inline;color:darkgreen;">'
                . $eCourse->fullname . ' (' . $eCourse->shortname . ")</span><br>\n";
        if (!empty($completedrecord->course_of_studies)) {
            echo get_string('course_of_studies', 'evaluation') .
                    ': <span style="font-size:12pt;font-weight:bold;display:inline;color:darkgreen;">'
                    . $completedrecord->course_of_studies . "</span><br>\n";
        }
    }
    if (!empty($completedrecord->teacherid)) {
        $user = core_user::get_user($completedrecord->teacherid);
        echo "Evaluation für " . get_string('teacher', 'evaluation') .
                ': <span style="font-size:12pt;font-weight:bold;display:inline;color:darkgreen;">' . $user->firstname . ' ' .
                $user->lastname . "</span><br>\n";
    }

    $form = new mod_evaluation_complete_form(mod_evaluation_complete_form::MODE_VIEW_RESPONSE,
            $evaluationstructure, 'evaluation_viewresponse_form');
    $form->display();

    list($prevresponseurl, $returnurl, $nextresponseurl) = $userid ?
            $responsestable->get_reponse_navigation_links($completedrecord) :
            $anonresponsestable->get_reponse_navigation_links($completedrecord);

    echo html_writer::start_div('response_navigation');

    $responsenavigation = [
            'col1content' => '',
            'col2content' => $goBack,
            'col3content' => '',
    ];

    if ($prevresponseurl) {
        $responsenavigation['col1content'] = html_writer::link($prevresponseurl, get_string('prev'), ['class' => 'prev_response']);
    }

    if ($nextresponseurl) {
        $responsenavigation['col3content'] = html_writer::link($nextresponseurl, get_string('next'), ['class' => 'next_response']);
    }

    echo $OUTPUT->render_from_template('core/columns-1to1to1', $responsenavigation);
    echo html_writer::end_div();

} else {

    // Output to printer only / Print Only (Hide on screen only)
    if (!$downloading and !$courseid) {
        print '<div class="d-none d-print-block">';
        print  get_string("course_of_studies", "evaluation") . ': <span style="font-size:12pt;font-weight:bold;display:inline;">';
        if ($course_of_studies) {
            print $course_of_studies;
        } else {
            print get_string('fulllistofstudies', 'evaluation');
        }
        print "</span><br>\n";
        print  get_string("teacher", "evaluation") . ': <span style="font-size:12pt;font-weight:bold;display:inline;">';
        if ($teacherid) {
            print evaluation_get_user_field($teacherid, 'fullname');
        } else {
            print get_string('fulllistofteachers', 'evaluation');
        }
        print '</div>';
    }

    if (!$downloading and defined('EVALUATION_OWNER')) {
        // process filters by forms
        //echo "\n".'<div style="display:none;" id="evFilters" class="d-print-none d-inline">';
        echo "\n" . '<div style="display:none;" id="evFilters" class="d-print-none">';
        if (!$courseid and $SiteEvaluation) {    // Process course of studies select form.
            $studyselectform =
                    new mod_evaluation_course_of_studies_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
            if ($data = $studyselectform->get_data()) {
                evaluation_spinnerJS(false);
                redirect(new moodle_url($url, ['course_of_studiesID' => $data->course_of_studiesID]));
            }
            echo "\n" . '<div style="display:inline;float:left;" class="d-print-none">'; // do not print
            $studyselectform->display();
            echo "</div>\n";
        }
        if ($SiteEvaluation) {    // Process course select form.
            $courseselectform = new mod_evaluation_course_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
            if ($data = $courseselectform->get_data()) {
                evaluation_spinnerJS(false);
                redirect(new moodle_url($url, ['courseid' => $data->courseid]));
            }
            echo "\n" . '<div style="display:inline;float:left;" class="d-print-none">'; // do not print
            $courseselectform->display();
            echo "</div>\n";
        }
        if (!$courseid or safeCount($_SESSION["allteachers"][$courseid]) > 1)  // ( $evaluation->teamteaching ) )
        {    // Process teachers select form.
            $teacherselectform = new mod_evaluation_teachers_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
            if ($data = $teacherselectform->get_data()) {
                evaluation_spinnerJS(false);
                redirect(new moodle_url($url, ['teacherid' => $data->teacherid]));
            }
            echo "\n" . '<div style="display:inline;float:left;" class="d-print-none">'; // do not print
            $teacherselectform->display();
            echo "</div>\n";
        }

        echo '</div><div style="display:block;clear:both;">&nbsp;</div>' . "\n";
    }

    // Print the list of responses.
    $totalrowsRT = $responsestable->get_total_responses_count();
    $totalrowsART = $anonresponsestable->get_total_responses_count();

    $minResults = evaluation_min_results($evaluation);
    $minResultsText = min_results_text($evaluation);
    $minResultsPriv = min_results_priv($evaluation);
    if (defined('EVALUATION_OWNER')) {
        $minResults = $minResultsText = $minResultsPriv;
    }

    /*$completed_responses = $evaluationstructure->count_completed_responses();
    $minresults = evaluation_min_results($evaluation);
    if ( $cosPrivileged )
    {	if ( $completed_responses < $minresults )
        {	echo '<br><b style="color:red;">Für diese Auswertung wurden weniger als '. $minresults
                   . " Abgaben gemacht. Daher können Sie keine Antworten einsehen!</b><br>\n";
            echo $OUTPUT->continue_button("/mod/evaluation/view.php?id=$id");
            if ( !is_siteadmin() )
            {	print $OUTPUT->footer(); exit; }
        }
        elseif ( $completed_responses >= $minresults AND $completed_responses < $minresults+3 )
        {	echo '<br><b style="color:red;">Für diese Auswertung wurden weniger als '.($minresults+3)
                   . " Abgaben gemacht. Daher können Sie keine Textantworten einsehen!</b><br>\n";
        }
    }*/

    echo "\n" . '<div  class="d-print-block" id="evCharts" style="display:none;">';   // no display before onload

    // do not show results if less than minimum required evaluations
    // before 2022/7/28: ( !defined('EVALUATION_OWNER') ?true :!$cosPrivileged ) AND
    if (defined("SiteEvaluation") and
            ((!$totalrowsRT and !$totalrowsART) or
                    ($totalrowsRT < $minResults and $totalrowsART < evaluation_min_results($evaluation)))) {
        echo "<p style=\"color:red;font-weight:bold;align:center;\">" . get_string('min_results', 'evaluation', $minResults) .
                "</p>";
        evaluation_spinnerJS(false);
        echo $OUTPUT->footer();
        exit;
        if (!is_siteadmin()) {
            echo $OUTPUT->footer();
            exit;
        }
    }

    // Get the items of the evaluation.
    $items = $evaluationstructure->get_items(true);
    $itemsCounted = 0;
    foreach ($items as $item) {    // export only rateable items
        if (!in_array($item->typ, array("numeric", "multichoice", "multichoicerated"))) {
            continue;
        }
        $itemsCounted++;
    }
    $itemsText = safeCount($items) - $itemsCounted;

    // Show the summary.
    echo "<b>" . get_string('completed_evaluations', "evaluation") . "</b>: " . $completed_responses . "<br>\n";
    echo "<b>" . get_string("questions", "evaluation") . "</b>: " . safeCount($items) .
            " ($itemsCounted numerisch ausgewertete Fragen)<br>\n";

    // Show non-anonymous responses (always retrieve them even if current evaluation is anonymous).
    if (!$evaluationstructure->is_anonymous() and $totalrowsRT) {
        // echo $OUTPUT->heading(get_string('non_anonymous_entries', 'evaluation', $totalrowsRT), 4);
        $responsestable->display();
    }

    // Show anonymous responses (always retrieve them even if current evaluation is not anonymous).
    $evaluationstructure->shuffle_anonym_responses(); // needs to be called to renumber replies
    if ($evaluationstructure->is_anonymous() and $totalrowsART) {
        // echo $OUTPUT->heading(get_string('anonymous_entries', 'evaluation', $totalrowsART), 4);
        $anonresponsestable->display();
    }

}

// js code for loading spinner
evaluation_spinnerJS();
evaluation_trigger_module_entries($evaluation, $cm, $courseid);

// Finish the page.
echo $OUTPUT->footer();
