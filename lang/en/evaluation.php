<?php
// This file is part of Moodle - https://moodle.org/
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
 * Strings for component 'evaluation', language 'en', branch 'MOODLE_405_STABLE'
 *
 * @package mod_evaluation
 * @category    string
 * @copyright 1999 onwards Martin Dougiamas  {@link https://moodle.com}
 * @copyright 2021 onwards Harry Bleckert for ASH Berlin  {@link https://ash-berlin.eu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $string[''] = '';
$string['language'] = 'en';

// translator function
$string['evaluation_of_courses'] = 'Evaluation of courses';
$string['by_students'] = 'by students';
$string['of_'] = 'of';
$string['for_'] = 'for';
$string['sose_'] = 'summer semester';
$string['wise_'] = 'winter semester';
// end translator function

// graph lib
$string['show_graphic_data'] = 'Hhow chart data';
$string['hide_graphic_data'] = 'Hide chart data';
// end graph lib

// view.php
$string['is'] = 'is';
$string['was'] = 'was';
$string['teamteachingtxt'] = 'In seminars with team teaching, the lecturers are evaluated individually.';
$string['is_WM_disabled'] = 'Continuing education master\'s degree programs are excluded.';
$string['siteadmintxt'] = 'Administrator and thus';
$string['andrawdata'] = 'and raw data';
$string['yourcos'] = 'your degree programs';
$string['viewanddownload'] = 'view and download if at least more than {$a->minresultspriv} submissions have been provided.';
$string['privilegestxt'] = 'As {$a->siteadmintxt} privileged person for this evaluation, all results {$a->andrawdata} of this evaluation you can';
$string['courseparticipants'] = 'This course has {$a->numteachers} lecturers and {$a->numstudents} student participants.';
$string['participantsandquota'] = 'Participants have participated in this evaluation. This corresponds to a participation of {$a->evaluated}.';
$string['quotaevaluatedall'] = 'of participants have evaluated all lecturers.';
$string['quotaevaluatedteacher'] = 'of participants have evaluated this lecturer.';
$string['coursequota'] = '{$a->completed_responses} of a maximum of {$a->numToDo} submissions have been made. The submission quota is {$a->quote}.';
$string['nostudentsincourse'] = 'This course has no student participants.';
$string['questionaireenglish'] = 'Here is an English translation of the questionnaire.';
$string['clickquestionaireenglish'] = '<b>Click here</b> to open an <b>english</b> translation of the questionnaire';
$string['also'] = 'also';
$string['foryourcourses'] = 'for each of your courses';
$string['msg_student_all_courses'] = 'Please take {$a->also} part in this evaluation.
The survey is anonymous and only takes a few minutes per course.<br>Click \'<b>Evaluate now</b>\' below for each of your courses that have not yet been evaluated and then fill out the questionnaire.';
$string['yourevaluationhelps'] = 'Your evaluation is a great help to us!';
$string['resultconditions'] = 'For each course you have already evaluated, you can view the evaluation as soon as {$a->minresults} submissions are received.';
$string['yourpartcourses'] = 'You have courses that are participating in this evaluation. Please encourage students to participate';
$string['yourpastpartcourses'] = 'You have courses that have participated in this evaluation';
$string['teachersviewconditions'] = 'For your own courses, you can view the evaluation as soon as {$a->minresults} submissions are received.
With {$a->minresultstxt} submissions, you can also see the text answers.';
$string['evaluationalert'] = 'Only {$a->daysleft} days left until the end of the submission deadline!';
$string['show_active_only'] = 'Adjusted: Only participants who used Moodle during the evaluation period';
$string['onefeedbackperteacher'] = 'One submission per lecturer is activated?';
$string['teamteachingcourses'] = 'Courses with team teaching';
$string['duplicatedfeedbacks'] = 'Duplicate submissions';
$string['logananalysis'] = 'Evaluation of the Moodle log. Log data is kept for {$a->loglifetime} days.';
$string['currentday'] = 'Today is <b>day {$a->currentday} {$a->currentday_percent}';
$string['not_anonymous'] = 'not anonymous';
$string['evaluationperiod'] = 'Participation in the evaluation {$a->is_or_was} possible from {$a->timeopen} to {$a->timeclose}.';
$string['thxforcompletingall'] = 'Thank you. You have participated in this evaluation for each of your participating courses!';
$string['thxforcompletingcourse'] = 'You have already participated in the evaluation for this course!';
$string['view_after_participating'] = 'You can view results of this evaluation at any time after you have participated for this course!';
$string['no_participation_no_view'] = 'You cannot view results of this course because you have not participated for this course!';
$string['no_part_no_results_site'] = 'You have not participated in this evaluation for any of your courses and therefore do not have the right to view course related results!';
$string['no_part_no_results'] = 'You have not participated in this evaluation and therefore do not have the right to view results!';
$string['no_course_participated'] = 'None of your courses was part of this evaluation';
$string['no_course_participing'] = 'None of your courses is part of this evaluation!';
$string['results_all_evaluated_teachers'] = 'Results for all evaluated teachers';
$string['for_participants'] = 'for participants';
$string['for_teachers'] = 'for teachers';
$string['courses_of'] = 'Courses of';
$string['note'] = 'Note';
$string['show_evaluated_courses_student'] = 'You will only see courses for which you have participated in the evaluation.';
$string['show_evaluated_courses_teacher'] = 'You will only see courses for which submissions have been made';
$string['num_courses_in_ev'] = '{$a->num_courses} of your courses were part of this evaluation.';
$string['submitted_for'] = 'Submitted for';
$string['evaluated'] = 'Submitted';
$string['to_evaluate'] = 'To be submitted';
$string['non_of_your_courses_participated'] = 'None of your courses participated in this evaluation!';
$string['for_you'] = 'for you';
$string['in_your_courses'] = 'in your participating courses';
$string['summer_semester'] = 'Summer semester';
$string['winter_semester'] = 'Winter semester';
// end view.php

// compare_results_inc.php
$string['back'] = 'Back';
$string['analysis_cos'] = 'Evaluation of the degree programs';
$string['reset_selection'] = 'Reset selection';
$string['question_hint'] = 'There are 3 variants of automatically assessed questions: radio and dropdown (single choice) or checkbox (multi choice). For single choice questions, exactly one answer can be selected from several answer options. Multi choice questions allow any selection of answers';
$string['no_questions_for_analysis'] = 'There are neither multi-choice questions nor numerical questions. A statistical evaluation is not possible for this evaluation!';
$string['no_answer'] = 'no answer';
$string['cant_answer'] = 'k.b.';
$string['i_dont_know'] = 'I can\'t answer';
$string['analyzed_sc_questions'] = 'Analyzed single choice questions';
$string['reply_scheme'] = 'Reply scheme';
$string['filter_on_questions'] = 'Filter on questions';
$string['with_reply'] = 'mit Antwort';
$string['change_sort_up_down'] = 'Switch sorting between Ascending and Descending';
$string['change_sort_by'] = 'Sort by submissions or by averages';
$string['click_for_graphics'] = 'Click here to scroll directly to the graphic';
$string['horizontal'] = 'Horizontal';
$string['vertical'] = 'Vertical';
$string['maxgraphs'] = 'maximum number for graphic display';
$string['graphics'] = 'Graphic';
$string['no_minreplies_no_show'] = 'Evaluations for {$a->allSubject} with less than {$a->minReplies} submissions can not be evaluated.';
$string['submissions'] = 'Submissions';
$string['with_minimum'] = 'with at least';
$string['show'] = 'show';
$string['hide'] = 'hide';
$string['toggle_by_minreplies'] = 'Show/hide results with less than {$a->minReplies} submissions';
$string['evaluated_question'] = 'Evaluated question';
$string['all_numquestions'] = 'All {$a->numQuestions} comparable evaluable questions';
$string['this_is_a_multichoice_question'] = 'This is a multi-choice question. Only single-choice answers can be meaningfully evaluated';
$string['apply'] = 'apply';
$string['remove'] = 'remove';
$string['filter_action'] = '{$a->action} <b>Filter</b>';
$string['remove_filter'] = 'Remove filter';
$string['less_minreplies'] = '<span style="color:red;font-weight:bold;">There are <span style="color:red;font-weight:bold;">less than {$a->minReplies} submissions</span> for</span> {$a->ftitle}. <b>Therefore, no evaluation is displayed!</b>';
$string['except_siteadmin'] = ' - siteadmins excluded';
$string['team_teaching'] = 'Team Teaching';
$string['single_submission_per_course'] = 'One submission per participant and course';
$string['this_course_has_numteachers'] = 'This course has {$a->numTeachers}';
$string['all_submissions'] = 'All submissions';
$string['course_participants_info'] = 'This course has {$a->numTeachers} and {$a->numStudents}. {$a->participated} participants have participated in this evaluation. This corresponds to a participation of {$a->evaluated}.';
$string['completed_for_this_teacher'] = '{$a->completed} of the participants have rated this lecturer.';
$string['completed_for_all_teachers'] = '{$a->completed} of the participants have rated all lecturers. ';
$string['submissions_for_course'] = '{$a->numresultsF} of a maximum of {$a->numToDo} submissions have been made. The submission quota is {$a->quote}.';
$string['course_has_no_students'] = 'This course has no student participants.';
$string['no_teamteaching_all_same'] = 'This evaluation has not activated team teaching. In courses with team teaching, all lecturers therefore have the same evaluation.';
$string['analyzed'] = 'Evaluated';
$string['of_total'] = 'of total';
$string['incl_duplicated'] = 'including {$a->duplicated} duplicated submissions';
$string['permitted_cos'] = 'Viewable courses';
$string['all_filtered_submissions'] = 'All filtered submissions {$a->ftitle}';
$string['omitted_submissions'] = '{$a->allSubject} with less than {$a->minReplies} submissions {$a->percentage}';
//$string[''] = '';
//$string[''] = '';
// $string[''] = '';
// end compare_results_inc.php

$string['active_only'] = 'Active only';
$string['AllActivities'] = 'All activities of this Evaluation';
$string['all_courses'] = 'All courses';
$string['all_course_of_studies'] = 'All courses of studiese';
$string['all_teachers'] = 'All teachers';
$string['AllViews'] = 'All Views of this Evaluation';
$string['autoreminders'] = 'Automatically mail reminders';
$string['autoreminders_help'] = 'At the start of the evaluation, every 2 weeks, 4 days before the end of the evaluation. Non-Responders: Weekly';
$string['cannot_participate'] = 'You are not allowed to participate in this evaluation';
$string['crontask'] = "Background task for the evaluation activity";
$string['course'] = 'Course';
$string['courses'] = 'Courses';
$string['coursehasnoteachers'] = 'This course has no teachers';
$string['courses_list'] = 'All courses of this evaluation';
$string['courses_selected'] = 'List of courses to evaluate';
$string['course_of_studies'] = 'Course of studies';
$string['course_of_studies_selected'] = 'List of courses of studies to evaluate';
$string['course_of_studies_list'] = 'All courses of studies of Evaluation semester';
$string['courses_of_studies'] = 'Courses of studies';
$string['courses_with_content_only'] = "Courses with content";
$string['daily_progress'] = 'Daily Evaluation statistics and Evaluation overview';
$string['department'] = 'Department';
$string['departments'] = 'Departments';
$string['docu_download'] = 'Open/Download documentation';
$string['evaluate_now'] = 'Evaluate now';
$string['evaluate_teacher'] = 'Your are now evaluating: <span style="color:darkgreen;font-weight:bolder;">{$a}</span>';
$string['evaluated_courses'] = 'Evaluated courses';
$string['evaluated_teachers'] = 'Evaluated teachers';
$string['evaluation_period'] = 'Evaluation period';
$string['filter_courses'] = 'Select courses instead of courses of studies';
$string['filter_courses_desc'] = 'Select courses instead of courses of studies. (1 id/line)';
$string['filter_by_course_of_studies'] = 'Filter course of studies';
$string['filter_by_teacher'] = 'Filter teacher';
$string['filter_by_department'] = 'Filter department';
$string['filter_course_of_studies_desc'] =
        'Course of studies for this evaluation (1 name/line). Add privileged persons following "||" and separate multiple usernames by commata';
$string['fulllistofstudies'] = 'All course of studies';
$string['fulllistofteachers'] = 'All teachers';
$string['fulllistofdepartments'] = 'All departments';
$string['good_day'] = 'Good day';
$string['global_evaluations'] = 'Global evaluations';
$string['min_results'] = 'Less than {$a} evaluations have been completed. No results displayed to protect privacy!';
$string['min_results_desc'] = 'Privacy: No of evaluations required to show results.';
$string['min_results_text'] = 'Less than {$a} evaluations have been completed. No text results displayed to protect privacy!';
$string['min_results_text_desc'] = 'Privacy: No of evaluations required to show free text results.';
$string['min_results_priv'] = 'Less than {$a} evaluations have been completed. No results displayed to privileged users to protect privacy!';
$string['min_results_priv_desc'] = 'Privacy: No of evaluations required to show results to privileged users.';
$string['no_course_selected'] = "No course for evaluation selectedt!";
$string['no_data'] = "No data found for this view!";
$string['no_permission'] = $string['you_have_no_permission'] = 'You do not have the permisssions required to view this page!';
$string['no_permission_analysis'] = 'You do not have the permissions required to view this analysis!';
$string['no_responses_yet'] = "No evaluations received!";
$string['non_responders_only'] = 'Non responders only';
$string['not_participated'] = 'You haven\'t yet participated for any of your courses.';
$string['not_participated_course'] = 'You haven\'t yet participated for this course.';
$string['open_evaluation'] = 'open Evaluation';
$string['pageviews'] = 'pageviews';
$string['participants'] = 'Participant';
$string['participants'] = 'Participants';
$string['please_participate'] = 'Please participate!';
$string['participating_courses'] = 'Participating courses';
$string['participating_courses_of_studies'] = 'Participating courses of studies';
$string['participant_roles'] = 'Default role(s) of participants';
$string['participant_roles_help'] = 'Enter the default role(s) of evaluation participants. Default role is student. Default settings can be overwritten per evaluation setting.';
$string['privileged_users'] = 'Privileged users';
$string['privileged_users_desc'] = 'Privileged users for this evaluation (1 username/line)';
$string['privileged_users_overview'] = 'Overview of persons privileged to access results of this evaluation';
$string['reminders_sent_at'] = 'Reminders were mailed at:';
$string['send_reminders_to'] = 'Mail reminders to:';
$string['reminders_title'] = 'Reminder mails can be sent by admins or as a scheduled server task only. The note \'NR\' indicates that only students without submissions or lecturers with fewer than three submissions (non-responders) were contacted.';
$string['select_teacher'] = 'Please select a teacher for this evaluation!<br>Note: You can give a separate evaluation of each teacher';
$string['semesters'] = 'Semesters';
$string['sendername'] = 'Reminders: Sender name';
$string['sendermail'] = 'Reminders: Sender mailaddress';
$string['sexes'] = 'Gender';
$string['signature'] = 'Reminders: Signature';
$string['show_on_index'] = 'Show Evaluation on Evaluation index page';
$string['sort_tag'] = 'Tag for sorting Evaluations in overview';
$string['statistic'] = 'Statistic';
$string['statistics'] = 'Statistics';
$string['student'] = 'Student';
$string['students'] = 'Students';
$string['students_only'] = 'Only students coming from course pages can participate in this evaluation!';
$string['submittedEvaluations'] = 'submitted Evaluationss';
$string['teacher'] = 'Teacher';
$string['teachers'] = 'Teachers';
$string['teachers_in_courses'] = 'Teachers in participating courses';
$string['teamteaching'] = 'Team Teaching';
$string['teamteaching_help'] = 'Team Teaching allows one evaluation for each course teachers';
$string['this_evaluation'] = 'This evaluation';
$string['usageReport'] = 'Evaluation Usage Reports';
$string['viewsglobalEvaluationInstances'] = 'overview of semester wide Evaluations';
$string['welcome_text'] = 'Welcome text';
$string['your'] = 'Your';
$string['no_responses_yet'] = 'No responses yet';

// settings for plugin configuration
$string['config_course_of_studies_cat_level'] = 'Course category level for course of Studies';
$string['config_course_of_studies_cat_field'] = 'Course category field identifier for course of Studies';
$string['config_semester_cat_level'] = 'Course category level for semester';
$string['config_course_semester_field'] = 'Course field identifier for semester';
$string['config_summer_semester'] = 'Summer semester months';
// end settings for plugin configuration

// partly from Moodle 3.10 evaluation
$string['add_item'] = 'Add question';
$string['add_pagebreak'] = 'Add a page break';
$string['adjustment'] = 'Adjustment';
$string['after_submit'] = 'After submission';
$string['allowfullanonymous'] = 'Allow full anonymous';
$string['analysis'] = 'Analysis';
$string['analysis_course'] = 'Analysis course';
$string['analysis_own_courses'] = 'Analysis own courses';
$string['analysis_all_courses'] = 'Analysis all courses';
$string['analysis_own_cos'] =  'Analysis own courses of studies';
$string['anonymous'] = 'Anonymous';
$string['anonymous_edit'] = 'Record user names';
$string['anonymous_entries'] = 'Anonymous entries ({$a})';
$string['anonymous_user'] = 'Anonymous user';
$string['answerquestions'] = 'Answer the questions';
$string['append_new_items'] = 'Append new items';
$string['autonumbering'] = 'Auto number questions';
$string['autonumbering_help'] = 'Enables or disables automated numbers for each question';
$string['average'] = 'Average';
$string['bold'] = 'Bold';
$string['calendarend'] = '{$a} closes';
$string['calendarstart'] = '{$a} opens';
$string['cannotaccess'] = 'You can only access this evaluation from a course';
$string['cannotsavetempl'] = 'Saving templates is not allowed';
$string['captcha'] = 'Captcha';
$string['captchanotset'] = 'Captcha hasn\'t been set.';
$string['check'] = 'Multiple choice - multiple answers';
$string['check_values'] = 'Possible responses';
$string['checkbox'] = 'Multiple choice - multiple answers allowed (check boxes)';
$string['choosefile'] = 'Choose a file';
$string['chosen_evaluation_response'] = 'Chosen evaluation response';
$string['closebeforeopen'] = 'You have specified an end date before the start date.';
$string['completed_evaluations'] = 'Submitted evaluations';
$string['complete_the_form'] = 'Answer the questions';
$string['completed'] = 'Completed';
$string['completedon'] = 'Completed on {$a}';
$string['completionsubmit'] = 'View as completed if the evaluation is submitted';
$string['configallowfullanonymous'] = 'If set to \'yes\', users can complete a evaluation activity on the front page without being required to log in.';
$string['confirmdeleteentry'] = 'Are you sure you want to delete this entry?';
$string['confirmdeleteitem'] = 'Are you sure you want to delete this element?';
$string['confirmdeletetemplate'] = 'Are you sure you want to delete this template?';
$string['confirmusetemplate'] = 'Are you sure you want to use this template?';
$string['continue_the_form'] = 'Continue answering the questions';
$string['count_of_nums'] = 'Count of numbers';
$string['course'] = 'Course';
$string['courseid'] = 'Course ID';
$string['creating_templates'] = 'Save these questions as a new template';
$string['delete_entry'] = 'Delete entry';
$string['delete_item'] = 'Delete question';
$string['delete_old_items'] = 'Delete old items';
$string['delete_pagebreak'] = 'Delete page break';
$string['delete_template'] = 'Delete template';
$string['delete_templates'] = 'Delete template...';
$string['depending'] = 'Dependencies';
$string['depending_help'] = 'It is possible to show an item depending on the value of another item.<br />
<strong>Here is an example.</strong><br />
<ul>
<li>First, create an item on which another item will depend on.</li>
<li>Next, add a pagebreak.</li>
<li>Then add the items dependant on the value of the item created before. Choose the item from the list labelled "Dependence item" and write the required value in the textbox labelled "Dependence value".</li>
</ul>
<strong>The item structure should look like this.</strong>
<ol>
<li>Item Q: Do you have a car? A: yes/no</li>
<li>Pagebreak</li>
<li>Item Q: What colour is your car?<br />
(this item depends on item 1 with value = yes)</li>
<li>Item Q: Why don\'t you have a car?<br />
(this item depends on item 1 with value = no)</li>
<li> ... other items</li>
</ol>';
$string['dependitem'] = 'Dependence item';
$string['dependvalue'] = 'Dependence value';
$string['description'] = 'Description';
$string['do_not_analyse_empty_submits'] = 'Do not analyse empty submits';
$string['downloadresponseas'] = 'Download all responses as:';
$string['drop_evaluation'] = 'Remove from this course';
$string['dropdown'] = 'Multiple choice - single answer allowed (drop-down menu)';
$string['dropdownlist'] = 'Multiple choice - single answer (drop-down menu)';
$string['dropdownrated'] = 'Drop-down menu (rated)';
$string['dropdown_values'] = 'Answers';
$string['drop_evaluation'] = 'Remove from this course';
$string['edit_item'] = 'Edit question';
$string['edit_items'] = 'Edit questions';
$string['email_notification'] = 'Enable notification of submissions';
$string['email_notification_help'] = 'If enabled, teachers will receive notification of evaluation submissions.';
$string['emailteachermail'] = '{$a->username} has completed evaluation activity: \'{$a->evaluation}\' You can view it here: {$a->url}';
$string['emailteachermailhtml'] = '<p>{$a->username} has completed evaluation activity : <i>\'{$a->evaluation}\'</i>.</p><p>It is <a href="{$a->url}">available on the site</a>.</p>';
$string['entries_saved'] = 'Your answers have been saved. Thank you.';
$string['eventresponsedeleted'] = 'Response deleted';
$string['eventresponsesubmitted'] = 'Response submitted';
$string['export_questions'] = 'Export questions';
$string['export_to_excel'] = 'Export to Excel';
$string['ev_end_msg'] = 'Evaluation period ends in {$a->ev_end_msg}';
$string['evaluation:addinstance'] = 'Add a new evaluation';
$string['evaluation:complete'] = 'Complete a evaluation';
$string['evaluation:createprivatetemplate'] = 'Create private template';
$string['evaluation:createpublictemplate'] = 'Create public template';
$string['evaluation:deletesubmissions'] = 'Delete completed submissions';
$string['evaluation:deletetemplate'] = 'Delete template';
$string['evaluation:edititems'] = 'Edit items';
$string['evaluation:mapcourse'] = 'Map courses to global evaluations';
$string['evaluation:receivemail'] = 'Receive email notification';
$string['evaluation:view'] = 'View a evaluation';
$string['evaluation:viewanalysepage'] = 'View the analysis page after submit';
$string['evaluation:viewreports'] = 'View reports';
$string['evaluation_is_not_for_anonymous'] = 'Evaluation is not for anonymous';
$string['evaluation_is_not_open'] = 'The Evaluation is not open';
$string['evaluationclose'] = 'Allow answers to';
$string['evaluationcompleted'] = '{$a->username} completed {$a->evaluationname}';
$string['evaluationopen'] = 'Allow answers from';
$string['file'] = 'File';
$string['filter_by_course'] = 'Filter by course';
$string['handling_error'] = 'Error occurred in evaluation module action handling';
$string['hide_no_select_option'] = 'Hide the "Not selected" option';
$string['import_questions'] = 'Import questions';
$string['import_successfully'] = 'Import successfully';
$string['importfromthisfile'] = 'Import from this file';
$string['includeuserinrecipientslist'] = 'Include {$a} in the list of recipients';
$string['indicator:cognitivedepth'] = 'Evaluation cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a Evaluation activity.';
$string['indicator:cognitivedepthdef'] = 'Evaluation cognitive';
$string['indicator:cognitivedepthdef_help'] = 'The participant has reached this percentage of the cognitive engagement offered by the Evaluation activities during this analysis interval (Levels = No view, View, Submit)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Evaluation social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a Evaluation activity.';
$string['indicator:socialbreadthdef'] = 'Evaluation social';
$string['indicator:socialbreadthdef_help'] = 'The participant has reached this percentage of the social engagement offered by the Evaluation activities during this analysis interval (Levels = No participation, Participant alone, Participant with others)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['info'] = 'Information';
$string['infotype'] = 'Information type';
$string['insufficient_responses_for_this_group'] = 'There are insufficient responses for this group';
$string['insufficient_responses'] = 'insufficient responses';
$string['insufficient_responses_help'] = 'For the evaluation to be anonymous, there must be at least 2 responses.';
$string['item_label'] = 'Label';
$string['item_name'] = 'Question';
$string['label'] = 'Label';
$string['labelcontents'] = 'Contents';
$string['mapcourse'] = 'Map evaluation to courses';
$string['mapcourse_help'] = 'By default, evaluation forms created on your homepage are available site-wide
and will appear in all courses using the evaluation block. You can force the evaluation form to appear by making it a sticky block or limit the courses in which a evaluation form will appear by mapping it to specific courses.';
$string['mapcourseinfo'] = 'This is a site-wide evaluation that is available to all courses using the evaluation block. You can however limit the courses to which it will appear by mapping them. Search the course and map it to this evaluation.';
$string['mapcoursenone'] = 'No courses mapped. Evaluation available to all courses';
$string['mapcourses'] = 'Map evaluation to courses';
$string['mappedcourses'] = 'Mapped courses';
$string['mappingchanged'] = 'Course mapping has been changed';
$string['maximal'] = 'Maximum';
$string['messageprovider:message'] = 'Evaluation reminder';
$string['messageprovider:submission'] = 'Evaluation notifications';
$string['minimal'] = 'Minimum';
$string['mode'] = 'Mode';
$string['modulename'] = 'Evaluation';
$string['modulename_help'] = 'The evaluation activity module enables a teacher to create a custom survey for collecting evaluation from participants using a variety of question types including multiple choice, yes/no or text input.
Evluation responses may be anonymous if desired, and results may be shown to all participants or restricted to teachers only. Any evaluation activities on the site front page may also be completed by non-logged-in users.

Evaluation activities may be used

* Evaluations of teachers by students
* Evaluations of teachers
* For course evaluations, helping improve the content for later participants
* To enable participants to sign up for course modules, events etc.
* For anti-bullying surveys in which students can report incidents anonymously';
$string['modulename_link'] = 'mod/evaluation/view';
$string['modulenameplural'] = 'Evaluations';
$string['move_item'] = 'Move this question';
$string['multichoice'] = 'Multiple choice';
$string['multichoiceoption'] = '<span class="weight">({$a->weight}) </span>{$a->name}';
$string['multichoicerated'] = 'Multiple choice (rated)';
$string['multichoicetype'] = 'Multiple choice type';
$string['multichoice_values'] = 'Multiple choice values';
$string['multiplesubmit'] = 'Allow multiple submissions';
$string['multiplesubmit_help'] = 'If enabled for anonymous surveys, users can submit evaluation an unlimited number of times.';
$string['name'] = 'Name';
$string['name_required'] = 'Name required';
$string['nameandlabelformat'] = '({$a->label}) {$a->name}';
$string['next_page'] = 'Next page';
$string['no_handler'] = 'No action handler exists for';
$string['no_itemlabel'] = 'No label';
$string['no_itemname'] = 'No itemname';
$string['no_items_available_yet'] = 'No questions have been set up yet';
$string['no_templates_available_yet'] = 'No templates available yet';
$string['non_anonymous'] = 'User\'s name will be logged and shown with answers';
$string['non_anonymous_entries'] = 'Non anonymous entries ({$a})';
$string['non_respondents_students'] = 'Non-respondent students ({$a})';
$string['not_completed_yet'] = 'Not completed yet';
$string['not_started'] = 'Not started';
$string['not_selected'] = 'Not selected';
$string['numberoutofrange'] = 'Number out of range';
$string['numeric'] = 'Numeric answer';
$string['numeric_range_from'] = 'Range from';
$string['numeric_range_to'] = 'Range to';
$string['of'] = 'of';
$string['oldvaluespreserved'] = 'All old questions and the assigned values will be preserved';
$string['oldvalueswillbedeleted'] = 'Current questions and all responses will be deleted.';
$string['only_one_captcha_allowed'] = 'Only one captcha is allowed in a evaluation';
$string['openafterclose'] = 'You have specified an open date after the close date';
$string['overview'] = 'Overview';
$string['page'] = 'Page';
$string['page-mod-evaluation-x'] = 'Any evaluation module page';
$string['page_after_submit'] = 'Completion message';
$string['pagebreak'] = 'Page break';
$string['pluginadministration'] = 'Evaluation administration';
$string['pluginname'] = 'Evaluation';
$string['position'] = 'Position';
$string['previous_page'] = 'Previous page';
$string['privacy:metadata:completed'] = 'A record of the submissions to the evaluation';
$string['privacy:metadata:completed:anonymousresponse'] = 'Whether the submission is to be used anonymously.';
$string['privacy:metadata:completed:timemodified'] = 'The time when the submission was last modified.';
$string['privacy:metadata:completed:userid'] = 'The ID of the user who completed the evaluation activity.';
$string['privacy:metadata:completedtmp'] = 'A record of the submissions which are still in progress.';
$string['privacy:metadata:value'] = 'A record of the answer to a question.';
$string['privacy:metadata:value:value'] = 'The chosen answer.';
$string['privacy:metadata:valuetmp'] = 'A record of the answer to a question in a submission in progress.';
$string['public'] = 'Public';
$string['question'] = 'Question';
$string['questionandsubmission'] = 'Question and submission settings';
$string['questions'] = 'Questions';
$string['questionslimited'] = 'Showing only {$a} first questions, view individual answers or download table data to view all.';
$string['radio'] = 'Multiple choice - single answer';
$string['radio_values'] = 'Responses';
$string['ready_evaluations'] = 'Ready evaluations';
$string['required'] = 'Required';
$string['resetting_data'] = 'Reset evaluation responses';
$string['resetting_evaluations'] = 'Resetting evaluations';
$string['response_nr'] = 'Response number';
$string['responses'] = 'Responses';
$string['responsetime'] = 'Responses time';
$string['save_as_new_item'] = 'Save as new question';
$string['save_as_new_template'] = 'Save as new template';
$string['save_entries'] = 'Submit this evaluation';
$string['save_entries_help'] = 'Your answers will only be saved and thus submitted once you have submitted this evaluation';
$string['save_item'] = 'Save question';
$string['saving_failed'] = 'Saving failed';
$string['search:activity'] = 'Evaluation - activity information';
$string['search_course'] = 'Search course';
$string['searchcourses'] = 'Search courses';
$string['searchcourses_help'] = 'Search for the code or name of the course(s) that you wish to associate with this evaluation.';
$string['selected_dump'] = 'Selected indexes of $SESSION variable are dumped below:';
$string['send'] = 'Send';
$string['send_message'] = 'Send notification';
$string['show_all'] = 'Show all';
$string['show_analysepage_after_submit'] = 'Show analysis page';
$string['show_entries'] = 'Show responses';
$string['show_entry'] = 'Show response';
$string['show_nonrespondents'] = 'Show non-respondents';
$string['site_after_submit'] = 'Site after submit';
$string['sort_by_course'] = 'Sort by course';
$string['started'] = 'Started';
$string['startedon'] = 'Started on {$a}';
$string['subject'] = 'Subject';
$string['switch_item_to_not_required'] = 'Set as not required';
$string['switch_item_to_required'] = 'Set as required';
$string['template'] = 'Template';
$string['templates'] = 'Templates';
$string['template_deleted'] = 'Template deleted';
$string['template_saved'] = 'Template saved';
$string['textarea'] = 'Longer text answer';
$string['textarea_height'] = 'Number of lines';
$string['textarea_width'] = 'Width';
$string['textfield'] = 'Short text answer';
$string['textfield_maxlength'] = 'Maximum characters accepted';
$string['textfield_size'] = 'Textfield width';
$string['there_are_no_settings_for_recaptcha'] = 'There are no settings for captcha';
$string['this_evaluation_is_already_submitted'] = 'You\'ve already completed this activity.';
$string['typemissing'] = 'Missing value "type"';
$string['update_item'] = 'Save changes to question';
$string['url_for_continue'] = 'Link to next activity';
$string['url_for_continue_help'] =
        'After submitting the evaluation, a continue button is displayed, which links to the course page. Alternatively, it may link to the next activity if the URL of the activity is entered here.';
$string['use_one_line_for_each_value'] = 'Use one line for each answer!';
$string['use_this_template'] = 'Use this template';
$string['using_templates'] = 'Use a template';

