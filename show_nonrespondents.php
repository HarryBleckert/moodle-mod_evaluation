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
 */

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/tablelib.php');

////////////////////////////////////////////////////////
//get the params
////////////////////////////////////////////////////////
$id = required_param('id', PARAM_INT);
$subject = optional_param('subject', '', PARAM_CLEANHTML);
$message = optional_param_array('message', '', PARAM_CLEANHTML);
$format = optional_param('format', FORMAT_MOODLE, PARAM_INT);
$messageuser = optional_param_array('messageuser', false, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$perpage = optional_param('perpage', EVALUATION_DEFAULT_PAGE_COUNT, PARAM_INT);  // how many per page
$showall = optional_param('showall', false, PARAM_INT);  // should we show all users
// $SESSION->evaluation->current_tab = $do_show;
$current_tab = 'nonrespondents';

////////////////////////////////////////////////////////
//get the objects
////////////////////////////////////////////////////////

if ($message) {
    $message = $message['text'];
}

list ($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
if (!$evaluation = $DB->get_record("evaluation", array("id" => $cm->instance))) {
    throw new moodle_exception('invalidcoursemodule');
}

//this page only can be shown on nonanonymous evaluations in courses
//we should never reach this page
if ($evaluation->anonymous != EVALUATION_ANONYMOUS_NO or $evaluation->course == SITEID) {
    throw new moodle_exception('error');
}

$url = new moodle_url('/mod/evaluation/show_nonrespondents.php', array('id' => $cm->id));

$PAGE->set_url($url);

$context = context_module::instance($cm->id);

//we need the coursecontext to allow sending of mass mails
$coursecontext = context_course::instance($course->id);

require_login($course, true, $cm);

if (($formdata = data_submitted()) and !confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey');
}

require_capability('mod/evaluation:viewreports', $context);

$canbulkmessaging = has_capability('moodle/course:bulkmessaging', $coursecontext);
if ($action == 'sendmessage' and $canbulkmessaging) {
    $shortname = format_string($course->shortname,
            true,
            array('context' => $coursecontext));
    $strevaluations = get_string("modulenameplural", "evaluation");

    $htmlmessage = "<body id=\"email\">";

    $link1 = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
    $link2 = $CFG->wwwroot . '/mod/evaluation/index.php?id=' . $course->id;
    $link3 = $CFG->wwwroot . '/mod/evaluation/view.php?id=' . $cm->id;

    $htmlmessage .= '<div class="navbar">' .
            '<a target="_blank" href="' . $link1 . '">' . $shortname . '</a> &raquo; ' .
            '<a target="_blank" href="' . $link2 . '">' . $strevaluations . '</a> &raquo; ' .
            '<a target="_blank" href="' . $link3 . '">' . format_string(ev_get_tr($evaluation->name), true) . '</a>' .
            '</div>';

    $htmlmessage .= $message;
    $htmlmessage .= '</body>';

    $good = 1;
    if (is_array($messageuser)) {
        foreach ($messageuser as $userid) {
            $senduser = $DB->get_record('user', array('id' => $userid));
            $eventdata = new \core\message\message();
            $eventdata->courseid = $course->id;
            $eventdata->name = 'message';
            $eventdata->component = 'mod_evaluation';
            $eventdata->userfrom = $USER;
            $eventdata->userto = $senduser;
            $eventdata->subject = $subject;
            $eventdata->fullmessage = html_to_text($htmlmessage);
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = $htmlmessage;
            $eventdata->smallmessage = '';
            $eventdata->courseid = $course->id;
            $eventdata->contexturl = $link3;
            $eventdata->contexturlname = ev_get_tr($evaluation->name);
            $good = $good && message_send($eventdata);
        }
        if (!empty($good)) {
            $msg = $OUTPUT->heading(get_string('messagedselectedusers'));
        } else {
            $msg = $OUTPUT->heading(get_string('messagedselectedusersfailed'));
        }
        redirect($url, $msg, 4);
        exit;
    }
}

////////////////////////////////////////////////////////
//get the responses of given user
////////////////////////////////////////////////////////

/// Print the page header
$PAGE->set_heading($course->fullname);
$PAGE->set_title(ev_get_tr($evaluation->name));
echo $OUTPUT->header();
$icon = '<img src="pix/icon120.png" height="30" alt="' . ev_get_tr($evaluation->name) . '">';
echo $OUTPUT->heading($icon . "&nbsp;" . format_string(ev_get_tr($evaluation->name)));

require('tabs.php');

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////
/// Print the users with no responses
////////////////////////////////////////////////////////
//get the effective groupmode of this course and module
if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
    $groupmode = $cm->groupmode;
} else {
    $groupmode = $course->groupmode;
}

$groupselect = groups_print_activity_menu($cm, $url->out(), true);
$mygroupid = groups_get_activity_group($cm);

// preparing the table for output
$baseurl = new moodle_url('/mod/evaluation/show_nonrespondents.php');
$baseurl->params(array('id' => $id, 'showall' => $showall));

$tablecolumns = array('userpic', 'fullname', 'status');
$tableheaders = array(get_string('userpic'), get_string('fullnameuser'), get_string('status'));

if ($canbulkmessaging) {
    $tablecolumns[] = 'select';

    // Build the select/deselect all control.
    $selectallid = 'selectall-non-respondents';
    $mastercheckbox = new \core\output\checkbox_toggleall('evaluation-non-respondents', true, [
            'id' => $selectallid,
            'name' => $selectallid,
            'value' => 1,
            'label' => get_string('select'),
        // Consistent label to prevent the select column from resizing.
            'selectall' => get_string('select'),
            'deselectall' => get_string('select'),
            'labelclasses' => 'm-0',
    ]);
    $tableheaders[] = $OUTPUT->render($mastercheckbox);
}

$table = new flexible_table('evaluation-shownonrespondents-' . $course->id);

$table->define_columns($tablecolumns);
$table->define_headers($tableheaders);
$table->define_baseurl($baseurl);

$table->sortable(true, 'lastname', SORT_DESC);
$table->set_attribute('cellspacing', '0');
$table->set_attribute('id', 'showentrytable');
$table->set_attribute('class', 'generaltable generalbox');
$table->set_control_variables(array(
        TABLE_VAR_SORT => 'ssort',
        TABLE_VAR_IFIRST => 'sifirst',
        TABLE_VAR_ILAST => 'silast',
        TABLE_VAR_PAGE => 'spage'
));

$table->no_sorting('select');
$table->no_sorting('status');

$table->setup();

if ($table->get_sql_sort()) {
    $sort = $table->get_sql_sort();
} else {
    $sort = '';
}

//get students in conjunction with groupmode
if ($groupmode > 0) {
    if ($mygroupid > 0) {
        $usedgroupid = $mygroupid;
    } else {
        $usedgroupid = false;
    }
} else {
    $usedgroupid = false;
}

$matchcount = evaluation_count_incomplete_users($cm, $usedgroupid);
$table->initialbars(false);

if ($showall) {
    $startpage = false;
    $pagecount = false;
} else {
    $table->pagesize($perpage, $matchcount);
    $startpage = $table->get_page_start();
    $pagecount = $table->get_page_size();
}

// Return students record including if they started or not the evaluation.
$students = evaluation_get_incomplete_users($cm, $usedgroupid, $sort, $startpage, $pagecount, true);
//####### viewreports-start
//print the list of students
echo $OUTPUT->heading(get_string('non_respondents_students', 'evaluation', $matchcount), 4);
echo isset($groupselect) ? $groupselect : '';
echo '<div class="clearer"></div>';

if (empty($students)) {
    echo $OUTPUT->notification(get_string('noexistingparticipants', 'enrol'));
} else {

    if ($canbulkmessaging) {
        echo '<form class="mform" action="show_nonrespondents.php" method="post" id="evaluation_sendmessageform">';
    }

    foreach ($students as $student) {
        //userpicture and link to the profilepage
        $profileurl = $CFG->wwwroot . '/user/view.php?id=' . $student->id . '&amp;course=' . $course->id;
        $profilelink = '<strong><a href="' . $profileurl . '">' . fullname($student) . '</a></strong>';
        $data = array($OUTPUT->user_picture($student, array('courseid' => $course->id)), $profilelink);

        if ($student->evaluationstarted) {
            $data[] = get_string('started', 'evaluation');
        } else {
            $data[] = get_string('not_started', 'evaluation');
        }

        //selections to bulk messaging
        if ($canbulkmessaging) {
            $checkbox = new \core\output\checkbox_toggleall('evaluation-non-respondents', false, [
                    'id' => 'messageuser-' . $student->id,
                    'name' => 'messageuser[]',
                    'classes' => 'mr-1',
                    'value' => $student->id,
                    'label' => get_string('includeuserinrecipientslist', 'mod_evaluation', fullname($student)),
                    'labelclasses' => 'accesshide',
            ]);
            $data[] = $OUTPUT->render($checkbox);
        }
        $table->add_data($data);
    }
    $table->print_html();

    $allurl = new moodle_url($baseurl);

    if ($showall) {
        $allurl->param('showall', 0);
        echo $OUTPUT->container(html_writer::link($allurl, get_string('showperpage', '', EVALUATION_DEFAULT_PAGE_COUNT)),
                array(), 'showall');

    } else if ($matchcount > 0 && $perpage < $matchcount) {
        $allurl->param('showall', 1);
        echo $OUTPUT->container(html_writer::link($allurl, get_string('showall', '', $matchcount)), array(), 'showall');
    }
    if ($canbulkmessaging) {
        echo '<fieldset class="clearfix">';
        echo '<legend class="ftoggler">' . get_string('send_message', 'evaluation') . '</legend>';
        echo '<div>';
        echo '<label for="evaluation_subject">' . get_string('subject', 'evaluation') . '&nbsp;</label>';
        echo '<input type="text" id="evaluation_subject" size="50" maxlength="255" name="subject" value="' . s($subject) . '" />';
        echo '</div>';
        echo $OUTPUT->print_textarea('message', 'edit-message', $message, 15, 25);
        print_string('formathtml');
        echo '<input type="hidden" name="format" value="' . FORMAT_HTML . '" />';
        echo '<br /><div class="buttons">';
        echo '<input type="submit" name="send_message" value="' . get_string('send', 'evaluation') .
                '" class="btn btn-secondary" />';
        echo '</div>';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
        echo '<input type="hidden" name="action" value="sendmessage" />';
        echo '<input type="hidden" name="id" value="' . $id . '" />';
        echo '</fieldset>';
        echo '</form>';
    }
}

/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();

