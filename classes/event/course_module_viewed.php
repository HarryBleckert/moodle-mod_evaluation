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
 * The mod_evaluation course module viewed event.
 *
 * @package    mod_evaluation
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_evaluation\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_evaluation course module viewed event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      - int anonymous if evaluation is anonymous.
 * }
 *
 * @package    mod_evaluation
 * @since      Moodle 2.6
 * @copyright  2013 Ankit Agarwal
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Creates an instance from evaluation record
     *
     * @param stdClass $evaluation
     * @param cm_info|stdClass $cm
     * @param stdClass $course
     * @return course_module_viewed
     */
    public static function create_from_record($evaluation, $cm, $course) {
        $event = self::create(array(
                'objectid' => $evaluation->id,
                'context' => \context_module::instance($cm->id),
                'anonymous' => ($evaluation->anonymous == EVALUATION_ANONYMOUS_YES),
                'other' => array(
                        'anonymous' => $evaluation->anonymous // Deprecated.
                )
        ));
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('evaluation', $evaluation);
        return $event;
    }

    public static function get_objectid_mapping() {
        return array('db' => 'evaluation', 'restore' => 'evaluation');
    }

    public static function get_other_mapping() {
        // No need to map the 'anonymous' flag.
        return false;
    }

    /**
     * Define whether a user can view the event or not. Make sure no one except admin can see details of an anonymous response.
     *
     * @param int|\stdClass $userorid ID of the user.
     * @return bool True if the user can view the event, false otherwise.
     * @deprecated since 2.7
     *
     */
    public function can_view($userorid = null) {
        global $USER;
        debugging('can_view() method is deprecated, use anonymous flag instead if necessary.', DEBUG_DEVELOPER);

        if (empty($userorid)) {
            $userorid = $USER;
        }
        if ($this->anonymous) {
            return is_siteadmin($userorid);
        } else {
            return has_capability('mod/evaluation:viewreports', $this->context, $userorid);
        }
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'evaluation';
    }

    /**
     * Replace add_to_log() statement.Do this only for the case when anonymous mode is off,
     * since this is what was happening before.
     *
     * @return array of parameters to be passed to legacy add_to_log() function.
     */
    protected function get_legacy_logdata() {
        if ($this->anonymous) {
            return null;
        } else {
            return parent::get_legacy_logdata();
        }
    }

    /**
     * Custom validations.
     *
     * @throws \coding_exception in case of any problems.
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['anonymous'])) {
            throw new \coding_exception('The \'anonymous\' value must be set in other.');
        }
    }
}

