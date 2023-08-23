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
 * CLI script to mail reminder to all evaluation participants
 *
 *
 * @package     set-studiengang.php
 * @subpackage  cli
 * @copyright   2021 Harry@Bleckert.com for ASH Berlin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

Purpose:
send mails to evaluation participants. Default: Students

 */

// primary location for this script: mod/evaluation/cli


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

if ( empty($USER) OR !isset($USER->username) )
{ $USER = core_user::get_user(30421); }

setlocale(LC_ALL, 'de_DE');

// usage need be updated to fit!
$usage = "
This Moodle Cli - Script \"$PHP_SELF\" sends participation reminders to all participants of evaluation defined by parameter evaluation-id

Usage:
    # php $PHP_SELF --evaluation=<ID>
    # php $PHP_SELF [--help|-h]

Options:
    -h --help                   Print this help.
    -f --evaluation=<ID>  		Evaluation ID number. Example: 380
	-s --send					Send all Reminders (No Test))
	-r --role					Send only to role (student and teacher supported).
	-c --cos					Only selected course of studies by course_categories id.
	-v --verbose                Enable Moodle Debug Mode.
Examples:
    # php $PHP_SELF -f=39300 -r=student
    Tests participation reminders to all students of evaluation with id 39300
 
    # php $PHP_SELF -f=39300 -r=teacher
    Tests course overview for all teachers in courses of evaluation with id 39300

	# php $PHP_SELF -f=39300 -r=teacher -s
    sends participation reminders to all teachers of evaluation with id 39300
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'evaluation' => false,
	'cos' => false,
	'send' => false,
	'role' => false,
	'verbose' => false,	
], [
    'h' => 'help',
    'f' => 'evaluation',
	'c' => 'cos',
	's' => 'send',
	'r' => 'role',
	'v' => 'verbose',
]);
$DB->set_debug(false);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help'] )
{   cli_writeln($usage);
    exit(2);
}


if ($options['verbose']) {	$DB->set_debug(true); }
if (!$options['evaluation']) { echo "error! Evaluation ID missing\n"; echo $usage; exit;}
if (!$options['role']) { echo "error! Role not provided\n"; echo $usage; exit;}

$cos = false;
if ( $options['cos'] AND $options['cos'] == intval($options['cos']) ) {
	$cos = $options['cos'];
	echo "error! CoS functionality has not been implemented yet!\n\n"; exit;
}

$role = $options['role'];
if ( ($role !== "student" AND $role !== "teacher" ) )
{	{ echo "error! Role is invalid\n"; echo $usage; exit;} }

$test = ($CFG->dbname  == 'moodle_production' ?!$options['send'] :true );

$evaluationid = $options["evaluation"];
$evaluation = $DB->get_record_sql( "SELECT * FROM {evaluation} WHERE id=".$evaluationid);
if ( !isset($evaluation->id) )
{ show_log( "ERROR: Evaluation with ID $evaluationid not found!"); exit; }

show_log("\n".date("Ymd H:m:s")."\n$PHP_SELF: Sending reminders to all participants with role $role in evaluation $evaluation->name (ID: $evaluationid)");

if ( $test )
{	show_log("Test Mode"); }
else
{	

	// uncomment this AFTER validating all settings in this script, mail message and mail header details suit your needs.
	//echo $usage; "\n\nScript is currently blocked. You need to validate settings and uncomment this line of code before you can run it!\n";  exit;

}

//set_time_limit(44000);
$start=time();	

//get all participating students/teachers
$evaluation_users = get_evaluation_participants( $evaluation, false, false, ($role=="teacher"), ($role=="student") ); 
$minResults = evaluation_min_results($evaluation);
$remaining_evaluation_days = round( remaining_evaluation_days($evaluation),0);
$current_evaluation_day = round( current_evaluation_day($evaluation),0);
$total_evaluation_days = total_evaluation_days($evaluation);
$lastEvaluationDay = date("d.m.Y",$evaluation->timeclose);
$minResultsTeacher = $minResults+2;
$cmid = get_evaluation_cmid_from_id($evaluation);
$evUrl = "https://moodle.ash-berlin.eu/mod/evaluation/view.php?id=" .$cmid;

