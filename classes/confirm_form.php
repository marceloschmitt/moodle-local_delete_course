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

defined('MOODLE_INTERNAL') || die();

/**
 * Class to confirm couse deletion.
 *
 * @package     local_delete_course
 * @copyright   2024 Marcelo Augusto Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_delete_course_confirm_form extends moodleform {
    // Add elements to form.
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $mform->addElement('static', 'alert', get_string('alert', 'local_delete_course'));
        $this->add_action_buttons(true, get_string('delete_course', 'local_delete_course'));
    }
}
