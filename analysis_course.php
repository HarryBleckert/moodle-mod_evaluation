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
 * shows an analysed view of a evaluation on the mainsite
 *
 * @author mod_evaluation: Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 * branch created by by Harry.Bleckert@ASH-Berlin.eu to allow course teachers view results
 */

require_once("../../config.php");
require_once("lib.php");
global $DB, $USER;

$id = required_param('id', PARAM_INT);  //the POST dominated the GET
$courseitemfilter = optional_param('courseitemfilter', '0', PARAM_INT);
$courseitemfiltertyp = optional_param('courseitemfiltertyp', '0', PARAM_ALPHANUM);
$courseid = optional_param('courseid', false, PARAM_INT);
$course_of_studiesID = optional_param('course_of_studiesID', false, PARAM_INT);
$teacherid = optional_param('teacherid', false, PARAM_INT);
$department = optional_param('department', false, PARAM_INT);
$TextOnly = optional_param('TextOnly', false, PARAM_INT);
$graphicsonly= optional_param('graphicsonly', false, PARAM_INT);
$Chart = optional_param('Chart', false, PARAM_ALPHANUM);
$SetShowGraf = optional_param('SetShowGraf', 'verbergen', PARAM_ALPHANUM);
$analysisCoS = optional_param('analysisCoS', false, PARAM_INT);
if (!isset($_SESSION["Chart"])) {
    $_SESSION["Chart"] = "bar";
}
if (empty($Chart)) {
    $Chart = $_SESSION["Chart"];
} else {
    $_SESSION["Chart"] = $Chart;
}

$urlparams = ['id' => $id];
$url = new moodle_url('/mod/evaluation/analysis_course.php', array('id' => $id)); // ,'courseid' => $courseid ) );
navigation_node::override_active_url($url);

$url->param('Chart', $Chart);
$urlparams = ['Chart' => $Chart];
if ($courseitemfilter !== '0') {
    $url->param('courseitemfilter', $courseitemfilter);
}
if ($courseitemfiltertyp !== '0') {
    $url->param('courseitemfiltertyp', $courseitemfiltertyp);
}


list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
$context = context_module::instance($cm->id);

require_course_login($course, true, $cm);

$evaluation = $PAGE->activityrecord;
$subquery = "";
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
if ($department) {
    $url->param('department', $department);
    $urlparams['department'] = $department;
}

// set PAGE layout and print the page header
$evurl = new moodle_url('/mod/evaluation/analysis_course.php', array('id' => $id)); //,'courseid' => $courseid ) );
evSetPage($url, $evurl, get_string("analysis", "evaluation"));

// handle CoS priveleged user
if (isset($_SESSION['CoS_privileged'][$USER->username])) {
    print "Auswertungen der Studiengänge: " . '<span style="font-weight:600;white-space:pre-line;">'
            . implode(", ", $_SESSION['CoS_privileged'][$USER->username]) . "</span><br>\n";
}

$icon = '<img src="pix/icon120.png" height="30" alt="' . $evaluation->name . '">';
echo $OUTPUT->heading($icon . "&nbsp;" . format_string($evaluation->name));

if (!isset($_SESSION["participating_courses_of_studies"])) {
    $_SESSION["participating_courses_of_studies"] = 3;
    if (!empty($sg_filter)) {
        $_SESSION["participating_courses_of_studies"] = safeCount($sg_filter);
    }
}

list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) =
        evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm, true);
list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation);
if (!isset($_SESSION['myEvaluations'])) {
    $_SESSION["myEvaluations"] = get_evaluation_participants($evaluation, $USER->id);
    $_SESSION["myEvaluationsName"] = $evaluation->name;
}
$evaluationstructure = new mod_evaluation_structure($evaluation, $PAGE->cm, $courseid, null, 0,
            $teacherid, $course_of_studies, $course_of_studiesID, $department,$analysisCoS);

$cosStudies = safeCount($evaluationstructure->get_completed_course_of_studies());
$completed_responses = $evaluationstructure->count_completed_responses();
$minResults = evaluation_min_results($evaluation);
$minResultsText = min_results_text($evaluation);
$minResultsPriv = min_results_priv($evaluation);
$privGlobalUser = (is_siteadmin() OR (isset($_SESSION["privileged_global_users"][$USER->username]) &&
        !empty($_SESSION["privileged_global_users"][$USER->username])));
if ($privGlobalUser) {
    $minResults = $minResultsText = $minResultsPriv;
}

if (isset($_SESSION['CoS_privileged_sgl'][$USER->username])){
    $graphicsonly = true;
}


