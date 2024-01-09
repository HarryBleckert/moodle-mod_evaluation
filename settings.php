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

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array(0 => get_string('no'), 1 => get_string('yes'));
    $str = get_string('configallowfullanonymous', 'evaluation');
    $settings->add(new admin_setting_configselect('evaluation_allowfullanonymous',
            get_string('allowfullanonymous', 'evaluation'),
            $str, 0, $options));

    // set default role(s) of participants. Defaults to role 5 (student)
    global $DB;
    $roles = $DB->get_records_sql("SELECT id,shortname FROM {role} WHERE trim(name) <> '' ORDER BY name asc");
    $participant_roles = array();
    foreach ( $roles as $role ){
        $participant_roles[$role->id] = ucfirst(trim($role->shortname));
    }
    /*
    $name = new lang_string('participant_roles', 'evaluation');
    $description = new lang_string('participant_roles_help', 'evaluation');
    $element = new admin_setting_configmultiselect('evaluation_participant_roles',
            $name,
            $description,
            array(5), $participant_roles);
    $settings->add($element);
    */
    /*
    Monate des Sommersemesters: sommermonths (  Januar Februar März April Mai Juni Juli August September Oktober November Dezember

    Semester identifier in course records: SUBSTRING(idnumber,1,5) or substr(shortname, -1, 5)
    */


}
