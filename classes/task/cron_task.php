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
 * Contains class mod_evaluation_course_map_form
 *
 * @package mod_evaluation
 * @copyright 2024 by Harry.Bleckert@ASH-Berlin.eu for ASH Berlin
 *
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_evaluation\task;
defined('MOODLE_INTERNAL') || die();

/**
 * A schedule task for assignment cron.
 *
 * @package   mod_assign
 * @copyright 2019 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_assign');
    }

    /**
     * Run assignment cron.
     */
    public function execute() {
        global $CFG;
        // only template, not done yet
        //need for send_reminders
        // return true;
        require_once($CFG->dirroot . '/mod/evaluation/lib.php');
        // \evaluation::cron();
        ev_cron();
        return true;
    }
}

