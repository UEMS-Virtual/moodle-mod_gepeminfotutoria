<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_gepeminfotutoria;

use mod_gepeminfotutoria\local\team_data;

/**
 * Tests for tutoring team data rules.
 *
 * @package    mod_gepeminfotutoria
 * @covers     \mod_gepeminfotutoria\local\team_data
 */
final class team_data_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_tutoring_is_always_expected(): void {
        $course = (object) ['shortname' => 'ABC_2026'];
        $this->assertTrue(team_data::expects_tutors((object) ['expecttutor' => team_data::EXPECT_NO], $course));
    }

    public function test_get_polo_groups_returns_only_course_groups_named_as_polos(): void {
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();

        $polo = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Polo Bataguassu (20)']);
        $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Seminário']);
        $this->getDataGenerator()->create_group(['courseid' => $othercourse->id, 'name' => 'Polo Outro Curso']);

        $groups = team_data::get_polo_groups($course->id);

        $this->assertArrayHasKey($polo->id, $groups);
        $this->assertCount(1, $groups);
        $this->assertSame('Polo Bataguassu (20)', $groups[$polo->id]->name);
    }

    public function test_get_team_returns_active_teacher_role_users_with_their_polos(): void {
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('gepeminfotutoria', ['course' => $course->id]);
        $context = \context_module::instance($module->cmid);

        $tutorroleid = $this->ensure_role(team_data::ROLE_TUTOR, 'Moderador');
        $editingteacherroleid = $this->ensure_role('editingteacher', 'Professor');

        $tutor = $generator->create_user(['firstname' => 'Ana', 'lastname' => 'Tutoria']);
        $editingteacher = $generator->create_user(['firstname' => 'Maria', 'lastname' => 'Professora']);
        $student = $generator->create_user(['firstname' => 'João', 'lastname' => 'Aluno']);
        $suspendedtutor = $generator->create_user(['firstname' => 'Carlos', 'lastname' => 'Suspenso']);

        $generator->enrol_user($tutor->id, $course->id, $tutorroleid);
        $generator->enrol_user($editingteacher->id, $course->id, $editingteacherroleid);
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($suspendedtutor->id, $course->id, $tutorroleid, 'manual', 0, 0, ENROL_USER_SUSPENDED);

        $polo = $generator->create_group(['courseid' => $course->id, 'name' => 'Polo Bataguassu']);
        $notpolo = $generator->create_group(['courseid' => $course->id, 'name' => 'Grupo de estudo']);
        groups_add_member($polo, $tutor);
        groups_add_member($polo, $editingteacher);
        groups_add_member($notpolo, $student);

        $team = team_data::get_team($course->id, $context);

        $this->assertCount(1, $team['tutors']);
        $this->assertSame($tutor->id, $team['tutors'][0]['user']->id);
        $this->assertSame(['Polo Bataguassu'], $team['tutors'][0]['polos']);
    }

    /**
     * Ensure a course-level role exists for the test.
     *
     * @param string $shortname
     * @param string $fullname
     * @return int
     */
    private function ensure_role(string $shortname, string $fullname): int {
        global $DB;

        if ($role = $DB->get_record('role', ['shortname' => $shortname])) {
            return (int) $role->id;
        }

        $roleid = create_role($fullname, $shortname, '', 'teacher');
        set_role_contextlevels($roleid, [CONTEXT_COURSE]);
        return $roleid;
    }
}
