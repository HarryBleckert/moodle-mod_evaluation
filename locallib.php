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
 * Library of functions and constants for module evaluation
 * includes the main-part of evaluation-functions
 *
 * @package mod_evaluation
 * @copyright Andreas Grabs for mod_evaluation
 * @copyright by Harry.Bleckert@ASH-Berlin.eu for ASH Berlin
 * + forked from mod_feedback 12/2021
 *
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
To Do:
		add configuration settings for category tree of course_of_studies

*/

// ASH specific organisation of course of studies (Studiengänge) = Level 2
// Semester is level 3
define('COURSE_OF_STUDIES_PATH', 2);
//define('EVALUATION_DEBUG', TRUE );
define('EVALUATION_DEBUG', false);

function evaluation_debug($msg = false): bool {
    if (EVALUATION_DEBUG and is_siteadmin()) {
        if ($msg) {
            print "<hr>EVALUATION_DEBUG<br>\n$msg<br><hr>";
        }
        //if ( !defined('READ_ONLY_SESSION') )	{	define('READ_ONLY_SESSION', true); }
        return true;
    }
    return false;
}

function ev_get_plugin_version($component = "mod_evaluation") {
    list($plugintype, $pluginname) = core_component::normalize_component($component);
    $pluginpath = core_component::get_plugin_directory($plugintype, $pluginname);
    $plugin = new \stdClass();
    require $pluginpath.'/version.php';
    //return $plugin->version;
    return $plugin;
}

// Wrapper for PHP 8.x to catch uncountable parameters of safeCount()
function safeCount($value) {
    if (is_numeric($value)) {
        return $value;
    }
    if (is_countable($value)) {
        return count($value);
    }
    return 0;
}

// get_string for get_string('show_user', 'mod_evaluarion');
function ev_get_string($string, $param = null, $language = null) {
    $currentlang = current_language();
    if (!empty($language)) {
        // Temporarily force the target language
        force_current_language($language);
    }
    $plugin = 'evaluation';
    $trstring = get_string($string, $plugin, $param);

    if (!empty($language)) {
        // Restore the original language
        force_current_language($currentlang);

    }

    if (stristr($trstring, ']')) {
        return $string;
    }
    return $trstring;
}


function ev_get_tr($source_string, $args=new stdClass(), $source_lang='de',$field='', $target_lang = null) {
    global $CFG, $DB;
    if (!$target_lang) {
        $target_lang = current_language();
    }
    $target_string = $source_string;
    if (substr($target_lang,0,2) != 'en'){
        return $target_string;
    }
    if (substr($target_lang,0,2) == substr($source_lang,0,2)){
        if (is_object($args)) {
            foreach ($args as $key => $value) {
                if (!stristr($target_string, "{" . $key . "}")) {
                    continue;
                }
                $target_string = str_replace("{" . $key . "}", $value, $target_string);
            }
        }
        return $target_string;
    }

    if ( $translation = $DB->get_record_sql("SELECT * from {evaluation_translator} 
         WHERE source_string = '$source_string' AND target_lang='$target_lang'")){  // source_lang = '$source_lang' AND
        $target_string = $translation->target_string;
        if (is_object($args)) {
            foreach ($args as $key => $value) {
                if (!stristr($translation->target_string, "{" . $key . "}")) {
                    continue;
                }
                $target_string = str_replace("{" . $key . "}", $value, $target_string);
            }
        }
        return $target_string;
    }
    // handle special case of german evaluation name
    if (!empty($CFG->ash) AND $field == 'name' AND $source_lang == 'de'){
        $repl = "Evaluation der Lehrveranstaltungen";
        $evaluation_of_courses = ev_get_string('evaluation_of_courses');
        if (stristr($target_string, $repl ) AND $evaluation_of_courses !=="evaluation_of_courses"){
            $target_string = str_ireplace($repl, $evaluation_of_courses, $target_string);
        }
        $repl = "durch Studierende";
        $by_students = ev_get_string('by_students');
        if (stristr($target_string, $repl ) AND $by_students !=="by_students"){
            $target_string = str_ireplace($repl, "", $target_string);
        }
        $repl = " des ";
        $of = " " . ev_get_string('of_') ." ";
        if (stristr($target_string, $repl )){
            $target_string = str_ireplace($repl, $of, $target_string);
        }
        $repl = " für das ";
        $of = " " . ev_get_string('for_') ." ";
        if (stristr($target_string, $repl )){
            $target_string = str_ireplace($repl, $of, $target_string);
        }
        $repl = " sose ";
        $sose = " " . ev_get_string('sose_') ." ";
        if (stristr($target_string, $repl )){
            $target_string = str_ireplace($repl, $sose, $target_string);
        }
        $repl = " wise ";
        $wise = " " . ev_get_string('wise_') ." ";
        if (stristr($target_string, $repl )){
            $target_string = str_ireplace($repl, $wise, $target_string);
        }
        $repl = "Sommersemester";
        $sose = ev_get_string('sose_');
        if (stristr($target_string, $repl )){
            $target_string = str_ireplace($repl, $sose, $target_string);
        }
        $repl = "Wintersemester";
        $wise = ev_get_string('wise_');
        if (stristr($target_string, $repl )){
            $target_string = str_ireplace($repl, $wise, $target_string);
        }
    }
    if ( is_siteadmin()) {
        $str_trans = new stdClass();
        $str_trans->source_lang = $source_lang;
        $str_trans->target_lang = $target_lang;
        $str_trans->source_string = $source_string;
        $str_trans->target_string = $target_string;
        $str_trans->timemodified = time();
        $DB->insert_record("evaluation_translator", $str_trans);
    }
    return $target_string;
}


// Moodle return clode for addidional html settings
function evaluation_additional_html() {
    global $CFG, $USER;
    if (substr($CFG->release, 0, 1) > "3") {
        $LoggedInAs = empty($_SESSION["LoggedInAs"]) ? "" : '
					/*if ( document.getElementById("usernavigation").length > 0 ) 
					{	document.getElementById("usernavigation").style.display="none"; }*/
					if ( document.getElementsByClassName("primary-navigation").length > 0 ) 
					{	document.getElementsByClassName("primary-navigation")[0].style.display="none"; }
					if ( document.getElementsByClassName("fixed-top").length > 0 ) 
					{	var nav = document.getElementsByClassName("fixed-top"); for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; } }
					';

        $html = '<script>
				if ( document.getElementsByClassName("page-header-image").length > 0 ) 
        		{	document.getElementsByClassName("page-header-image")[0].style.display="none"; }
                if ( document.getElementsByClassName("page-header-headings").length > 0 ) 
        		{	document.getElementsByClassName("page-header-headings")[0].style.display="none"; }

				if ( document.getElementsByClassName("page-context-header").length > 0 ) 
				{	document.getElementsByClassName("page-context-header")[0].style.display="none"; }
				if ( document.getElementsByClassName("secondary-navigation").length > 0 ) 
				{	document.getElementsByClassName("secondary-navigation")[0].style.display="none"; }
				if ( document.getElementsByClassName("activity-description").length > 0 ) 
				{	document.getElementsByClassName("activity-description")[0].style.display="none"; }'
                . $LoggedInAs . '</script>';
    } else {
        set_user_preference("drawer-open-nav", false, $USER);
        $LoggedInAs = empty($_SESSION["LoggedInAs"]) ? "" : '
		if ( document.getElementsByClassName("usermenu").length > 0 ) 
		{	var nav = document.getElementsByClassName("usermenu");
			for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; };
		}
		';

        $html = '<style>.container-fluid.navbar-nav > div { display: none; }</style>
		<script>
		// document.getElementById("nav-drawer").style.display="none";
		if ( document.getElementsByClassName("nav").length > 0 ) 
		{	var nav = document.getElementsByClassName("nav"); for (var i = 0; i < nav.length; i++) { nav[i].style.display="none"; } }
		if ( document.getElementsByClassName("list-group").length > 0 ) 
		{	var nav = document.getElementsByClassName("list-group"); for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; } }
		if ( document.getElementsByClassName("fixed-top").length > 0 ) 
		{	var nav = document.getElementsByClassName("fixed-top");	for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; } }
		if ( document.getElementsByClassName("breadcrumb-item").length > 0 ) 
		{	var nav = document.getElementsByClassName("breadcrumb-item"); nav[0].style.display="none"; }
		if ( document.getElementById("page-footer").length > 0 ) 
		{	document.getElementById("page-footer").style.display="none"; }
		if ( document.getElementsByClassName("footnote").length > 0 ) 
		{	var nav = document.getElementsByClassName("footnote"); for (var i = 0; i < nav.length; i++) {	nav[i].style.display="none"; }; }
		// document.getElementById("page-navbar").style.display="block";
		// document.getElementByClassName("breadcrumb-item").style.display="block";
		' . $LoggedInAs . '
		</script>';
        /* obsolete
			document.getElementById("page-footer").style.display="none";
			document.getElementsByClassName("logininfo")[0].style.display="none";
		*/
        if (empty($_SESSION["LoggedInAs"]))  // need remove elements only if logged in as role
        {
            return "";
        }
    }
    return $html;
}

// Moodle hide classes that show settings menu items and activity title
function evHideSettings() {
    print evaluation_additional_html();
}

// set Page layout for Evaluation menu pages
function evSetPage($url, $url2 = false, $anker = false) {
    global $CFG, $PAGE, $OUTPUT, $id, $course, $evaluation, $courseid, $downloading;

    //$pagelayout = 'report';
    $pagelayout = empty($_SESSION["LoggedInAs"]) ? 'report' : 'popup';
    $PAGE->set_pagelayout($pagelayout);  // report	incourse	popup	base	standard
    //$PAGE->set_context(context_course::instance($course->id));
    //$PAGE->set_context(context_module::instance($cm->id));

    $evurl = new moodle_url('/mod/evaluation/');
    //navigation_node::override_active_url($evurl);

    $PAGE->navbar->ignore_active();
    $PAGE->navbar->ignore_active();

    /*doesn't work
	$previewnode = $PAGE->navigation->add(get_string("modulenameplural", "evaluation"),$evurl, navigation_node::TYPE_CONTAINER);
	$thingnode = $previewnode->add(ev_get_tr($evaluation->name), new moodle_url('/mod/evaluation/view.php', array('id'=>$id )));
	$thingnode->make_active();
	*/
    // add navbar /mod/evaluation/
    $PAGE->navbar->add(get_string("modulenameplural", "evaluation"), $evurl);

    // view page settings
    $evurl = new moodle_url('/mod/evaluation/view.php', array('id' => $id));
    $PAGE->navbar->add(ev_get_tr($evaluation->name), $evurl);

    // current page settings
    if ($url2) {
        $PAGE->navbar->add($anker, $url2);
    } // show settings cog for admin
    else if (is_siteadmin()) {
        $PAGE->force_settings_menu(false);
    }

    // add empty navbar for Moodle > 3.n
    if (substr($CFG->release, 0, 1) > "3") {
        $evurl = new moodle_url('/mod/evaluation/view.php', array('id' => $id));
        $PAGE->navbar->add("", $evurl);
    }

    $PAGE->set_url($url);
    $PAGE->set_title(ev_get_tr($evaluation->name));
    $PAGE->set_heading($course->fullname);
    if (!$downloading) {    // Print the page header
        echo $OUTPUT->header();
        // Moodle 4 Boost: hide classes that show settings menu items and activity title
        if (true or stristr($CFG->dataroot, 'dev')) {
            evHideSettings();
            //$CFG->additionalhtmlfooter = evaluation_additional_html();
        }
        print '<div id="LoginAs" class="LoginAs d-print-none"></div><span style="clear:both;"><br></span>';
    }
}

