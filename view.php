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
 * View page for mod_gepeminfotutoria.
 *
 * @package    mod_gepeminfotutoria
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use mod_gepeminfotutoria\output\tutoria_page;

$id = optional_param('id', 0, PARAM_INT);
$n  = optional_param('n',  0, PARAM_INT);

if ($id) {
    $cm               = get_coursemodule_from_id('gepeminfotutoria', $id, 0, false, MUST_EXIST);
    $course           = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $gepeminfotutoria  = $DB->get_record('gepeminfotutoria', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $gepeminfotutoria  = $DB->get_record('gepeminfotutoria', ['id' => $n], '*', MUST_EXIST);
    $course           = $DB->get_record('course', ['id' => $gepeminfotutoria->course], '*', MUST_EXIST);
    $cm               = get_coursemodule_from_instance('gepeminfotutoria', $gepeminfotutoria->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/gepeminfotutoria:view', $context);

$PAGE->set_url('/mod/gepeminfotutoria/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($gepeminfotutoria->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_gepeminfotutoria');
echo $renderer->render(new tutoria_page($gepeminfotutoria, $cm, $course, $context));

echo $OUTPUT->footer();
