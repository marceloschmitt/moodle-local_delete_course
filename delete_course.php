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
 * Edit course recompletion settings
 *
 * @package     local_delete_course
 * @copyright   2021 Marcelo A. Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/formslib.php');
require_once('classes/confirm_form.php');

$id = required_param('id', PARAM_INT);

// Perform some basic access control checks.
if ($id) {
    if ($id == SITEID) {
        // Don't allow editing of 'site course' using this form.
        print_error('cannoteditsiteform');
    }
    if (!$course = $DB->get_record('course', array('id' => $id))) {
        print_error('invalidcourseid');
    }
    require_login($course);
    $context = context_course::instance($course->id);
    require_capability('local/delete_course:manage', $context);
} else {
    require_login();
    print_error('needcourseid');
}

//Setup PAGE
$PAGE->set_course($course);
$PAGE->set_url('/local/delete_course/delete_course.php', array('id' => $course->id));
$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');

$form = new local_delete_course_confirm_form('delete_course.php?id='.$id, array('course' => $course));

// Se foi cancelado
if ($form->is_cancelled()) {
	redirect($CFG->wwwroot.'/course/view.php?id='.$course->id);
// Se foi confirmado.
} else if ($data = $form->get_data()) {
    $strdeletingcourse = get_string("deletingcourse", "local_delete_course") . " " .
	       	$course->shortname;
    $categoryurl = new moodle_url('/course/index.php', array('categoryid' => $course->category));
    $PAGE->navbar->add($strdeletingcourse);
    $PAGE->set_title("$SITE->shortname: $strdeletingcourse");
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdeletingcourse);
    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();
    // We do this here because it spits out feedback as it goes.
    delete_course($course);
    echo $OUTPUT->heading( get_string("deletedcourse", "", $course->shortname) );
    // Update course count in categories.
    fix_course_sortorder();
    echo $OUTPUT->continue_button($categoryurl);
    echo $OUTPUT->footer();
    exit; // We must exit here!!!
}
// Se foi a primeira vez
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('delete_course', 'local_delete_course'));
$form->display();
echo $OUTPUT->footer();