function evaluation_set_results($evaluation, $forceGlobal = false, $forceCourse = false, $forceUsers = false) {
    global $DB, $CFG;
    if (!defined('NO_OUTPUT_BUFFERING')) {
        define('NO_OUTPUT_BUFFERING', true);
    }
    ini_set("output_buffering", 600);
    @ob_flush();@ob_end_flush();@flush();@ob_start();

    // only siteadmin should call this
    if (isset($_SESSION['set_results_' . $evaluation->id]) or evaluation_is_open($evaluation)
            or intval(date("Ymd", $evaluation->timeopen)) > intval(date("Ymd"))) {
        return false;
    }

    if (evaluation_is_closed($evaluation)) {    // create item Studiengang
        $timecloseSaved = $evaluation->timeclose;
        $timeopen = ($evaluation->timeopen ? $evaluation->timeopen : time() - 86400);
        $teamteaching = $evaluation->teamteaching;

        //$DB->update_record('evaluation', $evaluation);
        // store global evaluation details in table evaluation
        if ($forceGlobal or empty($evaluation->possible_evaluations)) {
            if (is_siteadmin()) {
                print "<br><br>\nSaving evaluation results to database table evaluation<br>\n";
            }
            // set evaluation to Open for allowing set_results
            $evaluation->timeclose = time() + 86400;
            if ( $forceGlobal OR !safeCount($_SESSION["distinct_s"]) OR !isset($_SESSION["distinct_s_active"]) )
            {
                list($_SESSION["participating_courses"], $_SESSION["participating_empty_courses"],
                        $_SESSION["distinct_s"], $_SESSION["distinct_s_active"], $_SESSION["students"],
                        $_SESSION["students_active"],
                        $_SESSION["distinct_t"], $_SESSION["distinct_t_active"], $_SESSION["Teachers"], $_SESSION["Teachers_active"]
                        )
                        = get_evaluation_participants($evaluation);
            }
            //$_SESSION["num_courses_of_studies"] = safeCount(evaluation_get_course_studies($evaluation));
            //$_SESSION["duplicated"] = evaluation_count_duplicated_replies($evaluation);
            $_SESSION["teamteaching_courses"] = evaluation_count_teamteaching_courses($evaluation);

            $courses_of_studies = $_SESSION["num_courses_of_studies"];
            $duplicated_replies = $_SESSION["duplicated"];
            $teamteaching_courses = $_SESSION["teamteaching_courses"];
            $participating_students = $_SESSION["distinct_s"];
            $participating_active_students = $_SESSION["distinct_s_active"];
            $participating_teachers = $_SESSION["distinct_t"];
            $participating_active_teachers = $_SESSION["distinct_t_active"];
            $participating_courses = $_SESSION["participating_courses"];
            $participating_active_courses = $_SESSION["participating_courses"] - $_SESSION["participating_empty_courses"];
            $possible_evaluations = $_SESSION["students"]; // possible_evaluations( $evaluation );
            $possible_active_evaluations = $_SESSION["students_active"];
            if ($teamteaching and $participating_courses) {
                $possible_evaluations = possible_evaluations($evaluation);
                // WHEN Open: MUST divide by all  teachers and all courses because teachers of empty courses and empty courses may get evaluated!
                $possible_active_evaluations = possible_active_evaluations($evaluation);
                //$possible_active_evaluations = 	round($_SESSION["students_active"] 	* ($_SESSION["Teachers_active"]/$participating_active_courses), 0);
            }
            // only up to 15 days
            if ($forceGlobal and
                    (!empty($evaluation->participating_active_students) and ($timecloseSaved + (15 * 86400)) < time())) {
                $participating_students = $evaluation->participating_students;
                $participating_active_students = $evaluation->participating_active_students;
                $participating_teachers = $evaluation->participating_teachers;
                $participating_active_teachers = $evaluation->participating_active_teachers;
                //$_SESSION["distinct_t_active"];
                $participating_courses = $evaluation->participating_courses;
                $participating_active_courses = $evaluation->participating_active_courses;
                $possible_evaluations = $evaluation->possible_evaluations;
                $possible_active_evaluations = $evaluation->possible_active_evaluations;

                $DB->execute("UPDATE {evaluation} SET courses_of_studies=$courses_of_studies, 
										participating_students=$participating_students, participating_active_students=$participating_active_students,
										participating_courses=$participating_courses, participating_active_courses=$participating_active_courses,
										possible_evaluations=$possible_evaluations, possible_active_evaluations=$possible_active_evaluations,
										duplicated_replies=$duplicated_replies, teamteaching_courses=$teamteaching_courses, 
										participating_teachers=$participating_teachers, participating_active_teachers=$participating_active_teachers
										WHERE id=$evaluation->id");
            } else {
                $DB->execute("UPDATE {evaluation} SET courses_of_studies=$courses_of_studies, 
										participating_students=$participating_students, participating_active_students=$participating_active_students,
										participating_courses=$participating_courses, participating_active_courses=$participating_active_courses,
										possible_evaluations=$possible_evaluations, possible_active_evaluations=$possible_active_evaluations,
										duplicated_replies=$duplicated_replies, teamteaching_courses=$teamteaching_courses, 
										participating_teachers=$participating_teachers, participating_active_teachers=$participating_active_teachers
										WHERE id=$evaluation->id");
            }
        }

        // store per course evaluation details in tables evaluation_enrolments
        if ($forceCourse or
                !$DB->count_records_sql("SELECT COUNT(*) from {evaluation_enrolments} WHERE evaluation=$evaluation->id")
        ) {    // set evaluation to Open for allowing set_results
            $evaluation->timeclose = time() + 86400;
            $courses = evaluation_participating_courses($evaluation);
            ini_set("output_buffering", 256);
            if (true) //evaluation_debug() )
            {
                print "<br>\nUpdating table evaluation_enrolments<br>\n";
            }

            $active_students = $active_teachers = 0;
            $possible_evaluations = $possible_active_evaluations = 0;
            foreach ($courses as $courseid) {
                $numTeachersCourse = $numStudentsCourse = $numTeachersActiveCourse = $numStudentsActiveCourse = 0;
                $students = get_evaluation_participants($evaluation, false, $courseid, false, true);
                $teachers = get_evaluation_participants($evaluation, false, $courseid, true, false);
                $course_of_studies = evaluation_get_course_of_studies($courseid, false);
                $department = get_department_from_cos($course_of_studies);
                $teacherids = $active_teacherids = array();
                foreach ($teachers as $teacher) {
                    $teacherids[] = $teacher['id'];
                    $numTeachersCourse++;
                    if ($teacher["lastaccess"] > $timeopen) {
                        $active_teachers++;
                        $numTeachersActiveCourse++;
                        $active_teacherids[] = $teacher['id'];
                    }
                    //evaluation_user_lastaccess($evaluation, $teacher["id"], $teacher["lastaccess"], "teacher", $courseid);
                }
                $teacherids = implode(",", $teacherids);
                $active_teacherids = implode(",", $active_teacherids);
                $fullname = $shortname = "''";
                if ($course = $DB->get_record('course', array('id' => $courseid), '*')) {
                    $fullname = trim($course->fullname);
                    $shortname = trim($course->shortname);
                }
                foreach ($students as $student) {
                    if ($student["lastaccess"] > $timeopen) {
                        $active_students++;
                        $numStudentsActiveCourse++;
                    }
                    //evaluation_user_lastaccess($evaluation, $student["id"], $student["lastaccess"], "student", $courseid);
                }
                $fields =
                        array("evaluation", "courseid", "fullname", "shortname", "course_of_studies",
                                "department", "students", "active_students",
                                "teacherids", "active_teachers", "active_teacherids", "timemodified");
                $values = array($evaluation->id, $courseid, $fullname, $shortname, $course_of_studies,
                        $department, safeCount($students), $numStudentsActiveCourse,
                        $teacherids, $numTeachersActiveCourse, $active_teacherids, time());

                $recObj = new stdClass();
                foreach ($fields as $key => $value) {
                    $recObj->{$value} = $values[$key];
                }
                $recObj2 =
                        $DB->get_record_sql("SELECT id from {evaluation_enrolments} WHERE evaluation=$evaluation->id AND courseid=$courseid");
                if (isset($recObj2->id) and is_numeric($recObj2->id)) {
                    $recObj->id = $recObj2->id;
                    $DB->update_record('evaluation_enrolments', $recObj);
                } else {
                    $DB->insert_record('evaluation_enrolments', $recObj);
                }
                if (true) //evaluation_debug() )
                {
                    print "<br>\nCourse: $courseid - $fullname\n";
                    @ob_flush();@ob_end_flush();@flush();@ob_start();
                }
                if ($students and !empty($teacherids)) {
                    $possible_evaluations += (safeCount($students) * count(explode(",", $teacherids)));
                }
                if ($numStudentsActiveCourse and !empty($numTeachersActiveCourse)) {
                    $possible_active_evaluations += ($numStudentsActiveCourse * $numTeachersActiveCourse);
                }
            }
            if ($possible_evaluations and $possible_active_evaluations) {
                if ($evaluationU = $DB->get_record("evaluation", array("id" => $evaluation->id))) {
                    $evaluationU->possible_evaluations = $possible_evaluations;
                    $evaluationU->possible_active_evaluations = $possible_active_evaluations;
                    // $evaluationU->timeclose = $timecloseSaved;
                    $DB->update_record('evaluation', $evaluationU);
                }
            }
        }

        // revert tempoary timeclose
        $evaluation->timeclose = $timecloseSaved;
        //$DB->update_record('evaluation', $evaluation);

        $_SESSION['set_results_' . $evaluation->id] = true;
        if (!is_siteadmin()) {
            return true;
        }
        // store evaluation user core data in table evaluation_users
        if ($forceUsers or empty($evaluation->possible_evaluations)) /*$evaluation->timeclose > $DB->get_record_sql("SELECT timemodified from {evaluation_users}
														ORDER BY timemodified DESC LIMIT 1")->timemodified) */ {
            if (true) //evaluation_debug() )
            {
                print "<br>\nUpdating table evaluation_users<br>\n";
            }
            $evaluation->timeclose = $timeclose = time() + 86400;
            foreach (array("userid", "teacherid") as $participant) {
                $loopcnt = 1;
                $completed = $DB->get_records_sql("SELECT $participant AS partid, count(*) AS count
													FROM {evaluation_completed}
													WHERE evaluation=$evaluation->id
													GROUP BY $participant
													ORDER BY $participant ASC");
                ini_set("output_buffering", 256);
                foreach ($completed as $complete) {
                    $userid = $complete->partid;
                    $participated = $complete->count;
                    $user = $DB->get_record_sql("SELECT * FROM {user} WHERE id=$userid ");
                    if ($user) {
                        $username = trim($user->username);
                        $firstname = trim($user->firstname);
                        $lastname = trim($user->lastname);
                        $alternatename = trim($user->alternatename);
                        $email = trim($user->email);
                        $lastaccess = $user->lastaccess ?: 0;
                    } else {
                        continue;
                    }
                    $completeU = $DB->get_record_sql("SELECT id, timemodified 
													FROM {evaluation_completed}
													WHERE evaluation=$evaluation->id AND $participant=$userid
													ORDER BY timemodified DESC LIMIT 1");

                    // $lastaccess = evaluation_user_lastaccess($evaluation, $userid, $lastaccess, $participant, $courseid);
                    $timemodified = $lastaccess;
                    if ($completeU and $completeU->timemodified > 0) {
                        $lastaccess = $completeU->timemodified;
                    }
                    $fullname = "$firstname $lastname";
                    $utype = ($participant == "userid" ? "student" : "teacher");
                    $fields = array("userid", "username", "firstname", "lastname", "alternatename", "email", "$utype", "lastaccess",
                            "timemodified");
                    $values = array("$userid", "$username", "$firstname", "$lastname", "$alternatename", "$email", "$participated",
                            $lastaccess, time());
                    $recObj = new stdClass();
                    foreach ($fields as $key => $value) {
                        $recObj->{$value} = $values[$key];
                    }
                    $userID = $DB->get_record_sql("SELECT id,lastaccess from {evaluation_users} WHERE userid=$userid");
                    // update if existing
                    if (isset($userID->id) and $userID->id) {
                        $recObj->id = $userID->id;
                        //if ( $userID->lastaccess>0 ) {	unset($recObj->lastaccess); }
                        $DB->update_record('evaluation_users', $recObj);
                    } else {
                        $DB->insert_record('evaluation_users', $recObj);
                    }
                    if (true) //evaluation_debug() )
                    {
                        print "<br>\n" . str_pad(number_format($loopcnt), 6, " ", STR_PAD_LEFT) . ". <b>$participant</b>: " .
                                str_pad($userid, 6) . " - "
                                . str_pad($username, 12) . " - " . str_pad($fullname, 42) . " - Replies $utype: $participated\n";
                        @ob_flush();@ob_end_flush();@flush();@ob_start();
                    }
                    $loopcnt++;
                }
            }
        }

        $evaluation->timeclose = $timecloseSaved;
        if (is_siteadmin()) {
            if (!empty($CFG->ash) and !evaluation_is_item_course_of_studies($evaluation->id)) {
                evaluation_autofill_item_studiengang($evaluation);
            }
            // shuffle userids per evaluation and course
            if ($evaluation->anonymous and !$evaluation->anonymized) {
                ev_shuffle_completed_userids($evaluation);
            }
        }
    }
    return true;
}

function ev_shuffle_completed_userids($evaluation, $force = false) {
    global $DB;
    if ($evaluation->anonymous < 1) {
        print "<br>Funktion ev_shuffle_completed_userids(): Die Evaluation '".ev_get_tr($evaluation->name)."' ist nicht anonym.<br>"
                . "Daher ist keine Anonymisierung der Abgaben erforderlich!<br>\n";
        return;
    } else if ($evaluation->anonymized and !$force) {
        print "<br>Funktion ev_shuffle_completed_userids(): Die Evaluation '".ev_get_tr($evaluation->name)."' wurde bereits anonymisiert.<br>"
                . "Daher ist keine Anonymisierung der Abgaben erforderlich!<br>\n";
        return;
    }
    $courses = evaluation_participating_courses($evaluation);
    if (!$courses) {
        print "'<br><hr><b>ev_shuffle_completed_userids(): No courses found! Aborting.</b><hr>\n";
        return false;
    }
    $loopcntC = 1;

    $evaluationstructure = new mod_evaluation_structure($evaluation); // , $cm, $courseid, null, 0, $teacherid, null, null, null,1);
    $evaluationstructure->shuffle_anonym_responses(); // needs to be called to renumber replies

    print "<br><hr><b>ev_shuffle_completed_userids(): Courses:</b><hr>\n
        <span id='showCourseRec_".$evaluation->id."'>&nbsp;</span><br>\n";
    ini_set("output_buffering", 350);
    @ob_flush();@ob_end_flush();@flush();@ob_start();
    foreach ($courses as $courseid) {
        print '<script>document.getElementById("showCourseRec_' . $evaluation->id . '").innerHTML = "<b>' . $courseid . '</b> (' .
                $loopcntC . ' courses)";</script>';
        $loopcntC++;
        @ob_flush();@ob_end_flush();@flush();@ob_start();

        $completed = $DB->get_records_sql("SELECT * FROM {evaluation_completed} WHERE evaluation=$evaluation->id AND courseid=$courseid 
						ORDER BY id");
        $userids = array();
        if (safeCount($completed)) {
            foreach ($completed as $record) {
                $userids[] = $record->userid;
            }
            //print "<br><hr>userids: " . var_export($userids, true);
            shuffle($userids);
            $loopcnt = 0;
            foreach ($completed as $record) {
                $record->userid = $userids[$loopcnt];
                $loopcnt++;
                $DB->update_record('evaluation_completed', $record);
            }
        }
    }
    $recObj = new stdClass();
    //$DB->get_record_sql("SELECT * FROM {evaluation} WHERE id=$evaluation->id");
    $recObj->id = $evaluation->id;
    $recObj->anonymized = 1;
    $DB->update_record('evaluation', $recObj);
}

// identify course roles and set permissions
function evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm, $setD = true, $user = false) {
    global $DB, $USER, $CFG;
    $evaluationName = ev_get_tr($evaluation->name);
    $evaluationCourse = $evaluation->course;
    $isPermitted = $isTeacher = $isStudent = $SiteEvaluation = false;
    $teachers = array();
    $courseid = false ? optional_param('courseid', false, PARAM_INT) : $courseid;
    $CourseTitle = $CourseName = "";
    if (empty($user) or !isset($user->username)) {
        $user = $USER;
    }
    $username = $user->username;

    // unset evaluation sessions if required
    validate_evaluation_sessions($evaluation);

    if ($evaluationCourse == SITEID) {
        if ($setD and !defined("SiteEvaluation")) {
            define("SiteEvaluation", true);
        }
        $SiteEvaluation = true;
    } else {
        $courseid = $evaluationCourse;
    }
    // if called from inside a course
    if ($courseid and $courseid !== SITEID) {

        $evaluation_is_open = (evaluation_is_open($evaluation) or $evaluation->timeopen > time());
        //$evaluation_is_open = true;
        if ($evaluation_is_open) {
            $CourseRec = $DB->get_record_sql("SELECT id, fullname, shortname FROM {course} WHERE id = $courseid");
        } else {
            $CourseRec = $DB->get_record_sql("SELECT DISTINCT ON (courseid) courseid, fullname, shortname, teacherids, id 
                FROM {evaluation_enrolments} WHERE evaluation = $evaluation->id AND courseid = $courseid");
        }
        if (isset($CourseRec->fullname)) // || is_object( $CourseRec ))
        {
            if (empty($_SESSION["LoggedInAs"])) {
                $CourseTitle = get_string("course", "evaluation") .
                        ": <span style=\"font-size:12pt;font-weight:bold;\"><a href=\"/course/view.php?id="
                        . $courseid . "\">" . $CourseRec->fullname . "</a> (" . $CourseRec->shortname . ")</span>";
            } else {
                $CourseTitle = get_string("course", "evaluation") . ": <span style=\"font-size:12pt;font-weight:bold;\">"
                        . $CourseRec->fullname . " (" . $CourseRec->shortname . ")</span>";
            }
            if ($evaluation_is_open) {    //(3,4,5,9,12);" ); // get all teachers and students
                // get editingteachers, teachers and students
                $roleT = $DB->get_records_sql("SELECT * FROM {role} WHERE id IN (3,4,12,5);");
                $contextC = context_course::instance($courseid);
                if (is_array($contextC) or is_object($contextC)) {
                    foreach ($roleT as $role) {
                        $rolesC = get_role_users($role->id, $contextC);
                        foreach ($rolesC as $roleC) {
                            if ($username == $roleC->username) {    // if student
                                if ($role->id == 5) {
                                    $isStudent = true;
                                } else {
                                    $isTeacher = true;
                                }
                            }
                        }
                    }
                }
            } else  // evaluation is closed
            {
                if (in_array($user->id, explode(",", $CourseRec->teacherids))) {
                    $isTeacher = true;
                } else {
                    $isStudent = !empty($DB->get_record_sql("SELECT DISTINCT ON (userid) userid, evaluation, id  
                    FROM {evaluation_users_la} 
                    WHERE evaluation = $evaluation->id AND userid = $user->id AND role='student'"));
                }
            }
            if ($isStudent && !$isTeacher) {
                $evaluationcompletion = new mod_evaluation_completion($evaluation, $cm, $courseid);
                if ($setD and !defined("isStudent")) {
                    define("isStudent", true);
                }
                if ($evaluationcompletion->is_open()) {
                    if ($setD and !defined("EVALUATION_ALLOWED")) {
                        define("EVALUATION_ALLOWED", $username);
                    }
                }
            }
            if ($isTeacher) {
                $isPermitted = true;
                if ($setD and !defined("isTeacher")) {
                    define("isTeacher", true);
                }
            }

            $CourseName = $CourseRec->fullname;
        }
    }  // if $courseid
    if ($setD AND (evaluation_isPrivilegedUser($evaluation, $user) or is_siteadmin())) //AND $evaluation->course == SITEID) )
    {
        $isPermitted = true; //( empty($_SESSION['CoS_privileged'][$user->username]) ?true :!$courseid );
        if ($setD and !defined("EVALUATION_OWNER")) {
            define("EVALUATION_OWNER", $username);
        }
    }
    /*
	}
	else  //if !$SiteEvaluation
	{	if ( $setD AND !defined( "EVALUATION_ALLOWED") ) { define( "EVALUATION_ALLOWED", $username ); }
		//if ( $setD AND !defined( "isStudent") ) { define( "isStudent", true ); }
	}
	*/
    $showTeachers = "";
    if ($setD and $courseid) {
        evaluation_showteachers($evaluation, $courseid, $cm->id);
    }

    return array($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation);
}

function evaluation_isPrivilegedUser($evaluation, $user = false) {
    global $USER;
    $privileged_users = array();
    if (empty($user) or !isset($user->username)) {
        $user = $USER;
    }
    if (isset($_SESSION["EVALUATION_OWNER"]) and $_SESSION["EVALUATION_OWNER"] == $user->username) {
        return true;
    }
    if (!empty($evaluation->privileged_users)) {
        $privileged_users = explode("\n", $evaluation->privileged_users);
    }
    if (in_arrayi($user->username, $privileged_users) or evaluation_cosPrivileged($evaluation)) {
        $_SESSION["EVALUATION_OWNER"] = $user->username;
        return true;
    }
    if ( !isset($_SESSION["privileged_global_users"]) OR !is_array($_SESSION["privileged_global_users"])) {
        ev_set_privileged_users();
    }
    if (in_arrayi($user->username, $_SESSION["privileged_global_users"])) {
        $_SESSION["EVALUATION_OWNER"] = $user->username;
        return true;
    }
    if (is_siteadmin()) {
        $_SESSION["EVALUATION_OWNER"] = $USER->id;
        return true;
    }
    return false;
}

// is user CoS privileged
function evaluation_cosPrivileged($evaluation) {
    global $USER;
    // get CoS privileged users
    if (!isset($_SESSION['CoS_privileged'])) {
        $_SESSION['CoS_privileged'] = array();
        ev_set_privileged_users();
        get_evaluation_filters($evaluation);
    }
    if (false and evaluation_debug(false)) {
        print "<br><hr>:\n_SESSION['CoS_privileged']: " . nl2br(var_export($_SESSION['CoS_privileged'], true)) . "<hr>\n";
    }
    if (!empty($_SESSION['CoS_privileged'][$USER->username])) {
        return evaluation_is_cosPrivileged_filter($_SESSION['CoS_privileged'][$USER->username],
                $_SESSION['filter_course_of_studies']);
    }
    return false;
}


function evaluation_is_cosPrivileged_filter($needles, $haystack) {
    if (empty($needles) OR empty($haystack)){
        return false;
    }
    return array_intersect($needles, $haystack) !== [];
}
// is user CoS privileged users
function evaluation_get_cosPrivileged_filter($evaluation, $tableName = "") {
    global $USER;
    $filter = "";
    $filterset = false;
    // get CoS privileged users
    if (!isset($_SESSION['CoS_privileged'])) {
        ev_set_privileged_users();
        get_evaluation_filters($evaluation);
    }
    if (false) //evaluation_debug( false ) )
    {
        print "<br><hr>:\n_SESSION['CoS_privileged']: " . nl2br(var_export($_SESSION['CoS_privileged'], true)) . "<hr>\n";
    }
    if (!empty($_SESSION['CoS_privileged'][$USER->username]) AND !empty($_SESSION['filter_course_of_studies'])
            AND evaluation_is_cosPrivileged_filter($_SESSION['CoS_privileged'][$USER->username],$_SESSION['filter_course_of_studies'])) {
           $filter = " AND " . ($tableName ? $tableName . "." : "") . "course_of_studies ";
           $filter .= " IN ('" . implode("','", $_SESSION['CoS_privileged'][$USER->username]) . "')";
    //else
        //{	$filter .= "= '" . $_SESSION['CoS_privileged'][$USER->username][0] . "' "; }
    } // exclude WM course_of_studies from list
    else if (evaluation_is_WM_disabled($evaluation)) {
        $excluded = array();
        foreach ($_SESSION["course_of_studies_wm"] as $CoS => $is_WM) {
            if ($is_WM) {
                $excluded[] = $CoS;
            }
        }
        if (!empty($excluded)) {
            $filter = " AND " . ($tableName ? $tableName . "." : "") . "course_of_studies ";
            $filter .= " NOT IN ('" . implode("','", $excluded) . "')";
        }
    }
    if (false and !empty($filter) and evaluation_debug(false)) {
        print "<br><hr>\n<b>Filter</b>: $filter<hr>\n";
    }
    return $filter;
}

// privileged_global_users without WM course_of_studies
function evaluation_is_WM_disabled($evaluation) {
    global $USER;
    if (isset($_SESSION["privileged_global_users"][$USER->username]) and isset($_SESSION["course_of_studies_wm"])
            and !isset($_SESSION["privileged_global_users_wm"][$USER->username])
    ) {
        $sg_filter = explode("\n", $evaluation->filter_course_of_studies);
        $excluded = array();
        foreach ($_SESSION["course_of_studies_wm"] as $CoS => $is_WM) {
            if ($is_WM and in_arrayi($CoS, $sg_filter)) {
                $excluded[] = $CoS;
            }
        }
        return !empty($excluded);
    }
    return false;
}

//return all course ids a given mdl_user id is assossiated with
function ev_courses_of_id($evaluation, $userid) {
    global $DB;
    $evaluation_is_open = (evaluation_is_open($evaluation) or intval(date("Ymd", $evaluation->timeopen)) > intval(date("Ymd")));
    $sql = array();
    if (true) //$evaluation_is_open )
    {
        $filter = "";
        if ($evaluation->course == SITEID) {
            $evaluation_semester = get_evaluation_semester($evaluation);
            $filter = " AND RIGHT(c.idnumber,5) = '$evaluation_semester'";
        }
        $sql = "SELECT DISTINCT ON (e.courseid) e.courseid, e.id, c.shortname, c.fullname, c.idnumber 
                FROM {enrol} e, {course} c 
				WHERE e.courseid=c.id AND c.visible=1 $filter AND e.id IN " .
                "(SELECT enrolid FROM {user_enrolments} ue WHERE ue.userid=$userid) ORDER BY e.courseid DESC";
    }
    if (!$evaluation_is_open) {
        $filter = evaluation_get_cosPrivileged_filter($evaluation);
        $recC = $DB->get_records_sql($sql);
        $sql = "SELECT DISTINCT ON (courseid) courseid, id, course_of_studies FROM {evaluation_completed} 
				WHERE evaluation=$evaluation->id AND (userid=$userid OR teacherid=$userid) $filter ORDER BY courseid DESC";
        $recEv = $DB->get_records_sql($sql);
        return array_merge_recursive_distinct($recC, $recEv);
    }
    return $DB->get_records_sql($sql);

}

function ev_CoS_of_id($evaluation, $userid) {
    global $DB;
    $courses = ev_courses_of_id($evaluation, $userid);
    $CoS = array();
    //$is_open = evaluation_is_open ($evaluation);
    foreach ($courses as $course) {
        if (isset($course->course_of_studies)) {
            $CoS[$course->course_of_studies] = $course->course_of_studies;
        } else {
            $cos = evaluation_get_course_of_studies($course->courseid);
            if ($cos) {
                $CoS[$cos] = $cos;
            }
        }
    }
    if (false) //evaluation_debug( false ) )
    {
        print "<br><hr><b>CoS</b>: " . nl2br(var_export($CoS, true)) . "<hr>Courses:<br>" . nl2br(var_export($courses, true)) .
                "<hr>\n";
    }
    return $CoS;
}

function ev_is_course_in_CoS($evaluation, $courseid) {
    global $DB;
    $filter = evaluation_get_cosPrivileged_filter($evaluation);
    $count =
            $DB->count_records_sql("SELECT COUNT(*) FROM {evaluation_completed} WHERE evaluation=$evaluation->id AND courseid=$courseid $filter");
    if (false and evaluation_debug(false)) {
        print "<br><hr><b>Course $courseid is " . ($count ? "" : "NOT") . " in CoS_privileged courses</b><hr>\n";
    }
    return $count;
}

function ev_is_user_in_CoS($evaluation, $userid) {
    global $DB, $USER;

    //$DB->set_debug(true);
    $filter = evaluation_get_cosPrivileged_filter($evaluation);
    $sql =
            "SELECT COUNT(*) FROM {evaluation_completed} WHERE evaluation=$evaluation->id AND ( userid=$userid OR teacherid=$userid ) $filter";
    $count = $DB->count_records_sql($sql);
    if (false and evaluation_debug(false)) {
        print "<br><hr><b>userid $userid is " . ($count ? "" : "NOT") . " in CoS_privileged courses</b><br>sql: $sql<hr>\n";
    }
    return $count;
}

// get role name of given mdl_user id in course courseid
function ev_roles_in_course($userid, $courseid) {
    $context = context_course::instance($courseid);
    $roles = get_user_roles($context, $userid, true);
    $rolenames = array();
    foreach ($roles as $role) {
        $rolenames[$role->name] = $role->name;
    }
    return implode(", ", $rolenames);
}

function get_department_from_cos($cos) {
    if ( isset($_SESSION['CoS_department']) AND safeCount($_SESSION['CoS_department']) ) {
        $keys = array_keys($_SESSION['CoS_department']);
        $dept = array_searchi($cos, $keys);
        if ($dept AND isset($_SESSION['CoS_department'][$keys[$dept]]) ) {
            $department = $_SESSION['CoS_department'][$keys[$dept]];
            return $department;
        }
    }
    return "";
}

// view as teacher, student or standard user
function evaluation_LoginAs() {
    global $CFG, $DB, $USER, $PAGE, $OUTPUT, $id, $teacheridSaved,
           $courseid, $teacherid, $course_of_studiesID, $evaluation, $downloading;
    // !is_siteadmin() AND
    if (!(is_siteadmin() OR $USER->username =="harry" OR $USER->username =="bleckerth" OR $USER->username =="gorling" OR $USER->username =="khayat")
            AND empty($_SESSION["LoggedInAs"])) {
        return false;
    }
    if ($evaluation->course !== SITEID or !empty($downloading)){
            // or (isset($_SESSION["EVALUATION_OWNER"]) and
            //        $_SESSION["EVALUATION_OWNER"] !== $USER->id and empty($_SESSION["LoggedInAs"]) and empty($USER->realuser))) {
        return false;
    }
    $teachers = $DB->get_records_sql("SELECT DISTINCT ON (teacherid) teacherid, id 
                                from {evaluation_completed} 
								WHERE evaluation=$evaluation->id ORDER BY teacherid");

    $students = $DB->get_records_sql("SELECT DISTINCT ON (userid) userid, 
                            id from {evaluation_completed} 
							WHERE evaluation=$evaluation->id ORDER BY userid");

    $userid = false;
    $role = optional_param('LoginAs', "", PARAM_TEXT);
    $cmid = $id;
    $isActive = "deleted=0 AND suspended=0 AND ";
    list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation);
    evaluation_cosPrivileged($evaluation);
    $CoS_privileged = array();
    foreach ($_SESSION['CoS_privileged'] AS $CoSuser=>$CoSA){
        foreach ( $CoSA AS $cusername => $CoS){
            if ( in_arrayi($CoS, $sg_filter)){
                if (!isset($_SESSION['CoS_privileged_sgl'][$CoSuser])) {
                    $CoS_privileged[$CoSuser] = $CoS;
                }
            }
        }
    }
    $CoS_privileged_cnt = safeCount($CoS_privileged);
    // SGL
    $CoS_privileged_sgl = array();
    foreach ($_SESSION['CoS_privileged_sgl'] AS $CoSuser=>$CoSA){
        foreach ( $CoSA AS $cusername => $CoS){
            if ( in_arrayi($CoS, $sg_filter)){
                $CoS_privileged_sgl[$CoSuser] = $CoS;
            }
        }
    }
    $CoS_privileged_sgl_cnt = safeCount($CoS_privileged_sgl);

    if ($role == "logout" and !empty($USER->realuser)) {
        $userid = $_SESSION["EVALUATION_OWNER"] = $USER->realuser;
    } else if ($role == "privileg") {
        // $role = "Privilegierte Person";
        if (!empty($evaluation->privileged_users) or !empty($_SESSION["privileged_global_users"])) {
            $privileged_users = explode("\n", $evaluation->privileged_users);
            if (!empty($_SESSION["privileged_global_users"])) {    //array_merge_recursive_distinct( $privileged_users, $_SESSION["privileged_global_users"]);
                $privileged_users += $_SESSION["privileged_global_users"];
            }
            $loopcnt = 0;
            $choice = random_int(0, intval(safeCount($privileged_users)));
            if (false) //evaluation_debug( false ) )
            {
                print "<br><hr>:Selected: $choice\nprivileged_users: " . nl2br(var_export($privileged_users, true)) . "<hr>\n";
            }
            foreach ($privileged_users as $username) {
                if ($loopcnt == $choice) {    //print "<br><hr>Current choice: '$username'<hr>\n";
                    $user = $DB->get_record_sql("SELECT id,username from {user} WHERE $isActive username='" . trim($username) . "'");
                    if (isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if (safeCount($privileged_users) > $loopcnt) {
                        $choice++;
                    }
                }
                $loopcnt++;
            }
        }
    } else if ($role == "priv_sg") { // stristr($role, "priv_sg")) {
        // $role = "Privilegierte Person (Studiengang)";

        if ($CoS_privileged_cnt) {
            $loopcnt = 0;
            $choice = random_int(0, $CoS_privileged_cnt);
            if (false) //evaluation_debug( false ) )
            {
                print "<br><hr>\$CoS_privileged_cnt: $CoS_privileged_cnt:\n_CoS_privileged: "
                        . nl2br(var_export($CoS_privileged, true)) . "<hr>\n";
            }
            foreach (array_keys($CoS_privileged) as $uKey) {
                if ($loopcnt == $choice) {
                    $username = $uKey;
                    // print "<br><hr>uKey: \n" .nl2br(var_export($uKey,true))."<hr>\n";
                    if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive username='$username'") and isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if ($CoS_privileged_cnt > $loopcnt) {
                        $choice++;
                    }
                }
                $loopcnt++;
            }
        }
    } else if ($role == "priv_sg_sgl") {
        // $role = "Privilegierte Person (Studiengang, SGL)";

        if ($CoS_privileged_sgl_cnt) {
            $loopcnt = 0;
            $choice = random_int(0, $CoS_privileged_sgl_cnt);
            if (false) //evaluation_debug( false ) )
            {
                print "<br><hr>\$CoS_privileged_cnt: $CoS_privileged_cnt:\n_CoS_privileged: "
                        . nl2br(var_export($CoS_privileged, true)) . "<hr>\n";
            }
            foreach (array_keys($CoS_privileged_sgl) as $uKey) {
                if ($loopcnt == $choice) {
                    $username = $uKey;
                    // print "<br><hr>uKey: \n" .nl2br(var_export($uKey,true))."<hr>\n";
                    if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive username='$username'") and isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if ($CoS_privileged_sgl_cnt > $loopcnt) {
                        $choice++;
                    }
                }
                $loopcnt++;
            }
        }
    } else if (strstr($role, "teacher")) {
        $role = "editingteacher";
        if (false AND $teacheridSaved) {
            $userid = $teacheridSaved;
        } else {
            if ( $teachers ) {
                $choice = random_int(1, intval(safeCount($teachers)));
                $loopcnt = 1;
                foreach ($teachers as $teacher) {
                    if ($loopcnt == $choice) {
                        if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive id='$teacher->teacherid'") and isset($user->id)) {
                            $userid = $user->id;
                            break;
                        } else if (safeCount($teachers) > $loopcnt) {
                            $choice++;
                        }
                    }
                    $loopcnt++;
                }
            }
        }
    } else if ($role == "student") {
        if ( $students ) {
            $choice = random_int(1, intval(safeCount($students)));
            $loopcnt = 1;
            foreach ($students as $student) {
                if ($loopcnt == $choice) {
                    if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive id='$student->userid'") and isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if (safeCount($students) > $loopcnt) {
                        $choice++;
                    }
                }
                $loopcnt++;
            }
        }
    } else if ($role == "user") {
        $role = "user";
        $userid = evaluation_get_nonuserid($evaluation);
    }

    $url = "/mod/evaluation/view.php?id=$cmid";
    /*if ( $teacheridSaved )
	{	$url .= "&teacherid=".$teacheridSaved; }
	elseif ( $teacherid )
	{	$url .= "&teacherid=".$teacherid; }
	if ( $courseid )
	{	$url .= "&courseid=".$courseid; }
	if ( $course_of_studiesID )
	{	$url .= "&course_of_studiesID=".$course_of_studiesID; }
	*/
    if (!empty($role) AND is_numeric($userid) AND $userid) {
        if ($role == "logout") //\core\session\manager::is_loggedinas() )
        {
            $realuser = \core\session\manager::get_realuser();
            complete_user_login($realuser);
            unset($_SESSION["LoggedInAs"], $_SESSION["myEvaluations"], $_SESSION["EvaluationsID"]);
            redirect(new moodle_url($url), "", 0);
        } else {
            require_once(__DIR__ . '/../../course/lib.php');
            // $course = $DB->get_record('course', array('id' => SITEID), '*', MUST_EXIST);
            // $context = context_course::instance($course->id);
            // $systemcontext = context_system::instance();
            // $PAGE->set_context($context);
            // \core\session\manager::loginas( $userid, $systemcontext, true );
            $context = context_course::instance(SITEID);
            \core\session\manager::loginas($userid, $context, true);
            // $PAGE->set_context($context);
            // user_can_view_profile($user, null, $context))

            /*if (substr($CFG->release, 0, 1) < "4") // Moodle Version <4
            {
                set_user_preference("drawer-open-nav", false, $USER);
            }*/

            $_SESSION["LoggedInAs"] = $role;
            //$CFG->additionalhtmlfooter = evaluation_additional_html();
            //$CFG->additionalhtmlfooter = "";
            evHideSettings();
            unset($_SESSION["EvaluationsID"]);
            $role = stristr($role, "privileg") ? "privilegierte Person"
                    : (stristr($role, "SG_priv") ? "SG Priv"
                            : $DB->get_record('role', array('shortname' => $role), '*')->name);
            $roleuser = $DB->get_record_sql("SELECT id,firstname,lastname from {user} WHERE id=$userid");
            print '<br><h2 style="font-weight:bold;color:#131313;background-color:#131314;">Sie sind jetzt im Kontext der Evaluationen als '
                    . trim($roleuser->firstname) . " " . trim($roleuser->lastname) . " in der Rolle "
                    . $role . ' angemeldet.<br>'
                    . "</h2>Hinweis: <b>Die Auswahl des Moodle Kontos erfolgt randomisiert.</b><br>";
            //echo $OUTPUT->continue_button($url);
            //sleep(9);
            redirect(new moodle_url($url), "", 0);
            //echo $OUTPUT->footer();
            exit;
        }
    }
    // position output to page top right
    //print '<style>.LoginAs { color: red; position: absolute; top: 6px; right: 69px; font-weight: bold; font-size: 14px; }</style>'; //a:link {color:darkred;}
    print '<style>.LoginAs { color: #131313; float: right; font-weight: bold; font-size: 14px; }</style>'; //a:link {color:darkred;}
    $msg = $priv = "";
    // moved to view.php: $msg = '<a href="/course/modedit.php?update='.$id.'&return=1"><i class="fa fa-cog fa-1x" aria-hidden="true"></i></a>&nbsp;';
    if (!empty($_SESSION["LoggedInAs"])) {
        $showStop = '<span style="color:maroon;font-weight:bold;">Rollenansicht beenden</span>';
        $role = (stristr($_SESSION["LoggedInAs"], "privileg") ? "privilegierte Person"
                : ($_SESSION["LoggedInAs"] == "priv_sg" ? "Privilegierte Person (Studiengang)"
                : ($_SESSION["LoggedInAs"] == "priv_sg_sgl" ? "Privilegierte Person (Studiengang, SGL)"
                : $DB->get_record('role', array('shortname' => $_SESSION["LoggedInAs"]), '*')->name)));
        $msg .= "Aktuelle Ansicht: " . $role . '&nbsp; <a href="' . $url . '&LoginAs=logout">'.$showStop.'</a>';
    } else {
        $msg .= 'Rollenansicht wählen: '
                . ((!empty($evaluation->privileged_users) or !empty($_SESSION["privileged_global_users"]))
                        ? '<a href="' . $url . '&LoginAs=privileg">Privilegiert</a> - ' : "")
                . ($CoS_privileged_cnt ? '<a href="' . $url . '&LoginAs=priv_sg">Privilegiert (SG)</a> - ' : "")
                . ($CoS_privileged_sgl_cnt ? '<a href="' . $url . '&LoginAs=priv_sg_sgl">Privilegiert (SG,SGL)</a> - ' : "")
                . ($teachers ?'<a href="' . $url . '&LoginAs=teacher">Dozent_in</a> - ' :"")
                . ($students ?'<a href="' . $url . '&LoginAs=student">Student_in</a> - ' :"")
                . '<a href="' . $url . '&LoginAs=user">ASH Mitglied</a>';
        // not done: $msg .= ' - <a href="' . $url . '&LoginAs=username">' .get_string('username'). '</a>";
    }
    print "\n" . '<script>document.getElementById("LoginAs").innerHTML = "' . str_replace('"', '\"', $msg) . '<br>";</script>' .
            "\n";
}

function evaluation_get_nonuserid($evaluation) {
    global $DB;
    $userid = 38333;
    $users =
            $DB->get_records_sql("SELECT id from {user} WHERE deleted=0 AND suspended=0 AND lastaccess>0 ORDER by lastaccess ASC LIMIT 1000");
    $choice = random_int(1, 270);
    $loopcnt = 1;
    foreach ($users as $user) {
        $loopcnt++;
        if ($loopcnt <= $choice) {
            continue;
        }
        $completed = $DB->get_record_sql("SELECT id,userid from {evaluation_completed} 
								WHERE evaluation=$evaluation->id AND (userid=$user->id OR teacherid=$user->id)LIMIT 1");
        if (!isset($completed->userid)) {
            $userid = $user->id;
            break;
        }
    }
    return $userid;

}

function evaluation_count_students($evaluation, $courseid) {
    global $DB;
    /*$filter = "";
	if ( $courseid )
	{	$filter = " AND courseid=$courseid"; }
	*/
    // if (evaluation_is_open($evaluation) OR $evaluation->timeopen >time() )
    return safeCount(get_evaluation_participants($evaluation, false, $courseid, false, true));
}

function evaluation_count_active_students($evaluation, $courseid) {
    global $DB;
    $filter = "";
    $timeopen = ($evaluation->timeopen > 0) ? $evaluation->timeopen : (time() - 80600);
    $timeclose = ($evaluation->timeclose > 0) ? $evaluation->timeclose : (time() + 80600);
    if ($courseid) {
        $filter = " AND courseid=$courseid";
    }
    $active_students = 0;
    if (evaluation_is_open($evaluation) or $evaluation->timeopen > time()) {
        $students = get_evaluation_participants($evaluation, false, $courseid, false, true);
        foreach ($students as $student) {
            if ($student->lastaccess > $timeclose) {
                $active_students++;
            }
        }
        return $active_students;
    }
    return $DB->get_record_sql("SELECT DISTINCT ON (courseid) id, courseid, active_students FROM {evaluation_enrolments} 
												WHERE evaluation = $evaluation->id $filter")->active_students;
}

function possible_evaluations($evaluation, $courseid = false, $active = false,$cosFilter = false){
    global $DB, $USER;
    $possible_evaluations = $possible_active_evaluations = 0;
    $is_open = evaluation_is_open($evaluation);
    if ($is_open OR empty($evaluation->possible_evaluations)) {
        if (empty($_SESSION["allteachers"])) {
            evaluation_get_all_teachers($evaluation);
            //evaluation_get_course_teachers($courseid)
        }
        if ( !safeCount($_SESSION["participating_courses"])) {
            get_evaluation_participants($evaluation);
        }
        if ($cosFilter AND !isset($_SESSION['CoS_privileged'])) {
            ev_set_privileged_users();
            get_evaluation_filters($evaluation);
        }
        if ($cosFilter AND !isset($_SESSION['CoS_privileged'][$USER->username])){
            $cosFilter = false;
        }
        if ($active) {
            foreach ($_SESSION["possible_active_evaluations"] as $key => $maxEvaluations) {
                if ($courseid and $courseid != $key) {
                    continue;
                }
                if ($cosFilter){
                    if (!in_array(evaluation_get_course_of_studies($key),
                                    $_SESSION['CoS_privileged'][$USER->username])){
                        // print "<hr>Course: $courseid - in_array($cos,.".$_SESSION['CoS_privileged'][$user->username].")<hr>";
                        continue;
                    }
                }
                $possible_active_evaluations += $maxEvaluations;
            }
        } else {
            foreach ($_SESSION["possible_evaluations"] as $key => $maxEvaluations) {
                if ($courseid and $courseid != $key) {
                    continue;
                }
                if ($cosFilter){
                    if (!in_array(evaluation_get_course_of_studies($key),
                                    $_SESSION['CoS_privileged'][$USER->username])){
                        // print "<hr>Course: $courseid - in_array($cos,.".$_SESSION['CoS_privileged'][$user->username].")<hr>";
                        continue;
                    }
                }
                $possible_evaluations += $maxEvaluations;
            }
        }

    } else {
        $_SESSION["possible_evaluations"] = $_SESSION["possible_active_evaluations"] = array();
        $enrolments = $DB->get_records_sql("SELECT * from {evaluation_enrolments} WHERE evaluation=" . $evaluation->id);
        foreach ($enrolments as $enrolment) {
            if ($courseid AND $enrolment->courseid == $courseid){
                continue;
            }
            if ($cosFilter){
                if (!in_array(evaluation_get_course_of_studies($enrolment->courseid),
                        $_SESSION['CoS_privileged'][$USER->username])){
                    // print "<hr>Course: $courseid - in_array($cos,.".$_SESSION['CoS_privileged'][$user->username].")<hr>";
                    continue;
                }
            }
            if ($enrolment->students and !empty($enrolment->teacherids)) {
                $teachers = safeCount(explode(",", $enrolment->teacherids));
                $_SESSION["possible_evaluations"][$enrolment->courseid]
                        = ($enrolment->students * $teachers);
                $possible_evaluations += ($enrolment->students * $teachers);
            }
            if ($enrolment->active_students and !empty($enrolment->active_teachers)) {
                $_SESSION["possible_active_evaluations"][$enrolment->courseid] =
                $possible_active_evaluations += ($enrolment->active_students * $enrolment->active_teachers);
            }
        }
    }
    return ((!$active) ? $possible_evaluations : $possible_active_evaluations);
    /*else
	{	if ( $teacherid AND $course_of_studies AND isset($_SESSION["possible_evaluations_teachers"][$teacherid])
				AND $course_of_studies AND isset($_SESSION["possible_evaluations_cos"][$course_of_studies]) )
		{	$possible_evaluations += $_SESSION["possible_evaluations_teachers"][$teacherid];
			$possible_evaluations += $_SESSION["possible_evaluations_cos"][$course_of_studies];
		}
		elseif ( $teacherid AND isset($_SESSION["possible_evaluations_teachers"][$teacherid]) )
		{	$possible_evaluations += $_SESSION["possible_evaluations_teachers"][$teacherid];}
		elseif ( $course_of_studies AND isset($_SESSION["possible_evaluations_cos"][$course_of_studies]) )
		{	$possible_evaluations += $_SESSION["possible_evaluations_cos"][$course_of_studies];}
	}*/
}

function possible_active_evaluations($evaluation,$cosFilter = false) {
    return possible_evaluations($evaluation, false, true, $cosFilter);
    $possible_evaluations = 0;
    if (empty($_SESSION["allteachers"])) {
        evaluation_get_all_teachers($evaluation);
    }
    if ( !safeCount($_SESSION["participating_courses"])) {
        get_evaluation_participants($evaluation);
    }
    foreach ($_SESSION["possible_active_evaluations"] as $maxEvaluations) {
        $possible_evaluations += $maxEvaluations;
    }
    return $possible_evaluations;
}

// get all teachers of courses of current user of current evaluation - currently unused (Jan 7, 2022))
function evaluation_get_all_teachers($evaluation, $userid = false, $force = false) {
    validate_evaluation_sessions($evaluation);
    if ($force or empty($_SESSION["allteachers"]) or empty($_SESSION["teamteaching_courses"])) {
        if (empty($_SESSION["allteachers"])) {
            $_SESSION["allteachers"] = array();
        }
        $_SESSION["teamteaching_courses"] = 0;
        $_SESSION["teamteaching_courseids"] = array();
        $courseids = evaluation_participating_courses($evaluation, $userid);
        //print "<br>courses: ";safeCount($courses);print "<br>\n";
        foreach ($courseids as $courseid) {
            $numTeachers = 0;
            if (empty($_SESSION["allteachers"][$courseid])) {
                evaluation_get_course_teachers($courseid);
            }
            if (!isset($_SESSION["numStudents"][$courseid])) {
                $_SESSION["numStudents"][$courseid] = evaluation_count_students($evaluation, $courseid);
            }
            if (!empty($_SESSION["allteachers"][$courseid])) {    // courses with Team Teaching
                $numTeachers = safeCount($_SESSION["allteachers"][$courseid]);
                if ($numTeachers > 1) {
                    $_SESSION["teamteaching_courses"]++;
                    $_SESSION["teamteaching_courseids"][] = $courseid;
                }
            }
        }
    }
}

// get teacherid by courseid
function evaluation_get_course_teachers($courseid) {
    global $DB, $evaluation;
    if ($courseid and !isset($_SESSION["allteachers"][$courseid])) {
        $my_evaluation_users = array();
        if (empty($_SESSION["allteachers"])) {
            $_SESSION["allteachers"] = array();
        }
        $evaluation_is_open = true;
        if (isset($evaluation->name)) {
            $evaluation_is_open =
                    (evaluation_is_open($evaluation) or intval(date("Ymd", $evaluation->timeopen)) > intval(date("Ymd")));
        }
        if ( $evaluation_is_open )
        {
            $course = $DB->get_record('course', array('id' => $courseid), '*');
            if ($evaluation_is_open and (empty($course) or !isset($course->id))) //OR safeCount($course)<1
            {
                $_SESSION["allteachers"][$courseid] = $my_evaluation_users;
                return;
            }
            $contextC = context_course::instance($courseid);
            if (is_array($contextC) or is_object($contextC)) {
                $roleT =
                        $DB->get_records_sql("SELECT * FROM {role} WHERE id IN (3,4,12);"); // get only editingteachers //(3,4,5,9,12)
                foreach ($roleT as $role) {
                    $rolesC = get_role_users($role->id, $contextC);
                    foreach ($rolesC as $roleC) {    //echo "<hr>$roleC->lastname\n" . var_export($roleC,true)."<br>\n";
                        $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                        $my_evaluation_users[$roleC->id] = array("fullname" => $fullname, "lastname" => $roleC->lastname,
                                "id" => $roleC->id, "username" => $roleC->username, "email" => $roleC->email
                        , "lastaccess" => $roleC->lastaccess
                        );
                    }
                }
            }
        }
        else { // if (!$evaluation_is_open) {
            $CourseRec = $DB->get_record_sql("SELECT DISTINCT ON (courseid) courseid, fullname, shortname, teacherids, id 
												FROM {evaluation_enrolments} 
												WHERE evaluation = $evaluation->id AND courseid = $courseid");
            if (isset($CourseRec->teacherids) and !empty($CourseRec->teacherids)) {
                $teacherids = explode(",", $CourseRec->teacherids);
                foreach ($teacherids as $teacherid) {
                    if (!empty($teacherid) and !isset($my_evaluation_users[$teacherid])) {
                        $userRec =
                                $DB->get_record_sql("SELECT DISTINCT ON (userid) * FROM {evaluation_users} WHERE userid = $teacherid");
                        if (isset($userRec->userid)) {
                            $fullname = ($userRec->alternatename ? $userRec->alternatename : $userRec->firstname) . " " .
                                    $userRec->lastname;
                            $my_evaluation_users[$userRec->userid] = array("fullname" => $fullname,
                                    "firstname" => $userRec->firstname, "lastname" => $userRec->lastname,
                                    "id" => $userRec->userid, "username" => $userRec->username, "email" => $userRec->email
                            , "lastaccess" => $userRec->lastaccess
                            );
                        }
                    }
                }
            }
        }
        uasort($my_evaluation_users, function($a, $b) {
            return strcmp($a['lastname'], $b['lastname']);
        });
        $_SESSION["allteachers"][$courseid] = $my_evaluation_users;
    }
}

// show all teachers of a participating course
function evaluation_showteachers($evaluation, $courseid, $cmid = false, $user = false) {
    global $USER;
    /*if ( empty($user) OR !isset($user->username) )
	{ $user = $USER; }*/
    if (!$courseid) {
        return array();
    }
    if (empty($cmid)) {
        $cmid = get_evaluation_cmid_from_id($evaluation);
    }
    // evaluation_get_all_teachers( $evaluation, $user );
    evaluation_get_course_teachers($courseid);
    $teachers = $_SESSION["teachers"] = $_SESSION["allteachers"][$courseid];
    //print '<br>teachers: ';var_dump($teachers); print "<br>\n";
    $showTeachers = "<br>" . ev_get_string('coursehasnoteachers');
    if (safeCount($teachers) > 0) {
        $showTeachers = "<br>" . ev_get_string('teacher' . (safeCount($teachers) > 1 ? "s" : "")) . ": ";
        //var_dump($teachers);
        foreach ($teachers as $teacher) {
            if (defined("EVALUATION_OWNER") or $teacher["id"] == $USER->id) {
                $showTeachers .= '<a href="/mod/evaluation/print.php?id=' . $cmid . '&showTeacher=' . $teacher["id"] .
                        '&courseid=' . $courseid . '">';
            } else if (!empty($_SESSION["LoggedInAs"])) {
                $showTeachers .= '<a href="#">';
            } else {
                $showTeachers .= '<a href="/user/profile.php?id=' . $teacher["id"] . '" target="teacher">';
            }
            $showTeachers .= '<span style="font-weight:bold;color:darkgreen;">' . $teacher["fullname"] . "</span></a>, ";
        }
        $showTeachers = $_SESSION["showTeachers"] = substr($showTeachers, 0, -2);
    }
    if (!defined("showTeachers")) {
        define("showTeachers", $showTeachers);
        define("teachers", $teachers);
    }
    //print '<br>_SESSION["teachers"]: ';print print_r($_SESSION["teachers"]); print "<br>\n";
}

// unset evaluation sessions if required
function validate_evaluation_sessions($evaluation) {
    if (!isset($_SESSION["EvaluationsID"]) || $_SESSION["EvaluationsID"] != $evaluation->id) {
        unset( $_SESSION['allteachers'] );
        unset($_SESSION["myEvaluations"], $_SESSION['anonresponsestable'], $_SESSION['responsestable'], // $_SESSION['blockedusers'],
                $_SESSION["numStudents"], $_SESSION["teachers"], $_SESSION["showTeachers"],
                $_SESSION["participating_courses"], $_SESSION["participating_empty_courses"],
                $_SESSION["distinct_s"], $_SESSION["distinct_s_active"], $_SESSION["students"], $_SESSION["students_active"], $_SESSION["active_student"],
                $_SESSION["distinct_t"], $_SESSION["distinct_t_active"], $_SESSION["Teachers"], $_SESSION["Teachers_active"], $_SESSION["active_teacher"],
                $_SESSION["cos_distinct_s"], $_SESSION["cos_distinct_s_active"], $_SESSION["cos_students"], $_SESSION["cos_students_active"], $_SESSION["cos_active_student"],
                $_SESSION["cos_distinct_t"], $_SESSION["cos_distinct_t_active"], $_SESSION["cos_Teachers"], $_SESSION["cos_Teachers_active"], $_SESSION["cos_active_teacher"],
                $_SESSION["teamteaching_courses"], $_SESSION["teamteaching_courseids"], $_SESSION["questions"],
                $_SESSION["participating_courses_of_studies"], $_SESSION['EVALUATION_OWNER'],
                $_SESSION['filter_course_of_studies'], $_SESSION['course_of_studies'], $_SESSION["notevaluated"], $_SESSION['CoS_department'],
                $_SESSION['CoS_privileged'], $_SESSION['CoS_privileged_sgl'], $_SESSION['filter_courses'],
                $_SESSION["numStudents"], $_SESSION["possible_evaluations"],
                $_SESSION["possible_active_evaluations"], $_SESSION["active_teacher"], $_SESSION["active_student"],
                $_SESSION["num_courses_of_studies"], $_SESSION["duplicated"], $_SESSION["orderBy"],
                $_SESSION["distinct_users"], $_SESSION["evaluated_teachers"], $_SESSION["evaluated_courses"], $_SESSION["privileged_global_users"],
                $_SESSION["privileged_global_users_wm"], $_SESSION["course_of_studies_wm"], $_SESSION['ev_global_cfgfile'],
                $_SESSION['studentid'], $_SESSION['subqueries']

        );
        // $_SESSION['set_results_'.$evaluation->id];
    }
    $_SESSION["EvaluationsID"] = $evaluation->id;
}

// Print Button
function evPrintButton() {
    $buttonStyle = 'margin: 3px 5px;font-weight:bold;background-color:white;';
    echo '<div class="d-print-none" style="width:40px;display:inline;' . $buttonStyle . 'vertical-align:bottom;">';
    print html_writer::tag('a', '<img class="image" alt="Auswertung Drucken" title="Auswertung Drucken" 
			src="pix/Printer_icon-teal-29px.png" width="40">',
            array('id' => 'printPage', 'style' => $buttonStyle, 'href' => 'javascript: window.print();',
                    'title' => 'Auswertung Drucken'));
    echo '</div>';
}

// show loading spinner icon / font awesome required
function evaluation_showLoading() {    //evaluation_spinnerJS();
    echo "
	<style>
	.spinner, #spinner {
    position: fixed;
    left: 0px;
    top: 60px;
    width: 100%;
    height: 100%;
    z-index: 9999; 
    opacity: 0.4;
	}
	</style>
	";
    echo '<div id="spinner" class="d-print-none" style="display:block;float:center;text-align:center;font-weight:bold;font-size:12em;">			
			<i style="color:blue;" class="d-print-none fa fa-spinner fa-pulse fa-2x fa-fw"></i></div>';
    echo "\n<script>
	function ev_spinner_disable()
	{	if ( document.getElementById('spinner')  !== null ) 
		{	document.getElementById('spinner').style.display='none'; }
		if ( document.getElementById('evFilters')  !== null  ) 
		{	document.getElementById('evFilters').style.display='block'; }
		if ( document.getElementById('evButtons')  !== null  ) 
		{	document.getElementById('evButtons').style.display='block'; }
		if ( document.getElementById('evCharts')  !== null ) 
		{	document.getElementById('evCharts').style.display='block'; }
		if ( document.getElementById('evView')  !== null ) 
		{	document.getElementById('evView').style.display='inline'; }
	}
	function ev_spinner_disable_timeout()
	{	setTimeout(function() { ev_spinner_disable(); }, 2100 ); }
	</script>\n";
    @ob_flush();@ob_end_flush();@flush();@ob_start();
}

// js code for loafing spinner
function evaluation_spinnerJS($hide = true) {
    if (!$hide) {    /*print '<script>document.getElementById("spinner").style.display="none";
				document.getElementById("evFilters").style.display="block";</script>'; */
        print '<script>ev_spinner_disable();</script>';
    } else {
        ?>
        <script>
            //addFunctionOnWindowLoad(ev_spinner_disable;
            if (window.addEventListener) {
                window.addEventListener('load', ev_spinner_disable_timeout, false);
            } else {
                window.attachEvent('onload', ev_spinner_disable_timeout);
            }
        </script>
        <?php
    }
}

// create session var from $_REQUEST and return value or preset value
function ev_session_request($var, $preset) {
    if (!isset($_SESSION[$var])) {
        $_SESSION[$var] = $preset;
    }
    $val = !isset($_REQUEST[$var]) ? $_SESSION[$var] : $_REQUEST[$var];
    //print "<br>var: $var - preset: $preset - val: $val - _REQUEST[var]: $_REQUEST[$var] - _SESSION[var]: $_SESSION[$var] - ";
    $_SESSION[$var] = $val;
    //if ( empty($val) ) { $val = $_SESSION[$var]; }
    //if ( $val == $preset ) { $val = $_SESSION[$var]; }
    //else {	$_SESSION[$var] = $val; }
    //print "var: $var - preset: $preset - val: $val - _SESSION[var]: $_SESSION[$var]<br>"	;
    return $_SESSION[$var];
}

// get course metadata from customfields if any
function evaluation_get_course_metadata($courseid, $field = "") {
    $handler = \core_customfield\handler::get_handler('core_course', 'course');
    // This is equivalent to the line above.
    //$handler = \core_course\customfield\course_handler::create();
    $datas = $handler->get_instance_data($courseid);
    $metadata = [];
    foreach ($datas as $data) {
        if (empty($data->get_value())) {
            continue;
        }
        $cat = $data->get_field()->get_category()->get('name');
        $shortname = $data->get_field()->get('shortname');
        if ($field == $shortname) {
            return $data->get_value();
        }
        $metadata[$shortname] = $cat . ': ' . $data->get_value();
    }
    if (empty($field)) {
        return $metadata;
    }
    return "";

}

// minimum size of result list - Set in db min_results
function evaluation_min_results($evaluation) {
    return (stristr($evaluation->name, "Pretest mit neuem Fragenkatalog")) ? 0 : $evaluation->min_results;
}

function min_results_text($evaluation) {
    return $evaluation->min_results_text;
}

function min_results_priv($evaluation) {
    return $evaluation->min_results_priv;
}

function evaluation_count_qtype($evaluation, $qtype = "textarea") {
    global $DB;
    return $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id, 'typ' => $qtype), '*');
}

// get current semester
function evaluation_get_current_semester() {
    $year = date("Y");
    $month = ((int) date("n") > 3 and (int) date("n") < 10) ? "1" : "2";
    //if ( $month == 2 AND date("n")>1 AND date("Y")==$year )
    if ($month == 2 and date("n") < 4) {
        $year = $year - 1;
    }
    $semester = $year . $month;
    return $semester;
}

function get_evaluation_semester($evaluation) {
    if (!empty($evaluation->semester)) {
        return trim($evaluation->semester);
    }
    $timeopen = ($evaluation->timeopen > 0) ? $evaluation->timeopen : (time() - 80600);
    $timeclose = ($evaluation->timeclose > 0) ? $evaluation->timeclose : (time() + 80600);
    $year = date("Y", $timeopen);
    $month = ((int) date("n", $timeopen) > 3 and (int) date("n", $timeopen) < 10) ? "1" : "2";
    //if ( $month == 2 AND date("n")>1 AND date("Y")==$year )
    if ($month == 2 and date("n", $timeopen) < 4) {
        $year = $year - 1;
    }
    $semester = $year . $month;
    return $semester;
}

// calculate evaluation period, etc.
function total_evaluation_days($evaluation) {
    $timeopen = ($evaluation->timeopen > 0 ? $evaluation->timeopen : 0);
    $timeclose = ($evaluation->timeclose > 0 ? $evaluation->timeclose : 0);
    $difference = $timeclose - $timeopen;
    if ($difference > 0) {
        return floor(($difference / 60 / 60 / 24));
    }
    return 1;
}

function current_evaluation_day($evaluation) {
    $timeopen = ($evaluation->timeopen > 0 ? $evaluation->timeopen : 0);
    $timeclose = ($evaluation->timeclose > 0 ? $evaluation->timeclose : 0);
    $current_seconds = time() - $timeopen;
    if ($current_seconds > 0 and $timeclose > time()) {    //return round(($current_evaluation_day/60/60/24)+1,1);
        return max(1, floor($current_seconds / (60 * 60 * 24)));
    }
    return total_evaluation_days($evaluation);
}

function remaining_evaluation_days($evaluation) {
    $timeclose = ($evaluation->timeclose > 0 ? $evaluation->timeclose : 0);
    $remaining_days = $timeclose - time();
    if ($remaining_days > 0 and $timeclose > time()) {
        return floor(($remaining_days / 60 / 60 / 24));
    }
    return 0;
}

// get array of of Studiengangs for semester of evaluation
function evaluation_get_course_studies($evaluation, $link = false, $raw = false) {
    global $DB;
    $is_closed = (!evaluation_is_open($evaluation) and $evaluation->timeopen < time());
    list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation, false);

    /*if (!empty($courses_filter)) {
        $sg_courses_filter = evaluation_get_course_of_studies_from_courseids($courses_filter);
    }*/

    $sgTmp = $sgNtmp = $studynames = array();
    /*if ( $is_closed ) {
		$course_studies_raw = $DB->get_records_sql( "select DISTINCT ON (course_of_studies) completed.* from {evaluation_completed} AS completed
														WHERE evaluation=$evaluation->id ORDER BY course_of_studies");
	}
	else{*/
    $evaluation_semester = get_evaluation_semester($evaluation);
    // get path of current semester
    $cat = $DB->get_record_sql("select id,idnumber,path from {course_categories} 
                        where idnumber='$evaluation_semester' LIMIT 1");
    //print_r("PATH:" .$cat->path);
    if (empty($cat->path)) {
        return array();
    }
    $course_studies_raw =
            $DB->get_records_sql("select id,idnumber,name AS course_of_studies from {course_categories} 
                    where path like '" . $cat->path . "/%' AND array_length(string_to_array(path, '/'), 1)-1 =2");
    if (empty($course_studies_raw)) {
        return array();
    }
    usort($course_studies_raw, function($a, $b) {
        return strcmp($a->course_of_studies, $b->course_of_studies);
    });
    //}
    if ($raw) {
        foreach ($course_studies_raw as $course_studies) {
            if (empty($course_studies->course_of_studies)) {
                continue;
            }
            if (!empty($sg_filter)) {
                if (!in_arrayi($course_studies->course_of_studies, $sg_filter)) {
                    continue;
                }
                /*if (!empty($courses_filter) and in_arrayi($course_studies->course_of_studies, $sg_courses_filter)) {
                    continue;
                }*/
            } else if (!empty($courses_filter)) {
                $sg_filter = evaluation_get_course_of_studies_from_courseids($courses_filter);
                if (!in_arrayi($course_studies->course_of_studies, $sg_filter)) {
                    continue;
                }
            }
            $sgTmp[$course_studies->course_of_studies] = $course_studies;
            $sgNtmp[$course_studies->course_of_studies] = $course_studies->course_of_studies;
        }

        foreach ($sgNtmp as $studyname) {
            $studynames[$studyname] = $studyname;
        }
        return $studynames;
    }

    $course_studies = array();
    foreach ($course_studies_raw as $studiengang) {
        if ( ($studiengang->course_of_studies == "weitere Veranstaltungen" or
                $studiengang->course_of_studies == "Zusatzveranstaltungen"
                or $studiengang->course_of_studies == "Alle Studiengänge und Semester")) {
            $studiengang->course_of_studies = '<span style="font-style: italic;">' . $studiengang->course_of_studies . '</span>';
        }
        if ($link) {
            $studiengang->course_of_studies =
                    '<a href="/course/index.php?categoryid=' . $studiengang->id . '" target="studiengang">' .
                    $studiengang->course_of_studies . "</a>\n";
        }
        $course_studies[] = $studiengang;
    }
    return $course_studies;
}

// get Studiengang name of course from course_categories path
function evaluation_get_course_of_studies($courseid, $link = false, $showsemester = false) {
    global $DB;
    if (empty($courseid) or $courseid == 1 or !defined('COURSE_OF_STUDIES_PATH')) {
        return "";
    }
    if (!$showsemester and !$link) {
        $studiengang =
                $DB->get_record_sql("SELECT id, course_of_studies AS name FROM {evaluation_completed} WHERE courseid = $courseid LIMIT 1");
        if (isset($studiengang->name) and !empty($studiengang->name)) {
            return $studiengang->name;
        }
    }
    if ($showsemester or $link or !isset($studiengang->name) or empty($studiengang->name)) {
        $course = $DB->get_record('course', array('id' => $courseid), '*'); //get_course($courseid);
        if (!isset($course->category) and !$showsemester) {
            return "";
        }
        $cat = $DB->get_record_sql("select id,path from {course_categories} where id=" . $course->category);
        //print_r("Course category path: " .$cat->path . ""<br>\n");
        $path = explode("/", $cat->path);
        $semesterCat = (safeCount($path) >= 2 ? $path[1] : 0);
        if ($showsemester and (empty($semesterCat) or !isset($path[COURSE_OF_STUDIES_PATH]))) {
            return "";
        }
        $studiengangCat = $path[min(safeCount($path) - 1, COURSE_OF_STUDIES_PATH)];
        //echo ""Course of Studies path: $studiengangCat<br>\n";
        if (empty($studiengangCat) and !$showsemester) {
            return "";
        }

        if ($showsemester) {
            if (!isset($path[COURSE_OF_STUDIES_PATH + 1])) {
                return "./.";
            }
            $SsemesterCat = $path[COURSE_OF_STUDIES_PATH + 1];
            $semester = $DB->get_record_sql("select id,name from {course_categories} where id=" . $SsemesterCat);
            //echo ""Semester category: $SemesterCat - Semester: $semester->name<br>\n";
            if (!$semester or !isset($semester->name) or empty($semester->name) or !stristr($semester->name, 'semester')) {
                return "./.";
            }
            if ($link and empty($_SESSION["LoggedInAs"])) {
                return '<a href="/course/index.php?categoryid=' . $semester->id . '" target="semester">' . $semester->name .
                        "</a>\n";
            }
            return $semester->name;
        }
    }
    if (isset($studiengangCat) and empty ($studiengang->name)) {
        $studiengang = $DB->get_record_sql("select id,name from {course_categories} where id=$studiengangCat");
    }
    //$GLOBALS["studiengang"] = $studiengang;
    // return name of studiengang
    //echo ""Course of Studies: $studiengang->name<br>\n";
    if (isset($studiengang->name) and
            !empty($studiengang->name)) {    //set new value to course->customfield_studiengang, disabled because customfield was removed
        if ($link and !empty($studiengang->id) and empty($_SESSION["LoggedInAs"])) {
            return '<a href="/course/index.php?categoryid=' . $studiengang->id . '" target="studiengang">' . $studiengang->name .
                    "</a>\n";
        }
        $_SESSION['course_of_studies'] = $studiengang->name;
        return $studiengang->name;
    }
    return "";
}

function evaluation_get_course_of_studies_from_courseids($courses_filter) {
    global $DB;
    //list( $sg_filter, $courses_filter ) = get_evaluation_filters(
    $studynames = $studytmp = array();
    foreach ($courses_filter as $courseid) {
        $cos = evaluation_get_course_of_studies($courseid);
        $studytmp[$cos] = $cos;
    }
    foreach ($studytmp as $studyname) {
        $studynames[] = $studyname;
    }
    //$_SESSION['studynames'] = $studynames
    return $studynames;
}

function evaluation_get_course_of_studies_from_evc($course_of_studiesID) {
    global $DB;
    $sql = "SELECT fbc.id, fbc.course_of_studies, fbc.teacherid, fbc.courseid
            FROM {evaluation_completed} fbc WHERE fbc.id = :id limit 1";
    $record = $DB->get_record_sql($sql, ['id' => $course_of_studiesID]);
    if (!empty($record)) {
        return $record->course_of_studies;
    }
    return "";

}

// this funcion does's work as expoected because there are many possible complete-ids with same course of study!
function evaluation_get_course_of_studies_id_from_evc($id, $course_of_studies, $evaluation = false) {
    global $DB;
    $filter = "";
    list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
    $evaluationstructure = new mod_evaluation_structure($evaluation, $cm, false, null, 0, false, $course_of_studies, false);
    $allStudies = $evaluationstructure->get_completed_course_of_studies();
    return array_searchi($course_of_studies, $allStudies);

}

// get all courses of course_of_studies
function get_courseid_from_course_of_studies($evaluation, $course_of_studies) // unused 20221212
{
    global $DB;

    $course = $DB->get_record('course', array('id' => $courseid), '*');

    if ($evaluation->course == SITEID) {
        $evaluation_semester = get_evaluation_semester($evaluation);
        $filter = "AND SUBSTRING(idnumber,1,5) = '$evaluation_semester'";
        $cat = $DB->get_record_sql("select id from {course_categories} where path like '%/$course->category' AND $filter LIMIT 1");
        $courses = $DB->get_records_sql("SELECT id,fullname,shortname FROM {course} WHERE category = $cat->id");
        usort($courses, function($a, $b) {
            return $a->{$fullname} < $b->{$fullname};
        });
    } else {
        $courses = $DB->get_records_sql("SELECT id,fullname,shortname FROM {course} WHERE id = $courseid");
    }
    return $courses;
}

// get participating course_of_studies
function evaluation_participing_course_of_studies($evaluation) {
    return evaluation_participating_courses($evaluation, false, true);
}

// get participating courses and course_of_studies
function evaluation_participating_courses($evaluation, $userid = false, $cstudies = false) {
    global $DB;
    $evaluation_semester = get_evaluation_semester($evaluation);
    $fcourses = "";
    $ids = array();
    $evaluation_is_open = (evaluation_is_open($evaluation) or intval(date("Ymd", $evaluation->timeopen)) > intval(date("Ymd")));
    if ($userid) {    //$DB->set_debug(true);
        if ($evaluation_is_open) {
            $myCourses = $DB->get_records_sql("SELECT e.id AS eid,e.courseid as courseid,c.idnumber as idnumber FROM {enrol} e, {course} c 
							WHERE  e.id IN (SELECT enrolid FROM {user_enrolments} ue WHERE ue.userid=$userid AND (ue.timeend<1 or ue.timeend>"
                    . time() . "))
							AND e.courseid=c.id AND c.visible=1 AND RIGHT(c.idnumber,5) = '$evaluation_semester' ");
        } else{
            $myCourses = $DB->get_records_sql("SELECT DISTINCT ON (courseid) courseid, id,course_of_studies FROM {evaluation_completed} 
												WHERE evaluation=$evaluation->id AND (userid=$userid OR teacherid=$userid) ORDER BY courseid DESC");
        }
        //$DB->set_debug(false);
        foreach ($myCourses as $course) {
            $ids[$course->courseid] = $course->courseid;
        }
        //if ( safeCount($ids) >0	) { $fcourses = "AND ".($evaluation_is_open?"" :"course")."id IN (".implode(",",$ids).")"; }
        if (safeCount($ids) > 0) {
            $fcourses = "AND " . ((true or $evaluation_is_open) ? "" : "course") . "id IN (" . implode(",", $ids) . ")";
        } else {
            return $ids;
        }
    }
    if ($evaluation_is_open) {
        $myCourses =
                $DB->get_records_sql("SELECT id FROM {course} WHERE visible=1 AND RIGHT(idnumber,5) = '$evaluation_semester' $fcourses");
    } else {
        $myCourses = $DB->get_records_sql("SELECT DISTINCT ON (courseid) id as eveid, courseid AS id, course_of_studies FROM {evaluation_enrolments} 
											WHERE evaluation=$evaluation->id $fcourses ORDER by courseid");
    }
    $ids = $studies = array();
    foreach ($myCourses as $course) {
        if (!pass_evaluation_filters($evaluation, $course->id)) {
            continue;
        }
        $ids[$course->id] = $course->id;
        if ($cstudies) {
            if (isset($course->course_of_studies)) {
                $Studiengang = $course->course_of_studies;
            } else {
                $Studiengang = evaluation_get_course_of_studies($course->id, false);
            }
            $studies[$Studiengang] = $Studiengang;
        }
    }
    natsort($ids);
    //print "<br><br><br>IDS: ".safeCount($ids);var_dump($ids);print "<br>\n";
    if ($cstudies) {
        return natsort($studies);
    }
    return $ids;
}

/**
 * Retrieve the preferred language of a user.
 *
 * @param int $userid The ID of the user whose preferred language is being retrieved.
 * @return string|$CFG->lang The preferred language of the user, or $CFG->lang if not set.
 */
function ev_get_user_language($userid) {
    global $CFG, $DB;

    // Retrieve the preferred language for the user.
    $user = $DB->get_record('user', array('id' => $userid), 'lang', IGNORE_MISSING);

    // Return the preferred language or null if not set.
    return $user ? $user->lang : $CFG->lang;
}
function evaluation_user_lastaccess($evaluation, $userid, $lastaccess = 0, $role = "student", $courseid=false) {
    global $DB;
    if (empty($lastaccess)) {
        $lastaccess = 0;
    }
    if (!isset($evaluation->timeclose)) {
        return $lastaccess;
    }
    if ( !$courseid){
        $courseid = "";
    }
    $userlast = $DB->get_record_sql("SELECT * from {evaluation_users_la} WHERE evaluation=" . $evaluation->id .
            " AND userid=$userid AND role='$role' LIMIT 1");
    $is_open = evaluation_is_open($evaluation);
    $update = false;

    if (!empty($userlast->lastaccess) and !$is_open) {
        $lastaccess = $userlast->lastaccess ?: 0;
        return $lastaccess;
    } else if ($is_open) {
        if (empty($userlast->lastaccess)) {
            $fields = array("evaluation", "userid", "role", "courseids", "lastaccess", "timemodified");
            $values = array($evaluation->id, $userid, $role, $courseid, $lastaccess, time());
            $recObj = new stdClass();
            foreach ($fields as $key => $value) {
                $recObj->{$value} = $values[$key];
            }
            $DB->insert_record('evaluation_users_la', $recObj);
            return $lastaccess;
        } else if ($lastaccess > ($userlast->lastaccess+86400)) {
            // update once daily to save resources
            $userlast->lastaccess = $lastaccess;
            $userlast->timemodified = time();
            $update = true;
        }
    }
    if ( empty($userlast->courseids)) {
        $courseids = array();
    }
    else{
        $courseids = explode(",", $userlast->courseids);
    }
    if (!empty($userlast) AND is_object($userlast)
            AND is_numeric($courseid) AND !in_array($courseid, $courseids)){
        $courseids[] = $courseid;
        $courseidsC = implode(",", $courseids);
        if ( is_string($courseidsC) AND !empty($courseidsC)){
            // print nl2br(print_r("<hr>courseidsC: $courseidsC<hr>"));
            $userlast->courseids = $courseidsC;
            $update = true;
        }
    }
    if ( $update AND !empty($userlast) AND is_object($userlast)){
        $DB->update_record('evaluation_users_la', $userlast);
    }
    return $lastaccess;
}

// has user participated in evaluation
function evaluation_has_user_participated($evaluation, $userid, $courseid = false, $teacherid=false) {
    global $DB;
    $filter = "";
    if ($courseid) {
        $filter = " AND courseid=$courseid";
    }else if ($teacherid ){
        $participated = $DB->get_records_sql("SELECT id,userid from {evaluation_completed} WHERE evaluation=" . $evaluation->id
                . " AND userid=$userid AND teacherid=$teacherid $filter");

        return safeCount($participated);
    }
    $participated = $DB->get_records_sql("SELECT id,userid from {evaluation_completed} WHERE evaluation=" . $evaluation->id
            . " AND (userid=$userid OR teacherid=$userid) $filter");
    return safeCount($participated);
}

// is user enrolled to participating courses
function evaluation_is_user_enrolled($evaluation, $userid, $courseid = false) {
    global $DB;
    if ($evaluation->course == SITEID) {
        $evaluation_semester = get_evaluation_semester($evaluation);
        $filter = " AND RIGHT(c.idnumber,5) = '$evaluation_semester'";
        if ($courseid) {
            $filter .= " AND e.courseid = $courseid";
        }
    } else {
        $filter = " AND e.courseid = $evaluation->course";
    }

    $is_open = evaluation_is_open($evaluation); // OR $evaluation->timeopen > time();
    $myCourses = $DB->get_records_sql("SELECT e.id,e.courseid as courseid, c.idnumber as idnumber FROM {enrol} e, {course} c 
						WHERE  e.id IN (SELECT enrolid FROM {user_enrolments} ue WHERE ue.userid=$userid AND (ue.timeend<1 or ue.timeend>" .
            time() . "))
						AND e.courseid=c.id  AND c.visible=1 $filter ORDER BY e.courseid");

    if (!$is_open) {
        $filter = "";
        if ($courseid) {
            $filter = " AND courseid = $courseid";
        }
        $myCoursesC = $DB->get_records_sql("SELECT id, courseid, userid FROM {evaluation_completed} 
						WHERE evaluation=$evaluation->id $filter AND (userid=$userid OR teacherid=$userid) ORDER BY courseid DESC");
        array_merge_recursive_distinct($myCourses, $myCoursesC);
    }
    /*if ( !$is_open )
	{	$myCoursesC = $DB->get_records_sql("SELECT e.courseid as courseid, e.id, c.idnumber as idnumber FROM {enrol} e, {course} c
						WHERE  e.id IN (SELECT enrolid FROM {user_enrolments} ue WHERE ue.userid=$userid AND (ue.timeend<1 or ue.timeend>".time()."))
						AND e.courseid=c.id AND c.visible=1 $filter ORDER BY e.courseid");
		array_merge_recursive_distinct( $myCourses, $myCoursesC );
	}*/
    $ids = array();
    foreach ($myCourses as $course) {
        if ($is_open and !pass_evaluation_filters($evaluation, $course->courseid)) {
            continue;
        }
        $ids[$course->courseid] = $course->courseid;
    }
    return $ids;
}

function evaluation_is_student($evaluation, $myEvaluations, $courseid = false, $teacherid = false) {
    global $USER;
    foreach ($myEvaluations as $myEvaluation) {
        if ($myEvaluation['role'] == "student" and $myEvaluation['id'] == $USER->id) {
            if ($teacherid){
                if (is_array($myEvaluation['teachers']) AND array_key_exists( $teacherid, $myEvaluation['teachers'])) {
                    return true;
                } else {
                    continue;
                }
            }
            if (!$courseid) {
                return true;
            }
            if ($myEvaluation['courseid'] == $courseid) {
                return true;
            }
        }
    }
    return false;
}

function evaluation_is_teacher($evaluation, $myEvaluations, $courseid = false) {
    global $USER;
    foreach ($myEvaluations as $myEvaluation) {
        if ($myEvaluation['role'] == "teacher" and $myEvaluation['id'] == $USER->id) {
            if (!$courseid) {
                return true;
            }
            if ($myEvaluation['courseid'] == $courseid) {
                return true;
            }
        }
    }
    return false;
}

function evaluation_is_my_courseid($myEvaluations, $courseid) {
    global $USER;
    foreach ($myEvaluations as $myEvaluation) {
        if ($myEvaluation['courseid'] == $courseid) {
            return true;
        }
    }
    return false;
}

function array_merge_recursive_distinct(array &$array1, array &$array2) {
    $merged = $array1;

    foreach ($array2 as $key => &$value) {
        if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
            $merged [$key] = array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged [$key] = $value;
        }
    }

    return $merged;
}

// get all participants of evaluation by various filters. Count teachers and students in all participating courses of given evaluation
 function get_evaluation_participants($evaluation, $userid = false, $courseid = false, $getTeachers = false,
         $getStudents = false, $CoSfilter = false) {
    global $DB, $USER;
    if ($userid > 0) {
        // $user = core_user::get_user($userid);
        $user = $DB->get_record("user", array('id' => $userid), '*');
    } else {
        $user = false;
    }
    //print "<br><hr>Userid: $userid - ".( $userid ?"yes":"no")."<br>\n";

    if (!isset($_SESSION["possible_evaluations"]) or !is_array($_SESSION["possible_evaluations"])) {
        $_SESSION["possible_evaluations"] = $_SESSION["possible_active_evaluations"] = array();
    }

    // get semester of Evaluation
    $evaluation_semester = get_evaluation_semester($evaluation);
    $evaluation_is_open = (evaluation_is_open($evaluation) or $evaluation->timeopen > time());
    //$from_course = ($evaluation_is_open OR $evaluation->timeopen > time());
    //$from_course = true;
    $total_evaluation_days = total_evaluation_days($evaluation);
    $timeopen = ($evaluation->timeopen > 0 AND $evaluation->timeopen<time()) ? $evaluation->timeopen : (time() - (30*80600));
    $timeclose = ($evaluation->timeclose > 0) ? $evaluation->timeclose : (time() + 80600);
    $loopcnt_courses = $loopcnt_empty_courses = $loopcnt_students = $loopcnt_teachers = $loopcnt_students_active = $loopcnt_teachers_active = 0;
    $distinct_s_active = $distinct_s = $distinct_t_active = $distinct_t = $courseTeachers = array();
    $my_evaluation_courses = $my_evaluation_users = $ids = array();
    $fcourses = "WHERE true";
    $ids = array();
    $filter = " AND RIGHT(idnumber,5) = '$evaluation_semester' ";

    if (!empty($evaluation->course) AND $evaluation->course !== SITEID) {
        $filter = " AND id=$evaluation->course";
    } else if ($courseid AND is_numeric($courseid)){
        $filter = " AND id=$courseid";
    } else if ($userid and $userid > 0) {    //$ids = evaluation_participating_courses($evaluation, $userid );
        $ids = evaluation_is_user_enrolled($evaluation, $userid);
        if (safeCount($ids) > 0) {
            $filter = "";
            $fcourses = "WHERE id IN (" . implode(",", $ids) . ")";
        } else {
            return $my_evaluation_courses;
        }
    }
    $courses = $DB->get_records_sql("SELECT id,idnumber,fullname,shortname from {course} $fcourses AND visible=1 $filter");
    $roleT =
            $DB->get_records_sql("SELECT * FROM {role} WHERE id IN (3,4,12,5);"); // get only editingteachers and students //(3,4,5,9,12)

    if (!$evaluation_is_open) {
        $filter = ($courseid and $filter) ? " AND courseid=$courseid" : "";
        $fcourses = str_replace("id IN", "courseid IN", $fcourses);
        $ev_courses = $DB->get_records_sql("SELECT DISTINCT ON (courseid) id as enrolid, courseid as id, fullname, shortname, teacherids
											FROM {evaluation_enrolments} $fcourses $filter AND evaluation = $evaluation->id");
        array_merge_recursive_distinct($courses, $ev_courses);
    }

    foreach ($courses as $course) {
        $numTeachersCourse = $numStudentsCourse = $numTeachersActiveCourse = $numStudentsActiveCourse = 0;
        $courseid = $course->id;
        if ($CoSfilter ){
            if (!isset($_SESSION['CoS_privileged'])) {
                ev_set_privileged_users();
                get_evaluation_filters($evaluation);
            }
            if (is_array($_SESSION['CoS_privileged'][$USER->username])
                    AND !in_array(evaluation_get_course_of_studies($courseid),
                    $_SESSION['CoS_privileged'][$USER->username])){
                // print "<hr>Course: $courseid - in_array($cos,.".$_SESSION['CoS_privileged'][$user->username].")<hr>";
                continue;
            }
        }

        if (empty($_SESSION["allteachers"][$courseid])) {
            evaluation_get_course_teachers($courseid);
        }

        if (isset($course->idnumber) and !empty($course->idnumber)) //$from_course )
        {    //if ( $evaluation->course == SITEID AND substr( $course->idnumber, -5) !== $evaluation_semester )
            //{	continue; }
            if (!pass_evaluation_filters($evaluation, $course->id)) {
                continue;
            }
        }

        // full filtering
        list($show, $reminder) =
                evaluation_filter_Evaluation($course->id, $evaluation, $user); //($userid AND $userid > 0 ?$user :false) );
        if (!$show) {
            continue;
        }

        $loopcnt_courses++;

        if (evaluation_is_empty_course($course->id)) {
            $loopcnt_empty_courses++;
        }

        //$contextC = get_context_instance(CONTEXT_COURSE, $course->id);
        $contextC = context_course::instance($course->id);
        if (!is_array($contextC) AND !is_object($contextC)){
            continue;
        }
        // only used when open!
        if ( $evaluation_is_open) {
            // $loopcnt=0;
            foreach ($roleT as $role) {
                $rolesC = get_role_users($role->id, $contextC);
                foreach ($rolesC as $roleC) {
                    /* if ($loopcnt<1 AND is_siteadmin()){
                        print "<hr>RolesC:\n" .nl2br(var_export($roleC, true));
                    }
                    $loopcnt++;
                    */

                    $lang = ev_get_user_language($userid);
                    $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                    if ($roleC->roleid == 5)  // student
                    {
                        $loopcnt_students++;
                        $numStudentsCourse++;
                        $distinct_s[$roleC->id] = $fullname; //=$loopcnt_students;
                        // get active students
                        if ($roleC->lastaccess > $timeopen) {
                            $roleC->lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "student", $courseid);
                            $loopcnt_students_active++;
                            $numStudentsActiveCourse++;
                            $distinct_s_active[$roleC->id] = $fullname;
                        }

                        if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                            $my_evaluation_courses[$course->id] = array("role" => "student", "id" => $roleC->id,
                                    "username" => $roleC->username,
                                    "email" => $roleC->email, "fullname" => $fullname, "courseid" => $course->id,
                                    "course" => $course->fullname, "shortname" => $course->shortname,
                                    "lastaccess" => $roleC->lastaccess, "language" => $lang,
                                    "teachers" => $_SESSION["allteachers"][$course->id], "reminder" => $reminder);
                        } else if ($getStudents) {
                            $my_evaluation_users[$roleC->id] =
                                    array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username,
                                            "email" => $roleC->email, "firstname" => $roleC->firstname,
                                            "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                            "language" => $lang, "role" => "student", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                        }

                }
                else {
                        $loopcnt_teachers++;
                        $numTeachersCourse++;
                        $distinct_t[$roleC->id] = $fullname;
                        // get active teachers
                        if ($roleC->lastaccess > $timeopen) {
                            $roleC->lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "teacher", $courseid);
                            $loopcnt_teachers_active++;
                            $numTeachersActiveCourse++;
                            $distinct_t_active[$roleC->id] = $fullname;
                        }
                        if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                            $my_evaluation_courses[$course->id] =
                                    array("role" => "teacher", "id" => $roleC->id, "username" => $roleC->username,
                                            "email" => $roleC->email, "fullname" => $fullname, "courseid" => $course->id,
                                            "course" => $course->fullname, "shortname" => $course->shortname,
                                            "teachers" => $_SESSION["allteachers"][$course->id], "lastaccess" => $roleC->lastaccess,
                                            "language" => $lang, "reminder" => $reminder);
                        } else if ($getTeachers) {
                            $my_evaluation_users[$roleC->id] =
                                    array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username,
                                            "email" => $roleC->email, "firstname" => $roleC->firstname,
                                            "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                            "language" => $lang, "role" => "teacher", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                        }
                    }
                }
            }
        }

        if (!$evaluation_is_open){    // get students
            $rolesC = $DB->get_records_sql("SELECT evul.id AS evulid, evul.userid AS id, evu.id AS evuid, evu.username,
													evu.firstname, evu.lastname, evu.alternatename, evu.email, evul.lastaccess
												FROM {evaluation_users_la} AS evul, {evaluation_users} AS evu
												WHERE evul.evaluation = $evaluation->id AND evul.role='student' AND evul.courseids LIKE '%$courseid%'
												AND evul.userid=evu.userid");

            // print "<hr>Students rolesC: ".nl2br(var_export($rolesC,true)) ."<hr>";
            // $loopcnt = 0;
            foreach ($rolesC as $roleC) {
                /* if ($loopcnt<1 AND is_siteadmin()){
                    print "<hr>roleC:\n" .nl2br(var_export($roleC, true));
                }
                */
                $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                if (!isset($distinct_s[$roleC->id])) {
                    $loopcnt_students++;
                    $numStudentsCourse++;
                }
                $distinct_s[$roleC->id] = $fullname; //=$loopcnt_students;
                // get active students
                if (!isset($distinct_s_active[$roleC->id])
                        AND $lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "student", $courseid)) {
                    $roleC->lastaccess = $lastaccess;
                    $loopcnt_students_active++;
                    $numStudentsActiveCourse++;
                    $distinct_s_active[$roleC->id] = $_SESSION["active_student"][$roleC->id] = $fullname;
                }

                if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                    $my_evaluation_courses[$course->id] =
                            array("role" => "student", "id" => $roleC->id, "username" => $roleC->username, "language" => $lang,
                                    "email" => $roleC->email, "fullname" => $fullname, "courseid" => $course->id,
                                    "course" => $course->fullname, "shortname" => $course->shortname,
                                    "teachers" => $_SESSION["allteachers"][$course->id], "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                } else if ($getStudents) {
                    $my_evaluation_users[$roleC->id] =
                            array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username, "language" => $lang,
                                    "email" => $roleC->email, "firstname" => $roleC->firstname,
                                    "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename, "language" => $lang,
                                    "role" => "student", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                }
            }
            // get teachers
            $rolesC = $DB->get_records_sql("SELECT evul.id AS evulid, evul.userid AS id, evu.id AS evuid, evu.username,
													evu.firstname, evu.lastname, evu.alternatename, evu.email, evul.lastaccess
												FROM {evaluation_users_la} AS evul, {evaluation_users} AS evu
												WHERE evul.evaluation = $evaluation->id AND evul.role='teacher' AND evul.courseids LIKE '%$courseid%'
												AND evul.userid=evu.userid");

            //print "<hr>Teachers rolesC: ".nl2br(var_export($rolesC,true)) ."<hr>";
            foreach ($rolesC as $roleC) {
                $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                if (!isset($distinct_t[$roleC->id])) {
                    $loopcnt_teachers++;
                    $numTeachersCourse++;
                }
                $distinct_t[$roleC->id] = $fullname; //=$loopcnt_teac
                // get inactive teachers
                if ($roleC->lastaccess > $timeopen and !isset($distinct_t_active[$roleC->id])
                        AND $lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "teacher", $courseid)
                ) {
                    $roleC->lastaccess = $lastaccess;
                    $loopcnt_teachers_active++;
                    $numTeachersActiveCourse++;
                    $distinct_t_active[$roleC->id] = $_SESSION["active_teacher"][$roleC->id] = $fullname;
                }
                if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                    $my_evaluation_courses[$course->id] =
                            array("courseid" => $course->id, "role" => "teacher", "id" => $roleC->id, "language" => $lang,
                                    "username" => $roleC->username, "email" => $roleC->email, "fullname" => $fullname,
                                    "course" => $course->fullname, "shortname" => $course->shortname,
                                    "teachers" => $_SESSION["allteachers"][$course->id],"lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                } else if ($getTeachers) {
                    $my_evaluation_users[$roleC->id] =
                            array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username, "language" => $lang,
                                    "email" => $roleC->email, "firstname" => $roleC->firstname,
                                    "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                    "role" => "teacher", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                }

            }
        } // from evc$numTeachersCourse++;
        // if ( !$getTeachers AND !$getStudents){
        $_SESSION["possible_evaluations"][$courseid]
                = $_SESSION["possible_active_evaluations"][$courseid] = 0;
        if ($numTeachersCourse) {
            $_SESSION["possible_evaluations"][$courseid]
                    = ($numStudentsCourse * ($evaluation->teamteaching ? $numTeachersCourse : 1));
        }
        if ($numTeachersActiveCourse) {
            $_SESSION["possible_active_evaluations"][$courseid]
                    = ($numStudentsActiveCourse *
                    ($evaluation->teamteaching ? $numTeachersActiveCourse : 1));
        }
        //}
    }
    $_SESSION["distinct_s"] = safeCount($distinct_s);
    if ($userid) {
        uasort($my_evaluation_courses, function($a, $b) {
            return strcmp($a['courseid'], $b['courseid']);
        });
        return $my_evaluation_courses;
    } else if ($getStudents || $getTeachers) {
        uasort($my_evaluation_users, function($a, $b) {
            return strcmp($a['lastname'], $b['lastname']);
        });
        return $my_evaluation_users;
    } else {
        return array($loopcnt_courses, $loopcnt_empty_courses,
                safeCount($distinct_s), safeCount($distinct_s_active),
                $loopcnt_students, $loopcnt_students_active,
                safeCount($distinct_t), safeCount($distinct_t_active),
                $loopcnt_teachers, $loopcnt_teachers_active);
    }
}

function ev_get_participants($evaluation, $myEvaluations, $courseid = false) {
    $possible_evaluations = 0;
    if ( evaluation_is_closed($evaluation) ) {
        possible_evaluations($evaluation);
    }
    if (!$courseid AND safeCount($_SESSION["possible_evaluations"])){
        $possible_evaluations = array_sum($_SESSION["possible_evaluations"]);
    } else {
        foreach ($myEvaluations as $id => $course) {
            if ($courseid and $id != $courseid) {
                continue;
            }
            if (isset($_SESSION["possible_evaluations"][$id])) {
                $possible_evaluations += $_SESSION["possible_evaluations"][$id];
            }
            if ($courseid and $id == $courseid) {
                break;
            }
        }
    }
    return $possible_evaluations;
}

// show current user all participating courses
function show_user_evaluation_courses($evaluation, $myEvaluations, $cmid = false, $showCounter = false, $showName = false,
        $userResults = true, $sortby = "course") {
    global $CFG, $DB, $USER;
    $wwwroot = $CFG->wwwroot;
    $str = "";
    if (empty($cmid)) {
        $cmid = get_evaluation_cmid_from_id($evaluation);
    }
    $myCourseReplies = $possible_evaluations = $possible_evaluations_per_teacher = 0;
    $evaluation_is_open = evaluation_is_open($evaluation); //  OR date("Ymd",$evaluation->timeopen) < date("Ymd");
    $min_results = evaluation_min_results($evaluation);
    $minresultsPriv = min_results_priv($evaluation);
    $privGlobalUser = (isset($_SESSION["privileged_global_users"][$USER->username])
            ?!empty($_SESSION["privileged_global_users"][$USER->username]) :false);
    if ($privGlobalUser) {
        $min_results = $minresultsPriv;
    }

    $min_resTitle = ' title="' . get_string('min_results', 'evaluation', $min_results) . '" ';
    $numCourses = array();
    if (!safeCount($myEvaluations)) {
        if (!defined('EVALUATION_OWNER') and $evaluation->timeclose < time()) {
            $str .= "<p style=\"color:red;font-weight:bold;align:center;\">"
                    . ev_get_string('non_of_your_courses_participated') . "</p>\n";
            return $str;
        }
    }
    // sorting by replies not yet working
    if (!isset($_SESSION["orderBy"])) {
        $_SESSION["orderBy"] = "ASC";
    } else {
        $_SESSION["orderBy"] = ($_SESSION["orderBy"] == "ASC" ? "DESC" : "ASC");
    }

    if ($sortby == "course") {
        uasort($myEvaluations, function($a, $b) {
            return strcoll(strtoupper($a["course"]), strtoupper($b["course"]));
        });
    } else {
        uasort($myEvaluations, function($a, $b) {
            return strcoll(strtoupper($a["course"]), strtoupper($b["course"]));
        });
    }
    $str .= "\n<style>th, td { padding-right: 5px;vertical-align:top;}</style>\n";
    foreach ($myEvaluations as $myEvaluation) {
        $str .= "<h2>" . ($showName ? "<b>" . $myEvaluation["fullname"] . "</b>:" . ($userResults ? " " .ev_get_string('your') : "") : "")
                . " " .ev_get_string('courses_of') ." "
                . ev_get_tr($evaluation->name) . "</h2>\n";
        if ($myEvaluation["role"] != "teacher") {
            $num_courses = safeCount(ev_courses_of_id($evaluation, $myEvaluation["id"]));
            if (!$evaluation_is_open and $num_courses > safeCount($myEvaluations)) {
                // $ismycourses = ($myEvaluation["id"]==$USER->id);
                $isstudent = ($myEvaluation["role"] == 'student' and $myEvaluation["id"] == $USER->id);
                $a = new stdClass();
                $a->num_courses = $num_courses;
                $str .= "\n<b>" . ev_get_string('note') . "</b>: ";
                if ($isstudent){
                    $str .= ev_get_string('show_evaluated_courses_student');
                } else{
                    $str .= ev_get_string('show_evaluated_courses_teacher');
                }
                $str .= ev_get_string('num_courses_in_ev',$a) . "<br>\n";
            }

        }
        break;
    }
    $str .= "<table>\n";
    foreach ($myEvaluations as $myEvaluation) {
        $evaluation_has_user_participated = true;
        $numCourses[$myEvaluation["courseid"]] = $myEvaluation["courseid"];
        evaluation_get_course_teachers($myEvaluation['courseid']);
        $teachers = $_SESSION["allteachers"][$myEvaluation['courseid']];
        $ev_get_participantsc = ev_get_participants($evaluation, $myEvaluations, $myEvaluation["courseid"]);
        if (safeCount($teachers)) {
            $possible_evaluations_per_teacher += round($ev_get_participantsc / safeCount($teachers), 0);
        }
        $possible_evaluations += round($ev_get_participantsc, 0);
        $actionTxt = get_string("evaluate_now", "evaluation");
        $color = "darkred";
        $min_resInfo = $min_resTitle;
        $isTeacher = ($myEvaluation["role"] == "teacher");
        $teacherid = false;
        if ($userResults and $isTeacher) //$evaluation->teamteaching AND
        {
            $replies = evaluation_countCourseEvaluations($evaluation, $myEvaluation["courseid"],
                    $myEvaluation["role"], $myEvaluation["id"]);
            $teacherid = $myEvaluation["id"]; //$USER->id;
        } else {
            $replies = evaluation_countCourseEvaluations($evaluation, $myEvaluation["courseid"]);
            ///$userResults = false;
        }
        $myCourseReplies += $replies;
        // complete Evaluation
        $urlF = "<a href=\"$wwwroot/mod/evaluation/complete.php?id=$cmid&courseid=" . $myEvaluation["courseid"] . "\">";
        if (!$isTeacher) {
            $evaluation_has_user_participated =
                    evaluation_has_user_participated($evaluation, $USER->id, $myEvaluation["courseid"]);
        }

        if (!$evaluation_is_open or $isTeacher or
                stristr($myEvaluation["reminder"], ev_get_string("analysis"))) {
            $color = "grey";
            $actionTxt = get_string("analysis", "evaluation");
            $statTxt = get_string("statistics", "evaluation");
            // link to Evaluation Overview
            $urlF = "<a href=\"$wwwroot/mod/evaluation/view.php?id=$cmid&courseid=" . $myEvaluation["courseid"] . "\">";
            if ($replies >= $min_results and $evaluation_has_user_participated) {
                $color = "darkgreen";
                $min_resInfo = "";
                // see graphic results
                $urlF = "<a href=\"$wwwroot/mod/evaluation/analysis_course.php?id=$cmid&courseid=" . $myEvaluation["courseid"]
                        . (($isTeacher and $userResults) ? "&teacherid=" . $myEvaluation["id"] : "") . '" target="ev_results">';
                $urlStats = "<a href=\"$wwwroot/mod/evaluation/print.php?showCompare=1&id=$cmid&courseid=" . $myEvaluation["courseid"]
                        .  '" target="ev_results">';

            }
            if (empty($_SESSION["LoggedInAs"])) {
                $urlC = "<a href=\"$wwwroot/course/view.php?id=" . $myEvaluation["courseid"] . "\">";
            } else {
                $urlC = "<a href=\"#\">";
                // print "<hr>Reminder: ".$myEvaluation["reminder"]." - " .ev_get_string("analysis") ."<hr>";
            }
            $str .= "<tr>\n";
            $str .= "<td $min_resInfo>$urlF<b style=\"color:$color;\">$actionTxt</b></a></td>\n";
            $str .= "<td $min_resInfo>";
            if (empty($min_resInfo)){
                $str .= $urlStats."<b style=\"color:$color;\">$statTxt</b></a>";
            }
            else{
                $str .= $urlF."<b style=\"color:$color;\">$statTxt</b></a>";
            }
            $str .= "</td>\n<td style=\"text-align:right;\">" . $replies . "</td>\n";
            if (empty($_SESSION["LoggedInAs"])) {
                $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></a></td>\n";
            } else {
                $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></td>\n";
            }
            $str .= "</tr>\n";
        } else {
            $replies = "";
            $loopcnt = 0;
            if ($evaluation->teamteaching) {    // get course teachers
                foreach ($teachers as $teacher) {
                    $Txt = $actionTxt;
                    $url = $urlF;
                    $str .= "<tr>\n";
                    $completed = $DB->get_record_sql("select id,evaluation,courseid,userid,teacherid from {evaluation_completed} 
                                    WHERE evaluation=" . $evaluation->id . " AND userid=" . $myEvaluation["id"]
                            . " AND courseid=" . $myEvaluation['courseid'] . " AND teacherid=" . $teacher['id'], null,IGNORE_MULTIPLE);
                    //$str .= "$actionTxt";
                    if ($completed and isset($completed->teacherid) and $completed->teacherid == $teacher['id']) {
                        $color = "green";
                        $Txt = '<span style="font-weight:normal;color:$color;">'
                                . ev_get_string('submitted_for') . "<br>" . ev_get_string('teacher')
                                . " " . $teacher['fullname'] . "</span>";
                        $str .= "<td><b style=\"color:$color;\">$Txt</b></td>";
                    } else {
                        $color = "darkred";
                        $Txt .= '<span style="font-weight:normal;color:black;"><br>' . ev_get_string('teacher')
                                .' ' . $teacher['fullname'] . "</span>";
                        $url = "<a href=\"$wwwroot/mod/evaluation/complete.php?id=$cmid&courseid=" . $myEvaluation["courseid"] .
                                "&teacherid=" . $teacher['id'] . '">';
                        $str .= "<td>$url<b style=\"color:$color;\">$Txt</b></a></td>";
                    }
                    if (empty($_SESSION["LoggedInAs"])) {
                        $urlC = "<a href=\"$wwwroot/course/view.php?id=" . $myEvaluation["courseid"] . "\">";
                    } else {
                        $urlC = "<a href=\"#\">";
                    }
                    $str .= "<td>&nbsp;</td>";
                    $str .= "<td style=\"text-align:right;\">" . $replies . "</td>";
                    if (empty($_SESSION["LoggedInAs"])) {
                        $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></a></td>\n";
                    } else {
                        $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></td>\n";
                    }
                    $str .= "</tr>\n";
                    $loopcnt++;
                }
            } else {
                $Txt = $actionTxt;
                $url = $urlF;
                $str .= "<tr>\n";
                $completed = $DB->get_record_sql("select id,evaluation,courseid,userid,teacherid from {evaluation_completed} 
                            WHERE evaluation=" . $evaluation->id . " AND userid=" . $myEvaluation["id"]
                        . " AND courseid=" . $myEvaluation['courseid'], null,IGNORE_MULTIPLE);
                if (isset($completed->teacherid) and safeCount($completed)) {
                    $color = "green";
                    $Txt = '<span style="font-weight:normal;color:' . $color . ';">' .ev_get_string('evaluated'). 'Abgegeben</span>';
                    $str .= "<td><b style=\"color:$color;\">$Txt</b></td>";
                } else {
                    $Txt .= '<span style="font-weight:normal;color:black;">' .ev_get_string('to_evaluate')
                            .'</span>';
                    $url = "<a href=\"$wwwroot/mod/evaluation/complete.php?id=$cmid&courseid=" . $myEvaluation["courseid"] .
                            '">';
                    $str .= "<td>$url<b style=\"color:$color;\">$Txt</b></a></td>";
                }
                if (empty($_SESSION["LoggedInAs"])) {
                    $urlC = "<a href=\"$wwwroot/course/view.php?id=" . $myEvaluation["courseid"] . "\">";
                } else {
                    $urlC = "<a href=\"#\">";
                }
                $str .= "<td>&nbsp;</td>";
                $str .= "<td style=\"text-align:right;\">" . $replies . "</td>";
                if (empty($_SESSION["LoggedInAs"])) {
                    $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></a></td>\n";
                } else {
                    $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></td>\n";
                }
                $str .= "</tr>\n";
            }
        }
    }
    $str .= "</table>\n";
    $possible_evaluations_txt = "";
    $maxevaluations = ($userResults ? $possible_evaluations_per_teacher : $possible_evaluations);
    if ($isTeacher) {
        $possible_evaluations_txt = "/" . $maxevaluations;
        if ($maxevaluations && $myCourseReplies){
            $possible_evaluations_txt .=  evaluation_calc_perc($myCourseReplies, $maxevaluations);
        }
    }
    if ($myCourseReplies > 0 and $showCounter) {
        $str .= "<b style=\"color:darkgreen;\">" . ev_get_string('completed_evaluations')
                . (($isTeacher and $userResults) ? " " .ev_get_string('for_you') : "")
                . ($userResults ? " " . ev_get_string('in_your_courses') :"")
                . ": $myCourseReplies$possible_evaluations_txt</b>";
    }
    $str .= "<p> </p>";


    return $str;
}



// show list of courses with evaluation results, sorted by results or course names
function showEvaluationCourseResults($evaluation, $showMin = 3, $sortBy = "fullname", $id = false, $courseid = false,
        $teacherid = false) {
    global $DB, $USER;
    if (!$id) {
        $id = get_evaluation_cmid_from_id($evaluation);
    }

    // handle CoS privileged User
    $cosPrivileged_filter = evaluation_get_cosPrivileged_filter($evaluation,"completed");
    //$completed_responses = evaluation_countCourseEvaluations( $evaluation );
    $evaluationstructure = new mod_evaluation_structure($evaluation, false, null, $courseid, null, 0, $teacherid);
    $completed_responses = $evaluationstructure->count_completed_responses();
    // if ( $courseid AND !isset($_SESSION['allteachers'][$courseid])) {
    evaluation_get_all_teachers($evaluation);
    // }

    if (!isset($_SESSION["orderBy"])) {
        $_SESSION["orderBy"] = "ASC";
    } else {
        $_SESSION["orderBy"] = ($_SESSION["orderBy"] == "ASC" ? "DESC" : "ASC");
    }
    $minResults = evaluation_min_results($evaluation);
    // $minResultsText = min_results_text($evaluation);
    $minResultsPriv = min_results_priv($evaluation);
    $privGlobalUser = (is_siteadmin() OR isset($_SESSION["privileged_global_users"][$USER->username]));
    if ($privGlobalUser) {
        $minResults = $minResultsPriv;
    }
    print "<style>td { border: 1px solid #ddd;padding:8px;}</style>";
    $empty_courses = $listed_courses = $listed_empty_courses = $noteacher_courses = $nostudent_courses = 0;
    $notevaluated = false;

    if (isset($_SESSION["notevaluated"]) and $_SESSION["notevaluated"]) {
        $notevaluated = true;
        $ids = evaluation_participating_courses($evaluation);
        $evaluated =
                $DB->get_records_sql("SELECT distinct courseid FROM mdl_evaluation_completed AS completed WHERE evaluation=$evaluation->id $cosPrivileged_filter ORDER BY courseid");
        foreach ($evaluated as $course) {
            if (isset($ids[$course->courseid])) {
                unset($ids[$course->courseid]);
            }
        }
        if (!empty($ids)) {
            $courses = implode(",", $ids);
            $query = "SELECT c.id, c.fullname, c.shortname
					FROM {course} AS c
					WHERE c.id IN ($courses)
					ORDER BY c.fullname " . $_SESSION["orderBy"];
            //print var_export($ids,true);cosPrivileged_filter
            //print "<br>Query: $query<br>";exit;
            $results = $DB->get_records_sql($query);
        } else {
            $results = array();
        }
    } else {
        $query = "SELECT c.id, c.fullname, c.shortname, count(completed.courseid) AS evaluations
				FROM {evaluation_completed} AS completed
				LEFT JOIN {course} AS c ON c.id = completed.courseid
				WHERE evaluation= :evid AND coalesce(c.fullname, '') != ''" . $cosPrivileged_filter . "
				GROUP BY c.id, c.fullname, c.shortname
				ORDER BY c.fullname " . $_SESSION["orderBy"];

        //print "$query<br>";
        // get all Results
        if ($cosPrivileged_filter) {
            $allResults = safeCount($DB->get_records_sql("SELECT  c.id, c.fullname, c.shortname, 
                        count(completed.courseid) AS evaluations 
						FROM {evaluation_completed} AS completed
						LEFT JOIN {course} AS c ON c.id = completed.courseid
						WHERE evaluation=$evaluation->id AND coalesce(c.fullname, '') != '' 
						GROUP BY c.id, c.fullname, c.shortname
						ORDER BY c.fullname " . $_SESSION["orderBy"]));
        }
        // max 1 million results to fetch
        $results = $DB->get_records_sql($query, array("evid" => $evaluation->id), 0, 1000000);
    }
    $evaluatedCourses=safeCount($results);
    foreach ($results as $key => $result) {
        if (empty($notevaluated) and $result->evaluations < $showMin) {
            unset($results[$key]);
            continue;
        }
        if (!isset($_SESSION["allteachers"][$result->id])) {
            evaluation_get_course_teachers($result->id);
        }
        if ( !isset($_SESSION["allteachers"][$result->id])) {
            evaluation_get_course_teachers($result->id);
        }
        $results[$key]->numTeachers = safeCount($_SESSION["allteachers"][$result->id]);
        $results[$key]->numStudents = evaluation_count_students($evaluation, $result->id);
        $results[$key]->emptyCourse = evaluation_is_empty_course($result->id);
    }

    $_SESSION["sortBy"] = $sortBy;
    if (!empty($results) and $sortBy) {    // $locale = setlocale(LC_ALL, 'de_DE');
        if ($_SESSION["orderBy"] == "ASC") {
            uasort($results,
                    function($a, $b) {
                        if ($_SESSION["sortBy"] == "fullname") {
                            return strnatcasecmp($a->{$_SESSION["sortBy"]}, $b->{$_SESSION["sortBy"]});
                        }
                        if ($a->{$_SESSION["sortBy"]} == $b->{$_SESSION["sortBy"]}) {
                            return 0;
                        }
                        return ($a->{$_SESSION["sortBy"]} < $b->{$_SESSION["sortBy"]} ? -1 : 1);
                    });
        } else {
            uasort($results,
                    function($a, $b) {
                        if ($_SESSION["sortBy"] == "fullname") {
                            return !strnatcasecmp($a->{$_SESSION["sortBy"]}, $b->{$_SESSION["sortBy"]});
                        }
                        if ($a->{$_SESSION["sortBy"]} == $b->{$_SESSION["sortBy"]}) {
                            return 0;
                        }
                        return ($a->{$_SESSION["sortBy"]} > $b->{$_SESSION["sortBy"]} ? -1 : 1);
                    });
        }
        // setlocale(LC_ALL, $locale);
    }
    unset($_SESSION["sortBy"]);

    //{	return strcmp($a->course, $b->course);});
    //print print_r($results)."<br><hr>";
    $return_to = (isset($_REQUEST["goBack"]) ? $_REQUEST["goBack"] : "analysis_course");
    $goBack = html_writer::tag('a',  ev_get_string('back'),
            array('class' => "d-print-none", 'style' => 'font-size:125%;color:white;background-color:black;text-align:right;',
                    'type' => 'button', 'href' => $return_to . '.php?id=' . $id . '&courseid=' . $courseid));
    print $goBack;
    echo evPrintButton();
    // handle CoS priveleged user
    if (!empty($_SESSION['CoS_privileged'][$USER->username])) {
        print "<p>Abgaben aller <b>Kurse</b> der Studiengänge: " . '<span style="font-weight:600;white-space:pre-line;">'
                . implode(", ", $_SESSION['CoS_privileged'][$USER->username]) . "</span></p>\n";
    }
    ?>
    <p>
    <form style="display:inline;line-break:inline;" method="POST" action="print.php">
        <input type="submit" style="font-size:100%;color:white;background-color:black;" value="Alle Kurse mit mindestens ">
        <input type="number" name="showResults" value="<?php echo $showMin; ?>"
               min="<?php echo ($privGlobalUser?1:$minResults);?>"
               style="font-size:100%;color:white;background-color:teal;">
        <input type="hidden" name="sortBy" value="<?php echo $sortBy; ?>">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
        <input type="hidden" name="id" value="<? echo $id; ?>">
        <input type="hidden" name="goBack" value="<? echo $return_to; ?>">
        <input type="submit" style="font-size:100%;color:white;background-color:black;" value="<?php echo ev_get_string('submissions');?>">
        <br>
        <input type="submit" name="notevaluated" style="font-size:100%;color:white;background-color:black;"
               value="Kurse ohne Abgaben">
    </form>

    <form style="display:inline;line-break:inline;" method="POST" action="print.php">
        <button name="showCompare" style="font-size:100%;color:white;background-color:black;" value="1"
                onclick="this.form.submit();"><?php
            echo "Statistik mit Vergleich"; ?></button>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
        <!--input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID; ?>"-->
        <input type="hidden" name="teacherid" value="<?php echo $teacherid; ?>">
    </form>
    </p>
    <?php
    $output = $header = $lines = "";
    $sumTTC = 0;
    $params = "$id&courseid=$courseid&showResults=$showMin&goBack=$return_to";
    if ($notevaluated) {
        $params .= "&notevaluated=Kurse ohne Abgaben";
        $output .= '<table style="border:1px;border-collapse: collapse;"><tr style="font-weight:bolder;">
				<td><a href="?id=' . $params . '&sortBy=emptyCourse"><span style="color:teal;" title="Leere Kurse haben keine Kursinhalte">Leer</span></a></td>
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=numTeachers"><span style="color:teal;">Doz</span></a></td>
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=numStudents"><span style="color:teal;">Stud</span></a></td>			
				<td><a href="?id=' . $params . '&sortBy=fullname"><span style="color:teal;">Kurs</span></a></td>
				<td>Kurzname</td>
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=id"><span style="color:teal;">ID</span></a></td>
				<td>Studiengang</td></tr>';
        foreach ($results as $result) {
            $Studiengang = evaluation_get_course_of_studies($result->id, true);  // get Studiengang with link
            $isEmptyCourse = $result->emptyCourse;
            if ($isEmptyCourse) {
                $empty_courses++;
            }
            if (false) //$cosPrivileged_filter AND evaluation_debug( false ) )
            {
                print "<br><hr>Studiengang: " . trim(strip_tags($Studiengang)) . " - _SESSION['CoS_privileged'][$USER->username]: "
                        . var_export($_SESSION['CoS_privileged'][$USER->username], true) . "<hr>\n";
            }
            if ($cosPrivileged_filter and !in_arrayi(trim(strip_tags($Studiengang)), $_SESSION['CoS_privileged'][$USER->username])) {
                continue;
            }
            $listed_courses++;
            $is_empty = "Nein";
            if ($isEmptyCourse) {
                $listed_empty_courses++;
                $is_empty = '<span style="color:red;font-weight:bold;">Ja</span>';
            }

            $courseL = '<a href="/course/view.php?id=' . $result->id . '" target="course">';
            $numTeachers = $result->numTeachers;
            $numStudents = $result->numStudents;
            if (!$numTeachers) {
                $noteacher_courses++;
                $numTeachers = '<span style="color:red;font-weight:bold;">0</span>';
            }
            if ($numTeachers>1){
                $sumTTC++;
            }
            if (!$numStudents) {
                $nostudent_courses++;
                $numStudents = '<span style="color:red;font-weight:bold;">0</span>';
            }

            $output .= '<tr><td>' . $is_empty . '</td><td style="text-align:right;">' . $numTeachers .
                    '</td><td style="text-align:right;">'
                    . $numStudents . '</td><td>' . $courseL . '<span title="' . $result->fullname . '">'
                    . substr($result->fullname, 0, 60) . (strlen($result->fullname) > 60 ? '...' : '') . '</span></a></td><td>'
                    . $result->shortname . '</td><td style="text-align:right;">' . $result->id . "</td><td>" . $Studiengang .
                    "</td></tr>\n";
        }
        $output .= "</table>\n";
        $show_stats = "";
        if ($cosPrivileged_filter) {
            $show_stats .= '<b>Ausgewertete Kurse ohne Abgaben: ' . $listed_courses . '</b>' . "<br>\n";
            $show_stats .= '<b>Ausgewertete leere Kurse: ' . $listed_empty_courses . '</b>' . "<br>\n";
        }
        $show_stats .= '<b>Kurse ohne Abgaben: ' . safeCount($results) . '</b>' . "<br>\n";
        $show_stats .= '<b>Kurse ohne Dozent_innen: ' . $noteacher_courses . '</b>' . "<br>\n";
        $show_stats .= '<b>Kurse ohne Student_innen: ' . $nostudent_courses . '</b>' . "<br>\n";
        $show_stats .= '<b>Leere Kurse: ' . $empty_courses . '</b>' . "<br>\n";
        $show_stats .= '<b>Kurse mit Team Teaching: ' . $sumTTC . '</b>' . "<br>\n";
        print $show_stats;
        if ($listed_courses) {
            print $output;
        }
        if ($listed_courses > 15) {
            print $show_stats;
        }

    } else {
        $sort_sym = ($_SESSION["orderBy"] == "ASC" ? "&uarr;" : "&darr;");
        $Abgaben = "Abgaben" . ($sortBy == "evaluations" ? $sort_sym : "");
        $Doz = "Doz" . ($sortBy == "numTeachers" ? $sort_sym : "");
        $Stud = "Stud" . ($sortBy == "numStudents" ? $sort_sym : "");
        $Kurs = "Kurs" . ($sortBy == "fullname" ? $sort_sym : "");
        $ID = "ID" . ($sortBy == "id" ? $sort_sym : "");
        $sumR = $sumC = $sumTTC = $modus = $median = 0;
        $table = '<table style="border:1px;border-collapse: collapse;">';
        $header = '<tr style="font-weight:bolder;">
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=evaluations"><span style="color:teal;">' .
                $Abgaben . '</span></a></td>
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=numTeachers"><span style="color:teal;">' . $Doz . '</span></a></td>
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=numStudents"><span style="color:teal;">' . $Stud . '</span></a></td>
				<td><a href="?id=' . $params . '&sortBy=fullname"><span style="color:teal;">' . $Kurs . '</span></a></td><td>Kurzname</td>
				<td style="text-align:right;"><a href="?id=' . $params . '&sortBy=id"><span style="color:teal;">' . $ID . '</span></a></td>
				<td title="Leere Kurse haben keine Kursinhalte">Leer</td><td>Studiengang</td></tr>';
        foreach ($results as $result) {
            $isEmptyCourse = $result->emptyCourse;
            if ($isEmptyCourse) {
                $empty_courses++;
            }
            $is_empty = "Nein";
            if ($isEmptyCourse) {
                $listed_empty_courses++;
                $is_empty = '<span style="color:red;font-weight:bold;">Ja</span>';
            }
            $sumC++;
            $modus = max($result->evaluations, $modus);
            $evaluations[] = $result->evaluations;
            $sumR += $result->evaluations;
            $numTeachers = $result->numTeachers;
            if ($numTeachers>1){
                $sumTTC++;
            }
            $numStudents = $result->numStudents;
            if (!$numTeachers) {
                $noteacher_courses++;
                $numTeachers = '<span style="color:red;font-weight:bold;">0</span>';
            }
            if (!$numStudents) {
                $nostudent_courses++;
                $numStudents = '<span style="color:red;font-weight:bold;">0</span>';
            }

            $Studiengang = evaluation_get_course_of_studies($result->id, true);  // get Studiengang with link
            $courseL = '<a href="/course/view.php?id=' . $result->id . '" target="course">';
            $resultL = '<a href="analysis_course.php?id=' . $id . '&courseid=' . $result->id . '">';
            $compareLink = '<a href="print.php?showCompare=1&id=' . $id . '&courseid='
                    . $result->id . '" target="compare">' . $result->evaluations . '</a>';
            $lines .= '<tr><td style="text-align:right;">' . $compareLink
                    . '</td><td style="text-align:right;">' . $numTeachers
                    . '</td><td style="text-align:right;">' . $numStudents
                    . '</td><td>' . $resultL . '<span title="' . $result->fullname . '">'
                    . substr($result->fullname, 0, 60) . (strlen($result->fullname) > 60 ? '...' : '') . '</span></a></td><td>'
                    . $courseL . $result->shortname . '</a></td><td style="text-align:right;">'
                    . $result->id . "</td><td>$is_empty</td><td>" . $Studiengang . "</td></tr>\n";
        }
        if (empty($evaluations)) {
            print "<br><b style=\"color:red;\">Keine Kurse mit mindestens $showMin Abgaben!</b>";
        } else {
            sort($evaluations);
            $median = $evaluations[round($sumC / 2, 0)];
            $average = round($sumR / $sumC);
            if ($cosPrivileged_filter) {
                $topline = '<b>Anzahl aller Kurse mit Abgaben:</b></td><td colspan="2" style="text-align:right;"><b>'
                        . evaluation_number_format($allResults) . "</b>";
                $output .= '<tr><td colspan="3">' . $topline . "</td></tr>\n";
            }
            $topline = '<b>Abgaben aus allen Kursen:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($completed_responses) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline . "</td></tr>\n";
            $topline = '<b>Ausgewertete Kurse mit Abgaben:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($evaluatedCourses) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline . "</td></tr>\n";
            $percentage = evaluation_calc_perc($sumC,$evaluatedCourses);
            $topline = '<b>Ausgewertete Kurse mit mindestens ' . $showMin
                    . ' Abgaben '.$percentage.':</b></td><td colspan="2" style="text-align:right;"><b>' . evaluation_number_format($sumC) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline . "</td></tr>\n";
            $topline = '<b>Ausgewertete Kurse mit Team Teaching:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($sumTTC) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline . "</td></tr>\n";
            $output .= '<tr><td colspan="4"><b>Abgaben aus diesen ' . evaluation_number_format($sumC)
                    . ' Kursen:</b></td><td colspan="2" style="text-align:right;"><b>' . evaluation_number_format($sumR) .
                    "</b></td></tr>\n";
            $output .= '<tr><td colspan="4"><b>Abgaben/Kurs: Median:</b></td><td colspan="2" style="text-align:right;"><b>' .
                    evaluation_number_format($median) . "</b></td></tr>\n";
            $output .= '<tr><td colspan="4"><b>Abgaben/Kurs: Mittelwert:</b></td><td colspan="2" style="text-align:right;"><b>' .
                    evaluation_number_format($average) . "</b></td></tr>\n";
            $output .= '<tr><td colspan="4"><b>Abgaben/Kurs: Modus:</b></td><td colspan="2" style="text-align:right;"><b>' .
                    evaluation_number_format($modus) . "</b></td></tr>\n";
            if ($sumC >= 12) {
                $table .= $output;
            }

            print $table . $header . $lines;
            $topline = '<b>Kurse mit Team Teaching:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($sumTTC) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline . "</td></tr>\n";

            $output .= '<tr><td colspan="4"><b>Leere Kurse:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($empty_courses) . '</b>' . "</td></tr>\n";
            $output .= '<tr><td colspan="4"><b>Kurse ohne Dozent_innen:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($noteacher_courses) . '</b>' . "</td></tr>\n";
            $output .= '<tr><td colspan="4"><b>Kurse ohne Studierende:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($nostudent_courses) . '</b>' . "</td></tr>\n";
            print $output . "</table>\n";
        }
    }
    print "<br>$goBack;";
}

// show list of teachers with evaluation results, sorted by results or teacher names
function showEvaluationTeacherResults($evaluation, $showMin = 6, $sortBy = "lastname", $id = false, $courseid = false,
        $teacherid = false) {
    global $DB, $USER;
    if (!$id) {
        $id = get_evaluation_cmid_from_id($evaluation);
    }

    // handle CoS privileged User
    $cosPrivileged_filter = evaluation_get_cosPrivileged_filter($evaluation,"completed");

    //$completed_responses = evaluation_countCourseEvaluations( $evaluation );
    $evaluationstructure = new mod_evaluation_structure($evaluation, false, null, $courseid, null, 0, $teacherid);
    $completed_responses = $evaluationstructure->count_completed_responses();

    // if not called from form
    if (!isset($_POST["showResults"])) {
        if (!isset($_SESSION["orderBy"])) {
            $_SESSION["orderBy"] = "ASC";
        } else {
            $_SESSION["orderBy"] = ($_SESSION["orderBy"] == "ASC" ? "DESC" : "ASC");
        }
    }

    $minResults = evaluation_min_results($evaluation);
    // $minResultsText = min_results_text($evaluation);
    $minResultsPriv = min_results_priv($evaluation);
    $privGlobalUser = (is_siteadmin() OR isset($_SESSION["privileged_global_users"][$USER->username]));
    if ($privGlobalUser) {
        $minResults = $minResultsPriv;
    }

    print "<style>td { border: 1px solid #ddd;padding:8px;}</style>";

    $notevaluated = false;
    if (isset($_SESSION["notevaluated"]) and $_SESSION["notevaluated"]) {
        $notevaluated = true;
        $sessionName = "allteachers_" . $evaluation->id;
        if (!isset($_SESSION[$sessionName])) {
            $_SESSION[$sessionName] = get_evaluation_participants($evaluation, false, false, true, false);
        }
        $allteachers = $_SESSION[$sessionName];
        $evaluated =
                $DB->get_records_sql("SELECT distinct teacherid FROM mdl_evaluation_completed WHERE evaluation=$evaluation->id");
        //. $cosPrivileged_filter);
        $evaluated_teachers = 0;
        foreach ($evaluated as $teacher) {
            if (isset($allteachers[$teacher->teacherid])) {
                $evaluated_teachers++;
                unset($allteachers[$teacher->teacherid]);
            }
        }
        if (empty($allteachers)) {
            $results = array();
        }
    } else {
        $query = "SELECT u.id, u.firstname, u.lastname, count(completed.teacherid) AS evaluations
				FROM {evaluation_completed} AS completed
				LEFT JOIN {user} AS u ON u.id = completed.teacherid
				WHERE evaluation= :feedid  AND coalesce(u.lastname, '') != '' " . $cosPrivileged_filter . "
				GROUP BY u.id, u.firstname, u.lastname
				ORDER BY u.lastname " . $_SESSION["orderBy"];

        //print "$query<br>";
        // max 1 million results to fetch
        $results = $DB->get_records_sql($query, array("feedid" => $evaluation->id), 0, 1000000);
        if ($cosPrivileged_filter) {
            $allResults = safeCount($DB->get_records_sql("SELECT u.id, u.firstname, u.lastname, count(completed.teacherid) AS evaluations
				FROM {evaluation_completed} AS completed
				LEFT JOIN {user} AS u ON u.id = completed.teacherid
				WHERE evaluation=$evaluation->id  AND coalesce(u.lastname, '') != '' 
				GROUP BY u.id, u.firstname, u.lastname
				ORDER BY u.lastname " . $_SESSION["orderBy"]));
        }
        $_SESSION["sortBy"] = $sortBy;
        if (!empty($results) and $sortBy) {    // $locale = setlocale(LC_ALL, 'de_DE');
            if ($_SESSION["orderBy"] == "ASC") {
                uasort($results,
                        function($a, $b) {
                            if ($_SESSION["sortBy"] == "lastname") {
                                return strnatcasecmp($a->{$_SESSION["sortBy"]}, $b->{$_SESSION["sortBy"]});
                            }
                            if ($a->{$_SESSION["sortBy"]} == $b->{$_SESSION["sortBy"]}) {
                                return 0;
                            }
                            return ($a->{$_SESSION["sortBy"]} < $b->{$_SESSION["sortBy"]} ? -1 : 1);
                        });
            } else {
                uasort($results,
                        function($a, $b) {
                            if ($_SESSION["sortBy"] == "lastname") {
                                return !strnatcasecmp($a->{$_SESSION["sortBy"]}, $b->{$_SESSION["sortBy"]});
                            }
                            if ($a->{$_SESSION["sortBy"]} == $b->{$_SESSION["sortBy"]}) {
                                return 0;
                            }
                            return ($a->{$_SESSION["sortBy"]} > $b->{$_SESSION["sortBy"]} ? -1 : 1);
                        });
            }
            // setlocale(LC_ALL, $locale);
        }
        unset($_SESSION["sortBy"]);
    }
    //{	return strcmp($a->course, $b->course);});
    //print print_r($results)."<br><hr>";
    $return_to = (isset($_REQUEST["goBack"]) ? $_REQUEST["goBack"] : "analysis_course");
    $goBack = html_writer::tag('a', ev_get_string('back'),
            array('class' => "d-print-none", 'style' => 'font-size:125%;color:white;background-color:black;text-align:right;',
                    'type' => 'button',
                    'href' => $return_to . '.php?id=' . $id . '&courseid=' . $courseid . '&teacherid=' . $courseid));
    print $goBack;
    echo evPrintButton();

    // handle CoS priveleged user
    if (!empty($_SESSION['CoS_privileged'][$USER->username])) {
        print "<p>Abgaben aller <b>Dozent_innen</b> der Studiengänge: " . '<span style="font-weight:600;white-space:pre-line;">'
                . implode(", ", $_SESSION['CoS_privileged'][$USER->username]) . "</span></p>\n";
    }

    ?>
    <p>
    <form style="display:inline;line-break:inline;" method="POST" action="print.php">
        <input type="submit" style="font-size:100%;color:white;background-color:black;" value="Alle Dozent_innen mit mindestens ">
        <input type="number" name="showTeacherResults" value="<?php echo $showMin; ?>"
               min="<?php echo ($privGlobalUser?1:$minResults);?>"
               style="font-size:100%;color:white;background-color:teal;">
        <input type="hidden" name="sortBy" value="<?php echo $sortBy; ?>">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
        <input type="hidden" name="id" value="<? echo $id; ?>">
        <input type="hidden" name="goBack" value="<? echo $return_to; ?>">
        <input type="submit" style="font-size:100%;color:white;background-color:black;"
               value="Abgaben"><?php
        if (true or empty($cosPrivileged_filter))  // show also to CoS privileged users
        {
            ?>
            <br>
            <input type="submit" name="notevaluated" style="font-size:100%;color:white;background-color:black;"
                   value="Dozent_innen ohne Abgaben"><?php
        } ?>
    </form>
    <form style="display:inline;line-break:inline;" method="POST" action="print.php">
        <button name="showCompare" style="font-size:100%;color:white;background-color:black;" value="1"
                onclick="this.form.submit();"><?php
            echo "Statistik mit Vergleich"; ?></button>
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
        <!--input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID; ?>"-->
        <input type="hidden" name="teacherid" value="<?php echo $teacherid; ?>">
    </form>
    </p>
    <?php
    $params = "$id&courseid=$courseid&showTeacherResults=$showMin&goBack=$return_to";
    $header = $lines = $output = "";
    $evaluatedTeachers = ($cosPrivileged_filter ?$allResults :safeCount($results));
    if ($notevaluated) {
        $listed = 0;
        print '<b>Anzahl aller Dozent_innen ohne Abgaben: ' . safeCount($allteachers) . '</b>' . "\n";
        print '<table style="border:1px;border-collapse: collapse;"><tr style="font-weight:bolder;">
				<td>Vorname</td><td>Name</td></tr>';
        $col = array_column($allteachers, "lastname");
        array_multisort($col, SORT_ASC, SORT_NATURAL, $allteachers);
        // print "<hr>allteachers: ".nl2br(var_export($allteachers,true));
        foreach ($allteachers as $teacher) {    /*if ( !evaluation_is_user_enrolled($evaluation,  $teacher['id'] ) )
			{ continue; }*/
            $show = !ev_is_user_in_CoS($evaluation, $teacher['id']);
            if ($show) {
                $listed++;
                $showTeacher =
                        '<a href="/mod/evaluation/print.php?id=' . $id . '&showTeacher=' . $teacher['id'] . '" target="teacher">';
                print '<tr><td>' . $showTeacher . $teacher['firstname'] . '</td><td>' . $showTeacher . $teacher['lastname'] .
                        "</a></td></tr>\n";
            }
        }
        print "</table>\n";
        print '<b>Anzahl aller ausgewerteten Dozent_innen mit Abgaben: ' . evaluation_number_format($evaluated_teachers) .
                '</b><br>' . "\n";
        print '<b>Anzahl aller ausgewerteten Dozent_innen ohne Abgaben: ' . evaluation_number_format($listed) . '</b>' . "\n";
    } else {
        $sort_sym = ($_SESSION["orderBy"] == "ASC" ? "&uarr;" : "&darr;");
        $Abgaben = "Abgaben" . ($sortBy == "evaluations" ? $sort_sym : "");
        $Lastname = "Name" . ($sortBy == "lastname" ? $sort_sym : "");
        $sumR = $sumC = $sum = $modus = $median = 0;
        $table = '<table style="border:1px;border-collapse: collapse;">';
        $header = '<tr style="font-weight:bolder;"><td style="text-align:right;"><a href="?id='
                . $params . '&sortBy=evaluations"><span style="color:teal;">' . $Abgaben . '</span></a></td><td>Vorname</td>
					<td><a href="?id=' . $params . '&sortBy=lastname"><span style="color:teal;">' . $Lastname .
                '</span></a></td></tr>';
        foreach ($results as $result) {
            if ($result->evaluations >= $showMin) {
                $sumC++;
                $modus = max($result->evaluations, $modus);
                $evaluations[] = $result->evaluations;
                $sumR += $result->evaluations;
                $profile = '<a href="/user/profile.php?id=' . $result->id . '" target="teacher">';
                $showTeacher =
                        '<a href="/mod/evaluation/print.php?id=' . $id . '&showTeacher=' . $result->id . '" target="teacher">' .
                        $result->firstname . '</a>';
                $compareLink = '<a href="print.php?showCompare=1&id=' . $id . '&teacherid='
                        . $result->id . '" target="compare">' . evaluation_number_format($result->evaluations) . '</a>';
                $lines .= '<tr><td style="text-align:right;">' . $compareLink . '</td>
						<td>' . $showTeacher . '</td><td>' . $profile . $result->lastname . "</a></td></tr>\n";
            }
        }
        if (empty($evaluations)) {
            print "<br><b style=\"color:red;\">Keine Kurse mit mindestens $showMin Abgaben!</b>";
        } else {
            sort($evaluations);
            $median = $evaluations[round($sumC / 2, 0)];
            $average = round($sumR / $sumC);


            $topline = '<b>Anzahl aller Dozent_innen mit Abgaben:</b></td><td colspan="1" style="text-align:right;"><b>'
                        . evaluation_number_format($evaluatedTeachers) . "</b>";
            $output .= '<tr><td colspan="2">' . $topline . "</td></tr>\n";
            $percentage = evaluation_calc_perc($sumC,$evaluatedTeachers);
            $output .= '<tr><td colspan="2"><b>Anzahl der ausgewerteten Dozent_innen mit mindestens ' . $showMin
                    . ' Abgaben '.$percentage.':</b></td><td colspan="1" style="text-align:right;"><b>' . evaluation_number_format($sumC) .
                    "</b></td></tr>\n";
            $output .= '<tr><td colspan="2"><b>Abgaben für diese ' . evaluation_number_format($sumC) . ' Dozent_innen:</b></td>'
                    . '<td colspan="1" style="text-align:right;"><b>' . evaluation_number_format($sumR) . "</b></td></tr>\n";
            $topline = '<b>Abgaben aus allen Kursen:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($completed_responses) . "</b>";
            $output .= '<tr><td colspan="1">' . $topline . "</td></tr>\n";
            $output .= '<tr><td colspan="2"><b>Abgaben/Dozent_in: Median:</b></td><td colspan="1" style="text-align:right;"><b>' .
                    evaluation_number_format($median)
                    . "</b></td></tr>\n";
            $output .= '<tr><td colspan="2"><b>Abgaben/Dozent_in: Mittelwert:</b></td><td colspan="1" style="text-align:right;"><b>' .
                    evaluation_number_format($average)
                    . "</b></td></tr>\n";
            $output .= '<tr><td colspan="2"><b>Abgaben/Dozent_in: Modus:</b></td><td colspan="1" style="text-align:right;"><b>' .
                    evaluation_number_format($modus)
                    . "</b></td></tr>\n";
            if ($sumC >= 12) {
                $table .= $output;
            }
            print $table . $header . $lines . $output . "</table>\n";
        }
    }
    print "<br>$goBack;";
}

// make block_evaluation visible in every course in current semester participating
function make_block_evaluation_visible($evaluation) {
    global $DB;
    $evaluation_semester = get_evaluation_semester($evaluation);
    $courses = evaluation_participating_courses($evaluation);
    $loopcnt = 0;
    $blockinstance =
            $DB->get_record_sql("SELECT id FROM {block_instances} where blockname = 'evaluation' and parentcontextid=1 LIMIT 1");
    $corrections = "";
    foreach ($courses as $courseid) {
        list($show, $reminder) = evaluation_filter_Evaluation($courseid, $evaluation);
        if (!$show) {
            continue;
        }
        $coursecontext = $DB->get_record_sql("SELECT id FROM {context} where instanceid = $courseid and contextlevel = 50");
        if ($coursecontext and
                isset($coursecontext->id) and isset($blockinstance->id)) {    // move blocks with position < -10 down to -9
            if ($onTop = $DB->get_records_sql("SELECT id, visible, weight FROM {block_positions} 
								WHERE blockinstanceid != $blockinstance->id AND contextid=$coursecontext->id AND (visible=1 AND weight < -9)")) {
                $loopcnt++;
                $corrections .= str_pad($loopcnt, 4, " ", STR_PAD_LEFT) . " On top of Evaluation: " . safeCount($onTop) .
                        ' Course: <a href="/course/view.php?id=' .
                        $courseid . '" target="new">' . $courseid . "</a><br>\n";
                $DB->execute("UPDATE {block_positions} set weight = -9 
								WHERE blockinstanceid != $blockinstance->id AND contextid=$coursecontext->id AND (visible=1 AND weight < -9)");
            }

            // get course blockposid for this evaluation
            $blockposid = $DB->get_record_sql("SELECT id, contextid from {block_positions} 
							WHERE blockinstanceid=$blockinstance->id AND contextid=$coursecontext->id AND (visible=0 OR weight < -10)");
            if ($blockposid and isset($blockposid->id) and $blockposid->id) // = 45346;
            {
                $DB->execute("DELETE FROM {block_positions} WHERE id=$blockposid->id");
                $loopcnt++;
                //$course = $DB->get_record('course', array('id' => $courseid), '*');
                $corrections .= str_pad($loopcnt, 4, " ", STR_PAD_LEFT) . ' Course: <a href="/course/view.php?id=' .
                        $courseid . '" target="new">' . $courseid . "</a><br>\n";
            }
        }
    }

    if ($corrections) {
        return "List of courses participating in '" .ev_get_tr($evaluation->name) ."' with higher block positions and hidden block evaluation made visible again:<br>\n"
                . $corrections;
    }
    return "<br><br>make_block_evaluation_visible(evaluation): No corrections required<br>";
}

/* unused
function evaluation_daily_progress( $evaluation, $sort = "DESC" )
{	global $DB;
	$query = "SELECT to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy') as \"".get_string('date')."\",
					COUNT(to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy')) AS \"".get_string('completed_evaluations',"evaluation")."\"
					FROM {evaluation_completed}
					WHERE evaluation=$evaluation->id
					GROUP BY to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy')
					ORDER BY to_char(to_timestamp(timemodified),'YYYY-MM-DD Dy') $sort;";
	return $DB->get_records_sql($query);
}
*/

function get_evaluation_cm_from_id($evaluation) {
    global $DB;
    $cm = $DB->get_record_sql("SELECT * FROM {course_modules} AS cm, {modules} AS m WHERE
						cm.instance = $evaluation->id and cm.module = m.id  and m.name='evaluation'");
    return ($cm);
}

function get_evaluation_cmid_from_id($evaluation) {
    global $DB;
    $cmid = $DB->get_record_sql("SELECT cm.id as cmid FROM {course_modules} AS cm, {modules} AS m WHERE
						cm.instance = $evaluation->id and cm.module = m.id  and m.name='evaluation'");
    return ($cmid->cmid);
}

function hasUserEvaluationCompleted($evaluation, $userid) {
    $ids = evaluation_participating_courses($evaluation, $userid);
    foreach ($ids as $courseid) {
        if (!isEvaluationCompleted($evaluation, $courseid, $userid)) {
            return false;
        }
    }
    return true;
}

function isEvaluationCompleted($evaluation, $courseid, $userid, $missingTeachers = false) {
    global $DB;
    if (!$courseid or !$userid) {
        return false;
    }
    $fteachers = "";
    $ids = array();
    $teamteaching = $evaluation->teamteaching;
    $evaluationid = 0;
    evaluation_get_course_teachers($courseid);
    $filter = "";
    if ($courseid) {
        $filter .= " AND courseid=$courseid ";
    }
    if ($userid) {
        $filter .= " AND userid=$userid ";
    }
    if (true) //$teamteaching )
    {
        $teachers = $_SESSION["allteachers"][$courseid];
    } else {
        $teachers = array(1);
    }

    //$DB->set_debug(true);
    $completed = $DB->get_records_sql("select id,evaluation,courseid,userid,teacherid from {evaluation_completed} 
									  WHERE evaluation=" . $evaluation->id . " $filter");
    $missing = $teachers;
    foreach ($completed as $key => $complete) {
        unset($missing[$complete->teacherid]);
        $evaluationid = $complete->evaluation;
    }
    // echo "<br>Missing Teachers: ".SafeCount($missing) ." - Completed: ".safeCount($completed).' - '.print_r($missing,true)."<hr>";
    if ($missingTeachers) {    //if ( !$teamteaching ) { return array(); }
        return $missing;
    }
    // if allteachers is undefined (called from block_evaluations)
    /*if ( safeCount( $completed ) AND ( !isset( $_SESSION["allteachers"][$courseid] ) OR empty($_SESSION["allteachers"][$courseid]) ) )
	{	$teachers = $completed; }*/

    //$DB->set_debug(false);
    //print "\n<hr>Course: $courseid - User: $userid - TeamTeaching: ".($teamteaching?"Yes":"No")." Evaluation-ID: $evaluationid - Completed: ";
    //print safeCount( $completed )."==".safeCount( $teachers);
    //print ((safeCount( $completed ) == safeCount( $teachers)  AND $evaluationid) ?" -Yes":" -No")."<hr>\n";
    return (safeCount($completed) == safeCount($teachers) and empty($missing) and $evaluationid);
}

function EvaluationMissingTeachers($evaluation, $courseid, $userid) {
    return isEvaluationCompleted($evaluation, $courseid, $userid, true);
}

function evaluation_count_teamteaching_courses($evaluation) {
    if ($evaluation->teamteaching_courses > 0 and $evaluation->timeclose < time()) {
        $_SESSION["teamteaching_courses"] = $evaluation->teamteaching_courses;
        return $evaluation->teamteaching_courses;
    }
    if (empty($_SESSION["teamteaching_courses"])) {
        evaluation_get_all_teachers($evaluation);
    }
    //print "<br><br><br><br>Team Teaching Kurse: " .var_export($courses,true);
    return $_SESSION["teamteaching_courses"];
}

function evaluation_count_duplicated_replies($evaluation, $filter = "") {
    global $DB;
    if ($evaluation->teamteaching) {
        return 0;
    }

    if ($evaluation->duplicated_replies > 0 and $evaluation->timeclose < time()) {
        return $evaluation->duplicated_replies;
    }

    if (empty($_SESSION["teamteaching_courseids"])) {
        evaluation_get_all_teachers($evaluation, false, true);
    }
    if (empty($_SESSION["teamteaching_courseids"])) {
        return 0;
    }
    $courseids = implode(",", $_SESSION["teamteaching_courseids"]);
    $completed = $DB->get_records_sql("select courseid, count(*) AS count from {evaluation_completed} 
										WHERE evaluation=$evaluation->id AND courseid IN ($courseids) GROUP BY courseid ORDER BY courseid");
    $duplicated = 0;
    if (empty($completed)) {
        return 0;
    }
    foreach ($completed as $course) {
        $numTeachers = safeCount($_SESSION["allteachers"][$course->courseid]);
        $duplicated += ($course->count / $numTeachers) * ($numTeachers - 1);
    }
    return round($duplicated, 0);
}

function evaluation_countCourseEvaluations($evaluation, $courseid = false, $role = false, $userid = false) {
    global $DB;
    $fcourseid = $fteacherid = "";
    if (!empty($courseid)) {    // if courseid is $myEvaluations
        if (is_array($courseid)) {
            $ids = array();
            foreach ($courseid as $myEvaluation) {
                $ids[] = $myEvaluation["courseid"];
                if (evaluation_debug(false)) {
                    print "<hr>myEvaluation: " . nl2br(var_export($myEvaluation));
                }
            }
            if (safeCount($ids)) {
                $fcourseid = " AND courseid IN (" . implode(",", $ids) . ")";
            }
        } else if ($courseid) {
            $fcourseid = " AND courseid=$courseid";
        }
    }
    if ($role and $role == "teacher" and $userid) {
        $fteacherid = " AND teacherid=$userid";
    }
    $completed = $DB->count_records_sql("select count(*) as count from {evaluation_completed} 
									   WHERE evaluation=$evaluation->id $fcourseid $fteacherid");
    //echo "<br>evaluation_countCourseEvaluations: Count: $completed - $evaluation->id - courseid: $courseid - ".safeCount($completed)." - ";
    //		print var_dump($completed);exit;
    return $completed;
}

function evaluation_is_open($evaluation) {
    $timeopen = ($evaluation->timeopen > 0) ? $evaluation->timeopen : (time() - 80600);
    $timeclose = ($evaluation->timeclose > 0) ? $evaluation->timeclose : (time() + 80600);
    return ($timeopen < time() and $timeclose > time());
}

function evaluation_is_closed($evaluation) {
    return ($evaluation->timeclose>0 AND $evaluation->timeclose < time());
}

// validate if course of studies is part of completed evaluations
function ev_is_cos_in_completed($evaluation, $cos){
    global $DB;
    return $DB->count_records("evaluation_completed", array("evaluation" => $evaluation->id, "course_of_studies" => $cos));
}

// get filters set for evaluation and set CoS_privileged users
function pass_evaluation_filters($evaluation, $courseid) {
    $passed = true;
    if (!isset($_SESSION['filter_course_of_studies']) or !isset($_SESSION['filter_courses'])) {
        list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation);
    }
    $sg_filter = $_SESSION['filter_course_of_studies'];
    $courses_filter = $_SESSION['filter_courses'];
    if (!empty($sg_filter) and is_array($sg_filter)) {
        $Studiengang = evaluation_get_course_of_studies($courseid);
        $passed = ((!empty($Studiengang) and in_arrayi($Studiengang, $sg_filter)) ? true : false);
        // courses in courses_filter are to be excluded
        if ($passed and !empty($courses_filter) and in_arrayi($courseid, $courses_filter)) {
            $passed = false;
        }
        //print "<br><br><hr>Evaluation: " .ev_get_tr($evaluation->name)." - Studiengang: $Studiengang - ".var_export($sg_filter,true)." - showEval: ".($showEval ?"Yes" :"No") ."<br>\n";
    } else if (!empty($courses_filter) and is_array($courses_filter)
            and !in_array($courseid, $courses_filter)) {
        $passed = false;
    } // else if (ev_is_cos_in_completed($evaluation, $CoS))
    if (false and is_siteadmin() and $passed) {
        echo "\n<br>pass_evaluation_filters: passed: \$courseid: $courseid - \$Studiengang: $Studiengang";
    }
    return $passed;
}

function ev_set_privileged_users($show = false, $getEmails = false) {
    global $CFG, $DB, $USER, $evaluation;
    $allemails = $eMails = array();
    $cfgFile = $CFG->dirroot . "/mod/evaluation/privileged_users.csv";
    if (is_readable($cfgFile)) {
        $cfgA = explode("\n", file_get_contents($cfgFile));
        $privileged_users = $_SESSION["privileged_global_users"] =  $_SESSION["privileged_users"]
                = $_SESSION["privileged_global_users_wm"] = $_SESSION["course_of_studies_wm"]
                = $_SESSION['CoS_department'] = $_SESSION['CoS_privileged'] = $_SESSION['CoS_privileged_sgl'] = array();

        foreach ($cfgA as $line) {
            $CoS = "";
            $WM = "Nein";
            $department = 0;
            if (substr(trim($line), 0, 1) == "#" or empty(trim($line))) {
                continue;
            }
            $parts = explode(",", $line);
            $username = trim($parts[0]);
            // check only for current user
            if (empty($username)) // OR !($username == $USER->username) )
            {
                continue;
            }
            // Course of Studies
            if (isset($parts[1])) {
                $CoS = trim($parts[1]);
            }
            // Department (FB, Fachbereich)
            if (isset($parts[2])) {
                $department = trim($parts[2]);
            }
            // Master CoS
            if (isset($parts[3])) {
                $WM = trim($parts[3]);
            }
            $is_WM = (strtolower($WM) == "ja");
            // if global privileged user
            $priv_user = true;
            if (empty($CoS) or substr($CoS, 0, 1) == "#") {
                if (!defined("EVALUATION_OWNER") and $username == $USER->username) {
                    define("EVALUATION_OWNER", $username);
                    $_SESSION["EVALUATION_OWNER"] = $username;
                }
                //if ( $username == $USER->username )
                $_SESSION["privileged_global_users"][$username] = $username;
                if ($is_WM) {
                    $_SESSION["privileged_global_users_wm"][$username] = $username;
                }
            } else {
                if ((is_array($_SESSION['filter_course_of_studies']) and !empty($_SESSION['filter_course_of_studies'])
                                and in_arrayi($CoS, $_SESSION['filter_course_of_studies'])) or
                        ev_is_cos_in_completed($evaluation, $CoS)) {
                    $_SESSION['CoS_privileged'][$username][$CoS] = $CoS;
                    if (isset($parts[6]) and (strtolower($parts[6]) == "sgl" or strtolower($parts[6]) == "studiengangsleitung")) {
                        $_SESSION['CoS_privileged_sgl'][$username][$CoS] = $CoS;
                    }
                    //print "<hr>\$users: " .var_export($users,true) .$user[0].": ". $_SESSION['CoS_privileged'][$user[0]][$user[1]] = $user[1]."<hr>";
                } else {
                    $priv_user = false;
                }
            }
            if ($priv_user) {
                $privileged_users[$username] = $username;
                $_SESSION["privileged_users"][$username] = $username;
                if ($eMail = $DB->get_record("user",array("username" => $username))) {
                    $eMails[$username] = '"' . $eMail->firstname . ' ' . $eMail->lastname . '" <' . $eMail->email . ">";
                            // . '" &lt;' . $eMail->email . "&gt;";
                    $allemails[$username] = $eMail->email;
                }
            }
            if (!empty($CoS) and substr($CoS, 0, 1) != "#") {
                $_SESSION["course_of_studies_wm"][$CoS] = $is_WM;
            }

            /*if ( $is_WM AND empty($department)){
                $department = "WM";
            }*/

            if (!empty($department)) {
                $_SESSION['CoS_department'][$CoS] = $department;
            }
        }

    } else if (!isset($_SESSION['ev_global_cfgfile'])) {
        $_SESSION['ev_global_cfgfile'] = false;
    }
    if (!empty($evaluation->privileged_users)) {
        $ev_privileged_users = explode("\n", $evaluation->privileged_users);
        foreach ($ev_privileged_users as $privileged_user) {
            $privileged_users[$privileged_user] = $privileged_user;
            $_SESSION["privileged_users"][$privileged_user] = $privileged_user;
            // $_SESSION["privileged_global_users"][$privileged_user] = $privileged_user;
            if ($eMail = $DB->get_record("user",array("username" => $privileged_user))) {
                $eMails[$privileged_user] = '"' . $eMail->firstname . ' ' . $eMail->lastname . '" <' . $eMail->email . ">";
                        // . '" &lt;' . $eMail->email . "&gt;";

                $allemails[$username] = $eMail->email;
            }
        }
    }

    // display list as html table
    if ( is_readable($cfgFile) AND ($show OR $getEmails)){
        $cfgData = file_get_contents($cfgFile);
        $pos = strpos($cfgData,"#Anmeldename");
        $out = "";
        if ( $pos){
            $cfgData = substr($cfgData, $pos+1);
        }
        $rows = explode("\n", $cfgData);
        $out .= "<style>tr:nth-child(odd) {background-color:lightgrey;}</style>";
        $out .= "<b>Übersicht privilegierte Personen</b> (diese Evaluation)<br><br>\n";
        $out .= '<table style="">' . "\n";

        $first = true;
        foreach ($rows as $srow) {
            $CoS = "";
            $row = explode(",", $srow);
            if (isset($row[1]) and !strstr($row[1], "#")) {
                $CoS = trim($row[1]);
            }
            if (strstr($row[0], "#")) {
                continue;
            }
            if (!$first and !empty($CoS)) {
                if (isset($_SESSION['CoS_privileged'][$USER->username])) {
                    if (!isset($_SESSION['CoS_privileged'][$USER->username][$CoS])) {
                        continue;
                    }
                }
                if (!empty($_SESSION['filter_course_of_studies']) and !in_arrayi($CoS, $_SESSION['filter_course_of_studies'])) {
                    continue;
                }
            }
            //$_SESSION["privileged_global_users_wm"][$username] = $username;
            if ( $show) {
                $out .= "<tr>\n";
                if ($first) {
                    foreach ($row as $col) {
                        $out .= '<th style="font-weight:bold;">' . htmlspecialchars($col) . "</th>\n";
                    }
                    // add email
                    $out .= '<th style="font-weight:bold;">' . "Mail" . "</th>\n";
                    $first = false;
                } else {
                    foreach ($row as $col) {
                        $out .= "<td>" . htmlspecialchars($col) . "</td>\n";
                    }
                    // add email
                    $mail = (isset($allemails[$row[0]]) ?$allemails[$row[0]] :"&nbsp;");
                    $out .= '<td>' . $mail . "</td>\n";
                }
                $out .= "</tr>\n";
            }
        }
        if (!empty($ev_privileged_users)) {
            foreach ($ev_privileged_users AS $privileged_user){
                if ($eMail = $DB->get_record("user",array("username" => $privileged_user))) {
                    $out .= "<tr>\n<td>$privileged_user</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>"
                            . "<td>$eMail->firstname</td><td>$eMail->lastname</td><td>&nbsp;</td><td>$eMail->email</td>\n</tr>\n";
                }
            }
        }
        $out .=  "</table>";
        // print nl2br(var_export($_SESSION['filter_course_of_studies'],true));
        if ($getEmails) {
            return $eMails;
        }
        return $out;
    }
    return $privileged_users;
}


// get filters set for evaluation and set CoS_privileged users
function get_evaluation_filters($evaluation, $get_course_studies=true) {
    global $CFG, $USER;
    $filter = $sg_filter = $courses_filter = array();
    if (isset($_SESSION['filter_course_of_studies']) AND isset($_SESSION['filter_courses'])
            AND (!empty($_SESSION['filter_course_of_studies']) OR !empty($_SESSION['filter_courses']) )

    ) {
        return array($_SESSION['filter_course_of_studies'], $_SESSION['filter_courses']);
    }

    // initialize arrays
    if (!empty($evaluation->filter_course_of_studies)) {
        $sg_filter = explode("\n", $evaluation->filter_course_of_studies);
        if (strstr($evaluation->filter_course_of_studies, "||")) {
            foreach ($sg_filter as $line) {
                $line = trim($line);
                if (strstr($line, "||")) {
                    $parts = explode("||", $line);
                    $CoS = $parts[0];
                    $filter[$CoS] = $CoS;
                    $users = str_replace(" ", "", $parts[1]);
                    if (strstr($users, ",")) {
                        $usernames = explode(",", $users);
                        foreach ($usernames as $user) {
                            $_SESSION['CoS_privileged'][$user][$CoS] = $CoS;
                        }
                    } else {
                        $_SESSION['CoS_privileged'][$users][$CoS] = $CoS;
                    }
                } else {
                    $filter[$line] = $line;
                }
            }
            $sg_filter = $filter;
        }
    }
    else if($get_course_studies){
        $sg_filter = evaluation_get_course_studies($evaluation, false, true);
    }
    // get courses filter if any
    if (isset($evaluation->filter_courses) and !empty($evaluation->filter_courses)) {
        $courses_filter = explode("\n", $evaluation->filter_courses);
    }
    $_SESSION['filter_course_of_studies'] = $sg_filter;
    $_SESSION['filter_courses'] = $courses_filter;
    if ( !isset($_SESSION["privileged_global_users"]) AND !isset($_SESSION['CoS_privileged'])) {
        ev_set_privileged_users();
    }
    return array($_SESSION['filter_course_of_studies'], $_SESSION['filter_courses']);
}

// allow access only to listed Studiengang and Semester
// to Do: get valid Studiengänge from external table Studiengang (to be created)
function evaluation_filter_Evaluation($courseid, $evaluation, $user = false) {
    global $DB, $USER;
    $no_user = empty($user);
    if ($no_user) {
        $user = $USER;
    }

    $cm = get_coursemodule_from_id('evaluation', $evaluation->id);
    list($isPermitted, $CourseTitle, $CourseName, $SiteEvaluation)
            = evaluation_check_Roles_and_Permissions($courseid, $evaluation, $cm, false, $user);

    //if ( !isset($_SESSION["EVALUATION_OWNER"]) )
    //{	evaluation_isPrivilegedUser($evaluation, $user ); }

    $showPriv = ((isset($_SESSION["EVALUATION_OWNER"]) and $_SESSION["EVALUATION_OWNER"]));  // or evaluation_debug(false));
    if ($no_user) {
        $isPermitted = true;
    }
    if (!$SiteEvaluation) {
        return array(true, "");
    }

    $timeopen = ($evaluation->timeopen > 0) ? $evaluation->timeopen : (time() - 80600);
    $timeclose = ($evaluation->timeclose > 0) ? $evaluation->timeclose : (time() + 80600);
    //$semester = evaluation_get_current_semester();
    $evaluation_semester = get_evaluation_semester($evaluation);
    $course = $DB->get_record('course', array('id' => $courseid), '*'); //$course = get_course($courseid);
    // ignore invisible courses
    if ($course->visible < 1) {
        return array(false, "");
    }
    if (($SiteEvaluation and trim(substr($course->idnumber, -5)) != $evaluation_semester)
            or (intval(date("Ymd", $timeopen)) > intval(date("Ymd")) and
                    !$showPriv)) {
        //print "<br><hr>Evaluation: ".ev_get_tr($evaluation->name)." - idnumber,-5: ".trim(substr( $course->idnumber, -5))
        //		." - showPriv: ".($showPriv ?"Yes" :"No") ." - evaluation_semester: $evaluation_semester<br>\n";
        return array(false, "");
    }

    $reminder = get_string("analysis", "evaluation") . " ";
    $showEval = pass_evaluation_filters($evaluation, $courseid);
    if (!$showEval) {
        return array($showEval, "");
    }

    $is_open = evaluation_is_open($evaluation);
    //print "<br><br><hr>isEvaluationCompleted: $courseid - $user->id".(isEvaluationCompleted( $evaluation, $courseid, $user->id )?"yes":"no")."<br>\n";
    //if ( $showEval AND ( ($evaluation->course == SITEID AND $evaluation_semester < $semester) || (!$is_open AND !$showPriv) ) )
    if (!$is_open and !$showPriv) {
        if (!$no_user and !$isPermitted and !evaluation_has_user_participated($evaluation, $user->id, $courseid)) {
            $showEval = false;
            $reminder = "";
        }
        return array($showEval, $reminder);
    }
    if (!$no_user and isset($_SESSION["myEvaluations"]) and safeCount($_SESSION["myEvaluations"])) {
        $isPermitted = !evaluation_is_student($evaluation, $_SESSION["myEvaluations"], $courseid);
    }

    // if student
    if (!$no_user and $showEval and !$isPermitted ){ // and intval(date("Ymd", $timeopen)) <= intval(date("Ymd"))) {
        if ($is_open) {
            if (!isEvaluationCompleted($evaluation, $courseid, $user->id)) {
                $has_user_participated = evaluation_has_user_participated($evaluation, $user->id);
                $fullname = ($user->alternatename ? $user->alternatename : $user->firstname) . " " . $user->lastname;
                $days = remaining_evaluation_days($evaluation);
                $style_b = '<b style="color:#000000;">';
                $a = new stdclass();
                if ($days < 7) {
                    // $a->ev_end_msg = ($days > 0 ? "in " . $days . ' Tagen' : "heute")
                    $a->ev_end_msg = ($days > 0 ? ev_get_string("in") . ' ' . $days . ' '
                            .ev_get_string('days'): ev_get_string('today'));
                    $style_b = '<b style="color:#000000;">'.
                            ev_get_string('ev_end_msg',$a)
                            . '</b><br><b style="color:#000000;"> ';
                }
                $a->fullname = $fullname;
                $a->has_participated = $has_user_participated;
                /*
                $reminder = 'Guten Tag ' . $fullname
                        . '<br>Sie haben '
                        . ($has_user_participated
                                ? 'für diesen Kurs noch nicht'
                                : "bisher <b>für keinen Kurs</b>")
                        . '  an der Evaluation teilgenommen.<br>'
                        . $style_b
                        . 'Bitte machen ' . ($has_user_participated ? '' : "auch ")
                        . 'Sie mit!</b><br>';
                */
                $reminder = ev_get_string('good_day') ." " . $fullname
                        . '<br>'
                        . ($has_user_participated
                                ? ev_get_string('not_participated',$a)
                                : ev_get_string('not_participated_course',$a)
                        . '<br>'
                        . $style_b
                        . ev_get_string('please_participate',$a)
                        .'</b><br>');
            }
        } else if (!evaluation_has_user_participated($evaluation, $user->id, $courseid)) {
            $reminder = "";
        }
    }
    return array($showEval, $reminder);
}

// get fullname of user by userid
function evaluation_get_user_field($userid, $field = false) {
    global $DB, $USER;
    // $user = core_user::get_user($userid);
    $user = $DB->get_record("user", array('id' => $userid), '*');
    if (isset($user->id)) {
        if (!$field or $field == 'fullname') {
            $data = ($user->alternatename ? $user->alternatename : $user->firstname) . " " . $user->lastname;
        } else {
            $data = $user->$field;
        }
        return $data;
    }
    return "";
}

// get fullname of user by userid
function evaluation_get_course_field($courseid, $field = false) {
    global $DB, $USER;
    $course = $DB->get_record('course', array('id' => $courseid), '*');
    if (isset($course->id)) {
        if (!$field or $field == 'fullname') {
            $data = $course->fullname;
        } else {
            $data = $course->$field;
        }
        return $data;
    }
    return "";
}

// get role by shortname for userid in courseid
function evaluation_is_user_with_role($courseid, $rolename, $userid = 0) {
    $result = false;
    $roles = get_user_roles(context_course::instance($courseid), $userid, false);
    foreach ($roles as $role) {
        if ($role->shortname == $rolename) {
            $result = true;
            break;
        }
    }
    return $result;
}

function evaluation_calc_perc($number, $total) {    // Can't divide by zero so let's catch that early.
    if ($total == 0) {
        return " (0%)";
    }
    return " (" . round(($number / $total) * 100, 0) . "%)";
}

function evaluation_number_format($number, $decimals = 0) {    // use Moodle lang setting for display options
    $dsep = ".";
    $tsep = ",";
    if (get_string("language", "evaluation") == "de") {
        $dsep = ",";
        $tsep = ".";
    }
    if (!is_numeric($number)){
        $number = 0;
    }
    return number_format($number, $decimals, $dsep, $tsep);
}

// sort up to 5 ($data) arrays preserving indexes
function ev_multi_sort($a, $b, $c = false, $d = false, $e = false, $f = false) {
    natsort($a);
    foreach ($a as $key => $val) {
        foreach (array("b", "c", "d", "e", "f") as $arr) {
            if (isset($$arr)) {
                ${$arr . $arr}[] = $a[$key];
            }
        }

    }
    foreach (array("b", "c", "d", "e", "f") as $arr) {
        if (isset($$arr)) {
            $$arr = ${$arr . $arr};
        }
    }
    return array($a, $b, $c, $d, $e, $f);
}

// auto fill empty fields course_of_studies from courseid in tables evaluation_completed and evaluation_value
function evaluation_autofill_field_studiengang() {
    global $DB;

    echo "<h2>Autofilling Studiengang</h2><br>\n";
    $completed =
            $DB->get_records_sql("SELECT DISTINCT courseid AS courseid FROM {evaluation_completed} WHERE courseid>0 AND course_of_studies=''" .
                    " ORDER BY courseid");
    foreach ($completed as $complete) {
        $cos = evaluation_get_course_of_studies($complete->courseid);
        $updated = $DB->execute("UPDATE {evaluation_completed} SET course_of_studies='$cos' 
								WHERE course_of_studies='' AND courseid=" . $complete->courseid);
    }
    $values =
            $DB->get_records_sql("SELECT DISTINCT courseid AS courseid FROM {evaluation_value} WHERE courseid>0 AND course_of_studies IS NULL" .
                    " ORDER BY courseid");
    foreach ($values as $value) {
        $updated = $DB->execute("UPDATE {evaluation_value} SET course_of_studies='"
                . evaluation_get_course_of_studies($value->courseid) . "' WHERE course_of_studies='' AND courseid=" .
                $value->courseid);
    }
}

// if Evaluation has no team_teaching: auto fill empty fields teacherid from courseid in tables evaluation_completed and evaluation_value
function evaluation_autofill_field_teacherid($evaluation, $reset = false) {
    global $DB;
    if ($evaluation->teamteaching) {
        echo "<br><b>Evaluation " . ev_get_tr($evaluation->name) ." has team teaching enabled. No autofill of teachers possible!</b><br>\n";
        return false;
    } else {
        if ($reset) {
            $DB->execute("UPDATE {evaluation_completed} SET teacherid=0 WHERE evaluation = $evaluation->id ");
        }

        $completed = $DB->get_records_sql("SELECT * FROM {evaluation_completed} 
											WHERE evaluation = $evaluation->id AND teacherid<1 ORDER BY id");
        if (!safeCount($completed) > 1) {
            echo "<br><b>Evaluation ev_get_tr($evaluation->name) (ID: $evaluation->id) teacherid autofill was already done!</b><br>\n";
            return false;
        }
    }
    echo "<h2>Autofilling teacherid</h2><br>\n";
    if ($reset) {
        print "Resetted all teacherid for $evaluation->name<br>\n";
    }
    ini_set("output_buffering", 350);
    @ob_flush();@ob_end_flush();@flush();@ob_start();
    foreach ($completed as $complete) {
        evaluation_get_course_teachers($complete->courseid);
        $teacherid = 0;
        if (empty($complete->teacherid) and !empty($_SESSION["allteachers"][$complete->courseid]) and
                safeCount($_SESSION["allteachers"][$complete->courseid]) == 1) {
            foreach ($_SESSION["allteachers"][$complete->courseid] as $teacher) {
                $teacherid = $teacher['id'];
                break;
            }
            $DB->execute("UPDATE {evaluation_completed} SET teacherid=$teacherid WHERE id=$complete->id");
            $DB->execute("UPDATE {evaluation_value} SET teacherid=$teacherid WHERE completed = $complete->id");
        }
    }
    @flush();
}

function evaluation_autofill_duplicate_field_teacherid($evaluation, $reset = false) {
    global $DB;
    if ($evaluation->teamteaching) {
        echo "<br><b>Evaluation $evaluation->name has team teaching enabled. No autofill of teachers required!</b><br>\n";
        return false;
    } else {
        if ($reset) {
            $DB->execute("UPDATE {evaluation_completed} SET teacherid=0 WHERE evaluation = $evaluation->id ");
        }
        $completed = $DB->get_records_sql("SELECT * FROM {evaluation_completed} 
										WHERE evaluation = $evaluation->id AND teacherid<1 AND courseid>1000 ORDER BY id");
        if (!safeCount($completed)) {
            echo "<br><b>Evaluation $evaluation->name (ID: $evaluation->id) teacherid duplicate autofill was already done!</b><br>\n";
            return false;
        }
    }
    echo "<h3>Autofilling teacherid: Create 1 reply per teacher for courses with team teaching</h3><br>\n";
    if ($reset) {
        print "Resetted all teacherid for $evaluation->name<br>\n";
    }
    print '<span id="counter"></span><br>';
    ini_set("output_buffering", 360);
    @ob_flush();@ob_end_flush();@flush();@ob_start();
    $counter = 0;
    foreach ($completed as $complete) {
        $counter++;
        set_time_limit(30);
        evaluation_get_course_teachers($complete->courseid);
        /*$teachers = $DB->get_records_sql("SELECT userid,courseid FROM {evaluation_completed}
										WHERE evaluation = $evaluation->id AND courseid=$complete->courseid AND userid=$complete->userid
										GROUP BY userid,courseid ORDER BY userid,courseid");

		if ( safeCount($teachers) >= safeCount($_SESSION["allteachers"][$complete->courseid]) )
		{	continue; }*/
        if (!empty($_SESSION["allteachers"][$complete->courseid]) and
                safeCount($_SESSION["allteachers"][$complete->courseid]) > 0) {
            $loopcnt = 0;
            foreach ($_SESSION["allteachers"][$complete->courseid] as $teacher) {
                $teacherid = $teacher['id'];
                if ($loopcnt == 0) {
                    $DB->execute("UPDATE {evaluation_completed} SET teacherid=$teacherid WHERE id=$complete->id");
                    $DB->execute("UPDATE {evaluation_value} SET teacherid=$teacherid WHERE completed = $complete->id");
                    $loopcnt++;
                    continue;
                }
                /*print "<br>\n<br>\n<br>\$loopcnt: $loopcnt - ID: $complete->id: CourseID: $complete->courseid - UserID: $complete->userid - Teachers: ".safeCount($teachers)
		. " - Teacher: " . var_export($teacher,true)." - Session: " . safeCount($_SESSION["allteachers"][$complete->courseid]) . "<br>\n";
if ( $counter >= 6)	{ exit;} */

                $newcompl = new stdClass();
                foreach ($complete as $key => $value) {
                    $newcompl->{$key} = $value;
                }
                unset($newcompl->id);
                $newcompl->teacherid = $teacherid;
                $compl_id = $DB->insert_record('evaluation_completed', $newcompl);

                $values = $DB->get_records_sql("SELECT * FROM {evaluation_value} WHERE completed = $complete->id");
                foreach ($values as $value) {
                    $newval = new stdClass();
                    foreach ($value as $keyV => $valueV) {
                        $newval->{$keyV} = $valueV;
                    }
                    unset($newval->id);
                    $newval->teacherid = $teacher['id'];
                    $newval->completed = $compl_id;
                    $val_id = $DB->insert_record('evaluation_value', $newval);
                }
                $loopcnt++;
                print '<script>document.getElementById("counter").innerHTML = "'
                        .
                        "Count: $counter - ID: $complete->id: CourseID: $complete->courseid - UserID: $complete->userid - Teacherid: $teacherid" .
                        '";</script>';
                @ob_flush();@ob_end_flush();@flush();@ob_start();
            }
        }
    }
    print "<br>Completed: Autofilling teacherid: Create duplicate replies for courses with team teaching<br>\n";
}

function evaluation_set_module_viewed($cm) {
    global $CFG, $USER;
    require_once($CFG->libdir . '/completionlib.php');
    $completion = new completion_info($cm->get_course());
    $completion->set_module_viewed($cm, $USER->id);
}

// logging functions
function evaluation_trigger_module_viewed($evaluation, $cm, $courseid) {
    if (!is_siteadmin() and !isset($_SESSION["LoggedInAs"])) {
        global $DB;
        if (!$courseid) {
            $courseid = $evaluation->course;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*');
        $event = \mod_evaluation\event\course_module_viewed::create_from_record($evaluation, $cm, $course);
        $event->trigger();
    }
}

function evaluation_trigger_module_analysed($evaluation, $cm = false, $courseid = false) {
    if (!is_siteadmin() and !isset($_SESSION["LoggedInAs"])) {
        global $DB;
        if (!$courseid) {
            $courseid = $evaluation->course;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*'); //get_course($courseid);
        //$course = $DB->get_record()
        if (!$cm) {
            list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
        }
        $event = \mod_evaluation\event\course_module_analysed::create_from_record($evaluation, $cm, $course);
        $event->trigger();
    }
}

function evaluation_trigger_module_analysedExported($evaluation, $cm = false, $courseid = false) {
    if (!is_siteadmin() and !isset($_SESSION["LoggedInAs"])) {
        global $DB;
        if (!$courseid) {
            $courseid = $evaluation->course;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*'); //get_course($courseid);
        //$course = $DB->get_record()
        if (!$cm) {
            list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
        }
        $event = \mod_evaluation\event\course_module_analysedExported::create_from_record($evaluation, $cm, $course);
        $event->trigger();
    }
}

function evaluation_trigger_module_entries($evaluation, $cm = false, $courseid = false) {
    if (!is_siteadmin() and !isset($_SESSION["LoggedInAs"])) {
        global $DB;
        if (!$courseid) {
            $courseid = $evaluation->course;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*'); //get_course($courseid);
        if (!$cm) {
            list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
        }
        $event = \mod_evaluation\event\course_module_entries::create_from_record($evaluation, $cm, $course);
        $event->trigger();
    }
}

function evaluation_trigger_module_statistics($evaluation, $cm = false, $courseid = false) {
    if (!is_siteadmin() and !isset($_SESSION["LoggedInAs"])) {
        global $DB;
        if (!$courseid) {
            $courseid = $evaluation->course;
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*'); //get_course($courseid);
        if (!$cm) {
            $id = get_evaluation_cmid_from_id($evaluation);
            list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
        }
        $event = \mod_evaluation\event\course_module_statistics::create_from_record($evaluation, $cm, $course);
        $event->trigger();
    }
}

// get userids of site admins
function evaluation_get_siteadmins() {
    global $CFG;
    return $CFG->siteadmins;
}

function evaluation_is_item_course_of_studies($evaluationid) {
    global $DB;
    $is_item =
            $DB->get_record_sql("SELECT id FROM {evaluation_item} WHERE evaluation = $evaluationid AND name = 'course_of_studies'");
    //echo "<br><br><b>Studiengang enthalten: ".(isset($is_item->id) ?"Ja" :"Nein")."</b><br>\n";
    return isset($is_item->id);

}

// auto-generate replies from courseid to fill studiengang
function evaluation_autofill_item_studiengang($evaluation, $force = false) {
    // check settings before execution!
    //return;

    global $DB;
    $position = 1; //default position
    $itemid = 0;
    $completed_responses = evaluation_countCourseEvaluations($evaluation);
    if ( !$completed_responses ){
        return false;
    }

    // don't run before evaluation is closed
    if (evaluation_is_open($evaluation)) {
        return false;
    }

    // check if studiengang has been added to items
    $courseids =
            $DB->get_records_sql("SELECT * FROM {evaluation_item} WHERE name ILIKE '%tudiengang%' AND evaluation=$evaluation->id and typ='multichoice'");
    // make sure this is never run twice!
    if (safeCount($courseids)) {
        evaluation_debug("<br><br><hr><b>Evaluation item 'studiengang' already exists in Evaluation $evaluation->name!</b>. No action required! \n");
        return true;
    } else // create item Studiengang
    {
        $item =
                $DB->get_record_sql("SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id and typ='multichoice' LIMIT 1");
        if (!isset($item->id)) {
            evaluation_debug("<br><br><hr><b>Can't find sample item in Evaluation $evaluation->name!</b>. No action done! \n");
            return false;
        }

        // get course_of_studies array
        if (!empty($evaluation->filter_course_of_studies) or !empty($evaluation->filter_courses)) {
            list($sg_arr, $courses_filter) = get_evaluation_filters($evaluation);
            if (safeCount($sg_arr) < 2) {
                evaluation_debug("<br><br><hr><b>Only a single course of studies is evaluated in evaluation $evaluation->name!</b>. No action required! \n");
                return false;
            }
            if (empty($sg_arr) and !empty($courses_filter)) {
                if (safeCount($courses_filter) < 2) {
                    evaluation_debug("<b>Only a single course is evaluated in evaluation $evaluation->name!</b>. No action required! \n");
                    return false;
                }
                $sg_arr = evaluation_get_course_of_studies_from_courseids($courses_filter);
            }
        } else {
            $sg_arr = evaluation_get_course_studies($evaluation, false);
        }
        if ( empty($sg_arr)) {
            return false;
        }
        if ( is_object($sg_arr)){
            $sg_arr = (array) $sg_arr;
        }
        //print "<br><br>\nsg_arr: ".var_export($sg_arr, true) ."<br><br>\n";
        $template = $item->id;
        unset($item->id);
        $item->name = "Studiengang";
        $item->label = "";
        $item->hasvalue = 1;
        $item->position = $position;
        $item->required = 0;
        $item->dependitem = 0;
        $item->options = "h";
        $item->presentation = "r>>>>>" . str_replace("\n", "\n|",
                        implode("\n", $sg_arr) . "<<<<<1");
        $itemid = $DB->insert_record('evaluation_item', $item);
        evaluation_renumber_items($evaluation->id);
    }
    // make sure this is never run twice!
    if (!$itemid or !$template) {
        print "<br><br><hr><b>ERROR creating 'studiengang' in Evaluation $evaluation->name!</b>. Action required! \n";
        return false;
    }

    $courseids = $DB->get_records_sql("SELECT * FROM {evaluation_value} WHERE item=$itemid AND courseid>0 ORDER BY completed ASC");
    // make sure this is never run twice!
    if (safeCount($courseids)) {
        print "<br><br><hr><b>Evaluation values for item $item exist already in Evaluation $evaluation->name!</b>. No action done! \n";
        return false;
    }

    $courseids =
            $DB->get_records_sql("SELECT * FROM {evaluation_value} WHERE item=$template AND courseid>0 ORDER BY completed ASC");
    $hits = safeCount($courseids);
    $loopcnt = 0;
    print '<br><span id="counter"></span><br>';
    foreach ($courseids as $courseid) {    // id	courseid	item	completed	value
        set_time_limit(30);
        $loopcnt++;
        $newval = new stdClass();
        foreach ($courseid as $key => $value) {
            $newval->{$key} = $value;
        }
        unset($newval->id);
        $newval->item = $itemid;
        $Studiengang = evaluation_get_course_of_studies($courseid->courseid);
        $position = array_searchi($Studiengang, $sg_arr);
        $newval->value = (is_numeric($position) ? $position : 0) + 1;
        $val_id = $DB->insert_record('evaluation_value', $newval);
        //print "<hr><br>$loopcnt/$hits: $val_id - $courseid->courseid - $Studiengang - position: $position - newval: ".$newval->value."<hr>";
        print '<script>document.getElementById("counter").innerHTML = "' .
                "$loopcnt/$hits: $val_id - $courseid->courseid - $Studiengang - Wert:"
                . trim($newval->value) . '";</script>';
        //print "<script>window.scrollTo(0,document.body.scrollHeight);</script>\n";
        @ob_flush();@ob_end_flush();@flush();@ob_start();
    }
    return true;
}

// move legacy ecaluion dta from feedback tables to evaluation tables
function evaluation_move_from_feedback() {
    /* 	use with uttermost care!
		Make a dump of all feedback and evaluation tables before you start
		Example (as user postgres)):
			pg_dump -d moodle -c -t "mdl_feedback*" -t "mdl_evaluation*" -t "mdl_course_modules" -t "mdl_context" -f before_migration.sql" postgres
		do the migration, if errors:
			psql  -d moodle -f before_migration.sql

	*/

    // Needs re-coding and testing
    return;

    global $DB;
    print "<hr><br><b>Migrating Feedback site course data to Evaluation data.<br>This may take a very long time...</b><br>\n<span id=\"counter\"></span><hr>\n\n";

    $evaluations = $DB->get_records_sql("SELECT * FROM {mdl_feedback} WHERE course=1 ORDER BY id");
    foreach ($evaluations as $evaluation) {
        $evaluation->min_results = $evaluation->min_results;
        $evaluationid = $evaluation->id;
        unset($evaluation->min_results, $evaluation->id);
        $eID = $DB->insert_record('evaluation', $evaluation);

        $cm = $DB->get_record_sql("SELECT * FROM {course_modules} WHERE instance = $evaluationid and module = 7");
        $module = $DB->get_record_sql("SELECT id FROM {modules} WHERE name='evaluation'");
        $cm->module = $module->id;
        $cm->instance = $eID;
        $cmid = $cm->id;

        if (!$eID or !isset ($cm->module) or empty($cm->module)) {
            print "New evaluation ID: $eID - Module-ID: $module->id - cmid: $cmid \n<br>";
            var_dump($cm);
            return;
        }
        $updated = $DB->execute("UPDATE {course_modules} SET module=$module->id, instance=$eID WHERE id=$cmid");
        $cm = $DB->get_record_sql("SELECT * FROM {course_modules} WHERE id=$cmid");
        if (!isset ($cm->module) or empty($cm->module) or $cm->module !== $module->id) {
            print "New evaluation ID: $eID - Module-ID: $module->id - cmid: $cmid update: $updated\n<br>";
            var_dump($cm);
            return;
        }

        // instance record stays unchanged

        $evaluations_completed =
                $DB->get_records_sql("SELECT * FROM {evaluation_completed} WHERE evaluation=$evaluationid ORDER BY id");
        $evaluations_completedIDs = array();
        foreach ($evaluations_completed as $evaluation_completed) {
            $evaluation_completed->evaluation = $eID;
            $evaluation_completedid = $evaluation_completed->id;
            unset($evaluation_completed->evaluation, $evaluation_completed->id);
            $ecID = $DB->insert_record('evaluation_completed', $evaluation_completed);
            $evaluations_completedIDs[$evaluation_completedid] = $ecID;
        }

        $evaluation_items = $DB->get_records_sql("SELECT * FROM {evaluation_item} WHERE evaluation=$evaluationid ORDER BY id");
        foreach ($evaluation_items as $evaluation_item) {
            $evaluation_item->evaluation = $eID;
            $evaluation_itemID = $evaluation_item->id;
            unset($evaluation_item->evaluation, $evaluation_item->id);
            $eiID = $DB->insert_record('evaluation_item', $evaluation_item);
            $evaluation_values = $DB->get_records_sql("SELECT * FROM {evaluation_value} WHERE item=$evaluation_itemID ORDER BY id");
            $values = safeCount($evaluation_values);
            $loopcnt = 0;
            foreach ($evaluation_values as $evaluation_value) {
                $loopcnt++;
                print '<script>document.getElementById("counter").innerHTML = "' . $loopcnt . " of " . $values .
                        ' rows in values table";</script>';

                if ($completed = $evaluations_completedIDs[$evaluation_value->completed]) {
                    $DB->execute("delete from {evaluation_value} WHERE id=$evaluation_value->id");
                    $evaluation_value->item = $eiID;
                    $evaluation_value->completed = $completed;
                    unset($evaluation_value->id);
                    $DB->insert_record('evaluation_value', $evaluation_value);
                }
            }
        }
        $DB->execute("delete from {evaluation} WHERE id=$evaluationid");
        $DB->execute("delete from {evaluation_completed} WHERE evaluation=$evaluationid");
        $DB->execute("delete from {evaluation_item} WHERE evaluation=$evaluationid");
    }
    // purge all caches
    exec("/usr/bin/php" . __DIR__ . '/../../admin/cli/purge_caches.php');
    print "<hr><b>Data Migrating completed. Now validate!</b><hr>\n\n";
}

/**
 * Return whether the course is empty or not.
 *
 * @param int $courseid the course id.
 * @return bool
 *
 * A course is considered empty even if it has a forum and BBB modules
 */
function evaluation_is_empty_course($courseid,$debug=false) {
    global $DB;

    // THIS FUNCTION IS BEING MODULARIZED SO THAT IN THE FUTURE WE CAN
    // SELECT AT SEARCH TIME WHAT CONSTITUTES AN EMPTY COURSE.
    $sql="
    SELECT *
FROM mdl_course c
WHERE c.id in(4834,5281,3099)
AND (1 < (
select count(*)
FROM mdl_course_modules cm, mdl_modules m
WHERE course in(4834,5281,3099) AND cm.module=m.id AND m.name NOT IN ('forum','bigbluebuttonbn')
) OR 1 < (
select count(*)
FROM mdl_grade_categories
WHERE courseid in(4834,5281)
) OR 1 < (
select count(*)
FROM mdl_grade_items
WHERE courseid in(4834,5281,3099)
) OR c.id IN (
SELECT customint1
FROM mdl_enrol
WHERE enrol = 'meta'
AND
status = 0
))";

    // Course module count.
    $modularsql = "1 <= (
						select count(*)
						  FROM {course_modules} cm, {modules} m /*, {course_sections} cs */
						 WHERE cm.course = :courseid1 AND cm.module=m.id 
						 /* AND cm.course = cs.course 
						 AND (coalesce(cs.name) <>'' OR coalesce(cs.summary) <>'') */
						 AND m.name NOT IN ('forum','bigbluebuttonbn')
					   )";
    $params['courseid1'] = $courseid;

    // Grade category count.
    $modularsql .= !empty($modularsql) ? " OR " : "";
    $modularsql .= "1 <= (
						select count(*)
						  FROM {grade_categories}
						 WHERE courseid = :courseid2
					   )";
    $params['courseid2'] = $courseid;

    // Grade items count.
    $modularsql .= !empty($modularsql) ? " OR " : "";
    $modularsql .= "1 <= (
						select count(*)
						  FROM {grade_items}
						 WHERE courseid = :courseid3
					   )";
    $params['courseid3'] = $courseid;

    // Check to see if course is meta child.
    $modularsql .= !empty($modularsql) ? " OR " : "";
    $modularsql .= "c.id IN (
							SELECT customint1
							  FROM {enrol}
							 WHERE enrol = 'meta'
								   AND
								   status = 0
							)";
    $sql = "SELECT *
			  FROM {course} c
			 WHERE c.id = :courseid
				   AND ($modularsql)";
    $params['courseid'] = $courseid;
    if ($DB->get_records_sql($sql, $params)) {
        return false;
    } else {
        if ( $debug ){
            print nl2br("SQL: $sql" .var_export($params, true));
        }
        return true;
    }
}

function evaluation_get_empty_courses($sdate=false) {
    global $DB;
    $filter = $filterText = "";
    if ( is_string($sdate) AND strlen($sdate)>=5 ){
        $filter = "WHERE startdate<=" . strtotime($sdate);
        $filterText = " -with start date before $sdate";
    }
    $courses = $DB->get_records_sql("SELECT id, startdate, fullname, shortname, idnumber from {course} 
    $filter ORDER BY startdate ASC");
    $loopcnt = $empty_courses = 0;
    print "<h2>Empty Courses</h2><br><table>\n";
    print "<tr><th>Courseid</th><th>Shortname</th><th>Fullname</th><th>Idnumber</th><th>Startdate</th></tr>\n";
    foreach ($courses as $course){
        if (evaluation_is_empty_course($course->id,($empty_courses<1))){
            print '<tr><td><a href="/course/view.php?id='.$course->id.'" target="_blank">'
                    .$course->id.'</a></td>'
                    ."<td>$course->shortname</td><td>$course->fullname</td><td>$course->idnumber</td>
                <td>".date("Y-m-d",$course->startdate)."</td></tr>\n";

            $empty_courses++;
        }
        $loopcnt++;
    }
    print "</table>\n";
    print "<h2><b>$empty_courses empty courses found $filterText</h2><br><table></table></b>\n";
}


function ev_send_reminders($evaluation,$role="teacher",$noreplies=false,$test=true,
        $verbose=false,$cli=false,$cronjob=false) {
    global $CFG, $DB, $USER;
    $_SESSION['ev_cli'] = $cli;
    set_time_limit(3000);
    $start = time();
    $DB->set_debug(false);
    if ($verbose) {
        $DB->set_debug(true);
    }
    if ($cronjob) {
        ini_set('output_buffering', 600);
    }

    /*if ($CFG->noemailever) {
        $test = true;
    }*/

    if (!isset($evaluation->id)) {
        ev_show_reminders_log("ERROR: Evaluation with ID $evaluation->id not found!", $cronjob);
        return false;
    }
    if (!evaluation_is_open($evaluation)) {
        ev_show_reminders_log("ERROR: The evaluation '$evaluation->name' is not open. Reminders can not be mailed!", $cronjob);
        return false;
    }
    // set user var to Admin Harry
    if (empty($USER) or !isset($USER->username)) {
        /*
        SELECT value FROM mdl_config WHERE name = 'siteadmins'; (30421,26259)
        */
        // $USER = core_user::get_user(30421);
        $USER = $DB->get_record("user", array('id' => 30421), '*');
    }
    // $saveduser = $USER;

    $blockedusers = array();
    $blockedusersfile = $CFG->dataroot . "/" . $CFG->dbname ."_evaluation_blockedusers_" .date("Ymd"). ".txt";
    if (file_exists($blockedusersfile)) {
        $blockedusers = explode("\n",file_get_contents($blockedusersfile));
    }
    elseif ($files = glob($CFG->dataroot . "/" . $CFG->dbname . "_evaluation_blockedusers_*.txt")) {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file); // Delete each file
               //  echo "Deleted: $file\n";
            }
        }
    }
    setlocale(LC_ALL, 'de_DE');
    $current_language = current_language();
    $role = ($role == "participants" ? "student" : $role);

    ev_show_reminders_log("\n" . date("Ymd H:i:s") .
            "\nSending reminders to all participants with role $role in evaluation ($CFG->dbname)"
            . "'$evaluation->name' (ID: $evaluation->id)", $cronjob);

    if ($test) {
        ev_show_reminders_log("Test Mode $test", $cronjob);
    }
    if ($CFG->noemailever) {
        ev_show_reminders_log("CFG->noemailever: Only Test mails to be sent for this Moodle instance", $cronjob);
    }
    //get all participating students/teachers
    $evaluation_users = get_evaluation_participants($evaluation, false, false, ($role == "teacher"), ($role == "student"));
    $minResults = evaluation_min_results($evaluation);
    $minResultsText = min_results_text($evaluation);
    $remaining_evaluation_days = round(remaining_evaluation_days($evaluation), 0);
    $current_evaluation_day = round(current_evaluation_day($evaluation), 0);
    // $total_evaluation_days = total_evaluation_days($evaluation);
    $lastEvaluationDay = date("d.m.Y", $evaluation->timeclose);
    $cmid = get_evaluation_cmid_from_id($evaluation);
    $evUrl = $CFG->wwwroot . "/evaluation/view.php?id=" . $cmid;
    $senderName = '=?UTF-8?B?' . base64_encode($evaluation->sendername) . '?=';
    $senderMail = strtolower($evaluation->sendermail);
    $sender = $senderName . " <$senderMail>";
    $headers = array("From" => $sender, "Return-Path" => "<$senderMail>", "Reply-To" => $sender, "MIME-Version" => "1.0",
            "Content-type" => "text/html;charset=UTF-8", "Content-Transfer-Encoding" => "quoted-printable");

    $a = new stdClass();
    $a->minResults = $minResults;
    $a->min_results_text = $minResultsText;
    $a->role = ev_get_string(($role == "teacher" ? "teachers" : "students"));
    $a->signum = (!empty($CFG->ash)
            ? "<hr>
                <b>Alice Salomon Hochschule Berlin</b><br>
                 - University of Applied Sciences -<br>
                Alice-Salomon-Platz 5, 12627 Berlin<br>"
            : "");
    $send_reminders_noreplies = "send_reminders_noreplies_students";
    if ($noreplies) {
        if ($role == "teacher") {
            $send_reminders_noreplies = "send_reminders_noreplies_teachers";
        }
        ev_show_reminders_log(ev_get_string($send_reminders_noreplies, $a), $cronjob);
    }

    $loopcnt = $loopcntStudents = $loopcntTeachers = 0;
    foreach ($evaluation_users as $evaluation_user) {    //if ( $loopcnt<280) { $loopcnt++; continue; }   // set start counter
        if (!$cronjob) {
            @ob_flush();
            @ob_end_flush();
            ob_start(null, 600);;
        }
        //print print_r($key)."<hr>"; print print_r($evaluation_user);exit;

        $loopcnt++;
        $username = $evaluation_user["username"];
        // $firstname = $evaluation_user["firstname"];
        // $lastname = $evaluation_user["lastname"];
        $fullname = $a->fullname = str_replace('"', '', $evaluation_user["fullname"]);
        $email = strtolower($evaluation_user["email"]);
        $userid = $evaluation_user["id"];
        $lang = $evaluation_user["language"];
        // ev_show_reminders_log("$loopcnt. $fullname - $username - $email - ID: $userid", $cronjob);
        if (empty($username) || empty($fullname)) {
            ev_show_reminders_log("$loopcnt. $fullname - $username - $email - ID: $userid - Can't send mail to undefined user!!",
                    $cronjob);
            continue;
        }
        if (empty($email) or strtolower($email) == "unknown" or !strstr($email, "@") or stristr($email, "unknown@")) {
            ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $email - ID: $userid - Can't send mail to $email!!",
                    $cronjob);
            continue;
        }

        if (!empty($CFG->ash)) {
            $blockedusersondemand = array('01242044', '00053230');
            if (in_array($username, $blockedusersondemand)) {
                ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $email - ID: $userid - user is in blocklist !!",
                        $cronjob);
                continue;
            }
            /*$blockeduserstmp = array();
            if (in_array($username, $blockeduserstmp) and date("Y-m-d") == "2025-02-11") {
                ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $email - ID: $userid - user is in temporary blocklist !!",
                        $cronjob);
                $blockedusers[$username] = $username;
                // continue;
            }*/
        }
        if (($test ?$loopcnt>1:true) && !empty($blockedusers) && in_array($username, $blockedusers)) {
            ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $email - ID: $userid - user is in daily blocklist !!",
                    $cronjob);
            continue;
        }

        unset($_SESSION["possible_evaluations"], $_SESSION["possible_active_evaluations"]);
        $myEvaluations = get_evaluation_participants($evaluation, $userid);

        if (empty($myEvaluations)) {
            ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $email - ID: $userid - No courses in Evaluation!! - "
                    . "Participating courses: " . count(evaluation_is_user_enrolled($evaluation, $userid)), $cronjob);
            continue;
        }

        force_current_language($lang);
        $a->ev_name = ev_get_tr($evaluation->name);

        if ($test) { // OR ($loopcnt<2 AND $CFG->noemailever)) {
            if (false && $loopcnt > 1) {
                break;
            }
            $to = "Harry <Harry@Bleckert.com>";
            // $to = $sender;
            // $to = "Anja Voss <voss@ash-berlin.eu>";
            if (strpos($test, "@")) {
                $to = $test;
            }
            if (strpos($to, "<") !== false and strpos($to, ">") !== false) {
                list($tname, $emailt) = explode(' <', trim($to, '> '));
                $to = '=?UTF-8?B?' . base64_encode($tname . " (Test)") . '?=' . " <$emailt>";
            }
        } else {
            $to = '=?UTF-8?B?' . base64_encode($fullname) . '?=' . " <$email>";
        }

        $testinfo = ($test ? " Test: " : "");
        $subject = $testinfo . '=?UTF-8?B?' . base64_encode($a->ev_name) . '?=';

        $a->role = ev_get_string(($role == "teacher" ? "teachers" : "students"));

        $a->testmsg = "";
        if ($test) {
            $a->testmsg = "<p>" . ev_get_string('send_reminders_pmsg', $a) . "</p>\n";
            if ($noreplies) {
                $a->testmsg .= " - " . ev_get_string($send_reminders_noreplies, $a);
            }
            $a->testmsg .= "<hr>\n";
        } elseif (($test ?$loopcnt>1:true) && !in_array($username, $blockedusers)) {
            $blockedusers[] = $username;
            file_put_contents($blockedusersfile, $username . "\n",FILE_APPEND);
        }

        $a->remaining_evaluation_days = $remaining_evaluation_days;
        $a->lastEvaluationDay = $lastEvaluationDay;
        $a->evUrl = $evUrl;
        $a->reminder = ($remaining_evaluation_days <= 9 ? "<b>" . ev_get_string('send_reminders_remaining', $a) . "</b>" : "");
        $a->signature = $evaluation->signature;
        if ($role == "student") {
            $hasParticipated = evaluation_has_user_participated($evaluation, $userid);
            if ($noreplies and $hasParticipated) {
                ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $userid - $email - HAS PARTICIPATED!!",
                        $cronjob);
                continue;
            }
            if (hasUserEvaluationCompleted($evaluation, $userid)) {
                ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $userid - $email - COMPLETED ALL!!", $cronjob);
                continue;
            }
            $a->myCourses = show_user_evaluation_courses($evaluation, $myEvaluations, $cmid, true, false);
            $loopcntStudents++;
            $a->also = (($hasParticipated or remaining_evaluation_days($evaluation) > 15) ? ""
                    : ev_get_string('also'));
            $message = '<html><head><title>' . $a->ev_name . '</title></head><body>'
                    . ev_get_string('send_reminders_students', $a) . "</body></html>";
        } else { // $role == teacher
            if (!safeCount($_SESSION["distinct_s"])) {
                ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $userid - $email - NO SESSION['distinct_s']!!",
                        $cronjob);
                continue;
            }

            $replies = evaluation_countCourseEvaluations($evaluation, false, "teacher", $userid);
            if ($noreplies and $replies >= $a->min_results_text) {
                ev_show_reminders_log("$loopcnt.  Mail not sent: $fullname - $username - $userid - $email - >= $a->min_results_text replies!!",
                        $cronjob);
                continue;
            }
            $a->myCourses = show_user_evaluation_courses($evaluation, $myEvaluations, $cmid, true, true, true);
            $a->distinct_s = $_SESSION["distinct_s"];
            $a->replies = $replies;
            $a->submissions = ev_get_string('submission' . ($replies > 1 ? "s" : ""));

            $a->onlyfew = "";
            if ($current_evaluation_day > 7 or $replies > 3) {
                if ($replies < 21) {
                    if ($replies < 1) {
                        $a->onlyfew = "<b>" . ev_get_string('send_reminders_no_replies', $a) . "</b>.<br>\n";
                    } else {
                        $a->onlyfew = "<b>" . ev_get_string('send_reminders_few_replies', $a) . "</b>.<br>\n";
                    }
                } else {
                    $a->onlyfew = "<b>" . ev_get_string('send_reminders_many_replies', $a) . "</b>.<br>";
                }
            }

            $loopcntTeachers++;

            $message = '<html><head><title>' . $a->ev_name . '</title></head><body>'
                    . ev_get_string('send_reminders_teachers', $a) . "</body></html>";

        }
        if ((!$CFG->noemailever && !$test ) || ($test && $loopcnt < 2)) {
            mail($to, $subject, quoted_printable_encode($message), $headers, "-f$senderMail"); //,"-r '$sender'");
            ev_show_reminders_log("$loopcnt.$testinfo $fullname - $username - $email - ID: $userid", $cronjob);
        } else {
            ev_show_reminders_log("$loopcnt.  Mail not sent.$testinfo $fullname - $username - $email - ID: $userid", $cronjob);
        }
    }
    force_current_language($current_language);
    $elapsed = time() - $start;
    echo "";
    if ($role == "student") {
        ev_show_reminders_log("Sent reminder to $loopcntStudents students", $cronjob);
        $a->mails_sent = $loopcntStudents;
    } else {
        ev_show_reminders_log("Sent reminder to $loopcntTeachers teachers", $cronjob);
        $a->mails_sent = $loopcntTeachers;
    }
    echo "\n";
    ev_show_reminders_log("Processed $loopcnt $a->role records", $cronjob);
    ev_show_reminders_log("Total time elapsed : " . (round($elapsed / 60, 0)) . " minutes and " . ($elapsed % 60)
            . " seconds. " . date("Ymd H:i:s"), $cronjob);

    $role = ($role == "teacher" ? $role : "student");
    $admindata = get_site_admin_user('harry');

    // send info mails to privileged
    if (!$test) {
        ev_set_reminders($evaluation, $role . "s", $noreplies);
    }
    if ($emails = ev_set_privileged_users(false, true)) {
        //$mails = explode("\n",$emails);
        $loopcnt = 1;
        foreach ($emails as $username => $email) {
            if (!strstr($email, "@") || !strstr($email, "<")) {
                continue;
            }
            force_current_language(get_user_lang($username));
            $a->ev_name = ev_get_tr($evaluation->name);
            $fullname_last = $fullname;
            list($fullname, $emailt) = explode(' <', trim($email, '> '));
            $fullname = str_replace('"', '', $fullname);
            $a->role = ev_get_string(($role == "teacher" ? "teachers" : "students"));
            $a->sent_reminders_info = ev_get_string('sent_reminders_info', $a) . ": ";
            $a->fullname = ""; // ev_get_string('john_doe');
            $email = '=?UTF-8?B?' . base64_encode($fullname) . '?=' . " <$emailt>";
            // $email = '=?UTF-8?B?' . base64_encode($fullname) . '?=' . " <Harry.Bleckert@ASH-Berlin.eu>";
            $a->testmsg = "<p>" . ev_get_string('send_reminders_pmsg', $a) . "</p>\n";
            if ($noreplies) {
                $a->testmsg .= " - " . ev_get_string($send_reminders_noreplies, $a);
            }
            $a->testmsg .= "<hr>\n";
            $message = '<html><head><title>' . $a->ev_name . '</title></head><body>'
                    . ev_get_string('good_day') . " " . $fullname . "<br><br>\n"
                    . ev_get_string('send_reminders_privileged')
                    . ev_get_string('send_reminders_' . $role . 's', $a) . "</body></html>";
            $subject = '=?UTF-8?B?' . base64_encode($a->sent_reminders_info . $a->ev_name) . '?=';
            if ($CFG->noemailever || ($test && $loopcnt < 2)) {
                $email = (!empty($admindata->email) ? $admindata->email : "harry.bleckert@ASH-berlin.eu");
            }
            if ((!$CFG->noemailever && !$test) || ($test && $loopcnt < 2)) {
                mail($email, $subject, quoted_printable_encode($message), $headers, "-f$senderMail");
            }
            $msg = " -Info to privileged persons:";
            ev_show_reminders_log("$loopcnt.$msg $fullname - $username - $emailt", $cronjob);
            $loopcnt++;
        }
        force_current_language($current_language);
    } else if (is_siteadmin()) {
        print nl2br("<hr>Emails:\n" . var_export($emails, true));
    }

    // send also one copy to Moodle admin (Harry)
    if ($admindata) {
        force_current_language(get_user_lang($admindata->username));
        $a->ev_name = ev_get_tr($evaluation->name);
        $a->role = ev_get_string(($role == "teacher" ?"teachers" :"students"));
        $a->testmsg = "<p>" . ev_get_string('send_reminders_pmsg', $a) . "</p>\n";
        $subject = $testinfo . '=?UTF-8?B?' . base64_encode($a->ev_name) . '?=';
        $dbname = "<br>\n(" . $CFG->dbname .") ";
        $mailsSent = "\$CFG->noemailever: " . ($CFG->noemailever ?"No m" :"M") . "ails sent. \n";
        if (!$test) {
            $a->testmsg = ev_get_string('send_reminders_pmsg', $a) . "\n";
            if ($noreplies) {
                $a->testmsg .= "<br> - " . ev_get_string($send_reminders_noreplies, $a);
            }
            $a->testmsg .= "<hr>\n";
        }
        $message = '<html><head><title>' .$a->ev_name .'</title></head><body>'
                . ev_get_string('send_reminders_'.$role.'s', $a) . "</body></html>";
        $msg = "Hey Admin :)<br><br>\n" . ev_get_string('send_reminders_privileged')
                . $dbname . $mailsSent;
        $msg = str_ireplace("<body>", "<body>" . $msg, $message);
        $email = (!empty($admindata->email) ?$admindata->email: "harry.bleckert@ASH-berlin.eu");
        mail($email, $subject, quoted_printable_encode($msg), $headers,"-f$senderMail");
        force_current_language($current_language);
    }
    unset($_SESSION["EvaluationsID"]);
    validate_evaluation_sessions($evaluation);
    return true;
}


function ev_show_reminders_log($msg, $cronjob=false) {
    $logfile = "/var/log/moodle/evaluation_send_reminders.log";
    if (!$cronjob) {
        if (!empty($_SESSION['ev_cli'])) {
            echo $msg . "\n";
        } else {
            echo nl2br($msg . "\n");
        }
    }
    if (is_writable($logfile)){
        system("echo ".date("Ymd H:i:s") . " - \"$msg\">>$logfile");
    }
    return true;
}

function ev_set_reminders($evaluation,$action,$noreplies=false) {
    global $DB;
    $nonresponding = ($noreplies ?" (NR)" :"");
    $evUpdate = new stdClass();
    $evUpdate->id = $evaluation->id;
    $reminders = $evaluation->reminders;
    $ndate = date("d.m.Y");
    if (!empty($reminders)){
        $remindersA = explode("\n",$reminders);
        /*
         17.11.2024:teachers,students
         30.11.2024:teachers (NR),students
        */
        foreach ( $remindersA AS $key => $line) {
            if (!strstr($line, $ndate.":")) {
                continue;
            }
            $remindersA[$key] .= "," . $action . $nonresponding;
            $evUpdate->reminders = implode("\n",$remindersA);
            $DB->update_record("evaluation",$evUpdate);

            return true;
        }
    }
    $evUpdate->reminders = $reminders . $ndate . ":" . $action . $nonresponding . "\n";
    $DB->update_record("evaluation",$evUpdate);
    return true;
}



function ev_get_reminders($evaluation, $id) {
    global $DB;
    $nonresponding = " (NR)";
    $remindertxt = ev_get_string('reminders_sent_at'); // Hinweismails wurden versandt am:
    $send_reminders_to = ev_get_string('send_reminders_to'); // Hinweismails versenden an:
    $is_open = evaluation_is_open($evaluation);
    /*
     20240102:teachers,students
     20240122:teachers,students
     */

    if( is_siteadmin() AND $is_open){
        $send_reminders = optional_param('send_reminders', false, PARAM_INT);
        $role = optional_param('role', false, PARAM_TEXT);
        $noreplies = optional_param('noreplies', false, PARAM_INT);
        $test = optional_param('test', false, PARAM_RAW);
        $remindertxt = '<a href="?id='.$id.'&send_reminders=1">' . $remindertxt . "</a>";
        $non_responders_only = ev_get_string('non_responders_only'); // Nur Non Responders
        $reminders_title =  ev_get_string('reminders_title');

        if ( $send_reminders ){
            ?>
                <form method="POST" action="view.php">
                    <p><b><?php echo $send_reminders_to;?></b><br>
                        <input type="hidden" name="id" value="<?php echo $id;?>">
                       <select name="role">
                           <option value="teacher">Teachers</option>
                           <option value="student">Students</option>
                       </select>
                        - Test: <input type="text" name="test" value="Harry Bleckert <Harry.Bleckert@ASH-Berlin.eu>">
                        - <?php echo $non_responders_only;?>?
                        <input type="radio" name="noreplies" value="0" checked>Nein</input>
                        <input type="radio" name="noreplies" value="1">Ja</input>&nbsp;
                        <button type="submit">Go</button>
                    </p>
                </form>
            <?php
        }
        elseif ( $role ) {
            print nl2br(var_export($noreplies,true));
            if (!str_contains($test, "@")){
                $test = false;
            }
            ev_send_reminders($evaluation, $role, $noreplies, $test);
            $evaluation = $DB->get_record("evaluation", array('id' => $evaluation->id), '*');
        }
    }
    $reminders = $evaluation->reminders;
    if (empty($reminders) AND !is_siteadmin()){
        return ""; // $remindertxt: " . ($is_open ?"./." :"nicht erfasst");
    }
    $remindersA = explode("\n",$reminders);
    $retval = "";
    $retval .=  '<b title="'.$reminders_title.'">' . $remindertxt . '</b>: ';
    foreach ( $remindersA AS $line){
        if (!strpos($line,":")){
            continue;
        }

        $lineA = explode(":",$line);
        if ( empty($lineA) OR empty($lineA[0]) OR !isset($lineA[1])){
            continue;
        }
        $ndate = $lineA[0];
        $retval .= "<b>$ndate</b>: ";
        $roles = explode(",",$lineA[1]);
        $loopcnt = 0;
        $alen = safeCount($roles);
        foreach ($roles as $role){
            $nrd = "";
            if ( strstr($role, $nonresponding)){
                $role = str_replace($nonresponding,"", $role);
                $nrd = $nonresponding;
            }
            if (strstr("students,teachers,participants",$role)) {
                $role =  get_string($role,"evaluation");
            }
            $retval .= $role . $nrd;
            $loopcnt++;
            if ($loopcnt<$alen){
                $retval .= ", ";
            }
            else{
                $retval .= ". ";
            }
        }
    }
    return $retval ."</br>\n";
}

// cron for scheduled tasks. But works extremely slow and therefore disabled.
// maybe better use as a non-Moodle cron job, meanwhile call reminders from view.php
// for testing as non-cron task: commented all cron related lines...
function ev_cron($cronjob=true, $cli=false, $test=false, $verbose=false) {
    global $CFG, $DB;
    // mtrace('send_reminders cron is currently disabled in function ev_cron');
    // return true;
    // $test = true;
    mtrace('mod_evaluation: Start processing send_reminders');

    setlocale(LC_ALL, 'de_DE');

    $reminders_sent = false;
    $timenow = time();
    $task = \core\task\manager::get_scheduled_task(mod_evaluation\task\cron_task::class);
    // presets
    $task->set_fail_delay(0);
    $task->set_next_run_time(0);
    // $taskdb = $task->get_task_record();
    // $taskdb->faildelay = 0;
    // $DB->update_record('task_scheduled', $taskdb);
    $lastruntime = $task->get_last_run_time();
    mtrace("Time now: ".date("d.m,Y H:i:s",$timenow). " - last runtime: "
           .date("d.m,Y H:i:s",$lastruntime));
    mtrace("Checking all open evaluations for due reminders to teachers and students");
    $evaluations = $DB->get_records_sql("SELECT * from {evaluation}");
    // only run in test mode
    // $test = (true AND $CFG->dbname != 'moodle_staging');
    // $verbose = false;
    $noreplies = false;
    try {
        foreach ($evaluations as $evaluation) {
            if (!$evaluation->autoreminders) {
                continue;
            }
            $is_open = evaluation_is_open($evaluation);
            if ($is_open) {
                $reminders = $evaluation->reminders;
                if (empty($reminders)) {
                    mtrace("Evaluation '$evaluation->name': Sending reminders to teachers and students ($CFG->dbname)");
                    ev_send_reminders($evaluation, "teacher", $noreplies, $test, $verbose, $cli, $cronjob);
                    $evaluation = $DB->get_record_sql("SELECT * from {evaluation} where id=" . $evaluation->id);
                    ev_send_reminders($evaluation, "student", $noreplies, $test, $verbose, $cli, $cronjob);
                    continue;
                } else {
                    /*
                     * format:
                     * 04.06.2024:teachers,students
                     * */
                    $remindersA = explode("\n", $reminders);
                    $tsent_nr = $ssent_nr = false;
                    $tsent = $ssent = 0;
                    foreach ($remindersA as $reminder) {
                        $items = explode(":", $reminder);
                        if (empty($reminder) or empty($items[0])) {
                            continue;
                        }
                        $tsent_nr = $ssent_nr = false;
                        $timestamp = strtotime($items[0]);
                        // print "<hr>timestamp: $timestamp - Date: ".date("d.m.Y",$timestamp)."<hr>";
                        $roles = explode(",", $items[1]);
                        // print "- Role: ";
                        foreach ($roles as $role) {
                            $nonResponderFlag = false;
                            if (stristr($role, " (NR)")) {
                                $role = str_ireplace(" (NR)", "", $role);
                                $nonResponderFlag = true;
                            }
                            // print $role.", ";
                            if ($role == "teachers") {
                                $tsent = $timestamp;
                                $tsent_nr = $nonResponderFlag;
                            } else if ($role == "students") {
                                $ssent = $timestamp;
                                $ssent_nr = $nonResponderFlag;
                            }
                        }
                    }
                    $week = 86400 * 7;
                    $durationdays = ($evaluation->timeclose -  $evaluation->timeopen)/86400;
                    $term = $week;
                    if ($durationdays > 84){
                        $term = 2 * $week;
                    } elseif ($durationdays > 49){
                        $term = 1.5 * $week;
                    }
                    $days = remaining_evaluation_days($evaluation);
                    $weekend = (idate("w")==6 OR idate("w")==0);
                    // print "<hr>tsent: ".date("d.m.Y",$ssent)." - ssent: "
                    //        .date("d.m.Y",$ssent)." - ".date("d.m.Y",time())."<hr>";
                    if (!$weekend AND $tsent) {
                        if (($tsent_nr and $tsent + (1 * $term) < time()) or ($tsent + (2 * $term) < time())) {
                            $reminders_sent = true;
                            mtrace("Evaluation '$evaluation->name': Sending reminders to teachers ($CFG->dbname)");
                            ev_send_reminders($evaluation, "teacher", false, $test, $verbose, $cli, $cronjob);
                        } else if ($tsent + (1 * $term) < time()) {
                            $reminders_sent = true;
                            mtrace("Evaluation '$evaluation->name': Sending reminders to non-responding teachers ($CFG->dbname)");
                            ev_send_reminders($evaluation, "teacher", true, $test, $verbose, $cli, $cronjob);
                        } else if ($days < 4 and ($tsent + (3 * 86400)) < time()) {
                            $reminders_sent = true;
                            mtrace("Evaluation '$evaluation->name': Sending final reminders to teachers ($CFG->dbname)");
                            ev_send_reminders($evaluation, "teacher", false, $test, $verbose, $cli, $cronjob);
                        }
                    }
                    $evaluation = $DB->get_record_sql("SELECT * from {evaluation} where id=" . $evaluation->id);
                    if (!$weekend AND $ssent) {
                        if (($ssent_nr and $ssent + (1 * $term) < time()) or ($ssent + (2 * $term) < time())) {
                            $reminders_sent = true;
                            mtrace("Evaluation '$evaluation->name': Sending reminders to students ($CFG->dbname)");
                            ev_send_reminders($evaluation, "student", false, $test, $verbose, $cli, $cronjob);
                        } else if ($ssent + (1 * $term) < time()) {
                            $reminders_sent = true;
                            mtrace("Evaluation '$evaluation->name': Sending reminders to non-responding students ($CFG->dbname)");
                            ev_send_reminders($evaluation, "student", true, $test, $verbose, $cli, $cronjob);
                        } else if ($days < 4 and $ssent + (3 * 86400) < time()) {
                            $reminders_sent = true;
                            mtrace("Evaluation '$evaluation->name': Sending final reminders to students ($CFG->dbname)");
                            ev_send_reminders($evaluation, "student", false, $test, $verbose, $cli, $cronjob);
                        }
                    }
                }
            }
            /*if (!$reminders_sent){
                mtrace("Evaluation '$evaluation->name': No reminders due for sending");
            }*/
            unset($_SESSION["EvaluationsID"]);
            validate_evaluation_sessions($evaluation);
        }
    }
    catch (Exception $e) {
        // Send error to admin with additional context
        mtrace("Task failed: " . $e->getMessage());
        $task->clear_fail_delay();
        $task->set_fail_delay(0);
        $task->set_next_run_time(0);
        // Disable the task to prevent further executions
        $task = $task->get_task_record();
        if ($task) {
            \core\task\manager::set_disabled($task->id, true);
            mtrace("Task has been disabled due to an error.");
        }
        ev_mail_error_to_admin($e);
        throw new moodle_exception('ev_cron_error', 'error', '', $e->getMessage());
    }
    mtrace('mod_evaluation: Completed processing send_reminders');
    return true;
}

// Case insensitive in_array
function in_arrayi($needle, $haystack) {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

// case insensitive array_search
function array_searchi($needle, $haystack) {
    foreach ($haystack as $key => $value) {
        if ($value == $needle) {
            return $key;
        }
    }
    return false;
}

// Retrieve a user's preferred language by username
function get_user_lang($username) {
    global $DB;

    // Check if the user exists
    $user = $DB->get_record('user', ['username' => $username], 'id, username, lang', MUST_EXIST);

    // Return the user's preferred language, or default to the site's default language
    return !empty($user->lang) ? $user->lang : get_string('defaultlang', 'langconfig');
}


// Retrieve a user's preferred language by user ID
function get_user_preferred_language($userid) {
    global $DB;

    // Check if the user exists
    $user = $DB->get_record('user', ['id' => $userid], 'id, lang', MUST_EXIST);

    // Return the user's preferred language, or default to the site's default language
    return !empty($user->lang) ? $user->lang : get_string('defaultlang', 'langconfig');
}

/**
 * Retrieve the site administrator user data.
 *
 * @return mixed|stdClass|null The site admin user data, or null if not found.
 * @throws dml_exception In case of database issues.
 */
function get_site_admin_user($username) {
    global $DB,$USER;

    // Fetch the first site admin's user ID from the config table.
    $siteadmins = get_config('moodle', 'siteadmins');

    if (empty($siteadmins)) {
        return null; // No site admins found.
    }

    // Get the first admin user ID.
    $siteadminids = explode(',', $siteadmins);
    foreach ($siteadminids as $siteadminid) {
        // Retrieve the site admin user data from the user table.
        if ( $siteadmin = $DB->get_record('user', ['id' => $siteadminid], '*')){
            if ($siteadmin->username == $username) {
                return $siteadmin;
            }
        }
    }
    if (is_siteadmin()) {
        return $USER;
    }
    return new stdClass();
}

/**
 * Sends error information to site admin
 *
 * @param Exception $error The caught exception
 * @param array $additional_info Optional additional information to include in email
 * @return bool Success/failure of email sending
 * // Usage example:
 * /* try {
 * ... code ....
 * } catch (Exception $e) {
 * // Send error to admin with additional context
 * ev_mail_error_to_admin($e,__FUNCTION__,additional_info);
 * throw new moodle_exception('scheduled_tasks_error', 'error', '', $e->getMessage());
 * /
 */

function ev_mail_error_to_admin($error, $additional_info = []) {
    global $CFG, $USER;

    // Get admin user
    $admin = get_admin();
    if (!$admin) {
        error_log('Could not find site admin for error notification');
        return false;
    }

    // Build error details
    $error_details = [
            'Timestamp' => date('Y-m-d H:i:s'),
            'Error Type' => get_class($error),
            'Error Message' => $error->getMessage(),
            'File' => $error->getFile(),
            'Line' => $error->getLine(),
            'User' => isset($USER->id) ? "{$USER->username} (ID: {$USER->id})" : 'Not logged in',
            'URL' => qualified_me(),
            'Referrer' => isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'N/A',
            'User Agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'N/A'
    ];

    // Add any additional info
    if (!empty($additional_info)) {
        $error_details['Additional Information'] = $additional_info;
    }

    // Add stack trace
    $error_details['Stack Trace'] = $error->getTraceAsString();

    // Format message
    $message = '';
    foreach ($error_details as $key => $value) {
        if (is_array($value)) {
            $message .= "$key:\n" . print_r($value, true) . "\n";
        } else {
            $message .= "$key: $value\n";
        }
    }

    // Email subject
    $subject = "[{$CFG->sitename}]: Error in {$error_details['file']} on {$error_details['line']}";

    mtrace('mod_evaluation: Error caught and mail was sent to ' . $admin->lastname);
    // Send email using Moodle's email function
    $mailstatus = $CFG->noemailever;
    $CFG->noemailever = 0;
    $ret = email_to_user(
            $admin,
            $USER, // core_user::get_support_user(),
            $subject,
            $message
    );
    $CFG->noemailever = $mailstatus;
    return $ret;
}

