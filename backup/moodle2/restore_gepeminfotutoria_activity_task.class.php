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
 * Restore task for mod_gepeminfotutoria.
 *
 * @package    mod_gepeminfotutoria
 * @category   backup
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/gepeminfotutoria/backup/moodle2/restore_gepeminfotutoria_stepslib.php');

/**
 * Provides the steps to restore one gepeminfotutoria activity instance.
 */
class restore_gepeminfotutoria_activity_task extends restore_activity_task {

    /**
     * No specific restore settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Add the activity structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_gepeminfotutoria_activity_structure_step(
            'gepeminfotutoria_structure',
            'gepeminfotutoria.xml'
        ));
    }

    /**
     * Define fields whose links must be decoded after restore.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('gepeminfotutoria', ['intro'], 'gepeminfotutoria'),
        ];
    }

    /**
     * Define link decoding rules for this module.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('GEPEMINFOTUTORIAVIEWBYID', '/mod/gepeminfotutoria/view.php?id=$1', 'course_module'),
            new restore_decode_rule('GEPEMINFOTUTORIAINDEX', '/mod/gepeminfotutoria/index.php?id=$1', 'course'),
        ];
    }
}
