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
 * CLI script to set course-customfield_studiengang to Category Studiengang
 *
 *
 * @package     set-studiengang.php
 * @subpackage  cli
 * @copyright   2021 Harry@Bleckert.com for ASH Berlin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

Purpose:
set course customfield studiengang to name of parent categoy of studiengang 

Problem: Does not work from cli!!!
 */

// primary location for this script: Moodle_Root/admin/cli
// copy to all ASH Moodle instances using: sync_mdl_ASH_scripts.sh

define('CLI_SCRIPT', true);
$PHP_SELF = basename($_SERVER['PHP_SELF']);

$configFile = '../../../config.php';
if ( !is_file($configFile) )
{	print "ERROR: Script $PHP_SELF must be located in folder mod/evaluation/cli of Moodle instance.\nCurrent location is: ".__DIR__."\n\n"; exit; }

require($configFile);
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->dirroot.'/mod/evaluation/lib.php');
global $CFG, $DB, $USER;


// constant definitions

$usage = "
This Moodle Cli - Script \"$PHP_SELF\" sets course customfield 'studiengang' to name of parent categoy of studiengang for selected Semesters

Usage:
    # php $PHP_SELF --semester=<YYYY(1|2))>
    # php $PHP_SELF [--help|-h]

Options:
    -h --help                   Print this help.
    -s --semester=<YYYY(1|2))>  Course idnumber must start with Semester or Year. Example: 20211
	-v --verbose                Enable Moodle Debug Mode.
Examples:
    # php $PHP_SELF
    Sets name of Studiengang to every course in current semester
    
    # php $PHP_SELF -s=20211
    Sets name of Studiengang to every course in Semester 20211
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'semester' => null,
	'verbose' => false,	
], [
    'h' => 'help',
    's' => 'semester',
	'v' => 'verbose',
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

$DB->set_debug(false);
if ($options['verbose']) {	$DB->set_debug(true); }



if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}
//$month = date("n") ;
$month = (date("n")>3 AND date("n")<10) ?"1" :"2";
$semester = date("Y").$month;
if ($options['semester']) { $semester = $options["semester"]; }

// unlock customfield for editing
$field = $DB->get_record_sql( "select * from {customfield_field} where shortname='studiengang'");
$locked = strstr($field->configdata, '"locked":"1"' );
$invisible = strstr($field->configdata, '"visibility":"0"' );
if ( $locked )
{	$field->configdata = str_replace( '"locked":"1"', '"locked":"0"', $field->configdata );
	$DB->update_record("customfield_field", $field,true); 
}
if ( $invisible )
{	$field->configdata = str_replace( '"visibility":"0"', '"visibility":"1"', $field->configdata );
	$DB->update_record("customfield_field", $field,true); 
}

$courses = $DB->get_records_sql("SELECT * FROM {course} WHERE idnumber like '%$semester' ORDER BY id"); 
print "\n".date("d.m.Y H:i:s").": Starting script $PHP_SELF.\nNumber of courses with semester = $semester: ".count($courses)."\n";
$cnt = $updated = 0;
$started = time();
mb_internal_encoding('utf-8');
foreach ( $courses as $course )
{	$cnt++;
	$studiengang = evaluation_get_course_metadata($course->id,"studiengang");
	if ( empty($studiengang) )
	{	//echo print_r()."$studiengang\n";	
echo "Script now working properly: Meta: ".print_r(evaluation_get_course_metadata($course->id))."\n";
exit;
		$studiengang = evaluation_get_course_of_studies($course->id,true); 
		//echo print_r()."$studiengang\n";		
		$updated++;
		print "#".str_pad($course->id, 6, " ", STR_PAD_LEFT)." - $course->idnumber - ".str_pad(substr($course->fullname,0,55),55, " ").
			  (strlen($course->fullname)>56 ?"..." :"   ") . " - ".substr($studiengang,0,50)."\n"; 
		//if ( $updated >8 ) { break; }
    }
}
// reverse lock + visibilty of customfield for editing
if ( $locked AND strstr($field->configdata, '"locked":"0"' ) )
{	$field->configdata = str_replace( '"locked":"0"', '"locked":"1"', $field->configdata );
	$DB->update_record("customfield_field", $field, true); 
}
if ( $invisible AND strstr($field->configdata, '"visibility":"1"' ) )
{	$field->configdata = str_replace( '"visibility":"0"', '"visibility":"1"', $field->configdata );
	$DB->update_record("customfield_field", $field, true); 
}

// format statistics
$endtime = time();
$elapsed = $endtime-$started;
$updated = $updated ?"$updated courses were updated for customfield 'studiengang'.\n" :"";

// last message
print "\nProcessing started at ".date("H:i:s",$started).", completed at ".date("H:i:s",$endtime)
	 .". Time elapsed : ".(round($elapsed/60,0))." minutes and ".($elapsed%60)  . " seconds.\n"
	 .count($courses)." courses with Semester = $semester were scanned.\n"
	 .$updated;

exit;
