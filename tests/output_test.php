<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_gepeminfotutoria;

use mod_gepeminfotutoria\local\team_data;
use mod_gepeminfotutoria\output\tutoria_page;

/**
 * Tests for template export rules.
 *
 * @package    mod_gepeminfotutoria
 * @covers     \mod_gepeminfotutoria\output\tutoria_page
 */
final class output_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    public function test_student_without_polo_does_not_receive_full_team_as_my_polo(): void {
        global $PAGE;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('gepeminfotutoria', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('gepeminfotutoria', $module->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $PAGE->set_context($context);

        $tutorroleid = $this->ensure_role(team_data::ROLE_TUTOR, 'Moderador');
        $student = $generator->create_and_enrol($course, 'student');
        $tutor = $generator->create_user(['firstname' => 'Ana', 'lastname' => 'Tutoria']);
        $generator->enrol_user($tutor->id, $course->id, $tutorroleid);
        $polo = $generator->create_group(['courseid' => $course->id, 'name' => 'Polo Bataguassu']);
        groups_add_member($polo, $tutor);

        $this->setUser($student);
        $data = (new tutoria_page($module, $cm, $course, $context, $student->id))
            ->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($data['isstudent']);
        $this->assertFalse($data['has_polo']);
        $this->assertFalse($data['mine_has_tutors']);
        $this->assertCount(0, $data['mine_tutors']);
        $this->assertTrue($data['all_has_tutors']);
        $this->assertCount(1, $data['all_tutors']);
    }

    public function test_student_panel_uses_default_title_and_tutoring_label(): void {
        global $PAGE;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('gepeminfotutoria', [
            'course' => $course->id,
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'supporttitle' => '',
        ]);
        $module->intro = '';
        $cm = get_coursemodule_from_instance('gepeminfotutoria', $module->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $PAGE->set_context($context);

        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);

        $data = (new tutoria_page($module, $cm, $course, $context, $student->id))
            ->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame(get_string('seuponto', 'gepeminfotutoria'), $data['supporttitle']);
        $this->assertSame('', $data['full_intro']);
        $this->assertFalse($data['has_full_intro']);
        $this->assertSame(get_string('tutoria', 'gepeminfotutoria'), $data['mine_tutor_label']);
    }

    public function test_full_intro_is_shown_when_configured(): void {
        global $PAGE;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('gepeminfotutoria', [
            'course' => $course->id,
            'intro' => 'Subtítulo opcional da lista completa',
            'introformat' => FORMAT_HTML,
        ]);
        $cm = get_coursemodule_from_instance('gepeminfotutoria', $module->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $PAGE->set_context($context);

        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $data = (new tutoria_page($module, $cm, $course, $context, $teacher->id))
            ->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($data['has_full_intro']);
        $this->assertStringContainsString('Subtítulo opcional da lista completa', $data['full_intro']);
    }

    public function test_polo_names_are_normalized_for_display_without_affecting_filtering(): void {
        global $PAGE;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('gepeminfotutoria', ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('gepeminfotutoria', $module->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $PAGE->set_context($context);

        $tutorroleid = $this->ensure_role(team_data::ROLE_TUTOR, 'Moderador');
        $student = $generator->create_and_enrol($course, 'student');
        $teacher = $generator->create_and_enrol($course, 'editingteacher');
        $tutor = $generator->create_user(['firstname' => 'Ana', 'lastname' => 'Tutoria']);
        $generator->enrol_user($tutor->id, $course->id, $tutorroleid);

        $rawnames = [
            'POLO UAB DE BATAGUASSU (20)',
            'POLO UAB DE CAMPO GRANDE (96)',
            'POLO ASSOCIADO DE NOVA ANDRADINA',
            'POLO DE RIO BRILHANTE',
        ];
        foreach ($rawnames as $rawname) {
            $group = $generator->create_group(['courseid' => $course->id, 'name' => $rawname]);
            groups_add_member($group, $tutor);
            if ($rawname === 'POLO UAB DE CAMPO GRANDE (96)') {
                groups_add_member($group, $student);
            }
        }

        $this->setUser($student);
        $studentdata = (new tutoria_page($module, $cm, $course, $context, $student->id))
            ->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame('Campo Grande', $studentdata['polo_name']);
        $this->assertTrue($studentdata['mine_has_tutors']);

        $this->setUser($teacher);
        $teacherdata = (new tutoria_page($module, $cm, $course, $context, $teacher->id))
            ->export_for_template($PAGE->get_renderer('core'));

        $polonames = array_column($teacherdata['all_tutors'][0]['polos_items'], 'name');
        sort($polonames);
        $this->assertSame(['Bataguassu', 'Campo Grande', 'Nova Andradina', 'Rio Brilhante'], $polonames);
    }

    public function test_tutoring_is_always_expected_even_without_contacts(): void {
        global $PAGE;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $module = $generator->create_module('gepeminfotutoria', [
            'course' => $course->id,
            'expecttutor' => team_data::EXPECT_NO,
        ]);
        $cm = get_coursemodule_from_instance('gepeminfotutoria', $module->id, $course->id, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        $PAGE->set_context($context);

        $student = $generator->create_and_enrol($course, 'student');
        $this->setUser($student);
        $studentdata = (new tutoria_page($module, $cm, $course, $context, $student->id))
            ->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($studentdata['hascontent']);
        $this->assertFalse($studentdata['all_has_tutors']);
        $this->assertSame(get_string('tutorianotinformedcourse', 'gepeminfotutoria'), $studentdata['all_empty_tutors_message']);
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
