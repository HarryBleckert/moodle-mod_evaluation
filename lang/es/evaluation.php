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
$string['language'] = 'ee';


// índice.php
$string['index_group_by_tag'] = 'Grupo';
// fin del índice.php

// enviar recordatorios
$string['send_reminders_noreplies_teachers'] = 'Sólo para profesores que hayan realizado menos de {$a->min_results_text} envíos.';
$string['send_reminders_noreplies_students'] = 'Sólo a estudiantes que aún no han participado en la evaluación.';
$string['send_reminders_pmsg'] = 'Hoy se enviaron correos electrónicos con recordatorios sobre la evaluación en curso a todos los {$a->role} cuyos cursos están participando en la evaluación. A continuación puedes ver un ejemplo. ';
$string['send_reminders_remaining'] = 'solo quedan {$a->remaining_evaluation_days} días para {$a->lastEvaluationDay}';
$string['send_reminders_students'] = '{$a->testmsg}<p>Hola {$a->fullname}</p>
<p>Por favor, {$a->también} participe en la {$a->evaluación continua} de recordatorio<br>
    La encuesta es anónima y sólo toma unos minutos por curso y profesor.
    Para cada curso que ya hayas evaluado, podrás ver inmediatamente la evaluación si se han realizado al menos {$a->minResults} envíos.<br>
    Por razones de protección de datos, se excluyen los datos personales y las respuestas a preguntas abiertas.
</p>
<p><b>¡Al participar estás ayudando a mejorar la enseñanza!</b></p>
<p>A continuación se muestra una descripción general de los cursos que están disponibles en
    <a href="{$a->evUrl}"><b>{$a->ev_name}</b></a> participar:</p>
{$a->misCursos}
<p style="margin-bottom: 0cm">Saludos cordiales<br>
    {$a->firma}
    {$a->signum}</p>';

$string['send_reminders_teachers'] = '{$a->testmsg}<p>Hola {$a->fullname}</p>
{$a->solo unos pocos}
<p>Por favor, anime a sus estudiantes a participar en la evaluación continua {$a->reminder}<br>
    Sería ideal si pudieras integrar la participación en tus eventos haciendo un llamado motivador a ello y
    ¡Dé a los estudiantes unos minutos para participar durante el evento!</p>
<p>Si hay al menos {$a->minResults} envíos <b>para usted</b> para uno de sus cursos, puede ver la evaluación de los envíos realizados para usted.<br>
    Solo puedes ver las respuestas de texto si se han realizado al menos {$a->min_results_text} envíos para ti.</p>
<p>A continuación se muestra una descripción general de los cursos que están disponibles en
    <a href="{$a->evUrl}"><b>{$a->ev_name}</b></a> participar:</p>
{$a->misCursos}
<p style="margin-bottom: 0cm">Saludos cordiales<br>
    {$a->firma}
    {$a->signum}</p>';

$string['send_reminders_no_replies'] = 'Ninguno de sus {$a->distinct_s} estudiantes ha participado todavía. ';
$string['send_reminders_few_replies'] = 'Hasta el momento solo hay {$a->respuestas} {$a->envíos} de sus {$a->distinct_s} estudiantes. ';
$string['send_reminders_many_replies'] = 'Hasta el momento hay {$a->replies} envíos de sus {$a->distinct_s} estudiantes';
$string['send_reminders_privileged'] = 'Está recibiendo este correo electrónico con fines informativos porque está autorizado a ver los resultados de la evaluación para esta evaluación.';
//Finalizar el envío de recordatorios

// Función de traductor, Magik trabajó para Berthe
$string['evaluation_of_courses'] = 'Evaluación de cursos';
$string['by_students'] = 'por estudiantes';
$string['of_'] = 'des';
$string['for_'] = 'para eso';
$string['sose_'] = 'Semestre de verano';
$string['wise_'] = 'Semestre de invierno';
// fin de la función traductora

// biblioteca gráfica
$string['show_graphic_data'] = 'Mostrar datos gráficos';
$string['hide_graphic_data'] = 'Ocultar datos gráficos';
// fin de la biblioteca gráfica

