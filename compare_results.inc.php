<?php
// this file is part of Moodle mod_evaluation plugin

/*
 * 
// question presentation constants
define('EVALUATION_MULTICHOICE_TYPE_SEP', '>>>>>');
define('EVALUATION_MULTICHOICE_LINE_SEP', '|');
define('EVALUATION_MULTICHOICE_ADJUST_SEP', '<<<<<');
define('EVALUATION_MULTICHOICE_IGNOREEMPTY', 'i');
define('EVALUATION_MULTICHOICE_HIDENOSELECT', 'h');
*/

// get average results of all answers fore selected items or all course_of_studies, courses and teachers
/**
 * Compares and processes the results of an evaluation, applying filters and user roles.
 *
 * @param int $evaluation The evaluation ID to be analyzed.
 * @param int|false $courseid Optional course ID to filter the results by a specific course.
 * @param int|false $teacherid Optional teacher ID to filter the results by a specific teacher.
 * @param int|false $course_of_studiesID Optional course of studies ID to filter results to specific studies.
 * @param int|false $department Optional department ID to filter results by a specific department.
 * @return void This function does not return a value, but it processes results and prepares data for output.
 */
function evaluation_compare_results($evaluation, $courseid = false,
        $teacherid = false, $course_of_studiesID = false, $department =false) {
    global $DB, $OUTPUT, $USER;
    $a = new stdClass();
    validate_evaluation_sessions($evaluation);
    if (!isset($_SESSION["duplicated"])) {
        $_SESSION["duplicated"] = evaluation_count_duplicated_replies($evaluation);
    }
    $id = get_evaluation_cmid_from_id($evaluation);

    // auto-submit if called by $_GET
    if (!empty($_GET["showCompare"])) {
        ?>
        <form style="display:inline;" id="postForm" method="POST" action="print.php">
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="showCompare" value="1">
            <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
            <input type="hidden" name="teacherid" value="<?php echo $teacherid; ?>">
            <input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID; ?>">
            <input type="hidden" name="department" value="<?php echo $department; ?>">
            <script>document.getElementById("postForm").submit();</script>
        </form>
        <?php
    }
    $minResults = evaluation_min_results($evaluation);
    $minResultsText = min_results_text($evaluation);
    $minResultsPriv = min_results_priv($evaluation);

    // handle CoS privileged user
    $cosPrivileged = evaluation_cosPrivileged($evaluation);
    $cosPrivileged_filter = evaluation_get_cosPrivileged_filter($evaluation);
    // ev_set_privileged_users();
    $privGlobalUser = (is_siteadmin() OR !empty($_SESSION["privileged_global_users"][$USER->username]));
    if ($privGlobalUser) {
        $minResults = $minResultsText = $minResultsPriv;
    }
    $isOpen = evaluation_is_open($evaluation);
    $maxCharts = intval(ev_session_request("maxCharts", 21));
    $allSelected = ev_session_request("allSelected", "");
    $ChartAxis = ev_session_request("ChartAxis", "x");
    $sortOrder = intval(ev_session_request("sortOrder", SORT_ASC));
    $minReplies = intval(ev_session_request("minReplies", $minResults));
    $qSelected = intval(ev_session_request("qSelected", ""));
    $sortKey = ev_session_request("sortKey", "values");
    $validation = intval(ev_session_request("validation", 0));
    $hideInvalid = intval(ev_session_request("hideInvalid", 1));
    $applysubquery = intval(ev_session_request("applysubquery", 0));
    $subqueries = ev_session_request("subqueries", array());
    $showOmitted = intval(ev_session_request("showOmitted", 0));
    $isFilter = ($teacherid or $courseid or $course_of_studiesID or $department);
    if ( !is_siteadmin() AND $minReplies < $minResults ){
        $minReplies = $minResults;
    }
    $isStudent = $isTeacher = false;
    $allSubject = $subquery = $subqueryC = $subquerytxt = $filterDept = "";
    $data = $subqueryids = array();
    $zeroReplies = $invalidReplies = array();
    $evaluatedResults = $evaluationResults = $omittedResults = $omittedSubjects = 0;
    $course_of_studies = false;
    if ($course_of_studiesID) {
        $course_of_studies = evaluation_get_course_of_studies_from_evc($course_of_studiesID, $evaluation);
    }

    if (!isset($_SESSION["participating_courses_of_studies"])) {
        $_SESSION["participating_courses_of_studies"] = 0;
        if (!empty($sg_filter)) {
            $_SESSION["participating_courses_of_studies"] = safeCount($sg_filter);
        }
    }

    $boldStyle = "font-size:12pt;font-weight:bold;display:inline;";
    $buttonstyle = 'font-size:125%;color:white;background-color:black;text-align:center;';
    $goBack = html_writer::tag('button', ev_get_string('back'), array('class' => "d-print-none", 'style' => $buttonstyle,
            'type' => 'button', 'onclick' => 'window.history.back();'));
    $goBack .= "&nbsp;&nbsp;" . html_writer::tag('a', ev_get_string('overview'), array('class' => "d-print-none", 'style' => $buttonstyle,
                    'type' => 'button', 'href' => 'view.php?id=' . $id . '&courseid=' . $courseid . '&teacherid=' . $teacherid
                            . '&course_of_studiesID=' . $course_of_studiesID));
    $goBack .= "&nbsp;&nbsp;" . html_writer::tag('a', ev_get_string('analysis'), array('class' => "d-print-none", 'style' => $buttonstyle,
                    'type' => 'button',
                    'href' => 'analysis_course.php?id=' . $id . '&courseid=' . $courseid . '&teacherid=' . $teacherid
                            . '&course_of_studiesID=' . $course_of_studiesID));

    print $goBack;
    echo evPrintButton();

    $responses = get_string('completed_evaluations', "evaluation");
    $filterSubject = ev_get_string('reset_selection');
    echo '<h1 title="' . ev_get_string('question_hint')
            . '" style="display:inline;color:darkgreen;text-align:left;font-weight:bolder;">'
            . ev_get_string('statistics') . "</h1><br>\n";

    if ($allSelected == "allDepartments") {
        $allSubject = get_string("departments", "evaluation");
    } else if ($allSelected == "allStudies") {
        $allSubject = get_string("courses_of_studies", "evaluation");
    } else if ($allSelected == "allCourses") {
        $allSubject = get_string("courses", "evaluation");
    } else if ($allSelected == "allTeachers") {
        $allSubject = get_string("teachers", "evaluation");
    }

    // validation needs more research
    if (!is_siteadmin()){  //AND !defined('EVALUATION_OWNER') )
        $validation = false;
        $hideInvalid = false;
    }
    // access control
    $myEvaluations = get_evaluation_participants($evaluation, $USER->id);
    // print nl2br("\$myEvaluations" . var_export($myEvaluations, true));

    if (defined('EVALUATION_OWNER')) {
        get_evaluation_filters($evaluation);
        if ($department AND isset($_SESSION['CoS_department']) and safeCount($_SESSION['CoS_department'])) {
            $CoS = "'" . implode("','", array_keys($_SESSION['CoS_department'], $department)) . "'";
            $filterDept = " AND course_of_studies IN($CoS)";
        }
    }else {
        $department = false;
        if ($course_of_studiesID or ($teacherid and $teacherid != $USER->id)
                or ($courseid and !evaluation_is_my_courseid($myEvaluations, $courseid))
        ) {
            print '<br><h2 style="font-weight:bold;color:red;background-color:whitesmoke;">'
                    . get_string('no_permission_analysis', 'evaluation') . "</h2><br>";
            echo $OUTPUT->continue_button("/mod/evaluation/view.php?id=$id");
            echo $OUTPUT->footer();
            evaluation_spinnerJS();
            exit;
        }
        $isStudent = evaluation_is_student($evaluation, $myEvaluations);
        $isTeacher = evaluation_is_teacher($evaluation, $myEvaluations);
    }

    $showteachercourses = ($isTeacher and $allSelected == "allCourses");
    if ($showteachercourses){
        $teacherid = $USER->id;
    }

    $query = "SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id 
				AND (typ='multichoice' OR typ='numeric') AND hasvalue=1 
				AND name NOT ILIKE '%" . get_string("course_of_studies", "evaluation") . "%' 
				ORDER by position ASC";
    $allQuestions = $DB->get_records_sql($query);
    $numAllQuestions = safeCount($allQuestions);
    if (!$numAllQuestions) {
        echo $OUTPUT->notification(ev_get_string('no_questions_for_analysis'));
        echo $OUTPUT->footer();
        flush();
        exit;
    }

    $presentation = array();
    $scheme = $numQuestions = "";
    $stimmezu = array("stimme zu", "stimme eher zu", "stimme eher nicht zu", "stimme nicht zu");
    $trifftzu = array("trifft zu", "trifft eher zu", "trifft eher nicht zu", "trifft nicht zu");
    $schemeQ = "( presentation ilike '%stimme zu%' OR presentation ilike '%trifft zu%'
                  OR (presentation ilike '%hoch%' AND presentation ilike '%niedrig%')
                  OR (presentation ilike '%positiv%' AND presentation ilike '%negativ%')
                  )";

    if ($qSelected) {
        $query = "SELECT * FROM {evaluation_item} WHERE id = $qSelected 
                                    AND evaluation=$evaluation->id ORDER by position ASC";
        $question = array();
        $questions = $DB->get_records_sql($query);
        //extract presentation list
        foreach ($questions as $question) {    //$question = $question1; break; }
            $itemobj = evaluation_get_item_class($question->typ);
            $itemInfo = $itemobj->get_info($question);

            $presentationraw = $presentation =
                    /*explode("|", str_replace(array("<<<<<1", "r>>>>>", "c>>>>>", "d>>>>>", "\n"), "",
                            $question->presentation));*/
                    explode("|", str_replace(array("\t", "\r", "\n", "<<<<<1", "r>>>>>", "c>>>>>", "d>>>>>"),
                            "",
                            $question->presentation));

            // sub queries
            if (isset($_REQUEST['sqfilter']) ) {
                if (intval($_REQUEST['sqfilter']) == 1 and $_REQUEST['subreply']) {
                    $applysubquery = 1;
                    $_SESSION['subqueries'][$qSelected]['item'] = $qSelected;
                    $_SESSION['subqueries'][$qSelected]['name'] = trim($question->name);
                    $_SESSION['subqueries'][$qSelected]['value'] = $_REQUEST['subreply'];
                    $_SESSION['subqueries'][$qSelected]['reply'] = trim($presentationraw[intval($_REQUEST['subreply']) - 1]);
                } else if (intval($_REQUEST['sqfilter']) == 2) {
                    unset($_SESSION['subqueries'][$qSelected]);
                }
            }

            if (in_arrayi(ev_get_string('cant_answer'), $presentation) or in_arrayi(ev_get_string('no_answer'), $presentation) or
                    in_arrayi(ev_get_string('i_dont_know'), $presentation)) {
                array_pop($presentation);
            }
            // $presentationraw = $presentation; // used for subqueries
            $qfValues = "";
            for ($cnt = 1; $cnt <= (safeCount($presentation)); $cnt++) {
                $qfValues .= "'$cnt'" . ($cnt < safeCount($presentation) ? "," : "");
            }
            $scheme = implode(", ", $presentation) . " <=> $qfValues";

            array_unshift($presentation, ($validation ? "ungültig" : "keine Antwort"));
            break;
            //print "<br>qfValues: $qfValues<br>Scheme: $scheme<br>presentation: " . var_export($presentation,true)
            //. "<br>info: " .var_export($info,true) . "<br>" ;
            //print 'Ausgewertete Frage: <span style="' . $boldStyle .'">'	. $question->name . "</span><br>\n";
        }
    } else {
        $query = "SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id 
                    AND (typ like'multichoice%' OR typ='numeric') AND $schemeQ
					ORDER BY position ASC";
        $questions = $DB->get_records_sql($query);
        //print "<br><hr>".var_export($questions,true);exit;
        $numQuestions = safeCount($questions);

        $present = "nope";
        foreach ($questions as $question) {
            $present = $question->presentation;
            $presentationraw = $presentation = explode("|",
                            str_replace(array("\t", "\r", "\n", "<<<<<1", "r>>>>>", "c>>>>>", "d>>>>>"),
                            "",
                            $question->presentation));
            if (in_arrayi(ev_get_string('cant_answer'), $presentation) or in_arrayi(ev_get_string('no_answer'), $presentation) or
                    in_arrayi(ev_get_string('i_dont_know'), $presentation)) {
                array_pop($presentation);
            }
            $scheme = implode(", ", $presentation) . " <=> $qfValues";
            array_unshift($presentation, ($validation ? "ungültig" : "keine Antwort"));
            break;
        }
        if ($numQuestions and stristr($present, "stimme")) {
            $presentation = array_merge(array(($validation ? "ungültig" : "keine Antwort")), $stimmezu);
            //, "stimme zu", "stimme eher zu", "stimme eher nicht zu", "stimme nicht zu" );
            $scheme = '"stimme zu"=1 - "stimme nicht zu"=4<br>';
        }
        else if ($numQuestions and stristr($present, "trifft")) {
            $presentation = array_merge(array(($validation ? "ungültig" : "keine Antwort")), $trifftzu);
            $scheme = '"trifft zu"=1 - "trifft nicht zu"=4<br>';
        }
        $qfValues = "";
        for ($cnt = 1; $cnt <= (safeCount($presentation)); $cnt++) {
            $qfValues .= "'$cnt'" . ($cnt < safeCount($presentation) ? "," : "");
        }
        print ev_get_string('analyzed_sc_questions') . ': <span style="'
                . $boldStyle . '">' . $numQuestions . "</span> - ";
    }

    // access subquery selector only for global priv users
    if (!empty($_SESSION['subqueries'])) {  // $privGlobalUser AND
        $subquerytxt = ev_get_string('filter_on_questions');
        foreach ($_SESSION['subqueries'] as $subqueryid) {
            $subqueryids[] = $subqueryid['item'];
            if ($applysubquery) {
                /*$subquery .= " AND completed IN ((SELECT completed AS done FROM {evaluation_value}
		                        WHERE item=" .$subqueryid['item'] ." and value='".$subqueryid['value']."'))";
                // not working...
                $subquery .= " AND EXISTS (SELECT completed AS done FROM {evaluation_value}
		                        WHERE item=" .$subqueryid['item'] ." and value='".$subqueryid['value']."')";
                */
                $subquery .= " AND completed IN ((SELECT completed AS done FROM {evaluation_value}
		                        WHERE item=" . $subqueryid['item'] . " and value='" . $subqueryid['value'] . "'))";
                $subqueryC .= str_ireplace("AND completed", "AND id", $subquery);
            }
            $subquerytxt .= " '" . $subqueryid['name'] . "' " . ev_get_string('with_reply') . ": '" . $subqueryid['reply'] . "', ";
        }
        $subquerytxt = substr($subquerytxt, 0, -2);
        // print "subqueries: ".nl2br(var_dump($_SESSION['subqueries'], true));
        // print "subquery: " . $subquery;
    }

    $numAnswers = safeCount($presentation);
    echo "<b>" . ev_get_string('reply_scheme') . "</b>: $scheme<br>\n";
    //echo "<b>\$presentation ".var_export($presentation,true) . "</b><br>\n";

    $buttonStyle = 'margin: 3px 5px;font-weight:bold;color:white;background-color:teal;';
    $selectStyle = 'margin: 3px 5px;font-weight:bolder;color:white;background-color:darkblue;';
    ?>
    <div style="display:block;">
        <form style="display:inline;" id="statsForm" method="POST" action="print.php">
            <?php

            if (is_siteadmin() AND !$qSelected){
                $label = ($validation ? "V" : "Nicht V") . "alidiert";
                $value = ($validation ? 0 : 1);
                ?>
                <button name="validation" style="<?php echo $buttonStyle; ?>" value="<?php echo $value; ?>"
                        onclick="this.form.submit();">
                <?php
                echo '<span title="Abgaben werden auf Plausibilität geprüft. Abgaben, bei denen immer die erste Antwort gewählt wurde, werden als ungültig markiert!">'
                        . $label . '</span>';
                echo "</button>\n";
                if ($validation) {
                    $label = "Invalide " . ($hideInvalid ? "verbergen" : "anzeigen");
                    $value = ($hideInvalid ? 0 : 1);
                    ?>
                    <button name="hideInvalid" style="<?php echo $buttonStyle; ?>" value="<?php echo $value; ?>"
                            onclick="this.form.submit();">
                    <?php
                    echo '<span title="Es werden nur ' . ($hideInvalid ? "validierte" : "ungültige")
                            . ' Auswertungen angezeigt">' . $label . '</span>'
                            . "</button>\n";

                }
            }

            if ($allSelected and $allSelected !== "useFilter"){
                $label = ($sortOrder == SORT_ASC ? "up" : "down");
                $value = ($sortOrder == SORT_DESC ? SORT_ASC : SORT_DESC);
                ?>
                <button name="sortOrder" style="<?php echo $buttonStyle; ?>" value="<?php echo $value; ?>"
                        onclick="this.form.submit();"><?php
                    echo '<span style="width:21px;color:white;" class="fa fa-arrow-' . $label . ' fa-1x" title="'
                    . ev_get_string('change_sort_up_down') . '"></span>'; ?>
                </button>
                <?php
                $label = ev_get_string(($sortKey == "replies") ? "submissions" : "average");
                $value = ($sortKey == "values" ? "replies" : "values");
                ?>
                <button name="sortKey" style="<?php echo $buttonStyle; ?>" value="<?php echo $value; ?>"
                        onclick="this.form.submit();">
                <?php
                echo '<span title="' . ev_get_string('change_sort_by') . '">' . $label . '</span></button>';

            }
        if (!$qSelected){  //AND ($allSelected AND $allSelected !== "allCourses" AND $allSelected !== "allTeachers" ) )
            print '<div style="display:inline;" id="showGraf" title="' . ev_get_string('click_for_graphics') . '"><b>'
                . ev_get_string('graphics') . "</b>: </div>\n";
            $label = ev_get_string(($ChartAxis == "x" )? "horizontal" : "vertical");
            $value = ($ChartAxis == "x" ? "y" : "x");
            ?>
            <button name="ChartAxis" style="<?php echo $buttonStyle; ?>" value="<?php echo $value; ?>"
                    onclick="this.form.submit();"><?php
                echo $label; ?></button>

            <?php
            if (defined('EVALUATION_OWNER') or is_siteadmin()) {
                print '<input type="number" name="maxCharts" value="' . $maxCharts
                        . '" style="width:42px;font-size:100%;color:white;background-color:teal;" min="3" ondblclick="this.form.submit();" title="'
                        . ev_get_string('maxgraphs') . '">';
            }

        }
        if (($isTeacher or $isStudent) or defined('EVALUATION_OWNER')){
            print $isFilter ? "" : "- alle: ";

            if ($privGlobalUser AND $_SESSION["participating_courses_of_studies"]>1
            ) {
                if ($allSelected == "allDepartments") {
                    $style = $selectStyle;
                    $value = "";
                } else {
                    $style = $buttonStyle;
                    $value = "allDepartments";
                }
                ?>
                <button name="allSelected" style="<?php echo $style; ?>" value="<?php
                    echo $value; ?>" onclick="this.form.submit();"><?php
                    echo get_string("departments", "evaluation");
                    ?></button>
                <?php
            }

            if ($allSelected == "allStudies") {
                $style = $selectStyle;
                $value = "";
            } else {
                $style = $buttonStyle;
                $value = "allStudies";
            }
            ?>
            <button name="allSelected" style="<?php echo $style; ?>" value="<?php
            echo $value; ?>" onclick="this.form.submit();"><?php
                echo get_string("courses_of_studies", "evaluation"); ?></button>

            <?php
            //if (defined('EVALUATION_OWNER')){
            if ($allSelected == "allCourses") {
                $style = $selectStyle;
                $value = "";
            } else {
                $style = $buttonStyle;
                $value = "allCourses";
            }

            ?>
            <button name="allSelected" style="<?php
                    echo $style; ?>" value="<?php
                    echo $value; ?>" onclick="this.form.submit();">
            <?php
            echo get_string("courses", "evaluation");
            echo "</button>";

            if ($isStudent or (defined('EVALUATION_OWNER'))){ // AND !isset($_SESSION['CoS_privileged_sgl'][$USER->username]))) { // ($isTeacher and $teacherid)
                if ($allSelected == "allTeachers") {
                    $style = $selectStyle;
                    $value = "";
                } else {
                    $style = $buttonStyle;
                    $value = "allTeachers";
                }
                print '<button name="allSelected" style="'. $style .'" value="' . $value .'" onclick="this.form.submit();">';
                echo get_string("teachers", "evaluation") . "</button>\n";
            }
            $a->allSubject = $allSubject;
            $a->minReplies = $minReplies;
            print '<span title="' . ev_get_string('no_minreplies_no_show',$a) . '">';
            if (($allSelected == "allCourses" or $allSelected == "allTeachers")) {
                print  ev_get_string('with_minimum');
                print ' <input type="number" name="minReplies" value="' . $minReplies . '"
                    style="width:42px;font-size:100%;color:white;background-color:teal;" 
                    ondblclick="this.form.submit();"
                    min="'.($privGlobalUser?1:$minResults).'"> ';
                print ev_get_string('submissions');
                // show or hide lines < minReplies
                print '<button name="showOmitted" style="' . $buttonStyle . '" value="' . ($showOmitted ?0 :1)
                        . '" title="' . ev_get_string('toggle_by_minreplies',$a) . '"' . ' onclick="this.form.submit();">';
                print ev_get_string(($showOmitted ?"show" :"hide")) . "</button>\n";
            }
            print "</span>";
            print "\n<br>";

            // start of snippet duplicated in analysis_course.php
            if ($qSelected) {
                print "<b>" . ev_get_string('evaluated_question') . "</b>: ";
            }
            $a->numQuestions = $numQuestions;
            print '<select name="qSelected" style="' . $buttonStyle . '" onchange="this.form.submit();">'
                  . "\n" . '<option value="">' . ev_get_string('all_numquestions',$a) . "</option>\n";
            foreach ($allQuestions as $question) {
                $selected = "";
                if ($question->id == $qSelected) {
                    $selected = ' selected="' . $selected . '" ';
                }
                if ($isStudent AND stristr($question->name,"Geschlecht")){
                    continue;
                }
                $qname = $question->name;
                if (strlen($qname) > 90) {
                    $qname = substr($qname, 0, 87) . "...";
                }
                print '<option value="' . $question->id . '"' . $selected
                        . ' title="' . htmlentities($question->name) . '">' . $qname
                        . "</option>\n";
            }
            print "</select>\n";
            if ($qSelected) {
                if (defined('EVALUATION_OWNER')) {
                    $a->action = ev_get_string(in_array($qSelected, $subqueryids) ? "remove" : "apply");
                    $value = in_array($qSelected, $subqueryids) ? "2" : "1";
                    $label = ev_get_string('filter_action',$a);
                    ?>
                    <button name="sqfilter" style="<?php echo $style; ?>" value="<?php echo $value; ?>"
                            onclick="this.form.submit();"><?php
                        echo $label; ?></button>
                    <?php
                    if ($value == 1) {
                        print '<span id="replies">';
                        $cnt = 1;
                        // $hide_reply = array("k.b.", "keine Angabe", "Kann ich nicht beantworten");
                        $hide_reply = array();
                        foreach ($presentationraw as $reply) {
                            if ( !in_arrayi($reply, $hide_reply)) {
                                print '<label>';
                                print '<input type="radio" name="subreply" value="' . $cnt . '">';
                                print "$reply&nbsp;</label>";
                            }
                            $cnt++;
                        }
                        print "</span>\n";
                    }
                }
                if ($itemInfo->subtype == 'c') {
                    print '<br><span style="color:blue;">' . ev_get_string('this_is_a_multichoice_question') . "</span><br>\n";
                }
            }
            // subqueries
            if (!empty($_SESSION['subqueries'])) { // $privGlobalUser AND
                print "<br>\n" . ev_get_string('apply_filter') . ":&nbsp;";
                print '<label><input type="radio" name="applysubquery" ' . ($applysubquery ? "checked" : "")
                        . ' value="1">' . get_string('yes') .'</label>&nbsp;
                <label><input type="radio" name="applysubquery" ' . (!$applysubquery ? "checked" : "")
                        . 'value="0">' . get_string('no') .'</label>';

                // results for subqueries
                if ($subquerytxt) {
                    print '<span style="font-weight:normal;color:blue;"> - ' . $subquerytxt . "</span>";
                } else {
                    print "&nbsp;&nbsp;";
                }
            }
            // end of snippet duplicated in analysis_course.php
            } // if isTeacher OR isStudent or Owner
            ?>
            <input type="hidden" name="id" value="<?php echo $id; ?>">
            <input type="hidden" name="showCompare" value="1">
            <input type="hidden" name="courseid" value="<?php echo $courseid; ?>">
            <input type="hidden" name="teacherid" value="<?php echo $teacherid; ?>">
            <input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID; ?>">
            <input type="hidden" name="department" value="<?php echo $department; ?>">
        </form>
    </div>


    <?php

    $completed_responses = evaluation_countCourseEvaluations($evaluation);
    if (!$completed_responses) {
        echo $OUTPUT->notification(get_string('no_responses_yet', 'mod_evaluation'));
        echo $OUTPUT->footer();
        flush();
        exit;
    }


    // show if CoS privileged filter applied for user
    if (!empty($_SESSION['CoS_privileged'][$USER->username]) AND empty($teacherid)) {
        print  '<span style="font-weight:600;">' . ev_get_string('analysis_cos') . ": " . '<span style="white-space:pre-line;">'
                . implode(", ", $_SESSION['CoS_privileged'][$USER->username]) . "</span></span><br>\n";
    }


    $filter = $allKey = $allKeyV = "";
    $numTeachers = 0;
    $allIDs = $allValues = $allCounts = $allResults = $fTitle = $allCosIDs = $sortArray = array();
    if ($teacherid) {
        $filter .= " AND teacherid=" . $teacherid;
        $teacher = evaluation_get_user_field($teacherid, 'fullname');
        $fTitle[] = get_string("teacher", "evaluation") . ": $teacher";
        $anker = get_string("teacher", "evaluation") . ': <span style="font-size:12pt;font-weight:bold;">'
                . $teacher . "</span>";
        // if ($isStudent OR defined('EVALUATION_OWNER')) {
        if (!($isTeacher and $allSelected == "allCourses")){
            print '<a href="print.php?id=' . $id . '&showTeacher=' . $teacherid . '" target="teacher">' . $anker . '</a>';
            print ' (<a href="print.php?showCompare=1&allSelected=' . $allSelected . '&id='
                    . $id . '&courseid=' . $courseid
                    . '&course_of_studiesID=' . $course_of_studiesID
                    . '&department=' .$department . '">' . ev_get_string('remove_filter') . '</a>)';
        } else {
            print $anker;
        }
        print "<br>\n";
    }
    if ($courseid) {
        $filter .= " AND courseid=" . $courseid;
        $course = $DB->get_record('course', array('id' => $courseid), '*'); //$course = get_course($courseid);
        $fTitle[] = get_string("course", "evaluation") . ": $course->fullname";
        evaluation_get_course_teachers($courseid);
        $numTeachers = safeCount($_SESSION["allteachers"][$courseid]);
        $Studiengang = evaluation_get_course_of_studies($courseid, true);  // get Studiengang with link
        $semester = evaluation_get_course_of_studies($courseid, true, true);  // get Semester with link
        if (!empty($Studiengang)) {
            $Studiengang = get_string("course_of_studies", "evaluation") .
                    ": <span style=\"font-size:12pt;font-weight:bold;display:inline;\">"
                    . $Studiengang . (empty($semester) ? "" : " <span style=\"font-size:10pt;font-weight:normal;\">("
                            . $semester . ")</span>") . "</span><br>\n";
        }
        $anker = get_string("course", "evaluation") . ': <span style="font-size:12pt;font-weight:bold;display:inline;">'
                . $course->fullname . " ($course->shortname)</span>\n";
        print $Studiengang . '<a href="analysis_course.php?id=' . $id . '&courseid=' . $courseid
                . '" target="course">' . $anker .
                '</a>';

        // option to remove filter
        print ' (<a href="print.php?showCompare=1&allSelected=' . $allSelected . '&id='
                . $id . '&teacherid=' . $teacherid
                . '&course_of_studiesID=' . $course_of_studiesID
                . '&department=' .$department . '">' . ev_get_string('remove_filter') . '</a>)';
        $msg = ($numTeachers > 1) ? " (" .ev_get_string('team_teaching') . ")"
                : " (" .ev_get_string('single_submission_per_course') . ")";
        if (defined("showTeachers")) {
            echo showTeachers . $msg . "<br>\n";
        } else {
            $msg = "<br>- " . ev_get_string('this_course_has_numteachers') . " "
                    . get_string("teacher" . ($numTeachers > 1 ? "s" : ""), "evaluation") .
                    $msg;
            print '<span style="font-size:12pt;font-weight:normal;display:inline;">'
                    . $msg . "</span><br>\n";

        }

    }
    if ($course_of_studies) {
        $filter .= " AND course_of_studies='" . $course_of_studies . "'";
        $fTitle[] = get_string("course_of_studies", "evaluation") . ":  $course_of_studies";
        $anker = get_string("course_of_studies", "evaluation") . ': <span style="font-size:12pt;font-weight:bold;display:inline;">'
                . $course_of_studies . "</span>\n";
        print '<a href="analysis_course.php?id=' . $id . '&course_of_studiesID=' . $course_of_studiesID
                . '" target="course_of_studies">' . $anker . '</a>';
        // if (defined('EVALUATION_OWNER')) {
            print ' (<a href="print.php?showCompare=1&allSelected=' . $allSelected . '&id='
                    . $id . '&teacherid=' . $teacherid
                    . '&courseid=' . $courseid
                    . '&department=' .$department . '">' . ev_get_string('remove_filter') . '</a>)';
        // }
        print "<br>\n";
    }
    if ($department  AND !empty($filterDept)){
        $filter .= $filterDept;
        $fTitle[] = get_string("department", "evaluation") . ":  $department";
        $anker = get_string("department", "evaluation") .
                ': <span style="font-size:12pt;font-weight:bold;display:inline;">'
                . $department . "</span>\n";
        print '<a href="analysis_course.php?id=' . $id . '&department=' . $department
                . '" target="department">' . $anker . '</a>';
        // if (defined('EVALUATION_OWNER')) {
            print ' (<a href="print.php?showCompare=1&allSelected=' . $allSelected . '&id='
                    . $id . '&teacherid=' . $teacherid
                    . '&courseid=' . $courseid
                    . '&course_of_studiesID=' . $course_of_studiesID . '">' . ev_get_string('remove_filter') . '</a>)';
        // }
        print "<br>\n";;
    }


    $numresultsF =
            safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} 
                WHERE evaluation=$evaluation->id $filter $subqueryC"));
    if ($filter and $numresultsF < $minReplies) {
        $a->ftitle = implode(", ", $fTitle);
        print ev_get_string('less_minreplies',$a) . "<br>\n"
                . (is_siteadmin() ? ev_get_string('except_siteadmin') : "") . "<br>\n";
    }
    //handle CoS priv users
    $setFilter = $filter;
    if (!$teacherid) {
        $filter .= $cosPrivileged_filter;
    }
    // subquery needs filter
    if ($isFilter and !empty($subquery)) {
        $subquery = str_ireplace("))", " $filter))", $subquery);
        $subqueryC = str_ireplace("))", " $filter))", $subqueryC);
    }

    $numresultsF =
            safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} 
                WHERE evaluation=$evaluation->id $filter $subqueryC"));

    if ($allSelected == "allDepartments"  ) {
        $allKey = "course_of_studies";
        $allKeyV = "course_of_studies";
        $aFilter = "course_of_studies <>''";
        // $evaluationResults = safeCount($_SESSION['CoS_department']);
        $departments = array();
        foreach ($_SESSION['CoS_department'] AS $CoS => $department){
            $departments[$CoS] =  $department;
        }
        $evaluationResults = safeCount($departments);
        $allResults = $DB->get_records_sql("SELECT course_of_studies, count(*) AS count 
											 FROM {evaluation_completed} 
											 WHERE evaluation=$evaluation->id $filter $subqueryC
											 GROUP BY course_of_studies ORDER BY course_of_studies");
        $evaluatedResults = 0;
        foreach ($allResults as $allResult) {
            // array_keys($_SESSION['CoS_department'], $department)
            $dept = $_SESSION['CoS_department'][$allResult->course_of_studies];
            if ($dept) {
                $allIDs[$dept] = $allValues[$dept] = $dept;
                if (defined('EVALUATION_OWNER') && empty($_SESSION['CoS_privileged_sgl'][$USER->username]) ) {
                    $links = '<a href="analysis_course.php?id=' . $id
                            . '&department='
                            . $dept
                            . '" target="analysis">' . $dept . "</a>";
                } else {
                    $links = $dept;
                }
                $allLinks[$dept] = $links;
                $Counts = $allResult->count;
                $allCounts[$dept] += $Counts;
                $sortArray[$dept] = array("allIDs" => $dept, "allValues" => $dept,
                        "allLinks" => $links, "allCounts" => $Counts);
                $evaluatedResults++;
                if ( $Counts < $minReplies) {
                    $omittedResults += $Counts;
                    // $omittedSubjects ++;
                }
            } else{
                $omittedResults += $allResult->count;
                // $omittedSubjects ++;
            }
        }
        $evaluatedResults = safeCount($allCounts);
    } else if ($allSelected == "allStudies") {
        $allKey = "course_of_studiesID";
        $allKeyV = "course_of_studies";
        $aFilter = "course_of_studies <>''"; // . $cosPrivileged_filter;
        $evaluationResults = safeCount($DB->get_records_sql("SELECT course_of_studies, count(*) AS count 
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND $aFilter $subqueryC
											 GROUP BY course_of_studies ORDER BY course_of_studies"));
        $allResults = $DB->get_records_sql("SELECT course_of_studies, count(*) AS count 
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id $setFilter $subqueryC
											 GROUP BY course_of_studies ORDER BY course_of_studies");
        $evaluatedResults = 0;
        foreach ($allResults as $allResult) {
            if (empty($allResult->course_of_studies)){
                continue;
            }
            $allIDs[] = $allValues[] = $allResult->course_of_studies;
            $course_of_studiesID =
                    evaluation_get_course_of_studies_id_from_evc($id, $allResult->course_of_studies, $evaluation);
            $allCosIDs[] = $course_of_studiesID;
            if (defined('EVALUATION_OWNER') &&
                    (empty($_SESSION['CoS_privileged_sgl'][$USER->username][$allResult->course_of_studies]) ?false :empty($teacherid))) {
                $links = '<a href="analysis_course.php?id=' . $id .
                        '&course_of_studiesID='
                        . $course_of_studiesID
                        . '" target="analysis"><span style="color:navy;font-weight:bold;">' . $allResult->course_of_studies . "</span></a>";
            } else {
                $links = $allResult->course_of_studies;
            }
            $allLinks[] = $links;
            $Counts = $allResult->count;
            $allCounts[$allResult->course_of_studies] = $Counts;
            $sortArray[] = array("allIDs" => $allResult->course_of_studies, "allValues" => $allResult->course_of_studies,
                    "allLinks" => $links, "allCounts" => $Counts);
            $evaluatedResults++;

            if ( $Counts < $minReplies) {
                $omittedResults += $Counts;
                $omittedSubjects ++;
            }
        }
    } else if ($allSelected == "allCourses") {
        $allKey = "courseid";
        $allKeyV = "courseid";
        $aFilter = "courseid >0";
        $evaluationResults = safeCount($DB->get_records_sql("SELECT courseid AS courseid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND $aFilter $subqueryC
											 GROUP BY courseid ORDER BY courseid"));
        $allResults = $DB->get_records_sql("SELECT courseid AS courseid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id $filter $subqueryC
											 GROUP BY courseid ORDER BY courseid");
        $evaluatedResults = 0;
        foreach ($allResults as $allResult) {
            $fullname = "";
            $isCourseStudent = evaluation_is_student($evaluation, $myEvaluations, $allResult->courseid);
            // if ((!defined('EVALUATION_OWNER') && (empty($_SESSION['CoS_privileged_sgl'][$USER->username]) ?true :empty($teacherid)) )
            //if (!$privGlobalUser AND (!empty($teacherid) OR $isCourseStudent)){
            if (!$privGlobalUser AND empty($_SESSION['CoS_privileged'][$USER->username])){
                if ($isCourseStudent and !evaluation_has_user_participated($evaluation, $USER->id, $allResult->courseid)) {
                    continue;
                }
                if (!evaluation_is_teacher($evaluation, $myEvaluations, $allResult->courseid) and !$isCourseStudent) {
                    continue;
                }
            }
            if ($allResult->courseid==SITEID OR empty($allResult->courseid)){
                continue;
            }

            if ($isOpen) {
                $fullname = evaluation_get_course_field($allResult->courseid, 'fullname');
            }else{
                if (!$uRecord = $DB->get_record("evaluation_enrolments",array("courseid" => $allResult->courseid), '*')){
                    continue;
                }
                $fullname = $uRecord->fullname;
            }

            if (empty($fullname)) {
                continue;
            }

            if ((defined('EVALUATION_OWNER') &&
                            (empty($_SESSION['CoS_privileged_sgl'][$USER->username]) ?true :empty($teacherid)) )
                    || evaluation_is_teacher($evaluation, $myEvaluations, $allResult->courseid)
                    || $isCourseStudent) {
                $links = '<a href="analysis_course.php?id=' . $id . '&courseid=' . $allResult->courseid
                        . '" title="' . $fullname . '" target="analysis">'
                        . (strlen($fullname) > 120 ? substr($fullname, 0, 120) . "..." : $fullname) . "</a>";
            } else {
                $links = $fullname;
            }
            $allLinks[] = $links;
            $allIDs[] = $allResult->courseid;
            $allValues[] = $fullname;
            $Counts = $allResult->count;
            $allCounts[$fullname] = $Counts;
            $sortArray[] = array("allIDs" => $allResult->courseid, "allValues" => $fullname, "allLinks" => $links,
                    "allCounts" => $Counts);
            $evaluatedResults++;
            if ( $Counts < $minReplies) {
                $omittedResults += $Counts;
                $omittedSubjects ++;
            }
        }
    } else if ($allSelected == "allTeachers") {
        $allKey = "teacherid";
        $allKeyV = "teacherid";
        $aFilter = "teacherid >0"; // .$cosPrivileged_filter;
        $evaluationResults = safeCount($DB->get_records_sql("SELECT teacherid AS teacherid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND $aFilter $subqueryC
											 GROUP BY teacherid ORDER BY teacherid"));
        $allResults = $DB->get_records_sql("SELECT teacherid AS teacherid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id $filter $subqueryC
											 GROUP BY teacherid ORDER BY teacherid");
        $evaluatedResults = 0;
        foreach ($allResults as $allResult) {
            $isMyTeacher = true;

            if (!defined('EVALUATION_OWNER')){
                if ($isStudent) {
                    $isMyTeacher = evaluation_is_student($evaluation, $myEvaluations, false, $allResult->teacherid);
                    if (!evaluation_has_user_participated($evaluation, $USER->id, false, $allResult->teacherid)) {
                        continue;
                    }
                }
                if (!$isMyTeacher) {
                    continue;
                }
            }
            if( empty($allResult->teacherid)){
                continue;
            }
            if ($isOpen) {
                $fullname = evaluation_get_user_field($allResult->teacherid, 'fullname');
            }else{
                if (!$uRecord = $DB->get_record("evaluation_users",array("userid" => $allResult->teacherid), '*')){
                    continue;
                }
                $fullname = ($uRecord->alternatename ? $uRecord->alternatename : $uRecord->firstname) . " " . $uRecord->lastname;
            }
            if (isset($_SESSION['CoS_privileged_sgl'][$USER->username]) AND $USER->id != $allResult->teacherid){
                continue;
            }
            if ($USER->id == $allResult->teacherid
                    || (defined('EVALUATION_OWNER') &&
                            (empty($_SESSION['CoS_privileged_sgl'][$USER->username]) ?true :empty($teacherid)) )) {
                $links = '<a href="print.php?id=' . $id . '&showTeacher=' . $allResult->teacherid
                        . '" target="analysis">' . $fullname . "</a>";
            } else {
                $links = $fullname;
            }
            if (empty($fullname)) {
                continue;
                // $fullname = '<b style="color:red;">Es gibt kein ASH Konto mehr für einen Lehrenden mit der User-ID' . $allResult->teacherid . '!</b>';
                // $links = $fullname;
            }
            $allLinks[] = $links;
            $allIDs[] = $allResult->teacherid;
            $allValues[] = $fullname;
            $Counts = $allResult->count;
            $allCounts[$fullname] = $Counts;
            $sortArray[] = array("allIDs" => $allResult->teacherid, "allValues" => $fullname, "allLinks" => $links,
                    "allCounts" => $Counts);
            $evaluatedResults++;
            if ( $Counts < $minReplies) {
                $omittedResults += $Counts;
                $omittedSubjects ++;
            }
        }
    }

    /*print 	"<br>allResults=".nl2br(substr(var_export( $allResults[0], true),0,210))	 .
    "<br>allIDs=".nl2br(substr(var_export( $allIDs[0], true),0,150))	 .
    "<br>allValues=".nl2br(substr(var_export( $allValues[0], true),0,150))	 . "<br>\n";*/
    if ($courseid) {    //$divisor = ($evaluation->teamteaching AND !$teacherid )?$numTeachers :1 ;
        //$numTeachers = safeCount($_SESSION["allteachers"][$courseid]);
        $divisor = (!$teacherid) ? $numTeachers :1;
        $students = get_evaluation_participants($evaluation, false, $courseid, false, true);
        $participated = $completed = 0;

        foreach ($students as $participant) {
            if (evaluation_has_user_participated($evaluation, $participant["id"], $courseid)) {
                $participated++;
            }
            if (true) //$evaluation->teamteaching )
            {
                if (isEvaluationCompleted($evaluation, $courseid, $participant["id"])) {
                    $completed++;
                }
            }
            //print "Reminder: ".$participant["reminder"] . "<br>";
            //if ( $participant["reminder"] == trim( get_string("analysis","evaluation") ) )	{	$participated++; }
        }
        $numStudents = safeCount($students);
        if (!$isOpen) {
            $numStudents = evaluation_count_students($evaluation, $courseid);
        }
        print '<span style="font-size:12pt;font-weight:normal;">';
        if ($numStudents) {
            $numToDo = $numStudents * $divisor;
            $a->evaluated = round(($participated / $numStudents) * 100, 1) . "%";
            $a->numTeachers = $numTeachers . " " . ev_get_string(($numTeachers > 1) ? "teachers" : "teacher");
            $a->numStudents = $numStudents . " " . ev_get_string(($numStudents > 1) ? "students" : "student");
            $a->participated = $participated;
            print ev_get_string('course_participants_info',$a) . "<br>\n";
            if ($numTeachers > 1) {
                $a->completed = round(($completed / $numStudents) * 100, 1) . "%";
                print ev_get_string('completed_for_all_teachers',$a);
                if (!empty($teacherid)) {
                    $a->completed = round(($numresultsF / $numStudents) * 100, 1) . "%";
                    print "<br>" . ev_get_string('completed_for_this_teacher',$a);
                }
            }
            $a->quote = round(($numresultsF / $numToDo) * 100, 1) . "%";
            // print ev_get_string('completed_for_this_teacher',$a);
            $a->numresultsF = evaluation_number_format($numresultsF);
            $a->numToDo = evaluation_number_format($numToDo);
            print ev_get_string('submissions_for_course',$a);
        } else {
            print ev_get_string('course_has_no_students');
        }
        echo "</span><br>\n";
    }

    if ($allKey) {
        $hint = "";
        if ($allSelected == "allTeachers" and !$evaluation->teamteaching) {
            $hint = "<br>\n<small>" . ev_get_string('no_teamteaching_all_same') . "</small><br>\n";
        }
        print ev_get_string('analyzed') . " "
                . '<span style="font-size:12pt;font-weight:bold;display:inline;">' . $allSubject . ': ' . $evaluatedResults
                . ($evaluatedResults == $evaluationResults
                        ? "" : " " . ev_get_string('of_total') . " " . $evaluationResults) . $hint . "</span><br>\n";
    }

    $numresults = safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} WHERE evaluation=$evaluation->id"));
    $a->duplicated = evaluation_number_format($_SESSION["duplicated"]);
    print '<style> table, th, td { border:1px solid black;} th, td { padding:5px; text-align:right; vertical-align:bottom;}</style>';
    print '<table id="chartResultsTable" style="border-collapse:collapse;margin: 5px 30px;font-size:12pt;font-weight:normal;">';
    print   "\n" . '<tr style="font-weight:bold;background-color:lightgrey;">'
            . "\n" . '<th colspan="2" style="text-align:left">' . ev_get_string('submissions')
            . ($_SESSION["duplicated"] ? " <small>(" . ev_get_string('incl_duplicated',$a) . ")</small>"
                    : "") . '</th>
			<th colspan="2">' . ev_get_string('average') . "</th>\n</tr>\n";
    print  '<tr><td style="text-align:left;">' . ev_get_string('all_submissions') . ":" . '</td>
				<td>' . evaluation_number_format($numresults) . '</td>
				<td style="text-align:left;"><span id="totalPresentation"></span></td>
				<td><span id="totalAvg"></span></td></tr>' . "\n";
    $title = "";
    if ($filter) {
        if (empty($fTitle) and $cosPrivileged_filter) {
            $fTitle[] = ev_get_string('permitted_cos');
        }
        $numresultsF =
                safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} 
                WHERE evaluation=$evaluation->id $filter"));

        $title = implode("<br>\n", $fTitle);
        print '<tr id="showFilter" style="display:table-row;"><td style="text-align:left;">'
                . ev_get_string('all_submissions') . " " . get_string('for') . ": " . $title .
                '</td>'
                . '<td>' . $numresultsF . '</td>'
                . '<td style="text-align:left;"><span id="filterPresentation"></span></td>'
                . '<td><span id="filterAvg"></span></td></tr>'
                . "\n";
    }
    if ($subquery) {
        $numresultsSq =
                safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} 
                WHERE evaluation=$evaluation->id $filter $subqueryC"));
        $a->ftitle = (!empty($title) ? " " . get_string('for') . " " . $title : "");
        $sqTitle = ev_get_string('all_filtered_submissions',$a);
        print '<tr id="showSqFilter" style="display:table-row;"><td style="text-align:left;">'
                . '<span title="' . htmlentities($subquerytxt) . '"><b>' . $sqTitle . '</b></span></td>'
                . '<td>' . $numresultsSq . '</td>'
                . '<td style="text-align:left;"><span id="SqPresentation"></span></td>'
                . '<td><span id="SqAvg"></span></td></tr>'
                . "\n";
    }

    if ($omittedResults) {
        $button = '';
        $percentage = evaluation_calc_perc($omittedResults, ($filter ? $numresultsF : $numresults));
        print  '<tr><td style="text-align:left;">' . ev_get_string('all_submissions') . " < "
        . $minReplies . $percentage . '</td>
				<td>' . $omittedResults . '</td>
				<td style="text-align:left;"><span id="omittedResult"></span></td>
				<td><span id="omittedAvg"></span></td></tr>' . "\n";
    }
    if ($omittedSubjects){
        $a->percentage = evaluation_calc_perc($omittedSubjects,$evaluatedResults);
        $a->minReplies = $minReplies;
        $a->allSubject = $allSubject;
        print  '<tr><td style="text-align:left;">'
                . ev_get_string('omitted_submissions',$a)
                . ": " . $omittedSubjects . "/" . $evaluatedResults 
        . '</td>
				<td>&nbsp;</td>
				<td style="text-align:left;"><span id="omittedResult"></span></td>
				<td><span id="omittedAvg"></span></td></tr>' . "\n";
    }

    /* print "<hr>\$qfValues: $qfValues -\$scheme: $scheme - \$schemeQ: $schemeQ\n\$presentation: "
        .implode(", ",$presentation)."<hr>"; */
    print '</table><div style="display:block;" id="chartResultsList"></div>' . "\n";




    // Question Loop
    $qCount = $validCount = $validFCount = $maxval = 0;
    $minval = $filterValid = $allKeyValid = 1;
    $allKeyValidCount = array();
    foreach ($questions as $question) {
        $YesNo = stripos(strtolower($question->presentation), "nein") !== false; // yes/no handling
        if ($qSelected) {
            if ($question->id != $qSelected) {
                continue;
            } //print "<br>$question->id!==$qSelected";
            // $fValues = $qfValues;
        } /*else {
            $fValues = "'1','2'" . ($YesNo ? "" : ",'3','4'");
        }*/
        if ($validation) {
            $query = "SELECT count (*) as count FROM {evaluation_value} 
				  WHERE item=$question->id AND coalesce(value, '') = ''";
            $zeroReplies[$question->name][$qCount] = $DB->get_record_sql($query)->count;
            $query = "SELECT count (*) as count FROM {evaluation_value} 
				  WHERE item=$question->id AND value NOT IN ($qfValues) ";
            $ignoredReplies[$question->name][$qCount] = $DB->get_record_sql($query)->count;
        }
        $query = "SELECT AVG (value::INTEGER)::NUMERIC(10,2) as average FROM {evaluation_value} 
				  WHERE item=$question->id AND value IN ($qfValues) ";
        $answer = $DB->get_record_sql($query);
        if (empty($answer)) {
            continue;
        }
        $validCount++;
        $average = round($answer->average, 2);
        $minval = min($minval, $average);
        $maxval = max($maxval, $average);

        if ($YesNo) {
            $hint = "Ja/Nein (1-2)";
        } else {
            $hint = $presentation[max(0, round($average))];
        }
        $data['average'][$qCount] = $average;
        $data['labels'][$qCount] = $question->name;
        $data['average_presentation'][$qCount] = $hint;
        $data['average_labels'][$qCount] = $hint . " ($average)";
        if ($allKeyV) {
            if ($validation) {
                $query = "SELECT $allKeyV AS $allKeyV, COUNT(*) as count FROM {evaluation_value} 
							WHERE item=$question->id AND coalesce(value, '') = '' $subquery
							GROUP BY $allKeyV ORDER BY $allKeyV";
                $_zeroReplies[$qCount] = $DB->get_records_sql($query);
                $query = "SELECT $allKeyV AS $allKeyV, COUNT(*) as count FROM {evaluation_value} 
							WHERE item=$question->id AND value NOT IN ($qfValues) $subquery
							GROUP BY $allKeyV ORDER BY $allKeyV";
                $_ignoredReplies[$qCount] = $DB->get_records_sql($query);
            }
            if ($allSelected == "allDepartments" && !$isOpen) {
                $query = "SELECT e.department AS department, AVG (v.value::INTEGER)::NUMERIC(10,2) as average
					  FROM {evaluation_value} v, {evaluation_enrolments} e  
					  WHERE item=$question->id AND value IN ($qfValues)  $subquery 
					    AND e.courseid=v.courseid
					  GROUP BY e.department ORDER BY e.department";
            } else {
                $query = "SELECT $allKeyV AS $allKeyV, AVG (value::INTEGER)::NUMERIC(10,2) as average
					  FROM {evaluation_value} 
					  WHERE item=$question->id AND value IN ($qfValues)  $subquery
					  GROUP BY $allKeyV ORDER BY $allKeyV";
            }

            $records = $DB->get_records_sql($query);
            $average = 0;
            $cnt = 0;
            $averageA = array();
            if (count($records)) {
                foreach ($records as $key) {
                    // $_SESSION['CoS_department']
                    if ($allSelected == "allDepartments"){
                        $cnt ++;
                        $aKey = $key->department;
                        $average = round($key->average, 2);

                        if (isset($allIDs[$aKey])) {
                            $value = $allIDs[$aKey];
                            if (!isset($allKeyValidCount[$value])) {
                                $allKeyValidCount[$value] = 0;
                            }
                            if ($YesNo) {
                                $hint = "Ja/Nein (1-2)";
                            } else {
                                if (!isset($presentation[max(0, round($average))])) {
                                    $presentation[max(0, round($average))] = 0;
                                }
                                $hint = $presentation[max(0, round($average))];
                            }
                            if (!isset($data['average_' . $value][$qCount])) {
                                $data['average_' . $value][$qCount] = 0;
                            }
                            $data['average_' . $value][$qCount] = $average;
                            $data['average_presentation' . $value][$qCount] = $hint;
                            $data['labels_' . $value][$qCount] = $hint . " ($average)";
                            $minval = min($minval, $average);
                            $maxval = max($maxval, $average);
                            $allKeyValidCount[$value]++;
                        }
                    } else {
                        $aKey = array_search($key->$allKeyV, $allIDs);
                        $average = round($key->average, 2);

                        if (isset($allIDs[$aKey])) {
                            $value = $allIDs[$aKey];
                            if (!isset($allKeyValidCount[$value])) {
                                $allKeyValidCount[$value] = 0;
                            }
                            if (isset($key->$allKeyV) and $key->$allKeyV == $value) {
                                if ($YesNo) {
                                    $hint = "Ja/Nein (1-2)";
                                } else {
                                    if (!isset($presentation[max(0, round($average))])) {
                                        $presentation[max(0, round($average))] = 0;
                                    }
                                    $hint = $presentation[max(0, round($average))];
                                }
                                if (!isset($data['average_' . $value][$qCount])) {
                                    $data['average_' . $value][$qCount] = 0;
                                }
                                $data['average_' . $value][$qCount] = $average;
                                $data['average_presentation' . $value][$qCount] = $hint;
                                $data['labels_' . $value][$qCount] = $hint . " ($average)";
                                $minval = min($minval, $average);
                                $maxval = max($maxval, $average);

                                $allKeyValidCount[$value]++;
                            }
                        }
                    }
                }
            }
            // make sure every set has a record (answering was not enforced)
            foreach ($allIDs as $value) {    // still testing... need to find correct key in DB object by $value
                if ($validation) {
                    if (isset($_zeroReplies[$qCount][$value]->count) and $_zeroReplies[$qCount][$value]->count) {
                        $zeroReplies[$question->name . "_" . $value][$qCount] = $_zeroReplies[$qCount][$value]->count;
                    }
                    if (isset($_ignoredReplies[$qCount][$value]->count) and $_ignoredReplies[$qCount][$value]->count) {
                        $ignoredReplies[$question->name . "_" . $value][$qCount] = $_ignoredReplies[$qCount][$value]->count;
                    }
                }
                if (!isset($data['average_' . $value][$qCount])) {
                    if (!isset($allKeyValidCount[$value])) {
                        $allKeyValidCount[$value] = 0;
                    }
                    $average = $data['average'][$qCount];
                    if ($YesNo) {
                        $hint = "Ja/Nein (1-2)";
                    } else {
                        $hint = $presentation[max(0, round($average))];
                    }
                    // problem!!!!!!!!!!!!!!!!!!!!!!!!!!!! Antworten waren nicht pflichtig
                    $data['average_' . $value][$qCount] = $average;
                    $data['average_presentation' . $value][$qCount] = $hint;
                    $data['labels_' . $value][$qCount] = $hint . " (0)";
                }
            }
            $allKeyValid = max($allKeyValid, $average);
        }
        if ($filter) {
            if ($validation) {
                $query = "SELECT COUNT (*) as count FROM {evaluation_value} 
						WHERE item=$question->id AND coalesce(value, '') = ''  $filter $subquery";
                $tmp = $DB->get_record_sql($query)->count;
                if ($tmp > 0) {
                    $zeroReplies[$question->name . "_F"][$qCount] = $tmp;
                }
                $query = "SELECT COUNT (*) as count FROM {evaluation_value} 
						WHERE item=$question->id AND value NOT IN ($qfValues) $filter $subquery";
                $tmp = $DB->get_record_sql($query)->count;
                if ($tmp > 0) {
                    $ignoredReplies[$question->name . "_F"][$qCount] = $tmp;
                }
            }
            $query = "SELECT AVG (value::INTEGER)::NUMERIC(10,2) as average FROM {evaluation_value}
					  WHERE item=$question->id AND value IN ($qfValues) $filter";
            $record = $DB->get_record_sql($query);
            //$count = $DB->get_record_sql("SELECT COUNT (*) as count WHERE item=$question->id AND value IN ($qfValues) $filter" $subquery)->count;
            if (!empty($record) and $record->average >= 1) {
                $average = round($record->average, 2);
                if ($YesNo) {
                    $hint = "Ja/Nein (1-2)";
                } else {
                    $hint = $presentation[max(0, round($average))];
                }
                $data['averageF'][$qCount] = $average;
                $data['averageF_presentation'][$qCount] = $hint;
                $data['averageF_labels'][$qCount] = $hint . " ($average)";
                $validFCount++;
            } else {
                $average = $data['average'][$qCount];
                if ($YesNo) {
                    $hint = "Ja/Nein (1-2)";
                } else {
                    $hint = $presentation[max(0, round($average))];
                }
                $data['averageF'][$qCount] = $average; // problem!!!!!!!!!!!!!!!!!!!!!!!!!!!! Antworten waren nicht pflichtig
                $data['averageF_presentation'][$qCount] = $hint;
                $data['averageF_labels'][$qCount] = $hint . " (0)";
            }
        }
        if ($subquery) {
            $query = "SELECT AVG (value::INTEGER)::NUMERIC(10,2) as average FROM {evaluation_value}
			    		  WHERE item=$question->id AND value IN ($qfValues) $filter $subquery";
            $record = $DB->get_record_sql($query);
            //$count = $DB->get_record_sql("SELECT COUNT (*) as count WHERE item=$question->id AND value IN ($qfValues) $filter" $subquery)->count;
            if (!empty($record) and $record->average >= 1) {
                $average = round($record->average, 2);
                if ($YesNo) {
                    $hint = "Ja/Nein (1-2)";
                } else {
                    $hint = $presentation[max(0, round($average))];
                }
                $data['averageSq'][$qCount] = $average;
                $data['averageSq_presentation'][$qCount] = $hint;
                $data['averageSq_labels'][$qCount] = $hint . " ($average)";
                $validFCount++;
            } else {
                $average = $data['average'][$qCount];
                if ($YesNo) {
                    $hint = "Ja/Nein (1-2)";
                } else {
                    $hint = $presentation[max(0, round($average))];
                }
                $data['averageSq'][$qCount] = $average; // problem!!!!!!!!!!!!!!!!!!!!!!!!!!!! Antworten waren nicht pflichtig
                $data['averageSq_presentation'][$qCount] = $hint;
                $data['averageSq_labels'][$qCount] = $hint . " (0)";
            }
        }

        $minval = min($minval, $average);
        $maxval = max($maxval, $average);

        $qCount++;
    }




    // get total averages
    $totalAvg = 0;
    if ($validCount) {
        $totalAvg = round(array_sum($data['average']) / $validCount, 2);
    }
    $filterAvg = "";
    $tags = array("totalAvg" => $totalAvg);
    if ($qSelected and isset($data['average_presentation'][0])
        AND stristr($data['average_presentation'][0], "ja")
        and stristr($data['average_presentation'][0], "nein")) {
        $presentation = array(($validation ? "ungültig" : "keine Antwort"), "Ja", "Nein");
    }
    $hint = $presentation[max(0, round($totalAvg))];
    $tags["totalPresentation"] = trim($hint);
    $invalidItems = $replies = 0;
    $rowsA = array();
    if ($allKey) {
        $allAvg = array();
        $filterAVGsum = $repliesSum = 0;
        foreach ($allIDs as $key => $value) {
            $validated = true;
            $replies = $allCounts[$allValues[$key]];
            $AVGsum = $replypattern = $filterAvg = 0;
            foreach ($data['average_' . $value] as $reply) {
                $replypattern = max($replypattern, $reply);
            }
            //if ( $replypattern <= 1 and is_siteadmin() )
            //{ print "<hr>data['average_'.$value:" . nl2br(var_export($data['average_'.$value], true)) . "reply: $reply<hr>"; }

            if (($replypattern > 1 or $qSelected or !$validation) and $validCount) {
                $AVGsum = round(array_sum($data['average_' . $value]) / $validCount, 2);
            }
            if ($AVGsum or $qSelected) // ?true :!$validation ) )
            {
                $filterAvg = $AVGsum;
                $hint = $presentation[max(0, round($AVGsum))];
                $validated = true;
            } else {
                $hint = "ungültig ($filterAvg)";
                $validated = false;
            }
            if (!$qSelected and $validation) {
                if ($hideInvalid and !$validated) {
                    unset($data['average_' . $value]);
                    $invalidItems++;
                    continue;
                } else if (!$hideInvalid and $validated) {
                    $invalidItems++;
                    unset($data['average_' . $value]);
                    continue;
                }
            } else if (!$qSelected and $validation and !$validated) {
                $invalidItems++;
                continue;
            }
            $filterAVGsum += $filterAvg;
            $allAvg[$key] = $AVGsum;
            //$filterAVGsum += $filterAvg;
            $repliesSum += $replies;
            // handle different link for course_of_studies
            if (isset($allCosIDs[$key])) {
                $value = $allCosIDs[$key];
            }
            $sortCol = $filterAvg;
            $hint = trim($hint);
            if ($sortKey == "replies") {
                $sortCol = $replies; // $allCounts[$allValues[$key]];
            }

            if ( $replies < $minReplies){
                if ( !$showOmitted ){
                    continue;
                }
                $filterAvg = "";
                $hint = '';
                if ($sortKey != "replies") {
                    $sortCol = 0;
                }
            }

            if ((defined('EVALUATION_OWNER') || $allSelected == "allCourses") && stristr($allLinks[$key],"<a") ){
                $selector = ($allSelected == "allDepartments") ?"department" : $allKey;

                $hintLink = '<a href="print.php?showCompare=1&allSelected=' . $allSelected . '&id='
                        . $id . '&' . $selector . '=' . $value . '" target="compare"><span style="color:navy;font-weight:bold;">' . $hint . '</span></a>';
            } else {
                $hintLink = $hint;
            }
            $rowsA[] = array("key" => $key, "sortKey" => $sortCol,
                    "row" => 'row = table.insertRow(-1); ' ."\n"
                            . 'nCell = row.insertCell(0); nCell.innerHTML = \'' . $allLinks[$key] . '\';' ."\n"
                            . 'nCell.style.textAlign ="left";' ."\n"
                            . 'nCell = row.insertCell(1); nCell.innerHTML = \'' . $replies . "';\n"
                            . 'nCell = row.insertCell(2); nCell.innerHTML = \'' . $hintLink . '\';' . "\n"
                            . 'nCell.style.textAlign ="left"; '
                            . 'nCell = row.insertCell(3); nCell.innerHTML = "' . $filterAvg . '";'
                            . "\n");
        }
        $ids = array_column($rowsA, 'key');
        $sortCol = array_column($rowsA, 'sortKey');
        //$rows = array_column($rowsA, 'row');
        array_multisort($sortCol, $sortOrder, $ids, $sortOrder, $rowsA);

        $rows = "";
        // Verprobung
        if (false) //safeCount($allIDs)>1 AND is_siteadmin() ) //AND $validated ) //!$filter ) // OR is_siteadmin() )
        {
            $validItems = safeCount($allIDs) - $invalidItems;
            $AVGsum = 0;
            $hint = "ungültig";
            if ($validItems) {
                $AVGsum = round($filterAVGsum / $validItems, 2);
                $hint = $presentation[max(0, round($AVGsum))];
            }
            $hint = trim($hint);
            $label = "Verprobung";
            if ($allSelected == "allDepartments") {
                $label = "Alle Abgaben für " . (($evaluatedResults != $evaluationResults) ? "ausgwählte " : "") .
                        get_string("departments", "evaluation");
            } else if ($allSelected == "allStudies") {
                $label = "Alle Abgaben für " . (($evaluatedResults != $evaluationResults) ? "ausgwählte " : "") .
                        get_string("courses_of_studies", "evaluation");
            } else if ($allSelected == "allCourses") {
                $label = "Alle Abgaben für " . (($evaluatedResults != $evaluationResults) ? "ausgwählte " : "") .
                        get_string("courses", "evaluation");
            } else if ($allSelected == "allTeachers") {
                $label = "Alle Abgaben für " . ($evaluatedResults != $evaluationResults ? "ausgwählte " : "") .
                        get_string("teachers", "evaluation");
            }

            $rows = 'row = table.insertRow(-1); '
                    . 'nCell = row.insertCell(0); nCell.innerHTML = '
                    .
                    '\'<span title="Abweichungen entstehen durch gesetzte Mindestabgaben oder leere Abgaben und unbeantwortete Fragen">' .
                    $label . "</span>';"
                    . 'nCell.style.textAlign ="left"; '
                    . 'nCell = row.insertCell(1); nCell.innerHTML = "' . $repliesSum . '"; '
                    . 'nCell = row.insertCell(2); nCell.innerHTML = "' . $hint . '";nCell.style.textAlign ="left"; '
                    . 'nCell = row.insertCell(3); nCell.innerHTML = "' . $AVGsum . '";'
                    . "\n"; //row.style.textAlign ="right";
        }

        foreach ($rowsA as $val) {
            $rows .= $val['row'];
        }
        print '<script>var table = document.getElementById("chartResultsTable");var row = ncell = "";'
                . $rows . '</script>';
    }
    if ($filter) {
        $filterAvg = $replypattern = 0;
        $validated = false;
        foreach ($data['averageF'] as $reply) {
            $replypattern = max($replypattern, $reply);
        }
        if (($replypattern > 1 or $qSelected or !$validation) and $validCount) {
            $filterAvg = round(array_sum($data['averageF']) / $validCount, 2);
            $validated = true;
        }
        $hint = $presentation[max(0, round($filterAvg))];

        if ($validation) {
            if ($hideInvalid and !$validated) {
                unset($data['average_F']);
            } else if (!$hideInvalid and $validated) {
                unset($data['average_F']);
            } else if ($filterAvg or $qSelected) {
                $tags["filterAvg"] = $filterAvg;
                $tags["filterPresentation"] = $hint;
            }
        } else if ($filterAvg or $qSelected) {
            $tags["filterAvg"] = $filterAvg;
            $tags["filterPresentation"] = $hint;
        } else {
            $hint = "ungültig ($filterAvg)";
            $tags["filterAvg"] = 0;
        }
        if ( $numresultsF < $minReplies){
            $tags["filterPresentation"] = '';
            $tags["filterAvg"] = "";
        }
    }
    // subquery
    if ($subquery) {

        $filterAvg = $replypattern = 0;
        $validated = false;
        foreach ($data['averageSq'] as $reply) {
            $replypattern = max($replypattern, $reply);
        }
        if (($replypattern > 1 or $qSelected or !$validation) and $validCount) {
            $filterAvg = round(array_sum($data['averageSq']) / $validCount, 2);
            $validated = true;
        }
        $hint = $presentation[max(0, round($filterAvg))];

        if ($validation) {
            if ($hideInvalid and !$validated) {
                unset($data['average_Fsq']);
            } else if (!$hideInvalid and $validated) {
                unset($data['average_Fsq']);
            } else if ($filterAvg or $qSelected) {
                $tags["SqAvg"] = $filterAvg;
                $tags["SqPresentation"] = $hint;
            }
        } else if ($filterAvg or $qSelected) {
            $tags["SqAvg"] = $filterAvg;
            $tags["SqPresentation"] = $hint;
        } else {
            $hint = "ungültig ($filterAvg)";
            $tags["SqAvg"] = 0;
        }

        if ( $numresultsSq < $minReplies){
            $tags["SqPresentation"] = '';
            $tags["SqAvg"] = "";
        }
    }
    if (count($rowsA) < 2 AND !$omittedResults){    // AND !$isFilter
        print "\n<script>document.getElementById('showFilter').style.display='none';</script>\n";
        unset($data["averageF"]);
    }
    // print "<hr>$totalAvg: " .nl2br(var_export($tags,true)."<hr>\n");
    print "<script>\n";
    // print 'document.getElementById("showFilter").display="table-row";'. "\n";
    foreach ($tags as $key => $value) {
        //print 'document.getElementById("' . $key . '").innerHTML="' . $value . '";' . "\n";
        print "document.getElementById('" . $key . "').innerHTML='" . $value . "';" . "\n";
    }
    print "</script>\n";

    $maxval = ceil($maxval); //intval($maxval+0.7); //ceil( $maxval );
    for ($cnt = 0; $cnt <= $maxval - 1; $cnt++) {
        $label2[$cnt] = $presentation[$cnt];
    }
    //print " - maxval2: $maxval<br>";






    // we do not need graphics if we have only 1 data point and this data is already shown in list
    if ($qSelected) {
        print "<br><b>Für die Antworten auf einzelne Fragen werden keine grafischen Ergebnisese angezeigt!</b><br>\n";
    }else{
        // Use source Chartjs With Wrapper Class
        // using own php wrapper
        /*
            ChartAxis.helpers.color(color).lighten(0.2);
        */

        // message regarding max charts to display
        //if ( $allKey AND safeCount($allResults) > $maxCharts )
        if ($allKey and $evaluatedResults >= $maxCharts) {
            print "<br><b>Es werden nur die ersten $maxCharts Ergebnisese grafisch angezeigt, da die Auswahl > $maxCharts ist!</b><br>\n";
        }

        $colors0 = array("black", "red", "green", "blue","orange", "purple", "cyan",
                "magenta", "Lime", "pink",
                "teal", "lavender", "brown", "beige", "maroon", "mint", "olive", "apricot",
                "navy", "grey", "amber", "darkblue", "darkred",
                "darkred", "violet",
                "#3366CC", "#DC3912", "#FF9900", "#109618", "#990099", "#3B3EAC", "#0099C6",
                "#DD4477", "#66AA00", "#B82E2E", "#316395", "#994499", "#22AA99", "#AAAA11",
                "#6633CC", "#E67300", "#8B0707", "#329262", "#5574A6", "#651067", "#661067", "#691067",

        );
        $colors = ($colors0 + $colors0 + $colors0 + $colors0 + $colors0);
        $labelAxis = ($ChartAxis == "x" ? "y" : "x");
        $ev_name = ev_get_tr($evaluation->name);
        $options = ['responsive' => true, 'indexAxis' => $ChartAxis, 'lineTension' => 0.3,
                'radius' => 4, 'hoverRadius' => 8,
                'plugins' => ['title' => ['display' => true, 'text' => $ev_name, 'fontSize' => 18]],
                'scales' => [
                    //$labelAxis => [ 'suggestedMin' => 1, 'suggestedMax' => $maxval, 'ticks' => [ 'stepSize' => 1,] ],
                    //$labelAxis => [ 'min' => 1, 'max' => $maxval, 'ticks' => [ 'stepSize' => 1,'min' => 1, 'max' => $maxval] ],
                        $labelAxis . 'Axis' => ['min' => $minval, 'max' => $maxval, 'stepSize' => 1,
                                'ticks' => ['min' => $minval, 'max' => $maxval,
                                        'callback' => 'function(value, index, values) { return Labels2[value];}',]],
                ],
            //'plugins' => ['colorschemes' => ['scheme' => 'brewer.RdYlGn9' ] ]
            //, 'stepSize' => 1  // 'beginAtZero' => false,
        ];

        //html attributes for the canvas element
        $attributes = ['style' => 'display:block;width:100%;'];
        $attributes['id'] = 'chartJS_line';

        // define additional dataset values
        /*if ( $ChartAxis == "bubble" OR $ChartAxis == "pie"  OR $ChartAxis == "doughnut")
        {   // needs special treatments
            // {count: 7, rmin: 5, rmax: 21, min: 0, max: 100};
        }*/

        $JSdata = [
                'labels' => $data['labels'],
                'datasets' => [] //You can add datasets directly here or add them later with addDataset()
        ];
        $JSdata['datasets'][] = ['data' => $data['average'], 'label' => "Alle Abgaben", 'labels' => $data['average_labels'],
                'backgroundColor' => $colors[0], 'borderColor' => $colors[0],
            //'axes' => [ 'x' => [ 'labels' => $data['average_labels']] ]
        ];

        if ($filter and isset($data["averageF"])) {
            $JSdata['datasets'][] =
                    ['data' => $data["averageF"], 'label' => implode(", ", $fTitle),
                            'labels' => $data['averageF_labels'],
                            'backgroundColor' => $colors[1], 'borderColor' => $colors[1]];
        }
        if ($subquery and isset($data["averageSq"])) {
            $JSdata['datasets'][] =
                    ['data' => $data["averageSq"], 'label' => $sqTitle,
                            'labels' => $data['averageSq_labels'],
                            'backgroundColor' => $colors[2], 'borderColor' => $colors[2]];
        }
        // show maximum $maxCharts graphs
        if ($allKey) //AND $evaluatedResults <= $maxCharts )
        {    // show graphics for top ten - to Do
            //$sortArray[] = array( "allIDs" => $allResult->courseid, "allValues" => $fullname, "allLinks" => $links, "allCounts" => $Counts );
            $newIDs = $newValues = array();
            if ($sortKey == "replies") {
                if ($sortOrder == SORT_ASC) {
                    uasort($sortArray, function($a, $b) {
                        return ($a["allCounts"] > $b["allCounts"] ? 1 : 0);
                    });
                } else {
                    uasort($sortArray, function($a, $b) {
                        return ($a["allCounts"] > $b["allCounts"] ? 0 : 1);
                    });
                }
                foreach ($sortArray as $key => $value) {
                    $newIDs[] = $sortArray[$key]["allIDs"];
                    $newValues[] = $sortArray[$key]["allValues"];
                }
            } else {
                if ($sortOrder == SORT_ASC) {
                    asort($allAvg);
                } else {
                    arsort($allAvg);
                }
                foreach ($allAvg as $key => $value) {
                    $newIDs[] = $sortArray[$key]["allIDs"];
                    $newValues[] = $sortArray[$key]["allValues"];
                }
            }
            $allIDs = $newIDs;
            $allValues = $newValues;
            $cnt = 0;
            foreach ($allIDs as $key => $value) {
                if (empty($data['average_' . $value])) {
                    continue;
                }
                $replies = $allCounts[$allValues[$key]];
                // print "<hr>minReplies: $minReplies - key: $key => value:$value - allCounts[$allValues[$key]] = $replies<hr>";
                if ($replies >= $minReplies) {
                    $JSdata['datasets'][] =
                            ['data' => $data['average_' . $value], 'label' => $allValues[$key],
                                    'labels' => $data['labels_' . $value],
                                    'backgroundColor' => $colors[$cnt + 3], 'borderColor' => $colors[$cnt + 3]];
                    $cnt++;
                }
                if ($cnt == $maxCharts) {
                    break;
                }
            }
        }
        require_once("classes/ChartJS.php");

        $Line = new ChartJS("line", $JSdata, $options, $attributes);
        // print the chart to html
        echo $Line;

        /*
        print "<hr><br>\n";
        print nl2br(var_export($allIDs));
        print "<hr><br>\n";
        print nl2br(var_export($allCounts));
        print "<hr><br>\n";
        print nl2br(var_export($allValues));
        print "<hr><br>\n";
        print nl2br(var_export($allAvg));
        print "<hr><br>\n";
        print nl2br(var_export($sortArray));
        print "<hr><br>\n";
        //print nl2br(var_export($JSdata));
        */

        /*
        <script src="js/chart/chart.min-2.7.2.js"></script>
        <script src="js/chart/chartjs-plugin-colorschemes.js"></script>
        */
        ?>
        <div id="evDataTable"></div>
        <script src="js/jquery.min.js"></script>
        <script src="js/chart/chart.min.js"></script>
        <script src="js/chart/driver.js"></script>
        <script>


            var Labels2 = <?php echo json_encode($label2); ?>;
            // function to generate borderColor / not yet working
            /*function setOpacity (hex, alpha)
            { return `${hex}${Math.floor(alpha * 255).toString(16).padStart(2, 0)}`;*/
            // render chartJS
            (function () {
                loadChartJsPhp();
            })();


            // scroll screen to top of graphics
            $("#showGraf").click(function () {
                $('html,body').animate({scrollTop: $('#chartJS_line').offset().top}, 'fast');
            });

            /*
            // trying to move chart position below rop list
            function evChartRender()
            {
                //position chart below top info
                var nonparent = $('#chartResultsList');
                var position = nonparent.offset();
                $('#chartJS_line').offset({
                  top: position.top,
                  left: position.left
                });

            }
            if (window.addEventListener )
            {	window.addEventListener('load',evChartRender,false);	}
            else
            {	window.attachEvent('onload',evChartRender); 	}
            //*/

            // switsch horizontal to vertical view
            function toggleAxes() {
                var toggleAxes;

            }

            var dataToTable = function (dataset) {
                var html = '<table style="overflow:scroll; overflow-x:scroll;text-align:left;">'; //width:100%;
                html += '<thead><tr><th> </th>'; // style="width:120px;"

                var columnCount = 0;
                jQuery.each(dataset.datasets, function (idx, item) {
                    html += '<th style="background-color:' + item.fillColor + ';">' + item.label + '</th>';
                    columnCount += 1;
                });

                html += '</tr></thead>';
                var total = [];
                jQuery.each(dataset.labels, function (idx, item) {
                    html += '<tr><td>' + item + '</td>';

                    for (i = 0; i < columnCount; i++) {
                        html += '<td style="background-color:' + dataset.datasets[i].fillColor + ';">' + (dataset.datasets[i].labels[idx] === '0' ? './.' : dataset.datasets[i].labels[idx].substring(0, 80)) + '</td>';
                        total[idx] += dataset.datasets[i].data[idx]
                    }
                    html += '</tr>';
                });
                /*
                html += '<tr>';
                html += '<td>Total Average</td>';
                jQuery.each(dataset, function (idx) {
                    total[idx] = total[idx]/columnCount;
                    total[idx] = total[idx].toFixed(2)
                    html += '<td>' + total[idx]  + '</td>';	//  style="background-color:' + dataset[idx].fillColor + ';"
                });
                html += '</tr>';
                */
                //html += '<tbody></table>';
                html += '</table>';

                return html;
            };

            //Chart.helpers.color(color).lighten(0.2);
            jQuery('#evDataTable').html(dataToTable(<?php echo json_encode($JSdata); ?>));

        </script>
        <?php
    }

    @ob_flush();
    @ob_end_flush();
    @flush();
    @ob_start();
    print "<hr>$goBack";
    // store stats event
    evaluation_trigger_module_statistics($evaluation, false, $courseid);

    //check for unanswered questions
    if ($validation and is_siteadmin()) //AND ( safeCount($zeroReplies) OR safeCount($ignoredReplies) ) )
    {

        // chatGPT

        $evaluationid = $evaluation->id; // Replace with the actual evaluation ID


        // Query to get the number of unanswered questions per submission for a specific evaluation
        $sql = "
    WITH unanswered AS (
        SELECT 
            ebc.id AS submission_id,
            COUNT(ebv.id) AS unanswered_questions
        FROM {evaluation_completed} ebc
        JOIN {evaluation_value} ebv ON ebc.id = ebv.completed
        JOIN {evaluation_item} ebi ON ebv.item = ebi.id
        JOIN {evaluation} e ON ebi.evaluation = e.id
        WHERE e.id = :evaluationid
        AND ebi.hasvalue = 1
        AND (ebv.value IS NULL OR ebv.value = '')
        GROUP BY ebc.id ORDER BY ebc.id
    )
    SELECT * FROM unanswered ORDER BY unanswered_questions DESC;
";

        $submissions = $DB->get_records_sql($sql, ['evaluationid' => $evaluationid]);

        // Query to get the total number of submissions with unanswered questions for the specific evaluation
        $sql_total = "
    WITH unanswered AS (
        SELECT ebc.id
        FROM {evaluation_completed} ebc
        JOIN {evaluation_value} ebv ON ebc.id = ebv.completed
        JOIN {evaluation_item} ebi ON ebv.item = ebi.id
        JOIN {evaluation} e ON ebi.evaluation = e.id
        WHERE e.id = :evaluationid
        AND ebi.hasvalue = 1
        AND (ebv.value IS NULL OR ebv.value = '')
        GROUP BY ebc.id ORDER BY ebc.id
    )
    SELECT COUNT(*) AS submissions_with_unanswered_questions FROM unanswered;
";

        $total_unanswered_submissions = $DB->get_field_sql($sql_total, ['evaluationid' => $evaluationid]);

        // Query to get the total number of valid answers
        $sql_valid_answers = "
    SELECT COUNT(ebv.id) AS total_valid_answers
    FROM {evaluation_value} ebv
    JOIN {evaluation_item} ebi ON ebv.item = ebi.id
    JOIN {evaluation} e ON ebi.evaluation = e.id
    WHERE e.id = :evaluationid
    AND ebi.hasvalue = 1
    AND ebv.value IS NOT NULL 
    AND ebv.value <> '';
";

        $total_valid_answers = $DB->get_field_sql($sql_valid_answers, ['evaluationid' => $evaluationid]);

        // Output results
        echo "<br><hr>\n";

        $total_unanswered_questions = 0;
        foreach ($submissions as $submission) {
            $total_unanswered_questions += $submission->unanswered_questions;
        }

        echo "Total submissions with unanswered questions for Evaluation ID $evaluationid: " . $total_unanswered_submissions . "<br>";
        echo "Total valid answers for Evaluation ID $evaluationid: " . $total_valid_answers . "<br>";
        echo "<hr>Total unanswered questions for Evaluation ID $evaluationid: " . $total_unanswered_questions . "<br>";

        foreach ($submissions as $submission) {
            echo "Submission ID: {$submission->submission_id}, Unanswered Questions: {$submission->unanswered_questions} <br>";
        }
        echo "<hr><br>\n";

        $noAnswerSum = $ignoredAnswerSum = 0;
        $noAnswerQSum = $ignoredAnswerQSum = array();
        foreach ($zeroReplies as $key => $value) {
            $noAnswerQSum[$key] = 0;
            foreach ($value as $cnt => $reply) {
                if ($reply > 0) {    //$_zeroReplies[$key][$cnt] = $reply;
                    $noAnswerQSum[$key] += $reply;
                    $noAnswerSum += $reply;
                }
            }
        }
        foreach ($ignoredReplies as $key => $value) {
            $ignoredAnswerQSum[$key] = 0;
            foreach ($value as $cnt => $reply) {
                if ($reply > 0) {
                    $ignoredAnswerQSum[$key] += $reply;
                    $ignoredAnswerSum += $reply;
                }
            }
        }

        print "<br><hr><b>Validation<br>Keine Antworten: " . ($qSelected ? "" : "$noAnswerSum - pro Frage:") . "</b><br><ol>";
        foreach ($noAnswerQSum as $key => $value) {
            if (strstr($key, "_")) {
                print '<ul style="display:inline;"><li style="display:inline;">';
            } else {
                print "<li>";
            }
            print "$key: <b>$value</b>";
            if (strstr($key, "_")) {
                print "</li></ul>";
            } else {
                print "</li>";
            }
            print "<br>\n";
        }
        print "</ol><br><b>Ignorierte Antworten (zB 'k.b.'): " . ($qSelected ? "" : "$ignoredAnswerSum - pro Frage:") .
                "</b><br><ol>";

        foreach ($ignoredAnswerQSum as $key => $value) {
            if (strstr($key, "_")) {
                print '<ul style="display:inline;"><li style="display:inline;">';
            } else {
                print "<li>";
            }
            print "$key: <b>$value</b>";
            if (strstr($key, "_")) {
                print "</li></ul>";
            } else {
                print "</li>";
            }
            print "<br>\n";
        }
        print "</ol>";
        //. nl2br(var_export($noAnswerQSum, true));
        //. nl2br(var_export($invalidReplies, true));
    }

    $n = 300;
    function decColorToHex($int) {
        return str_pad(dechex($int), 2, '0', STR_PAD_LEFT);
    }

    function getColorForItem($itemNumber, $numberOfItems) {
        $x = ($itemNumber - 1) / ($numberOfItems - 1);

        $r = max(0, round(sin(M_PI * ($x - 1)) * 255));
        $g = max(0, round(sin(M_PI * $x) * 255));
        $b = max(0, round(sin(M_PI * ($x + 3)) * 255));

        return decColorToHex($r) . decColorToHex($g) . decColorToHex($b);
    }

    /*
     for ($i=1;$i<=$n;$i++) {
        $c=getColorForItem($i,$n);
        echo '<div style="width:200px;height:30px;color:#fff;font-weight:bold;background-color:#'.$c.';">Item '.$i.': #'.$c.'</div>';
    }
    */
    /*
    $count = 0;
    for($i=0,$i<=255,$i++){
        for($i_i=0,$i_i<=255,$i_i++){
            for($i_i_i=0,$i_i_i<=255,$i_i_i++){
                echo '<span style="color:rgb('.$i.','.$i_i.','.$i_i_i.')">'.$count.'&nbsp;&nbsp;</span>';
                $count++;
                if( $count>42){
                    break;
                }
            }
        }
    }
    */
}
