<?php

// this file is part of Moodle mod_evaluation plugin

/*
// question presentation constants
define('EVALUATION_MULTICHOICE_TYPE_SEP', '>>>>>');
define('EVALUATION_MULTICHOICE_LINE_SEP', '|');
define('EVALUATION_MULTICHOICE_ADJUST_SEP', '<<<<<');
define('EVALUATION_MULTICHOICE_IGNOREEMPTY', 'i');
define('EVALUATION_MULTICHOICE_HIDENOSELECT', 'h');
*/

// get average results of all answers fore selected items or all course_of_studies, courses and teachers
function evaluation_compare_results( $evaluation, $courseid=false, $course_of_studiesID=false, $teacherid=false )
{	global $DB, $OUTPUT, $USER;
	if ( !isset($_SESSION["duplicated"]) )
	{	$_SESSION["duplicated"] = evaluation_count_duplicated_replies($evaluation); }
	$id = get_evaluation_cmid_from_id($evaluation);
	
	// auto-submit if called by $_GET
	if ( !empty( $_GET["showCompare"] ) )
	{	
	?>
	<form style="display:inline;" id="postForm" method="POST" action="print.php">
	<input type="hidden" name="id" value="<?php echo $id;?>">
	<input type="hidden" name="showCompare" value="1">
	<input type="hidden" name="courseid" value="<?php echo $courseid;?>">
	<input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID;?>">
	<input type="hidden" name="teacherid" value="<?php echo $teacherid;?>">	
	<script>document.getElementById("postForm").submit();</script>
	</form>
	<?php
	}
	$isOpen = evaluation_is_open( $evaluation );
	$maxCharts	 = 21;
	$allSelected = ev_session_request( "allSelected", "");
	$ChartAxis = ev_session_request( "ChartAxis", "x");
	$sortOrder = intval(ev_session_request( "sortOrder", SORT_ASC));
	$minReplies = intval(ev_session_request( "minReplies", evaluation_min_results($evaluation) ) );
	$minResults = evaluation_min_results($evaluation);
	$minResultsText = min_results_text($evaluation);
	$minResultsPriv = min_results_priv($evaluation);
	if ( defined('EVALUATION_OWNER') )
	{	$minResults = $minResultsText = $minResultsPriv; }
	$qSelected = intval(ev_session_request( "qSelected", ""));
	$sortKey   = ev_session_request( "sortKey", "values");
	$validation = intval(ev_session_request( "validation", 0 ));
	$hideInvalid = intval(ev_session_request( "hideInvalid", 1 ));
	
	$isFilter	 = ( $teacherid OR $courseid OR $course_of_studiesID );
	/*if ( $isFilter AND $allSelected == "useFilter" )
	{	if ( $courseid ) { $allSelected = "allCourses"; }
		$isFilter = false; $course_of_studiesID=false; $teacherid=false; $courseid = false;	
	}*/
	$isStudent = $isTeacher = false;
	$allSubject = "";
	$data = array(); 
	$zeroReplies = $invalidReplies = array();
	
	// handle CoS privileged user
	$cosPrivileged = evaluation_cosPrivileged( $evaluation );
	$cosPrivileged_filter = evaluation_get_cosPrivileged_filter( $evaluation );
	//if ( !$course_of_studiesID AND ( $cosPrivileged = evaluation_cosPrivileged( $evaluation ) ) )
	//{	$course_of_studiesID = evaluation_get_course_of_studies_id_from_evc( $id, $_SESSION['CoS_privileged'][$USER->username][0], $evaluation ); }

	$course_of_studies = false;
	if ( $course_of_studiesID )
	{	$course_of_studies = evaluation_get_course_of_studies_from_evc( $course_of_studiesID, $evaluation ); }
	
	$boldStyle = "font-size:12pt;font-weight:bold;display:inline;";
	$buttonstyle = 'font-size:125%;color:white;background-color:black;text-align:center;';
	$goBack = html_writer::tag( 'button', "Zurück", array( 'class' =>"d-print-none", 'style'=>$buttonstyle, 
							'type' => 'button','onclick'=>'(window.history.back()?window.history.back():window.close());'));
	$goBack .= "&nbsp;&nbsp;" . html_writer::tag( 'a', "Überblick", array( 'class' =>"d-print-none",'style'=> $buttonstyle, 
								'type' => 'button','href' => 'view.php?id='.$id.'&courseid='.$courseid.'&teacherid='.$teacherid
								.'&course_of_studiesID='.$course_of_studiesID ));
	$goBack .= "&nbsp;&nbsp;" . html_writer::tag( 'a', "Auswertung", array( 'class' =>"d-print-none",'style'=> $buttonstyle, 
								'type' => 'button','href' => 'analysis_course.php?id='.$id.'&courseid='.$courseid.'&teacherid='.$teacherid
								.'&course_of_studiesID='.$course_of_studiesID ));
	
	// handle CoS priveleged user
	if ( !empty($_SESSION['CoS_privileged'][$USER->username]) )
	{	print "Auswertungen der Studiengänge: " . '<span style="font-weight:600;white-space:pre-line;">'
								.implode(", ", $_SESSION['CoS_privileged'][$USER->username]) . "</span><br>\n";
	}
	
	print $goBack;
	echo evPrintButton();

	$responses = get_string('completed_evaluations',"evaluation");
	$filterSubject = "Auswahl zurücksetzen";

	$hint = "Es gibt 3 Varianten von automatisch bewertbaren Fragen: Radio und Dropdown (Single Choice) oder Checkbox (Multi Choice). Bei Single Choice Fragen kann aus mehreren Antwortoptionen genau eine Antwort ausgewählt werden. Multi Choice Fragen erlauben eine beliebige Auswahl von Antworten";
	echo '<h1 title="'. $hint .'" style="display:inline;color:darkgreen;text-align:left;font-weight:bolder;">Statistik</h1><br>';

	if ( $allSelected == "allStudies" )
	{	$allSubject = get_string("courses_of_studies","evaluation"); }
	elseif ( $allSelected == "allCourses" )
	{	$allSubject = get_string("courses","evaluation"); }
	elseif ( $allSelected == "allTeachers" )
	{	$allSubject = get_string("teachers","evaluation"); }
	
	// access control
	if ( !defined('EVALUATION_OWNER') )
	{	$myEvaluations = get_evaluation_participants($evaluation, $USER->id );
		if  ( 	$isOpen OR $course_of_studiesID OR ( $teacherid AND $teacherid != $USER->id ) 
				OR ( $courseid AND !evaluation_is_my_courseid( $myEvaluations, $courseid ) )
			)
		{	print '<br><h2 style="font-weight:bold;color:darkred;background-color:white;">'
					. get_string('no_permission_analysis', 'evaluation') . "</h2><br>"; 
			echo $OUTPUT->continue_button("/mod/evaluation/view.php?id=$id");
			echo $OUTPUT->footer(); evaluation_spinnerJS(); exit;
		}
		$isStudent = evaluation_is_student( $evaluation, $myEvaluations );
		$isTeacher = evaluation_is_teacher( $evaluation, $myEvaluations ); 
	}
			
	$query = "SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id 
				AND (typ='multichoice' OR typ='numeric') AND hasvalue=1 
				AND name NOT ILIKE '%". get_string("course_of_studies","evaluation") ."%' 
				ORDER by position ASC";
	$allQuestions = $DB->get_records_sql($query);
	$numAllQuestions = safeCount($allQuestions);
	if ( !$numAllQuestions )
	{	echo $OUTPUT->notification("Es gibt weder Multichoice Fragen noch numerische Fragen. 
				Eine statistische Auswertung ist für diese Evaluation nicht möglich!"); echo $OUTPUT->footer(); flush(); exit; 
	}
	
	$presentation = array();
	$scheme = $numQuestions = "";
	if ( $qSelected )
	{	$query = "SELECT * FROM {evaluation_item} WHERE id = $qSelected AND evaluation=$evaluation->id ORDER by position ASC";
		$question = array();
		$questions = $DB->get_records_sql($query);
		//extract presentation list
		foreach ( $questions AS $question )
		{	//$question = $question1; break; }
		
			$itemobj = evaluation_get_item_class($question->typ);
			$itemInfo = $itemobj->get_info($question);
			
			$presentation = explode("|", str_replace( array("<<<<<1", "r>>>>>", "c>>>>>", "r>>>>>", "\n"),"", $question->presentation));
			if ( in_array( "k.b.", $presentation ) OR in_array( "keine Angabe", $presentation )  OR in_array( "Kann ich nicht beantworten", $presentation ))
			{	array_pop($presentation); }
						
			$qfValues = "";
			for ( $cnt=1; $cnt <= (safeCount($presentation)); $cnt++ )
			{	$qfValues .= "'$cnt'" . ( $cnt < safeCount($presentation) ?"," :""); }
			$scheme = implode(", ", $presentation) . " <=> $qfValues";

			array_unshift($presentation, ($validation ?"ungültig" :"keine Antwort") );
			break;
			//print "<br>qfValues: $qfValues<br>Scheme: $scheme<br>presentation: " . var_export($presentation,true) 
			//. "<br>info: " .var_export($info,true) . "<br>" ;
			//print 'Ausgewertete Frage: <span style="' . $boldStyle .'">'	. $question->name . "</span><br>\n"; 
		}
	}
	else
	{	//$schemeQ = "( presentation ilike '%stimme zu%' OR (presentation ilike '%ja%' AND presentation ilike '%nein%'))";	
		$stimmezu = array("stimme zu", "stimme eher zu", "stimme eher nicht zu", "stimme nicht zu" );
		$trifftzu = array("trifft zu", "trifft eher zu", "trifft eher nicht zu", "trifft nicht zu" );
		$schemeQ = "( presentation ilike '%stimme zu%' OR presentation ilike '%trifft zu%')";	
		$query = "SELECT * FROM {evaluation_item} WHERE evaluation=$evaluation->id AND (typ like'multichoice%' OR typ='numeric') AND $schemeQ
					ORDER BY position ASC";
		$questions = $DB->get_records_sql($query);	
		//print "<br><hr>".var_export($questions,true);exit;
		$numQuestions =  safeCount($questions);
		//$presentation = array( ($validation ?"ungültig" :"keine Antwort") ) + $stimmezu; 
		$presentation = array_merge( array( ($validation ?"ungültig" :"keine Antwort") ), $stimmezu );
		//, "stimme zu", "stimme eher zu", "stimme eher nicht zu", "stimme nicht zu" );
		$scheme = '"stimme zu"=1 - "stimme nicht zu"=4<br>';
		$present = "nope";
		foreach ( $questions AS $quest )
		{	$present = $quest->presentation; break; }
		if ( $numQuestions AND stristr( $present, "trifft" ) )
		{	$presentation = array_merge( array( ($validation ?"ungültig" :"keine Antwort") ), $trifftzu );
			$scheme = '"trifft zu"=1 - "trifft nicht zu"=4<br>';
		}
		print '<span title="'. $hint .'">Ausgewertete Single Choice Fragen: </span><span style="' 
				. $boldStyle .'">'	. $numQuestions . "</span> - "; 
	}
	if ( false) //empty($presentation) )
	{	echo $OUTPUT->notification("Es gibt keine multichoice Fragen und auch keine Fragen mit numerischen Antworten. 
				Eine statistische Auswertung ist für diese Evaluation nicht möglich!"); echo $OUTPUT->footer(); flush(); exit; 
	}
	$numAnswers = safeCount($presentation);
	echo "<b>Antwort - Schema</b>: $scheme<br>\n";
	//echo "<b>\$presentation ".var_export($presentation,true) . "</b><br>\n";
				
	$buttonStyle = 'margin: 3px 5px;font-weight:bold;color:white;background-color:teal;';
	$selectStyle = 'margin: 3px 5px;font-weight:bolder;color:white;background-color:darkblue;';
	?>
	<div style="display:block;">
	<form style="display:inline;" id="statsForm" method="POST" action="print.php">
	
	<?php
	
	// validation needs more research
	if ( !is_siteadmin() ) //AND !defined('EVALUATION_OWNER') )
	{	$validation = $hideInvalid = true; }
	elseif ( !$qSelected )
	{
		$label =  ( $validation ?"V" :"Nicht V" ) . "alidiert";
		$value =  ( $validation ?0 :1 );
		?>	
		<button name="validation" style="<?php echo $buttonStyle;?>" value="<?php echo $value;?>" onclick="this.form.submit();">
		<?php 
		echo '<span title="Abgaben werden auf Plausibilität geprüft. Abgaben, bei denen immer die erste Antwort gewählt wurde, werden als ungültig markiert!">'
			. $label . '</span>';
		echo "</button>\n";
		if ( $validation )
		{	$label =  "Invalide " . ( $hideInvalid ?"verbergen" :"anzeigen" );
			$value =  ( $hideInvalid ?0 :1 );
			?>	
			<button name="hideInvalid" style="<?php echo $buttonStyle;?>" value="<?php echo $value;?>" onclick="this.form.submit();">
			<?php 
			echo '<span title="Es werden nur ' . ($hideInvalid ?"validierte" :"ungültige")
				. ' Auswertungen angezeigt">'. $label . '</span>'
				. "</button>\n";
		
		}
	}

	if ( $allSelected AND  $allSelected !== "useFilter" ) 
	{			
			
		$label =  ( $sortOrder == SORT_ASC ?"up" :"down" );
		$value =  ( $sortOrder == SORT_DESC ?SORT_ASC :SORT_DESC );
		?>	
		<button name="sortOrder" style="<?php echo $buttonStyle;?>" value="<?php echo $value;?>" onclick="this.form.submit();"><?php 
				echo '<span style="width:21px;color:white;" class="fa fa-arrow-'.$label.' fa-1x" 
					  title="Sortierung zwischen Aufsteigend und Absteigend wechseln"></i>';?>
		</button>
		<?php
		$label =  ( $sortKey == "replies" ?"Abgaben" :"Mittelwert" );
		$value =  ( $sortKey == "values" ?"replies" :"values" );
		?>	
		<button name="sortKey" style="<?php echo $buttonStyle;?>" value="<?php echo $value;?>" onclick="this.form.submit();">
		<?php 
		echo '<span title="Sortierung nach Abgaben oder nach AMittwlwerten">'.$label.'</span></button>';
	
	}
	if ( !$qSelected) //AND ($allSelected AND $allSelected !== "allCourses" AND $allSelected !== "allTeachers" ) )
	{	
	?>
	<div style="display:inline;" id="showGraf" title="Hier Klicken um direkt zur Grafik zu scrollen"><b>Grafik</b>: </div>
	<?php
	$label = ( $ChartAxis == "x" ?"Horizonal" :"Vertikal" );
	$value = ( $ChartAxis == "x" ?"y" :"x" );
	?>
	<button name="ChartAxis" style="<?php echo $buttonStyle;?>" value="<?php echo $value;?>" onclick="this.form.submit();"><?php 
			echo $label;?></button>

	<?php 
	}
	if ( $isFilter AND $allSelected AND $allSelected !== "useFilter" )  // filter conditions set
	{
	?>
	<button name="allSelected" style="<?php echo $buttonStyle;?>" value="useFilter" onclick="this.form.submit();"><?php 
			echo $filterSubject;?></button>
	<?php
	}

	//if ( $isFilter OR defined('EVALUATION_OWNER') )
	if ( ( $isTeacher OR $isStudent ) OR defined('EVALUATION_OWNER') )
	{	
	print $isFilter ?"" :"- alle: ";
	if ( $allSelected == "allStudies" )
	{ 	$style = $selectStyle; $value = "";	}
	else
	{	$style = $buttonStyle; $value = "allStudies"; }
	?>
	<button name="allSelected" style="<?php echo $style;?>" value="<?php echo $value;?>" onclick="this.form.submit();"><?php 
			echo get_string("courses_of_studies","evaluation");?></button>
	
	<?php 
	if ( $allSelected == "allCourses" )
	{ 	$style = $selectStyle; $value = "";	}
	else
	{	$style = $buttonStyle; $value = "allCourses"; }

	?>
	<button name="allSelected" style="<?php echo $style;?>" value="<?php echo $value;?>" onclick="this.form.submit();">
	<?php 
	echo get_string("courses","evaluation");
	echo "</button>";
	
	if ( ( $isTeacher AND $teacherid ) OR defined('EVALUATION_OWNER') ) //( !$teacherid AND !$isStudent AND !$isTeacher ) OR defined('EVALUATION_OWNER') )
	{	if ( $allSelected == "allTeachers" )
		{ 	$style = $selectStyle; $value = "";	}
		else
		{	$style = $buttonStyle; $value = "allTeachers"; }
	?>
	<button name="allSelected" style="<?php echo $style;?>" value="<?php echo $value;?>" onclick="this.form.submit();"><?php 
			echo get_string("teachers","evaluation");?></button>
	<?php 
	} // if !teacherid
	//defined('EVALUATION_OWNER') AND
	if ( ( $allSelected == "allCourses" OR $allSelected == "allTeachers" ) )
	{ 
	print '- mindestens 
			<input type="number" name="minReplies" value="' . $minReplies .'"
			style="width:42px;font-size:100%;color:white;background-color:teal;"
			min="$minResults">
			Abgaben';
	}
	//print 	"\n<br><b>" . $numAllQuestions . " " . get_string("questions","evaluation")	. '</b> ' 
	print 		"\n<br>";
	
	if ( $qSelected )
	{	print "<b>Ausgwertete Frage</b>.: "; }
	
	print 	'<select name="qSelected" style="'. $buttonStyle.'" onchange="this.form.submit();">'. "\n"
			. '<option value="">'.get_string("all"). " " .$numQuestions. " ".get_string("questions","evaluation") . "</option>\n"; //. $numAllQuestions ." " 
	foreach ( $allQuestions AS $question )
	{	$selected = "";
		if ( $question->id == $qSelected )
		{	$selected = ' selected="'.$selected.'" '; }
		print '<option value="'.$question->id.'"'.$selected.'>'.$question->name."</option>\n";
	}
	print "</select>\n";
	if ( $qSelected AND $itemInfo->subtype == 'c')
	{	print '<br><span style="color:blue;">Dies ist eine Multi Choice Frage. 
				Es können nur Single Choice Antworten sinnvoll ausgwertet werden'."</span><br>\n"; 
	}
	
	} // if isTeacher OR Owner 
	?>
	<input type="hidden" name="id" value="<?php echo $id;?>">
	<input type="hidden" name="showCompare" value="1">
	<input type="hidden" name="courseid" value="<?php echo $courseid;?>">
	<input type="hidden" name="course_of_studiesID" value="<?php echo $course_of_studiesID;?>">
	<input type="hidden" name="teacherid" value="<?php echo $teacherid;?>">
	</form>
	</div>


	<?php

		
	$completed_responses = evaluation_countCourseEvaluations( $evaluation );
	if ( !$completed_responses )
	{	echo $OUTPUT->notification(get_string('no_responses_yet', 'mod_evaluation')); echo $OUTPUT->footer(); flush(); exit; }
	
	$filter = $filterC = $allKey = $allKeyV = "";
	$numTeachers = 0;
	$allIDs = $allValues = $allCounts = $allResults = $fTitle = $allCosIDs = $sortArray = array();

	if ( $teacherid )
	{	$filter  .= " AND teacherid=".$teacherid; 
		$filterC .= " AND teacherid=".$teacherid; 
		$teacher = evaluation_get_user_field( $teacherid, 'fullname' );
		$fTitle[] = get_string("teacher","evaluation") .": $teacher";
		$anker = get_string("teacher","evaluation").': <span style="font-size:12pt;font-weight:bold;">'
					. $teacher . "</span>";
		if ( defined('EVALUATION_OWNER') )
		{	print '<a href="print.php?id=' .$id. '&showTeacher='. $teacherid . '" target="teacher">'.$anker.'</a>';	
			print ' (<a href="print.php?showCompare=1&allSelected='.$allSelected.'&id=' 
							. $id. '&courseid=' . $courseid . '&course_of_studiesID=' 
							. $course_of_studiesID.'">'."Filter entfernen".'</a>)';
		}
		else
		{
			print $anker;
		}
		print "<br>\n";
	}
	if ( $courseid )
	{	$filter  .= " AND courseid=".$courseid; 
		$filterC .= " AND courseid=".$courseid; 
		$course = $DB->get_record('course', array('id' => $courseid), '*'); //$course = get_course($courseid);
		$fTitle[] = get_string("course","evaluation") .": $course->fullname";		
		evaluation_get_course_teachers( $courseid); 
		$numTeachers = safeCount($_SESSION["allteachers"][$courseid]);	
		$Studiengang = evaluation_get_course_of_studies($courseid,true);  // get Studiengang with link
		$semester 	 = evaluation_get_course_of_studies($courseid,true,true);  // get Semester with link
		if ( !empty($Studiengang) )	
		{	$Studiengang = get_string("course_of_studies","evaluation").": <span style=\"font-size:12pt;font-weight:bold;display:inline;\">"
							.$Studiengang.(empty($semester) ?"" :" <span style=\"font-size:10pt;font-weight:normal;\">("
							.$semester.")</span>") . "</span><br>\n"; 
		}
		$anker = get_string("course","evaluation").': <span style="font-size:12pt;font-weight:bold;display:inline;">'
				. $course->fullname . " ($course->shortname)</span>\n";	
		print $Studiengang.'<a href="analysis_course.php?id=' .$id. '&courseid='. $courseid . '" target="course">'.$anker.'</a>';
		
		// option to remove filter
		if ( defined('EVALUATION_OWNER') )
		{	print ' (<a href="print.php?showCompare=1&allSelected='.$allSelected.'&id=' 
							. $id. '&teacherid=' . $teacherid . '&course_of_studiesID=' 
							. $course_of_studiesID.'">'."Filter entfernen".'</a>)';		
		}
		//$msg = ($evaluation->teamteaching AND $numTeachers>1) ?" (Team Teaching)" :" (Eine Abgabe pro Teilnehmer_in und Kurs)";
		$msg = ( $numTeachers>1 ) ?" (Team Teaching)" :" (Eine Abgabe pro Teilnehmer_in und Kurs)";
		if ( defined( "showTeachers") ) 
		{	echo showTeachers . $msg. "<br>\n"; }
		else
		{	$msg = "<br>- Dieser Kurs hat $numTeachers ".get_string("teacher".($numTeachers>1 ?"s" :""),"evaluation") . $msg; 
			print '<span style="font-size:12pt;font-weight:normal;display:inline;">'
					. $msg . "</span><br>\n";	

		}

	}
	if ( $course_of_studies )
	{	$filter  .= " AND course_of_studies='".$course_of_studies."'"; 
		$filterC .= " AND course_of_studies='".$course_of_studies."'"; 		
		$fTitle[] = get_string("course_of_studies","evaluation") .":  $course_of_studies";
		$anker = get_string("course_of_studies","evaluation").': <span style="font-size:12pt;font-weight:bold;display:inline;">'
				. $course_of_studies . "</span>\n";
		print '<a href="analysis_course.php?id=' .$id. '&course_of_studiesID='. $course_of_studiesID 
				. '" target="course_of_studies">'.$anker.'</a>';
		if ( defined('EVALUATION_OWNER') )
		{	print ' (<a href="print.php?showCompare=1&allSelected='.$allSelected.'&id=' 
							. $id. '&teacherid=' . $teacherid . '&courseid=' 
							. $courseid.'">'."Filter entfernen".'</a>)';		
		}
		print "<br>\n";
	}
	
	$numresultsF  = safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} WHERE evaluation=$evaluation->id $filterC"));
	if ( $filterC AND $numresultsF < $minResults )
	{	if ( !is_siteadmin())	{	$filter = $filterC = ""; }
		print '<span style="color:red;font-weight:bold;">'."Es gibt für</span> '" . implode(", ", $fTitle ) . "' "
				. '<span style="color:red;font-weight:bold;">' ."weniger als $minResults Abgaben</span>. "
				. "<b>Daher wird keine Auswertung angezeigt!</b><br>".(is_siteadmin()?"- except for siteadmin": "")."<br>\n";
	}
	//handle CoS priv users
	$filter  .= $cosPrivileged_filter;
	$filterC .= $cosPrivileged_filter;
	
	$numresultsF  = safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} WHERE evaluation=$evaluation->id $filterC"));

	if ( $allSelected == "allStudies" )
	{	$allKey = "course_of_studiesID"; $allKeyV = "course_of_studies";
		$aFilter = "course_of_studies <>''"; // . $cosPrivileged_filter;
		$evaluationResults = safeCount( $DB->get_records_sql("SELECT course_of_studies, count(*) AS count 
											 FROM {evaluation_completed} 
											 WHERE evaluation=$evaluation->id AND $aFilter
											 GROUP BY course_of_studies ORDER BY course_of_studies") );
		$aFilter = "";
		if ( $isFilter )
		{	if ( $course_of_studies )
			{	$aFilter  .= " AND course_of_studies='$course_of_studies'";  }
			if ( $teacherid )
			{	$aFilter  .= " AND teacherid=".$teacherid; }
			if ( $courseid )
			{	$aFilter  .= " AND courseid=".$courseid; }
		}
		$aFilter  .= $cosPrivileged_filter;
		$allResults  = $DB->get_records_sql("SELECT course_of_studies, count(*) AS count 
											 FROM {evaluation_completed} 
											 WHERE evaluation=$evaluation->id $aFilter
											 GROUP BY course_of_studies ORDER BY course_of_studies");
		$evaluatedResults = 0;
		foreach ($allResults AS $allResult )
		{	if ( $allResult->count >= $minReplies )
			{	
				$allIDs[] = $allValues[] = $allResult->course_of_studies; 
				$course_of_studiesID = evaluation_get_course_of_studies_id_from_evc( $id, $allResult->course_of_studies, $evaluation );
				$allCosIDs[] = $course_of_studiesID;
				if ( defined('EVALUATION_OWNER') )
				{	$links = '<a href="analysis_course.php?id=' . $id .
									'&course_of_studiesID='
									.  $course_of_studiesID
									. '" target="analysis">'.$allResult->course_of_studies."</a>"; 
				}
				else
				{	$links = $allResult->course_of_studies; }
				$allLinks[] = $links;
				if ( $isFilter )
				{	$Result  = $DB->get_record_sql("select count(*) AS count 
													FROM {evaluation_completed} 
													WHERE evaluation=$evaluation->id AND course_of_studies='$allResult->course_of_studies'");
													//GROUP BY course_of_studies ORDER BY course_of_studies");
					$Counts = $Result->count; 
					//print 	"<br>Result= ".nl2br(var_export( $Result, true)) ."<br>";	
				}
				else
				{	$Counts = $allResult->count; }
				$allCounts[$allResult->course_of_studies] = $Counts;
				$sortArray[] = array( 	"allIDs" => $allResult->course_of_studies, "allValues" => $allResult->course_of_studies, 
										"allLinks" => $links, "allCounts" => $Counts );
				$evaluatedResults ++;
			}
		}
  		
	}
	elseif( $allSelected == "allCourses" )
	{	$allKey = "courseid"; $allKeyV = "courseid";
		$aFilter = "courseid >0"; // .$cosPrivileged_filter;
		$evaluationResults = safeCount( $DB->get_records_sql("SELECT courseid AS courseid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND $aFilter
											 GROUP BY courseid ORDER BY courseid") );
		$aFilter = "";
		if ( $isFilter )
		{	if ( $courseid )
			{	$aFilter  .= " AND courseid=".$courseid; }
			if ( $teacherid )
			{	$aFilter  .= " AND teacherid=".$teacherid; }
			if ( $course_of_studies )
			{	$aFilter  .= " AND course_of_studies='$course_of_studies'";  }
		}
		$aFilter  .= $cosPrivileged_filter;
		$allResults  = $DB->get_records_sql("SELECT courseid AS courseid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id $aFilter
											 GROUP BY courseid ORDER BY courseid");
		$evaluatedResults = 0;
		foreach ($allResults AS $allResult )
		{	if ( $allResult->count >= $minReplies )
			{	if ( !defined('EVALUATION_OWNER') AND !evaluation_is_teacher( $evaluation, $myEvaluations, $allResult->courseid)
						AND !evaluation_is_student( $evaluation, $myEvaluations, $allResult->courseid) )
				{	continue; }
			
				$fullname = evaluation_get_course_field( $allResult->courseid, 'fullname');
				if ( true ) //defined('EVALUATION_OWNER') )
				{	$links = '<a href="analysis_course.php?id=' . $id . '&courseid='.$allResult->courseid
									.'" title="'.$fullname.'" target="analysis">'
									.(strlen($fullname)>120 ?substr($fullname,0,120)."..." :$fullname)."</a>";
				}
				else
				{	$links = $fullname; }						
				if ( empty($fullname) )
				{	$fullname = '<b style="color:red;">Der Kurs mit Kurs-ID ' . $allResult->courseid . ' existiert nicht mehr!</b>'; 
					$links = $fullname;
				}
				$allLinks[] = $links;
				$allIDs[] = $allResult->courseid;
				$allValues[] = $fullname;
				if ( $isFilter )
				{	$Result  = $DB->get_record_sql("select count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND courseid=$allResult->courseid");
											 //GROUP BY courseid ORDER BY courseid");
					$Counts = $Result->count;
				}
				else
				{	$Counts = $allResult->count; }
				$allCounts[$fullname] = $Counts;
				$sortArray[] = array( "allIDs" => $allResult->courseid, "allValues" => $fullname, "allLinks" => $links, "allCounts" => $Counts );
				$evaluatedResults ++;
			}
		}
	}
	elseif ( $allSelected == "allTeachers" )
	{	$allKey = "teacherid"; $allKeyV = "teacherid";
		$aFilter = "teacherid >0"; // .$cosPrivileged_filter;
		$evaluationResults = safeCount( $DB->get_records_sql("SELECT teacherid AS teacherid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND $aFilter
											 GROUP BY teacherid ORDER BY teacherid") );
		$aFilter = "";
		if ( $isFilter )
		{	if ( $teacherid )
			{	$aFilter  .= " AND teacherid=".$teacherid; }
			if ( $courseid )
			{	$aFilter  .= " AND courseid=".$courseid; }
			if ( $course_of_studies )
			{	$aFilter  .= " AND course_of_studies='$course_of_studies'";  }
		}
		$aFilter  .= $cosPrivileged_filter;
		$allResults  = $DB->get_records_sql("SELECT teacherid AS teacherid, count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id $aFilter
											 GROUP BY teacherid ORDER BY teacherid");
		$evaluatedResults = 0;
		foreach ($allResults AS $allResult )
		{	if ( $allResult->count >= $minReplies )
			{	$fullname = evaluation_get_user_field( $allResult->teacherid, 'fullname');
				if ( defined('EVALUATION_OWNER') )
				{	$links = '<a href="print.php?id=' .$id. '&showTeacher='. $allResult->teacherid
									.'" target="analysis">'.$fullname."</a>"; 
				}
				else
				{	$links = $fullname; }	
				if ( empty($fullname) )
				{	$fullname = '<b style="color:red;">Es gibt kein ASH Konto mehr für einen Lehrenden mit der User-ID'. $allResult->teacherid.'!</b>'; 
					$links = $fullname;
				}
				$allLinks[] = $links;
				$allIDs[] = $allResult->teacherid;
				$allValues[] = $fullname;
				if ( $isFilter )
				{	$Result  = $DB->get_record_sql("select count(*) AS count
											 FROM {evaluation_completed}
											 WHERE evaluation=$evaluation->id AND teacherid=$allResult->teacherid");
											 //GROUP BY courseid ORDER BY courseid");
					$Counts = $Result->count;
				}
				else
				{	$Counts = $allResult->count; }		
				$allCounts[$fullname] = $Counts;
				$sortArray[] = array( "allIDs" => $allResult->teacherid, "allValues" => $fullname, "allLinks" =>  $links, "allCounts" => $Counts );
				$evaluatedResults ++;
			}
		}
	}

	/*print 	"<br>allResults=".nl2br(substr(var_export( $allResults[0], true),0,210))	 . 
	"<br>allIDs=".nl2br(substr(var_export( $allIDs[0], true),0,150))	 . 
	"<br>allValues=".nl2br(substr(var_export( $allValues[0], true),0,150))	 . "<br>\n";*/
	if ( $courseid) 
	{	//$divisor = ($evaluation->teamteaching AND !$teacherid )?$numTeachers :1 ;
		//$numTeachers = safeCount($_SESSION["allteachers"][$courseid]);
		$divisor = ( !$teacherid ) ?$numTeachers :1 ;
		$students = get_evaluation_participants($evaluation, false, $courseid, false, true);
		$participated = $completed = 0;

		foreach ( $students AS $participant )
		{	
			if ( evaluation_has_user_participated($evaluation, $participant["id"], $courseid ) )
			{	$participated++; }
			if ( true ) //$evaluation->teamteaching )
			{	if ( isEvaluationCompleted( $evaluation, $courseid, $participant["id"] ) )
				{	$completed++; }
			}
			//print "Reminder: ".$participant["reminder"] . "<br>";
			//if ( $participant["reminder"] == trim( get_string("analysis","evaluation") ) )	{	$participated++; } 
		}
		$numStudents = safeCount($students );
		if ( !$isOpen )
		{	$numStudents = evaluation_count_students( $evaluation, $courseid); }
		print '<span style="font-size:12pt;font-weight:normal;">';
		if ( $numStudents )
		{	$numToDo = $numStudents * $divisor;
			$evaluated = round( ( $participated / $numStudents ) * 100,1) . "%"; 
			print "Dieser Kurs hat $numTeachers Dozent_in". ($numTeachers>1 ?"nen" :"")
					." und $numStudents studentische Teilnehmer_innen. 
				   $participated Teilnehmer_innen haben sich an dieser Evaluation beteiligt. 
				   Das entspricht einer Beteiligung von $evaluated.<br>\n";
			if ( true ) //$evaluation->teamteaching ) 
			{	if ( $numTeachers >1 ) 
				{	$completed = round( ( $completed / $numStudents ) * 100,1) . "%"; 
					print "$completed der Teilnehmer_innen haben alle Dozent_innen bewertet. "; 
					if ( !empty($teacherid) )
					{	$completed = round( ( $numresultsF / $numStudents ) * 100,1) . "%"; 
						print "<br>$completed der Teilnehmer_innen haben diese Dozent_in bewertet."; 
					}
				}
				$quote = round( ( $numresultsF / $numToDo ) * 100,1) . "%"; 				
				print "Es wurden ".evaluation_number_format($numresultsF)." von maximal ".evaluation_number_format($numToDo)
						." Abgaben gemacht. Die Abgabequote beträgt $quote. ";
			}
		}
		else
		{	print "Dieser Kurs hat keine studentischen Teilnehmer_innen."; }
		echo "</span><br>\n";
	}
	
	
	
	if ( $allKey )
	{	$hint = "";
		if (  $allSelected == "allTeachers" AND !$evaluation->teamteaching )
		{	$hint = "<br><small>Diese Evaluation hat kein Team Teaching aktiviert. 
					In Kursen mit Team Teaching haben daher alle Dozent_innen dieselbe Auswertung.</small><br>\n"; 
		}		
		print "Ausgewertete " 
			.'<span style="font-size:12pt;font-weight:bold;display:inline;">'.$allSubject.': '	. $evaluatedResults 
			. ($evaluatedResults==$evaluationResults ?"" :" von insgesamt ".$evaluationResults ) . $hint ."</span><br>\n";
	}

	$numresults  = safeCount($DB->get_records_sql("SELECT id FROM {evaluation_completed} WHERE evaluation=$evaluation->id"));	

	print '<style> table, th, td { border:1px solid black;} th, td { padding:5px; text-align:right; vertical-align:bottom;}</style>';
	print '<table id="chartResultsTable" style="border-collapse:collapse;margin: 5px 30px;font-size:12pt;font-weight:normal;">';
	print  '<tr style="font-weight:bold;background-color:lightgrey;">
			<th colspan="2" style="text-align:left">' . "Abgaben" .($_SESSION["duplicated"] ?" <small>(inkl. "
				. evaluation_number_format($_SESSION["duplicated"]) . " duplizierter Abgaben)</small>" :""). '</th>
			<th colspan="2">'. 'Mittelwert' .'</th></tr>'."\n";
	print  '<tr><td style="text-align:left;">' . "Alle Abgaben:" . '</td>
				<td>'.$numresults  . '</td>
				<td style="text-align:left;"><span id="totalPresentaion"></span></td>
				<td><span id="totalAvg"></span></td></tr>'."\n";
	if ( $filter )
	{	if ( empty($fTitle) AND $cosPrivileged_filter )
		{	$fTitle[] = "Einsehbare Studiengänge"; }
		$title = implode("<br>\n",$fTitle);
		print '<tr><td style="text-align:left;">' . "Alle Abgaben für: ". $title .'</td>'
					. '<td>'.$numresultsF       . '</td>'
					. '<td style="text-align:left;"><span id="filterPresentation"></span></td><td><span id="filterAvg"></span></td></tr>'."\n";
	}
	print '</table><div style="display:block;" id="chartResultsList"></div>'."\n";
	
	
	
	
	
	
	// Question Loop
	
	$qCount = $validCount = $validFCount = $maxval = 0; $minval = $filterValid = $allKeyValid = 1;
	$allKeyValidCount = array();
	foreach ( $questions as $question )
	{	$YesNo = stripos(strtolower($question->presentation),"nein") !== false; // yes/no handling
		if ( $qSelected )
		{ if ( $question->id != $qSelected )
			{ continue; } //print "<br>$question->id!==$qSelected"; 
			$fValues = $qfValues;
		}
		else
		{	$fValues = "'1','2'" . ($YesNo ?"" :",'3','4'"); }
		if ( $validation )		
		{	$query = "SELECT count (*) as count FROM {evaluation_value} 
				  WHERE item=$question->id AND coalesce(value, '') = ''";
			$zeroReplies[$question->name][$qCount] = $DB->get_record_sql($query)->count;
			$query = "SELECT count (*) as count FROM {evaluation_value} 
				  WHERE item=$question->id AND value NOT IN ($fValues)"; 
			$ignoredReplies[$question->name][$qCount] = $DB->get_record_sql($query)->count;
		}
		$query = "SELECT AVG (value::INTEGER)::NUMERIC(10,2) as average FROM {evaluation_value} 
				  WHERE item=$question->id AND value IN ($fValues)";
		$answer = $DB->get_record_sql($query);
		if ( empty( $answer ) )
		{	continue; }
		$validCount++;
		$average  = round($answer->average,2);
		$minval = min( $minval, $average); $maxval = max( $maxval, $average);
		
		if ( $YesNo )
		{	$hint = "Ja/Nein (1-2)"; }
		else
		{	$hint = $presentation[max(0,round($average))]; }
		$data['average'][$qCount] = $average;
		$data['labels'][$qCount] = $question->name; 
		$data['average_presentation'][$qCount] = $hint; 
		$data['average_labels'][$qCount] = $hint . " ($average)"; 
		if ( $allKeyV )
		{	if ( $validation )		
			{	$query = "SELECT $allKeyV AS $allKeyV, COUNT(*) as count FROM {evaluation_value} 
							WHERE item=$question->id AND coalesce(value, '') = '' $filter
							GROUP BY $allKeyV ORDER BY $allKeyV";			
				$_zeroReplies[$qCount] = $DB->get_records_sql($query);
				$query = "SELECT $allKeyV AS $allKeyV, COUNT(*) as count FROM {evaluation_value} 
							WHERE item=$question->id AND value NOT IN ($fValues) $filter
							GROUP BY $allKeyV ORDER BY $allKeyV";			
				$_ignoredReplies[$qCount] = $DB->get_records_sql($query);
			}
			$query = "SELECT $allKeyV AS $allKeyV, AVG (value::INTEGER)::NUMERIC(10,2) as average
					  FROM {evaluation_value} 
					  WHERE item=$question->id AND value IN ($fValues)
					  GROUP BY $allKeyV ORDER BY $allKeyV";	
			$records = $DB->get_records_sql($query);
			if ( count ( $records ) )
			{	foreach ( $records AS $key )
				{	$average = round($key->average,2);
					$aKey = array_search($key->$allKeyV, $allIDs );
					if ( isset($allIDs[$aKey]) )
					{	$value = $allIDs[$aKey];
						if ( !isset( $allKeyValidCount[$value] ) )	{	$allKeyValidCount[$value] = 0; }
						if ( isset($key->$allKeyV) AND $key->$allKeyV == $value )
						{	if ( $YesNo )
							{	$hint = "Ja/Nein (1-2)"; }
							else
							{	if ( !isset( $presentation[max(0,round($average))] ))	{	$presentation[max(0,round($average))] = 0; }
								$hint = $presentation[max(0,round($average))]; 
							}
							if ( !isset( $data['average_'.$value][$qCount] ) )	{	$data['average_'.$value][$qCount] = 0; }
							$data['average_'.$value][$qCount] = $average; 
							$data['average_presentation'.$value][$qCount] = $hint; 
							$data['labels_'.$value][$qCount]  = $hint . " ($average)";							
							$minval = min( $minval, $average); $maxval = max( $maxval, $average);
							
							$allKeyValidCount[$value]++;
						}
					}
				}
			}
			// make sure every set has a record (answering was not enforced)
			foreach ( $allIDs AS $value )
			{	// still testing... need to find correct key in DB object by $value
				if ( $validation )		
				{	if ( isset($_zeroReplies[$qCount][$value]->count) AND $_zeroReplies[$qCount][$value]->count )
					{	$zeroReplies[$question->name."_".$value][$qCount] = $_zeroReplies[$qCount][$value]->count; }
					if ( isset($_ignoredReplies[$qCount][$value]->count) AND $_ignoredReplies[$qCount][$value]->count )
					{	$ignoredReplies[$question->name."_".$value][$qCount] = $_ignoredReplies[$qCount][$value]->count; }
				}
				if ( !isset($data['average_'.$value][$qCount]) )
				{	if ( !isset( $allKeyValidCount[$value] ) )	{	$allKeyValidCount[$value] = 0; }
					$average = $data['average'][$qCount];
					if ( $YesNo )
					{	$hint = "Ja/Nein (1-2)"; }
					else
					{	$hint = $presentation[max(0,round($average))]; }
					$data['average_'.$value][$qCount] = $average; // problem!!!!!!!!!!!!!!!!!!!!!!!!!!!! Antworten waren nicht pflichtig
					$data['average_presentation'.$value][$qCount] = $hint; 
					$data['labels_'.$value][$qCount]  = $hint . " (0)";
				}
			}
			$allKeyValid = max( $allKeyValid, $average);
		}
		if ( $filter )
		{	if ( $validation )		
			{	$query = "SELECT COUNT (*) as count FROM {evaluation_value} 
						WHERE item=$question->id AND coalesce(value, '') = ''  $filter"; 
				$tmp = $DB->get_record_sql($query)->count;
				if ( $tmp >0 ) {	$zeroReplies[$question->name."_F"][$qCount] = $tmp; }
				$query = "SELECT COUNT (*) as count FROM {evaluation_value} 
						WHERE item=$question->id AND value NOT IN ($fValues) $filter";
				$tmp = $DB->get_record_sql($query)->count;
				if ( $tmp >0 ) {	$ignoredReplies[$question->name."_F"][$qCount] = $tmp; }
			}
			$query = "SELECT AVG (value::INTEGER)::NUMERIC(10,2) as average FROM {evaluation_value}
					  WHERE item=$question->id AND value IN ($fValues) $filter";
			$record = $DB->get_record_sql($query);
			//$count = $DB->get_record_sql("SELECT COUNT (*) as count WHERE item=$question->id AND value IN ($fValues) $filter")->count;
			if ( !empty( $record ) AND $numresultsF >= $minResults AND $record->average >= 1 )
			{	$average = round($record->average,2);
				if ( $YesNo )
				{	$hint = "Ja/Nein (1-2)"; }
				else
				{	$hint = $presentation[max(0,round($average))]; }
				$data['averageF'][$qCount] = $average;
				$data['averageF_presentation'][$qCount] = $hint; 
				$data['averageF_labels'][$qCount] = $hint . " ($average)"; 
				$validFCount++;
			}
			else
			{	$average = $data['average'][$qCount];
				if ( $YesNo )
				{	$hint = "Ja/Nein (1-2)"; }
				else
				{	$hint = $presentation[max(0,round($average))]; }
				if ( $numresultsF >=  $minResults )
				{	$data['averageF'][$qCount] = $average; // problem!!!!!!!!!!!!!!!!!!!!!!!!!!!! Antworten waren nicht pflichtig
					$data['averageF_presentationF'][$qCount] = $hint; 
					$data['averageF_labels'][$qCount] = $hint . " (0)";
				}
			}
			$filterValid = max( $filterValid, $average);
		}
		$minval = min( $minval, $average); $maxval = max( $maxval, $average);
		
		$qCount++;
	}	


	
	
	// get total averages
	$totalAvg = 0;
	if ( $validCount )
	{	$totalAvg = round( array_sum( $data['average']) / $validCount, 2); }
	$filterAvg = "";
	$tags = array("totalAvg" => $totalAvg);	
	if ( $qSelected AND stristr( $data['average_presentation'][0], "ja") AND stristr( $data['average_presentation'][0], "nein") )
	{	$presentation = array( ($validation ?"ungültig" :"keine Antwort") ,"Ja","Nein"); }
	$hint = $presentation[max(0,round($totalAvg))]; 
	$tags["totalPresentaion"] = trim($hint);	
	$invalidItems = 0;
	if ( $allKey )
	{	$allAvg = array();
		$rowsA = array();
		$filterAVGsum = $repliesSum = 0;
		foreach ( $allIDs AS $key => $value )
		{ 	$validated = true;
			$AVGsum = $replypattern = $filterAvg = 0;
			foreach ( $data['average_'.$value] AS $reply )
			{	$replypattern = max($replypattern,$reply); }
			//if ( $replypattern <= 1 and is_siteadmin() )
			//{ print "<hr>data['average_'.$value:" . nl2br(var_export($data['average_'.$value], true)) . "reply: $reply<hr>"; }
			
			if ( ($replypattern > 1 OR $qSelected OR !$validation) AND $validCount )
			{	$AVGsum = round( array_sum( $data['average_'.$value] ) / $validCount, 2); }			
			if ( $AVGsum OR $qSelected) // ?true :!$validation ) )
			{	$filterAvg = $AVGsum; 
				$hint = $presentation[max(0,round($AVGsum))];
				$validated = true;				
			}
			else
			{	$hint = "ungültig ($filterAvg)";
				$validated = false;
			}
			if ( !$qSelected AND $validation ) 
			{	if ( $hideInvalid AND !$validated) 
				{ 	unset( $data['average_'.$value]);
					$invalidItems++;
					continue; 
				}
				elseif ( !$hideInvalid AND $validated) 
				{	$invalidItems++;
					unset( $data['average_'.$value]);
					continue; 
				}
			}
			elseif ( !$qSelected AND $validation AND !$validated) 
			{	$invalidItems++; continue; }
			$filterAVGsum += $filterAvg;
			$allAvg[$key] = $AVGsum;
			//$filterAVGsum += $filterAvg;
			$repliesSum += $allCounts[$allValues[$key]];
			// handle different link for course_of_studies
			if ( isset( $allCosIDs[$key] ) )
			{	$value = $allCosIDs[$key]; }
			$sortCol = $filterAvg;
			$hint = trim( $hint );
			if ( $sortKey == "replies" )
			{	$sortCol = $allCounts[$allValues[$key]]; }
			if ( defined('EVALUATION_OWNER') || $allSelected=="allCourses" )
			//{	$hintLink = '<a href="print.php?showCompare=1&allSelected=useFilter&id=' 
			{	$hintLink = '<a href="print.php?showCompare=1&allSelected='.$allSelected.'&id=' 
							.$id. '&' . $allKey .'='. $value . '" target="compare">'.$hint.'</a>';
			}
			else
			{	$hintLink = $hint; }
			//if ( $isFilter AND $allSelected == "allTeachers" )
			//{	$allLinks[$key] = "Alle ". ( $teacherid ?"ausgwählten " :"") . get_string("teachers","evaluation"); }
			
			$rowsA[] = array( "key" => $key, "sortKey" => $sortCol, 
				"row" => 'row = table.insertRow(-1); '
				.'nCell = row.insertCell(0); nCell.innerHTML = \''.$allLinks[$key].'\';nCell.style.textAlign ="left"; '
				.'nCell = row.insertCell(1); nCell.innerHTML = \''.$allCounts[$allValues[$key]].'\'; '
				.'nCell = row.insertCell(2); nCell.innerHTML = \''.$hintLink.'\';nCell.style.textAlign ="left"; '
				.'nCell = row.insertCell(3); nCell.innerHTML = "'.$filterAvg.'";'
				."\n"); //row.style.textAlign ="right";
				/*print "<br>Data $value=".$allValues[$key].": filterAvg :$filterAvg: Average: " . var_export( $data['average_'.$value], true)	
				. "<br>\nLabels: "	.var_export( $data['labels_'.$value], true) . "<br>\n";*/
			
		}
		$ids = array_column($rowsA, 'key');
		$sortCol = array_column($rowsA, 'sortKey');
		//$rows = array_column($rowsA, 'row');
		array_multisort($sortCol, $sortOrder, $ids, $sortOrder, $rowsA);

		
		$rows = "";	
		// Verprobung
		if ( false) //safeCount($allIDs)>1 AND is_siteadmin() ) //AND $validated ) //!$filter ) // OR is_siteadmin() )
		{	$validItems = safeCount( $allIDs )-$invalidItems;
			$AVGsum = 0;
			$hint = "ungültig";
			if ( $validItems )
			{	$AVGsum = round( $filterAVGsum / $validItems, 2);
				$hint = $presentation[max(0,round($AVGsum))]; 
			}
			$hint = trim( $hint );
			$label = "Verprobung";
			if ( $allSelected == "allStudies" )
			{	$label = "Alle Abgaben für ". ( ($evaluatedResults!=$evaluationResults) ?"ausgwählte " :"") . get_string("courses_of_studies","evaluation"); }
			elseif ( $allSelected == "allCourses" )
			{	$label = "Alle Abgaben für ". ( ($evaluatedResults!=$evaluationResults) ?"ausgwählte " :"") . get_string("courses","evaluation"); }
			elseif ( $allSelected == "allTeachers" )
			{	$label = "Alle Abgaben für ". ( $evaluatedResults!=$evaluationResults ?"ausgwählte " :"") . get_string("teachers","evaluation"); }
			
			$rows = 'row = table.insertRow(-1); '
				.'nCell = row.insertCell(0); nCell.innerHTML = '
				.'\'<span title="Abweichungen entstehen durch gesetzte Mindestabgaben oder leere Abgaben und unbeantwortete Fragen">' . $label . "</span>';"
				.'nCell.style.textAlign ="left"; '
				.'nCell = row.insertCell(1); nCell.innerHTML = "' . $repliesSum .'"; '
				.'nCell = row.insertCell(2); nCell.innerHTML = "'.$hint.'";nCell.style.textAlign ="left"; '
				.'nCell = row.insertCell(3); nCell.innerHTML = "' . $AVGsum.'";'
				. "\n"; //row.style.textAlign ="right";
		}
		
		foreach ( $rowsA AS $val  )
		{	$rows .= $val['row']; }
		print '<script>var table = document.getElementById("chartResultsTable");var row = ncell = "";'.$rows.'</script>';
	}
	if ( $filter AND $numresultsF >= $minResults )
	{	$filterAvg = $replypattern = 0;
		$validated = false;
		foreach ( $data['averageF'] AS $reply )
		{	$replypattern = max($replypattern,$reply); }
		if ( ($replypattern > 1  OR $qSelected OR !$validation) AND $validCount )
		{	$filterAvg = round( array_sum( $data['averageF']) / $validCount, 2); 
			$validated = true;
		}
		$hint = $presentation[max(0,round($filterAvg))];
				
		if ( $validation ) 
		{	if ( $hideInvalid AND !$validated ) 
			{ 	unset( $data['average_F']); }
			elseif ( !$hideInvalid AND $validated) 
			{	unset( $data['average_F']);	}
			elseif (  $filterAvg OR $qSelected )
			{	$tags["filterAvg"] = $filterAvg; 
				$tags["filterPresentation"] = $hint;
			}
		}
		elseif (  $filterAvg OR $qSelected )
		{	$tags["filterAvg"] = $filterAvg; 
			$tags["filterPresentation"] = $hint;
		}
		else
		{	$hint = "ungültig ($filterAvg)";
			$tags["filterAvg"] = 0;
		}
	}
	print "<script>\n";
	foreach ( $tags AS $key => $value )
	{ print 'document.getElementById("'.$key.'").innerHTML="'.$value.'";'."\n"; }
	print "</script>\n";

	$maxval = ceil($maxval); //intval($maxval+0.7); //ceil( $maxval );
	for ( $cnt=0; $cnt<=$maxval-1; $cnt++)
	{	$label2[$cnt] = $presentation[$cnt]; }
	//print " - maxval2: $maxval<br>";
	
	// message regarding max charts to display
	//if ( $allKey AND safeCount($allResults) > $maxCharts )
	if ( $allKey AND $evaluatedResults >= $maxCharts )
	{	print "<br><b>Es werden nur die ersten $maxCharts Ergebnisese grafisch angezeigt, da die Auswahl > $maxCharts ist!</b><br>\n"; }


	// we do not need graphics if we have only 1 data point and this data is already shown in list
	if ( !$qSelected )
	{	// Use source Chartjs With Wrapper Class
		// using own php wrapper
		/*
			ChartAxis.helpers.color(color).lighten(0.2);
		*/
		
		
		$colors = array( "#3366CC", "#DC3912", "#FF9900", "#109618", "#990099", "#3B3EAC", "#0099C6",
			"#DD4477", "#66AA00", "#B82E2E", "#316395", "#994499", "#22AA99", "#AAAA11",
			"#6633CC", "#E67300", "#8B0707", "#329262", "#5574A6", "#651067", "#661067", "#691067"
		 );		
		$labelAxis = ($ChartAxis == "x" ?"y" :"x" );
		$options = [ 'responsive' => true, 'indexAxis' => $ChartAxis, 'lineTension' => 0.3, 
					 'radius' => 4, 'hoverRadius' => 8,
					 'plugins' => ['title' => [ 'display' => true, 'text' => $evaluation->name, 'fontSize' => 18 ]],
			 'scales' => [
				//$labelAxis => [ 'suggestedMin' => 1, 'suggestedMax' => $maxval, 'ticks' => [ 'stepSize' => 1,] ], 
				//$labelAxis => [ 'min' => 1, 'max' => $maxval, 'ticks' => [ 'stepSize' => 1,'min' => 1, 'max' => $maxval] ], 
				$labelAxis.'Axis' => [ 'min' => $minval, 'max' => $maxval, 'stepSize' => 1,
						'ticks' => [ 'min' => $minval, 'max' => $maxval,
						'callback' => 'function(value, index, values) { return Labels2[value];}', ]],					
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

		if ( $filter AND isset( $data["averageF"] ) )
		{	$JSdata['datasets'][] = ['data' => $data["averageF"], 'label' => implode(", ",$fTitle), 'labels' => $data['averageF_labels'],
					'backgroundColor' => $colors[1], 'borderColor' => $colors[1] ]; 
		}
		// show maximum $maxCharts graphs
		if ( $allKey ) //AND $evaluatedResults <= $maxCharts )
		{	// show graphics for top ten - to Do
			//$sortArray[] = array( "allIDs" => $allResult->courseid, "allValues" => $fullname, "allLinks" => $links, "allCounts" => $Counts );
			$newIDs = $newValues = array();
			if ( $sortKey == "replies" )
			{	if ( $sortOrder == SORT_ASC )
				{	uasort($sortArray, function($a, $b) 
					{	return ($a["allCounts"]> $b["allCounts"] ? 1:0); } );
				}
				else
				{	uasort($sortArray, function($a, $b) 
					{	return ($a["allCounts"] > $b["allCounts"] ? 0:1); } );
				}
				foreach ( $sortArray as $key => $value )
				{	$newIDs[] = $sortArray[$key]["allIDs"]; 
					$newValues[] = $sortArray[$key]["allValues"]; 
				}				
			}
			else
			{	if ( $sortOrder == SORT_ASC )
				{	asort($allAvg); }
				else
				{	arsort($allAvg); }
				foreach ( $allAvg as $key => $value )
				{	$newIDs[] = $sortArray[$key]["allIDs"]; 
					$newValues[] = $sortArray[$key]["allValues"]; 
				}
			}
			$allIDs = $newIDs;
			$allValues = $newValues;
			$cnt = 0;
			foreach ( $allIDs AS $key => $value )
			{ 	if ( empty($data['average_'.$value]) )	{	continue; }
				$JSdata['datasets'][] = ['data' => $data['average_'.$value], 'label' =>  $allValues[$key], 'labels' => $data['labels_'.$value],
						'backgroundColor' => $colors[$key+1], 'borderColor' => $colors[$key+1] ]; 
				$cnt++;
				if ( $cnt == $maxCharts )	{	break; }
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
		<script src="js/chart.min-2.7.2.js"></script>
		<script src="js/chartjs-plugin-colorschemes.js"></script>
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
		(function () { loadChartJsPhp();})();

		
		// scroll screen to top of graphics
		$("#showGraf").click(function() { $('html,body').animate({ scrollTop: $('#chartJS_line').offset().top}, 'fast'); });
		
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
		function toggleAxes()	
		{	var toggleAxes;
		
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
					html += '<td style="background-color:' + dataset.datasets[i].fillColor + ';">' + (dataset.datasets[i].labels[idx] === '0' ? './.' : dataset.datasets[i].labels[idx].substring( 0, 80)) + '</td>';
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

	@ob_flush();@ob_end_flush();@flush();@ob_start();
	print "<hr>$goBack";
	


	//check for unanswered questions
	if ( $validation AND is_siteadmin() ) //AND ( safeCount($zeroReplies) OR safeCount($ignoredReplies) ) )
	{	$noAnswerSum = $ignoredAnswerSum = 0; $noAnswerQSum = $ignoredAnswerQSum = array();
		foreach ( $zeroReplies AS $key => $value )	
		{	$noAnswerQSum[$key] = 0;
			foreach ( $value AS $cnt => $reply )	
			{	if ( $reply > 0 )
				{	//$_zeroReplies[$key][$cnt] = $reply;
					$noAnswerQSum[$key] += $reply;
					$noAnswerSum += $reply;
				}		
			}
		}
		foreach ( $ignoredReplies AS $key => $value )	
		{	$ignoredAnswerQSum[$key] = 0;
			foreach ( $value AS $cnt => $reply )	
			{	if ( $reply > 0 )
				{	$ignoredAnswerQSum[$key] += $reply;
					$ignoredAnswerSum += $reply;
				}		
			}
		}

		print "<br><hr><b>Validation<br>Keine Antworten: " .( $qSelected ?"" :"$noAnswerSum - pro Frage:" ) ."</b><br><ol>";
		foreach ( $noAnswerQSum AS $key => $value ) 
		{	if ( strstr($key,"_") )
			{ print '<ul style="display:inline;"><li style="display:inline;">'; }
			else
			{	print "<li>"; }
			print "$key: <b>$value</b>"; 
			if ( strstr($key,"_") )
			{ print "</li></ul>"; }
			else
			{	print "</li>"; }
			print "<br>\n";
		}
		print "</ol><br><b>Ignorierte Antworten (zB 'k.b.'): " .( $qSelected ?"" :"$ignoredAnswerSum - pro Frage:" ) ."</b><br><ol>";
		
		foreach ( $ignoredAnswerQSum AS $key => $value ) 
		{	if ( strstr($key,"_") )
			{ print '<ul style="display:inline;"><li style="display:inline;">'; }
			else
			{	print "<li>"; }
			print "$key: <b>$value</b>"; 
			if ( strstr($key,"_") )
			{ print "</li></ul>"; }
			else
			{	print "</li>"; }
			print "<br>\n";
		}
		print "</ol>";
		//. nl2br(var_export($noAnswerQSum, true));
			//. nl2br(var_export($invalidReplies, true));
	}
}
