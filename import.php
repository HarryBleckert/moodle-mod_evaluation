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
 * prints the form to import items from xml-file
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_evaluation
 */

require_once("../../config.php");
require_once("lib.php");
require_once('import_form.php');

// get parameters
$id = required_param('id', PARAM_INT);
$choosefile = optional_param('choosefile', false, PARAM_PATH);
$action = optional_param('action', false, PARAM_ALPHA);

$url = new moodle_url('/mod/evaluation/import.php', array('id' => $id));
if ($choosefile !== false) {
    $url->param('choosefile', $choosefile);
}
if ($action !== false) {
    $url->param('action', $action);
}
$PAGE->set_url($url);

if (!$cm = get_coursemodule_from_id('evaluation', $id)) {
    print_error('invalidcoursemodule');
}

if (!$course = $DB->get_record("course", array("id" => $cm->course))) {
    print_error('coursemisconf');
}

if (!$evaluation = $DB->get_record("evaluation", array("id" => $cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

require_capability('mod/evaluation:edititems', $context);

$mform = new evaluation_import_form();
$newformdata = array('id' => $id,
        'deleteolditems' => '1',
        'action' => 'choosefile',
        'confirmadd' => '1',
        'do_show' => 'templates');
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect('edit.php?id=' . $id . '&do_show=templates');
}

// process if we are happy file is ok
if ($choosefile) {
    $xmlcontent = $mform->get_file_content('choosefile');

    if (!$xmldata = evaluation_load_xml_data($xmlcontent)) {
        print_error('cannotloadxml', 'evaluation', 'edit.php?id=' . $id);
    }

    $importerror = evaluation_import_loaded_data($xmldata, $evaluation->id);
    if ($importerror->stat == true) {
        $url = 'edit.php?id=' . $id . '&do_show=templates';
        redirect($url, get_string('import_successfully', 'evaluation'), 3);
        exit;
    }
}

/// Print the page header
$strevaluations = get_string("modulenameplural", "evaluation");
$strevaluation = get_string("modulename", "evaluation");

$PAGE->set_heading($course->fullname);
$PAGE->set_title($evaluation->name);
echo $OUTPUT->header();
$icon = '<img src="pix/icon120.png" height="30" alt="' . $evaluation->name . '">';
echo $OUTPUT->heading($icon . "&nbsp;" . format_string($evaluation->name));

/// print the tabs
$current_tab = 'templates';
require('tabs.php');

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
echo $OUTPUT->heading(get_string('import_questions', 'evaluation'), 3);

if (isset($importerror->msg) and is_array($importerror->msg)) {
    echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter');
    foreach ($importerror->msg as $msg) {
        echo $msg . '<br />';
    }
    echo $OUTPUT->box_end();
}

$mform->display();

echo $OUTPUT->footer();

function evaluation_load_xml_data($xmlcontent) {
    global $CFG;
    require_once($CFG->dirroot . '/lib/xmlize.php');

    if (!$xmlcontent = evaluation_check_xml_utf8($xmlcontent)) {
        return false;
    }

    $data = xmlize($xmlcontent, 1, 'UTF-8');

    if (intval($data['EVALUATION']['@']['VERSION']) != 200701) {
        return false;
    }
    $data = $data['EVALUATION']['#']['ITEMS'][0]['#']['ITEM'];
    return $data;
}

function evaluation_import_loaded_data(&$data, $evaluationid) {
    global $CFG, $DB;

    evaluation_load_evaluation_items();

    $deleteolditems = optional_param('deleteolditems', 0, PARAM_INT);

    $error = new stdClass();
    $error->stat = true;
    $error->msg = array();

    if (!is_array($data)) {
        $error->msg[] = get_string('data_is_not_an_array', 'evaluation');
        $error->stat = false;
        return $error;
    }

    if ($deleteolditems) {
        evaluation_delete_all_items($evaluationid);
        $position = 0;
    } else {
        //items will be add to the end of the existing items
        $position = $DB->count_records('evaluation_item', array('evaluation' => $evaluationid));
    }

    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($data as $item) {
        $position++;
        //check the typ
        $typ = $item['@']['TYPE'];

        //check oldtypes first
        switch ($typ) {
            case 'radio':
                $typ = 'multichoice';
                $oldtyp = 'radio';
                break;
            case 'dropdown':
                $typ = 'multichoice';
                $oldtyp = 'dropdown';
                break;
            case 'check':
                $typ = 'multichoice';
                $oldtyp = 'check';
                break;
            case 'radiorated':
                $typ = 'multichoicerated';
                $oldtyp = 'radiorated';
                break;
            case 'dropdownrated':
                $typ = 'multichoicerated';
                $oldtyp = 'dropdownrated';
                break;
            default:
                $oldtyp = $typ;
        }

        $itemclass = 'evaluation_item_' . $typ;
        if ($typ != 'pagebreak' and !class_exists($itemclass)) {
            $error->stat = false;
            $error->msg[] = 'type (' . $typ . ') not found';
            continue;
        }
        $itemobj = new $itemclass();

        $newitem = new stdClass();
        $newitem->evaluation = $evaluationid;
        $newitem->template = 0;
        $newitem->typ = $typ;
        $newitem->name = trim($item['#']['ITEMTEXT'][0]['#']);
        $newitem->label = trim($item['#']['ITEMLABEL'][0]['#']);
        if ($typ === 'captcha' || $typ === 'label') {
            $newitem->label = '';
            $newitem->name = '';
        }
        $newitem->options = trim($item['#']['OPTIONS'][0]['#']);
        $newitem->presentation = trim($item['#']['PRESENTATION'][0]['#']);
        //check old types of radio, check, and so on
        switch ($oldtyp) {
            case 'radio':
                $newitem->presentation = 'r>>>>>' . $newitem->presentation;
                break;
            case 'dropdown':
                $newitem->presentation = 'd>>>>>' . $newitem->presentation;
                break;
            case 'check':
                $newitem->presentation = 'c>>>>>' . $newitem->presentation;
                break;
            case 'radiorated':
                $newitem->presentation = 'r>>>>>' . $newitem->presentation;
                break;
            case 'dropdownrated':
                $newitem->presentation = 'd>>>>>' . $newitem->presentation;
                break;
        }

        if (isset($item['#']['DEPENDITEM'][0]['#'])) {
            $newitem->dependitem = intval($item['#']['DEPENDITEM'][0]['#']);
        } else {
            $newitem->dependitem = 0;
        }
        if (isset($item['#']['DEPENDVALUE'][0]['#'])) {
            $newitem->dependvalue = trim($item['#']['DEPENDVALUE'][0]['#']);
        } else {
            $newitem->dependvalue = '';
        }
        $olditemid = intval($item['#']['ITEMID'][0]['#']);

        if ($typ != 'pagebreak') {
            $newitem->hasvalue = $itemobj->get_hasvalue();
        } else {
            $newitem->hasvalue = 0;
        }
        $newitem->required = intval($item['@']['REQUIRED']);
        $newitem->position = $position;
        $newid = $DB->insert_record('evaluation_item', $newitem);

        $itembackup[$olditemid] = $newid;
        if ($newitem->dependitem) {
            $dependitemsmap[$newid] = $newitem->dependitem;
        }

    }
    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('evaluation_item', array('id' => $key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('evaluation_item', $newitem);
    }

    return $error;
}

function evaluation_check_xml_utf8($text) {
    //find the encoding
    $searchpattern = '/^\<\?xml.+(encoding=\"([a-z0-9-]*)\").+\?\>/is';

    if (!preg_match($searchpattern, $text, $match)) {
        return false; //no xml-file
    }

    //$match[0] = \<\? xml ... \?\> (without \)
    //$match[1] = encoding="...."
    //$match[2] = ISO-8859-1 or so on
    if (isset($match[0]) and !isset($match[1])) { //no encoding given. we assume utf-8
        return $text;
    }

    //encoding is given in $match[2]
    if (isset($match[0]) and isset($match[1]) and isset($match[2])) {
        $enc = $match[2];
        return core_text::convert($text, $enc);
    }
}
