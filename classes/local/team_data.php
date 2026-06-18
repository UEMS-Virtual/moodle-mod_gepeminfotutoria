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
 * Team data service for mod_gepeminfotutoria.
 *
 * @package    mod_gepeminfotutoria
 * @copyright  2026 UEMS Virtual
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_gepeminfotutoria\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Reads tutoring team data from Moodle's existing users, roles and groups.
 *
 * Does not store anything — only reads.
 */
class team_data {

    /** Role shortname for tutoring staff (Moodle Moderador / non-editing teacher). */
    const ROLE_TUTOR = 'teacher';

    /** Expectation mode: resolve from course context. */
    const EXPECT_AUTO = 0;

    /** Expectation mode: function is expected. */
    const EXPECT_YES = 1;

    /** Expectation mode: function is not expected. */
    const EXPECT_NO = 2;

    /**
     * Return the full team for a course, grouped by tutoring function.
     *
     * Presential tutoring is identified by membership in groups whose names contain "polo".
     * Pedagogical mediation is identified by membership in course groups without "polo".
     *
     * @param int             $courseid
     * @param \context_module $modcontext  Used to build profile image URLs.
     * @return array{presential: array, mediation: array, tutors: array}
     */
    public static function get_team(int $courseid, \context_module $modcontext): array {
        $coursecontext = \context_course::instance($courseid);

        $polo_groups = self::get_polo_groups($courseid);
        $mediation_groups = self::get_mediation_groups($courseid);
        $user_polos = self::map_users_to_group_names($polo_groups);
        $user_mediation_groups = self::map_users_to_group_names($mediation_groups);

        $staff = self::get_users_by_role(self::ROLE_TUTOR, $coursecontext, $user_polos, $user_mediation_groups);

        $presential = [];
        $mediation = [];
        foreach ($staff as $member) {
            if (!empty($member['polos'])) {
                $presential[] = $member;
                continue;
            }
            if (!empty($member['mediation_groups'])) {
                $mediation[] = $member;
            }
        }

        return [
            'presential' => $presential,
            'mediation' => $mediation,
            // Backwards-compatible aggregate used by older tests/templates.
            'tutors' => array_merge($mediation, $presential),
        ];
    }

    /**
     * Return course groups whose names contain "polo" (case-insensitive).
     *
     * @param int $courseid
     * @return array  id => stdClass (id, name)
     */
    public static function get_polo_groups(int $courseid): array {
        global $DB;

        $groups = $DB->get_records('groups', ['courseid' => $courseid], 'name', 'id, name');
        return array_filter($groups, function ($g) {
            return self::is_polo_group($g->name);
        });
    }

    /**
     * Return course groups without "polo" in the name.
     *
     * @param int $courseid
     * @return array id => stdClass (id, name)
     */
    public static function get_mediation_groups(int $courseid): array {
        global $DB;

        $groups = $DB->get_records('groups', ['courseid' => $courseid], 'name', 'id, name');
        return array_filter($groups, function ($g) {
            return !self::is_polo_group($g->name);
        });
    }

    /**
     * Determine if a group is a polo group.
     *
     * @param string $name
     * @return bool
     */
    private static function is_polo_group(string $name): bool {
        return stripos($name, 'polo') !== false;
    }

    /**
     * Build a map of userid => string[] of group names.
     *
     * @param array $groups id => group records.
     * @return array userid => string[]
     */
    private static function map_users_to_group_names(array $groups): array {
        global $DB;

        if (empty($groups)) {
            return [];
        }

        list($in_sql, $params) = $DB->get_in_or_equal(array_keys($groups), \SQL_PARAMS_NAMED, 'grp');

        $rs = $DB->get_recordset_sql(
            "SELECT gm.id, gm.userid, g.name
               FROM {groups_members} gm
               JOIN {groups} g ON g.id = gm.groupid
              WHERE gm.groupid $in_sql",
            $params
        );

        $map = [];
        foreach ($rs as $row) {
            $map[$row->userid][] = $row->name;
        }
        $rs->close();
        return $map;
    }

