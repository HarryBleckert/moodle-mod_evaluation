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
 * print a printview of evaluation-items
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once("../../config.php");
require_once("lib.php");
global $USER, $DB;

/* used only once to migrate data from evaluation tables
$e_from_f = optional_param('e_from_f', false, PARAM_INT); // copy globl evaluations forem evaluation tables to evaluation tables
if( $e_from_f)
{	evaluation_copy_from_evaluation(); // in lib.php, use with care
	exit;
}*/


$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT); // Course where this evaluation is mapped to - used for return link.
$teacherid = optional_param('teacherid', false, PARAM_INT);
$daily_progress = optional_param('daily_progress', false, PARAM_INT); // show daily evaluation completions from start to end of evaluation 
$logViews = optional_param('logViews', false, PARAM_INT); // show log views from Moodle log
$logsubject = optional_param('logsubject', 'AllActivities', PARAM_TEXT); // subject for log views from Moodle log
$course_of_studiesID = optional_param('course_of_studiesID', false, PARAM_INT);
$showCourses_of_studies = optional_param('showCourses_of_studies', false, PARAM_INT);
$showCourses = optional_param('showCourses', false, PARAM_INT); // show all courses of evaluation>id
$reminder = optional_param('reminder', false, PARAM_INT); // show option to participate in evaluation completion
$showTeacher = optional_param('showTeacher', false, PARAM_INT); // show all courses for teacher by user->id
$showResults = optional_param('showResults', false, PARAM_INT); // show list of courses with evaluation results
$_SESSION["notevaluated"] = optional_param('notevaluated', "", PARAM_ALPHA); // show  courses that have not been evaluated
$showEvaluations = optional_param('showEvaluations', false, PARAM_INT); // show list of courses with evaluation results
$sortBy = optional_param('sortBy', "", PARAM_ALPHA); // sort order for showResults
$autofill = optional_param('autofill', false, PARAM_INT); // evaluation_autofill_item_studiengang
$showTeacherResults = optional_param('showTeacherResults', false, PARAM_INT); // show list of teachers with evaluation results
$showCompare = optional_param('showCompare', false, PARAM_INT); // compare all results with filter results

$Chart = optional_param('Chart', false, PARAM_ALPHANUM);
if ( !isset($_SESSION["Chart"]) ) {	$_SESSION["Chart"] = "line"; }
if ( empty($Chart) ) {	$Chart = $_SESSION["Chart"]; }
else {	$_SESSION["Chart"] = $Chart; }

$PAGE->set_url('/mod/evaluation/print.php', array('id'=>$id, 'courseid'=>$courseid));

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
require_course_login($course, true, $cm);

$evaluation = $PAGE->activityrecord;
$evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $courseid);
list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) = evaluation_check_Roles_and_Permissions( $courseid, $evaluation, $cm );

if ( $course_of_studiesID )
{	$course_of_studies = evaluation_get_course_of_studies_from_evc( $course_of_studiesID, $evaluation ); }


$PAGE->set_pagelayout('popup');

// Print the page header.
//$strevaluations = get_string("modulenameplural", "evaluation");
//$evaluation_url = new moodle_url('/mod/evaluation/index.php', array('id'=>$course->id));
//$PAGE->navbar->add($strevaluations, $evaluation_url);
//$PAGE->navbar->add(format_string($evaluation->name));

$PAGE->set_title($evaluation->name);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();
print '<div id="LoginAs" class="LoginAs d-print-none"></div><span style="clear:both;"><br></span>';	
evaluation_LoginAs(); 

// Print the main part of the page.
$icon = '<img src="pix/icon120.png" height="30" alt="'.$evaluation->name.'">';
echo $OUTPUT->heading( $icon. "&nbsp;" .format_string($evaluation->name) );

// show return button
$goBack = html_writer::tag( 'button', "Zurück", array( 'class' =>"d-print-none", 'style'=>'color:white;background-color:black;text-align:center;', 
							'type' => 'button','onclick'=>'window.history.back();'));
							// 'type' => 'button','onclick'=>'(window.history.back()?window.history.back():window.close());'));

