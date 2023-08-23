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
 * print the form to map courses for global evaluations
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once(__DIR__ . "/../../config.php");
require_once($CFG->dirroot . "/mod/evaluation/lib.php");
require_once("$CFG->libdir/tablelib.php");

$id = required_param('id', PARAM_INT); // Course Module ID.

$url = new moodle_url('/mod/evaluation/mapcourse.php', array('id'=>$id));
$PAGE->set_url($url);

$current_tab = 'mapcourse';

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
require_login($course, true, $cm);
$evaluation = $PAGE->activityrecord;

$context = context_module::instance($cm->id);
require_capability('mod/evaluation:mapcourse', $context);

$coursemap = array_keys(evaluation_get_courses_from_sitecourse_map($evaluation->id));
$form = new mod_evaluation_course_map_form();
$form->set_data(array('id' => $cm->id, 'mappedcourses' => $coursemap));
$mainurl = new moodle_url('/mod/evaluation/view.php', ['id' => $id]);
if ($form->is_cancelled()) {
    redirect($mainurl);
} else if ($data = $form->get_data()) {
    evaluation_update_sitecourse_map($evaluation, $data->mappedcourses);
    redirect($mainurl, get_string('mappingchanged', 'evaluation'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Print the page header.
$strevaluations = get_string("modulenameplural", "evaluation");
$strevaluation  = get_string("modulename", "evaluation");

$PAGE->set_heading($course->fullname);
$PAGE->set_title($evaluation->name);
echo $OUTPUT->header();

if ( substr( $CFG->release,0,1) < "4")
{	$icon = '<img src="pix/icon120.png" height="30" alt="'.$evaluation->name.'">';
	echo $OUTPUT->heading( $icon. "&nbsp;" .format_string($evaluation->name) );
	require('tabs.php');
}
echo $OUTPUT->box(get_string('mapcourseinfo', 'evaluation'));

$form->display();

echo $OUTPUT->footer();
