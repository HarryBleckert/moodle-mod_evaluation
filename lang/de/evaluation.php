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
 * Strings for component 'evaluation', language 'de', branch 'MOODLE_405_STABLE'
 *
 * @package     mod_evaluation
 * @category    string
 * @copyright 1999 onwards Martin Dougiamas  {@link https://moodle.com}
 * @copyright 2021 onwards Harry Bleckert for ASH Berlin  {@link https://ash-berlin.eu}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// $string[''] = '';
$string['language'] = 'de';

// index.php
$string['index_group_by_tag'] = 'Gruppe';
// end index.php

// mod_form.php
$string['participant_roles'] = 'Teilnehmende Rollen(n)';
$string['participant_roles_help'] = 'Wählen Sie in welchen kursbezogenen Rollen die Teilnahme möglich ist (Standard: Student_in';
$string['role_is_required'] = 'Es muss mindestens eine Rolle für Teilnehmer_innen gewählt werden!';

// print.php
$string['analysis_of_logs'] = 'Auswertung des Moodle Logs. Log Daten werden {$a->logexpiry} Tage aufbewahrt.';
$string['activities_per_day'] = 'Aktivitäten/Tag';
$string['median'] = 'Median';
$string['modus'] = 'Modus';
$string['numactivitydays'] = 'Aktivitäten gab es an <b>{$a->numresults} Tagen</b>';
$string['total_activities'] = 'Summe Aktivitäten';
$string['results_for_all_evaluated_teachers'] = 'Ergebnisse für alle evaluierten Dozent_innen';
$string['results_for_all_participating_courses'] = 'Ergebnisse für alle teilnehmenden Kurse';
$string['you_havent_yet_participated_for_this_course'] = 'Sie haben für diesen Kurs noch nicht teilgenommen. <b>Bitte beteiligen Sie sich!</b>';
$string['i_want_participate_now'] = 'Ich will mich jetzt beteiligen!';
$string['back_to_course'] = 'Zurück zum Kurs';
$string['prognosis'] = 'Prognose';
$string['remaining_days'] = 'Es verbleiben {$a->remaining_days} Tage Laufzeit';
$string['submissions_per_day'] = 'Abgaben pro Tag';
$string['numsubmissiondays'] = 'Abgaben erfolgten an <b>{$a->numresults} Tagen</b>';
$string['completed_responses'] = 'Summe Abgaben';
// end print.php

// send reminders
// $string[''] = '';
$string['sent_reminders_info'] = 'Hinweismails wurden an {$a->role} versandt';
$string['john_doe'] = 'John Doe';
$string['send_reminders_noreplies_teachers'] = 'Nur an Lehrende, für die bisher weniger als {$a->min_results_text} Abgaben gemacht wurden.';
$string['send_reminders_noreplies_students'] = 'Nur an Studierende, die bisher noch nicht an der Evaluation teilgenommen haben.';
$string['send_reminders_pmsg'] = 'Heute wurden Mails mit Hinweisen zur laufenden Evaluation an alle {$a->role} versandt, deren Kurse an der Evaluation teilnehmen.<br><b>Unten sehen Sie ein Beispiel</b>. ';
$string['send_reminders_remaining'] = 'nur noch {$a->remaining_evaluation_days} Tage bis zum {$a->lastEvaluationDay}';
$string['send_reminders_students'] = '{$a->testmsg}<p>Guten Tag {$a->fullname}</p>
<p>Bitte beteiligen {$a->also} Sie sich an der {$a->reminder} laufenden Evaluation<br>
Die Befragung erfolgt anonym und dauert nur wenige Minuten pro Kurs und Dozent_in.<br>
Für jeden bereits von Ihnen evaluierten Kurs können Sie selbst sofort die Auswertung einsehen, wenn mindestens {$a->minResults} Abgaben erfolgt sind.<br>
Ausgenommen sind aus Datenschutzgründen die persönlichen Angaben, sowie die Antworten auf die offenen Fragen.
</p>
<p><b>Mit Ihrer Teilnahme tragen Sie dazu bei die Lehre zu verbessern!</b></p>
<p>Hier eine Übersicht Ihrer Kurse, die an der 
<a href="{$a->evUrl}"><b>{$a->ev_name}</b></a> teilnehmen:</p>
{$a->myCourses}
<p style="margin-bottom: 0cm">Mit besten Grüßen<br>
{$a->signature}
{$a->signum}</p>';

$string['send_reminders_teachers'] = '{$a->testmsg}<p>Guten Tag {$a->fullname}</p>
{$a->onlyfew}
<p>Bitte motivieren Sie Ihre Studierenden an der {$a->reminder} laufenden Evaluation teilzunehmen<br>
Optimal wäre es, wenn Sie die Teilnahme jeweils in Ihre Veranstaltungen integrieren, indem Sie dafür einen motivierenden Aufruf machen und den 
Studierenden während der Veranstaltung die wenigen Minuten Zeit zur Teilnahme geben!</p>
<p>Sofern für einen Ihrer Kurse mindestens {$a->minResults} Abgaben <b>für Sie</b> vorliegen, können Sie jeweils die Auswertung der für Sie gemachten Abgaben einsehen.<br>
Nur wenn mindestens {$a->min_results_text} Abgaben für Sie gemacht wurden, können Sie auch selbst die Textantworten einsehen</p>
<p>Hier eine Übersicht Ihrer Kurse, die an der 
<a href="{$a->evUrl}"><b>{$a->ev_name}</b></a> teilnehmen:</p>
{$a->myCourses}
<p style="margin-bottom: 0cm">Mit besten Grüßen<br>
{$a->signature}
{$a->signum}</p>';

$string['send_reminders_no_replies'] = 'Keine Ihrer {$a->distinct_s} Studierenden hat bisher teilgenommen. ';
$string['send_reminders_few_replies'] = 'Bisher gibt es nur {$a->replies} {$a->submissions} Ihrer {$a->distinct_s} Studierenden. ';
$string['send_reminders_many_replies'] = 'Bisher gibt es {$a->replies} Abgaben Ihrer {$a->distinct_s} Studierenden';
$string['send_reminders_privileged'] = 'Sie erhalten diese Mail zur Kenntnisnahme, da Sie für diese Evaluation zur Einsicht in die Auswertungen berechtigt sind.';
// end send reminders

// translator function, Magik worked for Berthe
$string['evaluation_of_courses'] = 'Evaluation der Lehrveranstaltungen';
$string['by_students'] = 'durch Studierende';
$string['of_'] = 'des';
$string['for_'] = 'für das';
$string['sose_'] = 'Sommersemester';
$string['wise_'] = 'Wintersemester';
// end translator function

// graph lib
$string['show_graphic_data'] = 'Grafikdaten anzeigen';
$string['hide_graphic_data'] = 'Grafikdaten verbergen';
// end graph lib

// view.php
$string['in'] = 'in';
$string['is'] = 'ist';
$string['days'] = 'Tage';
$string['today'] = 'heute';
$string['was'] = 'war';
$string['teamteachingtxt'] = 'In Seminaren mit Team Teaching werden die Dozent_innen jeweils einzeln evaluiert.';
$string['is_WM_disabled'] = 'Ausgenommen sind Weiterbildende Master Studiengänge.';
$string['siteadmintxt'] = 'Administrator und daher';
$string['andrawdata'] =  'und Rohdaten';
$string['yourcos'] = 'Ihrer Studiengänge';
$string['viewanddownload'] = 'einsehen und herunterladen sobald {$a->minresultspriv} Abgaben vorliegen.';
$string['privilegestxt'] = 'Als {$a->siteadmintxt} für diese Evaluation privilegierte Person können Sie alle Auswertungen {$a->andrawdata}';
$string['courseparticipants'] = 'Dieser Kurs hat {$a->numteachers} Dozent_in und {$a->numstudents} studentische Teilnehmer_innen.';
$string['participantsandquota'] = 'Teilnehmer_innen haben sich an dieser Evaluation beteiligt. Das entspricht einer Beteiligung von {$a->evaluated}.';
$string['quotaevaluatedall'] = 'der Teilnehmer_innen haben alle Dozent_innen bewertet.';
$string['quotaevaluatedteacher'] = 'der Teilnehmer_innen haben diese Dozent_in bewertet.';
$string['coursequota'] = 'Es wurden {$a->completed_responses} von maximal {$a->numToDo} Abgaben gemacht. Die Abgabequote beträgt {$a->quote}.';
$string['nostudentsincourse'] = 'Dieser Kurs hat keine studentischen Teilnehmer_innen.';
$string['questionaireenglish'] = 'Hier ist eine englische Übersetzung des Fragebogens.';
$string['clickquestionaireenglish'] = '<b>Click here</b> to open an English translation of the questionnaire';
$string['also'] = 'auch';
$string['foryourcourses'] = 'für jeden Ihrer Kurse';
$string['msg_student_all_courses'] = 'Bitte beteiligen {$a->also} Sie sich {$a->foryourcourses} an dieser Evaluation. Die Befragung erfolgt anonym und dauert nur wenige Minuten pro Kurs.<br>Klicken Sie unten für jeden Ihrer noch nicht evaluierten Kurse auf \'<b>Jetzt evaluieren</b>\' 
                und füllen Sie dann jeweils den Fragebogen aus.';
$string['yourevaluationhelps'] = 'Ihre Evaluation ist uns eine große Hilfe!';
$string['resultconditions'] = 'Für jeden bereits von Ihnen evaluierten Kurs können Sie die Auswertung einsehen, sobald {$a->minresults} Abgaben vorliegen.';
$string['yourpartcourses'] = 'Sie haben Kurse, die an dieser Evaluation teilnehmen. Bitte motivieren Sie die Studierenden zur Teilnahme';
$string['yourpastpartcourses'] = 'Sie haben Kurse, die an dieser Evaluation teilgenommen haben';
$string['teachersviewconditions'] = 'Für Ihre eigenen Kurse können Sie die Auswertung einsehen, sobald {$a->minresults} Abgaben vorliegen. 
                            Ab {$a->minresultstxt} Abgaben können Sie auch die Textantworten einsehen.';
$string['evaluationalert'] = 'Nur noch {$a->daysleft} Tage bis zum Ende der Abgabefrist!';
$string['show_active_only'] = 'Bereinigt: Nur Teilnehmer_innen, die während der Laufzeit der Evaluation Moodle nutzten';
$string['onefeedbackperteacher'] = 'Je eine Abgabe pro Dozent_in ist aktiviert?';
$string['teamteachingcourses'] = 'Kurse mit Team Teaching';
$string['duplicatedfeedbacks'] = 'Duplizierte Abgaben';
$string['logananalysis'] = 'Auswertung des Moodle Logs. Log Daten werden {$a->loglifetime} Tage aufbewahrt.';
$string['currentday'] = 'Heute ist <b>Tag {$a->currentday} {$a->currentday_percent}';
$string['not_anonymous'] = 'Nicht Anonym';
$string['evaluationperiod'] = 'Die Teilnahme an der Evaluation {$a->is_or_was} vom {$a->timeopen} bis zum {$a->timeclose} möglich.';
$string['thxforcompletingall'] = 'Vielen Dank. Sie haben für jeden Ihrer teilnehmenden Kurse an dieser Evaluation teilgenommen!';
$string['thxforcompletingcourse'] = 'Sie haben für diesen Kurs bereits an der Evaluation teilgenommen!';
$string['view_after_participating'] = 'Sie können Auswertungen dieser Evaluation jederzeit einsehen, nachdem Sie selbst für diesen Kurs daran teilgenommen haben!';
$string['no_participation_no_view'] = 'Sie können Auswertungen dieses Kurses nicht einsehen, weil Sie selbst für diesen Kurs nicht an der Evaluation teilgenommen habern!';
$string['no_part_no_results_site'] = 'Sie haben für keinen Ihrer Kurse an dieser Evaluation teilgenommen und haben daher nicht das Recht kursbezogene Auswertungen einzusehen!';
$string['no_part_no_results'] = 'Sie haben nicht an dieser Evaluation teilgenommen und haben daher nicht das Recht Auswertungen einzusehen!';
$string['no_course_participated'] = 'Keiner Ihrer Kurse war Teil dieser Evaluation';
$string['no_course_participing'] = 'Keiner Ihrer Kurse ist Teil dieser Evaluation!';
$string['results_all_evaluated_teachers'] = 'Ergebnisse für alle evaluierten Dozent_innen';
$string['for_participants'] = 'für Teilnehmer_innen';
$string['for_teachers'] = 'für Dozent_innen';
$string['courses_of'] = 'Kurse der';
$string['note'] = 'Hinweis';
$string['show_evaluated_courses_student'] = 'Ihnen werden nur Kurse angezeigt, für die Sie an der Evaluation teilgenommen haben.';
$string['show_evaluated_courses_teacher'] = 'Ihnen werden nur Kurse angezeigt, für die Abgaben erfolgt sind';
$string['num_courses_in_ev'] = '{$a->num_courses} Ihrer Kurse waren Teil dieser Evaluation.';
$string['submitted_for'] = 'Abgegeben für';
$string['evaluated'] = 'Abgegeben';
$string['to_evaluate'] = 'Abzugeben';
$string['non_of_your_courses_participated'] = 'Keiner Ihrer Kurse nahm an dieser Evaluation teil!';
$string['for_you'] = 'für Sie';
$string['in_your_courses'] = 'in Ihren teilnehmenden Kursen';
$string['summer_semester'] = 'Sommersemester';
$string['winter_semester'] = 'Wintersemester';
// end view.php

// compare_results_inc.php
$string['back'] = 'Zurück';
$string['analysis_cos'] = 'Auswertungen der Studiengänge';
$string['reset_selection'] = 'Auswahl zurücksetzen';
$string['question_hint'] = 'Es gibt 3 Varianten von automatisch bewertbaren Fragen: Radio und Dropdown (Single Choice) oder Checkbox (Multi Choice). Bei Single Choice Fragen kann aus mehreren Antwortoptionen genau eine Antwort ausgewählt werden. Multi Choice Fragen erlauben eine beliebige Auswahl von Antworten';
$string['no_questions_for_analysis'] = 'Es gibt weder Multichoice Fragen noch numerische Fragen. Eine statistische Auswertung ist für diese Evaluation nicht möglich!';
$string['no_answer'] = 'keine Angabe';
$string['cant_answer'] = 'k.b.';
$string['i_dont_know'] = 'Kann ich nicht beantworten';
$string['analyzed_sc_questions'] = 'Ausgewertete Single Choice Fragen';
$string['reply_scheme'] = 'Antwort - Schema';
$string['filter_on_questions'] = 'Filter auf Fragen';
$string['with_reply'] = 'mit Antwort';
$string['change_sort_up_down'] = 'Sortierung zwischen Aufsteigend und Absteigend wechseln';
$string['change_sort_by'] = 'Sortierung nach Abgaben oder nach Mittwlwerten';
$string['click_for_graphics'] = 'Hier Klicken um direkt zur Grafik zu scrollen';
$string['horizontal'] = 'Horizontal';
$string['vertical'] = 'Vertikal';
$string['maxgraphs'] = 'maximale Anzahl für die grafische Anzeige';
$string['graphics'] = 'Grafik';
$string['no_minreplies_no_show'] = 'Evaluationen für {$a->allSubject} mit weniger als {$a->minReplies} Abgaben dürfen nicht ausgewertet werden.';
$string['submission'] = 'Abgabe';
$string['submissions'] = 'Abgaben';
$string['with_minimum'] = 'mit mindestens';
$string['show'] = 'anzeigen';
$string['hide'] = 'verbergen';
$string['toggle_by_minreplies'] = 'Ergebnisse mit weniger als {$a->minReplies} Abgaben anzeigen/verbergen';
$string['evaluated_question'] = 'Ausgewertete Frage';
$string['all_numquestions'] = 'Alle {$a->numQuestions} vergleichbar auswertbaren Fragen';
$string['this_is_a_multichoice_question'] = 'Dies ist eine Multi Choice Frage. Es können nur Single Choice Antworten sinnvoll ausgwertet werden';
$string['apply'] = 'anwenden';
$string['remove'] = 'entfernen';
$string['filter_action'] = '<b>Filter</b> {$a->action}';
$string['remove_filter'] = 'Filter entfernen';
$string['less_minreplies'] =
        '<span style="color:#000000;font-weight:bold;">Es gibt für</span>  {$a->ftitle} <span style="color:#000000;font-weight:bold;">weniger als {$a->minReplies} Abgaben</span>. <b>Daher wird keine Auswertung angezeigt!</b>';
$string['except_siteadmin'] = ' - ausgenommen sind Admins';
$string['team_teaching'] = 'Team Teaching';
$string['single_submission_per_course'] = 'Eine Abgabe pro Teilnehmer_in und Kurs';
$string['this_course_has_numteachers'] = 'Dieser Kurs hat {$a->numTeachers}';
$string['all_submissions'] = 'Alle Abgaben';
$string['course_participants_info'] = 'Dieser Kurs hat {$a->numTeachers} und {$a->numStudents}. {$a->participated} Teilnehmer_innen haben sich an dieser Evaluation beteiligt. Das entspricht einer Beteiligung von {$a->evaluated}.';
$string['completed_for_this_teacher'] = '{$a->completed} der Teilnehmer_innen haben diese Dozent_in bewertet.';
$string['completed_for_all_teachers'] = '{$a->completed} der Teilnehmer_innen haben alle Dozent_innen bewertet. ';
$string['submissions_for_course'] = 'Es wurden {$a->numresultsF} von maximal {$a->numToDo} Abgaben gemacht. Die Abgabequote beträgt {$a->quote}.';
$string['course_has_no_students'] = 'Dieser Kurs hat keine studentischen Teilnehmer_innen.';
$string['no_teamteaching_all_same'] = 'Diese Evaluation hat kein Team Teaching aktiviert. In Kursen mit Team Teaching haben daher alle Dozent_innen dieselbe Auswertung.';
$string['analyzed'] = 'Ausgewertete';
$string['of_total'] = 'von insgesamt';
$string['incl_duplicated'] = 'inkl. {$a->duplicated} duplizierter Abgaben ';
$string['permitted_cos'] = 'Einsehbare Studiengänge';
$string['all_filtered_submissions'] = 'Alle gefilterten Abgaben {$a->ftitle}';
$string['omitted_submissions'] = '{$a->allSubject} mit weniger als {$a->minReplies} Abgaben {$a->percentage}';
//$string[''] = '';
//$string[''] = '';
// $string[''] = '';
// end compare_results_inc.php

$string['active_only'] = 'Nur Aktive';
$string['AllActivities'] = 'Alle Aktivitäten dieser Evaluation';
$string['all_courses'] = 'Alle Kurse';
$string['all_course_of_studies'] = 'Alle Studiengänge';
$string['all_teachers'] = 'Alle Dozent_innen';
$string['AllViews'] = 'Alle Ansichten dieser Evaluation';
$string['autoreminders'] = 'Erinnerungen automatisch per Mail senden';
$string['autoreminders_help'] = 'Zu Beginn der Evaluation, alle 2 Wochen, 4 Tage vor Ende der Evaluation. Non-Responders: wöchentlich';
$string['cannot_participate'] = 'Sie können an dieser Evaluation nicht selbst teilnehmen';
$string['crontask'] = "Hintergrundprozess für die Aktivität Evaluation";
$string['course'] = 'Kurs';
$string['courses'] = 'Kurse';
$string['coursehasnoteachers'] = 'Dieser Kurs hat keine Dozent_innen"';
$string['courses_list'] = 'Alle Kurse dieser Evaluation';
$string['courses_selected'] = 'Liste der zu evaluierenden Kurse';
$string['course_of_studies'] = 'Studiengang';
$string['courses_of_studies'] = 'Studiengänge';
$string['course_of_studies_list'] = 'Alle Studiengänge des Semesters';
$string['course_of_studies_selected'] = 'Liste der zu evaluierenden Studiengänge';
$string['courses_with_content_only'] = "Nur genutzte Kurse";
$string['daily_progress'] = 'Abgabestatistik -pro Tag und über den gesamten Zeitraum';
$string['department'] = 'Fachbereich';
$string['departments'] = 'Fachbereiche';
$string['docu_download'] = 'Dokumentation öffnen/herunterladen';
$string['evaluate_now'] = 'Jetzt evaluieren';
$string['evaluate_teacher'] = 'Sie evaluieren jetzt: <span style="color:darkgreen;font-weight:bolder;">{$a}</span>';
$string['evaluated_courses'] = 'Evaluierte Kurse';
$string['evaluated_teachers'] = 'Evaluierte Dozent_innen';
$string['evaluation_period'] = 'Laufzeit der Evaluation';
$string['filter_courses'] = 'Auswahl von Kursen anstelle von Studiengängen';
$string['filter_courses_desc'] = 'Einzelne Kurse anstelle von Studiengängen. (1 Kurs-ID/Zeile)';
$string['filter_by_course_of_studies'] = 'Filter Studiengang';
$string['filter_by_teacher'] = 'Filter Dozent_in';
$string['filter_by_department'] = 'Filter Fachbereich';
$string['filter_course_of_studies_desc'] =
        'Studiengänge dieser Evaluation (je 1 Studiengang/Zeile). Privilegierte Personen können über Anmeldenamen nach Eingabe von "||" als Trennzeichen gesetzt werden. Falls es mehrere Personen sind, sind die Anmeldenamen durch Kommata zu trennen.';
$string['fulllistofstudies'] = 'Alle Studiengänge';
$string['fulllistofteachers'] = 'Alle Dozent_innen';
$string['fulllistofdepartments'] = 'Alle Fachbereiche';
$string['good_day'] = 'Guten Tag';
$string['global_evaluations'] = 'Globale Evaluationen';
$string['min_results'] = 'Bisher gibt es leider weniger als {$a} Abgaben. Aus datenschutzrechtlichen Gründen dürfen keine Ergebnisse gezeigt werden.';
$string['min_results_desc'] = 'Datenschutz: Mindestanzahl der Abgaben bevor Resultate angezeigt werden dürfen.';
$string['min_results_text'] = 'Bisher gibt es leider weniger als {$a} Abgaben. Aus datenschutzrechtlichen Gründen dürfen keine Textantworten gezeigt werden.';
$string['min_results_text_desc'] = 'Datenschutz: Mindestanzahl der Abgaben bevor Textantworten angezeigt werden dürfen.';
$string['min_results_priv'] = 'Bisher gibt es leider weniger als {$a} Abgaben. Aus datenschutzrechtlichen Gründen dürfen auch priv. Personen keine Ergebnisse gezeigt werden.';
$string['min_results_priv_desc'] = 'Datenschutz: Mindestanzahl der Abgaben bevor privilegierten Personen Resultate angezeigt werden dürfen.';
$string['no_course_selected'] = "Es wurde kein zu evalierender Kurs gesetzt!";
$string['no_data'] = "Es gibt keine Daten für diese Ansicht!";
$string['no_permission'] = $string['you_have_no_permission'] = 'Sie haben nicht die für diese Seite notwendigen Rechte!';
$string['no_permission_analysis'] = 'Sie haben nicht die für diese Auswertung notwendigen Rechte!';
$string['no_responses_yet'] = "Es gibt keine Abgaben!";
$string['non_responders_only'] = 'Nur Non Responders';
$string['not_participated'] = 'Sie haben bisher <b>für keinen Kurs</b> teilgenommen.';
$string['not_participated_course'] = 'Sie haben für diesen Kurs noch nicht teilgenommen.';
$string['open_evaluation'] = 'Evaluation öffnen';
$string['pageviews'] = 'Ansichten';
$string['participant'] = 'Teilnehmer';
$string['participants'] = 'Teilnehmer_innen';
$string['please_participate'] = 'Bitte machen Sie mit!';
$string['participating_courses'] = 'Teilnehmende Kurse';
$string['participating_courses_of_studies'] = 'Teilnehmende Studiengänge';
$string['participant_roles'] = 'Standardrolle(n) der Teilnehmer_innen';
$string['participant_roles_help'] = 'Geben Sie die Standardrolle(n) für Teilnehmer_innen ein. Die Standardrolle ist Student. Standardeinstellungen können für jede Evaluation in den Einstellungen überschrieben werden.';
$string['privileged_users'] = 'Privilegierte Anwender_innen';
$string['privileged_users_desc'] = 'Privilegierte Personen für diese Evaluation (je 1 Anmeldename/Zeile)';
$string['privileged_users_overview'] = 'Übersicht der für diese Evaluation zur Auswertung privilegierten Personen';
$string['reminders_sent_at'] = 'Hinweismails wurden versandt am:';
$string['send_reminders_to'] = 'Hinweismails versenden an:';
$string['reminders_title'] = 'Hinweismails können nur von Admins oder als geplanter Server Task versandt werden. Der Vermerk \'NR\' weist darauf hin, dass nur Studierende ohne Abgaben bzw. Dozent_innen mit weniger als 3 Abgaben (Non-Responders) angeschrieben wurden.';
$string['select_teacher'] = 'Bitte wählen Sie die Dozentin/den Dozenten für diese Evaluation!<br>Hinweis: Sie können einen Fragebogen pro Dozent_in ausfüllen';
$string['semesters'] = 'Semester';
$string['sendername'] = 'Hinweismails: Absender Name';
$string['sendermail'] = 'Hinweismails: Absender Mailadresse';
$string['sexes'] = 'Geschlechter';
$string['signature'] = 'Hinweismails: Signatur';
$string['show_on_index'] = 'Evaluation auf der Evaluationsübersicht anzeigen';
$string['sort_tag'] = 'Tag zur Sortierung der Evaluationen in der Übersicht';
$string['statistic'] = 'Statistik';
$string['statistics'] = 'Statistik';
$string['student'] = 'Student_in';
$string['students'] = 'Student_innen';
$string['students_only'] = 'An dieser Evaluation können nur Studierende aus einem Kurs heraus teilnehmen!';
$string['submittedEvaluations'] = 'Abgaben';
$string['teacher'] = 'Dozent_in';
$string['teachers'] = 'Dozent_innen';
$string['teachers_in_courses'] = 'Dozent_innen in teilnehmenden Kursen';
$string['teamteaching'] = 'Team Teaching';
$string['teamteaching_help'] = 'Team Teaching erlaubt einen Fragebogen für jeden Lehrenden im Kurs';
$string['this_evaluation'] = 'Diese Evaluation';
$string['usageReport'] = 'Übersicht zur Nutzung der Evaluation';
$string['viewsglobalEvaluationInstances'] = 'Übersicht semesterbezogener Evaluationen';
$string['welcome_text'] = 'Begrüßungstext';
$string['your'] = 'Ihre';
$string['no_responses_yet'] = 'Es gibt noch keine Antworten';

// settings for plugin configuration
$string['config_course_of_studies_cat_level'] = 'Kurskategorie Level für Studiengänge';
$string['config_course_of_studies_cat_field'] = 'Kurskategorie Feld für Semester (JJJJ1 oder JJJJ2) für Studiengänge';
$string['config_semester_cat_level'] = 'Kurskategorie Level für semester';
$string['config_course_semester_field'] = 'Kurs Feld für Semester (JJJJ1 oder JJJJ2)';
$string['config_summer_semester'] = 'Monate des Somersmesters';
// end settings for plugin configuration

// partly from Moodle 3.10 evaluation
$string['add_item'] = 'Frage hinzufügen';
$string['add_pagebreak'] = 'Seitenumbruch hinzufügen';
$string['adjustment'] = 'Ausrichtung';
$string['after_submit'] = 'Nach der Abgabe';
$string['allowfullanonymous'] = 'Völlige Anonymität erlauben';
$string['analysis'] = 'Auswertung';
$string['analysis_course'] = 'Auswertung Kurs';
$string['analysis_own_courses'] = 'Auswertung eigene Kurse';
$string['analysis_all_courses'] = 'Auswertung aller Kurse';
$string['analysis_own_cos'] =  'Auswertung eigene Studiengänge';
$string['analysis_own_cos_title'] =  'als dazu privilegierte Person';
$string['anonymous'] = 'Anonym';
$string['anonymous_edit'] = 'Anonym ausfüllen';
$string['anonymous_entries'] = 'Anonyme Einträge ({$a})';
$string['anonymous_user'] = 'Anonyme Person';
$string['answerquestions'] = 'Fragen beantworten';
$string['append_new_items'] = 'Neue Elemente anfügen';
$string['autonumbering'] = 'Automatische Nummerierung';
$string['autonumbering_help'] = 'Diese Option aktiviert die automatische Nummerierung der Fragen.';
$string['average'] = 'Mittelwert';
$string['bold'] = 'Fett';
$string['calendarend'] = '{$a} endet';
$string['calendarstart'] = '{$a} beginnt';
$string['cannotaccess'] = 'Sie können auf dieses Formular nur aus einem Kurs zugreifen.';
$string['cannotsavetempl'] = 'Vorlagen speichern ist nicht erlaubt';
$string['captcha'] = 'Captcha';
$string['captchanotset'] = 'Captcha wurde nicht ausgefüllt';
$string['check'] = 'Mehrere Antworten';
$string['check_values'] = 'Antworten';
$string['checkbox'] = 'Mehrere Antworten erlaubt (Checkboxen)';
$string['choosefile'] = 'Datei auswählen';
$string['chosen_evaluation_response'] = 'Gewählte Antwort';
$string['closebeforeopen'] = 'Das Ende des Evaluationen muss nach dem Beginn liegen.';
$string['complete_the_form'] = 'Formular ausfüllen';
$string['completed'] = 'Abgeschlossen';
$string['completed_evaluations'] = 'Abgeschlossene Evaluationen';
$string['completedon'] = 'Abgeschlossen am {$a}';
$string['completionsubmit'] = 'Als abgeschlossen ansehen, wenn die Evaluation abgegeben wurde';
$string['configallowfullanonymous'] = 'Wenn diese Option aktiviert ist, kann eine Evaluation ohne vorhergehende Anmeldung abgegeben werden. Dies betrifft aber ausschließlich Evaluationen auf der Startseite.';
$string['confirmdeleteentry'] = 'Möchten Sie diesen Eintrag wirklich löschen?';
$string['confirmdeleteitem'] = 'Möchten Sie dieses Element wirklich löschen?';
$string['confirmdeletetemplate'] = 'Möchten Sie diese Vorlage wirklich löschen?';
$string['confirmusetemplate'] = 'Möchten Sie diese Vorlage wirklich verwenden?';
$string['continue_the_form'] = 'Ausfüllen des Formulars fortsetzen';
$string['count_of_nums'] = 'Anzahl von Werten';
$string['courseid'] = 'Kurs-ID';
$string['creating_templates'] = 'Diese Fragen als neue Vorlage speichern';
$string['delete_entry'] = 'Eintrag löschen';
$string['delete_item'] = 'Element löschen';
$string['delete_old_items'] = 'Alte Elemente löschen';
$string['delete_pagebreak'] = 'Seitenumbruch löschen';
$string['delete_template'] = 'Vorlage löschen';
$string['delete_templates'] = 'Vorlage löschen...';
$string['depending'] = 'Abhängigkeiten';
$string['depending_help'] = 'Ein abhängiges Element wird in Abhängigkeit von einem anderen Element angezeigt.<br /><br />
<strong>Beispiel:</strong>
<ul>
<li>Legen Sie zunächst das Element an, von dem ein anderes Element abhängt.</li>
<li>Fügen Sie dann einen Seitenumbruch hinzu.</li>
<li>Fügen Sie nun die Elemente hinzu, die vom Wert des zuvor erstellten Elements abhängen. Wählen Sie das Element in der Liste "Abhängigkeitselement" aus und legen Sie den erforderlichen Wert im Textfeld "Abhängigkeitswert" fest.</li>
</ul>
<strong>Die Struktur sollte folgendermaßen aussehen:</strong>
<ol>
<li>Element - Frage: Haben Sie ein Auto? Antwort: ja/nein</li>
<li>Seitenumbruch</li>
<li>Element - Frage: Welche Farbe hat Ihr Auto?<br />
(Dieses Element wird bei der Antwort "ja" in der ersten Frage angezeigt)</li>
<li>Element - Frage: Warum haben Sie kein Auto?<br />
(Dieses Element wird bei der Antwort "nein" in der ersten Frage angezeigt)</li>
<li> ... weitere Elemente</li>
</ol>
Das ist schon alles.';
$string['dependitem'] = 'Abhängigkeitselement';
$string['dependvalue'] = 'Abhängigkeitswert';
$string['description'] = 'Beschreibung';
$string['do_not_analyse_empty_submits'] = 'Leere Abgaben ignorieren';
$string['downloadresponseas'] = 'Alle Antworten herunterladen als:';
$string['drop_evaluation'] = 'Aus diesem Kurs entfernen';
$string['dropdown'] = 'Multiple Choice - einzelne Antwort (Dropdown)';
$string['dropdown_values'] = 'Antworten';
$string['dropdownlist'] = 'Multiple Choice - einzelne Antwort (Dropdown)';
$string['dropdownrated'] = 'Dropdown-Menü (skaliert)';
$string['edit_item'] = 'Element bearbeiten';
$string['edit_items'] = 'Elemente bearbeiten';
$string['email_notification'] = 'Systemnachricht bei Abgabe senden';
$string['email_notification_help'] = 'Wenn diese Option aktiviert ist, bekommen die Lehrenden naach Abgaben eine Systemnachricht.';
$string['emailteachermail'] = '{$a->username} hat die Evaluation \'{$a->evaluation}\' abgeschlossen. Sie können es hier anschauen: {$a->url}';
$string['emailteachermailhtml'] = '<p>{$a->username} hat die Evaluation \'{$a->evaluation}\' abgeschlossen</p><p>Die Evaluation ist <a href="{$a->url}">auf der Webseite</a> verfügbar.</p>';
$string['entries_saved'] = 'Diese Evaluation wurde als abgegeben gespeichert.';
$string['eventresponsedeleted'] = 'Antwort gelöscht';
$string['eventresponsesubmitted'] = 'Antwort abgegeben';
$string['export_questions'] = 'Fragen exportieren';
$string['export_to_excel'] = 'Nach Excel exportieren';
$string['ev_end_msg'] = 'Die Abgabefrist endet {$a->ev_end_msg}';
$string['evaluation:addinstance'] = 'Neues Evaluation hinzufügen';
$string['evaluation:complete'] = 'Evaluation abschließen';
$string['evaluation:createprivatetemplate'] = 'Kursinternen Template erstellen';
$string['evaluation:createpublictemplate'] = 'Öffentlichtes Template erstellen';
$string['evaluation:deletesubmissions'] = 'Abgeschlossene Abgaben löschen';
$string['evaluation:deletetemplate'] = 'Template löschen';
$string['evaluation:edititems'] = 'Fragen bearbeiten';
$string['evaluation:mapcourse'] = 'Kurse zu globalen Evaluationen zuordnen';
$string['evaluation:receivemail'] = 'E-Mail-Nachricht empfangen';
$string['evaluation:view'] = 'Evaluation anzeigen';
$string['evaluation:viewanalysepage'] = 'Analyseseite nach der Abgabe anzeigen';
$string['evaluation:viewreports'] = 'Auswertungen anzeigen';
$string['evaluation_is_not_for_anonymous'] = 'Die Evaluation ist nicht für anonyme Personen.';
$string['evaluation_is_not_open'] = 'Ein Evaluation ist zu diesem Zeitpunkt nicht möglich';
$string['evaluationclose'] = 'Antworten erlaubt bis';
$string['evaluationcompleted'] = '{$a->username} hat {$a->evaluationname} abgeschlossen.';
$string['evaluationopen'] = 'Antworten erlaubt ab';
$string['file'] = 'Datei';
$string['filter_by_course'] = 'Kursfilter';
$string['handling_error'] = 'Fehler bei der Verarbeitung';
$string['hide_no_select_option'] = '\'Nicht gewählt\' verbergen';
$string['import_questions'] = 'Fragen importieren';
$string['import_successfully'] = 'Erfolgreich importiert';
$string['importfromthisfile'] = 'Aus dieser Datei importieren';
$string['includeuserinrecipientslist'] = 'Fügen Sie {$a} in die Empfängerliste hinzu';
$string['indicator:cognitivedepth'] = 'Evaluation kognitiv';
$string['indicator:cognitivedepth_help'] = 'Dieser Indikator basiert auf der kognitiven Tiefe, die eine Person in einer Evaluation-Aktivität erreicht hat.';
$string['indicator:cognitivedepthdef'] = 'Evaluation kognitiv';
$string['indicator:cognitivedepthdef_help'] = 'Die Person hat diesen Prozentsatz des kognitiven Engagements erreicht, das die Evaluation-Aktivitäten während dieses Analyseintervalls aufzeigen (Ebenen = Keine Ansicht, Ansicht, Beiträge).';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Evaluation sozial';
$string['indicator:socialbreadth_help'] = 'Dieser Indikator basiert auf der sozialen Breite, die eine Person in einer Evaluation-Aktivität erreicht hat.';
$string['indicator:socialbreadthdef'] = 'Evaluation sozial';
$string['indicator:socialbreadthdef_help'] = 'Die Person hat diesen Prozentsatz des soziale Engagements erreicht, das die Evaluation-Aktivitäten während dieses Analyseintervalls aufzeigen (Ebenen = Keine Teilnahme, Teilnahme allein, Teilnahme mit anderen).';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['info'] = 'Information';
$string['infotype'] = 'Informationstyp';
$string['insufficient_responses'] = 'Unzulängliche Antworten';
$string['insufficient_responses_for_this_group'] = 'Es gibt unzulängliche Antworten für diese Gruppe';
$string['insufficient_responses_help'] = 'Damit die Evaluation anonym ist, müssen mindestens zwei Antworten abgegeben sein.';
$string['item_label'] = 'Textfeld';
$string['item_name'] = 'Frage';
$string['label'] = 'Textfeld';
$string['labelcontents'] = 'Inhalte';
$string['mapcourse'] = 'Kurs zuordnen';
$string['mapcourse_help'] = 'Die auf der Startseite erstellten Evaluationen sind auf der gesamten Website verfügbar und werden über den Block \'Evaluation\' in allen Kursen angezeigt.
Sie können das Erscheinen in jedem Kurs erzwingen, indem Sie einen festen Block erzeugen. Andererseits können Sie die Evaluation auf ausgewählte Kurse einschränken, indem Sie die Evaluation mit bestimmten Kursen verknüpfen.';
$string['mapcourseinfo'] = 'Dieses globale Evaluation ist in allen Kursen verfügbar, die den Block \'Evaluation\' nutzen. Sie können die Kurse einschränken, in denen die Evaluation angezeigt wird. Ordnen Sie dazu die Evaluation ausgewählten Kursen zu.';
$string['mapcoursenone'] = 'Keinem Kurs zugeordnet. Dieses Evaluation ist in allen Kursen verfügbar.';
$string['mapcourses'] = 'Evaluation zu Kursen zuordnen';
$string['mappedcourses'] = 'Zugeordnete Kurse';
$string['mappingchanged'] = 'Kurszuordnung wurde geändert';
$string['maximal'] = 'Maximal';
$string['messageprovider:message'] = 'Erinnerung zum Evaluation';
$string['messageprovider:submission'] = 'Systemnachrichten bei Evaluation';
$string['minimal'] = 'Minimal';
$string['mode'] = 'Modus';
$string['modulename'] = 'Evaluation';
$string['modulename_help'] = 'Mit der Evaluation können Sie eigene Umfragen oder Evaluationsformulare anlegen, wofür eine Reihe von Fragetypen, einschließlich Multiple-Choice, Ja/Nein oder Texteingabe, zur Verfügung stehen.
Die Antworten können Personen zugeordnet werden oder anonym erfolgen. Die Ergebnisse können Sie nach dem Ausfüllen anzeigen lassen und später als Datei exportieren.
Evaluationen auf der Startseite können völlig anonym auch von nicht angemeldeten Personen ausgefüllt und abgegeben werden.
Eine Evaluation-Aktivität kann verwendet werden

* Lehrevaluationen der Studierenden
* Evaluationen der Lehrenden
* Bei Kursbewertungen, um den Inhalt für spätere Teilnehmer_innen zu verbessern
* Um den Teilnehmer_innen die Möglichkeit zu geben, sich für Kursmodule, Veranstaltungen usw. anzumelden
* Für Anti-Mobbing-Befragungen, bei denen Teilnehmer_innen Vorfälle anonym melden können';
$string['modulename_link'] = 'mod/evaluation/view';
$string['modulenameplural'] = 'Evaluationen';
$string['move_item'] = 'Element verschieben';
$string['multichoice'] = 'Multiple-Choice';
$string['multichoice_values'] = 'Antworten';
$string['multichoiceoption'] = '<span class="weight">({$a->weight}) </span> {$a->name}';
$string['multichoicerated'] = 'Multiple-Choice (skaliert)';
$string['multichoicetype'] = 'Typ';
$string['multiplesubmit'] = 'Mehrfache Abgabe';
$string['multiplesubmit_help'] = 'Wenn die Option für anonyme Fragebögen aktiviert ist, dürfen Nutzer/innen die Evaluation beliebig oft abgeben.';
$string['name'] = 'Name';
$string['name_required'] = 'Name benötigt';
$string['nameandlabelformat'] = '({$a->label}) {$a->name}';
$string['next_page'] = 'Nächste Seite';
$string['no_handler'] = 'Keine Aktion gefunden!';
$string['no_itemlabel'] = 'Kein Textfeld';
$string['no_itemname'] = 'Kein Name des Eintrags';
$string['no_items_available_yet'] = 'Keine Elemente angelegt';
$string['no_templates_available_yet'] = 'Keine Vorlagen verfügbar';
$string['non_anonymous'] = 'Nicht anonym';
$string['non_anonymous_entries'] = 'Nicht-anonyme Einträge ({$a})';
$string['non_respondents_students'] = 'Teilnehmer/innen ohne Antwort ({$a})';
$string['not_completed_yet'] = 'Nicht abgeschlossen';
$string['not_selected'] = 'Nicht gewählt';
$string['not_started'] = 'Nicht begonnen';
$string['numberoutofrange'] = 'Zahl außerhalb des Bereichs';
$string['numeric'] = 'Numerische Antwort';
$string['numeric_range_from'] = 'Bereich von';
$string['numeric_range_to'] = 'Bereich bis';
$string['of'] = 'von';
$string['oldvaluespreserved'] = 'Alle alten Fragen und eingegebenen Werte werden aufbewahrt.';
$string['oldvalueswillbedeleted'] = 'Die aktuellen Fragen und alle Antworten werden gelöscht.';
$string['only_one_captcha_allowed'] = 'Im Evaluation ist nur ein Captcha erlaubt';
$string['openafterclose'] = 'Sie haben ein Startdatum angelegt, das nach dem Enddatum liegt.';
$string['overview'] = 'Überblick';
$string['page'] = 'Seite';
$string['page-mod-evaluation-x'] = 'Jede Evaluation-Seite';
$string['page_after_submit'] = 'Abschlussmitteilung';
$string['pagebreak'] = 'Seitenumbruch';
$string['pluginadministration'] = 'Evaluation-Administration';
$string['pluginname'] = 'Evaluation';
$string['position'] = 'Position';
$string['previous_page'] = 'Vorherige Seite';
$string['privacy:metadata:completed'] = 'Datensatz mit beantworteten Evaluation-Fragebögen';
$string['privacy:metadata:completed:anonymousresponse'] = 'Hier wird festgelegt, ob die Abgabe anonymisiert stattfinden soll.';
$string['privacy:metadata:completed:timemodified'] = 'Zeitpunkt der letzten Bearbeitung der Abgabe.';
$string['privacy:metadata:completed:userid'] = 'ID des Nutzers, der die Evaluation Aktivität abgeschlossen hat.';
$string['privacy:metadata:completedtmp'] = 'Datensatz über Beantwortungen im Evaluation, die noch nicht abgeschlossen sind';
$string['privacy:metadata:value'] = 'Datensatz mit Antworten auf Fragen';
$string['privacy:metadata:value:value'] = 'Gewählte Antwort';
$string['privacy:metadata:valuetmp'] = 'Datensatz mit Antworten auf Fragen, wenn die Evaluation noch nicht abgeschlossen ist';
$string['public'] = 'öffentlich';
$string['question'] = 'Frage';
$string['questionandsubmission'] = 'Einstellungen für Fragen und Einträge';
$string['questions'] = 'Fragen';
$string['questionslimited'] = 'Nur die ersten {$a} Fragen werden angezeigt. Um alles zu sehen, lassen Sie sich die individuellen Antworten anzeigen oder laden Sie die gesamte Tabelle herunter.';
$string['radio'] = 'Einzelne Antwort - Radiobutton';
$string['radio_values'] = 'Antworten';
$string['ready_evaluations'] = 'Fertige Evaluationen';
$string['required'] = 'Erforderlich';
$string['resetting_data'] = 'Evaluation-Antworten zurücksetzen';
$string['resetting_evaluations'] = 'Evaluationen werden zurückgesetzt';
$string['response_nr'] = 'Antwort Nr.';
$string['responses'] = 'Antworten';
$string['responsetime'] = 'Antwortzeit';
$string['save_as_new_item'] = 'Als neue Frage speichern';
$string['save_as_new_template'] = 'Als neue Vorlage speichern';
$string['save_entries'] = 'Evaluation abgeben (Antworten speichern)';
$string['save_entries_help'] = 'Ihre Antworten werden erst durch die Abgabe dieser Evaluation gespeichert und somit abgegeben.<br>Nach der Abgabe sind keine Änderungen mehr möglich!';
$string['save_item'] = 'Element speichern';
$string['saving_failed'] = 'Fehler beim Speichern';
$string['search:activity'] = 'Evaluation - Aktivitätsinfo';
$string['search_course'] = 'Kurs suchen';
$string['searchcourses'] = 'Kurse suchen';
$string['searchcourses_help'] = 'Nach Codes oder Namen von Kursen suchen, die Sie dieser Evaluation zuordnen möchten.';
$string['selected_dump'] = 'Dump der ausgewählten Indexe der Variable $SESSION:';
$string['send'] = 'Senden';
$string['send_message'] = 'Mitteilung senden';
$string['show_all'] = 'Alle anzeigen';
$string['show_analysepage_after_submit'] = 'Analyseseite nach der Abgabe anzeigen';
$string['show_entries'] = 'Einträge anzeigen';
$string['show_entry'] = 'Eintrag anzeigen';
$string['show_nonrespondents'] = 'Ohne Antwort';
$string['site_after_submit'] = 'Seite nach Eingabe';
$string['sort_by_course'] = 'Sortiert nach Kursen';
$string['started'] = 'Begonnen';
$string['startedon'] = 'Begonnen am {$a}';
$string['subject'] = 'Thema';
$string['switch_item_to_not_required'] = 'Als nicht notwendig setzen';
$string['switch_item_to_required'] = 'Als notwendig setzen';
$string['template'] = 'Vorlage';
$string['template_deleted'] = 'Vorlage gelöscht';
$string['template_saved'] = 'Vorlage gespeichert';
$string['templates'] = 'Vorlagen';
$string['textarea'] = 'Eingabebereich';
$string['textarea_height'] = 'Höhe (in Zeilen)';
$string['textarea_width'] = 'Breite';
$string['textfield'] = 'Eingabezeile';
$string['textfield_maxlength'] = 'Maximale Zeichenzahl';
$string['textfield_size'] = 'Breite der Eingabe';
$string['there_are_no_settings_for_recaptcha'] = 'Keine Einstellungen für das Captcha';
$string['this_evaluation_is_already_submitted'] = 'Sie haben diese Aktivität bereits abgeschlossen.';
$string['typemissing'] = 'Fehlender Typ';
$string['update_item'] = 'Änderungen speichern';
$string['url_for_continue'] = 'URL für die Taste "Weiter"';
$string['url_for_continue_help'] =
        'Nach der Evaluation-Abgabe wird eine Taste "Weiter" gezeigt. Zumeist auf die Kursseite weitergeleitet. Falls Sie auf ein anderes Ziel verlinken möchten, können Sie hier die URL angeben.';
$string['use_one_line_for_each_value'] = 'Schreiben Sie jede Antwort in eine neue Zeile!';
$string['use_this_template'] = 'Diese Vorlage verwenden';
$string['using_templates'] = 'Vorlage verwenden';

