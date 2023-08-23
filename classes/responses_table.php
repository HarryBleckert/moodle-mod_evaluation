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
 * Contains class mod_evaluation_responses_table
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');

/**
 * Class mod_evaluation_responses_table
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_responses_table extends table_sql {

    /**
     * Maximum number of evaluation questions to display in the "Show responses" table
     */
    const PREVIEWCOLUMNSLIMIT = 20;

    /**
     * Maximum number of evaluation questions answers to retrieve in one SQL query.
     * Mysql has a limit of 60, we leave 1 for joining with users table.
     */
    const TABLEJOINLIMIT = 59;

    /**
     * When additional queries are needed to retrieve more than TABLEJOINLIMIT questions answers, do it in chunks every x rows.
     * Value too small will mean too many DB queries, value too big may cause memory overflow.
     */
    const ROWCHUNKSIZE = 100;

    /** @var mod_evaluation_structure */
    protected $evaluationstructure;

    /** @var int */
    protected $grandtotal = null;

    /** @var bool */
    protected $showall = false;

    /** @var string */
    protected $showallparamname = 'showall';

    /** @var string */
    protected $downloadparamname = 'download';

    /** @var int number of columns that were not retrieved in the main SQL query
     * (no more than TABLEJOINLIMIT tables with values can be joined). */
    protected $hasmorecolumns = 0;

    /** @var bool whether we are building this table for a external function */
    protected $buildforexternal = false;

    /** @var array the data structure containing the table data for the external function */
    protected $dataforexternal = [];

    // teacherid from completed
    //protected $teacherid = 0;
    /**
     * Constructor
     *
     * @param mod_evaluation_structure $evaluationstructure
     * @param int $group retrieve only users from this group (optional)
     */
    public function __construct(mod_evaluation_structure $evaluationstructure, $group = 0) {
        $this->evaluationstructure = $evaluationstructure;

        parent::__construct('evaluation-showentry-list-' . $evaluationstructure->get_cm()->instance);

        $this->showall = optional_param($this->showallparamname, 0, PARAM_BOOL);
        $this->define_baseurl(new moodle_url('/mod/evaluation/show_entries.php',
                ['id' => $this->evaluationstructure->get_cm()->id]));
        if ($courseid = $this->evaluationstructure->get_courseid()) {
            $this->baseurl->param('courseid', $courseid);
        }
        if ($course_of_studies = $this->evaluationstructure->get_course_of_studies()) {
            $this->baseurl->param('course_of_studies', $course_of_studies);
        }
        if ($teacherid = $this->evaluationstructure->get_teacherid()) {
            $this->baseurl->param('teacherid', $teacherid);
        }
        if ($this->showall) {
            $this->baseurl->param($this->showallparamname, $this->showall);
        }

        $name = format_string($evaluationstructure->get_evaluation()->name . " (Rohdaten)");
        $this->is_downloadable(true);
        $this->is_downloading(optional_param($this->downloadparamname, 0, PARAM_ALPHA),
                $name, get_string('responses', 'evaluation'));
        $this->useridfield = 'userid';
        $this->init($group);
    }

    /**
     * Initialises table
     *
     * @param int $group retrieve only users from this group (optional)
     */
    protected function init($group = 0) {

        $tablecolumns = array('userpic', 'fullname', 'groups');
        $tableheaders = array(
                get_string('userpic'),
                get_string('fullnameuser'),
                get_string('groups')
        );

        // TODO Does not support custom user profile fields (MDL-70456).
        $userfieldsapi = \core_user\fields::for_identity($this->get_context(), false)->with_userpic();
        $ufields = $userfieldsapi->get_sql('u', false, '', $this->useridfield, false)->selects;
        $extrafields = $userfieldsapi->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);
        $fields = 'c.id, c.timemodified as completed_timemodified, c.courseid, c.course_of_studies, c.teacherid, ' . $ufields;
        $from = '{evaluation_completed} c '
                . 'JOIN {user} u ON u.id = c.userid'; // ignore deletion status: AND u.deleted = :notdeleted

        $where = 'c.anonymous_response = :anon
                AND c.evaluation = :instance';

        if ($this->evaluationstructure->get_courseid()) {
            $where .= ' AND c.courseid = :courseid';
        }
        if ($this->evaluationstructure->get_course_of_studies()) {
            $where .= " AND c.course_of_studies = '" . $this->evaluationstructure->get_course_of_studies() . "'";
        }
        if ($this->evaluationstructure->get_teacherid()) {
            $where .= ' AND c.teacherid = ' . $this->evaluationstructure->get_teacherid();
        }
        // handle CoS privileged users
        $CoS_Filter = evaluation_get_cosPrivileged_filter($this->evaluationstructure->get_evaluation(), "c");
        if ($CoS_Filter) {
            $where .= $CoS_Filter;
        }

        if ($this->is_downloading()) {
            // When downloading data:
            // Remove 'userpic' from downloaded data.
            array_shift($tablecolumns);
            array_shift($tableheaders);

            // Add all identity fields as separate columns.
            foreach ($extrafields as $field) {
                $fields .= ", u.{$field}";
                $tablecolumns[] = $field;
                $tableheaders[] = get_user_field_name($field);
            }
        }

        $tablecolumns[] = 'completed_timemodified';
        $tableheaders[] = get_string('date');

        //if ( $this->evaluationstructure->get_evaluation()->teamteaching )
        $tablecolumns[] = 'teacherid';
        $tableheaders[] = get_string('teacher', 'evaluation');

        if ($this->evaluationstructure->get_evaluation()->course == SITEID) {
            if (!$this->evaluationstructure->get_courseid()) {
                $tablecolumns[] = 'courseid';
                $tableheaders[] = get_string('course');
                $this->no_sorting('courseid');
            }
            if (!$this->evaluationstructure->get_course_of_studies()
                    and !evaluation_is_item_course_of_studies($this->evaluationstructure->get_evaluation()->id)) {
                $tablecolumns[] = 'course_of_studies';
            }
            $tableheaders[] = get_string('course_of_studies', 'evaluation');
        }

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);

        $this->sortable(true, 'lastname', SORT_ASC);
        $this->no_sorting('groups');
        $this->collapsible(true);
        $this->set_attribute('id', 'showentrytable');

        $params = array();
        $params['anon'] = EVALUATION_ANONYMOUS_NO;
        $params['instance'] = $this->evaluationstructure->get_evaluation()->id;
        // we need to ignore deletion status, else stats will fail
        //$params['notdeleted'] = 0;
        $params['courseid'] = $this->evaluationstructure->get_courseid();
        $params['teacherid'] = $this->evaluationstructure->get_teacherid();
        $params['course_of_studies'] = $this->evaluationstructure->get_course_of_studies();

        $group = (empty($group)) ? groups_get_activity_group($this->evaluationstructure->get_cm(), true) : $group;
        if ($group) {
            $where .= ' AND c.userid IN (SELECT g.userid FROM {groups_members} g WHERE g.groupid = :group)';
            $params['group'] = $group;
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(c.id) FROM $from WHERE $where", $params);
    }

    /**
     * Current context
     *
     * @return context_module
     */
    public function get_context(): context {
        return context_module::instance($this->evaluationstructure->get_cm()->id);
    }

    /**
     * Defines columns
     *
     * @param array $columns an array of identifying names for columns. If
     * columns are sorted then column names must correspond to a field in sql.
     */
    public function define_columns($columns) {
        parent::define_columns($columns);
        foreach ($this->columns as $column => $column) {
            // Automatically assign classes to columns.
            $this->column_class[$column] = ' ' . $column;
        }
    }

    /**
     * Prepares column userpic for display
     *
     * @param stdClass $row
     * @return string
     */
    public function col_userpic($row) {
        global $OUTPUT;
        $user = user_picture::unalias($row, [], $this->useridfield);
        return $OUTPUT->user_picture($user, array('courseid' => $this->evaluationstructure->get_cm()->course));
    }

    /**
     * Prepares column deleteentry for display
     *
     * @param stdClass $row
     * @return string
     */
    public function col_deleteentry($row) {
        global $OUTPUT;
        $deleteentryurl = new moodle_url($this->baseurl, ['delete' => $row->id, 'sesskey' => sesskey()]);
        $deleteaction = new confirm_action(get_string('confirmdeleteentry', 'evaluation'));
        return $OUTPUT->action_icon($deleteentryurl,
                new pix_icon('t/delete', get_string('delete_entry', 'evaluation')), $deleteaction);
    }

    /**
     * Prepares column completed_timemodified for display
     *
     * @param stdClass $student
     * @return string
     */
    public function col_completed_timemodified($row) {
        if ($this->is_downloading()) {
            return userdate($row->completed_timemodified);
        } else {
            return html_writer::link($this->get_link_single_entry($row),
                    userdate($row->completed_timemodified));
        }
    }

    /**
     * Returns a link for viewing a single response
     *
     * @param stdClass $row
     * @return \moodle_url
     */
    protected function get_link_single_entry($row) {
        return new moodle_url($this->baseurl, ['userid' => $row->{$this->useridfield}, 'showcompleted' => $row->id]);
    }

    /**
     * Prepares column courseid for display
     *
     * @param array $row
     * @return string
     */
    public function col_courseid($row) {
        $courses = $this->evaluationstructure->get_completed_courses();
        $name = '';
        if (isset($courses[$row->courseid])) {
            $name = $courses[$row->courseid];
            if (!$this->is_downloading()) {
                $name = html_writer::link(course_get_url($row->courseid), $name, array("target" => "course"));
            }
        }
        return $name;
    }

    /**
     * Prepares column teacherid for display
     *
     * @param array $row
     * @return string
     */
    public function col_teacherid($row) {
        $teachers = $this->evaluationstructure->get_completed_teachers();
        $name = '';
        //if (isset($teachers[$row->teacherid])) {
        if (isset($teachers[$row->teacherid])) {
            $name = $teachers[$row->teacherid];
            if (!$this->is_downloading() and !empty($name)) {
                if (defined("EVALUATION_OWNER")) {
                    $name = '<a href="/mod/evaluation/print.php?id=' . $this->evaluationstructure->get_cm()->id
                            . '&showTeacher=' . $row->teacherid . '&courseid=' . $row->courseid . '" target="showteacher">' .
                            $name . '</a>';
                } else {
                    $name = '<a href="/user/profile.php?id=' . $row->teacherid . '" target="teacher">' . $name . '</a>';
                }
            }
        }
        //print "teachers[row->teacherid]: ".$teachers[$row->teacherid]."<br>".nl2br(var_export($teachers,true));exit;
        return $name;
    }

    /**
     * Prepares column studiengang for display
     *
     * @param array $row
     * @return string
     */
    public function col_course_of_studies($row) {
        //$courses = $this->evaluationstructure->get_completed_course_of_studies();
        $name = '';
        if (isset($row->course_of_studies)) {
            $name = $row->course_of_studies;
        }
        return $name;
    }

    /**
     * Prepares column groups for display
     *
     * @param array $row
     * @return string
     */
    public function col_groups($row) {
        $groups = '';
        if ($usergrps = groups_get_all_groups($this->evaluationstructure->get_cm()->course, $row->userid, 0, 'name')) {
            foreach ($usergrps as $group) {
                $groups .= format_string($group->name) . ' ';
            }
        }
        return trim($groups);
    }

    /**
     * Displays the table
     */
    public function display() {
        global $OUTPUT;
        groups_print_activity_menu($this->evaluationstructure->get_cm(), $this->baseurl->out());
        $grandtotal = $this->get_total_responses_count();
        if (!$grandtotal) {
            echo $OUTPUT->box(get_string('nothingtodisplay'), 'generalbox nothingtodisplay');
            return;
        }

        if (!isset($_SESSION['questionslimited']) and
                count($this->evaluationstructure->get_items(true)) > self::PREVIEWCOLUMNSLIMIT) {
            echo $OUTPUT->notification(get_string('questionslimited', 'evaluation', self::PREVIEWCOLUMNSLIMIT), 'info');
        }
        $_SESSION['questionslimited'] = true;
        $this->out($this->showall ? $grandtotal : EVALUATION_DEFAULT_PAGE_COUNT,
                $grandtotal > EVALUATION_DEFAULT_PAGE_COUNT);

        // Toggle 'Show all' link.
        if ($this->totalrows > EVALUATION_DEFAULT_PAGE_COUNT) {
            if (!$this->use_pages) {
                echo html_writer::div(html_writer::link(new moodle_url($this->baseurl, [$this->showallparamname => 0]),
                        get_string('showperpage', '', EVALUATION_DEFAULT_PAGE_COUNT)), 'showall');
            } else {
                echo html_writer::div(html_writer::link(new moodle_url($this->baseurl, [$this->showallparamname => 1]),
                        get_string('showall', '', $this->totalrows)), 'showall');
            }
        }
    }

    /**
     * Convenience method to call a number of methods for you to display the
     * table.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @param string $downloadhelpbutton
     */
    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $this->add_all_values_to_output();
        parent::out($pagesize, $useinitialsbar, $downloadhelpbutton);
    }

    /**
     * Adds common values to the table that do not change the number or order of entries and
     * are only needed when outputting or downloading data.
     */
    protected function add_all_values_to_output() {
        $tablecolumns = array_keys($this->columns);
        $tableheaders = $this->headers;

        $items = $this->evaluationstructure->get_items(true);
        if (!$this->is_downloading() && !$this->buildforexternal) {
            // In preview mode do not show all columns or the page becomes unreadable.
            // The information message will be displayed to the teacher that the rest of the data can be viewed when downloading.
            $items = array_slice($items, 0, self::PREVIEWCOLUMNSLIMIT, true);
        }

        $columnscount = 0;
        $this->hasmorecolumns = max(0, count($items) - self::TABLEJOINLIMIT);

        $headernamepostfix = !$this->is_downloading();
        // Add evaluation response values.
        foreach ($items as $nr => $item) {
            if ($columnscount++ < self::TABLEJOINLIMIT) {
                // Mysql has a limit on the number of tables in the join, so we only add limited number of columns here,
                // the rest will be added in {@link self::build_table()} and {@link self::build_table_chunk()} functions.
                $this->sql->fields .= ", v{$nr}.value AS val{$nr}";
                $this->sql->from .= " LEFT OUTER JOIN {evaluation_value} v{$nr} " .
                        "ON v{$nr}.completed = c.id AND v{$nr}.item = :itemid{$nr}";
                $this->sql->params["itemid{$nr}"] = $item->id;
            }

            $tablecolumns[] = "val{$nr}";
            $itemobj = evaluation_get_item_class($item->typ);
            $columnheader = $itemobj->get_display_name($item, $headernamepostfix);
            if (!$this->is_downloading()) {
                $columnheader = shorten_text($columnheader);
            }
            if (strval($item->label) !== '') {
                $columnheader = get_string('nameandlabelformat', 'mod_evaluation',
                        (object) ['label' => format_string($item->label), 'name' => $columnheader]);
            }
            $tableheaders[] = $columnheader;
        }

        // Add 'Delete entry' column.
        if (!$this->is_downloading() && has_capability('mod/evaluation:deletesubmissions', $this->get_context())) {
            $tablecolumns[] = 'deleteentry';
            $tableheaders[] = '';
        }

        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);
    }

    /**
     * Returns total number of reponses (without any filters applied)
     *
     * @return int
     */
    public function get_total_responses_count() {
        global $DB;
        if ($this->grandtotal === null) {
            $this->grandtotal = $DB->count_records_sql($this->countsql, $this->countparams);
        }
        return $this->grandtotal;
    }

    /**
     * Returns links to previous/next responses in the list
     *
     * @param stdClass $record
     * @return array array of three elements [$prevresponseurl, $returnurl, $nextresponseurl]
     */
    public function get_reponse_navigation_links($record) {
        $this->setup();
        $grandtotal = $this->get_total_responses_count();
        $this->query_db($grandtotal);
        $lastrow = $thisrow = $nextrow = null;
        $counter = 0;
        $page = 0;
        while ($this->rawdata->valid()) {
            $row = $this->rawdata->current();
            if ($row->id == $record->id) {
                $page = $this->showall ? 0 : floor($counter / EVALUATION_DEFAULT_PAGE_COUNT);
                $thisrow = $row;
                $this->rawdata->next();
                $nextrow = $this->rawdata->valid() ? $this->rawdata->current() : null;
                break;
            }
            $lastrow = $row;
            $this->rawdata->next();
            $counter++;
        }
        $this->rawdata->close();
        if (!$thisrow) {
            $lastrow = null;
        }
        return [
                $lastrow ? $this->get_link_single_entry($lastrow) : null,
                new moodle_url($this->baseurl, [$this->request[TABLE_VAR_PAGE] => $page]),
                $nextrow ? $this->get_link_single_entry($nextrow) : null,
        ];
    }

    /**
     * Query the db. Store results in the table object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar. Bar
     * will only be used if there is a fullname column defined for the table.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;
        $this->totalrows = $grandtotal = $this->get_total_responses_count();
        if (!$this->is_downloading()) {
            $this->initialbars($useinitialsbar);

            list($wsql, $wparams) = $this->get_sql_where();
            if ($wsql) {
                $this->countsql .= ' AND ' . $wsql;
                $this->countparams = array_merge($this->countparams, $wparams);

                $this->sql->where .= ' AND ' . $wsql;
                $this->sql->params = array_merge($this->sql->params, $wparams);

                $this->totalrows = $DB->count_records_sql($this->countsql, $this->countparams);
            }

            if ($this->totalrows > $pagesize) {
                $this->pagesize($pagesize, $this->totalrows);
            }
        }

        if ($sort = $this->get_sql_sort()) {
            $sort = "ORDER BY $sort";
        }
        $sql = "SELECT
                {$this->sql->fields}
                FROM {$this->sql->from}
                WHERE {$this->sql->where}
                {$sort}";

        if (!$this->is_downloading()) {
            $this->rawdata = $DB->get_recordset_sql($sql, $this->sql->params, $this->get_page_start(), $this->get_page_size());
        } else {
            $this->rawdata = $DB->get_recordset_sql($sql, $this->sql->params);
        }
    }

    /**
     * Download the data.
     */
    public function download() {
        \core\session\manager::write_close();
        $this->out($this->get_total_responses_count(), false);
        exit;
    }

    /**
     * Returns html code for displaying "Download" button if applicable.
     */
    public function download_buttons() {
        global $OUTPUT;

        if ($this->is_downloadable() && !$this->is_downloading()) {
            return $OUTPUT->download_dataformat_selector(get_string('downloadas', 'table'),
                    $this->baseurl->out_omit_querystring(), $this->downloadparamname, $this->baseurl->params());
        } else {
            return '';
        }
    }

    /**
     * Exports the table as an external structure handling pagination.
     *
     * @param int $page page number (for pagination)
     * @param int $perpage elements per page
     * @return array returns the table ready to be used by an external function
     * @since Moodle 3.3
     */
    public function export_external_structure($page = 0, $perpage = 0) {

        $this->buildforexternal = true;
        $this->add_all_values_to_output();
        // Set-up.
        $this->setup();
        // Override values, if needed.
        if ($perpage > 0) {
            $this->pageable = true;
            $this->currpage = $page;
            $this->pagesize = $perpage;
        } else {
            $this->pagesize = $this->get_total_responses_count();
        }
        $this->query_db($this->pagesize, false);
        $this->build_table();
        $this->close_recordset();
        return $this->dataforexternal;
    }

    /**
     * Take the data returned from the db_query and go through all the rows
     * processing each col using either col_{columnname} method or other_cols
     * method or if other_cols returns NULL then put the data straight into the
     * table.
     *
     * This overwrites the parent method because full SQL query may fail on Mysql
     * because of the limit in the number of tables in the join. Therefore we only
     * join 59 tables in the main query and add the rest here.
     *
     * @return void
     */
    public function build_table() {
        if ($this->rawdata instanceof \Traversable && !$this->rawdata->valid()) {
            return;
        }
        if (!$this->rawdata) {
            return;
        }

        $columnsgroups = [];
        if ($this->hasmorecolumns) {
            $items = $this->evaluationstructure->get_items(true);
            $notretrieveditems = array_slice($items, self::TABLEJOINLIMIT, $this->hasmorecolumns, true);
            $columnsgroups = array_chunk($notretrieveditems, self::TABLEJOINLIMIT, true);
        }

        $chunk = [];
        foreach ($this->rawdata as $row) {
            if ($this->hasmorecolumns) {
                $chunk[$row->id] = $row;
                if (count($chunk) >= self::ROWCHUNKSIZE) {
                    $this->build_table_chunk($chunk, $columnsgroups);
                    $chunk = [];
                }
            } else {
                if ($this->buildforexternal) {
                    $this->add_data_for_external($row);
                } else {
                    $this->add_data_keyed($this->format_row($row), $this->get_row_class($row));
                }
            }
        }
        $this->build_table_chunk($chunk, $columnsgroups);
    }

    /**
     * Retrieve additional columns. Database engine may have a limit on number of joins.
     *
     * @param array $rows Array of rows with already retrieved data, new values will be added to this array
     * @param array $columnsgroups array of arrays of columns. Each element has up to self::TABLEJOINLIMIT items. This
     *     is easy to calculate but because we can call this method many times we calculate it once and pass by
     *     reference for performance reasons
     */
    protected function build_table_chunk(&$rows, &$columnsgroups) {
        global $DB;
        if (!$rows) {
            return;
        }

        foreach ($columnsgroups as $columnsgroup) {
            $fields = 'c.id';
            $from = '{evaluation_completed} c';
            $params = [];
            foreach ($columnsgroup as $nr => $item) {
                $fields .= ", v{$nr}.value AS val{$nr}";
                $from .= " LEFT OUTER JOIN {evaluation_value} v{$nr} " .
                        "ON v{$nr}.completed = c.id AND v{$nr}.item = :itemid{$nr}";
                $params["itemid{$nr}"] = $item->id;
            }
            list($idsql, $idparams) = $DB->get_in_or_equal(array_keys($rows), SQL_PARAMS_NAMED);
            $sql = "SELECT $fields FROM $from WHERE c.id " . $idsql;
            $results = $DB->get_records_sql($sql, $params + $idparams);
            foreach ($results as $result) {
                foreach ($result as $key => $value) {
                    $rows[$result->id]->{$key} = $value;
                }
            }
        }

        foreach ($rows as $row) {
            if ($this->buildforexternal) {
                $this->add_data_for_external($row);
            } else {
                $this->add_data_keyed($this->format_row($row), $this->get_row_class($row));
            }
        }
    }

    /**
     * Add data for the external structure that will be returned.
     *
     * @param stdClass $row a database query record row
     * @since Moodle 3.3
     */
    protected function add_data_for_external($row) {
        $this->dataforexternal[] = [
                'id' => $row->id,
                'courseid' => $row->courseid,
                'course_of_studies' => $row->course_of_studies,
                'teacherid' => $row->teacherid,
                'userid' => $row->userid,
                'fullname' => fullname($row),
                'timemodified' => $row->completed_timemodified,
                'responses' => $this->get_responses_for_external($row),
        ];
    }

    /**
     * Return user responses data ready for the external function.
     *
     * @param stdClass $row the table row containing the responses
     * @return array returns the responses ready to be used by an external function
     * @since Moodle 3.3
     */
    protected function get_responses_for_external($row) {
        $responses = [];
        foreach ($row as $el => $val) {
            // Get id from column name.
            if (preg_match('/^val(\d+)$/', $el, $matches)) {
                $id = $matches[1];

                $responses[] = [
                        'id' => $id,
                        'name' => $this->headers[$this->columns[$el]],
                        'printval' => $this->other_cols($el, $row),
                        'rawval' => $val,
                ];
            }
        }
        return $responses;
    }

    /**
     * Allows to set the display column value for all columns without "col_xxxxx" method.
     *
     * @param string $column column name
     * @param stdClass $row current record result of SQL query
     */
    public function other_cols($column, $row) {
        if (preg_match('/^val(\d+)$/', $column, $matches)) {
            $items = $this->evaluationstructure->get_items();
            $itemobj = evaluation_get_item_class($items[$matches[1]]->typ);
            $printval = $itemobj->get_printval($items[$matches[1]], (object) ['value' => $row->$column]);
            if ($this->is_downloading()) {
                $printval = s($printval);
            }
            return trim($printval);
        }
        return parent::other_cols($column, $row);
    }
}
