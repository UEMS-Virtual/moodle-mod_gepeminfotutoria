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
 * Renderable page for mod_gepeminfortutoria.
 *
 * @package    mod_gepeminfortutoria
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gepeminfortutoria\output;

use mod_gepeminfortutoria\local\team_data;
use stdClass;

/**
 * Page renderable for the tutoring information activity.
 */
class tutoria_page implements \renderable, \templatable {

    /** @var stdClass Activity instance. */
    private stdClass $instance;

    /** @var object Course module (cm_info or plain stdClass). */
    private object $cm;

    /** @var stdClass Course record. */
    private stdClass $course;

    /** @var \context_module Module context. */
    private \context_module $context;

    /** @var int Viewing user id. */
    private int $userid;

    /**
     * Constructor.
     *
     * @param stdClass         $instance Activity instance record.
     * @param object           $cm       Course module record (stdClass or cm_info).
     * @param stdClass         $course   Course record.
     * @param \context_module  $context  Module context.
     * @param int              $userid   Viewing user id (defaults to $USER->id).
     */
    public function __construct(
        stdClass $instance,
        object $cm,
        stdClass $course,
        \context_module $context,
        int $userid = 0
    ) {
        global $USER;
        $this->instance = $instance;
        $this->cm       = $cm;
        $this->course   = $course;
        $this->context  = $context;
        $this->userid   = $userid ?: (int) $USER->id;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output
     * @return array<string, mixed>
     */
    public function export_for_template(\renderer_base $output): array {
        $coursecontext = \context_course::instance($this->course->id);
        $isstudent     = team_data::is_student($this->userid, $coursecontext);
        $polo_groups   = team_data::get_polo_groups($this->course->id);
        $team          = team_data::get_team($this->course->id, $this->context);
        $tutors_data   = $this->format_members($team['tutors']);

        $supporttitle = trim($this->instance->supporttitle ?? '');
        if ($supporttitle === '') {
            $supporttitle = get_string('seuponto', 'gepeminfortutoria');
        }

        $fullintro = trim($this->instance->intro ?? '');
        $fullintro = $fullintro === '' ? '' : format_text(
            $fullintro,
            $this->instance->introformat ?? FORMAT_HTML,
            ['context' => $this->context]
        );

        $base = [
            'hascontent' => true,
            'shownotice' => false,
            'show_tutors' => true,
            'all_tutors' => $tutors_data,
            'all_has_tutors' => !empty($tutors_data),
            'all_empty_tutors_message' => get_string('tutorianotinformedcourse', 'gepeminfortutoria'),
            'full_intro' => $fullintro,
            'has_full_intro' => $fullintro !== '',
        ];

        if ($isstudent) {
            $student_polos = team_data::get_student_polos($this->userid, $polo_groups);
            $polo_name     = !empty($student_polos) ? $student_polos[0] : '';
            $mine_tutors   = $this->filter_by_polo($team['tutors'], $polo_name);

            return $base + [
                'isstudent'           => true,
                'supporttitle'        => $supporttitle,
                'polo_name'           => self::format_polo_name($polo_name),
                'has_polo'            => !empty($polo_name),
                'mine_tutors'         => $mine_tutors,
                'mine_has_tutors'     => !empty($mine_tutors),
                'mine_tutor_label'    => get_string('tutoria', 'gepeminfortutoria'),
                'mine_empty_tutors_message' => get_string('tutorianotinformedpolo', 'gepeminfortutoria'),
                'nopolohelp' => get_string('nopolohelp', 'gepeminfortutoria'),
            ];
        }

        return $base + [
            'isstudent' => false,
        ];
    }

    /**
     * Convert raw member arrays into template-ready arrays.
     *
     * @param array $members Output of team_data::get_team().
     * @return array
     */
    private function format_members(array $members): array {
        $result = [];
        foreach ($members as $m) {
            $user  = $m['user'];
            $polos = $m['polos'];
            $count = count($polos);

            $polos_items = [];
            foreach (array_values($polos) as $index => $polo) {
                $polos_items[] = [
                    'name'          => self::format_polo_name($polo),
                    'has_separator' => $index > 0,
                ];
            }

            $polos_label = $count > 1
                ? get_string('polosatendidos', 'gepeminfortutoria')
                : get_string('polo', 'gepeminfortutoria');

            $result[] = [
                'name'            => fullname($user),
                'profileimageurl' => $m['profileimageurl'],
                'messageurl'      => $m['messageurl'],
                'polos_label'     => $polos_label,
                'polos_items'     => $polos_items,
                'has_polos'       => !empty($polos),
            ];
        }
        return $result;
    }

    /**
     * Filter team members to those assigned to a given polo.
     *
     * @param array  $members   Raw team_data members.
     * @param string $polo_name Polo name to filter by.
     * @return array Template-ready member arrays.
     */
    private function filter_by_polo(array $members, string $polo_name): array {
        if ($polo_name === '') {
            return [];
        }

        $filtered = array_filter($members, function ($m) use ($polo_name) {
            return in_array($polo_name, $m['polos'], true);
        });

        return $this->format_members(array_values($filtered));
    }

    /**
     * Format polo names for display only.
     *
     * @param string $name Raw Moodle group name.
     * @return string Display name.
     */
    private static function format_polo_name(string $name): string {
        $name = trim($name);
        $name = preg_replace('/^polo(?:\s+uab)?(?:\s+associado)?(?:\s+(?:de|da|do|das|dos))?\s+/iu', '', $name);
        $name = preg_replace('/\s*\(\d+\)\s*$/u', '', $name ?? '');
        $name = trim($name ?? '');

        if ($name === '') {
            return '';
        }

        $connectors = ['de', 'da', 'do', 'das', 'dos', 'e'];
        $words = preg_split('/(\s+)/u', \core_text::strtolower($name), -1, PREG_SPLIT_DELIM_CAPTURE);
        $wordindex = 0;

        foreach ($words as $index => $word) {
            if (trim($word) === '') {
                continue;
            }
            if ($wordindex > 0 && in_array($word, $connectors, true)) {
                $wordindex++;
                continue;
            }
            $words[$index] = \core_text::strtoupper(\core_text::substr($word, 0, 1)) . \core_text::substr($word, 1);
            $wordindex++;
        }

        return implode('', $words);
    }

}