$numTextQ = evaluation_count_qtype($evaluation, "textarea");
$is_open = evaluation_is_open($evaluation);

$isTeacher = defined('isTeacher');
$isStudent = defined('isStudent');
if (!empty($_SESSION["myEvaluations"])) {
    if (!$isTeacher) {
        $isTeacher = evaluation_is_teacher($evaluation, $_SESSION["myEvaluations"]);
    }
    if (!$isStudent) {
        $isStudent = evaluation_is_student($evaluation, $_SESSION["myEvaluations"]);
    }
}

/*$Teacher 	= ( ( defined('EVALUATION_OWNER') OR defined("isStudent")) ? false 
			: evaluation_is_teacher( $evaluation, $_SESSION["myEvaluations"], $courseid ));*/
//echo "Teacher: $Teacher - SESSION['myEvaluations']: ".nl2br(var_export($_SESSION["myEvaluations"],true));
$Teacher = evaluation_is_teacher($evaluation, $_SESSION["myEvaluations"], $courseid);

$showUnmatched_minResults = false;
if ($Teacher) {
    $showUnmatched_minResults = ($completed_responses >= $minResults and $completed_responses < $minResultsText);
} else if (defined('EVALUATION_OWNER') and !evaluation_cosPrivileged($evaluation)) {
    $showUnmatched_minResults = ($completed_responses < $minResultsPriv);
}

/// print the tabs
$current_tab = 'analysis';
if ($Teacher and !$courseid) {
    if ($teacherid) {
        $current_tab = 'analysisTeacher';
    } else {
        $current_tab = 'analysisASH';
    }
} else if ((!$isPermitted and !$courseid) and !$is_open) {
    $current_tab = 'analysisASH';
}


require('tabs.php');


if ($SiteEvaluation and !$courseid and (!defined('EVALUATION_OWNER') ? true : !$cosPrivileged)) {
    $CourseTitle = "\n<span style=\"font-size:12pt;font-weight:bold;display:inline;\">" . get_string("all_courses", "evaluation") .
            "</span>";
}

$Studiengang = "";
$numTeachers = 0;
if ($courseid and $courseid !== SITEID) {
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
    $numTeachers = safeCount($_SESSION["allteachers"][$courseid]);
}

//if ( $is_open AND $Teacher )
/*if ($Teacher) // and !$courseid )
{
    $Teacher = evaluation_is_teacher($evaluation, $_SESSION["myEvaluations"], $courseid);
}*/
if ($Teacher) {    //if ( !defined( "isTeacher") ) { define( "isTeacher", true ); }
    if ($is_open and $teacherid AND $teacherid != $USER->id) // OR $courseid
    {
        redirect(new moodle_url($url, ['teacherid' => $USER->id, 'courseid' => $courseid]));
    } else {
        if ($teacherid == $USER->id and $numTeachers > 1) // $evaluation->teamteaching )
        {
            echo '<br><span style="font-size:12pt;font-weight:bold;display:inline;color:blue;">'
                    . "Evaluationen für " . get_string('teacher', 'evaluation') . ': ' . $USER->firstname . ' ' . $USER->lastname .
                    "</span><br>\n";
        } else if (false) {
            if ($teacherid and $courseid and safeCount($_SESSION["allteachers"][$courseid]) > 1) {
                echo "TeamTeaching war in dieser Evaluation nicht aktiviert und es gab mehr als eine Dozent_in in diesem Kurs. 
					  Daher können Sie die Kursauswertung nicht einsehen!<br>\n";
            } else if (!$courseid) {
                echo "TeamTeaching war in dieser Evaluation nicht aktiviert. Daher werden die Auswertungen von Kursen, 
					  die mehr als eine Dozent_in hatten hier ignoriert!";
            }
        }
    }
}
if (!$graphicsonly AND $numTextQ and $showUnmatched_minResults) {
    echo '<br><b style="color:#000065;">Für diese Auswertung wurden weniger als ' . ($minResultsText)
            . " Abgaben gemacht. Daher können Sie keine Textantworten einsehen!</b><br>\n";
}

//get the groupid
//lstgroupid is the choosen id
$mygroupid = false;

/* testing combined form:
$studyselectform = new mod_evaluation_filters_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
if ( $data = $studyselectform->get_data() ) {
	redirect(new moodle_url($url, ['course_of_studies' => $data->course_of_studies]));
}
echo "\n".'<div style="display:inline;float:left;" class="d-print-none">'; // do not print
$studyselectform->display(); 
*/