// vista.php
$string['es'] = 'es';
$string['era'] = 'era';
$string['teamteachingtxt'] = 'En los seminarios con docencia en equipo, los profesores son evaluados individualmente.';
$string['is_WM_disabled'] = 'Se excluyen los programas de maestría de educación continua.';
$string['siteadmintxt'] = 'Administrador y por lo tanto';
$string['andrawdata'] = 'y datos sin procesar';
$string['yourcos'] = 'Sus programas de estudio';
$string['viewanddownload'] = 'ver y descargar una vez que haya {$a->minresultspriv} envíos.';
$string['privilegestxt'] = 'Como persona privilegiada {$a->siteadmintxt} para esta evaluación, puede ver todos los análisis {$a->andrawdata}';
$string['courseparticipants'] = 'Este curso tiene {$a->numteachers} profesores y {$a->numstudents} estudiantes participantes.';
$string['participantsandquota'] = 'participantes han tomado parte en esta evaluación. Esto corresponde a una participación del {$a->evaluated}.';
$string['quotaevaluatedall'] = 'de los participantes han evaluado a todos los profesores.';
$string['quotaevaluatedteacher'] = 'de los participantes han evaluado a este profesor.';
$string['coursequota'] = 'Se han realizado {$a->completed_responses} de un máximo de {$a->numToDo} envíos. La cuota de envío es {$a->quote}.';
$string['nostudentsincourse'] = 'Este curso no tiene estudiantes participantes.';
$string['questionaireenglish'] = 'Aquí hay una traducción al inglés del cuestionario.';
$string['clickquestionaireenglish'] = '<b>Haga clic aquí</b> para abrir una traducción al inglés del cuestionario';
$string['also'] = 'también';
$string['foryourcourses'] = 'para cada uno de sus cursos';
$string['msg_student_all_courses'] = 'Por favor {$a->also} participe en esta evaluación {$a->foryourcourses}. La encuesta es anónima y solo toma unos minutos por curso.<br>Haga clic abajo en \'<b>Evaluar ahora</b>\' para cada uno de sus cursos aún no evaluados y complete el cuestionario respectivo.';
$string['yourevaluationhelps'] = '¡Su evaluación nos ayuda mucho!';
$string['resultconditions'] = 'Para cada curso que ya haya evaluado, puede ver los resultados una vez que haya {$a->minresults} envíos.';
$string['yourpartcourses'] = 'Tiene cursos que participan en esta evaluación. Por favor motive a los estudiantes a participar';
$string['yourpastpartcourses'] = 'Tiene cursos que han participado en esta evaluación';
$string['teachersviewconditions'] = 'Para sus propios cursos, puede ver el análisis una vez que haya {$a->minresults} envíos.
A partir de {$a->minresultstxt} envíos también puede ver las respuestas de texto.';
$string['evaluationalert'] = '¡Solo quedan {$a->daysleft} días hasta el final del plazo de entrega!';
$string['show_active_only'] = 'Depurado: Solo participantes que usaron Moodle durante el período de evaluación';
$string['onefeedbackperteacher'] = '¿Está activado un envío por profesor?';
$string['teamteachingcourses'] = 'Cursos con enseñanza en equipo';
$string['duplicatedfeedbacks'] = 'Envíos duplicados';
$string['logananalysis'] = 'Análisis del registro de Moodle. Los datos de registro se conservan durante {$a->loglifetime} días.';
$string['currentday'] = 'Hoy es el <b>día {$a->currentday} {$a->currentday_percent}';
$string['not_anonymous'] = 'No anónimo';
$string['evaluationperiod'] = 'La participación en la evaluación {$a->is_or_was} posible desde el {$a->timeopen} hasta el {$a->timeclose}.';
$string['thxforcompletingall'] = '¡Muchas gracias! ¡Ha participado en esta evaluación para todos sus cursos participantes!';
$string['thxforcompletingcourse'] = '¡Ya ha participado en la evaluación para este curso!';
$string['view_after_participating'] = '¡Puede ver los análisis de esta evaluación en cualquier momento después de haber participado usted mismo para este curso!';
$string['no_participation_no_view'] = '¡No puede ver los análisis de este curso porque no ha participado usted mismo en la evaluación para este curso!';
$string['no_part_no_results_site'] = '¡No ha participado en esta evaluación para ninguno de sus cursos y por lo tanto no tiene derecho a ver los análisis relacionados con los cursos!';
$string['no_part_no_results'] = '¡No ha participado en esta evaluación y por lo tanto no tiene derecho a ver los análisis!';
$string['no_course_participated'] = 'Ninguno de sus cursos fue parte de esta evaluación';
$string['no_course_participing'] = '¡Ninguno de sus cursos es parte de esta evaluación!';
$string['results_all_evaluated_teachers'] = 'Resultados para todos los profesores evaluados';
$string['for_participants'] = 'para participantes';
$string['for_teachers'] = 'para profesores';
$string['courses_of'] = 'Cursos de';
$string['note'] = 'Nota';
$string['show_evaluated_courses_student'] = 'Solo se le muestran cursos para los que ha participado en la evaluación.';
$string['show_evaluated_courses_teacher'] = 'Solo se le muestran cursos para los que se han realizado envíos';
$string['num_courses_in_ev'] = '{$a->num_courses} de sus cursos fueron parte de esta evaluación.';
$string['submitted_for'] = 'Enviado para';
$string['evaluated'] = 'Enviado';
$string['to_evaluate'] = 'Por enviar';
$string['non_of_your_courses_participated'] = '¡Ninguno de sus cursos participó en esta evaluación!';
$string['for_you'] = 'para usted';
$string['in_your_courses'] = 'en sus cursos participantes';
$string['summer_semester'] = 'Semestre de verano';
$string['winter_semester'] = 'Semestre de invierno';
$string['back'] = 'Atrás';
$string['analysis_cos'] = 'Análisis de los programas de estudio';
$string['reset_selection'] = 'Restablecer selección';
$string['question_hint'] = 'Hay 3 variantes de preguntas que pueden evaluarse automáticamente: Radio y Desplegable (Opción única) o Casilla de verificación (Opción múltiple). En las preguntas de opción única, se puede seleccionar exactamente una respuesta de varias opciones. Las preguntas de opción múltiple permiten cualquier selección de respuestas';
$string['no_questions_for_analysis'] = '¡No hay preguntas de opción múltiple ni preguntas numéricas. No es posible un análisis estadístico para esta evaluación!';
$string['no_answer'] = 'sin respuesta';
$string['cant_answer'] = 'n/a';
$string['i_dont_know'] = 'No puedo responder';
$string['analyzed_sc_questions'] = 'Preguntas de opción única analizadas';
$string['reply_scheme'] = 'Esquema de respuesta';
$string['filter_on_questions'] = 'Filtros en preguntas';
$string['with_reply'] = 'con respuesta';
$string['change_sort_up_down'] = 'Cambiar orden entre ascendente y descendente';
$string['change_sort_by'] = 'Ordenar por envíos o por valores medios';
$string['click_for_graphics'] = 'Haga clic aquí para desplazarse directamente al gráfico';
$string['horizontal'] = 'Horizontal';
$string['vertical'] = 'Vertical';
$string['maxgraphs'] = 'número máximo para visualización gráfica';
$string['graphics'] = 'Gráfico';
$string['no_minreplies_no_show'] = 'Las evaluaciones para {$a->allSubject} con menos de {$a->minReplies} envíos no pueden ser analizadas.';
$string['submission'] = 'Envío';
$string['submissions'] = 'Envíos';
$string['with_minimum'] = 'con mínimo';
$string['show'] = 'mostrar';
$string['hide'] = 'ocultar';
$string['toggle_by_minreplies'] = 'Mostrar/ocultar resultados con menos de {$a->minReplies} envíos';
$string['evaluated_question'] = 'Pregunta evaluada';
$string['all_numquestions'] = 'Todas las {$a->numQuestions} preguntas evaluables de manera comparable';
$string['this_is_a_multichoice_question'] = 'Esta es una pregunta de opción múltiple. Solo se pueden evaluar de manera significativa las respuestas de opción única';
$string['apply'] = 'aplicar';
$string['remove'] = 'eliminar';
$string['filter_action'] = '<b>Filtro</b> {$a->action}';
$string['remove_filter'] = 'Eliminar filtro';
$string['less_minreplies'] = '<span style="color:red;font-weight:bold;">Hay para</span> {$a->ftitle} <span style="color:red;font-weight:bold;">menos de {$a->minReplies} envíos</span>. <b>¡Por lo tanto no se muestra ningún análisis!</b>';
$string['except_siteadmin'] = ' - excepto administradores';
$string['team_teaching'] = 'Enseñanza en equipo';
$string['single_submission_per_course'] = 'Un envío por participante y curso';
$string['this_course_has_numteachers'] = 'Este curso tiene {$a->numTeachers}';
$string['all_submissions'] = 'Todos los envíos';
$string['course_participants_info'] = 'Este curso tiene {$a->numTeachers} y {$a->numStudents}. {$a->participated} participantes han participado en esta evaluación. Esto corresponde a una participación del {$a->evaluated}.';
$string['completed_for_this_teacher'] = '{$a->completed} de los participantes han evaluado a este profesor.';
$string['completed_for_all_teachers'] = '{$a->completed} de los participantes han evaluado a todos los profesores. ';
$string['submissions_for_course'] = 'Se han realizado {$a->numresultsF} de un máximo de {$a->numToDo} envíos. La cuota de envío es {$a->quote}.';
$string['course_has_no_students'] = 'Este curso no tiene estudiantes participantes.';
$string['no_teamteaching_all_same'] = 'Esta evaluación no tiene activada la enseñanza en equipo. En cursos con enseñanza en equipo, todos los profesores tienen por lo tanto el mismo análisis.';
$string['analyzed'] = 'Analizados';
$string['of_total'] = 'de un total';
$string['incl_duplicated'] = 'incluidos {$a->duplicated} envíos duplicados ';
$string['permitted_cos'] = 'Programas de estudio visibles';
$string['all_filtered_submissions'] = 'Todos los envíos filtrados {$a->ftitle}';
$string['omitted_submissions'] = '{$a->allSubject} con menos de {$a->minReplies} envíos {$a->percentage}';

