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
 * prints the form to edit the evaluation items such moving, deleting and so on
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once('../../config.php');
require_once('lib.php');
require_once('edit_form.php');

evaluation_init_evaluation_session();
global $USER;
$id = required_param('id', PARAM_INT);

if (($formdata = data_submitted()) and !confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey');
}

$do_show = optional_param('do_show', 'edit', PARAM_ALPHA);
$switchitemrequired = optional_param('switchitemrequired', false, PARAM_INT);
$deleteitem = optional_param('deleteitem', false, PARAM_INT);

$current_tab = $do_show;

$url = new moodle_url('/mod/evaluation/edit.php', array('id' => $id, 'do_show' => $do_show));

list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');

$context = context_module::instance($cm->id);
require_login($course, false, $cm);
if ( !isset($_SESSION["privileged_users"][$USER->username])) {
    require_capability('mod/evaluation:edititems', $context);
}
$evaluation = $PAGE->activityrecord;
$evaluationstructure = new mod_evaluation_structure($evaluation, $cm);

if ($switchitemrequired) {
    require_sesskey();
    $items = $evaluationstructure->get_items();
    if (isset($items[$switchitemrequired])) {
        evaluation_switch_item_required($items[$switchitemrequired]);
    }
    redirect($url);
}

if ($deleteitem) {
    require_sesskey();
    $items = $evaluationstructure->get_items();
    if (isset($items[$deleteitem])) {
        evaluation_delete_item($deleteitem);
    }
    redirect($url);
}

// Process the create template form.
$cancreatetemplates =  isset($_SESSION["privileged_users"][$USER->username])
                        OR has_capability('mod/evaluation:createprivatetemplate', $context) ||
        has_capability('mod/evaluation:createpublictemplate', $context);
$create_template_form = new evaluation_edit_create_template_form(null, array('id' => $id));
if ($data = $create_template_form->get_data()) {
    // Check the capabilities to create templates.
    if (!$cancreatetemplates) {
        throw new moodle_exception('cannotsavetempl', 'evaluation', $url);
    }
    $ispublic = !empty($data->ispublic) ? 1 : 0;
    if (!evaluation_save_as_template($evaluation, $data->templatename, $ispublic)) {
        redirect($url, get_string('saving_failed', 'evaluation'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        redirect($url, get_string('template_saved', 'evaluation'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

//Get the evaluationitems
$lastposition = 0;
$evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id), 'position');
if (is_array($evaluationitems)) {
    $evaluationitems = array_values($evaluationitems);
    if (count($evaluationitems) > 0) {
        $lastitem = $evaluationitems[count($evaluationitems) - 1];
        $lastposition = $lastitem->position;
    } else {
        $lastposition = 0;
    }
}
$lastposition++;

//The use_template-form
$use_template_form = new evaluation_edit_use_template_form('use_templ.php', array('course' => $course, 'id' => $id));

//Print the page header.
$strevaluations = get_string('modulenameplural', 'evaluation');
$strevaluation = get_string('modulename', 'evaluation');

evHideSettings();
//$url = new moodle_url('/mod/evaluation/edit.php', array('id'=>$cm->id, 'do_show'=>$do_show )); 
//evSetPage( $url );

$PAGE->set_url('/mod/evaluation/edit.php', array('id' => $cm->id, 'do_show' => $do_show));
$PAGE->set_heading($course->fullname);
$PAGE->set_title($evaluation->name);

//Adding the javascript module for the items dragdrop.
if (count($evaluationitems) > 1) {
    if ($do_show == 'edit') {
        $PAGE->requires->strings_for_js(array(
                'pluginname',
                'move_item',
                'position',
        ), 'evaluation');
        $PAGE->requires->yui_module('moodle-mod_evaluation-dragdrop', 'M.mod_evaluation.init_dragdrop',
                array(array('cmid' => $cm->id)));
    }
}

echo $OUTPUT->header();
if (substr($CFG->release, 0, 1) < "4") {
    $icon = '<img src="pix/icon120.png" height="30" alt="' . $evaluation->name . '">';
    echo $OUTPUT->heading($icon . "&nbsp;" . format_string($evaluation->name));

    /// print the tabs
    require('tabs.php');
}
// Print the main part of the page.

if ($do_show == 'templates') {
    // Print the template-section.
    $use_template_form->display();

    if ($cancreatetemplates) {
        $deleteurl = new moodle_url('/mod/evaluation/delete_template.php', array('id' => $id));
        $create_template_form->display();
        echo '<p><a href="' . $deleteurl->out() . '">' .
                get_string('delete_templates', 'evaluation') .
                '</a></p>';
    } else {
        echo '&nbsp;';
    }

    if (isset($_SESSION["privileged_users"][$USER->username]) OR has_capability('mod/evaluation:edititems', $context)) {
        $urlparams = array('action' => 'exportfile', 'id' => $id);
        $exporturl = new moodle_url('/mod/evaluation/export.php', $urlparams);
        $importurl = new moodle_url('/mod/evaluation/import.php', array('id' => $id));
        echo '<p>
            <a href="' . $exporturl->out() . '">' . get_string('export_questions', 'evaluation') . '</a>/
            <a href="' . $importurl->out() . '">' . get_string('import_questions', 'evaluation') . '</a>
        </p>';
    }
}

if ($do_show == 'edit') {
    // Print the Item-Edit-section.

    $select = new single_select(new moodle_url('/mod/evaluation/edit_item.php',
            array('cmid' => $id, 'position' => $lastposition, 'sesskey' => sesskey())),
            'typ', evaluation_load_evaluation_items_options());
    $select->label = get_string('add_item', 'mod_evaluation');
    echo $OUTPUT->render($select);

    $form = new mod_evaluation_complete_form(mod_evaluation_complete_form::MODE_EDIT,
            $evaluationstructure, 'evaluation_edit_form');
    echo '<div id="evaluation_dragarea">'; // The container for the dragging area.
    $form->display();
    echo '</div>';
}

echo $OUTPUT->footer();