// Output to printer only / Print Only (Hide on screen only)
if (!$courseid) {
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
    print '</span></div>';
}

// show loading spinner
evaluation_showLoading();

// set filter forms
if ($completed_responses AND (has_capability('mod/evaluation:viewreports', $context)
                || (defined('EVALUATION_OWNER') AND ($cosPrivileged ?!$analysisCoS:true )))) {

    // construct questions and subquery arrays
    // start of snippets duplicated in compare_results.php
    $query = "SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id 
				AND (typ='multichoice' OR typ='numeric') AND hasvalue=1 
				AND name NOT ILIKE '%" . get_string("course_of_studies", "evaluation") . "%' 
				ORDER by position ASC";
    $allQuestions = $DB->get_records_sql($query);
    $numAllQuestions = safeCount($allQuestions);

    $qSelected = intval(ev_session_request("qSelected", ""));
    $applysubquery = intval(ev_session_request("applysubquery", 0));
    $subqueries = ev_session_request("subqueries", array());
    $subqueryids = array();
    $presentation = array();
    $scheme = $numQuestions = "";
    $stimmezu = array("stimme zu", "stimme eher zu", "stimme eher nicht zu", "stimme nicht zu");
    $trifftzu = array("trifft zu", "trifft eher zu", "trifft eher nicht zu", "trifft nicht zu");
    $schemeQ = "( presentation ilike '%stimme zu%' OR presentation ilike '%trifft zu%')";

    if ($qSelected) {

        $query = "SELECT * FROM {evaluation_item} WHERE id = $qSelected 
                                    AND evaluation=$evaluation->id ORDER by position ASC";
        $question = array();
        $questions = $DB->get_records_sql($query);
        //extract presentation list
        foreach ($questions as $question) {    //$question = $question1; break; }
            $itemobj = evaluation_get_item_class($question->typ);
            $itemInfo = $itemobj->get_info($question);

            $presentationraw = $presentation =
                    /*explode("|", str_replace(array("<<<<<1", "r>>>>>", "c>>>>>", "d>>>>>", "\n"), "",
                            $question->presentation));*/
                    explode("|", str_replace(array("\t", "\r", "\n", "<<<<<1", "r>>>>>", "c>>>>>", "d>>>>>"),
                            "",
                            $question->presentation));

            // sub queries
            if (isset($_REQUEST['sqfilter']) ) {
                if (intval($_REQUEST['sqfilter']) == 1 and $_REQUEST['subreply']) {
                    $applysubquery = 1;
                    $_SESSION['subqueries'][$qSelected]['item'] = $qSelected;
                    $_SESSION['subqueries'][$qSelected]['name'] = trim($question->name);
                    $_SESSION['subqueries'][$qSelected]['value'] = $_REQUEST['subreply'];
                    $_SESSION['subqueries'][$qSelected]['reply'] = trim($presentationraw[intval($_REQUEST['subreply']) - 1]);
                } else if (intval($_REQUEST['sqfilter']) == 2) {
                    unset($_SESSION['subqueries'][$qSelected]);
                }
            }

            if (in_array("k.b.", $presentation) or in_array("keine Angabe", $presentation) or
                    in_array("Kann ich nicht beantworten", $presentation)) {
                array_pop($presentation);
            }
            // $presentationraw = $presentation; // used for subqueries
            $qfValues = "";
            for ($cnt = 1; $cnt <= (safeCount($presentation)); $cnt++) {
                $qfValues .= "'$cnt'" . ($cnt < safeCount($presentation) ? "," : "");
            }
            $scheme = implode(", ", $presentation) . " <=> $qfValues";

            array_unshift($presentation, ($validation ? "ungültig" : "keine Antwort"));
            break;
            //print "<br>qfValues: $qfValues<br>Scheme: $scheme<br>presentation: " . var_export($presentation,true)
            //. "<br>info: " .var_export($info,true) . "<br>" ;
            //print 'Ausgewertete Frage: <span style="' . $boldStyle .'">'	. $question->name . "</span><br>\n";
        }
    } else {
        $query = "SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id 
                    AND (typ like'multichoice%' OR typ='numeric') AND $schemeQ
					ORDER BY position ASC";
        $questions = $DB->get_records_sql($query);
        //print "<br><hr>".var_export($questions,true);exit;
        $numQuestions = safeCount($questions);
        //$presentation = array( ($validation ?"ungültig" :"keine Antwort") ) + $stimmezu;
        $presentation = array_merge(array(($validation ? "ungültig" : "keine Antwort")), $stimmezu);
        //, "stimme zu", "stimme eher zu", "stimme eher nicht zu", "stimme nicht zu" );
        $scheme = '"stimme zu"=1 - "stimme nicht zu"=4<br>';
        $present = "nope";
        foreach ($questions as $quest) {
            $present = $quest->presentation;
            break;
        }
        if ($numQuestions and stristr($present, "trifft")) {
            $presentation = array_merge(array(($validation ? "ungültig" : "keine Antwort")), $trifftzu);
            $scheme = '"trifft zu"=1 - "trifft nicht zu"=4<br>';
        }
        $qfValues = "";
        for ($cnt = 1; $cnt <= (safeCount($presentation)); $cnt++) {
            $qfValues .= "'$cnt'" . ($cnt < safeCount($presentation) ? "," : "");
        }
        /*print '<span title="' . $hint . '">Ausgewertete Single Choice Fragen: </span><span style="'
                . $boldStyle . '">' . $numQuestions . "</span> - ";
        */
    }
    if (false) //empty($presentation) )
    {
        echo $OUTPUT->notification("Es gibt keine multichoice Fragen und auch keine Fragen mit numerischen Antworten. 
				Eine statistische Auswertung ist für diese Evaluation nicht möglich!");
        echo $OUTPUT->footer();
        flush();
        exit;
    }

    if (!empty($_SESSION['subqueries'])) {
        $subquerytxt = "Filter auf Fragen: ";
        foreach ($_SESSION['subqueries'] as $subqueryid) {
            $subqueryids[] = $subqueryid['item'];
            if ($applysubquery) {
                /*$subquery .= " AND completed IN ((SELECT completed AS done FROM {evaluation_value}
		                        WHERE item=" .$subqueryid['item'] ." and value='".$subqueryid['value']."'))";
                // not working...
                $subquery .= " AND EXISTS (SELECT completed AS done FROM {evaluation_value}
		                        WHERE item=" .$subqueryid['item'] ." and value='".$subqueryid['value']."')";
                */
                $subquery .= " AND completed IN ((SELECT completed AS done FROM {evaluation_value}
		                        WHERE item=" . $subqueryid['item'] . " and value='" . $subqueryid['value'] . "'))";
                $subqueryC .= str_ireplace("AND completed", "AND id", $subquery);
            }
            $subquerytxt .= " '" . $subqueryid['name'] . "' mit Antwort: '" . $subqueryid['reply'] . "', ";
        }
        $subquerytxt = substr($subquerytxt, 0, -2);
        // print "subqueries: ".nl2br(var_dump($_SESSION['subqueries'], true));
        // print "subquery: " . $subqueryC;
    }

    print "<form style='display:inline;' method='post'>\n";
    // start of snippet duplicated in compare_results.php
    if ($qSelected) {
        print "<b>Ausgewertete Frage</b>: ";
    }

    print    '<select name="qSelected" style="' . $buttonStyle . '" onchange="this.form.submit();">' . "\n"
            . '<option value="">' . get_string("all") . " " . $numQuestions
            . " vergleichbar auswertbaren " .
            get_string("questions", "evaluation") . "</option>\n";
    foreach ($allQuestions as $question) {
        $selected = "";
        if ($question->id == $qSelected) {
            $selected = ' selected="' . $selected . '" ';
        }
        if ($isStudent AND stristr($question->name,"Geschlecht")){
            continue;
        }
        $qname = $question->name;
        if (strlen($qname) > 90) {
            $qname = substr($qname, 0, 87) . "...";
        }
        print '<option value="' . $question->id . '"' . $selected
                . ' title="' . htmlentities($question->name) . '">' . $qname
                . "</option>\n";
    }
    print "</select>\n";
    if ($qSelected) {
        if (defined('EVALUATION_OWNER')) {
            $value = in_array($qSelected, $subqueryids) ? "2" : "1";
            $label = "Filter " . (in_array($qSelected, $subqueryids) ? "entfernen" : "setzen");
            ?>
            <button name="sqfilter" style="<?php echo $style; ?>" value="<?php echo $value; ?>"
                    onclick="this.form.submit();"><?php
                echo $label; ?></button>
            <?php
            if ($value == 1) {
                print '<span id="replies">';
                $cnt = 1;
                // $hide_reply = array("k.b.", "keine Angabe", "Kann ich nicht beantworten");
                $hide_reply = array();
                foreach ($presentationraw as $reply) {
                    if ( !in_array($reply, $hide_reply)) {
                        print '<label>';
                        print '<input type="radio" name="subreply" value="' . $cnt . '">';
                        print "$reply&nbsp;</label>";
                    }
                    $cnt++;
                }
                print "</span>\n";
            }
        }
        if ($itemInfo->subtype == 'c') {
            print '<br><span style="color:blue;">Dies ist eine Multi Choice Frage. 
                            Es können nur Single Choice Antworten sinnvoll ausgwertet werden'
                    . "</span><br>\n";
        }
    }
    // subqueries
    if (!empty($_SESSION['subqueries'])) {
        ?><br><b>Filter</b> anwenden:&nbsp;
        <label><input type="radio" name="applysubquery" <?php echo($applysubquery ? "checked" : ""); ?>
                      onclick="this.form.submit();" value="1">Ja</label>&nbsp;
        <label><input type="radio" name="applysubquery" <?php echo(!$applysubquery ? "checked" : ""); ?>
                      onclick="this.form.submit();" value="0">Nein</label>
        <?php

        // results for subqueries
        if ($subquerytxt) {
            print '<span style="font-weight:normal;color:blue;"> - ' . $subquerytxt . "</span>";
        } else {
            print "&nbsp;&nbsp;";
        }
    }
    print "</form>\n";
    // end of snippets duplicated in compare_results.php



    echo "\n" . '<div style="display:none;" id="evFilters" class="d-print-none">';
    if (is_siteadmin()) {
        echo '<span id="evFiltersMsg"></span>';
    } //<b>'.EVALUATION_OWNER.'</b>

	// process department (Fachbereich) select form $_SESSION['CoS_department'][$CoS]
    if ($SiteEvaluation and $_SESSION["participating_courses_of_studies"]>1 AND
            !$cosPrivileged and !$courseid AND !$course_of_studiesID AND !$teacherid
            AND (isset($_SESSION['CoS_department']) ?$_SESSION['CoS_department'] >1 :true)) {
        $deptselectform =
                new mod_evaluation_department_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
        if ($data = $deptselectform->get_data()) {
            evaluation_spinnerJS(false);
            redirect(new moodle_url($url, ['department' => $data->department]), "", 0);
        }
        echo "\n" . '<div style="padding: 2px; border:teal solid 1px;display:inline;float:left;" class="d-print-none">'; // do not print
        $deptselectform->display();
        echo "</div>\n";
    }
    // Process course of studies select form.
    if ($SiteEvaluation and $cosStudies>1
            AND !$courseid AND $_SESSION["participating_courses_of_studies"]>1
            AND (!$cosPrivileged OR (isset($_SESSION['CoS_privileged'][$USER->username])
                    ?count($_SESSION['CoS_privileged'][$USER->username])>1:false))){
        $studyselectform =
                new mod_evaluation_course_of_studies_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
        if ($data = $studyselectform->get_data()) {
            evaluation_spinnerJS(false);
            redirect(new moodle_url($url, ['course_of_studiesID' => $data->course_of_studiesID]), "", 0);
        }
        echo "\n" . '<div style="padding: 2px; border:teal solid 1px;display:inline;float:left;" class="d-print-none">'; // do not print
        $studyselectform->display();
        echo "</div>\n";
    }
    if ($SiteEvaluation) {    // Process course select form.
        $courseselectform = new mod_evaluation_course_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
        if ($data = $courseselectform->get_data()) {
            evaluation_spinnerJS(false);
            redirect(new moodle_url($url, ['courseid' => $data->courseid]), "", 0); // );
        }
        echo "\n" . '<div style="padding: 2px; border:teal solid 1px;display:inline;float:left;" class="d-print-none">'; // do not print
        $courseselectform->display();
        echo "</div>\n";
    }
    //print safeCount($_SESSION["allteachers"][$courseid]);nl2br(var_export($_SESSION["allteachers"][$courseid]));
    if ( (!$courseid or safeCount($_SESSION["allteachers"][$courseid]) > 1) AND !isset($_SESSION['CoS_privileged_sgl'][$USER->username]))
    {    // Process teachers select form.
        $teacherselectform = new mod_evaluation_teachers_select_form($url, $evaluationstructure, $evaluation->course == SITEID);
        if ($data = $teacherselectform->get_data()) {
            evaluation_spinnerJS(false);
            redirect(new moodle_url($url, ['teacherid' => $data->teacherid]), "", 0); //  );
        }
        echo "\n" . '<div style="padding: 2px; border:teal solid 1px;display:inline;float:left;" class="d-print-none">'; // do not print
        $teacherselectform->display();
        echo "</div>\n";
    }
    echo '</div><div style="display:block;clear:both;">&nbsp;</div>' . "\n";
}

