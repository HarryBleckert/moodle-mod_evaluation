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
 * CHANGE PERMISSIONS FOR SET OF ROLES
 *
 * @author Harry.Bleckert@ASH-Berlin.eu for ASH Berlin
 */
// current location: mod/evaluation/cli
require_once( "../../../config.php"); //__DIR__ .
//require_once($CFG->dirroot ."lib/lib.php");
defined('MOODLE_INTERNAL') || die();

$validation = optional_param('validation', 0, PARAM_INT);
$contextid  = 1; // site wide context required_param('contextid', PARAM_INT);
$roleid     = optional_param('roleid', 0, PARAM_INT);
$capability = optional_param('capability', false, PARAM_CAPABILITY);
$confirm    = optional_param('confirm', 0, PARAM_BOOL);
$prevent    = optional_param('prevent', 0, PARAM_BOOL);
$allow      = optional_param('allow', 0, PARAM_BOOL);
$unprohibit = optional_param('unprohibit', 0, PARAM_BOOL);
$prohibit   = optional_param('prohibit', 0, PARAM_BOOL);
$returnurl  = optional_param('returnurl', null, PARAM_LOCALURL);

list($context, $course, $cm) = get_context_info_array($contextid);
//$context = context_module::instance($cm->id);

$course = get_course(SITEID);
// Security first.
//require_login();
//require_course_login($course);
require_login($course, false, $cm);
//var_dump($course);exit;

$urlparams = array();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$pageurl = new moodle_url('/mod/evaluation/cli/ASH_change_permissions.php');
$PAGE->set_url($pageurl, $urlparams );
$PAGE->set_title($course->fullname);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo "<h1>ASH global Permissions Change</h1>\n";

if ( !is_siteadmin() )
{	echo get_string("error");echo $OUTPUT->footer();exit;}
$cap = "moodle/role:review";
//load_all_capabilities();
//require_capability($cap, $context) || print_error( "$cap: Not allowed!");

/*
 id |               name               |       shortname        |   archetype
----+----------------------------------+------------------------+----------------
  1 | Manager_in                       | manager                | manager
  2 | Kursersteller_in                 | coursecreator          | coursecreator
  3 | Dozent_in                        | editingteacher         | editingteacher
  4 | Dozent_in ohne Bearbeitungsrecht | teacher                | teacher
  5 | Student_in                       | student                | student
  6 | Gast                             | guest                  | guest
  7 |                                  | user                   | user
  8 |                                  | frontpage              | frontpage
  9 | Tutor_in                         | tutor                  | editingteacher
 10 | Moduleverantwortliche_r          | moduleverantwortlicher | editingteacher
 12 | Kursverantwortliche_r            | kursverantwortlicher   | editingteacher
 14 | Plagiatsprüfer_in                | plagchecker            | editingteacher
 15 | Betreuer_in                      | supervisor             | student
 16 | Datenschutzbeauftragte_r         | datenschutzbeauftragte |

role_change_permission($roleid, $context, $capability->name, CAP_PROHIBIT); 
role_change_permission($roleid, $context, $capability->name, CAP_ALLOW); 
CAP_ALLOW     1
CAP_PREVENT  -1
CAP_PROHIBIT -1000
CAP_INHERIT  0

permissions to change:
1,2,3,4,9,10,12,14
Capabilities to change:
report/participation:view
report/progress:view
report/outline:view
report/outline:viewuserreport

ToDo: remove all records with contextid >1 for same caps (Austausch- und Info Kurse)
*/


$roleids = array(1,2,3,4,9,10,12,14);
$caps = array("report/participation:view", "report/progress:view", "report/outline:view", "report/outline:viewuserreport");
$action = CAP_PROHIBIT; //CAP_ALLOW; //CAP_PROHIBIT; CAP_INHERIT
echo "Current Settings to be applied<br>Roles:"; 
echo print_r($roleids);echo "<br>Capabilities:"; 
echo print_r($caps); 
echo "<br>Action: $action (CAP_PROHIBIT)<br>\n";

if ( !$validation )
{	echo "<br><hr><b style=\"color:red;\">Permission Changes can only proceed if you add '?validation=1' to URL</b><br>";
	echo $OUTPUT->footer();
	exit;
}

foreach ( $caps AS $cap )
{	echo "<b>Before</b>:<br>\n";
	show_cap_records( $cap );
	foreach ( $roleids AS $roleid )
	{ role_change_permission($roleid, $context, $cap, $action); }
	echo "<br><b>After</b>:<br>\n";
	show_cap_records( $cap );

}
echo $OUTPUT->footer();

// show current permission settings
function show_cap_records( $cap )
{	global $DB;
	$fields = "id,contextid,roleid,capability,permission,timemodified,modifierid";
	$sql = "SELECT $fields FROM {role_capabilities} WHERE contextid=1 AND capability=? ORDER by roleid";
	//echo "<hr>sql: $sql<br>";	
	$perms = $DB->get_records_sql($sql,array($cap));
	$fieldsA = explode(",", $fields);
	echo "<table>\n";
	$header = 1;
	foreach ( $perms AS $perm )
	{	echo "\n<tr>";
		if ( $header)
		{	foreach ( $fieldsA AS $field ) { echo "<td><b>$field</b></td>"; } 
			echo "</tr>\n<tr>"; $header = 0;
		}
		foreach ( $fieldsA AS $field )
		{	echo "<td style=\"text-align:right;\">".$perm->{$field}."</td>"; }		
		echo "\n</tr>";
	}
	echo "</table>\n";
}

function ASH_role_change_permission($roleid, $context, $cap, $action)
{	role_change_permission($roleid, $context, $cap, $action);
	return true;
	global $DB, $USER;
	$sql = "UPDATE {role_capabilities} SET permission=$action, timemodified=".time().", modifierid=$USER->id 
			WHERE roleid=$roleid AND contextid=$context->id AND capability='$cap'";
	echo "<hr>sql: $sql<hr>";	
	return $DB->execute($sql); //, array("capability" => $cap));
}