//$subject = '=?UTF-8?B?' . base64_encode($evaluation->name) . '?=';
$subject = '=?UTF-8?Q?' . quoted_printable_encode($evaluation->name) . '?=';
$cntStudents = $cntTeachers = 0;
$cnt = 1;
foreach ( $evaluation_users as $key => $evaluation_user )
{	//if ( $cnt<280) { $cnt++; continue; }   // set start counter
	//print print_r($key)."<hr>"; print print_r($evaluation_user);exit;
	$username = $evaluation_user["username"];
	$fullname = $evaluation_user["fullname"];
	//$fullname = $evaluation_user["firstname"] . " " . $evaluation_user["lastname"];
	$email = $evaluation_user["email"];
	$userid = $evaluation_user["id"];
	//$role = $evaluation_user["role"];
	$to = $evaluation_user["firstname"] . " " . $evaluation_user["lastname"] . " <$email>";
	$sender = "ASH Berlin (Qualitätsmanagement) <khayat@ash-berlin.eu>";
	$headers = array( "From" => $sender, "Return-Path" => $sender, "Reply-To" => $sender, "MIME-Version" => "1.0",
					  "Content-type" => "text/html;charset=UTF-8", "Content-Transfer-Encoding" => "quoted-printable" );
	$start2=time();		
	// get student courses to evaluate
	$USER = core_user::get_user($userid);
	
	unset( $_SESSION["possible_evaluations"] );
	//$teamteaching = $evaluation->teamteaching;
	$myEvaluations = get_evaluation_participants($evaluation, $userid ); 
	//$evaluation->teamteaching = $teamteaching;
	if ( empty($myEvaluations) )
	{	show_log( "$cnt. $fullname - $username - $email - ID: $userid - No courses in Evaluation!! - "
					."Teilnehmende Kurse: " .count(evaluation_is_user_enrolled($evaluation, $userid )) );
		continue; 
	}
	
	if ( empty($email) OR strtolower($email) == "unknown" OR !strstr($email, "@") OR stristr( $email, "unknown@"))
	{	show_log( "$cnt. $fullname - $username - $email - ID: $userid - Can't send mail to unknown@" );
		continue; 
	}
	if ( $role == "student" || $role == "participants" )
	{	$myCourses = show_user_evaluation_courses( $evaluation, $myEvaluations, $cmid, true, false ); }
	else
	{	$myCourses = show_user_evaluation_courses( $evaluation, $myEvaluations, $cmid, true, true, true ); 
		//$myCourses .= "<p><b>Ergebnisse für alle evaluierten Dozent_innen Ihrer Kurse:</b></p>\n";
		//$myCourses .= show_user_evaluation_courses( $evaluation, $myEvaluations, $cmid, true, false, false );	
	}
	// if filter for course category. ToDo....
	if ( $cos ) {
		
	}
	
	
	$testMsg = "";
	
	if ( false AND $cnt <2 )
	{	show_log( "time used get_participants: ".date("i:s",time()-$start) . " - get_participant_courses: " . date("i:s",time()-$start2) ); }

	if ( $test)
	{	if ($role == "student" || $role == "participants")  
		{	$testMsg = "<p>Dies ist ein Entwurf für die Mail an die Studierenden, deren Kurse an der Evaluation teilnehmen.</p><hr>"; }
		else {	$testMsg = "<p>Dies ist ein Entwurf für die Mail an die Lehrenden, deren Kurse an der Evaluation teilnehmen.</p><hr>"; }
		$to = "Harry Bleckert <Harry@Bleckert.com>";
		$fullname = "Harry Bleckert";
		// $to = "Berthe Khayat <khayat@ash-berlin.eu>";
		// $fullname = "Berthe Khayat";
		//$to = "Anja Voss <voss@ash-berlin.eu>";
		//$fullname = "Anja Voss";
		if ( $cnt>1 ) { break; }
	}
	/*
wir möchten Sie daran erinnern,
	dass noch die Möglichkeit besteht, sich an der <a href="https://moodle.ash-berlin.eu/mod/evaluation/view.php?id=270154">laufenden
	  Lehrveranstaltungsevaluation</a> zu beteiligen!<br>
	Mit Ihren Antworten helfen Sie uns, die Lehre zu verbessern und Sie können ggf. noch im
	laufenden Semester in einen Austausch mit den Lehrenden treten.</p>
	
	
*/
	$reminder = ($remaining_evaluation_days <= 9 ?"<b>nur noch $remaining_evaluation_days Tage bis zum $lastEvaluationDay laufenden</b> " :"laufenden ");
	if ( $role == "student" || $role == "participants" )
	{	//$user = core_user::get_user($userid);
		if ( hasUserEvaluationCompleted( $evaluation, $userid ) )
		{ show_log( "$cnt. $fullname - $username - $userid - $email - COMPLETED ALL!!" ); $cnt++; continue; }
		$testStudent = true;
		$cntStudents++;
		$also = ( (evaluation_has_user_participated($evaluation, $userid ) OR remaining_evaluation_days($evaluation)>15 )?"" :"auch");
		$message = <<<HEREDOC
<html>
<head>
<title>$subject</title>
</head>
<body>
$testMsg<p>Guten Tag $fullname</p>
<p>Bitte beteiligen $also Sie sich an der $reminder
<a href="$evUrl"><b>$evaluation->name</b></a>.<br><br>
Die Befragung erfolgt anonym und dauert nur wenige Minuten pro Dozent_in.<br>
Für jeden bereits von Ihnen evaluierten Kurs können Sie selbst sofort die Auswertung einsehen, wenn mindestens $minResults Abgaben erfolgt sind.<br>
Ausgenommen sind aus Datenschutzgründen die persönlichen Angaben, sowie die Antworten auf die offenen Fragen.
</p>
<p><b>Mit Ihrer Teilnahme tragen Sie dazu bei die Lehre zu verbessern!</b></p>
<p>Hier eine Übersicht Ihrer Kurse, die an der 
<a href="$evUrl"><b>$evaluation->name</b></a> teilnehmen:</p>
$myCourses
<p style="margin-bottom: 0cm">Mit besten Grüßen<br>
Berthe Khayat und Harry Bleckert für das Evaluationsteam<hr>
<b>Alice Salomon Hochschule Berlin</b><br>
- University of Applied Sciences -<br>
Alice-Salomon-Platz 5, 12627 Berlin
	</p>
</body>
</html>
HEREDOC;
	}
	else   // role = teacher
	{	$testTeacher = true;
		$cntTeachers++;
		// $possible_evaluations = ev_get_participants($myEvaluations);
		// Bis zu $possible_evaluations Abgaben für Sie sind möglich.
		$onlyfew = "";
		$replies = evaluation_countCourseEvaluations( $evaluation, false, "teacher", $userid );	
		if ( $replies < 21 ) {
			if ( $replies < 1 ) {
				$onlyfew = "<b>Keine Ihrer ".$_SESSION["distinct_s"]." Studierenden hat bisher teilgenommen</b>.<br>";
			}
			else {
				$onlyfew = "<b>Bisher gibt es nur $replies Abgabe".($replies<2 ?"" :"n")
				." Ihrer ".$_SESSION["distinct_s"]." Studierenden</b>.<br>"; 
				// .($replies<2 ?"hat" :"haben")." bisher teilgenommen</b>. ";
			}
		}
		else {
			$onlyfew = "<b>Bisher gibt es $replies Abgaben Ihrer ".$_SESSION["distinct_s"]." Studierenden</b>.<br>"; 
		}
		
		$message = <<<HEREDOC
<html>
<head>
<title>$subject</title>
</head>
<body>
$testMsg<p>Guten Tag $fullname</p>
$onlyfew
Bitte motivieren Sie Ihre Studierenden an der $reminder Evaluation teilzunehmen!</b><br>
Optimal wäre es, wenn Sie die Teilnahme jeweils in Ihre Veranstaltungen integrieren, indem Sie dafür einen motivierenden Aufruf machen und den 
Studierenden während der Veranstaltung die wenigen Minuten Zeit zur Teilnahme geben!</p>
<p>Sofern für einen Ihrer Kurse mindestens $minResults Abgaben <b>für Sie</b> vorliegen, können Sie jeweils die Auswertung der für Sie gemachten Abgaben einsehen.<br>
Nur wenn mindestens $minResultsTeacher Abgaben für Sie gemacht wurden, können Sie auch selbst die Textantworten einsehen<br>
</p>
<p>Hier eine Übersicht Ihrer Kurse, die an der 
<a href="$evUrl"><b>$evaluation->name</b></a> teilnehmen:</p>
$myCourses
<p style="margin-bottom: 0cm">Mit besten Grüßen<br>
Berthe Khayat und Harry Bleckert für  das Evaluationsteam<hr>
<b>Alice Salomon Hochschule Berlin</b><br>
- University of Applied Sciences -<br>
Alice-Salomon-Platz 5, 12627 Berlin
	</p>
</body>
</html>
HEREDOC;
	}

	mail($to,$subject,quoted_printable_encode($message),$headers); //,"-r '$sender'");
	show_log( "$cnt. $fullname - $username - $email - ID: $userid" );	
	$cnt++;
}
$elapsed = time()-$start;
echo "";
if ( $role == "student")
{	show_log( "Sent reminder to $cntStudents students" ); }
else
{	show_log( "Sent reminder to $cntTeachers teachers" ); }
echo "\n";
show_log( "Total time elapsed : ".(round($elapsed/60,0))." minutes and ".($elapsed%60)  . " seconds. " .date("Ymd H:m:s") ); 

function show_log( $msg )
{	$logfile = "/var/log/moodle/evaluation_send_reminders.log";
	echo $msg . "\n";
	system( "echo \"$msg\">>$logfile" );
}