if ( defined('EVALUATION_OWNER') )
{
	if ( is_bool($showResults) === false )
	{	print showEvaluationCourseResults($evaluation, $showResults, $sortBy, $id, $courseid ); 
		//echo $OUTPUT->footer();
		evaluation_footer();
		$printWidth = "135vw"; 
		require_once("print.js.php");
		exit;
	}

	if ( is_bool($showTeacherResults) === false )
	{	print showEvaluationTeacherResults($evaluation, $showTeacherResults, $sortBy, $id, $courseid ); 
		evaluation_footer();
		$printWidth = "135vw"; 
		require_once("print.js.php");
		exit;
	}
}
if ( $showCompare )
{	require_once("compare_results.inc.php");
	evaluation_compare_results($evaluation, $courseid, $course_of_studiesID, $teacherid ); 
	evaluation_footer();
	// logging: error ivalid course module id ??? (March 2022)
	evaluation_trigger_module_statistics( $evaluation, $cm, $courseid );
	// load js to handle printing
	$printWidth = "115vw"; //100vw
	require_once("print.js.php");
	exit;
}


if( $autofill AND is_siteadmin() )
{	//evaluation_autofill_item_studiengang($autofill); // in lib.php, use with care, check item-no template and studiengang item before calling
	if 	( !$evaluation->teamteaching )
		{	echo  "<hr>\n";
			echo  "<br><hr>evaluation_autofill_field_teacherid für Evaluation ID $evaluation->id:<br>\n";
			//evaluation_autofill_field_teacherid($evaluation,true); 
			evaluation_autofill_duplicate_field_teacherid($evaluation, false);
			echo  "<br>Autofill wurde abgearbeitet!<hr>\n";	
		}
	evaluation_footer();
	exit;
}