echo "\n" . '<div id="evButtons" style="display:none;" class="d-print-none">';   // no printing

// do not show results if less than minimum required evaluations
// before July 28,2022: !defined('EVALUATION_OWNER') ?true :$cosPrivileged 

if (!$completed_responses) {
    evaluation_spinnerJS(false);
    $teacherTxt = ($Teacher and $teacherid) ? " für Sie." : "";
    echo "</div>" . $OUTPUT->notification(get_string('no_responses_yet', 'mod_evaluation') . $teacherTxt);
    echo $OUTPUT->footer();
    exit;
}
if ($completed_responses < $minResults) {
    evaluation_spinnerJS(false);
    $teacherTxt = ($Teacher and $teacherid) ? " Es werden nur die Abgaben für Sie ausgewertet." : "";
    echo "</div><p style=\"color:red;font-weight:bold;align:center;\">" . get_string('min_results', 'evaluation', $minResults) .
            $teacherTxt . "</p>";
    if (!is_siteadmin()) {
        echo $OUTPUT->footer();
        exit;
    }
}

$evaluation_has_user_participated = evaluation_has_user_participated($evaluation, $USER->id, $courseid);
$non_participated_student = ($courseid and defined('isStudent') and !$evaluation_has_user_participated);
// check access rights
if (!defined('EVALUATION_OWNER') and !$Teacher) {
    if (    /* (!$courseid and $is_open) or */
            (!defined('isStudent') and ($teacherid or $course_of_studies or $courseid))
            or $non_participated_student
    ) {
        evaluation_spinnerJS(false);
        $txt = "";
        if ($non_participated_student) {
            $txt = "Sie haben für diesen Kurs NICHT an der Evaluation teilgenommen. ";
        }

        print '<br><h2 style="font-weight:bold;color:darkred;background-color:white;">'
                . $txt . get_string('no_permission_analysis', 'evaluation') . "</h2><br>";
        print $OUTPUT->continue_button("/mod/evaluation/view.php?id=$id");
        print $OUTPUT->footer();
        exit;
    }
}

