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
 * Contains class mod_evaluation_responses_anon_table
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class mod_evaluation_responses_anon_table
 *
 * @package   mod_evaluation
 * @copyright 2016 Marina Glancy
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_evaluation_responses_anon_table extends mod_evaluation_responses_table {

    /** @var string */
    protected $showallparamname = 'ashowall';

    /** @var string */
    protected $downloadparamname = 'adownload';

    /**
     * Initialises table
     * @param int $group retrieve only users from this group (optional)
     */
    public function init($group = 0) {

        $cm = $this->evaluationstructure->get_cm();
        $this->uniqueid = 'evaluation-showentry-anon-list-' . $cm->instance;

        // There potentially can be both tables with anonymouns and non-anonymous responses on
        // the same page (for example when evaluation anonymity was changed after some people
        // already responded). In this case we need to distinguish tables' pagination parameters.
        $this->request[TABLE_VAR_PAGE] = 'apage';

        $tablecolumns = ['random_response'];
        $tableheaders = [get_string('response_nr', 'evaluation')];

		if ( defined('EVALUATION_OWNER') )
		{	$tablecolumns[] = 'completed_timemodified';
			$tableheaders[] = get_string('date'); 
		}
		
        //if ( $this->evaluationstructure->get_evaluation()->teamteaching )
        $tablecolumns[] = 'teacherid';
		$tableheaders[] = get_string('teacher','evaluation');
		

        if ($this->evaluationstructure->get_evaluation()->course == SITEID) 
		{	if ( !$this->evaluationstructure->get_courseid() )
			{	$tablecolumns[] = 'courseid';
				$tableheaders[] = get_string('course');
				$this->no_sorting('courseid');
			}
			if ( !$this->evaluationstructure->get_course_of_studies() 
				AND !evaluation_is_item_course_of_studies($this->evaluationstructure->get_evaluation()->id) )
			{	$tablecolumns[] = 'course_of_studies';
				$tableheaders[] = get_string('course_of_studies','evaluation');
			}
        }
		
        $this->define_columns($tablecolumns);
        $this->define_headers($tableheaders);

        $this->sortable(true, 'random_response');
        $this->collapsible(true);
        $this->set_attribute('id', 'showentryanontable');

        $params = ['instance' => $cm->instance,
            'anon' => EVALUATION_ANONYMOUS_YES,
            'courseid' => $this->evaluationstructure->get_courseid(), 
            'course_of_studies' => $this->evaluationstructure->get_course_of_studies(), 
			'teacherid' => $this->evaluationstructure->get_teacherid()
			];

        $fields = 'c.id, c.random_response, c.courseid, c.course_of_studies, c.teacherid, c.timemodified as completed_timemodified'; 
		//$fields = 'c.id, c.random_response, c.courseid, c.teacherid, c.timemodified as completed_timemodified'; 
        $from = '{evaluation_completed} c';
        $where = 'c.anonymous_response = :anon AND c.evaluation = :instance ';
        if ($this->evaluationstructure->get_courseid()) {
			$where .= ' AND c.courseid = :courseid';
        }
		if ($this->evaluationstructure->get_course_of_studies()) {
			$where .= ' AND c.course_of_studies = :course_of_studies';
        }
        if ($this->evaluationstructure->get_teacherid()) {
			$where .= ' AND c.teacherid = :teacherid';
        }
		// handle CoS privileged users
		$CoS_Filter = evaluation_get_cosPrivileged_filter( $this->evaluationstructure->get_evaluation(), "c" );
		if ( $CoS_Filter)
		{	$where .= $CoS_Filter; }


        $group = (empty($group)) ? groups_get_activity_group($this->evaluationstructure->get_cm(), true) : $group;
        if ($group) {
            $where .= ' AND c.userid IN (SELECT g.userid FROM {groups_members} g WHERE g.groupid = :group)';
            $params['group'] = $group;
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(c.id) FROM $from WHERE $where", $params);
    }

    /**
     * Returns a link for viewing a single response
     * @param stdClass $row
     * @return \moodle_url
     */
    protected function get_link_single_entry($row) {
        return new moodle_url($this->baseurl, ['showcompleted' => $row->id]);
    }

    /**
     * Prepares column reponse for display
     * @param stdClass $row
     * @return string
     */
    public function col_random_response($row) {
        if ($this->is_downloading()) {
            return $row->random_response;
        } else {
            return html_writer::link($this->get_link_single_entry($row),
                    get_string('response_nr', 'evaluation').': '. $row->random_response);
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
			'teacherid' => $row->teacherid,
            'courseid' => $row->courseid,
			'course_of_studies' => $row->course_of_studies,
            'number' => $row->random_response,
            'responses' => $this->get_responses_for_external($row)
        ];
    }
}
