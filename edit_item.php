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
 * prints the form to edit a dedicated item
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once("../../config.php");
require_once("lib.php");

evaluation_init_evaluation_session();

$itemid = optional_param('id', false, PARAM_INT);
if (!$itemid) {
    $cmid = required_param('cmid', PARAM_INT);
    $typ = required_param('typ', PARAM_ALPHA);
}

if ($itemid) {
    $item = $DB->get_record('evaluation_item', array('id' => $itemid), '*', MUST_EXIST);
    list($course, $cm) = get_course_and_cm_from_instance($item->evaluation, 'evaluation');
    $url = new moodle_url('/mod/evaluation/edit_item.php', array('id' => $itemid));
    $typ = $item->typ;
} else {
    $item = null;
    list($course, $cm) = get_course_and_cm_from_cmid($cmid, 'evaluation');
    $url = new moodle_url('/mod/evaluation/edit_item.php', array('cmid' => $cm->id, 'typ' => $typ));
    $item = (object) ['id' => null, 'position' => -1, 'typ' => $typ, 'options' => ''];
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/evaluation:edititems', $context);
$evaluation = $PAGE->activityrecord;

$editurl = new moodle_url('/mod/evaluation/edit.php', array('id' => $cm->id));

$PAGE->set_url($url);

// If the typ is pagebreak so the item will be saved directly.
if (!$item->id && $typ === 'pagebreak') {
    require_sesskey();
    evaluation_create_pagebreak($evaluation->id);
    redirect($editurl->out(false));
    exit;
}

//get the existing item or create it
// $formdata->itemid = isset($formdata->itemid) ? $formdata->itemid : NULL;
if (!$typ || !file_exists($CFG->dirroot . '/mod/evaluation/item/' . $typ . '/lib.php')) {
    throw new moodle_exception('typemissing', 'evaluation', $editurl->out(false));
}

require_once($CFG->dirroot . '/mod/evaluation/item/' . $typ . '/lib.php');

$itemobj = evaluation_get_item_class($typ);

$itemobj->build_editform($item, $evaluation, $cm);

if ($itemobj->is_cancelled()) {
    redirect($editurl);
    exit;
}
if ($itemobj->get_data()) {
    if ($item = $itemobj->save_item()) {
        evaluation_move_item($item, $item->position);
        redirect($editurl);
    }
}

////////////////////////////////////////////////////////////////////////////////////
/// Print the page header
$strevaluations = get_string("modulenameplural", "evaluation");
$strevaluation = get_string("modulename", "evaluation");

navigation_node::override_active_url(new moodle_url('/mod/evaluation/edit.php',
        array('id' => $cm->id, 'do_show' => 'edit')));
if ($item->id) {
    $PAGE->navbar->add(get_string('edit_item', 'evaluation'));
} else {
    $PAGE->navbar->add(get_string('add_item', 'evaluation'));
}
$ev_name = ev_get_tr($evaluation->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_title($ev_name);
echo $OUTPUT->header();

// Print the main part of the page.
echo $OUTPUT->heading(format_string($ev_name));

/// print the tabs
$current_tab = 'edit';
$id = $cm->id;
require('tabs.php');

//print errormsg
if (isset($error)) {
    echo $error;
}
$itemobj->show_editform();

/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();