$buttonStyle = 'margin: 3px 5px;font-weight:bold;color:white;background-color:teal;';
$activebuttonStyle = 'text-decoration:underline;margin: 3px 5px;font-weight:bold;color:white;background-color:teal;';
// Button Auswertung drucken
echo '<div style="float:left;">';
echo evPrintButton();
echo '</div>';

// show Evaluations per course to privileged persons - moved to view.php
if (false and defined('EVALUATION_OWNER')) {
    echo '<div style="float:left;' . $buttonStyle . '">';
    print html_writer::tag('a', "Abgaben/Kurs", array('style' => $buttonStyle,
            'href' => 'print.php?id=' . $id . '&courseid=' . $courseid . '&showResults=6&goBack=analysis_course'));
    echo '</div>';
}

// compare Evaluation results of filter with complete results to teachers and privileged persons	
if (false) //true OR $Teacher OR defined('EVALUATION_OWNER') )
{
    $stats = get_string("statistics", "evaluation") .
            " mit Vergleich"; // . (($courseid OR $course_of_studiesID OR $teacherid) ?" mit Vergleich" :"");
    ?>
    <div style="float:left;">
        <form style="display:inline;" method="POST" action="print.php">
            <button name="showCompare" style="<?php echo $buttonStyle; ?>" value="1"
                    onclick="this.form.submit();"><?php echo $stats; ?></button>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
            <input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID; ?>">
            <input type="hidden" name="teacherid" value="<?php echo $teacherid; ?>">
        </form>
    </div>
    <?php
}

