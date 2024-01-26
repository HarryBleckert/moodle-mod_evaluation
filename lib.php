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

// ASH specific organisation of cours of studies (Studiengänge) = Level 2
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
function ev_get_string($string, $param = "") {
    $plugin = 'evaluation';
    $trstring = get_string($string, $plugin, $param);
    /*if ( !empty($param) )
	{	$trstring = get_string($string, $plugin, $param ); }
	else
	{	$trstring = get_string($string, $plugin ); }
	*/
    if (stristr($trstring, ']')) {
        return $string;
    }
    return $trstring;
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
		if ( document.getElementsById("page-footer").length > 0 ) 
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
	$thingnode = $previewnode->add($evaluation->name, new moodle_url('/mod/evaluation/view.php', array('id'=>$id )));
	$thingnode->make_active();
	*/
    // add navbar /mod/evaluation/
    $PAGE->navbar->add(get_string("modulenameplural", "evaluation"), $evurl);

    // view page settings
    $evurl = new moodle_url('/mod/evaluation/view.php', array('id' => $id));
    $PAGE->navbar->add($evaluation->name, $evurl);

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
    $PAGE->set_title($evaluation->name);
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

    // only siteadmin should call this
    if (isset($_SESSION['set_results_' . $evaluation->id]) or evaluation_is_open($evaluation)
            or intval(date("Ymd", $evaluation->timeopen)) > intval(date("Ymd"))) {
        return false;
    }

    if (evaluation_is_closed($evaluation)) {    // create item Studiengang
        $timecloseSaved = $evaluation->timeclose;
        $timeopen = ($evaluation->timeopen ? $evaluation->timeopen : time() - 86400);

        if (is_siteadmin()) {
            if ($CFG->ash and !evaluation_is_item_course_of_studies($evaluation->id)) {
                evaluation_autofill_item_studiengang($evaluation);
            }
            // shuffle userids per evaluation and course
            if ($evaluation->anonymous and !$evaluation->anonymized) {
                ev_shuffle_completed_userids($evaluation);
            }
        }
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
            //$_SESSION["teamteaching_courses"] = evaluation_count_teamteaching_courses($evaluation);

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

        // store per course evaulation details in tables evaluation_enrolments
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
                    @ob_flush();
                    @ob_end_flush();
                    @flush();
                    @ob_start();
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
                $cnt = 1;
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
                        print "<br>\n" . str_pad(number_format($cnt), 6, " ", STR_PAD_LEFT) . ". <b>$participant</b>: " .
                                str_pad($userid, 6) . " - "
                                . str_pad($username, 12) . " - " . str_pad($fullname, 42) . " - Replies $utype: $participated\n";
                        @ob_flush();
                        @ob_end_flush();
                        @flush();
                        @ob_start();
                    }
                    $cnt++;
                }
            }
        }
        $evaluation->timeclose = $timecloseSaved;
    }
    return true;
}

