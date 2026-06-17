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
 * Library callbacks for mod_gepeminfotutoria.
 *
 * @package    mod_gepeminfotutoria
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * List supported features.
 *
 * @param string $feature Feature name.
 * @return mixed True if supported, null otherwise.
 */
function gepeminfotutoria_supports(string $feature) {
    // FEATURE_MOD_PURPOSE / MOD_PURPOSE_CONTENT were added after Moodle 4.2.
    // Keep this callback loadable on 4.2 while still advertising the purpose on newer sites.
    if (defined('FEATURE_MOD_PURPOSE') && $feature === FEATURE_MOD_PURPOSE) {
        return defined('MOD_PURPOSE_CONTENT') ? MOD_PURPOSE_CONTENT : null;
    }

    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_NO_VIEW_LINK:
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        default:
            return null;
    }
}

/**
 * Populate the course-module cache with inline content.
 *
 * Called during course rendering; the returned content replaces the normal
 * activity link so the plugin displays directly on the course page (like Label).
 *
 * @param stdClass $coursemodule Course-module record.
 * @return cached_cm_info|null
 */
function gepeminfotutoria_get_coursemodule_info(stdClass $coursemodule): ?cached_cm_info {
    global $DB;

    $instance = $DB->get_record(
        'gepeminfotutoria',
        ['id' => $coursemodule->instance],
        'id, name, intro, introformat, supporttitle'
    );
    if (!$instance) {
        return null;
    }

    $info = new cached_cm_info();
    $info->name = $instance->name;
    $info->content = format_module_intro('gepeminfotutoria', $instance, $coursemodule->id, false);
    return $info;
}

/**
 * Render the plugin content inline on the course page (Label-style).
 *
 * set_custom_cmlist_item(true) suppresses icon and title; set_content() provides
 * the actual HTML that appears in the course section.
 *
 * @param cm_info $cm Course-module info object.
 */
function gepeminfotutoria_cm_info_view(cm_info $cm): void {
    global $DB, $PAGE;

    if (!$cm->uservisible) {
        return;
    }

    $context = context_module::instance($cm->id);
    if (!has_capability('mod/gepeminfotutoria:view', $context)) {
        return;
    }

    $instance = $DB->get_record('gepeminfotutoria', ['id' => $cm->instance]);
    if (!$instance) {
        if (method_exists($cm, 'set_custom_cmlist_item')) {
            $cm->set_custom_cmlist_item(true);
        }
        return;
    }

    $course  = get_course($cm->course);

    $renderer   = $PAGE->get_renderer('mod_gepeminfotutoria');
    $renderable = new \mod_gepeminfotutoria\output\tutoria_page($instance, $cm, $course, $context);

    $cm->set_content($renderer->render($renderable));
    if (method_exists($cm, 'set_custom_cmlist_item')) {
        $cm->set_custom_cmlist_item(true);
    }
}

/**
 * Add a new instance of the activity.
 *
 * @param stdClass $data Form data.
 * @param mod_gepeminfotutoria_mod_form|null $mform Form instance.
 * @return int New instance id.
 */
function gepeminfotutoria_add_instance(stdClass $data, ?mod_gepeminfotutoria_mod_form $mform = null): int {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = $data->timecreated;

    return $DB->insert_record('gepeminfotutoria', $data);
}

/**
 * Update an existing instance of the activity.
 *
 * @param stdClass $data Form data.
 * @param mod_gepeminfotutoria_mod_form|null $mform Form instance.
 * @return bool
 */
function gepeminfotutoria_update_instance(stdClass $data, ?mod_gepeminfotutoria_mod_form $mform = null): bool {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('gepeminfotutoria', $data);
}

/**
 * Delete an instance of the activity.
 *
 * @param int $id Instance id.
 * @return bool
 */
function gepeminfotutoria_delete_instance(int $id): bool {
    global $DB;

    if (!$DB->record_exists('gepeminfotutoria', ['id' => $id])) {
        return false;
    }

    $DB->delete_records('gepeminfotutoria', ['id' => $id]);
    return true;
}
