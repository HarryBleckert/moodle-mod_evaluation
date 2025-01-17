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
 * This file keeps track of upgrades to the evaluation module.
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package   mod_evaluation
 * @copyright Andreas Grabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_evaluation_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    $newversion = 2022041403;
    // Put any upgrade step following this.
    if ($oldversion < $newversion) {

        // Define field min_results to be added to evaluation.
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('min_results', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '3', 'completionsubmit');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field privileged_users to be added to evaluation.
        $field = new xmldb_field('privileged_users', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'min_results');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field filter_course_of_studies to be added to evaluation.
        $field = new xmldb_field('filter_course_of_studies', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null,
                'privileged_users');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Define field filter_courses to be added to evaluation.
        $field = new xmldb_field('filter_courses', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null,
                'filter_course_of_studies');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field teamteaching to be added to table evaluation.
        $field = new xmldb_field('teamteaching', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'filter_courses');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field teacherid to be added to table evaluation_completed.
        $table = new xmldb_table('evaluation_completed');

        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // add field course_of_studies to various tables
        $field = new xmldb_field('course_of_studies', XMLDB_TYPE_TEXT, null, null, false, null, '', 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field teacherid to be added to table evaluation_completedtmp.
        $table = new xmldb_table('evaluation_completedtmp');
        $field = new xmldb_field('course_of_studies', XMLDB_TYPE_TEXT, null, null, false, null, '', 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'course_of_studies');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field teacherid to be added to table evaluation_value.
        $table = new xmldb_table('evaluation_value');

        $field = new xmldb_field('course_of_studies', XMLDB_TYPE_TEXT, null, null, false, null, '', 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'course_of_studies');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('evaluation_valuetmp');

        $field = new xmldb_field('course_of_studies', XMLDB_TYPE_TEXT, null, null, false, null, '', 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teacherid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'course_of_studies');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define 10 fields to fix evaluation results after timeclose is reached
        $table = new xmldb_table('evaluation');

        $field = new xmldb_field('possible_evaluations', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'teamteaching');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('possible_active_evaluations', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'possible_evaluations');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('participating_students', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'possible_active_evaluations');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('participating_active_students', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'participating_students');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('participating_teachers', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0',
                'participating_active_students');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('participating_courses', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0',
                'participating_teachers');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('participating_active_courses', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0',
                'participating_courses');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('teamteaching_courses', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0',
                'participating_active_courses');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('courses_of_studies', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0',
                'teamteaching_courses');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('duplicated_replies', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0',
                'courses_of_studies');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // End of Define 6 fields to fix evaluation results after timeclose is reached

        // Evaluation savepoint reached.
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2022041900;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('participating_active_teachers', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0',
                'participating_teachers');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table evaluation_enrolments to be created.
        $table = new xmldb_table('evaluation_enrolments');
        // Adding fields to table evaluation_enrolments.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('evaluation', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_of_studies', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('students', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('teacherids', XMLDB_TYPE_CHAR, '600', null, null, null, '');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        // Adding keys to table evaluation_enrolments.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('evaluation', XMLDB_KEY_FOREIGN, ['evaluation'], 'evaluation', ['id']);
        // Adding indexes to table evaluation_enrolments.
        $table->add_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        // Conditionally launch create table for evaluation_enrolments.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // add missing indexes to table completed
        $table = new xmldb_table('evaluation_completed');
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('teacherid', XMLDB_INDEX_NOTUNIQUE, ['teacherid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2022042900;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_enrolments');
        // Adding fields to table evaluation_enrolments.	
        $field = new xmldb_field('fullname', XMLDB_TYPE_TEXT, null, null, false, null, '', 'courseid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('shortname', XMLDB_TYPE_TEXT, null, null, false, null, '', 'fullname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2022072503;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_users');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_NOTNULL, null, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('alternatename', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
        $table->add_field('teacher', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('student', XMLDB_TYPE_INTEGER, '6', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table evaluation_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Adding indexes to table evaluation_users.
        $table->add_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);
        $table->add_index('username', XMLDB_INDEX_NOTUNIQUE, ['username']);
        // Conditionally launch create table for evaluation_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2022081509;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('anonymized', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'anonymous');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('evaluation_value');
        // Rename field names course_id to courseid and teacher_id to teacherid
        $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "courseid");
        }
        $field = new xmldb_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "teacherid");
        }

        $index = new xmldb_index('completed_item', XMLDB_INDEX_UNIQUE, ['completed, item, courseid, teacherid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $dbman->add_index($table, $index);
        $index = new xmldb_index('course_id', XMLDB_INDEX_NOTUNIQUE, ['course_id']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        $index = new xmldb_index('teacher_id', XMLDB_INDEX_NOTUNIQUE, ['teacher_id']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $index = new xmldb_index('teacherid', XMLDB_INDEX_NOTUNIQUE, ['teacherid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        };

        $table = new xmldb_table('evaluation_valuetmp');
        $field = new xmldb_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "courseid");
        }
        $field = new xmldb_field('teacher_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "teacherid");
        }

        $index = new xmldb_index('completed_item', XMLDB_INDEX_UNIQUE, ['completed, item, courseid, teacherid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2022122402;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('semester', XMLDB_TYPE_CHAR, '5', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2023010802;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('show_on_index', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', false);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('min_results_text', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '6', false);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('min_results_priv', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '3', false);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('filter_add_courses', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "filter_courses", true, true);
        }
        $field = new xmldb_field('evaluation_min_results', XMLDB_TYPE_INTEGER, null, null, XMLDB_NOTNULL, null, '3', false);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, "min_results", true, true);
        }

        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2023021801;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_users');
        //$table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $field = new xmldb_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('evaluation_enrolments');
        $field = new xmldb_field('active_students', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('active_teachers', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('evaluation_users_la');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('evaluation', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('role', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, '');
        $table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table evaluation_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Adding indexes to table evaluation_users.
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        $table->add_index('evaluation', XMLDB_INDEX_NOTUNIQUE, ['evaluation']);
        // Conditionally launch create table for evaluation_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2023021803;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_users_la');
        $index = new xmldb_index('userid', XMLDB_INDEX_UNIQUE, ['userid']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $table = new xmldb_table('evaluation_users_la');
        $index = new xmldb_index('userid', XMLDB_INDEX_NOTUNIQUE, ['userid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2023122700;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_enrolments');
        $field = new xmldb_field('department', XMLDB_TYPE_CHAR, '100', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }


    $newversion = 2024012500;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_users_la');
        $field = new xmldb_field('teacherids', XMLDB_TYPE_CHAR, '600', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }


    $newversion = 2024012501;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_users_la');
        $field = new xmldb_field('teacherids', XMLDB_TYPE_CHAR, '600', null, null, null, '');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field,"courseids");
        }
        $field = new xmldb_field('courseids', XMLDB_TYPE_CHAR, '600', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2024012900;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_completed');
        $index = new xmldb_index('timemodified', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('evaluation_enrolments');
        $index = new xmldb_index('evaluation', XMLDB_INDEX_NOTUNIQUE, ['evaluation']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('evaluation_users');
        $index = new xmldb_index('lastaccess', XMLDB_INDEX_NOTUNIQUE, ['lastaccess']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('evaluation_users_la');
        $index = new xmldb_index('evaluation', XMLDB_INDEX_NOTUNIQUE, ['evaluation']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('evaluation_users_la');
        $index = new xmldb_index('lastaccess', XMLDB_INDEX_NOTUNIQUE, ['lastaccess']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2024020300;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('reminders', XMLDB_TYPE_TEXT, null, XMLDB_NOTNULL, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2024101400;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');
        $field = new xmldb_field('sort_tag',  XMLDB_TYPE_CHAR, '150', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    $newversion = 2024111101;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation');

        $field = new xmldb_field('autoreminders',  XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sendername',  XMLDB_TYPE_CHAR, '150', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('sendermail',  XMLDB_TYPE_CHAR, '150', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('signature',  XMLDB_TYPE_CHAR, '150', null, null, null, '');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }


    $newversion = 2025011800;
    if ($oldversion < $newversion) {
        $table = new xmldb_table('evaluation_translator');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('source_lang', XMLDB_TYPE_CHAR, '6', null, null, null, '');
        $table->add_field('target_lang', XMLDB_TYPE_CHAR, '6', null, null, null, '');
        $table->add_field('source_string', XMLDB_TYPE_TEXT, null, null, false, null, '');
        $table->add_field('target_string', XMLDB_TYPE_TEXT, null, null, false, null, '');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        // Adding keys to table evaluation_users.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        // Adding indexes to table evaluation_users.
        $table->add_index('source_lang', XMLDB_INDEX_NOTUNIQUE, ['source_lang']);
        $table->add_index('target_lang', XMLDB_INDEX_NOTUNIQUE, ['target_lang']);
        // $table->add_index('source_string', XMLDB_INDEX_NOTUNIQUE, ['source_string']);
        // $table->add_index('source_lang_string', XMLDB_INDEX_NOTUNIQUE, ['source_lang,source_string']);
        // Conditionally launch create table for evaluation_users.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, $newversion, 'evaluation');
    }

    return true;
}

