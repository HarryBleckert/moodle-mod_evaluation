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

$configFile = '../../../config.php';
if (!is_file($configFile)) {
    print "ERROR: Script $PHP_SELF must be located in folder mod/evaluation/cli of Moodle instance.\nCurrent location is: " .
            __DIR__ . "\n\n";
    exit;
}

require($configFile);
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/mod/evaluation/lib.php');
global $CFG, $DB, $USER;


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

$test = ($CFG->dbname == 'moodle_production' ? !$options['send'] : true);

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
ev_show_reminders_log("\n" . date("Ymd H:m:s") . "\nSend using script '$PHP_SELF'");
$_SESSION['ev_cli'] = true;
ev_send_reminders($evaluation,$role,$test,$verbose,true);
exit;
