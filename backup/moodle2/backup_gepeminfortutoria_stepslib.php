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
 * Backup steps for mod_gepeminfortutoria.
 *
 * @package    mod_gepeminfortutoria
 * @category   backup
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete activity structure for backup.
 */
class backup_gepeminfortutoria_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the structure stored in gepeminfortutoria.xml.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $gepeminfortutoria = new backup_nested_element('gepeminfortutoria', ['id'], [
            'course',
            'name',
            'intro',
            'introformat',
            'supporttitle',
            'expecttutor',
            'expectmediator',
            'timecreated',
            'timemodified',
        ]);

        $gepeminfortutoria->set_source_table('gepeminfortutoria', ['id' => backup::VAR_ACTIVITYID]);
        $gepeminfortutoria->annotate_files('mod_gepeminfortutoria', 'intro', null);

        return $this->prepare_activity_structure($gepeminfortutoria);
    }
}
