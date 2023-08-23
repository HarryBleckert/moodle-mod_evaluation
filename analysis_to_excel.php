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
 * prints an analysed excel-spreadsheet of the evaluation
 *
 * @copyright Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once("../../config.php");
require_once("lib.php");
require_once("$CFG->libdir/excellib.class.php");

$id = required_param('id', PARAM_INT); // Course module id.
$courseid = optional_param('courseid', false, PARAM_INT); // '0'
$course_of_studiesID = optional_param('course_of_studiesID', false, PARAM_INT);
$teacherid = optional_param('teacherid', false, PARAM_INT);
$course_of_studies = false;
$url = new moodle_url('/mod/evaluation/analysis_to_excel.php', array('id' => $id));


$PAGE->set_url($url);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
require_login($course, false, $cm);
$context = context_module::instance($cm->id);

$evaluation = $PAGE->activityrecord;
if ($courseid) {
    $url->param('courseid', $courseid);
	$course_of_studies = evaluation_get_course_of_studies($courseid,false);
}
if ($teacherid) {	$url->param('teacherid', $teacherid); }
if ( $course_of_studiesID )
{	$course_of_studies = evaluation_get_course_of_studies_from_evc( $course_of_studiesID, $evaluation ); 
	$url->param('course_of_studiesID', $course_of_studiesID);
}

// patch to allow teachers in sitewide evaluations to evaluate results regarding their specific courses
list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) = evaluation_check_Roles_and_Permissions( $courseid, $evaluation, $cm );
// 
$Teacher 	= evaluation_is_teacher( $evaluation, $_SESSION["myEvaluations"], $courseid );
$isPermitted || $Teacher || require_capability('mod/evaluation:viewreports', $context);

// logging
evaluation_trigger_module_analysedExported( $evaluation, $cm, $courseid );

// Buffering any output. This prevents some output before the excel-header will be send.
ob_start();
ob_end_clean();

// Get the questions (item-names).
if ( $courseid )
{	$evaluationstructure = new mod_evaluation_structure($evaluation, $cm, $courseid, null, 0, $teacherid, $course_of_studies, $course_of_studiesID); }
else
{	$evaluationstructure = new mod_evaluation_structure($evaluation, $cm, false, null, 0, $teacherid, $course_of_studies, $course_of_studiesID); }

if (!$items = $evaluationstructure->get_items(true)) {
    print_error('no_items_available_yet', 'evaluation', $cm->url);
}

$mygroupid = groups_get_activity_group($cm);
$CourseName_ = empty($CourseName) ?"" :"_". $CourseName;
// Creating a workbook.
$filename = "evaluation_" . clean_filename($cm->get_formatted_name() . $CourseName_ ) ." (Auswertung).xlsx";
$workbook = new MoodleExcelWorkbook($filename);

$itemsCounted = 0;
foreach ($items as $item) 
{    // export only rateable items
	if ( !in_array($item->typ, array("numeric","multichoice","multichoicerated") ) )
	{	continue; }
	$itemsCounted++;
}
$itemsText = safeCount( $items ) - $itemsCounted;

// Creating the worksheet.
error_reporting(0);
$worksheet1 = $workbook->add_worksheet();
error_reporting($CFG->debug);
$worksheet1->hide_gridlines();
$worksheet1->set_column(0, $itemsCounted, 30);
// $worksheet1->set_column(1, 1, 35);
// worksheet1->set_column(2, 2, 35);

// Creating the needed formats.
$xlsformats = new stdClass();
$xlsformats->head1 = $workbook->add_format(['bold' => 1, 'size' => 12]);
$xlsformats->head2 = $workbook->add_format(['align' => 'left', 'bold' => 1, 'bottum' => 2]);
$xlsformats->default = $workbook->add_format(['align' => 'left', 'v_align' => 'top']);
$xlsformats->value_bold = $workbook->add_format(['align' => 'left', 'bold' => 1, 'v_align' => 'top']);
$xlsformats->procent = $workbook->add_format(['align' => 'left', 'bold' => 1, 'v_align' => 'top', 'num_format' => '#,##0.00%']);