function ev_shuffle_completed_userids($evaluation, $force = false) {
    global $DB;
    if ($evaluation->anonymous < 1) {
        print "<br>Funktion ev_shuffle_completed_userids(): Die Evaluation '$evaluation->name' ist nicht anonym.<br>"
                . "Daher ist keine Anonymisierung der Abgaben erforderlich!<br>\n";
        return;
    } else if ($evaluation->anonymized and !$force) {
        print "<br>Funktion ev_shuffle_completed_userids(): Die Evaluation '$evaluation->name' wurde bereits anonymisiert.<br>"
                . "Daher ist keine Anonymisierung der Abgaben erforderlich!<br>\n";
        return;
    }
    $courses = evaluation_participating_courses($evaluation);
    $cntC = 1;
    print '<br><hr>ev_shuffle_completed_userids(): Course id = <span id="showCourseRec_' . $evaluation->id . '">&nbsp;</span><hr>';
    ini_set("output_buffering", 256);
    @ob_flush();
    @ob_end_flush();
    @flush();
    @ob_start();
    foreach ($courses as $courseid) {
        print '<script>document.getElementById("showCourseRec_' . $evaluation->id . '").innerHTML = "<b>' . $courseid . '</b> (' .
                $cntC . ' courses)";</script>';
        $cntC++;
        @ob_flush();
        @ob_end_flush();
        @flush();
        @ob_start();
        $completed = $DB->get_records_sql("SELECT * FROM {evaluation_completed} WHERE evaluation=$evaluation->id AND courseid=$courseid 
						ORDER BY id");
        $userids = array();
        if (safeCount($completed)) {
            foreach ($completed as $record) {
                $userids[] = $record->userid;
            }
            //print "<br><hr>userids: " . var_export($userids, true);
            shuffle($userids);
            $cnt = 0;
            foreach ($completed as $record) {
                $record->userid = $userids[$cnt];
                $cnt++;
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
    $evaluationName = $evaluation->name;
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
            $CourseRec = $DB->get_record_sql("SELECT DISTINCT ON (courseid) courseid, fullname, shortname, teacherids, id FROM {evaluation_enrolments} 
												WHERE evaluation = $evaluation->id AND courseid = $courseid");
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
                    $isStudent = !empty($DB->get_record_sql("SELECT DISTINCT ON (userid) userid, evaluation, id FROM {evaluation_completed} 
														WHERE evaluation = $evaluation->id AND userid = $user->id"));
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
    if (evaluation_isPrivilegedUser($evaluation, $user) or is_siteadmin()) //AND $evaluation->course == SITEID) )
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
    if (in_array($user->username, $privileged_users) or evaluation_cosPrivileged($evaluation)) {
        $_SESSION["EVALUATION_OWNER"] = $user->username;
        return true;
    }
    if ( !isset($_SESSION["privileged_global_users"]) OR !is_array($_SESSION["privileged_global_users"])) {
        ev_set_privileged_users();
    }
    if (in_array($user->username, $_SESSION["privileged_global_users"])) {
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
    return (isset($_SESSION['CoS_privileged'][$USER->username]) and !empty($_SESSION['CoS_privileged'][$USER->username]));
}

// is user CoS privileged users
function evaluation_get_cosPrivileged_filter($evaluation, $tableName = "") {
    global $USER;
    $filter = ""; //" AND true";
    // get CoS privileged users
    if (!isset($_SESSION['CoS_privileged'])) {
        ev_set_privileged_users();
        get_evaluation_filters($evaluation);
    }
    if (false) //evaluation_debug( false ) )
    {
        print "<br><hr>:\n_SESSION['CoS_privileged']: " . nl2br(var_export($_SESSION['CoS_privileged'], true)) . "<hr>\n";
    }
    if (!empty($_SESSION['CoS_privileged'][$USER->username]))  //isset($_SESSION['CoS_privileged'][$USER->username]) AND
    {
        $filter = " AND " . ($tableName ? $tableName . "." : "") . "course_of_studies ";
        if (safeCount($_SESSION['CoS_privileged'][$USER->username]) > 0) {
            $filter .= " IN ('" . implode("','", $_SESSION['CoS_privileged'][$USER->username]) . "')";
        }
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
            if ($is_WM and in_array($CoS, $sg_filter)) {
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
        $dept = array_search($cos, $keys);
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

    //if ( $USER->username !=="fuchsb" AND !evaluation_debug( false ) AND empty($_SESSION["LoggedInAs"]) ) //AND empty($USER->realuser)
    if (!is_siteadmin() and empty($_SESSION["LoggedInAs"])) {
        return false;
    }
    if (!defined("SiteEvaluation") or !empty($downloading)
            or (isset($_SESSION["EVALUATION_OWNER"]) and
                    $_SESSION["EVALUATION_OWNER"] !== $USER->id and empty($_SESSION["LoggedInAs"]) and empty($USER->realuser))) {
        return false;
    }
    $userid = false;
    $role = optional_param('LoginAs', "", PARAM_ALPHANUM);
    $cmid = $id;
    $isActive = "deleted=0 AND suspended=0 AND ";
    list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation);
    evaluation_cosPrivileged($evaluation);
    $CoS_privileged = array();
    foreach ($_SESSION['CoS_privileged'] AS $CoSuser=>$CoSA){
        foreach ( $CoSA AS $cusername => $CoS){
            if ( in_array($CoS, $sg_filter)){
                $CoS_privileged[$CoSuser] = $CoS;
            }
        }
    }
    $CoS_privileged_cnt = safeCount($CoS_privileged);

    if ($role == "logout" and !empty($USER->realuser)) {
        $userid = $_SESSION["EVALUATION_OWNER"] = $USER->realuser;
    } else if (stristr($role, "privileg")) {
        $role = "Privilegierte Person";
        if (!empty($evaluation->privileged_users) or !empty($_SESSION["privileged_global_users"])) {
            $privileged_users = explode("\n", $evaluation->privileged_users);
            if (!empty($_SESSION["privileged_global_users"])) {    //array_merge_recursive_distinct( $privileged_users, $_SESSION["privileged_global_users"]);
                $privileged_users += $_SESSION["privileged_global_users"];
            }
            $cnt = 0;
            $choice = random_int(0, intval(safeCount($privileged_users)));
            if (false) //evaluation_debug( false ) )
            {
                print "<br><hr>:Selected: $choice\nprivileged_users: " . nl2br(var_export($privileged_users, true)) . "<hr>\n";
            }
            foreach ($privileged_users as $username) {
                if ($cnt == $choice) {    //print "<br><hr>Current choice: '$username'<hr>\n";
                    $user = $DB->get_record_sql("SELECT id,username from {user} WHERE $isActive username='" . trim($username) . "'");
                    if (isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if (safeCount($privileged_users) > $cnt) {
                        $choice++;
                    }
                }
                $cnt++;
            }
        }
    } else if (stristr($role, "Dekan")) {
        $role = "Dekan_in";

        if ($CoS_privileged_cnt) {
            $cnt = 0;
            $choice = random_int(0, $CoS_privileged_cnt);
            if (false) //evaluation_debug( false ) )
            {
                print "<br><hr>\$CoS_privileged_cnt: $CoS_privileged_cnt:\n_CoS_privileged: "
                        . nl2br(var_export($CoS_privileged, true)) . "<hr>\n";
            }
            foreach (array_keys($CoS_privileged) as $uKey) {
                if ($cnt == $choice) {
                    $username = $uKey;
                    print "<br><hr>uKey: \n" .nl2br(var_export($uKey,true))."<hr>\n";
                    if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive username='$username'") and isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if ($CoS_privileged_cnt > $cnt) {
                        $choice++;
                    }
                }
                $cnt++;
            }
        }
    } else if (strstr($role, "teacher")) {
        $role = "editingteacher";
        if (false AND $teacheridSaved) {
            $userid = $teacheridSaved;
        } else {
            $teachers = $DB->get_records_sql("SELECT DISTINCT ON (teacherid) teacherid, id 
                                from {evaluation_completed} 
								WHERE evaluation=$evaluation->id ORDER BY teacherid");
            if ( $teachers ) {
                $choice = random_int(1, intval(safeCount($teachers)));
                $cnt = 1;
                foreach ($teachers as $teacher) {
                    if ($cnt == $choice) {
                        if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive id='$teacher->teacherid'") and isset($user->id)) {
                            $userid = $user->id;
                            break;
                        } else if (safeCount($teachers) > $cnt) {
                            $choice++;
                        }
                    }
                    $cnt++;
                }
            }
        }
    } else if ($role == "student") {
        $students = $DB->get_records_sql("SELECT DISTINCT ON (userid) userid, 
                            id from {evaluation_completed} 
							WHERE evaluation=$evaluation->id ORDER BY userid");
        if ( $students ) {
            $choice = random_int(1, intval(safeCount($students)));
            $cnt = 1;
            foreach ($students as $student) {
                if ($cnt == $choice) {
                    if ($user = $DB->get_record_sql("SELECT id from {user} WHERE $isActive id='$student->userid'") and isset($user->id)) {
                        $userid = $user->id;
                        break;
                    } else if (safeCount($students) > $cnt) {
                        $choice++;
                    }
                }
                $cnt++;
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
            unset($_SESSION["LoggedInAs"], $_SESSION["myEvaluations"], $_SESSION["EvaluationsName"]);
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
            unset($_SESSION["EvaluationsName"]);
            $role = stristr($role, "privileg") ? "privilegierte Person"
                    : (stristr($role, "dekan") ? "Dekan_in"
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
        $role = (stristr($_SESSION["LoggedInAs"], "privileg") ? "privilegierte Person"
                : (stristr($_SESSION["LoggedInAs"], "dekan") ? "Dekan_in" :
                        $DB->get_record('role', array('shortname' => $_SESSION["LoggedInAs"]), '*')->name));
        $msg .= "Aktuelle Ansicht: " . $role . '&nbsp; <a href="' . $url . '&LoginAs=logout">Rollenansicht beenden</a>';
    } else {
        $msg .= 'Rollenansicht wählen: '
                . ((!empty($evaluation->privileged_users) or !empty($_SESSION["privileged_global_users"]))
                        ? '<a href="' . $url . '&LoginAs=privileg">Privilegiert</a> - ' : "")
                . ($CoS_privileged_cnt ? '<a href="' . $url . '&LoginAs=dekan">Dekan_in</a> - ' : "")
                . '<a href="' . $url . '&LoginAs=teacher">Dozent_in</a> - <a href="'
                . $url . '&LoginAs=student">Student_in</a> - <a href="' . $url . '&LoginAs=user">ASH Mitglied</a>';
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
    $cnt = 1;
    foreach ($users as $user) {
        $cnt++;
        if ($cnt <= $choice) {
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

function possible_evaluations($evaluation, $courseid = false, $active = false) // teacherid=false, $course_of_studies=false)
{
    global $DB;
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
        foreach ($_SESSION["possible_evaluations"] as $key => $maxEvaluations) {
            if ( $courseid AND $courseid != $key )
            {	continue; }
            $possible_evaluations += $maxEvaluations;
        }

    } else {
    $_SESSION["possible_evaluations"] = $_SESSION["possible_active_evaluations"] = array();
        $enrolments = $DB->get_records_sql("SELECT * from {evaluation_enrolments} WHERE evaluation=" . $evaluation->id);
        foreach ($enrolments as $enrolment) {
            if ($courseid AND $enrolment->courseid == $courseid){
                continue;
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

function possible_active_evaluations($evaluation) {
    return possible_evaluations($evaluation, false, true);
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
    if ($force or empty($_SESSION["allteachers"]) or !isset($_SESSION["teamteaching_courses"])) {
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
    $showTeachers = "<br>Dieser Kurs hat keine Dozent_innen";
    if (safeCount($teachers) > 0) {
        $showTeachers = "<br>Dozent_in" . (safeCount($teachers) > 1 ? "nen" : "") . ": ";
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
    if (!isset($_SESSION["EvaluationsName"]) || $_SESSION["EvaluationsName"] != $evaluation->name) {
        unset($_SESSION["myEvaluations"], $_SESSION['anonresponsestable'], $_SESSION['responsestable'],
                $_SESSION["numStudents"], $_SESSION["teachers"], $_SESSION["showTeachers"],
                $_SESSION["participating_courses"], $_SESSION["participating_empty_courses"],
                $_SESSION["distinct_s"], $_SESSION["distinct_s_active"], $_SESSION["students"], $_SESSION["students_active"], $_SESSION["active_student"],
                $_SESSION["distinct_t"], $_SESSION["distinct_t_active"], $_SESSION["Teachers"], $_SESSION["Teachers_active"], $_SESSION["active_teacher"],
                $_SESSION["teamteaching_courses"], $_SESSION["teamteaching_courseids"], $_SESSION["questions"],
                $_SESSION["participating_courses_of_studies"], $_SESSION['EVALUATION_OWNER'],
                $_SESSION['filter_course_of_studies'], $_SESSION['course_of_studies'], $_SESSION["notevaluated"], $_SESSION['CoS_department'],
                $_SESSION['CoS_privileged'], $_SESSION['filter_courses'], $_SESSION["numStudents"], $_SESSION["possible_evaluations"],
                $_SESSION["possible_active_evaluations"], $_SESSION["active_teacher"], $_SESSION["active_student"],
                $_SESSION["num_courses_of_studies"], $_SESSION["duplicated"], $_SESSION["orderBy"],
                $_SESSION["distinct_users"], $_SESSION["evaluated_teachers"], $_SESSION["evaluated_courses"], $_SESSION["privileged_global_users"],
                $_SESSION["privileged_global_users_wm"], $_SESSION["course_of_studies_wm"], $_SESSION['ev_global_cfgfile'],
        );
        //unset( $_SESSION['allteachers'] );
    }
    $_SESSION["EvaluationsName"] = $evaluation->name;
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
    @ob_flush();
    @ob_end_flush();
    @flush();
    @ob_start();
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
        return round(($difference / 60 / 60 / 24));
    }
    return 1;
}

function current_evaluation_day($evaluation) {
    $timeopen = ($evaluation->timeopen > 0 ? $evaluation->timeopen : 0);
    $timeclose = ($evaluation->timeclose > 0 ? $evaluation->timeclose : 0);
    $difference = time() - $timeopen;
    if ($difference > 0 and $timeclose > time()) {    //return round(($difference/60/60/24)+1,1);
        return max(1, round(($difference / 60 / 60 / 24), 1));
    }
    return total_evaluation_days($evaluation);
}

function remaining_evaluation_days($evaluation) {
    $timeclose = ($evaluation->timeclose > 0 ? $evaluation->timeclose : 0);
    $difference = $timeclose - time();
    if ($difference > 0 and $timeclose > time()) {
        return round(($difference / 60 / 60 / 24));
    }
    return 0;
}

// get array of of Studiengangs for semester of evaluation
function evaluation_get_course_studies($evaluation, $link = false, $raw = false) {
    global $DB;
    $is_closed = (!evaluation_is_open($evaluation) and $evaluation->timeopen < time());
    list($sg_filter, $courses_filter) = get_evaluation_filters($evaluation, false);
    if (!empty($courses_filter)) {
        $sg_courses_filter = evaluation_get_course_of_studies_from_courseids($courses_filter);
    }
    $sgTmp = $sgNtmp = $studynames = array();
    /*if ( $is_closed ) {
		$course_studies_raw = $DB->get_records_sql( "select DISTINCT ON (course_of_studies) completed.* from {evaluation_completed} AS completed
														WHERE evaluation=$evaluation->id ORDER BY course_of_studies");
	}
	else{*/
    $evaluation_semester = get_evaluation_semester($evaluation);
    // get path of current semester
    $cat = $DB->get_record_sql("select id,idnumber,path from {course_categories} where idnumber='$evaluation_semester' LIMIT 1");
    //print_r("PATH:" .$cat->path);
    if (empty($cat->path)) {
        return array();
    }
    $course_studies_raw =
            $DB->get_records_sql("select id,idnumber,name AS course_of_studies from {course_categories} where path like '" .
                    $cat->path . "/%' AND array_length(string_to_array(path, '/'), 1)-1 =2");
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
                if (!in_array($course_studies->course_of_studies, $sg_filter)) {
                    continue;
                }
                /*if (!empty($courses_filter) and in_array($course_studies->course_of_studies, $sg_courses_filter)) {
                    continue;
                }*/
            } else if (!empty($courses_filter)) {
                $sg_filter = evaluation_get_course_of_studies_from_courseids($courses_filter);
                if (!in_array($course_studies->course_of_studies, $sg_filter)) {
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
    return array_search($course_of_studies, $allStudies);

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

function evaluation_user_lastaccess($evaluation, $userid, $lastaccess = 0, $role = "student", $courseid=false) {
    global $DB;
    if (empty($lastaccess)) {
        $lastaccess = 0;
    }
    if (!isset($evaluation->timeclose)) {
        return 0;
    } //$lastaccess; }
    if ( !$courseid){
        $courseid = "";
    }
    $userlast = $DB->get_record_sql("SELECT * from {evaluation_users_la} WHERE evaluation=" . $evaluation->id .
            " AND userid=$userid AND role='$role' LIMIT 1");
    $is_open = evaluation_is_open($evaluation);
    $update = false;

    if (isset($userlast->lastaccess) and !$is_open) {
        $lastaccess = $userlast->lastaccess ?: 0;
    } else if ($is_open) {
        if (!isset($userlast->lastaccess)) {
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
function evaluation_has_user_participated($evaluation, $userid, $courseid = false) {
    global $DB;
    $filter = "";
    if ($courseid) {
        $filter = " AND courseid=$courseid";
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

function evaluation_is_student($evaluation, $myEvaluations, $courseid = false) {
    global $USER;
    /*if (!$myEvaluations) {
		if ( isset($_SESSION["myEvaluations"]) ) {
			$myEvaluations = $_SESSION["myEvaluations"];
		}
		else{
			return false;
		}

	}*/
    foreach ($myEvaluations as $myEvaluation) {
        if ($myEvaluation['role'] == "student" and $myEvaluation['id'] == $USER->id) {
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

function array_merge_recursive_new() {
    $arrays = func_get_args();
    $base = array_shift($arrays);

    foreach ($arrays as $array) {
        reset($base); //important
        while (list($key, $value) = @each($array)) {
            if (is_array($value) && @is_array($base[$key])) {
                $base[$key] = array_merge_recursive_new($base[$key], $value);
            } else {
                if (isset($base[$key]) && is_int($key)) {
                    $key++;
                }
                $base[$key] = $value;
            }
        }
    }

    return $base;
}

// get all participants of evaluation by various filters. Count teachers and students in all participating courses of given evaluation
function get_evaluation_participants($evaluation, $userid = false, $courseid = false, $getTeachers = false, $getStudents = false) {
    global $DB, $USER;
    if ($userid > 0) {
        $user = core_user::get_user($userid);
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
    $timeopen = ($evaluation->timeopen > 0) ? $evaluation->timeopen : (time() - 80600);
    $timeclose = ($evaluation->timeclose > 0) ? $evaluation->timeclose : (time() + 80600);
    $cnt_courses = $cnt_empty_courses = $cnt_students = $cnt_teachers = $cnt_students_active = $cnt_teachers_active = 0;
    $distinct_s_active = $distinct_s = $distinct_t_active = $distinct_t = $courseTeachers = array();
    $my_evaluation_courses = $my_evaluation_users = $ids = array();
    $fcourses = "WHERE true";
    $ids = array();
    $filter = " AND RIGHT(idnumber,5) = '$evaluation_semester' ";

    if ($evaluation->course !== SITEID) {
        $filter = " AND id=$evaluation->course";
    } else if ($courseid) //AND stristr($filter,"idnumber") )
    {
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
        if (isset($course->idnumber) and !empty($course->idnumber)) //$from_course )
        {    //if ( $evaluation->course == SITEID AND substr( $course->idnumber, -5) !== $evaluation_semester )
            //{	continue; }
            if (!pass_evaluation_filters($evaluation, $course->id)) {
                continue;
            }
        }
        $courseid = $course->id;
        // full filtering
        list($show, $reminder) =
                evaluation_filter_Evaluation($course->id, $evaluation, $user); //($userid AND $userid > 0 ?$user :false) );
        if (!$show) {
            continue;
        }
        $cnt_courses++;

        if (evaluation_is_empty_course($course->id)) {
            $cnt_empty_courses++;
        }

        //$contextC = get_context_instance(CONTEXT_COURSE, $course->id);
        $contextC = context_course::instance($course->id);

        // should be only used when open! Problem: No replacement yet for get_role_users:
        // $evaluation_is_open AND
        if ( (is_array($contextC) or is_object($contextC))) {
            // $cnt=0;
            foreach ($roleT as $role) {
                $rolesC = get_role_users($role->id, $contextC);
                foreach ($rolesC as $roleC) {
                    /* if ($cnt<1 AND is_siteadmin()){
                        print "<hr>RolesC:\n" .nl2br(var_export($roleC, true));
                    }
                    $cnt++;
                    */
                    $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                    if ($roleC->roleid == 5)  // student
                    {
                        $cnt_students++;
                        $numStudentsCourse++;
                        $distinct_s[$roleC->id] = $fullname; //=$cnt_students;
                        // get inactive students
                        $roleC->lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "student", $courseid);
                        //if ( $roleC->lastaccess > ( $evaluation_is_open ?(time()-(24*3600*$total_evaluation_days) ) :$timeopen ) )
                        if ($roleC->lastaccess > $timeopen) // ( $evaluation_is_open ?$timeopen :$timeclose ) )
                        {
                            $cnt_students_active++;
                            $numStudentsActiveCourse++;
                            $distinct_s_active[$roleC->id] = $fullname;
                        }

                        if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                            $my_evaluation_courses[$course->id] = array("role" => "student", "id" => $roleC->id,
                                    "username" => $roleC->username,
                                    "email" => $roleC->email, "fullname" => $fullname, "courseid" => $course->id,
                                    "course" => $course->fullname, "shortname" => $course->shortname,
                                    "lastaccess" => $roleC->lastaccess,
                                    "reminder" => $reminder);
                        } else if ($getStudents) {
                            $my_evaluation_users[$roleC->id] =
                                    array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username,
                                            "email" => $roleC->email, "firstname" => $roleC->firstname,
                                            "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                            "role" => "student", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                        }

                    } else {
                        $cnt_teachers++;
                        $numTeachersCourse++;
                        $distinct_t[$roleC->id] = $fullname; //=$cnt_teac
                        $roleC->lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "teacher", $courseid);
                        // get inactive teachers
                        //if ( $roleC->lastaccess > ( $evaluation_is_open ?(time()-(24*3600*$total_evaluation_days) ) :$timeopen ) )
                        if ($roleC->lastaccess > $timeopen) // ( $evaluation_is_open ?$timeopen :$timeclose ) )
                            //if ( $roleC->lastaccess > $timeopen )
                        {
                            $cnt_teachers_active++;
                            $numTeachersActiveCourse++;
                            $distinct_t_active[$roleC->id] = $fullname;
                        }
                        if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                            $my_evaluation_courses[$course->id] =
                                    array("role" => "teacher", "id" => $roleC->id, "username" => $roleC->username,
                                            "email" => $roleC->email, "fullname" => $fullname, "courseid" => $course->id,
                                            "course" => $course->fullname,
                                            "shortname" => $course->shortname, "lastaccess" => $roleC->lastaccess,
                                            "reminder" => $reminder);
                        } else if ($getTeachers) {
                            $my_evaluation_users[$roleC->id] =
                                    array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username,
                                            "email" => $roleC->email, "firstname" => $roleC->firstname,
                                            "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                            "role" => "teacher", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                        }
                    }
                }
            }
        }

        if (!$evaluation_is_open) //else	// from evc
        {    // get students

            $rolesC = $DB->get_records_sql("SELECT evc.id AS evcid, evc.courseid, evc.userid AS id, evc.teacherid, evu.id AS evuid, evu.username,
													evu.firstname, evu.lastname, evu.alternatename, evu.email, evul.lastaccess
												FROM {evaluation_completed} AS evc, {evaluation_users} AS evu, {evaluation_users_la} AS evul
												WHERE evc.evaluation = $evaluation->id AND evc.courseid=$course->id AND evu.userid = evc.userid
														AND evul.userid = evc.userid AND evul.evaluation = evc.evaluation");
            /*
            $rolesC = $DB->get_records_sql("SELECT evc.id AS evcid, evc.courseid, evc.userid AS id, evc.teacherid, evu.id AS evuid, evu.username, 
													evu.firstname, evu.lastname, evu.alternatename, evu.email, evul.lastaccess
												FROM {evaluation_enrolments} AS eve, {evaluation_users_la} AS evul, {evaluation_users} AS evu
												WHERE evul.evaluation = $evaluation->id AND evul.user->id AND evu.userid = evc.userid
														AND evul.userid = evc.userid AND evul.evaluation = evc.evaluation");
            */
            //print "<hr>Students rolesC: ".nl2br(var_export($rolesC,true)) ."<hr>";
            foreach ($rolesC as $roleC) {
                $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                if (!isset($distinct_s[$roleC->id])) {
                    $cnt_students++;
                    $numStudentsCourse++;
                }
                $distinct_s[$roleC->id] = $fullname; //=$cnt_students;
                // get inactive students
                //$userdata = core_user::get_user($roleC->id);
                $roleC->lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "student", $courseid);
                if ($roleC->lastaccess > $timeopen and !isset($distinct_s_active[$roleC->id])) {
                    $cnt_students_active++;
                    $numStudentsActiveCourse++;
                    $distinct_s_active[$roleC->id] = $_SESSION["active_student"][$roleC->id] = $fullname;
                }

                if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                    $my_evaluation_courses[$course->id] =
                            array("role" => "student", "id" => $roleC->id, "username" => $roleC->username,
                                    "email" => $roleC->email, "fullname" => $fullname, "courseid" => $course->id,
                                    "course" => $course->fullname,
                                    "shortname" => $course->shortname, "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                } else if ($getStudents) {
                    $my_evaluation_users[$roleC->id] =
                            array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username,
                                    "email" => $roleC->email, "firstname" => $roleC->firstname,
                                    "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                    "role" => "student", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                }
            }
            // get teachers
            $rolesC = $DB->get_records_sql("SELECT evc.id AS evcid, evc.courseid, evc.teacherid AS id, evc.teacherid, evu.id AS evuid, evu.username, 
													evu.firstname, evu.lastname, evu.alternatename, evu.email, evul.lastaccess
												FROM {evaluation_completed} AS evc, {evaluation_users} AS evu, {evaluation_users_la} AS evul
												WHERE evc.evaluation = $evaluation->id AND evc.courseid=$course->id AND evc.teacherid = evu.userid
													AND evul.userid = evc.teacherid  AND evul.evaluation = evc.evaluation");
            //print "<hr>Teachers rolesC: ".nl2br(var_export($rolesC,true)) ."<hr>";
            foreach ($rolesC as $roleC) {
                $fullname = ($roleC->alternatename ? $roleC->alternatename : $roleC->firstname) . " " . $roleC->lastname;
                if (!isset($distinct_t[$roleC->id])) {
                    $cnt_teachers++;
                    $numTeachersCourse++;
                }
                $distinct_t[$roleC->id] = $fullname; //=$cnt_teac
                // get inactive teachers
                //$userdata = core_user::get_user($roleC->id);
                $roleC->lastaccess = evaluation_user_lastaccess($evaluation, $roleC->id, $roleC->lastaccess, "teacher", $courseid);
                if ($roleC->lastaccess > $timeopen and !isset($distinct_t_active[$roleC->id])) {
                    $cnt_teachers_active++;
                    $numTeachersActiveCourse++;
                    $distinct_t_active[$roleC->id] = $_SESSION["active_teacher"][$roleC->id] = $fullname;
                }
                if ($userid and ($userid < 0 or $userid == $roleC->id)) {
                    $my_evaluation_courses[$course->id] =
                            array("courseid" => $course->id, "role" => "teacher", "id" => $roleC->id,
                                    "username" => $roleC->username, "email" => $roleC->email, "fullname" => $fullname,
                                    "course" => $course->fullname,
                                    "shortname" => $course->shortname, "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                } else if ($getTeachers) {
                    $my_evaluation_users[$roleC->id] =
                            array("fullname" => $fullname, "id" => $roleC->id, "username" => $roleC->username,
                                    "email" => $roleC->email, "firstname" => $roleC->firstname,
                                    "lastname" => $roleC->lastname, "alternatename" => $roleC->alternatename,
                                    "role" => "teacher", "lastaccess" => $roleC->lastaccess, "reminder" => $reminder);
                }

            }
        } // from evc$numTeachersCourse++;
        // if ( !$getTeachers AND !$getStudents){
        $_SESSION["possible_evaluations"][$courseid] = $_SESSION["possible_active_evaluations"][$courseid] = 0;
        if ($numTeachersCourse) {
            $_SESSION["possible_evaluations"][$courseid]
                    = ($numStudentsCourse * ($evaluation->teamteaching ? $numTeachersCourse : 1));
        }
        if ($numTeachersActiveCourse) {
            $_SESSION["possible_active_evaluations"][$courseid]
                    = ($numStudentsActiveCourse * ($evaluation->teamteaching ? $numTeachersActiveCourse : 1));
        }
        //}
    }
    $_SESSION["distinct_s"] = safeCount($distinct_s);
    if ($userid) {
        return $my_evaluation_courses;
    } else if ($getStudents || $getTeachers) {
        return $my_evaluation_users;
    } else {
        return array($cnt_courses, $cnt_empty_courses,
                safeCount($distinct_s), safeCount($distinct_s_active), $cnt_students, $cnt_students_active,
                safeCount($distinct_t), safeCount($distinct_t_active), $cnt_teachers, $cnt_teachers_active);
    }
}

function ev_get_participants($myEvaluations, $courseid = false) {
    global $evaluation;
    $possible_evaluations = 0;
    if ( evaluation_is_closed($evaluation) ) { // AND $courseid AND !isset($_SESSION["possible_evaluations"][$courseid]) ){
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
    if (safeCount($myEvaluations)) {
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
            $str .= "<h2>" . ($showName ? "<b>" . $myEvaluation["fullname"] . "</b>:" . ($userResults ? " Ihre" : "") : "")
                    . " Kurse für die " . $evaluation->name . "</h2>\n";
            if ($myEvaluation["role"] != "teacher") {
                $num_courses = safeCount(ev_courses_of_id($evaluation, $myEvaluation["id"]));
                if (!$evaluation_is_open and $num_courses > safeCount($myEvaluations)) {
                    // $ismycourses = ($myEvaluation["id"]==$USER->id);
                    $isstudent = ($myEvaluation["role"] == 'student' and $myEvaluation["id"] == $USER->id);
                    $str .= "\n<b>Hinweis</b>: Ihnen " . (safeCount($myEvaluations) > 1
                                    ? "werden nur die " . safeCount($myEvaluations) . " Kurse angezeigt, " .
                                    ($isstudent ? "für die Sie" : "für die")
                                    : "wird nur der Kurs angezeigt, " . ($isstudent ? "für den Sie" : "für den")
                            )
                            . ($isstudent ? " an der Evaluation teilgenommen haben" : " Abgaben erfolgt sind")
                            . ". Es nahmen $num_courses Ihrer Kurse an dieser Evaluation teil.<br>\n";
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
            if (safeCount($teachers)) {
                // $possible_evaluations_per_teacher += round(ev_get_participants($myEvaluations, $myEvaluation["courseid"]) / safeCount($teachers),0);
                $possible_evaluations_per_teacher += round(ev_get_participants($myEvaluations, $myEvaluation["courseid"]) / safeCount($teachers), 0);
            }
            $possible_evaluations += round(ev_get_participants($myEvaluations, $myEvaluation["courseid"]), 0);
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

            if (!$evaluation_is_open or $isTeacher or stristr($myEvaluation["reminder"], get_string("analysis", "evaluation"))) {
                $color = "grey";
                $actionTxt = get_string("analysis", "evaluation");
                // link to Evaluation Overview
                $urlF = "<a href=\"$wwwroot/mod/evaluation/view.php?id=$cmid&courseid=" . $myEvaluation["courseid"] . "\">";
                if ($replies >= $min_results and $evaluation_has_user_participated) {
                    $color = "darkgreen";
                    $min_resInfo = "";
                    // see graphic results
                    $urlF = "<a href=\"$wwwroot/mod/evaluation/analysis_course.php?id=$cmid&courseid=" . $myEvaluation["courseid"]
                            . (($isTeacher and $userResults) ? "&teacherid=" . $myEvaluation["id"] : "") . "\">";
                }
                if (empty($_SESSION["LoggedInAs"])) {
                    $urlC = "<a href=\"$wwwroot/course/view.php?id=" . $myEvaluation["courseid"] . "\">";
                } else {
                    $urlC = "<a href=\"#\">";
                }
                $str .= "<tr>\n";
                $str .= "<td $min_resInfo>$urlF<b style=\"color:$color;\">$actionTxt</b></a></td>
							<td style=\"text-align:right;\">" . $replies . "</td>";
                if (empty($_SESSION["LoggedInAs"])) {
                    $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></a></td>\n";
                } else {
                    $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></td>\n";
                }
                $str .= "</tr>\n";
            } else {
                $replies = "";
                $cnt = 0;
                if ($evaluation->teamteaching) {    // get course teachers
                    //if ( !isset( $_SESSION["allteachers"][$myEvaluation["courseid"]] ) )
                    //{	get_course_teachers( $evaluation, $myEvaluation["courseid"], $cmid ); }
                    //print "<br><hr>SESSION['allteachers'][myEvaluation[courseid]]: ";var_dump($_SESSION['allteachers'][$myEvaluation['courseid']]);print "<hr>\n";
                    //$missing = isEvaluationCompleted( $evaluation, false, false, true );
                    foreach ($teachers as $teacher) {
                        $Txt = $actionTxt;
                        $url = $urlF;
                        $str .= "<tr>\n";
                        $completed = $DB->get_record_sql("select id,evaluation,courseid,userid,teacherid from {evaluation_completed} 
										WHERE evaluation=" . $evaluation->id . " AND userid=" . $myEvaluation["id"]
                                . " AND courseid=" . $myEvaluation['courseid'] . " AND teacherid=" . $teacher['id']);
                        //$str .= "$actionTxt";
                        if ($completed and isset($completed->teacherid) and $completed->teacherid == $teacher['id']) {
                            $color = "green";
                            $Txt = '<span style="font-weight:normal;color:$color;">Abgegeben für<br>Dozent_in '
                                    . $teacher['fullname'] . '</span>';
                            $str .= "<td><b style=\"color:$color;\">$Txt</b></td>";
                        } else {
                            $color = "darkred";
                            $Txt .= '<span style="font-weight:normal;color:black;"><br>Dozent_in ' . $teacher['fullname'] .
                                    '</span>';
                            $url = "<a href=\"$wwwroot/mod/evaluation/complete.php?id=$cmid&courseid=" . $myEvaluation["courseid"] .
                                    "&teacherid=" . $teacher['id'] . '">';
                            $str .= "<td>$url<b style=\"color:$color;\">$Txt</b></a></td>";
                        }
                        if (empty($_SESSION["LoggedInAs"])) {
                            $urlC = "<a href=\"$wwwroot/course/view.php?id=" . $myEvaluation["courseid"] . "\">";
                        } else {
                            $urlC = "<a href=\"#\">";
                        }
                        $str .= "<td style=\"text-align:right;\">" . $replies . "</td>";
                        if (empty($_SESSION["LoggedInAs"])) {
                            $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></a></td>\n";
                        } else {
                            $str .= "<td>$urlC<span style=\"color:blue;\">" . $myEvaluation["course"] . "</span></td>\n";
                        }
                        $str .= "</tr>\n";
                        $cnt++;
                    }
                } else {
                    $Txt = $actionTxt;
                    $url = $urlF;
                    $completed = array();
                    $str .= "<tr>\n";
                    $completed = $DB->get_record_sql("select id,evaluation,courseid,userid,teacherid from {evaluation_completed} 
								WHERE evaluation=" . $evaluation->id . " AND userid=" . $myEvaluation["id"]
                            . " AND courseid=" . $myEvaluation['courseid']);
                    if (isset($completed->teacherid) and safeCount($completed)) {
                        $color = "green";
                        $Txt = '<span style="font-weight:normal;color:' . $color . ';">Abgegeben</span>';
                        $str .= "<td><b style=\"color:$color;\">$Txt</b></td>";
                    } else {
                        $Txt .= '<span style="font-weight:normal;color:black;">Abzugeben</span>';
                        $url = "<a href=\"$wwwroot/mod/evaluation/complete.php?id=$cmid&courseid=" . $myEvaluation["courseid"] .
                                '">';
                        $str .= "<td>$url<b style=\"color:$color;\">$Txt</b></a></td>";
                    }
                    if (empty($_SESSION["LoggedInAs"])) {
                        $urlC = "<a href=\"$wwwroot/course/view.php?id=" . $myEvaluation["courseid"] . "\">";
                    } else {
                        $urlC = "<a href=\"#\">";
                    }
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
        if ($isTeacher) {
            $possible_evaluations_txt = "/" . ($userResults ? $possible_evaluations_per_teacher : $possible_evaluations);
        }
        if ($myCourseReplies > 0 and $showCounter) {
            $str .= "<b style=\"color:darkgreen;\">" . get_string('completed_evaluations', "evaluation")
                    . (($isTeacher and $userResults) ? " für Sie" : "")
                    . ($userResults ? " in " . (safeCount($numCourses) > 1
                                    ? "Ihren " . safeCount($numCourses) . " teilnehmenden Kursen" : "Ihrem teilnehmenden Kurs") :
                            "")
                    . ": $myCourseReplies$possible_evaluations_txt</b>";
        }
        $str .= "<p> </p>";
    } else if (!defined('EVALUATION_OWNER') and $evaluation->timeclose < time()) // ".get_string('students_only', 'evaluation')."
    {
        $str .= "<p style=\"color:red;font-weight:bold;align:center;\">Keiner Ihrer Moodle Kurse nahm an dieser Evaluation teil!</p>";
    }
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
				WHERE evaluation= :feedid AND coalesce(c.fullname, '') != ''" . $cosPrivileged_filter . "
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
        $results = $DB->get_records_sql($query, array("feedid" => $evaluation->id), 0, 1000000);
    }

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
    $goBack = html_writer::tag('a', "Zurück",
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
               style="font-size:100%;color:white;background-color:teal;">
        <input type="hidden" name="sortBy" value="<?php echo $sortBy; ?>">
        <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
        <input type="hidden" name="id" value="<? echo $id; ?>">
        <input type="hidden" name="goBack" value="<? echo $return_to; ?>">
        <input type="submit" style="font-size:100%;color:white;background-color:black;" value="Abgaben">
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
            if ($cosPrivileged_filter and !in_array(trim(strip_tags($Studiengang)), $_SESSION['CoS_privileged'][$USER->username])) {
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
        $sumR = $sumC = $sum = $modus = $median = 0;
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
                $topline1 = '<b>Anzahl aller Kurse mit Abgaben:</b></td><td colspan="2" style="text-align:right;"><b>'
                        . evaluation_number_format($allResults) . "</b>";
                $output .= '<tr><td colspan="3">' . $topline1 . "</td></tr>\n";
            }
            $topline2 = '<b>Abgaben aus allen Kursen:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($completed_responses) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline2 . "</td></tr>\n";
            $topline2 = '<b>Ausgewertete Kurse mit Abgaben:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format(safeCount($results)) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline2 . "</td></tr>\n";
            $topline3 = '<b>Ausgewertete Kurse mit mindestens ' . $showMin
                    . ' Abgaben:</b></td><td colspan="2" style="text-align:right;"><b>' . evaluation_number_format($sumC) . "</b>";
            $output .= '<tr><td colspan="4">' . $topline3 . "</td></tr>\n";
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
    $goBack = html_writer::tag('a', "Zurück",
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

            if ($cosPrivileged_filter) {
                $topline1 = '<b>Anzahl aller Dozent_innen mit Abgaben:</b></td><td colspan="1" style="text-align:right;"><b>'
                        . evaluation_number_format($allResults) . "</b>";
                $output .= '<tr><td colspan="2">' . $topline1 . "</td></tr>\n";
            }
            $output .= '<tr><td colspan="2"><b>Anzahl der ausgewerteten Dozent_innen mit mindestens ' . $showMin
                    . ' Abgaben:</b></td><td colspan="1" style="text-align:right;"><b>' . evaluation_number_format($sumC) .
                    "</b></td></tr>\n";
            $output .= '<tr><td colspan="2"><b>Abgaben für diese ' . evaluation_number_format($sumC) . ' Dozent_innen:</b></td>'
                    . '<td colspan="1" style="text-align:right;"><b>' . evaluation_number_format($sumR) . "</b></td></tr>\n";
            $topline2 = '<b>Abgaben aus allen Kursen:</b></td><td colspan="2" style="text-align:right;"><b>'
                    . evaluation_number_format($completed_responses) . "</b>";
            $output .= '<tr><td colspan="1">' . $topline2 . "</td></tr>\n";
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
    $cnt = 0;
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
                $cnt++;
                $corrections .= str_pad($cnt, 4, " ", STR_PAD_LEFT) . " On top of Evaluation: " . safeCount($onTop) .
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
                $cnt++;
                //$course = $DB->get_record('course', array('id' => $courseid), '*');
                $corrections .= str_pad($cnt, 4, " ", STR_PAD_LEFT) . ' Course: <a href="/course/view.php?id=' .
                        $courseid . '" target="new">' . $courseid . "</a><br>\n";
            }
        }
    }

    if ($corrections) {
        return "List of courses participating in $evaluation->name with higher block positions and hidden block evaluation made visible again:<br>\n"
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
    if (!isset($_SESSION["teamteaching_courses"]) or !$_SESSION["teamteaching_courses"]) {
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
    return ($evaluation->timeclose < time());
}    // $evaluation->timeclose > 0 AND

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
        $passed = ((!empty($Studiengang) and in_array($Studiengang, $sg_filter)) ? true : false);
        // courses in courses_filter are to be excluded
        if ($passed and !empty($courses_filter) and in_array($courseid, $courses_filter)) {
            $passed = false;
        }
        //print "<br><br><hr>Evaluation: $evaluation->name - Studiengang: $Studiengang - ".var_export($sg_filter,true)." - showEval: ".($showEval ?"Yes" :"No") ."<br>\n";
    } else if (!empty($courses_filter) and is_array($courses_filter)
            and !in_array($courseid, $courses_filter)) {
        $passed = false;
    }
    if (false and is_siteadmin() and $passed) {
        echo "\n<br>pass_evaluation_filters: passed: \$courseid: $courseid - \$Studiengang: $Studiengang";
    }
    return $passed;
}

function ev_set_privileged_users($show = false) {
    global $CFG, $USER;
    $cfgFile = $CFG->dirroot . "/mod/evaluation/privileged_users.csv";
    if (is_readable($cfgFile)) {
        $cfgA = explode("\n", file_get_contents($cfgFile));
        $privileged_users = $_SESSION["privileged_global_users"]
                = $_SESSION["privileged_global_users_wm"] = $_SESSION["course_of_studies_wm"]
                = $_SESSION['CoS_department'] = $_SESSION['CoS_privileged'] = array();
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
                //if (is_array($_SESSION['filter_course_of_studies']) and !empty($_SESSION['filter_course_of_studies'])
                 //   and in_array($CoS, $_SESSION['filter_course_of_studies'])
                // ) {
                $_SESSION['CoS_privileged'][$username][$CoS] = $CoS;
                //print "<hr>\$users: " .var_export($users,true) .$user[0].": ". $_SESSION['CoS_privileged'][$user[0]][$user[1]] = $user[1]."<hr>";
            }
            $privileged_users[$username] = $username;
            if (!empty($CoS) and substr($CoS, 0, 1) != "#") {
                $_SESSION["course_of_studies_wm"][$CoS] = $is_WM;
            }

            /*if ( $is_WM AND empty($department)){
                $department = "WM";
            }*/

            if ( !empty($department)) {
                $_SESSION['CoS_department'][$CoS] = $department;
            }
        }
        // display list as html table
        if ( $show){
            $cfgData = file_get_contents($cfgFile);
            $pos = strpos($cfgData,"#Anmeldename");
            if ( $pos){
                $cfgData = substr($cfgData, $pos+1);
            }
            $rows = explode("\n", $cfgData);
            print "<style>tr:nth-child(odd) {background-color:lightgrey;}</style>";
            $out = "<b>Übersicht privilegierte Personen</b> (alle Evaluationen)<br><br>\n";
            $out .= '<table style="">'."\n";
            $first = true;
            foreach ($rows as $srow) {
                $CoS = "";
                $row = explode(",", $srow);
                if (isset($row[1])) {
                    $CoS = trim($row[1]);
                }
                if ( !$first AND !empty($CoS) AND isset($_SESSION['CoS_privileged'][$USER->username])){
                    if (!isset($_SESSION['CoS_privileged'][$USER->username][$CoS])) {
                        continue;
                    }
                }
                $out .= "<tr>\n";
                if ($first) {
                    foreach ($row as $col) {
                        $out .=  '<th style="font-weight:bold;">'.htmlspecialchars($col)."</th>\n";
                    }
                    $first = false;
                } else {
                    foreach ($row as $col) {
                        $out .=  "<td>".htmlspecialchars($col)."</td>\n";
                    }
                }
                $out .= "</tr>\n";
            }
            $out .=  "</table>";
            return $out;
        }
        return $privileged_users;
    } else if (!isset($_SESSION['ev_global_cfgfile'])) {
        $_SESSION['ev_global_cfgfile'] = false;
        print "<br><hr><b>Datei für Liste der privilegierten Personen ($cfgFile) kann nicht eingelesen werden!</b><br>
				Format: comma separated csv file. No text delimiters<br>
				Kommentare: vorangestelltes '#'.<br>
				Header: Anmeldename,Exakter Moodle Name des Studiengangs,Fachbereich,Weiterbildende Master,Vorname,Name,Funktion<br>
				- Wenn die Spalte Studiengang leer bleibt sind die Privilegien Global auf alle evaluierten Studiengänge.<br>
				- Wenn die Spalte Fachbereich (FB) leer bleibt gilt der Fachbereich als nicht gesetzt.
				- Wenn bei Personen mit globalen Privilegien die Spalte Weiterbildende Master auf \"Nein\" gesetzt ist, ist der Zugriff auf WM Studiengänge ausgeschlossen.<br>
				- Wenn die Spalte Weiterbildende Master auf \"Ja\" gesetzt ist, gilt der Studiengang als WM Studiengang.<br>
				- Wenn die Spalte Studiengang einen evaluierten Studiengang benennt, dann sind die Privilegien auf diesen Studiengang begrenzt.<hr><br>
		\n";
    }
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

    $showPriv = ((isset($_SESSION["EVALUATION_OWNER"]) and $_SESSION["EVALUATION_OWNER"]) or evaluation_debug(false));
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
                    !$showPriv)) {    //print "<br><hr>Evaluation: $evaluation->name - idnumber,-5: ".trim(substr( $course->idnumber, -5))
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
    if (!$no_user and $showEval and !$isPermitted and intval(date("Ymd", $timeopen)) <= intval(date("Ymd"))) {
        if ($is_open) {
            if (!isEvaluationCompleted($evaluation, $courseid, $user->id)) {
                $has_user_participated = evaluation_has_user_participated($evaluation, $user->id);
                $fullname = ($user->alternatename ? $user->alternatename : $user->firstname) . " " . $user->lastname;
                $days = remaining_evaluation_days($evaluation);
                $style = '<b style="color:darkgreen;">';

                if ($days < 7) {
                    $style = '<b style="color:red;">Die Abgabefrist endet ' . ($days > 0 ? "in " . $days . ' Tagen' : "heute") .
                            '</b><br><b style="color:teal;"> ';
                }
                $reminder = 'Guten Tag ' . $fullname
                        . '<br>Sie haben '
                        . ($has_user_participated
                                ? 'für diesen Kurs noch nicht'
                                : "bisher <b>für keinen Kurs</b>")
                        . '  an der Evaluation teilgenommen.
							<br>' . $style . 'Bitte machen ' . ($has_user_participated ? '' : "auch ") . 'Sie mit!</b><br>';
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
    $user = core_user::get_user($userid);
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
        echo "<br><b>Evaluation $evaluation->name has team teaching enabled. No autofill of teachers possible!</b><br>\n";
        return false;
    } else {
        if ($reset) {
            $DB->execute("UPDATE {evaluation_completed} SET teacherid=0 WHERE evaluation = $evaluation->id ");
        }

        $completed = $DB->get_records_sql("SELECT * FROM {evaluation_completed} 
											WHERE evaluation = $evaluation->id AND teacherid<1 ORDER BY id");
        if (!safeCount($completed) > 1) {
            echo "<br><b>Evaluation $evaluation->name (ID: $evaluation->id) teacherid autofill was already done!</b><br>\n";
            return false;
        }
    }
    echo "<h2>Autofilling teacherid</h2><br>\n";
    if ($reset) {
        print "Resetted all teacherid for $evaluation->name<br>\n";
    }
    //ini_set("output_buffering", 256);
    @ob_flush();
    @ob_end_flush();
    @flush();
    @ob_start();
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
    //ini_set("output_buffering", 256);
    @ob_flush();
    @ob_end_flush();
    @flush();
    @ob_start();
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
            $cnt = 0;
            foreach ($_SESSION["allteachers"][$complete->courseid] as $teacher) {
                $teacherid = $teacher['id'];
                if ($cnt == 0) {
                    $DB->execute("UPDATE {evaluation_completed} SET teacherid=$teacherid WHERE id=$complete->id");
                    $DB->execute("UPDATE {evaluation_value} SET teacherid=$teacherid WHERE completed = $complete->id");
                    $cnt++;
                    continue;
                }
                /*print "<br>\n<br>\n<br>\$cnt: $cnt - ID: $complete->id: CourseID: $complete->courseid - UserID: $complete->userid - Teachers: ".safeCount($teachers)
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
                $cnt++;
                print '<script>document.getElementById("counter").innerHTML = "'
                        .
                        "Count: $counter - ID: $complete->id: CourseID: $complete->courseid - UserID: $complete->userid - Teacherid: $teacherid" .
                        '";</script>';
                @ob_flush();
                @flush();
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
        if (!$cm) {
            list($course, $cm) = get_course_and_cm_from_cmid($id, 'evaluation');
        } else {
            $course = $DB->get_record('course', array('id' => $courseid), '*');
        }
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
function evaluation_autofill_item_studiengang($evaluation) {
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
    $cnt = 0;
    print '<br><span id="counter"></span><br>';
    foreach ($courseids as $courseid) {    // id	courseid	item	completed	value
        set_time_limit(30);
        $cnt++;
        $newval = new stdClass();
        foreach ($courseid as $key => $value) {
            $newval->{$key} = $value;
        }
        unset($newval->id);
        $newval->item = $itemid;
        $Studiengang = evaluation_get_course_of_studies($courseid->courseid);
        $position = array_search($Studiengang, $sg_arr);
        $newval->value = (is_numeric($position) ? $position : 0) + 1;
        $val_id = $DB->insert_record('evaluation_value', $newval);
        //print "<hr><br>$cnt/$hits: $val_id - $courseid->courseid - $Studiengang - position: $position - newval: ".$newval->value."<hr>";
        print '<script>document.getElementById("counter").innerHTML = "' .
                "$cnt/$hits: $val_id - $courseid->courseid - $Studiengang - Wert:"
                . trim($newval->value) . '";</script>';
        //print "<script>window.scrollTo(0,document.body.scrollHeight);</script>\n";
        @ob_flush();
        @ob_end_flush();
        @flush();
        @ob_start();
        //@flush();
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
            $cnt = 0;
            foreach ($evaluation_values as $evaluation_value) {
                $cnt++;
                print '<script>document.getElementById("counter").innerHTML = "' . $cnt . " of " . $values .
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
						  FROM {course_modules} cm, {modules} m, {course_sections} cs
						 WHERE cm.course = :courseid1 AND cm.module=m.id 
						 AND cm.course = cs.course 
						 AND (coalesce(cs.name) <>'' OR coalesce(cs.summary) <>'')
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
    $cnt = $empty_courses = 0;
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
        $cnt++;
    }
    print "</table>\n";
    print "<h2><b>$empty_courses empty courses found $filterText</h2><br><table></table></b>\n";
}


// end of ASH functions

// Include forms lib.
require_once($CFG->libdir . '/formslib.php');

define('EVALUATION_ANONYMOUS_YES', 1);
define('EVALUATION_ANONYMOUS_NO', 2);
define('EVALUATION_MIN_ANONYMOUS_COUNT_IN_GROUP', 2);
define('EVALUATION_DECIMAL', '.');
define('EVALUATION_THOUSAND', ',');
define('EVALUATION_RESETFORM_RESET', 'evaluation_reset_data_');
define('EVALUATION_RESETFORM_DROP', 'evaluation_drop_evaluation_');
define('EVALUATION_MAX_PIX_LENGTH', '400'); //max. Breite des grafischen Balkens in der Auswertung
define('EVALUATION_DEFAULT_PAGE_COUNT', 20);

// Event types.
define('EVALUATION_EVENT_TYPE_OPEN', 'open');
define('EVALUATION_EVENT_TYPE_CLOSE', 'close');

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 */
function evaluation_supports($feature) {
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}

/**
 * this will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $evaluation the object given by mod_evaluation_mod_form
 * @return int
 * @global object
 */
function evaluation_add_instance($evaluation) {
    global $DB;

    $evaluation->timemodified = time();
    $evaluation->id = '';

    if (empty($evaluation->site_after_submit)) {
        $evaluation->site_after_submit = '';
    }
    if (!isset($evaluation->min_results) or empty($evaluation->min_results)) {
        $evaluation->min_results = 3;
    }
    if (!isset($evaluation->privileged_users) or empty($evaluation->privileged_users)) {
        $evaluation->privileged_users = '';
    }
    if (!isset($evaluation->filter_course_of_studies) or empty($evaluation->filter_course_of_studies)) {
        $evaluation->filter_course_of_studies = '';
    }
    if (!isset($evaluation->filter_courses) or empty($evaluation->filter_courses)) {
        $evaluation->filter_courses = '';
    }

    //saving the evaluation in db
    $evaluationid = $DB->insert_record("evaluation", $evaluation);

    $evaluation->id = $evaluationid;

    evaluation_set_events($evaluation);

    if (!isset($evaluation->coursemodule)) {
        $cm = get_coursemodule_from_id('evaluation', $evaluation->id);
        $evaluation->coursemodule = $cm->id;
    }
    $context = context_module::instance($evaluation->coursemodule);

    if (!empty($evaluation->completionexpected)) {
        \core_completion\api::update_completion_date_event($evaluation->coursemodule, 'evaluation', $evaluation->id,
                $evaluation->completionexpected);
    }

    $editoroptions = evaluation_get_editor_options();

    // process the custom wysiwyg editor in page_after_submit
    if ($draftitemid = $evaluation->page_after_submit_editor['itemid']) {
        $evaluation->page_after_submit = file_save_draft_area_files($draftitemid, $context->id,
                'mod_evaluation', 'page_after_submit',
                0, $editoroptions,
                $evaluation->page_after_submit_editor['text']);

        $evaluation->page_after_submitformat = $evaluation->page_after_submit_editor['format'];
    }
    $DB->update_record('evaluation', $evaluation);

    return $evaluationid;
}

/**
 * this will update a given instance
 *
 * @param object $evaluation the object given by mod_evaluation_mod_form
 * @return boolean
 * @global object
 */
function evaluation_update_instance($evaluation) {
    global $DB;

    $evaluation->timemodified = time();
    $evaluation->id = $evaluation->instance;

    if (empty($evaluation->site_after_submit)) {
        $evaluation->site_after_submit = '';
    }
    if (!isset($evaluation->filter_course_of_studies) or empty($evaluation->filter_course_of_studies)) {
        $evaluation->filter_course_of_studies = '';
    }
    if (!isset($evaluation->privileged_users) or empty($evaluation->privileged_users)) {
        $evaluation->privileged_users = '';
    }

    //save the evaluation into the db
    $DB->update_record("evaluation", $evaluation);

    //create or update the new events
    evaluation_set_events($evaluation);
    $completionexpected = (!empty($evaluation->completionexpected)) ? $evaluation->completionexpected : null;
    \core_completion\api::update_completion_date_event($evaluation->coursemodule, 'evaluation', $evaluation->id,
            $completionexpected);

    $context = context_module::instance($evaluation->coursemodule);

    $editoroptions = evaluation_get_editor_options();

    // process the custom wysiwyg editor in page_after_submit
    if ($draftitemid = $evaluation->page_after_submit_editor['itemid']) {
        $evaluation->page_after_submit = file_save_draft_area_files($draftitemid, $context->id,
                'mod_evaluation', 'page_after_submit',
                0, $editoroptions,
                $evaluation->page_after_submit_editor['text']);

        $evaluation->page_after_submitformat = $evaluation->page_after_submit_editor['format'];
    }
    $DB->update_record('evaluation', $evaluation);
    return true;
}

/**
 * Serves the files included in evaluation items like label. Implements needed access control ;-)
 *
 * There are two situations in general where the files will be sent.
 * 1) filearea = item, 2) filearea = template
 *
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 * @package  mod_evaluation
 * @category files
 */
function evaluation_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $CFG, $DB;

    if ($filearea === 'item' or $filearea === 'template') {
        $itemid = (int) array_shift($args);
        //get the item what includes the file
        if (!$item = $DB->get_record('evaluation_item', array('id' => $itemid))) {
            return false;
        }
        $evaluationid = $item->evaluation;
        $templateid = $item->template;
    }

    if ($filearea === 'page_after_submit' or $filearea === 'item') {
        if (!$evaluation = $DB->get_record("evaluation", array("id" => $cm->instance))) {
            return false;
        }

        $evaluationid = $evaluation->id;

        //if the filearea is "item" so we check the permissions like view/complete the evaluation
        $canload = false;
        //first check whether the user has the complete capability
        if (has_capability('mod/evaluation:complete', $context)) {
            $canload = true;
        }

        //now we check whether the user has the view capability
        if (has_capability('mod/evaluation:view', $context)) {
            $canload = true;
        }

        //if the evaluation is on frontpage and anonymous and the fullanonymous is allowed
        //so the file can be loaded too.
        if (isset($CFG->evaluation_allowfullanonymous)
                and $CFG->evaluation_allowfullanonymous
                and $course->id == SITEID
                and $evaluation->anonymous == EVALUATION_ANONYMOUS_YES) {
            $canload = true;
        }

        if (!$canload) {
            return false;
        }
    } else if ($filearea === 'template') { //now we check files in templates
        if (!$template = $DB->get_record('evaluation_template', array('id' => $templateid))) {
            return false;
        }

        //if the file is not public so the capability edititems has to be there
        if (!$template->ispublic) {
            if (!has_capability('mod/evaluation:edititems', $context)) {
                return false;
            }
        } else { //on public templates, at least the user has to be logged in
            if (!isloggedin()) {
                return false;
            }
        }
    } else {
        return false;
    }

    if ($context->contextlevel == CONTEXT_MODULE) {
        if ($filearea !== 'item' and $filearea !== 'page_after_submit') {
            return false;
        }
    }

    if ($context->contextlevel == CONTEXT_COURSE || $context->contextlevel == CONTEXT_SYSTEM) {
        if ($filearea !== 'template') {
            return false;
        }
    }

    $relativepath = implode('/', $args);
    if ($filearea === 'page_after_submit') {
        $fullpath = "/{$context->id}/mod_evaluation/$filearea/$relativepath";
    } else {
        $fullpath = "/{$context->id}/mod_evaluation/$filearea/{$item->id}/$relativepath";
    }

    $fs = get_file_storage();

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!

    return false;
}

/**
 * this will delete a given instance.
 * all referenced data also will be deleted
 *
 * @param int $id the instanceid of evaluation
 * @return boolean
 * @global object
 */
function evaluation_delete_instance($id) {
    global $DB;

    //get all referenced items
    $evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $id));

    //deleting all referenced items and values
    if (is_array($evaluationitems)) {
        foreach ($evaluationitems as $evaluationitem) {
            $DB->delete_records("evaluation_value", array("item" => $evaluationitem->id));
            $DB->delete_records("evaluation_valuetmp", array("item" => $evaluationitem->id));
        }
        if ($delitems = $DB->get_records("evaluation_item", array("evaluation" => $id))) {
            foreach ($delitems as $delitem) {
                evaluation_delete_item($delitem->id, false);
            }
        }
    }

    //deleting the completeds
    $DB->delete_records("evaluation_completed", array("evaluation" => $id));

    //deleting the unfinished completeds
    $DB->delete_records("evaluation_completedtmp", array("evaluation" => $id));

    //deleting old events
    $DB->delete_records('event', array('modulename' => 'evaluation', 'instance' => $id));
    return $DB->delete_records("evaluation", array("id" => $id));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $evaluation
 * @return stdClass
 */
function evaluation_user_outline($course, $user, $mod, $evaluation) {
    global $DB;
    $outline = (object) ['info' => '', 'time' => 0];
    if ($evaluation->anonymous != EVALUATION_ANONYMOUS_NO) {
        // Do not disclose any user info if evaluation is anonymous.
        return $outline;
    }
    $params = array('userid' => $user->id, 'evaluation' => $evaluation->id,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO);
    $status = null;
    $context = context_module::instance($mod->id);
    if ($completed = $DB->get_record('evaluation_completed', $params)) {
        // User has completed evaluation.
        $outline->info = get_string('completed', 'evaluation');
        $outline->time = $completed->timemodified;
    } else if ($completedtmp = $DB->get_record('evaluation_completedtmp', $params)) {
        // User has started but not completed evaluation.
        $outline->info = get_string('started', 'evaluation');
        $outline->time = $completedtmp->timemodified;
    } else if (has_capability('mod/evaluation:complete', $context, $user)) {
        // User has not started evaluation but has capability to do so.
        $outline->info = get_string('not_started', 'evaluation');
    }

    return $outline;
}

/**
 * Returns all users who has completed a specified evaluation since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @param array $activities Passed by reference
 * @param int $index Passed by reference
 * @param int $timemodified Timestamp
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return void
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 */
function evaluation_get_recent_mod_activity(&$activities, &$index,
        $timemodified, $courseid,
        $cmid, $userid = "", $groupid = "") {

    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id' => $courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $sqlargs = array();

    $userfields = user_picture::fields('u', null, 'useridagain');
    $sql = " SELECT fk . * , fc . * , $userfields
                FROM {evaluation_completed} fc
                    JOIN {evaluation} fk ON fk.id = fc.evaluation
                    JOIN {user} u ON u.id = fc.userid ";

    if ($groupid) {
        $sql .= " JOIN {groups_members} gm ON  gm.userid=u.id ";
    }

    $sql .= " WHERE fc.timemodified > ?
                AND fk.id = ?
                AND fc.anonymous_response = ?";
    $sqlargs[] = $timemodified;
    $sqlargs[] = $cm->instance;
    $sqlargs[] = EVALUATION_ANONYMOUS_NO;

    if ($userid) {
        $sql .= " AND u.id = ? ";
        $sqlargs[] = $userid;
    }

    if ($groupid) {
        $sql .= " AND gm.groupid = ? ";
        $sqlargs[] = $groupid;
    }

    if (!$evaluationitems = $DB->get_records_sql($sql, $sqlargs)) {
        return;
    }

    $cm_context = context_module::instance($cm->id);

    if (!has_capability('mod/evaluation:view', $cm_context)) {
        return;
    }

    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames = has_capability('moodle/site:viewfullnames', $cm_context);
    $groupmode = groups_get_activity_groupmode($cm, $course);

    $aname = format_string($cm->name, true);
    foreach ($evaluationitems as $evaluationitem) {
        if ($evaluationitem->userid != $USER->id) {

            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
                $usersgroups = groups_get_all_groups($course->id,
                        $evaluationitem->userid,
                        $cm->groupingid);
                if (!is_array($usersgroups)) {
                    continue;
                }
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->get_groups($cm->groupingid));
                if (empty($intersect)) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();

        $tmpactivity->type = 'evaluation';
        $tmpactivity->cmid = $cm->id;
        $tmpactivity->name = $aname;
        $tmpactivity->sectionnum = $cm->sectionnum;
        $tmpactivity->timestamp = $evaluationitem->timemodified;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->evaluationid = $evaluationitem->id;
        $tmpactivity->content->evaluationuserid = $evaluationitem->userid;

        $tmpactivity->user = user_picture::unalias($evaluationitem, null, 'useridagain');
        $tmpactivity->user->fullname = fullname($evaluationitem, $viewfullnames);

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Prints all users who has completed a specified evaluation since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @param object $activity
 * @param int $courseid
 * @param string $detail
 * @param array $modnames
 * @return void Output is echo'd
 * @global object
 */
function evaluation_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user, array('courseid' => $courseid));
    echo "</td><td>";

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo $OUTPUT->image_icon('icon', $modname, $activity->type);
        echo "<a href=\"$CFG->wwwroot/mod/evaluation/view.php?id={$activity->cmid}\">{$activity->name}</a>";
        echo '</div>';
    }

    echo '<div class="title">';
    echo '</div>';

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
            . "{$activity->user->fullname}</a> - " . userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";

    return;
}

/**
 * Obtains the automatic completion state for this evaluation based on the condition
 * in evaluation settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function evaluation_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get evaluation details
    $evaluation = $DB->get_record('evaluation', array('id' => $cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false
    if ($evaluation->completionsubmit) {
        $params = array('userid' => $userid, 'evaluation' => $evaluation->id);
        return $DB->record_exists('evaluation_completed', $params);
    } else {
        // Completion option is not enabled so just return $type
        return $type;
    }
}

/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course
 * @param stdClass $user
 * @param cm_info|stdClass $mod
 * @param stdClass $evaluation
 */
function evaluation_user_complete($course, $user, $mod, $evaluation) {
    global $DB;
    if ($evaluation->anonymous != EVALUATION_ANONYMOUS_NO) {
        // Do not disclose any user info if evaluation is anonymous.
        return;
    }
    $params = array('userid' => $user->id, 'evaluation' => $evaluation->id,
            'anonymous_response' => EVALUATION_ANONYMOUS_NO);
    $url = $status = null;
    $context = context_module::instance($mod->id);
    if ($completed = $DB->get_record('evaluation_completed', $params)) {
        // User has completed evaluation.
        if (has_capability('mod/evaluation:viewreports', $context)) {
            $url = new moodle_url('/mod/evaluation/show_entries.php',
                    ['id' => $mod->id, 'userid' => $user->id,
                            'showcompleted' => $completed->id]);
        }
        $status = get_string('completedon', 'evaluation', userdate($completed->timemodified));
    } else if ($completedtmp = $DB->get_record('evaluation_completedtmp', $params)) {
        // User has started but not completed evaluation.
        $status = get_string('startedon', 'evaluation', userdate($completedtmp->timemodified));
    } else if (has_capability('mod/evaluation:complete', $context, $user)) {
        // User has not started evaluation but has capability to do so.
        $status = get_string('not_started', 'evaluation');
    }

    if ($url && $status) {
        echo html_writer::link($url, $status);
    } else if ($status) {
        echo html_writer::div($status);
    }
}

/**
 * @return bool true
 */
function evaluation_cron() {
    return true;
}

/**
 * @deprecated since Moodle 3.8
 */
function evaluation_scale_used() {
    throw new coding_exception('evaluation_scale_used() can not be used anymore. Plugins can implement ' .
            '<modname>_scale_used_anywhere, all implementations of <modname>_scale_used are now ignored');
}

/**
 * Checks if scale is being used by any instance of evaluation
 *
 * This is used to find out if scale used anywhere
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any assignment
 */
function evaluation_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * List the actions that correspond to a view of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = 'r' and edulevel = LEVEL_PARTICIPATING will
 *       be considered as view action.
 *
 * @return array
 */
function evaluation_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List the actions that correspond to a post of this module.
 * This is used by the participation report.
 *
 * Note: This is not used by new logging system. Event with
 *       crud = ('c' || 'u' || 'd') and edulevel = LEVEL_PARTICIPATING
 *       will be considered as post action.
 *
 * @return array
 */
function evaluation_get_post_actions() {
    return array('submit');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all responses from the specified evaluation
 * and clean up any related data.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 * @uses EVALUATION_RESETFORM_RESET
 * @uses EVALUATION_RESETFORM_DROP
 * @global object
 * @global object
 */
function evaluation_reset_userdata($data) {
    global $CFG, $DB;

    $resetevaluations = array();
    $dropevaluations = array();
    $status = array();
    $componentstr = get_string('modulenameplural', 'evaluation');

    //get the relevant entries from $data
    foreach ($data as $key => $value) {
        switch (true) {
            case substr($key, 0, strlen(EVALUATION_RESETFORM_RESET)) == EVALUATION_RESETFORM_RESET:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $resetevaluations[] = intval($templist[3]);
                    }
                }
                break;
            case substr($key, 0, strlen(EVALUATION_RESETFORM_DROP)) == EVALUATION_RESETFORM_DROP:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $dropevaluations[] = intval($templist[3]);
                    }
                }
                break;
        }
    }

    //reset the selected evaluations
    foreach ($resetevaluations as $id) {
        $evaluation = $DB->get_record('evaluation', array('id' => $id));
        evaluation_delete_all_completeds($evaluation);
        $status[] = array('component' => $componentstr . ':' . $evaluation->name,
                'item' => get_string('resetting_data', 'evaluation'),
                'error' => false);
    }

    // Updating dates - shift may be negative too.
    if ($data->timeshift) {
        // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
        // See MDL-9367.
        $shifterror = !shift_course_mod_dates('evaluation', array('timeopen', 'timeclose'), $data->timeshift, $data->courseid);
        $status[] = array('component' => $componentstr, 'item' => get_string('datechanged'), 'error' => $shifterror);
    }

    return $status;
}

/**
 * Called by course/reset.php
 *
 * @param object $mform form passed by reference
 * @uses EVALUATION_RESETFORM_RESET
 * @global object
 */
function evaluation_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'evaluationheader', get_string('modulenameplural', 'evaluation'));

    if (!$evaluations = $DB->get_records('evaluation', array('course' => $COURSE->id), 'name')) {
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetting_data', 'evaluation'));
    foreach ($evaluations as $evaluation) {
        $mform->addElement('checkbox', EVALUATION_RESETFORM_RESET . $evaluation->id, $evaluation->name);
    }
}

/**
 * Course reset form defaults.
 *
 * @param object $course
 * @uses EVALUATION_RESETFORM_RESET
 * @global object
 */
function evaluation_reset_course_form_defaults($course) {
    global $DB;

    $return = array();
    if (!$evaluations = $DB->get_records('evaluation', array('course' => $course->id), 'name')) {
        return;
    }
    foreach ($evaluations as $evaluation) {
        $return[EVALUATION_RESETFORM_RESET . $evaluation->id] = true;
    }
    return $return;
}

/**
 * Called by course/reset.php and shows the formdata by coursereset.
 * it prints checkboxes for each evaluation available at the given course
 * there are two checkboxes:
 * 1) delete userdata and keep the evaluation
 * 2) delete userdata and drop the evaluation
 *
 * @param object $course
 * @return void
 * @uses EVALUATION_RESETFORM_DROP
 * @global object
 * @uses EVALUATION_RESETFORM_RESET
 */
function evaluation_reset_course_form($course) {
    global $DB, $OUTPUT;

    echo get_string('resetting_evaluations', 'evaluation');
    echo ':<br />';
    if (!$evaluations = $DB->get_records('evaluation', array('course' => $course->id), 'name')) {
        return;
    }

    foreach ($evaluations as $evaluation) {
        echo '<p>';
        echo get_string('name', 'evaluation') . ': ' . $evaluation->name . '<br />';
        echo html_writer::checkbox(EVALUATION_RESETFORM_RESET . $evaluation->id,
                1, true,
                get_string('resetting_data', 'evaluation'));
        echo '<br />';
        echo html_writer::checkbox(EVALUATION_RESETFORM_DROP . $evaluation->id,
                1, false,
                get_string('drop_evaluation', 'evaluation'));
        echo '</p>';
    }
}

/**
 * This gets an array with default options for the editor
 *
 * @return array the options
 */
function evaluation_get_editor_options() {
    return array('maxfiles' => EDITOR_UNLIMITED_FILES,
            'trusttext' => true);
}

/**
 * this function is called by {@link evaluation_delete_userdata()}
 * it drops the evaluation-instance from the course_module table
 *
 * @param int $id the id from the coursemodule
 * @return boolean
 * @global object
 */
function evaluation_delete_course_module($id) {
    global $DB;

    if (!$cm = $DB->get_record('course_modules', array('id' => $id))) {
        return true;
    }
    return $DB->delete_records('course_modules', array('id' => $cm->id));
}

////////////////////////////////////////////////
//functions to handle capabilities
////////////////////////////////////////////////

/**
 * @deprecated since 3.1
 */
function evaluation_get_context() {
    throw new coding_exception('evaluation_get_context() can not be used anymore.');
}

/**
 *  returns true if the current role is faked by switching role feature
 *
 * @return boolean
 * @global object
 */
function evaluation_check_is_switchrole() {
    global $USER;
    if (isset($USER->switchrole) and
            is_array($USER->switchrole) and
            safeCount($USER->switchrole) > 0) {

        return true;
    }
    return false;
}

/**
 * count users which have not completed the evaluation
 *
 * @param cm_info $cm Course-module object
 * @param int $group single groupid
 * @param string $sort
 * @param int $startpage
 * @param int $pagecount
 * @param bool $includestatus to return if the user started or not the evaluation among the complete user record
 * @return array array of user ids or user objects when $includestatus set to true
 * @uses CONTEXT_MODULE
 * @global object
 */
function evaluation_get_incomplete_users(cm_info $cm,
        $group = false,
        $sort = '',
        $startpage = false,
        $pagecount = false,
        $includestatus = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    //first get all user who can complete this evaluation
    $cap = 'mod/evaluation:complete';
    $allnames = get_all_user_name_fields(true, 'u');
    $fields = 'u.id, ' . $allnames . ', u.picture, u.email, u.imagealt';
    if (!$allusers = get_users_by_capability($context,
            $cap,
            $fields,
            $sort,
            '',
            '',
            $group,
            '',
            true)) {
        return false;
    }
    // Filter users that are not in the correct group/grouping.
    $info = new \core_availability\info_module($cm);
    $allusersrecords = $info->filter_user_list($allusers);

    $allusers = array_keys($allusersrecords);

    //now get all completeds
    $params = array('evaluation' => $cm->instance);
    if ($completedusers = $DB->get_records_menu('evaluation_completed', $params, '', 'id, userid')) {
        // Now strike all completedusers from allusers.
        $allusers = array_diff($allusers, $completedusers);
    }

    //for paging I use array_slice()
    if ($startpage !== false and $pagecount !== false) {
        $allusers = array_slice($allusers, $startpage, $pagecount);
    }

    // Check if we should return the full users objects.
    if ($includestatus) {
        $userrecords = [];
        $startedusers = $DB->get_records_menu('evaluation_completedtmp', ['evaluation' => $cm->instance], '', 'id, userid');
        $startedusers = array_flip($startedusers);
        foreach ($allusers as $userid) {
            $allusersrecords[$userid]->evaluationstarted = isset($startedusers[$userid]);
            $userrecords[] = $allusersrecords[$userid];
        }
        return $userrecords;
    } else {    // Return just user ids.
        return $allusers;
    }
}

/**
 * count users which have not completed the evaluation
 *
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 * @global object
 */
function evaluation_count_incomplete_users($cm, $group = false) {
    if ($allusers = evaluation_get_incomplete_users($cm, $group)) {
        return safeCount($allusers);
    }
    return 0;
}

/**
 * count users which have completed a evaluation
 *
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 * @uses EVALUATION_ANONYMOUS_NO
 * @global object
 */
function evaluation_count_complete_users($cm, $group = false) {
    global $DB;

    $params = array(EVALUATION_ANONYMOUS_NO, $cm->instance);

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = ? AND g.userid = c.userid';
        $params[] = $group;
    }

    $sql = 'SELECT COUNT(u.id) FROM {user} u, {evaluation_completed} c' . $fromgroup . '
              WHERE anonymous_response = ? AND u.id = c.userid AND c.evaluation = ?
              ' . $wheregroup;

    return $DB->count_records_sql($sql, $params);

}

/**
 * get users which have completed a evaluation
 *
 * @param object $cm
 * @param int $group single groupid
 * @param string $where a sql where condition (must end with " AND ")
 * @param array parameters used in $where
 * @param string $sort a table field
 * @param int $startpage
 * @param int $pagecount
 * @return object the userrecords
 * @global object
 * @uses CONTEXT_MODULE
 * @uses EVALUATION_ANONYMOUS_NO
 */
function evaluation_get_complete_users($cm,
        $group = false,
        $where = '',
        array $params = null,
        $sort = '',
        $startpage = false,
        $pagecount = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    $params = (array) $params;

    $params['anon'] = EVALUATION_ANONYMOUS_NO;
    $params['instance'] = $cm->instance;

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = :group AND g.userid = c.userid';
        $params['group'] = $group;
    }

    if ($sort) {
        $sortsql = ' ORDER BY ' . $sort;
    } else {
        $sortsql = '';
    }

    $ufields = user_picture::fields('u');
    $sql = 'SELECT DISTINCT ' . $ufields . ', c.timemodified as completed_timemodified
            FROM {user} u, {evaluation_completed} c ' . $fromgroup . '
            WHERE ' . $where . ' anonymous_response = :anon
                AND u.id = c.userid
                AND c.evaluation = :instance
              ' . $wheregroup . $sortsql;

    if ($startpage === false or $pagecount === false) {
        $startpage = false;
        $pagecount = false;
    }
    return $DB->get_records_sql($sql, $params, $startpage, $pagecount);
}

/**
 * get users which have the viewreports-capability
 *
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 * @uses CONTEXT_MODULE
 */
function evaluation_get_viewreports_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
            'mod/evaluation:viewreports',
            '',
            'lastname',
            '',
            '',
            $groups,
            '',
            false);
}

/**
 * get users which have the receivemail-capability
 *
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 * @uses CONTEXT_MODULE
 */
function evaluation_get_receivemail_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
            'mod/evaluation:receivemail',
            '',
            'lastname',
            '',
            '',
            $groups,
            '',
            false);
}

////////////////////////////////////////////////
//functions to handle the templates
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * creates a new template-record.
 *
 * @param int $courseid
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return int the new templateid
 * @global object
 */
function evaluation_create_template($courseid, $name, $ispublic = 0) {
    global $DB;

    $templ = new stdClass();
    $templ->course = ($ispublic ? 0 : $courseid);
    $templ->name = $name;
    $templ->ispublic = $ispublic;

    $templid = $DB->insert_record('evaluation_template', $templ);
    return $DB->get_record('evaluation_template', array('id' => $templid));
}

/**
 * creates new template items.
 * all items will be copied and the attribute evaluation will be set to 0
 * and the attribute template will be set to the new templateid
 *
 * @param object $evaluation
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return boolean
 * @uses CONTEXT_MODULE
 * @uses CONTEXT_COURSE
 * @global object
 */
function evaluation_save_as_template($evaluation, $name, $ispublic = 0) {
    global $DB;
    $fs = get_file_storage();

    if (!$evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id))) {
        return false;
    }

    if (!$newtempl = evaluation_create_template($evaluation->course, $name, $ispublic)) {
        return false;
    }

    //files in the template_item are in the context of the current course or
    //if the template is public the files are in the system context
    //files in the evaluation_item are in the evaluation_context of the evaluation
    if ($ispublic) {
        $s_context = context_system::instance();
    } else {
        $s_context = context_course::instance($newtempl->course);
    }
    $cm = get_coursemodule_from_instance('evaluation', $evaluation->id);
    $f_context = context_module::instance($cm->id);

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($evaluationitems as $item) {

        $t_item = clone($item);

        unset($t_item->id);
        $t_item->evaluation = 0;
        $t_item->template = $newtempl->id;
        $t_item->id = $DB->insert_record('evaluation_item', $t_item);
        //copy all included files to the evaluation_template filearea
        $itemfiles = $fs->get_area_files($f_context->id,
                'mod_evaluation',
                'item',
                $item->id,
                "id",
                false);
        if ($itemfiles) {
            foreach ($itemfiles as $ifile) {
                $file_record = new stdClass();
                $file_record->contextid = $s_context->id;
                $file_record->component = 'mod_evaluation';
                $file_record->filearea = 'template';
                $file_record->itemid = $t_item->id;
                $fs->create_file_from_storedfile($file_record, $ifile);
            }
        }

        $itembackup[$item->id] = $t_item->id;
        if ($t_item->dependitem) {
            $dependitemsmap[$t_item->id] = $t_item->dependitem;
        }

    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('evaluation_item', array('id' => $key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('evaluation_item', $newitem);
    }

    return true;
}

/**
 * deletes all evaluation_items related to the given template id
 *
 * @param object $template the template
 * @return void
 * @global object
 * @uses CONTEXT_COURSE
 */
function evaluation_delete_template($template) {
    global $DB;

    //deleting the files from the item is done by evaluation_delete_item
    if ($t_items = $DB->get_records("evaluation_item", array("template" => $template->id))) {
        foreach ($t_items as $t_item) {
            evaluation_delete_item($t_item->id, false, $template);
        }
    }
    $DB->delete_records("evaluation_template", array("id" => $template->id));
}

/**
 * creates new evaluation_item-records from template.
 * if $deleteold is set true so the existing items of the given evaluation will be deleted
 * if $deleteold is set false so the new items will be appanded to the old items
 *
 * @param object $evaluation
 * @param int $templateid
 * @param boolean $deleteold
 * @global object
 * @uses CONTEXT_COURSE
 * @uses CONTEXT_MODULE
 */
function evaluation_items_from_template($evaluation, $templateid, $deleteold = false) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/completionlib.php');

    $fs = get_file_storage();

    if (!$template = $DB->get_record('evaluation_template', array('id' => $templateid))) {
        return false;
    }
    //get all templateitems
    if (!$templitems = $DB->get_records('evaluation_item', array('template' => $templateid))) {
        return false;
    }

    //files in the template_item are in the context of the current course
    //files in the evaluation_item are in the evaluation_context of the evaluation
    if ($template->ispublic) {
        $s_context = context_system::instance();
    } else {
        $s_context = context_course::instance($evaluation->course);
    }
    $course = $DB->get_record('course', array('id' => $evaluation->course));
    $cm = get_coursemodule_from_instance('evaluation', $evaluation->id);
    $f_context = context_module::instance($cm->id);

    //if deleteold then delete all old items before
    //get all items
    if ($deleteold) {
        if ($evaluationitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluation->id))) {
            //delete all items of this evaluation
            foreach ($evaluationitems as $item) {
                evaluation_delete_item($item->id, false);
            }

            $params = array('evaluation' => $evaluation->id);
            if ($completeds = $DB->get_records('evaluation_completed', $params)) {
                $completion = new completion_info($course);
                foreach ($completeds as $completed) {
                    $DB->delete_records('evaluation_completed', array('id' => $completed->id));
                    // Update completion state
                    if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC &&
                            $evaluation->completionsubmit) {
                        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
                    }
                }
            }
            $DB->delete_records('evaluation_completedtmp', array('evaluation' => $evaluation->id));
        }
        $positionoffset = 0;
    } else {
        //if the old items are kept the new items will be appended
        //therefor the new position has an offset
        $positionoffset = $DB->count_records('evaluation_item', array('evaluation' => $evaluation->id));
    }

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($templitems as $t_item) {
        $item = clone($t_item);
        unset($item->id);
        $item->evaluation = $evaluation->id;
        $item->template = 0;
        $item->position = $item->position + $positionoffset;

        $item->id = $DB->insert_record('evaluation_item', $item);

        //moving the files to the new item
        $templatefiles = $fs->get_area_files($s_context->id,
                'mod_evaluation',
                'template',
                $t_item->id,
                "id",
                false);
        if ($templatefiles) {
            foreach ($templatefiles as $tfile) {
                $file_record = new stdClass();
                $file_record->contextid = $f_context->id;
                $file_record->component = 'mod_evaluation';
                $file_record->filearea = 'item';
                $file_record->itemid = $item->id;
                $fs->create_file_from_storedfile($file_record, $tfile);
            }
        }

        $itembackup[$t_item->id] = $item->id;
        if ($item->dependitem) {
            $dependitemsmap[$item->id] = $item->dependitem;
        }
    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('evaluation_item', array('id' => $key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('evaluation_item', $newitem);
    }
}

/**
 * get the list of available templates.
 * if the $onlyown param is set true so only templates from own course will be served
 * this is important for droping templates
 *
 * @param object $course
 * @param string $onlyownorpublic
 * @return array the template recordsets
 * @global object
 */
function evaluation_get_template_list($course, $onlyownorpublic = '') {
    global $DB, $CFG;

    switch ($onlyownorpublic) {
        case '':
            $templates = $DB->get_records_select('evaluation_template',
                    'course = ? OR ispublic = 1',
                    array($course->id),
                    'name');
            break;
        case 'own':
            $templates = $DB->get_records('evaluation_template',
                    array('course' => $course->id),
                    'name');
            break;
        case 'public':
            $templates = $DB->get_records('evaluation_template', array('ispublic' => 1), 'name');
            break;
    }
    return $templates;
}

////////////////////////////////////////////////
//Handling der Items
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * load the lib.php from item-plugin-dir and returns the instance of the itemclass
 *
 * @param string $typ
 * @return evaluation_item_base the instance of itemclass
 */
function evaluation_get_item_class($typ) {
    global $CFG;

    //get the class of item-typ
    $itemclass = 'evaluation_item_' . $typ;
    //get the instance of item-class
    if (!class_exists($itemclass)) {
        require_once($CFG->dirroot . '/mod/evaluation/item/' . $typ . '/lib.php');
    }
    return new $itemclass();
}

/**
 * load the available item plugins from given subdirectory of $CFG->dirroot
 * the default is "mod/evaluation/item"
 *
 * @param string $dir the subdir
 * @return array pluginnames as string
 * @global object
 */
function evaluation_load_evaluation_items($dir = 'mod/evaluation/item') {
    global $CFG;
    $names = get_list_of_plugins($dir);
    $ret_names = array();

    foreach ($names as $name) {
        require_once($CFG->dirroot . '/' . $dir . '/' . $name . '/lib.php');
        if (class_exists('evaluation_item_' . $name)) {
            $ret_names[] = $name;
        }
    }
    return $ret_names;
}

/**
 * load the available item plugins to use as dropdown-options
 *
 * @return array pluginnames as string
 * @global object
 */
function evaluation_load_evaluation_items_options() {
    global $CFG;

    $evaluation_options = array("pagebreak" => get_string('add_pagebreak', 'evaluation'));

    if (!$evaluation_names = evaluation_load_evaluation_items('mod/evaluation/item')) {
        return array();
    }

    foreach ($evaluation_names as $fn) {
        $evaluation_options[$fn] = get_string($fn, 'evaluation');
    }
    asort($evaluation_options);
    return $evaluation_options;
}

/**
 * load the available items for the depend item dropdown list shown in the edit_item form
 *
 * @param object $evaluation
 * @param object $item the item of the edit_item form
 * @return array all items except the item $item, labels and pagebreaks
 * @global object
 */
function evaluation_get_depend_candidates_for_item($evaluation, $item) {
    global $DB;
    //all items for dependitem
    $where = "evaluation = ? AND typ != 'pagebreak' AND hasvalue = 1";
    $params = array($evaluation->id);
    if (isset($item->id) and $item->id) {
        $where .= ' AND id != ?';
        $params[] = $item->id;
    }
    $dependitems = array(0 => get_string('choose'));
    $evaluationitems = $DB->get_records_select_menu('evaluation_item',
            $where,
            $params,
            'position',
            'id, label');

    if (!$evaluationitems) {
        return $dependitems;
    }
    //adding the choose-option
    foreach ($evaluationitems as $key => $val) {
        if (trim(strval($val)) !== '') {
            $dependitems[$key] = format_string($val);
        }
    }
    return $dependitems;
}

/**
 * @deprecated since 3.1
 */
function evaluation_create_item() {
    throw new coding_exception('evaluation_create_item() can not be used anymore.');
}

/**
 * save the changes of a given item.
 *
 * @param object $item
 * @return boolean
 * @global object
 */
function evaluation_update_item($item) {
    global $DB;
    return $DB->update_record("evaluation_item", $item);
}

/**
 * deletes an item and also deletes all related values
 *
 * @param int $itemid
 * @param boolean $renumber should the kept items renumbered Yes/No
 * @param object $template if the template is given so the items are bound to it
 * @return void
 * @global object
 * @uses CONTEXT_MODULE
 */
function evaluation_delete_item($itemid, $renumber = true, $template = false) {
    global $DB;

    $item = $DB->get_record('evaluation_item', array('id' => $itemid));

    //deleting the files from the item
    $fs = get_file_storage();

    if ($template) {
        if ($template->ispublic) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($template->course);
        }
        $templatefiles = $fs->get_area_files($context->id,
                'mod_evaluation',
                'template',
                $item->id,
                "id",
                false);

        if ($templatefiles) {
            $fs->delete_area_files($context->id, 'mod_evaluation', 'template', $item->id);
        }
    } else {
        if (!$cm = get_coursemodule_from_instance('evaluation', $item->evaluation)) {
            return false;
        }
        $context = context_module::instance($cm->id);

        $itemfiles = $fs->get_area_files($context->id,
                'mod_evaluation',
                'item',
                $item->id,
                "id", false);

        if ($itemfiles) {
            $fs->delete_area_files($context->id, 'mod_evaluation', 'item', $item->id);
        }
    }

    $DB->delete_records("evaluation_value", array("item" => $itemid));
    $DB->delete_records("evaluation_valuetmp", array("item" => $itemid));

    //remove all depends
    $DB->set_field('evaluation_item', 'dependvalue', '', array('dependitem' => $itemid));
    $DB->set_field('evaluation_item', 'dependitem', 0, array('dependitem' => $itemid));

    $DB->delete_records("evaluation_item", array("id" => $itemid));
    if ($renumber) {
        evaluation_renumber_items($item->evaluation);
    }
}

/**
 * deletes all items of the given evaluationid
 *
 * @param int $evaluationid
 * @return void
 * @global object
 */
function evaluation_delete_all_items($evaluationid) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    if (!$evaluation = $DB->get_record('evaluation', array('id' => $evaluationid))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('evaluation', $evaluation->id)) {
        return false;
    }

    if (!$course = $DB->get_record('course', array('id' => $evaluation->course))) {
        return false;
    }

    if (!$items = $DB->get_records('evaluation_item', array('evaluation' => $evaluationid))) {
        return;
    }
    foreach ($items as $item) {
        evaluation_delete_item($item->id, false);
    }
    if ($completeds = $DB->get_records('evaluation_completed', array('evaluation' => $evaluation->id))) {
        $completion = new completion_info($course);
        foreach ($completeds as $completed) {
            $DB->delete_records('evaluation_completed', array('id' => $completed->id));
            // Update completion state
            if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC &&
                    $evaluation->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
            }
        }
    }

    $DB->delete_records('evaluation_completedtmp', array('evaluation' => $evaluationid));

}

/**
 * this function toggled the item-attribute required (yes/no)
 *
 * @param object $item
 * @return boolean
 * @global object
 */
function evaluation_switch_item_required($item) {
    global $DB, $CFG;

    $itemobj = evaluation_get_item_class($item->typ);

    if ($itemobj->can_switch_require()) {
        $new_require_val = (int) !(bool) $item->required;
        $params = array('id' => $item->id);
        $DB->set_field('evaluation_item', 'required', $new_require_val, $params);
    }
    return true;
}

/**
 * renumbers all items of the given evaluationid
 *
 * @param int $evaluationid
 * @return void
 * @global object
 */
function evaluation_renumber_items($evaluationid) {
    global $DB;

    $items = $DB->get_records('evaluation_item', array('evaluation' => $evaluationid), 'position');
    $pos = 1;
    if ($items) {
        foreach ($items as $item) {
            $DB->set_field('evaluation_item', 'position', $pos, array('id' => $item->id));
            $pos++;
        }
    }
}

/**
 * this decreases the position of the given item
 *
 * @param object $item
 * @return bool
 * @global object
 */
function evaluation_moveup_item($item) {
    global $DB;

    if ($item->position == 1) {
        return true;
    }

    $params = array('evaluation' => $item->evaluation);
    if (!$items = $DB->get_records('evaluation_item', $params, 'position')) {
        return false;
    }

    $itembefore = null;
    foreach ($items as $i) {
        if ($i->id == $item->id) {
            if (is_null($itembefore)) {
                return true;
            }
            $itembefore->position = $item->position;
            $item->position--;
            evaluation_update_item($itembefore);
            evaluation_update_item($item);
            evaluation_renumber_items($item->evaluation);
            return true;
        }
        $itembefore = $i;
    }
    return false;
}

/**
 * this increased the position of the given item
 *
 * @param object $item
 * @return bool
 * @global object
 */
function evaluation_movedown_item($item) {
    global $DB;

    $params = array('evaluation' => $item->evaluation);
    if (!$items = $DB->get_records('evaluation_item', $params, 'position')) {
        return false;
    }

    $movedownitem = null;
    foreach ($items as $i) {
        if (!is_null($movedownitem) and $movedownitem->id == $item->id) {
            $movedownitem->position = $i->position;
            $i->position--;
            evaluation_update_item($movedownitem);
            evaluation_update_item($i);
            evaluation_renumber_items($item->evaluation);
            return true;
        }
        $movedownitem = $i;
    }
    return false;
}

/**
 * here the position of the given item will be set to the value in $pos
 *
 * @param object $moveitem
 * @param int $pos
 * @return boolean
 * @global object
 */
function evaluation_move_item($moveitem, $pos) {
    global $DB;

    $params = array('evaluation' => $moveitem->evaluation);
    if (!$allitems = $DB->get_records('evaluation_item', $params, 'position')) {
        return false;
    }
    if (is_array($allitems)) {
        $index = 1;
        foreach ($allitems as $item) {
            if ($index == $pos) {
                $index++;
            }
            if ($item->id == $moveitem->id) {
                $moveitem->position = $pos;
                evaluation_update_item($moveitem);
                continue;
            }
            $item->position = $index;
            evaluation_update_item($item);
            $index++;
        }
        return true;
    }
    return false;
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_print_item_preview() {
    throw new coding_exception('evaluation_print_item_preview() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_print_item_complete() {
    throw new coding_exception('evaluation_print_item_complete() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_print_item_show_value() {
    throw new coding_exception('evaluation_print_item_show_value() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * if the user completes a evaluation and there is a pagebreak so the values are saved temporary.
 * the values are not saved permanently until the user click on save button
 *
 * @param object $evaluationcompleted
 * @return object temporary saved completed-record
 * @global object
 */
function evaluation_set_tmp_values($evaluationcompleted) {
    global $DB;

    //first we create a completedtmp
    $tmpcpl = new stdClass();
    foreach ($evaluationcompleted as $key => $value) {
        $tmpcpl->{$key} = $value;
    }
    unset($tmpcpl->id);
    $tmpcpl->timemodified = time();
    $tmpcpl->id = $DB->insert_record('evaluation_completedtmp', $tmpcpl);
    //get all values of original-completed
    if (!$values = $DB->get_records('evaluation_value', array('completed' => $evaluationcompleted->id))) {
        return;
    }
    foreach ($values as $value) {
        unset($value->id);
        $value->completed = $tmpcpl->id;
        $DB->insert_record('evaluation_valuetmp', $value);
    }
    return $tmpcpl;
}

/**
 * this saves the temporary saved values permanently
 *
 * @param object $evaluationcompletedtmp the temporary completed
 * @param object $evaluationcompleted the target completed
 * @return int the id of the completed
 * @global object
 */
function evaluation_save_tmp_values($evaluationcompletedtmp, $evaluationcompleted) {
    global $DB;

    $tmpcplid = $evaluationcompletedtmp->id;
    if ($evaluationcompleted) {
        //first drop all existing values
        $DB->delete_records('evaluation_value', array('completed' => $evaluationcompleted->id));
        //update the current completed
        $evaluationcompleted->timemodified = time();
        $DB->update_record('evaluation_completed', $evaluationcompleted);
    } else {
        $evaluationcompleted = clone($evaluationcompletedtmp);
        $evaluationcompleted->id = '';
        $evaluationcompleted->timemodified = time();
        $evaluationcompleted->id = $DB->insert_record('evaluation_completed', $evaluationcompleted);
    }

    $allitems = $DB->get_records('evaluation_item', array('evaluation' => $evaluationcompleted->evaluation));

    //save all the new values from evaluation_valuetmp
    //get all values of tmp-completed
    $params = array('completed' => $evaluationcompletedtmp->id);
    $values = $DB->get_records('evaluation_valuetmp', $params);
    foreach ($values as $value) {
        //check if there are depend items
        $item = $DB->get_record('evaluation_item', array('id' => $value->item));
        if ($item->dependitem > 0 && isset($allitems[$item->dependitem])) {
            $ditem = $allitems[$item->dependitem];
            while ($ditem !== null) {
                $check = evaluation_compare_item_value($tmpcplid,
                        $ditem,
                        $item->dependvalue,
                        true);
                if (!$check) {
                    break;
                }
                if ($ditem->dependitem > 0 && isset($allitems[$ditem->dependitem])) {
                    $item = $ditem;
                    $ditem = $allitems[$ditem->dependitem];
                } else {
                    $ditem = null;
                }
            }

        } else {
            $check = true;
        }
        if ($check) {
            unset($value->id);
            $value->completed = $evaluationcompleted->id;
            $DB->insert_record('evaluation_value', $value);
        }
    }
    //drop all the tmpvalues
    $DB->delete_records('evaluation_valuetmp', array('completed' => $tmpcplid));
    $DB->delete_records('evaluation_completedtmp', array('id' => $tmpcplid));

    // Trigger event for the delete action we performed.
    $cm = get_coursemodule_from_instance('evaluation', $evaluationcompleted->evaluation);
    $event = \mod_evaluation\event\response_submitted::create_from_record($evaluationcompleted, $cm);
    $event->trigger();
    return $evaluationcompleted->id;

}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_delete_completedtmp() {
    throw new coding_exception('evaluation_delete_completedtmp() can not be used anymore.');

}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the pagebreaks
////////////////////////////////////////////////

/**
 * this creates a pagebreak.
 * a pagebreak is a special kind of item
 *
 * @param int $evaluationid
 * @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
 * @global object
 */
function evaluation_create_pagebreak($evaluationid) {
    global $DB;

    //check if there already is a pagebreak on the last position
    $lastposition = $DB->count_records('evaluation_item', array('evaluation' => $evaluationid));
    if ($lastposition == evaluation_get_last_break_position($evaluationid)) {
        return false;
    }

    $item = new stdClass();
    $item->evaluation = $evaluationid;

    $item->template = 0;

    $item->name = '';

    $item->presentation = '';
    $item->hasvalue = 0;

    $item->typ = 'pagebreak';
    $item->position = $lastposition + 1;

    $item->required = 0;

    return $DB->insert_record('evaluation_item', $item);
}

/**
 * get all positions of pagebreaks in the given evaluation
 *
 * @param int $evaluationid
 * @return array all ordered pagebreak positions
 * @global object
 */
function evaluation_get_all_break_positions($evaluationid) {
    global $DB;

    $params = array('typ' => 'pagebreak', 'evaluation' => $evaluationid);
    $allbreaks = $DB->get_records_menu('evaluation_item', $params, 'position', 'id, position');
    if (!$allbreaks) {
        return false;
    }
    return array_values($allbreaks);
}

/**
 * get the position of the last pagebreak
 *
 * @param int $evaluationid
 * @return int the position of the last pagebreak
 */
function evaluation_get_last_break_position($evaluationid) {
    if (!$allbreaks = evaluation_get_all_break_positions($evaluationid)) {
        return false;
    }
    return $allbreaks[safeCount($allbreaks) - 1];
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_get_page_to_continue() {
    throw new coding_exception('evaluation_get_page_to_continue() can not be used anymore.');
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the values
////////////////////////////////////////////////

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_clean_input_value() {
    throw new coding_exception('evaluation_clean_input_value() can not be used anymore. '
            . 'Items must implement complete_form_element().');

}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_save_values() {
    throw new coding_exception('evaluation_save_values() can not be used anymore.');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_save_guest_values() {
    throw new coding_exception('evaluation_save_guest_values() can not be used anymore.');
}

/**
 * get the value from the given item related to the given completed.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp
 *
 * @param int $completeid
 * @param int $itemid
 * @param boolean $tmp
 * @return mixed the value, the type depends on plugin-definition
 * @global object
 */
function evaluation_get_item_value($completedid, $itemid, $tmp = false) {
    global $DB;

    $tmpstr = $tmp ? 'tmp' : '';
    $params = array('completed' => $completedid, 'item' => $itemid);
    return $DB->get_field('evaluation_value' . $tmpstr, 'value', $params);
}

/**
 * compares the value of the itemid related to the completedid with the dependvalue.
 * this is used if a depend item is set.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp.
 *
 * @param int $completedid
 * @param stdClass|int $item
 * @param mixed $dependvalue
 * @param bool $tmp
 * @return bool
 */
function evaluation_compare_item_value($completedid, $item, $dependvalue, $tmp = false) {
    global $DB;

    if (is_int($item)) {
        $item = $DB->get_record('evaluation_item', array('id' => $item));
    }

    $dbvalue = evaluation_get_item_value($completedid, $item->id, $tmp);

    $itemobj = evaluation_get_item_class($item->typ);
    return $itemobj->compare_value($item, $dbvalue, $dependvalue); //true or false
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_check_values() {
    throw new coding_exception('evaluation_check_values() can not be used anymore. '
            . 'Items must implement complete_form_element().');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_create_values() {
    throw new coding_exception('evaluation_create_values() can not be used anymore.');
}

/**
 * @deprecated since Moodle 3.1
 */
function evaluation_update_values() {
    throw new coding_exception('evaluation_update_values() can not be used anymore.');
}

/**
 * get the values of an item depending on the given groupid.
 * if the evaluation is anonymous so the values are shuffled
 *
 * @param object $item
 * @param int $groupid
 * @param int $courseid
 * @param bool $ignore_empty if this is set true so empty values are not delivered
 * @return array the value-records
 * @global object
 * @global object
 */
function evaluation_get_group_values($item,
        $groupid = false,
        $courseid = false,
        $teacherid = false,
        $course_of_studies = false,
        $department = false,
        $ignore_empty = false) {
    global $CFG, $DB;

    //if the groupid is given?
    if (intval($groupid) > 0) {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('fbv.value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        } else {
            $ignore_empty_select = "";
        }

        $query = 'SELECT fbv .  *
                    FROM {evaluation_value} fbv, {evaluation_completed} fbc, {groups_members} gm
                   WHERE fbv.item = :itemid
                         AND fbv.completed = fbc.id
                         AND fbc.userid = gm.userid
                         ' . $ignore_empty_select . '
                         AND gm.groupid = :groupid
                ORDER BY fbc.timemodified';
        $params += array('itemid' => $item->id, 'groupid' => $groupid);
        $values = $DB->get_records_sql($query, $params);

    } else {
        $params = array();
        if ($ignore_empty) {
            $value = $DB->sql_compare_text('value');
            $ignore_empty_select = "AND $value != :emptyvalue AND $value != :zerovalue";
            $params += array('emptyvalue' => '', 'zerovalue' => '0');
        } else {
            $ignore_empty_select = "";
        }

        $select = "item = :itemid " . $ignore_empty_select;
        $params += array('itemid' => $item->id);
        if ($courseid) {
            $select .= " AND courseid = :courseid ";
            $params += array('courseid' => $courseid);
        }
        if ($teacherid) {
            $select .= " AND teacherid = :teacherid ";
            $params += array('teacherid' => $teacherid);
        }
        if ($course_of_studies) {
            $select .= " AND course_of_studies = :course_of_studies ";
            $params += array('course_of_studies' => $course_of_studies);
        }
        if ($department) {
            global $evaluationstructure;
            $filterD = str_ireplace("completed.", "",$evaluationstructure->get_department_filter());
            $select .= "$filterD ";
        }
        $values = $DB->get_records_select('evaluation_value', $select, $params);
    }
    $params = array('id' => $item->evaluation);
    if ($DB->get_field('evaluation', 'anonymous', $params) == EVALUATION_ANONYMOUS_YES) {
        if (is_array($values)) {
            shuffle($values);
        }
    }
    return $values;
}

/**
 * check for multiple_submit = false.
 * if the evaluation is global so the courseid must be given
 *
 * @param int $evaluationid
 * @param int $courseid
 * @return boolean true if the evaluation already is submitted otherwise false
 * @global object
 * @global object
 */
function evaluation_is_already_submitted($evaluationid, $courseid = false) {
    global $USER, $DB;

    if (!isloggedin() || isguestuser()) {
        return false;
    }

    $params = array('userid' => $USER->id, 'evaluation' => $evaluationid);
    if ($courseid) {
        $params['courseid'] = $courseid;
    }
    return $DB->record_exists('evaluation_completed', $params);
}

/**
 * @deprecated since Moodle 3.1. Use evaluation_get_current_completed_tmp() or evaluation_get_last_completed.
 */
function evaluation_get_current_completed() {
    throw new coding_exception('evaluation_get_current_completed() can not be used anymore. Please ' .
            'use either evaluation_get_current_completed_tmp() or evaluation_get_last_completed()');
}

/**
 * get the completeds depending on the given groupid.
 *
 * @param object $evaluation
 * @param int $groupid
 * @param int $courseid
 * @return mixed array of found completeds otherwise false
 * @global object
 * @global object
 */
function evaluation_get_completeds_group_count($evaluation, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if (intval($groupid) > 0) {
        $query = "SELECT fbc.*
                    FROM {evaluation_completed} fbc, {groups_members} gm
                   WHERE fbc.evaluation = ?
                         AND gm.groupid = ?
                         AND fbc.userid = gm.userid";
        if ($values = $DB->get_records_sql($query, array($evaluation->id, $groupid))) {
            return $values;
        } else {
            return false;
        }
    } else {
        if ($courseid) {
            $query = "SELECT DISTINCT fbc.*
                        FROM {evaluation_completed} fbc, {evaluation_value} fbv
                        WHERE fbc.id = fbv.completed
                            AND fbc.evaluation = ?
                            AND fbv.courseid = ?
                        ORDER BY random_response";
            if ($values = $DB->get_records_sql($query, array($evaluation->id, $courseid))) {
                return $values;
            } else {
                return false;
            }
        } else {
            if ($values = $DB->get_records('evaluation_completed', array('evaluation' => $evaluation->id))) {
                return $values;
            } else {
                return false;
            }
        }
    }
}

/**
 * get the count of completeds depending on the given groupid.
 *
 * @param object $evaluation
 * @param int $groupid
 * @param int $courseid
 * @return mixed count of completeds or false
 * @global object
 * @global object
 */
function evaluation_get_completeds_group_safeCount($evaluation, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if ($courseid > 0 and !$groupid <= 0) {
        $sql = "SELECT id, COUNT(item) AS ci
                  FROM {evaluation_value}
                 WHERE courseid  = ?
              GROUP BY item ORDER BY ci DESC";
        if ($foundrecs = $DB->get_records_sql($sql, array($courseid))) {
            $foundrecs = array_values($foundrecs);
            return $foundrecs[0]->ci;
        }
        return false;
    }
    if ($values = evaluation_get_completeds_group($evaluation, $groupid)) {
        return safeCount($values);
    } else {
        return false;
    }
}

/**
 * deletes all completed-recordsets from a evaluation.
 * all related data such as values also will be deleted
 *
 * @param stdClass|int $evaluation
 * @param stdClass|cm_info $cm
 * @param stdClass $course
 * @return void
 */
function evaluation_delete_all_completeds($evaluation, $cm = null, $course = null) {
    global $DB;

    if (is_int($evaluation)) {
        $evaluation = $DB->get_record('evaluation', array('id' => $evaluation));
    }

    if (!$completeds = $DB->get_records('evaluation_completed', array('evaluation' => $evaluation->id))) {
        return;
    }

    if (!$course && !($course = $DB->get_record('course', array('id' => $evaluation->course)))) {
        return false;
    }

    if (!$cm && !($cm = get_coursemodule_from_instance('evaluation', $evaluation->id))) {
        return false;
    }

    foreach ($completeds as $completed) {
        evaluation_delete_completed($completed, $evaluation, $cm, $course);
    }
}

/**
 * deletes a completed given by completedid.
 * all related data such values or tracking data also will be deleted
 *
 * @param int|stdClass $completed
 * @param stdClass $evaluation
 * @param stdClass|cm_info $cm
 * @param stdClass $course
 * @return boolean
 */
function evaluation_delete_completed($completed, $evaluation = null, $cm = null, $course = null) {
    global $DB, $CFG;
    require_once($CFG->libdir . '/completionlib.php');

    if (!isset($completed->id)) {
        if (!$completed = $DB->get_record('evaluation_completed', array('id' => $completed))) {
            return false;
        }
    }

    if (!$evaluation && !($evaluation = $DB->get_record('evaluation', array('id' => $completed->evaluation)))) {
        return false;
    }

    if (!$course && !($course = $DB->get_record('course', array('id' => $evaluation->course)))) {
        return false;
    }

    if (!$cm && !($cm = get_coursemodule_from_instance('evaluation', $evaluation->id))) {
        return false;
    }

    //first we delete all related values
    $DB->delete_records('evaluation_value', array('completed' => $completed->id));

    // Delete the completed record.
    $return = $DB->delete_records('evaluation_completed', array('id' => $completed->id));

    // Update completion state
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && $cm->completion == COMPLETION_TRACKING_AUTOMATIC && $evaluation->completionsubmit) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
    }
    // Trigger event for the delete action we performed.
    $event = \mod_evaluation\event\response_deleted::create_from_record($completed, $cm, $evaluation);
    $event->trigger();

    return $return;
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle sitecourse mapping
////////////////////////////////////////////////

/**
 * @deprecated since 3.1
 */
function evaluation_is_course_in_sitecourse_map() {
    throw new coding_exception('evaluation_is_course_in_sitecourse_map() can not be used anymore.');
}

/**
 * @deprecated since 3.1
 */
function evaluation_is_evaluation_in_sitecourse_map() {
    throw new coding_exception('evaluation_is_evaluation_in_sitecourse_map() can not be used anymore.');
}

/**
 * gets the evaluations from table evaluation_sitecourse_map.
 * this is used to show the global evaluations on the evaluation block
 * all evaluations with the following criteria will be selected:<br />
 *
 * 1) all evaluations which id are listed together with the courseid in sitecoursemap and<br />
 * 2) all evaluations which not are listed in sitecoursemap
 *
 * @param int $courseid
 * @return array the evaluation-records
 * @global object
 */
function evaluation_get_evaluations_from_sitecourse_map($courseid) {
    global $DB;

    //first get all evaluations listed in sitecourse_map with named courseid
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.course AS course,
                   f.name AS name,
                   f.timeopen AS timeopen,
                   f.timeclose AS timeclose,
				   f.semester AS semester,
				   f.min_results AS min_results,				   
                   f.privileged_users AS privileged_users,
                   f.filter_course_of_studies AS filter_course_of_studies,
				   f.filter_courses AS filter_courses,
				   f.teamteaching AS teamteaching
            FROM {evaluation} f, {course_modules} cm, {evaluation_sitecourse_map} sm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '" . SITEID . "'
                   AND m.id = cm.module
                   AND m.name = 'evaluation'
                   AND sm.courseid = ?
                   AND sm.evaluationid = f.id
				   ORDER BY f.id DESC";

    if (!$evaluations1 = $DB->get_records_sql($sql, array($courseid))) {
        $evaluations1 = array();
    }

    //second get all evaluations not listed in sitecourse_map
    $evaluations2 = array();
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.course AS course,
                   f.name AS name,
                   f.timeopen AS timeopen,
                   f.timeclose AS timeclose,
				   f.semester AS semester,
				   f.min_results AS min_results,				   
                   f.privileged_users AS privileged_users,
                   f.filter_course_of_studies AS filter_course_of_studies,
				   f.filter_courses AS filter_courses,
				   f.teamteaching AS teamteaching
            FROM {evaluation} f, {course_modules} cm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '" . SITEID . "'
                   AND m.id = cm.module
                   AND m.name = 'evaluation'
				   ORDER BY f.id DESC";
    if (!$allevaluations = $DB->get_records_sql($sql)) {
        $allevaluations = array();
    }
    foreach ($allevaluations as $a) {
        if (!$DB->record_exists('evaluation_sitecourse_map', array('evaluationid' => $a->id))) {
            $evaluations2[] = $a;
        }
    }

    $evaluations = array_merge($evaluations1, $evaluations2);
    $modinfo = get_fast_modinfo(SITEID);
    return array_filter($evaluations, function($f) use ($modinfo) {
        return ($cm = $modinfo->get_cm($f->cmid)) && $cm->uservisible;
    });

}

/**
 * Gets the courses from table evaluation_sitecourse_map
 *
 * @param int $evaluationid
 * @return array the course-records
 */
function evaluation_get_courses_from_sitecourse_map($evaluationid) {
    global $DB;

    $sql = "SELECT c.id, c.fullname, c.shortname
              FROM {evaluation_sitecourse_map} f, {course} c
             WHERE c.id = f.courseid
                   AND f.evaluationid = ?
          ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array($evaluationid));

}

/**
 * Updates the course mapping for the evaluation
 *
 * @param stdClass $evaluation
 * @param array $courses array of course ids
 */
function evaluation_update_sitecourse_map($evaluation, $courses) {
    global $DB;
    if (empty($courses)) {
        $courses = array();
    }
    $currentmapping = $DB->get_fieldset_select('evaluation_sitecourse_map', 'courseid', 'evaluationid=?', array($evaluation->id));
    foreach (array_diff($courses, $currentmapping) as $courseid) {
        $DB->insert_record('evaluation_sitecourse_map', array('evaluationid' => $evaluation->id, 'courseid' => $courseid));
    }
    foreach (array_diff($currentmapping, $courses) as $courseid) {
        $DB->delete_records('evaluation_sitecourse_map', array('evaluationid' => $evaluation->id, 'courseid' => $courseid));
    }
    // TODO MDL-53574 add events.
}

/**
 * @deprecated since 3.1
 */
function evaluation_clean_up_sitecourse_map() {
    throw new coding_exception('evaluation_clean_up_sitecourse_map() can not be used anymore.');
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//not relatable functions
////////////////////////////////////////////////

/**
 * @deprecated since 3.1
 */
function evaluation_print_numeric_option_list() {
    throw new coding_exception('evaluation_print_numeric_option_list() can not be used anymore.');
}

/**
 * sends an email to the teachers of the course where the given evaluation is placed.
 *
 * @param object $cm the coursemodule-record
 * @param object $evaluation
 * @param object $course
 * @param stdClass|int $user
 * @param stdClass $completed record from evaluation_completed if known
 * @return void
 * @global object
 * @global object
 * @uses EVALUATION_ANONYMOUS_NO
 * @uses FORMAT_PLAIN
 */
function evaluation_send_email($cm, $evaluation, $course, $user, $completed = null) {
    global $CFG, $DB, $PAGE;

    if ($evaluation->email_notification == 0) {  // No need to do anything
        return;
    }

    if (!is_object($user)) {
        $user = $DB->get_record('user', array('id' => $user));
    }

    if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
        $groupmode = $cm->groupmode;
    } else {
        $groupmode = $course->groupmode;
    }

    if ($groupmode == SEPARATEGROUPS) {
        $groups = $DB->get_records_sql_menu("SELECT g.name, g.id
                                               FROM {groups} g, {groups_members} m
                                              WHERE g.courseid = ?
                                                    AND g.id = m.groupid
                                                    AND m.userid = ?
                                           ORDER BY name ASC", array($course->id, $user->id));
        $groups = array_values($groups);

        $teachers = evaluation_get_receivemail_users($cm->id, $groups);
    } else {
        $teachers = evaluation_get_receivemail_users($cm->id);
    }

    if ($teachers) {

        $strevaluations = get_string('modulenameplural', 'evaluation');
        $strevaluation = get_string('modulename', 'evaluation');

        if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO) {
            $printusername = fullname($user);
        } else {
            $printusername = get_string('anonymous_user', 'evaluation');
        }

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->evaluation = format_string($evaluation->name, true);
            $info->url = $CFG->wwwroot . '/mod/evaluation/show_entries.php?' .
                    'id=' . $cm->id . '&' .
                    'userid=' . $user->id;
            if ($completed) {
                $info->url .= '&showcompleted=' . $completed->id;
                if ($evaluation->course == SITEID) {
                    // Course where evaluation was completed (for site evaluations only).
                    $info->url .= '&courseid=' . $completed->courseid;
                }
            }

            $a = array('username' => $info->username, 'evaluationname' => $evaluation->name);

            $postsubject = get_string('evaluationcompleted', 'evaluation', $a);
            $posttext = evaluation_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = evaluation_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            $customdata = [
                    'cmid' => $cm->id,
                    'instance' => $evaluation->id,
            ];
            if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO) {
                $eventdata = new \core\message\message();
                $eventdata->anonymous = false;
                $eventdata->courseid = $course->id;
                $eventdata->name = 'submission';
                $eventdata->component = 'mod_evaluation';
                $eventdata->userfrom = $user;
                $eventdata->userto = $teacher;
                $eventdata->subject = $postsubject;
                $eventdata->fullmessage = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = $posthtml;
                $eventdata->smallmessage = '';
                $eventdata->courseid = $course->id;
                $eventdata->contexturl = $info->url;
                $eventdata->contexturlname = $info->evaluation;
                // User image.
                $userpicture = new user_picture($user);
                $userpicture->size = 1; // Use f1 size.
                $userpicture->includetoken = $teacher->id; // Generate an out-of-session token for the user receiving the message.
                $customdata['notificationiconurl'] = $userpicture->get_url($PAGE)->out(false);
                $eventdata->customdata = $customdata;
                message_send($eventdata);
            } else {
                $eventdata = new \core\message\message();
                $eventdata->anonymous = true;
                $eventdata->courseid = $course->id;
                $eventdata->name = 'submission';
                $eventdata->component = 'mod_evaluation';
                $eventdata->userfrom = $teacher;
                $eventdata->userto = $teacher;
                $eventdata->subject = $postsubject;
                $eventdata->fullmessage = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml = $posthtml;
                $eventdata->smallmessage = '';
                $eventdata->courseid = $course->id;
                $eventdata->contexturl = $info->url;
                $eventdata->contexturlname = $info->evaluation;
                // Evaluation icon if can be easily reachable.
                $customdata['notificationiconurl'] = ($cm instanceof cm_info) ? $cm->get_icon_url()->out() : '';
                $eventdata->customdata = $customdata;
                message_send($eventdata);
            }
        }
    }
}

/**
 * sends an email to the teachers of the course where the given evaluation is placed.
 *
 * @param object $cm the coursemodule-record
 * @param object $evaluation
 * @param object $course
 * @return void
 * @global object
 * @uses FORMAT_PLAIN
 */
function evaluation_send_email_anonym($cm, $evaluation, $course) {
    global $CFG;

    if ($evaluation->email_notification == 0) { // No need to do anything
        return;
    }

    $teachers = evaluation_get_receivemail_users($cm->id);

    if ($teachers) {

        $strevaluations = get_string('modulenameplural', 'evaluation');
        $strevaluation = get_string('modulename', 'evaluation');
        $printusername = get_string('anonymous_user', 'evaluation');

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->evaluation = format_string($evaluation->name, true);
            $info->url = $CFG->wwwroot . '/mod/evaluation/show_entries.php?id=' . $cm->id;

            $a = array('username' => $info->username, 'evaluationname' => $evaluation->name);

            $postsubject = get_string('evaluationcompleted', 'evaluation', $a);
            $posttext = evaluation_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = evaluation_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            $eventdata = new \core\message\message();
            $eventdata->anonymous = true;
            $eventdata->courseid = $course->id;
            $eventdata->name = 'submission';
            $eventdata->component = 'mod_evaluation';
            $eventdata->userfrom = $teacher;
            $eventdata->userto = $teacher;
            $eventdata->subject = $postsubject;
            $eventdata->fullmessage = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = $posthtml;
            $eventdata->smallmessage = '';
            $eventdata->courseid = $course->id;
            $eventdata->contexturl = $info->url;
            $eventdata->contexturlname = $info->evaluation;
            $eventdata->customdata = [
                    'cmid' => $cm->id,
                    'instance' => $evaluation->id,
                    'notificationiconurl' => ($cm instanceof cm_info) ? $cm->get_icon_url()->out() : '',  // Performance wise.
            ];

            message_send($eventdata);
        }
    }
}

/**
 * send the text-part of the email
 *
 * @param object $info includes some infos about the evaluation you want to send
 * @param object $course
 * @return string the text you want to post
 */
function evaluation_send_email_text($info, $course) {
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $posttext = $courseshortname . ' -> ' . get_string('modulenameplural', 'evaluation') . ' -> ' .
            $info->evaluation . "\n";
    $posttext .= '---------------------------------------------------------------------' . "\n";
    $posttext .= get_string("emailteachermail", "evaluation", $info) . "\n";
    $posttext .= '---------------------------------------------------------------------' . "\n";
    return $posttext;
}

/**
 * send the html-part of the email
 *
 * @param object $info includes some infos about the evaluation you want to send
 * @param object $course
 * @return string the text you want to post
 * @global object
 */
function evaluation_send_email_html($info, $course, $cm) {
    global $CFG;
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $course_url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
    $evaluation_all_url = $CFG->wwwroot . '/mod/evaluation/index.php?id=' . $course->id;
    $evaluation_url = $CFG->wwwroot . '/mod/evaluation/view.php?id=' . $cm->id;

    $posthtml = '<p><font face="sans-serif">' .
            '<a href="' . $course_url . '">' . $courseshortname . '</a> ->' .
            '<a href="' . $evaluation_all_url . '">' . get_string('modulenameplural', 'evaluation') . '</a> ->' .
            '<a href="' . $evaluation_url . '">' . $info->evaluation . '</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif">';
    $posthtml .= '<p>' . get_string('emailteachermailhtml', 'evaluation', $info) . '</p>';
    $posthtml .= '</font><hr />';
    return $posthtml;
}

/**
 * @param string $url
 * @return string
 */
function evaluation_encode_target_url($url) {
    if (strpos($url, '?')) {
        list($part1, $part2) = explode('?', $url, 2); //maximal 2 parts
        return $part1 . '?' . htmlentities($part2);
    } else {
        return $url;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $evaluationnode The node to add module settings to
 */
function evaluation_extend_settings_navigation(settings_navigation $settings,
        navigation_node $evaluationnode) {

    global $PAGE;

    if (!$context = context_module::instance($PAGE->cm->id, IGNORE_MISSING)) {
        throw new moodle_exception('badcontext');
    }

    if (has_capability('mod/evaluation:edititems', $context)) {
        $questionnode = $evaluationnode->add(get_string('questions', 'evaluation'));

        $questionnode->add(get_string('edit_items', 'evaluation'),
                new moodle_url('/mod/evaluation/edit.php',
                        array('id' => $PAGE->cm->id,
                                'do_show' => 'edit')));

        $questionnode->add(get_string('export_questions', 'evaluation'),
                new moodle_url('/mod/evaluation/export.php',
                        array('id' => $PAGE->cm->id,
                                'action' => 'exportfile')));

        $questionnode->add(get_string('import_questions', 'evaluation'),
                new moodle_url('/mod/evaluation/import.php',
                        array('id' => $PAGE->cm->id)));

        $questionnode->add(get_string('templates', 'evaluation'),
                new moodle_url('/mod/evaluation/edit.php',
                        array('id' => $PAGE->cm->id,
                                'do_show' => 'templates')));
    }

    if (has_capability('mod/evaluation:mapcourse', $context) && $PAGE->course->id == SITEID) {
        $evaluationnode->add(get_string('mappedcourses', 'evaluation'),
                new moodle_url('/mod/evaluation/mapcourse.php',
                        array('id' => $PAGE->cm->id)));
    }

    if (has_capability('mod/evaluation:viewreports', $context)) {
        $evaluation = $PAGE->activityrecord;
        if ($evaluation->course == SITEID) {
            $evaluationnode->add(get_string('analysis', 'evaluation'),
                    new moodle_url('/mod/evaluation/analysis_course.php',
                            array('id' => $PAGE->cm->id)));
        } else {
            $evaluationnode->add(get_string('analysis', 'evaluation'),
                    new moodle_url('/mod/evaluation/analysis.php',
                            array('id' => $PAGE->cm->id)));
        }

        $evaluationnode->add(get_string('show_entries', 'evaluation'),
                new moodle_url('/mod/evaluation/show_entries.php',
                        array('id' => $PAGE->cm->id)));

        if ($evaluation->anonymous == EVALUATION_ANONYMOUS_NO and $evaluation->course != SITEID) {
            $evaluationnode->add(get_string('show_nonrespondents', 'evaluation'),
                    new moodle_url('/mod/evaluation/show_nonrespondents.php',
                            array('id' => $PAGE->cm->id)));
        }
    }
}

function evaluation_init_evaluation_session() {
    //initialize the evaluation-Session - not nice at all!!
    global $SESSION;
    if (!empty($SESSION)) {
        if (!isset($SESSION->evaluation) or !is_object($SESSION->evaluation)) {
            $SESSION->evaluation = new stdClass();
        }
    }
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function evaluation_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-evaluation-*' => get_string('page-mod-evaluation-x', 'evaluation'));
    return $module_pagetype;
}

/**
 * Move save the items of the given $evaluation in the order of $itemlist.
 *
 * @param string $itemlist a comma separated list with item ids
 * @param stdClass $evaluation
 * @return bool true if success
 */
function evaluation_ajax_saveitemorder($itemlist, $evaluation) {
    global $DB;

    $result = true;
    $position = 0;
    foreach ($itemlist as $itemid) {
        $position++;
        $result = $result && $DB->set_field('evaluation_item',
                        'position',
                        $position,
                        array('id' => $itemid, 'evaluation' => $evaluation->id));
    }
    return $result;
}

/**
 * Checks if current user is able to view evaluation on this course.
 *
 * @param stdClass $evaluation
 * @param context_module $context
 * @param int $courseid
 * @return bool
 */
function evaluation_can_view_analysis($evaluation, $context, $courseid = false) {
    if (has_capability('mod/evaluation:viewreports', $context)) {
        return true;
    }

    if (intval($evaluation->publish_stats) != 1 ||
            !has_capability('mod/evaluation:viewanalysepage', $context)) {
        return false;
    }

    if (!isloggedin() || isguestuser()) {
        // There is no tracking for the guests, assume that they can view analysis if condition above is satisfied.
        return $evaluation->course == SITEID;
    }

    return evaluation_is_already_submitted($evaluation->id, $courseid);
}

/**
 * Get icon mapping for font-awesome.
 */
function mod_evaluation_get_fontawesome_icon_map() {
    return [
            'mod_evaluation:required' => 'fa-exclamation-circle',
            'mod_evaluation:notrequired' => 'fa-question-circle-o',
    ];
}

/**
 * Check if the module has any update that affects the current user since a given time.
 *
 * @param cm_info $cm course module data
 * @param int $from the time to check updates from
 * @param array $filter if we need to check only specific updates
 * @return stdClass an object with the different type of areas indicating if they were updated or not
 * @since Moodle 3.3
 */
function evaluation_check_updates_since(cm_info $cm, $from, $filter = array()) {
    global $DB, $USER, $CFG;

    $updates = course_check_module_updates_since($cm, $from, array(), $filter);

    // Check for new attempts.
    $updates->attemptsfinished = (object) array('updated' => false);
    $updates->attemptsunfinished = (object) array('updated' => false);
    $select = 'evaluation = ? AND userid = ? AND timemodified > ?';
    $params = array($cm->instance, $USER->id, $from);

    $attemptsfinished = $DB->get_records_select('evaluation_completed', $select, $params, '', 'id');
    if (!empty($attemptsfinished)) {
        $updates->attemptsfinished->updated = true;
        $updates->attemptsfinished->itemids = array_keys($attemptsfinished);
    }
    $attemptsunfinished = $DB->get_records_select('evaluation_completedtmp', $select, $params, '', 'id');
    if (!empty($attemptsunfinished)) {
        $updates->attemptsunfinished->updated = true;
        $updates->attemptsunfinished->itemids = array_keys($attemptsunfinished);
    }

    // Now, teachers should see other students updates.
    if (has_capability('mod/evaluation:viewreports', $cm->context)) {
        $select = 'evaluation = ? AND timemodified > ?';
        $params = array($cm->instance, $from);

        if (groups_get_activity_groupmode($cm) == SEPARATEGROUPS) {
            $groupusers = array_keys(groups_get_activity_shared_group_members($cm));
            if (empty($groupusers)) {
                return $updates;
            }
            list($insql, $inparams) = $DB->get_in_or_equal($groupusers);
            $select .= ' AND userid ' . $insql;
            $params = array_merge($params, $inparams);
        }

        $updates->userattemptsfinished = (object) array('updated' => false);
        $attemptsfinished = $DB->get_records_select('evaluation_completed', $select, $params, '', 'id');
        if (!empty($attemptsfinished)) {
            $updates->userattemptsfinished->updated = true;
            $updates->userattemptsfinished->itemids = array_keys($attemptsfinished);
        }

        $updates->userattemptsunfinished = (object) array('updated' => false);
        $attemptsunfinished = $DB->get_records_select('evaluation_completedtmp', $select, $params, '', 'id');
        if (!empty($attemptsunfinished)) {
            $updates->userattemptsunfinished->updated = true;
            $updates->userattemptsunfinished->itemids = array_keys($attemptsunfinished);
        }
    }

    return $updates;
}

/**
 * Add a get_coursemodule_info function in case any evaluation type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function evaluation_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionsubmit, timeopen, timeclose, teamteaching, semester, anonymous, course';
    if (!$evaluation = $DB->get_record('evaluation', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $evaluation->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = "$evaluation->intro";  //format_module_intro('evaluation', $evaluation, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionsubmit'] = $evaluation->completionsubmit;
    }
    // Populate some other values that can be used in calendar or on dashboard.
    if ($evaluation->timeopen) {
        $result->customdata['timeopen'] = $evaluation->timeopen;
    }
    if ($evaluation->timeclose) {
        $result->customdata['timeclose'] = $evaluation->timeclose;
    }
    if ($evaluation->anonymous) {
        $result->customdata['anonymous'] = $evaluation->anonymous;
    }
    // patched by harry
    //$result->customdata['name'] = $evaluation->name;
    //$result->customdata['course'] = $evaluation->course;
    // end patch
    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_evaluation_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
            || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'evaluation');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * This function calculates the minimum and maximum cutoff values for the timestart of
 * the given event.
 *
 * It will return an array with two values, the first being the minimum cutoff value and
 * the second being the maximum cutoff value. Either or both values can be null, which
 * indicates there is no minimum or maximum, respectively.
 *
 * If a cutoff is required then the function must return an array containing the cutoff
 * timestamp and error string to display to the user if the cutoff value is violated.
 *
 * A minimum and maximum cutoff return value will look like:
 * [
 *     [1505704373, 'The due date must be after the sbumission start date'],
 *     [1506741172, 'The due date must be before the cutoff date']
 * ]
 *
 * @param calendar_event $event The calendar event to get the time range for
 * @param stdClass $instance The module instance to get the range from
 * @return array
 */
function mod_evaluation_core_calendar_get_valid_event_timestart_range(\calendar_event $event, \stdClass $instance) {
    $mindate = null;
    $maxdate = null;

    if ($event->eventtype == EVALUATION_EVENT_TYPE_OPEN) {
        // The start time of the open event can't be equal to or after the
        // close time of the choice activity.
        if (!empty($instance->timeclose)) {
            $maxdate = [
                    $instance->timeclose,
                    get_string('openafterclose', 'evaluation')
            ];
        }
    } else if ($event->eventtype == EVALUATION_EVENT_TYPE_CLOSE) {
        // The start time of the close event can't be equal to or earlier than the
        // open time of the choice activity.
        if (!empty($instance->timeopen)) {
            $mindate = [
                    $instance->timeopen,
                    get_string('closebeforeopen', 'evaluation')
            ];
        }
    }

    return [$mindate, $maxdate];
}

/**
 * This creates new events given as timeopen and closeopen by $evaluation.
 *
 * @param object $evaluation
 * @return void
 * @global object
 */
function evaluation_set_events($evaluation) {
    global $DB, $CFG;

    // Include calendar/lib.php.
    require_once($CFG->dirroot . '/calendar/lib.php');

    // Get CMID if not sent as part of $evaluation.
    if (!isset($evaluation->coursemodule)) {
        $cm = get_coursemodule_from_instance('evaluation', $evaluation->id, $evaluation->course);
        $evaluation->coursemodule = $cm->id;
    }

    // Evaluation start calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => 'evaluation', 'instance' => $evaluation->id, 'eventtype' => EVALUATION_EVENT_TYPE_OPEN));

    // by harry 20210610 - only temp usage
    /*if ( $evaluation->course == SITEID )
    {   // Evaluation is global Evaluation
        if ($eventid) {
			$calendarevent = calendar_event::load($eventid);
			$calendarevent->delete();
		}
		return;
    }*/

    if (isset($evaluation->timeopen) && $evaluation->timeopen > 0) //if ( false )
    {
        $event = new stdClass();
        $event->eventtype = EVALUATION_EVENT_TYPE_OPEN;
        $event->type = empty($evaluation->timeclose) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
        $event->name = get_string('calendarstart', 'evaluation', $evaluation->name);
        $event->description =
                "$evaluation->intro"; //format_module_intro('evaluation', $evaluation, $evaluation->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->timestart = $evaluation->timeopen;
        $event->timesort = $evaluation->timeopen;
        $event->visible = instance_is_visible('evaluation', $evaluation);
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Event doesn't exist so create one.
            $event->courseid = $evaluation->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'evaluation';
            $event->instance = $evaluation->id;
            $event->eventtype = EVALUATION_EVENT_TYPE_OPEN;
            calendar_event::create($event, false);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }

    // Evaluation close calendar events.
    $eventid = $DB->get_field('event', 'id',
            array('modulename' => 'evaluation', 'instance' => $evaluation->id, 'eventtype' => EVALUATION_EVENT_TYPE_CLOSE));

    if (isset($evaluation->timeclose) && $evaluation->timeclose > 0) //if ( false )
    {
        $event = new stdClass();
        $event->type = CALENDAR_EVENT_TYPE_ACTION;
        $event->eventtype = EVALUATION_EVENT_TYPE_CLOSE;
        $event->name = get_string('calendarend', 'evaluation', $evaluation->name);
        $event->description =
                "$evaluation->intro"; // format_module_intro('evaluation', $evaluation, $evaluation->coursemodule, false);
        $event->format = FORMAT_HTML;
        $event->timestart = $evaluation->timeclose;
        $event->timesort = $evaluation->timeclose;
        $event->visible = instance_is_visible('evaluation', $evaluation);
        $event->timeduration = 0;
        if ($eventid) {
            // Calendar event exists so update it.
            $event->id = $eventid;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Event doesn't exist so create one.
            $event->courseid = $evaluation->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'evaluation';
            $event->instance = $evaluation->id;
            calendar_event::create($event, false);
        }
    } else if ($eventid) {
        // Calendar event is on longer needed.
        $calendarevent = calendar_event::load($eventid);
        $calendarevent->delete();
    }
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every evaluation event in the site is checked, else
 * only evaluation events belonging to the course specified are checked.
 * This function is used, in its new format, by restore_refresh_events()
 *
 * @param int $courseid
 * @param int|stdClass $instance Evaluation module instance or ID.
 * @param int|stdClass $cm Course module object or ID (not used in this module).
 * @return bool
 */
function evaluation_refresh_events($courseid = 0, $instance = null, $cm = null) {
    global $DB;

    // If we have instance information then we can just update the one event instead of updating all events.
    if (isset($instance)) {
        if (!is_object($instance)) {
            $instance = $DB->get_record('evaluation', array('id' => $instance), '*', MUST_EXIST);
        }
        evaluation_set_events($instance);
        return true;
    }

    if ($courseid) {
        if (!$evaluations = $DB->get_records("evaluation", array("course" => $courseid))) {
            return true;
        }
    } else {
        if (!$evaluations = $DB->get_records("evaluation")) {
            return true;
        }
    }

    foreach ($evaluations as $evaluation) {
        evaluation_set_events($evaluation);
    }
    return true;
}

/**
 * This function will update the evaluation module according to the
 * event that has been modified.
 *
 * It will set the timeopen or timeclose value of the evaluation instance
 * according to the type of event provided.
 *
 * @param \calendar_event $event
 * @param stdClass $evaluation The module instance to get the range from
 * @throws \moodle_exception
 */
function mod_evaluation_core_calendar_event_timestart_updated(\calendar_event $event, \stdClass $evaluation) {
    global $CFG, $DB;

    if (empty($event->instance) || $event->modulename != 'evaluation') {
        return;
    }

    if ($event->instance != $evaluation->id) {
        return;
    }

    if (!in_array($event->eventtype, [EVALUATION_EVENT_TYPE_OPEN, EVALUATION_EVENT_TYPE_CLOSE])) {
        return;
    }

    $courseid = $event->courseid;
    $modulename = $event->modulename;
    $instanceid = $event->instance;
    $modified = false;

    $coursemodule = get_fast_modinfo($courseid)->instances[$modulename][$instanceid];
    $context = context_module::instance($coursemodule->id);

    // The user does not have the capability to modify this activity.
    if (!has_capability('moodle/course:manageactivities', $context)) {
        return;
    }

    if ($event->eventtype == EVALUATION_EVENT_TYPE_OPEN) {
        // If the event is for the evaluation activity opening then we should
        // set the start time of the evaluation activity to be the new start
        // time of the event.
        if ($evaluation->timeopen != $event->timestart) {
            $evaluation->timeopen = $event->timestart;
            $evaluation->timemodified = time();
            $modified = true;
        }
    } else if ($event->eventtype == EVALUATION_EVENT_TYPE_CLOSE) {
        // If the event is for the evaluation activity closing then we should
        // set the end time of the evaluation activity to be the new start
        // time of the event.
        if ($evaluation->timeclose != $event->timestart) {
            $evaluation->timeclose = $event->timestart;
            $modified = true;
        }
    }

    if ($modified) {
        $evaluation->timemodified = time();
        $DB->update_record('evaluation', $evaluation);
        $event = \core\event\course_module_updated::create_from_cm($coursemodule, $context);
        $event->trigger();
    }
}

/**
 * Is the event visible?
 *
 * This is used to determine global visibility of an event in all places throughout Moodle. For example,
 * the calendar event will be shown only to evaluation participants on their calendar
 *
 * @param calendar_event $event
 * @return bool Returns true if the event is visible to the current user, false otherwise.
 */
function mod_evaluation_core_calendar_is_event_visible(calendar_event $event) {
    global $DB, $USER;
    $evaluation = $DB->get_record('evaluation', ['id' => $event->instance], '*', MUST_EXIST);
    if (!empty($evaluation) and !empty(evaluation_is_user_enrolled($evaluation, $USER->id))) {
        return true;
    }
    return false;
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_evaluation_core_calendar_provide_event_action(calendar_event $event, \core_calendar\action_factory $factory,
        int $userid = 0) {
    global $USER, $DB;
    if (empty($userid)) {
        $userid = $USER->id;
    }
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['evaluation'][$event->instance];

    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }

    $completion = new \completion_info($cm->get_course());

    $completiondata = $completion->get_data($cm, false, $userid);

    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }

    $evaluationcompletion = new mod_evaluation_completion(null, $cm, 0, false, null, null, $userid);

    if (!empty($cm->customdata['timeclose']) && $cm->customdata['timeclose'] < time()) {
        // Evaluation is already closed, do not display it even if it was never submitted.
        return null;
    }

    if (!$evaluationcompletion->can_complete()) {    // The user can't complete the evaluation so there is no action for them.
        return null;
    }

    // The evaluation is actionable if it does not have timeopen or timeopen is in the past.
    $actionable = $evaluationcompletion->is_open();

    if ($actionable && $evaluationcompletion->is_already_submitted(false)) {
        // There is no need to display anything if the user has already submitted the evaluation.
        return null;
    }

    $anker = get_string('answerquestions', 'evaluation');
    //  global Evaluation
    $evaluation = $evaluationcompletion->get_evaluation();
    if ($evaluation->course == SITEID) {    // Don't show if user not participates
        //print "<br><br><br><hr>Course: ".$evaluation->course . " - is enrolled: "
        //.(evaluation_is_user_enrolled($evaluation, $userid ) ?"Ja":"Nein")."<br\n";exit;
        if (empty(evaluation_is_user_enrolled($evaluation, $userid))) {
            return null;
        }
        $anker = get_string('open_evaluation', 'evaluation');
    }

    return $factory->create_instance(
            $anker,
            new \moodle_url('/mod/evaluation/view.php', ['id' => $cm->id]),
            1,
            $actionable
    );
}