// Button "Export to excel".
//if ( ($isPermitted OR has_capability('mod/evaluation:viewreports', $context)) AND $evaluationstructure->count_completed_responses()) {
if (($isPermitted or ($Teacher and $teacherid)) and $evaluationstructure->count_completed_responses()) {
    echo '<div style="float:left;' . $buttonStyle . '">';
    print html_writer::tag('a', get_string('export_to_excel', 'evaluation'), array('style' => $buttonStyle,
            'href' => 'analysis_to_excel.php?sesskey=' . sesskey() . '&id=' . $id . '&courseid=' . (int) $courseid .
                    '&teacherid=' . (int) $teacherid . '&course_of_studiesID=' . (int) $course_of_studiesID));
    //'params' => ['sesskey' => sesskey(), 'id' => $id, 'courseid' => (int)$courseid]));
    echo '</div>';
}

// show / print only text
if ($numTextQ and (((!$showUnmatched_minResults and ($completed_responses >= $minResultsText
                                AND !isset($_SESSION['CoS_privileged_sgl'][$USER->username])
                                AND ($cosPrivileged or $Teacher)))) or
                (defined('EVALUATION_OWNER') ? !$cosPrivileged : false))) {
    ?>
    <div style="float:left;">
        <form style="display:inline;" method="POST">
            <button name="TextOnly" style="<?php
            echo ($TextOnly ?$activebuttonStyle :$buttonStyle);
            ?>" value="1" onclick="this.form.submit();">Nur Text</button>
        </form>
    </div>
    <div style="float:left;">
        <form style="display:inline;" method="POST">
            <button name="graphicsonly" style="<?php
            echo ($graphicsonly ?$activebuttonStyle :$buttonStyle);
            ?>" value="1" onclick="this.form.submit();">Kein Text</button>
        </form>
    </div>
    <?php
}