    /**
     * Return active users who have a specific role shortname in the given context.
     *
     * @param string          $shortname             Role shortname.
     * @param \context_course $coursecontext
     * @param array           $user_polos            userid => polo group names.
     * @param array           $user_mediation_groups userid => non-polo group names.
     * @return array
     */
    private static function get_users_by_role(
        string $shortname,
        \context_course $coursecontext,
        array $user_polos,
        array $user_mediation_groups
    ): array {
        global $DB, $PAGE;

        $role = $DB->get_record('role', ['shortname' => $shortname]);
        if (!$role) {
            return [];
        }

        $userfields = \core_user\fields::for_userpic()->with_name()->including('email');
        $usersql = $userfields->get_sql('u', false, '', '', false);

        $role_users = \get_role_users(
            $role->id,
            $coursecontext,
            false,
            $usersql->selects,
            'u.lastname, u.firstname'
        );

        $active = self::filter_active_enrolments($coursecontext->instanceid, $role_users);

        $result = [];
        foreach ($active as $user) {
            $userpicture       = new \user_picture($user);
            $userpicture->size = 128;

            $result[] = [
                'user'              => $user,
                'polos'             => $user_polos[$user->id] ?? [],
                'mediation_groups'  => $user_mediation_groups[$user->id] ?? [],
                'profileimageurl'   => $userpicture->get_url($PAGE)->out(false),
                'messageurl'        => (new \moodle_url('/message/index.php', ['id' => $user->id]))->out(false),
            ];
        }

        return $result;
    }

    /**
     * Remove users who are not actively enrolled in the course.
     *
     * @param int   $courseid
     * @param array $users   id-keyed user records.
     * @return array
     */
    private static function filter_active_enrolments(int $courseid, array $users): array {
        global $DB;

        if (empty($users)) {
            return [];
        }

        list($in_sql, $params) = $DB->get_in_or_equal(array_keys($users), \SQL_PARAMS_NAMED, 'u');

        $now = time();
        $params['courseid'] = $courseid;
        $params['now1']     = $now;
        $params['now2']     = $now;

        $active_ids = $DB->get_fieldset_sql(
            "SELECT DISTINCT ue.userid
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
               JOIN {user}  u ON u.id = ue.userid
              WHERE e.courseid = :courseid
                AND ue.userid $in_sql
                AND ue.status = 0
                AND u.suspended = 0
                AND u.deleted   = 0
                AND (ue.timestart = 0 OR ue.timestart <= :now1)
                AND (ue.timeend   = 0 OR ue.timeend   >= :now2)",
            $params
        );

        $active_set = array_flip($active_ids);
        return array_filter($users, fn($u) => isset($active_set[$u->id]));
    }

    /**
     * Return true if the given user should see the student view (Meu polo).
     *
     * Any user without course management capability is treated as a student.
     *
     * @param int             $userid
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function is_student(int $userid, \context_course $coursecontext): bool {
        return !\has_capability('moodle/course:manageactivities', $coursecontext, $userid);
    }

    /**
     * Return true when the user can manage course activities.
     *
     * @param int             $userid
     * @param \context_course $coursecontext
     * @return bool
     */
    public static function can_manage_activities(int $userid, \context_course $coursecontext): bool {
        return \has_capability('moodle/course:manageactivities', $coursecontext, $userid);
    }

    /**
     * Return whether tutoring is expected for this adaptation.
     *
     * @param object $instance Activity instance.
     * @param object $course Course record.
     * @return bool
     */
    public static function expects_tutors(object $instance, object $course): bool {
        return true;
    }

    /**
     * Return polo names the given user belongs to (from the pre-filtered polo groups).
     *
     * @param int   $userid
     * @param array $polo_groups  Output of get_polo_groups().
     * @return string[]
     */
    public static function get_student_polos(int $userid, array $polo_groups): array {
        return self::get_user_group_names($userid, $polo_groups);
    }

    /**
     * Return mediation group names the given user belongs to.
     *
     * @param int   $userid
     * @param array $mediation_groups Output of get_mediation_groups().
     * @return string[]
     */
    public static function get_student_mediation_groups(int $userid, array $mediation_groups): array {
        return self::get_user_group_names($userid, $mediation_groups);
    }

    /**
     * Return group names the given user belongs to from a pre-filtered group list.
     *
     * @param int   $userid
     * @param array $groups id => group records.
     * @return string[]
     */
    private static function get_user_group_names(int $userid, array $groups): array {
        global $DB;

        if (empty($groups)) {
            return [];
        }

        list($in_sql, $params) = $DB->get_in_or_equal(array_keys($groups), \SQL_PARAMS_NAMED, 'grp');
        $params['userid'] = $userid;

        $rows = $DB->get_records_sql(
            "SELECT g.name
               FROM {groups_members} gm
               JOIN {groups} g ON g.id = gm.groupid
              WHERE gm.userid = :userid
                AND gm.groupid $in_sql",
            $params
        );

        return array_values(array_column($rows, 'name'));
    }
}
