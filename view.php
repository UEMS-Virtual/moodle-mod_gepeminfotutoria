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
 * View page for mod_gepeminfortutoria.
 *
 * @package    mod_gepeminfortutoria
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_gepeminfortutoria\output\tutoria_page;

$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n',  0, PARAM_INT);

if ($id) {
    $cm               = get_coursemodule_from_id('gepeminfortutoria', $id, 0, false, MUST_EXIST);
    $course           = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $gepeminfortutoria  = $DB->get_record('gepeminfortutoria', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $gepeminfortutoria  = $DB->get_record('gepeminfortutoria', ['id' => $n], '*', MUST_EXIST);
    $course           = $DB->get_record('course', ['id' => $gepeminfortutoria->course], '*', MUST_EXIST);
    $cm               = get_coursemodule_from_instance('gepeminfortutoria', $gepeminfortutoria->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/gepeminfortutoria:view', $context);

$PAGE->set_url('/mod/gepeminfortutoria/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($gepeminfortutoria->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_gepeminfortutoria');
echo $renderer->render(new tutoria_page($gepeminfortutoria, $cm, $course, $context));

echo $OUTPUT->footer();
