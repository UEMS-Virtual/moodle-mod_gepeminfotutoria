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
 * Backup task for mod_gepeminfotutoria.
 *
 * @package    mod_gepeminfotutoria
 * @category   backup
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/gepeminfotutoria/backup/moodle2/backup_gepeminfotutoria_stepslib.php');

/**
 * Provides the steps to backup one gepeminfotutoria activity instance.
 */
class backup_gepeminfotutoria_activity_task extends backup_activity_task {

    /**
     * No specific backup settings.
     */
    protected function define_my_settings() {
    }

    /**
     * Add the activity structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new backup_gepeminfotutoria_activity_structure_step(
            'gepeminfotutoria_structure',
            'gepeminfotutoria.xml'
        ));
    }

    /**
     * Encode links to this module.
     *
     * @param string $content Content that may contain module links.
     * @return string Encoded content.
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/gepeminfotutoria\/index.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@GEPEMINFOTUTORIAINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/gepeminfotutoria\/view.php\?id=)([0-9]+)/';
        $content = preg_replace($search, '$@GEPEMINFOTUTORIAVIEWBYID*$2@$', $content);

        return $content;
    }
}