$showGraf = (strstr($SetShowGraf, "anzeigen") ? "true" : "false");
$sGstatus = ($showGraf == "true" ? "verbergen" : "anzeigen");
// select chart types
?>
    <div style="float:left;">
        <form style="display:inline;" method="POST">
            &nbsp;<input name="SetShowGraf" id="SetShowGraf" type="submit" style="<?php echo $buttonStyle; ?>"
                         value="Grafikdaten <?php echo $sGstatus; ?>">
            &nbsp;<input type="submit" style="<?php echo $buttonStyle; ?>" value="Grafik:">
            <select name="Chart" style="<?php echo $buttonStyle; ?>" onchange="this.form.submit();">
                <?php
                $charts = array("bar" => "Balken -horizontal", "stacked" => "Balken -vertikal", "line" => "Liniendiagramm",
                        "linesmooth" => "Liniendiagramm -gerundet",
                        "pie" => "Kreisdiagramm", "doughnut" => "Kreisdiagramm -Donut");
                foreach ($charts as $chart => $label) {
                    $selected = "";
                    if ($chart == $Chart) {
                        $selected = ' selected="' . $selected . '" ';
                    }
                    print '<option value="' . $chart . '"' . $selected . '>' . $label . "</option>\n";
                }
                ?>
            </select>
        </form>
    </div>
<?php

print '</div><div style="clear:both;"></div>';

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


if ( $subquery AND $applysubquery){
    $filter = "";
    if ($courseid){
        $filter .= " AND courseid=" . $courseid;
    }
    if ($teacherid){
        $filter .= " AND teacherid=" . $teacherid;
    }
    if ($course_of_studies){
        $filter .= " AND course_of_studies='$course_of_studies'";
    }
    if ($department){
        $filter .= str_replace("completed.","",$evaluationstructure->get_department_filter());
    }

    $numresultsSq =
            safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} 
                WHERE evaluation=$evaluation->id $filter $subqueryC"));
    $sqTitle = "Alle gefilterten Abgaben: ";
    echo '<span><b>' . $sqTitle . '</b></span>: '
            . $numresultsSq
            . "<br>\n";
}


// Show the summary.
echo "<b>" . get_string('completed_evaluations', "evaluation") . "</b>: " . $completed_responses . "<br>\n";
echo "<b>" . get_string("questions", "evaluation") . "</b>: " . safeCount($items) .
        " ($itemsCounted numerisch ausgewertete Fragen)<br>\n";

// show div evCharts after pageload
echo "\n" . '<div  class="d-print-block" id="evCharts" style="display:none;">';   // no display before onload