// Writing the table header.
$rowoffset1 = 0;
$worksheet1->write_string($rowoffset1, 0, $cm->get_formatted_name(), $xlsformats->head1);
// date of print
$rowoffset1 ++; $worksheet1->write_string($rowoffset1, 0, userdate(time()), $xlsformats->default);

// Abgabefrist
if ( $evaluation->timeopen)
{	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, get_string('evaluationopen', 'evaluation').": ", $xlsformats->value_bold);
	$worksheet1->write_string($rowoffset1, 1, date("d.m.Y",$evaluation->timeopen), $xlsformats->default);
}
if ( $evaluation->timeclose)
{	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, get_string('evaluationclose', 'evaluation').": ", $xlsformats->value_bold);
	$worksheet1->write_string($rowoffset1, 1, date("d.m.Y",$evaluation->timeclose), $xlsformats->default);
}


if ( $courseid && $CourseName )
{	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, "Kurs ID: ", $xlsformats->default);
	$worksheet1->write_string($rowoffset1, 1, $courseid, $xlsformats->default);
	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, $CourseName, $xlsformats->value_bold);
	if ( !$teacherid )
	{	$teacher = evaluation_get_user_field( $teacherid, 'fullname' ); 
		$rowoffset1 ++;
		$worksheet1->write_string($rowoffset1, 0, get_string("teacher","evaluation").": ", $xlsformats->default);
		$worksheet1->write_string($rowoffset1, 1, $teacher, $xlsformats->value_bold);
	}
}
else 
{	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, get_string('fulllistofcourses'), $xlsformats->value_bold);
	
	if ( $course_of_studies )
	{	$rowoffset1 ++;
		$worksheet1->write_string($rowoffset1, 0, get_string("course_of_studies","evaluation"). ": ", $xlsformats->default);
		$worksheet1->write_string($rowoffset1, 1, $course_of_studies, $xlsformats->value_bold);

	}
	else 
	{	$rowoffset1 ++;
		$worksheet1->write_string($rowoffset1, 0, get_string('fulllistofstudies','evaluation'), $xlsformats->value_bold);
	}
}

if ( $teacherid )
{	$teacher = evaluation_get_user_field( $teacherid, 'fullname' ); 
	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, get_string("teacher","evaluation"). ": ", $xlsformats->default);
	$worksheet1->write_string($rowoffset1, 1, $teacher, $xlsformats->value_bold);
}
else 
{	$rowoffset1 ++;
	$worksheet1->write_string($rowoffset1, 0, get_string('fulllistofteachers','evaluation'), $xlsformats->value_bold);
}



// Get the completeds.
$rowoffset1++;
$completedscount = $evaluationstructure->count_completed_responses($mygroupid);
// Write the count of completeds.
// Keep consistency and write count of completeds even when they are 0.
$rowoffset1++;
$worksheet1->write_string($rowoffset1,
    0,
    get_string('completed_evaluations', 'evaluation').': '.strval($completedscount),
    $xlsformats->head1);

$rowoffset1++;
$worksheet1->write_string($rowoffset1,
    0,
    "Ausgewertete ". get_string('questions', 'evaluation').': '. $itemsCounted,
    $xlsformats->head1);

$rowoffset1 += 2;
$worksheet1->write_string($rowoffset1, 0, get_string('item_label', 'evaluation'), $xlsformats->head1);
$worksheet1->write_string($rowoffset1, 1, get_string('question', 'evaluation'), $xlsformats->head1);
$worksheet1->write_string($rowoffset1, 2, get_string('responses', 'evaluation'), $xlsformats->head1);

$rowoffset1++;

foreach ($items as $item) {
    // export only rateable items
	if ( !in_array($item->typ, array("numeric","multichoice","multichoicerated") ) )
	{	continue; }
	// Get the class of item-typ.
    $itemobj = evaluation_get_item_class($item->typ);
    $rowoffset1 = $itemobj->excelprint_item($worksheet1,
        $rowoffset1,
        $xlsformats,
        $item,
        $mygroupid, 
		$courseid, $teacherid, $course_of_studies);
}

$workbook->close();
