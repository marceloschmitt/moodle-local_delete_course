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
 * General functions for recompletion plugin.
 *
 * @package    local_delete_course
 * @copyright  2021 Marcelo A. Rauh Schmitt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * This function extends the navigation with the delete_course item
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass        $course     The course to object for the tool
 * @param context         $context    The context of the course
 */
function local_delete_course_extend_navigation_course($navigation, $course, $context) {
    global $CFG, $PAGE;

    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    if (has_capability('local/delete_course:manage', $context)) {
        $url = new moodle_url('/local/delete_course/delete_course.php', array('id' => $course->id));
        $name = get_string('delete_course', 'local_delete_course');
        $navigation->add($name, $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/delete', ''));
    }
}