// if (preg_match('/rated$/i', $item->typ))
if ($courseitemfilter > 0) {
    $sumvalue = 'SUM(' . $DB->sql_cast_char2real('value', true) . ')';
    $sql = "SELECT fv.courseid, c.shortname, $sumvalue AS sumvalue, COUNT(value) as countvalue
            FROM {evaluation_value} fv, {course} c, {evaluation_item} fi
            WHERE fv.courseid = c.id AND fi.id = fv.item AND fi.typ = ? AND fv.item = ?
            GROUP BY courseid, shortname
            ORDER BY sumvalue desc";

    if ($courses = $DB->get_records_sql($sql, array($courseitemfiltertyp, $courseitemfilter))) {
        $item = $DB->get_record('evaluation_item', array('id' => $courseitemfilter));
        echo '<h4>' . $item->name . '</h4>';
        echo '<div class="clearfix"></div>';
        echo '<table>';
        echo '<tr><th>Course</th><th>Average</th></tr>';

        foreach ($courses as $c) {
            $coursecontext = context_course::instance($c->courseid);
            $shortname = format_string($c->shortname, true, array('context' => $coursecontext));

            echo '<tr>';
            echo '<td>' . $shortname . '</td>';
            echo '<td align="right">';
            echo format_float(($c->sumvalue / $c->countvalue), 2);
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>' . get_string('noresults') . '</p>';
    }
    echo '<p><a href="analysis_course.php?id=' . $id . '&courseid=' . $courseid . '">';
    echo get_string('back');
    echo '</a></p>';
} else {

    // new feature to compare results -- not working, need print - function to compare average values
    $compare = false; // is_siteadmin() AND ( $courseid OR $course_of_studiesID OR $teacherid);
    //echo "<br>Studiengang: $course_of_studies<br>";

    $byTeacher =
            ((($Teacher and $teacherid == $USER->id) or defined('EVALUATION_OWNER')) and $completed_responses >= $minResultsText);
    echo "<br>\n";
    // Print the items in an analysed form.
    foreach ($items as $key => $item) {
        // filter data display by privileges
        // before: ( !defined('EVALUATION_OWNER') ?true :$cosPrivileged )
        if (!is_siteadmin() and defined("SiteEvaluation")) {
            if ((!$byTeacher and !in_array($item->typ, array("numeric", "multichoice", "multichoicerated"))) or
                    ($courseid and
                            (stripos($item->name, "geschlecht") !== false or stripos($item->name, "semester") !== false or
                                    stripos($item->name, "studiengang") !== false))
            ) {
                continue;
            }
        }

        // show text replies only
        if ($TextOnly and in_array($item->typ, array("numeric", "multichoice", "multichoicerated"))) {
            continue;
        }
        if ($graphicsonly and !in_array($item->typ, array("numeric", "multichoice", "multichoicerated"))) {
            continue;
        }

        if ((!empty($course_of_studiesID) or $courseid) and
                stripos($item->name, get_string("course_of_studies", "evaluation")) !== false) {
            continue;
        }
        echo '<table style="width:100%;">';
        //echo nl2br(var_export($key,true)).nl2br(var_export($item,true));
        $itemobj = evaluation_get_item_class($item->typ);
        $printnr = ($evaluation->autonumbering && $item->itemnr) ? ($item->itemnr . '.') : '';
        echo "<tr><td>\n";
        if (in_array($item->typ, array("multichoice", "multichoicerated"))) {
            $itemobj->print_analysed($item, $printnr, $mygroupid, $evaluationstructure->get_courseid(),
                    $evaluationstructure->get_teacherid(),
                    $evaluationstructure->get_course_of_studies(), $evaluationstructure->get_department(), $subquery, $Chart );
        } else {
            $itemobj->print_analysed($item, $printnr, $mygroupid, $evaluationstructure->get_courseid(),
                    $evaluationstructure->get_teacherid(),
                    $evaluationstructure->get_course_of_studies(), $evaluationstructure->get_department(), $subquery);
        }
        if (false and is_siteadmin() and $courseid) {
            if ($course->id == SITEID and defined("SiteEvaluation") and
                    (defined('EVALUATION_OWNER') || in_array($item->typ, array("multichoice", "multichoicerated")))) {
                $itemobj->print_analysed($item, $printnr, $mygroupid, $evaluationstructure2->get_courseid(),
                        $evaluationstructure2->get_teacherid(), $evaluationstructure2->get_course_of_studies(), $Chart);
            }
        }
        echo '</td></tr>';
        if (preg_match('/rated$/i', $item->typ)) {
            $url = new moodle_url('/mod/evaluation/analysis_course.php', array('id' => $id,
                    'courseitemfilter' => $item->id, 'courseitemfiltertyp' => $item->typ));
            $anker = html_writer::link($url, get_string('sort_by_course', 'evaluation'));

            echo '<tr><td colspan="2">' . $anker . '</td></tr>';
        }
        echo '</table>';
    }

}

// display graphs once page is loaded
print "\n</div> <!-- end evCharts-->\n";

//echo nl2br(var_export($GLOBALS['CFG'],true));
//echo "<br>evaluationstructure: ".nl2br(var_export($evaluationstructure,true));
//echo "<br>teacherid: ".$evaluationstructure->get_teacherid();
//echo " - course_of_studies: ".$evaluationstructure->get_course_of_studies();

// js code for closing spinner
evaluation_spinnerJS();
// logging
evaluation_trigger_module_analysed($evaluation, $cm, $courseid);

echo $OUTPUT->footer();

// load js to handle printing
$printWidth = "126vw";
require_once("print.js.php");