if ( $logViews )
{	if ( !defined('EVALUATION_OWNER') )
	{	print "<p style=\"color:red;font-weight:bold;align:center;\">".get_string('you_have_no_permission', 'evaluation')."</p>"; 
		print $OUTPUT->footer();exit;
	}
	$date = get_string('date');
	$responses = "views";
	echo '<h1 style="display:inlinecolor:darkgreen;text-align:left;font-weight:bolder;">' . get_string('usageReport',"evaluation") ."</h1><br>\n";	
	// all log data for this evaluation
	$subjects = array( 	'AllActivities' => get_string('AllActivities', 'evaluation'), 'submittedEvaluations' => get_string('submittedEvaluations', 'evaluation'),
						'overview' => get_string('overview', 'evaluation'),	
						'analysis' => get_string('analysis', 'evaluation'), 'analysisExport' => "Excel Export", 
						'statistics' => get_string('statistics', 'evaluation'), 'show_entries' => get_string('show_entries', 'evaluation'), 
						'viewsglobalEvaluationInstances' => get_string('viewsglobalEvaluationInstances', 'evaluation') 
					);						
	$goBack = "<br>".html_writer::tag( 'a', "Zurück", array( 'class' =>"d-print-none",
								'style'=>'color:white;background-color:black;text-align:center;', 
								'href'=>'/mod/evaluation/view.php?id=' . $id ));
	echo $goBack;
	echo evPrintButton();
	echo '<br>Auswertung des Moodle Logs. Log Daten werden '. get_config('logstore_standard')->loglifetime . " Tage aufbewahrt.<br>\n";

	foreach ( $subjects as $key => $subject)
	{	if ( $key==$logsubject )
		{	$Style	= 'margin:3px 5px;font-weight:bold;color:teal;background-color:white;text-decoration:underline;'; }
		else
		{	$Style	= 'margin:3px 5px;font-weight:bold;color:white;background-color:teal;'; }
		echo	'<div style="float:left;' . $Style . '">';
		print html_writer::tag( 'a', $subject, array('style'=>$Style, 'href' => 'print.php?id='.$cm->id.'&logViews=1&logsubject=' . $key) );
		echo "</div>\n";
	}
	echo '<div style="display:block;clear:both;"></div>'."\n";

	$AllActivities = "contextinstanceid = $cm->id";
	$submittedEvaluations = "action = 'submitted' AND contextinstanceid = $cm->id ";
	$overview = "action = 'viewed' AND contextinstanceid = $cm->id";
	$analysis = "action = 'analysed' AND contextinstanceid = $cm->id";
	$analysisExport = "action = 'analysedExported' AND contextinstanceid = $cm->id";
	$statistics = "action = 'statistics' AND contextinstanceid = $cm->id";
	$show_entries = "action = 'entries' AND contextinstanceid = $cm->id ";
	$viewsglobalEvaluationInstances = "action = 'viewed' AND contextinstanceid = $evaluation->course and eventname like '%instance_list%'";
	$filter = ${$logsubject};
	$site_admins = evaluation_get_siteadmins();
	$query	  = 	"SELECT to_char(to_timestamp(timecreated),'YYYY-MM-DD Dy') AS \"$date\",
					COUNT(to_char(to_timestamp(timecreated),'YYYY-MM-DD Dy')) AS \"$responses\"
					FROM {logstore_standard_log} 
					WHERE component='mod_evaluation' AND $filter AND userid NOT IN ($site_admins) 
					GROUP BY to_char(to_timestamp(timecreated),'YYYY-MM-DD Dy')
					ORDER BY to_char(to_timestamp(timecreated),'YYYY-MM-DD Dy') ASC";
	//echo "Query: $query"	;
	$results	= $DB->get_records_sql($query);				
	$NumResults	= safeCount($results);

	if ( $logsubject != "viewsglobalEvaluationInstances" AND $evaluation->timeopen AND $evaluation->timeclose )
	{	echo "<b>".get_string('evaluation_period','evaluation') ."</b>: " . date("d.m.Y",$evaluation->timeopen) 
			. " - ".date("d.m.Y",$evaluation->timeclose)
			. " (" . total_evaluation_days($evaluation) . " ". get_string("days") .")<br>\n"; 
		if ( $logsubject == "analysisExport" AND $evaluation->timeclose < strtotime("March 13 2023") )
		{	echo "Der Filter 'Excel Export' wurde erstmals am 14.03.2023 gesetzt.<br>\n"; }
	}
	
	// if no results
	if ( empty($NumResults) )
	{	print "<p style=\"color:red;font-weight:bold;align:center;\">".get_string('no_data', 'evaluation')."</p>"; 
		print $OUTPUT->footer();exit;
	}

	//echo "Log Data: ".nl2br(var_export($AllResults,true));
	//echo "site admins: ".evaluation_get_siteadmins().'<br>';
	$results2 = $results;
	usort($results2, function($a, $b) 
	{	global $responses; 
		if ( $a->{$responses} == $b->{$responses} )
		{	return 0; }
		return ($a->{$responses} < $b->{$responses} ?-1 :1); 
	});
	
	$days = total_evaluation_days($evaluation);	
	$views = 0;
	foreach ( $results as $result ) {	$views += $result->$responses; }
	
	$average = round( $views / $NumResults );
	
	$median  = $results2[round($NumResults/2)]->{$responses};
	$modus   = $results2[0]->{$responses};

	$date = get_string('date');
	//echo get_string($logsubject,'evaluation');	
	echo "Aktivitäten/Tag: <b>Mittelwert</b>: ".evaluation_number_format($average)." - <b>Median</b>: "
			.evaluation_number_format($median)." - <b>Modus</b>: " . evaluation_number_format($modus).".<br>\n";
	echo "Aktivitäten gab es an <b>".$NumResults." Tagen</b>.<br>\n";
	echo "<b>Summe Aktivitäten</b>: <b>".evaluation_number_format($views)."</b>\n";
	echo '<div style="width:89%;">';
	$data = $results2 = array(); $count = $replies = $Modus = $Median = 0;
	foreach ( $results as $result )
	{	$replies += $result->{$responses};
		$Modus = ( $result->{$responses} > $Modus ? $result->{$responses} :$Modus );
		$results2[] = $result->{$responses};
		sort($results2);
		$Median  = $results2[round(($count)/2)];
		$data['labels'][$count] = $result->{$date};
		$data['series'][$count] = $result->{$responses};
		$data['series_labels'][$count] = $result->{$responses}; 
		$data['average'][$count] = round($replies/($count+1));
		$data['median'][$count] = $Median;
		$data['modus'][$count] = $Modus;
		$count++;
	}
	// draw line chart
	$chart = new \core\chart_bar();
	$series = new \core\chart_series(format_string(get_string("pageviews", "evaluation")), $data['series']);
	$seriesAvg = new \core\chart_series(get_string("average", "evaluation"), $data["average"]);
	$seriesMed = new \core\chart_series('Median', $data["median"]);
	$seriesMod = new \core\chart_series('Modus', $data["modus"]);
	$seriesAvg->set_type(\core\chart_series::TYPE_LINE); // Set the series type to line chart.
	$seriesMed->set_type(\core\chart_series::TYPE_LINE);
	$seriesMod->set_type(\core\chart_series::TYPE_LINE);
	$seriesAvg->set_smooth(true); 
	$seriesMed->set_smooth(true); 
	$seriesMod->set_smooth(true); // Calling set_smooth() passing true as parameter, will display smooth lines.		
	
	$series->set_labels($data['series_labels']);
	$chart->add_series($series);
	$chart->add_series($seriesAvg);
	$chart->add_series($seriesMed);
	$chart->add_series($seriesMod);
	$chart->set_labels($data['labels']);
    echo $OUTPUT->render($chart);
	echo '</div>';
	evaluation_footer();
	// load js to handle printing
	$printWidth = "100vw"; //100vw
	require_once("print.js.php");
	exit;
}


