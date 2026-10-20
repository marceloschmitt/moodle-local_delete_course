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
 * Confirm and delete a course (sync or async, matching core Moodle 5.3+).
 *
 * @package     local_delete_course
 * @copyright   2021 Marcelo A. Rauh Schmitt
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once($CFG->libdir.'/formslib.php');
require_once('classes/confirm_form.php');

$id = required_param('id', PARAM_INT);

// Perform some basic access control checks.
if ($id) {
    if ($id == SITEID) {
        // Don't allow editing of 'site course' using this form.
        throw new moodle_exception('cannoteditsiteform');
    }
    if (!$course = $DB->get_record('course', ['id' => $id])) {
        throw new moodle_exception('invalidcourseid');
    }
    require_login($course);
    $context = context_course::instance($course->id);
    require_capability('local/delete_course:manage', $context);
} else {
    require_login();
    throw new moodle_exception('needcourseid');
}

// Setup PAGE.
$PAGE->set_course($course);
$PAGE->set_url('/local/delete_course/delete_course.php', ['id' => $course->id]);
$PAGE->set_title($course->shortname);
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('admin');

$categoryurl = new moodle_url('/course/index.php', ['categoryid' => $course->category]);
$courseshortname = format_string($course->shortname, true, ['context' => $context]);

$form = new local_delete_course_confirm_form('delete_course.php?id='.$id, ['course' => $course]);

// Se foi cancelado.
if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
} else if ($data = $form->get_data()) { // Se foi confirmado.
    // Same criterion delete_course() uses to choose async vs sync.
    $asyncdeletion = !empty(get_config('moodlecourse', 'enablecourseasyncdeletion'));

    // Use category context so course navigation is not shown after deletion starts (same as core).
    $PAGE->set_context(context_coursecat::instance($course->category));
    $strdeletingcourse = get_string('deletingcourse', '', $courseshortname);
    $PAGE->navbar->add($strdeletingcourse);
    $PAGE->set_title("$SITE->shortname: $strdeletingcourse");
    $PAGE->set_heading($SITE->fullname);
    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdeletingcourse);

    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();

    // Stream sync progress notifications as they happen (same pattern as course/delete.php).
    echo $OUTPUT->footer();
    echo $OUTPUT->select_element_for_append();

    // Preemptively reset the navcache before closing, so it remains the same on shutdown.
    navigation_cache::destroy_volatile_caches();
    \core\session\manager::write_close();

    // Defaults: showfeedback=true, asyncpreferred=true (async when site setting is on).
    $response = delete_course($course);

    if ($response === false) {
        echo $OUTPUT->notification(get_string('deletingcourseasynchronously_error'), 'error');
        echo $OUTPUT->continue_button($categoryurl);
        exit;
    }

    // Mirror core course/delete.php messaging.
    if ($asyncdeletion) {
        echo $OUTPUT->heading(get_string('deletingcourseasynchronously', 'moodle', $courseshortname));
    } else {
        fix_course_sortorder();
        echo $OUTPUT->heading(get_string('deletedcourse', 'moodle', $courseshortname));
    }

    echo $OUTPUT->continue_button($categoryurl);
    exit; // We must exit here!!!
}

// Se foi a primeira vez.
echo $OUTPUT->header();

// Block deletion while an async course backup is pending (same guard as core course/delete.php).
if (class_exists('async_helper') && async_helper::is_async_pending($id, 'course', 'backup')) {
    echo $OUTPUT->notification(get_string('pendingasyncerror', 'backup'), 'error');
    echo $OUTPUT->container(get_string('pendingasyncdeletedetail', 'backup'));
    echo $OUTPUT->continue_button($categoryurl);
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->heading(get_string('delete_course', 'local_delete_course'));
$form->display();
echo $OUTPUT->footer();
