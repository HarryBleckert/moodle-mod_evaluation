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
 *
 * Purpose:
 * send mails to evaluation participants. Default: Students
 */

// primary location for this script: mod/evaluation/cli

define('CLI_SCRIPT', true);
$PHP_SELF = basename($_SERVER['PHP_SELF']);

$configFile = __DIR__ . DIRECTORY_SEPARATOR . '../../../config.php';
if (!is_file($configFile)) {
    $configFile = '../../../config.php';
    if (!is_file($configFile)) {
        print "ERROR: Script $PHP_SELF must be located in folder mod/evaluation/cli of Moodle instance.\nCurrent location is: " .
                __DIR__ . "\n\n";
        exit;
    }
}
require($configFile);
require_once($CFG->libdir . '/clilib.php');
// require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/evaluation/lib.php');
global $CFG, $DB, $USER;

// run as manually called non-moodle cron task
ev_cron(false, true, false, false);
exit;


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

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

if (!$options['evaluation']) {
    echo "error! Evaluation ID missing\n";
    echo $usage;
    exit;
}
if (!$options['role']) {
    echo "error! Role not provided\n";
    echo $usage;
    exit;
}

$cos = false;
if ($options['cos'] and $options['cos'] == intval($options['cos'])) {
    $cos = $options['cos'];
    echo "error! CoS functionality has not been implemented yet!\n\n";
    exit;
}

$role = $options['role'];
if (($role !== "student" and $role !== "teacher")) {
    {
        echo "error! Role is invalid\n";
        echo $usage;
        exit;
    }
}



$_SESSION['ev_cli'] = true;
// $test = ($CFG->dbname == 'moodle_production' ? !$options['send'] : true);
$test = !$options['send'];

$evaluationid = $options["evaluation"];
$evaluation = $DB->get_record_sql("SELECT * FROM {evaluation} WHERE id=" . $evaluationid);
if (!isset($evaluation->id)) {
    ev_show_reminders_log("ERROR: Evaluation with ID $evaluationid not found!");
    exit;
}
$verbose = $options['verbose'];

// uncomment this AFTER validating all settings in this script, mail message and mail header details suit your needs.
// echo $usage; "\n\nScript is currently blocked. You need to validate settings and uncomment this line of code before you can run it!\n";  exit;
// exit
ev_show_reminders_log("\n" . date("Ymd H:m:s") . "\nSend using script $PHP_SELF");
ev_send_reminders($evaluation,$role,false,$test,$verbose,true);
exit;




// cron for scheduled tasks. But works extremely slow and therefore disabled.
// maybe better use as a non-Moodle cron job, meanwhile call reminders from view.php
// for testing as non-cron task: commented all cron related lines...
function ev_cron_cli($cronjob = true,$test = false) {
    global $CFG, $DB;
    // mtrace('send_reminders cron is currently disabled in function ev_cron');
    // return true;
    // mtrace('Start processing send_reminders');

    setlocale(LC_ALL, 'de_DE');
    $yesterday = time() - (24 * 3600);
    $timenow = time();
    // $task = \core\task\manager::get_scheduled_task(mod_evaluation\task\cron_task::class);
    // $lastruntime = $task->get_last_run_time();
    // mtrace("Time now: ".date("d.m,Y H:i:s",$timenow). " - last runtime: "
    //        .date("d.m,Y H:i:s",$lastruntime));

    $evaluations = $DB->get_records_sql("SELECT * from {evaluation}");
    // only run in test mode
    // $test = (true AND $CFG->dbname != 'moodle_staging');
    $cli = false;
    $verbose = false;
    $noreplies = false;
    foreach ($evaluations AS $evaluation){
        if (!$evaluation->autoreminders){
            continue;
        }
        $is_open = evaluation_is_open($evaluation);
        if ( $is_open ) {
            $reminders = $evaluation->reminders;
            if (empty($reminders)){
                // mtrace("Evaluation '$evaluation->name': Sending reminders to teachers and students");
                ev_send_reminders($evaluation, "teacher", $noreplies, $test, $cli, $verbose, $cronjob);
                $evaluation = $DB->get_record_sql("SELECT * from {evaluation} where id=".$evaluation->id);
                ev_send_reminders($evaluation, "student", $noreplies, $test, $cli, $verbose, $cronjob);
                continue;
            }
            else {
                /*
                 * format:
                 * 04.06.2024:teachers,students
                 * */
                $tsent = $ssent = $tsentnr = $ssentnr = 0;
                $remindersA = explode("\n", $reminders);
                foreach ($remindersA AS $reminder ){
                    $items = explode(":",$reminder);
                    if ( empty($reminder) OR empty($items[0])) {
                        continue;
                    }
                    $timestamp = strtotime($items[0]);
                    // print "<hr>timestamp: $timestamp - Date: ".date("d.m.Y",$timestamp)."<hr>";
                    $roles = explode(",", $items[1]);
                    // print "- Role: ";
                    foreach ($roles as $role){
                        if (stristr($role," (NR)")){
                            $role = str_ireplace(" (NR)","",$role);
                        }
                        // print $role.", ";
                        if ($role == "teachers") {
                            $tsent = $timestamp;
                        } else if ($role == "students") {
                            $ssent = $timestamp;
                        }

                    }
                }
                $week = 86400 * 7;
                $days = remaining_evaluation_days($evaluation);
                // print "<hr>tsent: ".date("d.m.Y",$ssent)." - ssent: "
                //        .date("d.m.Y",$ssent)." - ".date("d.m.Y",time())."<hr>";

                if ($tsent AND ($tsent+(2*$week) < time())){
                    ev_send_reminders($evaluation, "teacher", false, $test, $cli, $verbose, $cronjob);
                }
                else if (($tsent+(1*$week)) < time()){
                    ev_send_reminders($evaluation, "teacher", true, $test, $cli, $verbose, $cronjob);
                }
                else if ($days<4 and ($tsent+(3*86400))< time()){
                    ev_send_reminders($evaluation, "teacher", false, $test, $cli, $verbose, $cronjob);
                }
                $evaluation = $DB->get_record_sql("SELECT * from {evaluation} where id=".$evaluation->id);
                if (($ssent+(2*$week)) < time()){
                    ev_send_reminders($evaluation, "student", false, $test, $cli, $verbose, $cronjob);
                }
                else if (($ssent+(1*$week)) < time()){
                    ev_send_reminders($evaluation, "student", true, $test, $cli, $verbose, $cronjob);
                }
                else if ($days<4 and ($ssent+(3*86400)) < time()){
                    ev_send_reminders($evaluation, "student", false, $test, $cli, $verbose, $cronjob);
                }
            }
        }
        unset($_SESSION["EvaluationsName"]);
        validate_evaluation_sessions($evaluation);
    }
    // mtrace('Completed processing send_reminders');
    return true;
}