echo $goBack;
echo evPrintButton();
echo "<br>\n";


if ( $showTeacher )
{	if ( defined('EVALUATION_OWNER') OR $showTeacher == $USER->id ) //OR !empty($_SESSION["LoggedInAs"]) )
	{	$myEvaluations = get_evaluation_participants($evaluation, $showTeacher );
		//print nl2br(var_export($myEvaluations));	
		$teacherEvaluations = evaluation_countCourseEvaluations( $evaluation, $myEvaluations, "teacher", $showTeacher );
		$courseEvaluations  = evaluation_countCourseEvaluations( $evaluation, $myEvaluations, false, false );
		//if ( is_siteadmin() )	{	print nl2br("$teacherEvaluations - $courseEvaluations<hr>"); }
		if ( (!evaluation_is_open($evaluation) OR $evaluation->teamteaching) AND $teacherEvaluations < $courseEvaluations	)
		{	print show_user_evaluation_courses( $evaluation, $myEvaluations, $id, true, true, true );
			print "<h3>Ergebnisse für alle evaluierten Dozent_innen</h3>\n";
		}
		print show_user_evaluation_courses( $evaluation, $myEvaluations, $id, true, false, false );	
	
		print '<a href="analysis_course.php?id='.$id.'&teacherid='.$showTeacher
				.'" target="teacher"><b>'."Auswertung"."</b></a>&nbsp;&nbsp;\n";
		print '<a href="print.php?showCompare=1&id='.$id.'&teacherid='.$showTeacher
				.'" target="teacher"><b>'."Statistik"."</b></a>&nbsp;&nbsp;\n";
		if ( empty($_SESSION["LoggedInAs"]) )
		{	print '<a href="/user/profile.php?id='.$showTeacher.'" target="teacher"><b>'."Profilseite ansehen"."</b></a><br>\n\n"; }
	}
}



// show all results, but currently not used instead used showresults/showEvaluationCourseResults()
elseif ( false AND $showEvaluations )
{	list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) = evaluation_check_Roles_and_Permissions( $courseid, $evaluation, $cm, true );
	if ( 0 AND defined('EVALUATION_OWNER') )
	{	print "<h3>Ergebnisse für alle teilnehmenden Kurse</h3>\n";
		$SiteEvaluations = get_evaluation_participants($evaluation, -1 ); //($Userid = -1) ); 
		print show_user_evaluation_courses( $evaluation, $SiteEvaluations, $id, true, false, false );	
	}
}


elseif ( $reminder AND $courseid )
{ 	$fullname = ($USER->alternatename ?$USER->alternatename :$USER->firstname). " " . $USER->lastname;
	$myCourse = get_course($courseid);
	echo "<h2>Kurs: $myCourse->fullname</h2><br>\n";
	echo "<h3>Guten Tag $fullname,<br>Sie haben für diesen Kurs noch nicht teilgenommen. <b>Bitte beteiligen Sie sich!</b></h3><br><br>\n";
?>
	<button><a href="/mod/evaluation/view.php?id=<?php echo $id?>" style="font-size:125%;color:white;background-color:black;">Ich will mich jetzt beteiligen!</a></button>
	<button><a href="/course/view.php?id=<?php echo $courseid?>" style="font-size:125%;color:white;background-color:black;">Zurück zum Kurs</a></button>
<?php
}


