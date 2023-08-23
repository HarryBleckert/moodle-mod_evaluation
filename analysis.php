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
 * shows an analysed view of evaluation
 *
 * @copyright Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once("../../config.php");
require_once("lib.php");

$current_tab = 'analysis';

$id = required_param('id', PARAM_INT);  // Course module id.

$url = new moodle_url('/mod/evaluation/analysis.php', array('id'=>$id));
$PAGE->set_url($url);

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
require_course_login($course, true, $cm);

$evaluation = $PAGE->activityrecord;
$evaluationstructure = new mod_evaluation_structure($evaluation, $cm);
$context = context_module::instance($cm->id);
$courseid = $evaluation->course;

list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation) = evaluation_check_Roles_and_Permissions( $courseid, $evaluation, $cm );

if ( ($SiteEvaluation AND !defined('EVALUATION_OWNER')) || !$evaluationstructure->can_view_analysis()) {
    print_error(get_string('you_have_no_permission', 'evaluation'));
}

/// Print the page header

$PAGE->set_heading($course->fullname);
$PAGE->set_title($evaluation->name);
echo $OUTPUT->header();

$icon = '<img src="pix/icon120.png" height="30" alt="'.$evaluation->name.'">';
echo $OUTPUT->heading( $icon. "&nbsp;" .format_string($evaluation->name) );


/// print the tabs
require('tabs.php');


//get the groupid
$mygroupid = groups_get_activity_group($cm, true);
groups_print_activity_menu($cm, $url);

// Button "Export to excel".
if (has_capability('mod/evaluation:viewreports', $context) && $evaluationstructure->get_items()) {
    echo $OUTPUT->container_start('form-buttons');
    $aurl = new moodle_url('/mod/evaluation/analysis_to_excel.php', ['sesskey' => sesskey(), 'id' => $id]);
    echo $OUTPUT->single_button($aurl, get_string('export_to_excel', 'evaluation'));
    echo $OUTPUT->container_end();
}

// Show the summary.
$summary = new mod_evaluation\output\summary($evaluationstructure, $mygroupid);
echo $OUTPUT->render_from_template('mod_evaluation/summary', $summary->export_for_template($OUTPUT));

// Get the items of the evaluation.
$items = $evaluationstructure->get_items(true);
//var_dump($items);exit;

$check_anonymously = true;
if ($mygroupid > 0 AND $evaluation->anonymous == EVALUATION_ANONYMOUS_YES) {
    $completedcount = $evaluationstructure->count_completed_responses($mygroupid);
    if ($completedcount < EVALUATION_MIN_ANONYMOUS_COUNT_IN_GROUP) {
        $check_anonymously = false;
    }
}


echo '<div>';
if ($check_anonymously) {
    // Print the items in an analysed form.
    foreach ($items as $item) {
        if ( $course->id == SITEID AND !defined('EVALUATION_OWNER') )
		{	if ( $item->typ !== "multichoice" || (stristr($item->name,"geschlecht") || stristr($item->name,"semester") ) ) 
			{	continue; }
		}
		$itemobj = evaluation_get_item_class($item->typ);
		$printnr = ($evaluation->autonumbering && $item->itemnr) ? ($item->itemnr . '.') : '';
		$itemobj->print_analysed($item, $printnr, $mygroupid);
    }
} else {
    echo $OUTPUT->heading_with_help(get_string('insufficient_responses_for_this_group', 'evaluation'),
                                    'insufficient_responses',
                                    'evaluation', '', '', 3);
}
echo '</div>';

echo $OUTPUT->footer();