elseif ( $daily_progress)
{	if ( !defined('EVALUATION_OWNER') )
	{	echo $goBack;
		print "<p style=\"color:red;font-weight:bold;align:center;\">".get_string('you_have_no_permission', 'evaluation')."</p>"; 
		print $OUTPUT->footer();exit;
	}

	$responses = get_string('completed_evaluations',"evaluation");
	echo '<h1 style="color:darkgreen;text-align:left;font-weight:bolder;">'.get_string("statistics")." ".$responses."</h1><br>\n";
	$completed_responses = evaluation_countCourseEvaluations( $evaluation ); //$evaluationstructure->count_completed_responses();
	if ( !$completed_responses )
	{	echo $OUTPUT->notification(get_string('no_respnses_yet', 'mod_evaluation')); echo $OUTPUT->footer(); flush(); exit; }
	
	//$results = evaluation_daily_progress( $evaluation, "ASC" );
	//global $DB;
	$query = "SELECT to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy') as \"".get_string('date')."\",
					COUNT(to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy')) AS \"$responses\"
					FROM {evaluation_completed} 
					WHERE evaluation=$evaluation->id 
					GROUP BY to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy')
					ORDER BY to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy') ASC;";
	$results = $DB->get_records_sql($query);
	$numresults = safeCount($results) ?:1;
	$average = round( $completed_responses/( $numresults -1 + (date("H")/24) ) );
	$results2 = $results;
	usort($results2, function($a, $b) 
	{	global $responses; 
		if ( $a->{$responses} == $b->{$responses} )
		{	return 0; }
		return ($a->{$responses} < $b->{$responses} ?-1 :1); 
	});
	$median  = $results2[round($numresults/2)]->{$responses};
	$modus   = $results2[0]->{$responses};
	$dayC = round(remaining_evaluation_days($evaluation)); //+round(date("H")/24,4) );
	//$dayC = current_evaluation_day($evaluation);
	$prognosis = "";
	$date = get_string('date');
	if ( $dayC >=1 )
	{	$prognosis = ". Prognose: ".'<span style="color:darkgreen;font-weight:bolder;">'
					.round($completed_responses+(($completed_responses/$numresults)*$dayC))."</span>";
		$days = ". Es verbleiben ".'<span style="color:darkgreen;font-weight:bolder;">'.$dayC."</span> Tage Laufzeit"; 
	}
	else
	{	$days = ""; }

	echo "Abgaben pro Tag: <b>Mittelwert</b>: ".$average." - <b>Median</b>: ".$median." - <b>Modus</b>: ".$modus.".<br>\n";
	echo "Abgaben erfolgten an <b>".$numresults." Tagen</b>$days.<br>\n";
	echo "<b>Summe $responses</b>: <b>".$completed_responses."</b>".$prognosis."\n";
	echo '<div style="width:89%;">';
	$data = $results2 = array(); $count = $replies = $Modus = $Median = 0;
	foreach ( $results as $result )
	{	$replies += $result->{$responses};
		$Modus = ( $result->{$responses} > $Modus ? $result->{$responses} :$Modus );
		$results2[] = $result->{$responses};
		sort($results2);
		$Median  = $results2[round(($count)/2)];
		$data['labels'][$count] = $result->{$date};
		$data['series'][$count] = $result->{$responses};
		$data['series_labels'][$count] = $result->{$responses}; 
		$data['average'][$count] = round($replies/($count+1));
		$data['median'][$count] = $Median;
		$data['modus'][$count] = $Modus;
		$count++;
	}
	// draw line chart
	$chart = new \core\chart_bar();
	$series = new \core\chart_series(format_string(get_string("submittedEvaluations", "evaluation")), $data['series']);
	$seriesAvg = new \core\chart_series(get_string("average", "evaluation"), $data["average"]);
	$seriesMed = new \core\chart_series('Median', $data["median"]);
	$seriesMod = new \core\chart_series('Modus', $data["modus"]);
	$seriesAvg->set_type(\core\chart_series::TYPE_LINE); // Set the series type to line chart.
	$seriesMed->set_type(\core\chart_series::TYPE_LINE); // Set the series type to line chart.
	$seriesMod->set_type(\core\chart_series::TYPE_LINE); // Set the series type to line chart.
	$seriesAvg->set_smooth(true); 
	$seriesMed->set_smooth(true); 
	$seriesMod->set_smooth(true); // Calling set_smooth() passing true as parameter, will display smooth lines.		
	
	$series->set_labels($data['series_labels']);
	$chart->add_series($series);
	$chart->add_series($seriesAvg);
	$chart->add_series($seriesMed);
	$chart->add_series($seriesMod);
	$chart->set_labels($data['labels']);
    echo $OUTPUT->render($chart);
	//echo '</div></div><div style="clear:both;">&nbsp;</div>';
	echo '</div>';
	echo "<br>".$goBack;	

	evaluation_footer();

	// load js to handle printing
	$printWidth = "100vw"; //100vw
	require_once("print.js.php");
	exit;

}


elseif ( $showCourses_of_studies )  // show list course_of_studies
{	list( $sg_filter, $courses_filter ) = get_evaluation_filters( $evaluation );
	echo '<h1 style="color:darkred;display:inline;text-align:left;font-weight:bolder;">'
			 .get_string("course_of_studies_selected","evaluation").'</h1>';
	echo "<br>";
	if ( empty($sg_filter) AND !empty($courses_filter) )
	{	$sg_filter = evaluation_get_course_of_studies_from_courseids($courses_filter); }
	if ( !empty($sg_filter) )
	{	$selected = $sg_filter; //explode("\n", $evaluation->filter_course_of_studies);
		sort($selected);
		print "\n<ol><b>\n";
		foreach ( $selected AS $studiengang )
		{	print "<li>" . $studiengang . "</li>\n"; }
		print "\n</b></ol>\n";
	}
	else
	{	print "<br><b>".get_string("all") ."</b>"; }
	echo '<hr><h1 style="color:darkred;text-align:left;font-weight:bolder;">'.
		 get_string("course_of_studies_list","evaluation"). " " .get_evaluation_semester($evaluation). "</h1><br>\n";
	$all_course_studies = evaluation_get_course_studies($evaluation, true);
	print "\n<ol><b>\n";
	foreach ( $all_course_studies AS $studiengang )
	{	print "<li>" . $studiengang->course_of_studies . "</li>\n"; }
	print "\n</b></ol>\n";
}
elseif ( $showCourses )  // show list courses
{	echo '<h1 style="color:darkred;display:inline;text-align:left;font-weight:bolder;">'
			 .get_string("courses_selected","evaluation").'</h1>';
	echo "<br>";
	if ( !empty($evaluation->filter_courses) )
	{	$selected = explode("\n", $evaluation->filter_courses);
		print "\n<ol>\n";
		foreach ( $selected AS $courseid )
		{	$query = "SELECT id, fullname, shortname
					FROM {course} AS course
					WHERE id = $courseid";
			$result = $DB->get_record_sql($query);  
			print "<li>" . $courseid . ' <a href="/course/view.php?id="'.$courseid.'"><b>'.$result->fullname ."</b></a></li>\n"; 
		}
		print "\n</ol>\n";
	}
	else
	{	print "<br><b>".get_string("all") ."</b>"; }
	/*echo '<hr><h1 style="color:darkred;text-align:left;font-weight:bolder;">'.
		 get_string("courses_list","evaluation"). " " .get_evaluation_semester($evaluation). "</h1><br>\n";
	$all_courses = evaluation_participating_courses($evaluation;
	print "\n<ol>\n";
	foreach ( $all_courses AS $courseid )
	{	$query = "SELECT id, fullname, shortname
					FROM {course} AS course
					WHERE id = $courseid";
			$result = $DB->get_record_sql($query);  
			print "<li>" . $courseid . ' <a href="/course/view.php?id="'.$courseid.'"><b>'.$result->fullname ."</b></a></li>\n"; 
	}
	print "\n</ol>\n";
	*/
}


else  // show form preview
{	echo '<h1 style="color:darkred;display:inline;text-align:left;font-weight:bolder;">'.get_string('preview').'</h1><br>';
	echo "<br>".get_string("course","evaluation") . ": <span style=\"font-size:12pt;font-weight:bold;\">Muster Kurs</span>";
	if ( $evaluation->teamteaching)
	{	print "<h3><b>".get_string('evaluate_teacher','evaluation',"Alice Salomon")."</b></h3>\n"; }
	$form = new mod_evaluation_complete_form(mod_evaluation_complete_form::MODE_PRINT, $evaluationstructure, 'evaluation_print_form');
	$form->display();
}
//echo $OUTPUT->continue_button($continueurl);
echo "<br>".$goBack;

// Finish the page.
//echo $OUTPUT->footer();
evaluation_footer();

// load js to handle printing
$printWidth = "116vw"; //100vw
require_once("print.js.php");

function evaluation_footer( $footer = "")
{	global $OUTPUT;
	if ( empty($footer)	)	
	{	$footer = $OUTPUT->footer(); }
	//print "</div>\n";
	print $footer;
}
