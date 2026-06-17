@mod @mod_gepeminfortutoria
Feature: Display tutoring information inline in the course
  In order to find support contacts without extra navigation
  As a course participant
  I need the tutoring information activity to appear inline in the course page

  Background:
    Given the following "courses" exist:
      | fullname | shortname | format |
      | Course 1 | C1        | topics |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | student1 | João      | Aluno    | student1@example.com |
      | teacher1 | Teacher   | One      | teacher1@example.com |
      | tutor1   | Ana       | Tutoria  | tutor1@example.com |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | student1  | C1     | student        |
      | teacher1  | C1     | editingteacher |
      | tutor1    | C1     | teacher        |
    And the following "groups" exist:
      | name                    | course | idnumber |
      | Polo Bataguassu (20)    | C1     | POLO1    |
    And the following "group members" exist:
      | user      | group |
      | student1  | POLO1 |
      | tutor1    | POLO1 |
    And the following "activity" exists:
      | activity       | gepeminfortutoria |
      | course         | C1                 |
      | idnumber       | UIT1               |
      | name           | Tutoring Team      |
      | intro          | Tutoring assigned to the course polos. |
      | supporttitle   | Your Tutoring      |

  Scenario: Student sees their polo contacts inline in the course page
    When I am on the "Course 1" course page logged in as "student1"
    Then I should see "Your Tutoring"
    And I should see "Bataguassu"
    And I should not see "Polo Bataguassu (20)"
    And I should see "Ana Tutoria"
    And I should see "My polo"
    And I should see "Full list"

  Scenario: Teacher sees the full course team inline in the course page
    When I am on the "Course 1" course page logged in as "teacher1"
    Then I should see "Tutoring Team"
    And I should see "Ana Tutoria"
    And I should not see "My polo"

  @javascript
  Scenario: Teacher can add the activity from the activity chooser
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Add an activity or resource" "button" in the "New section" "section"
    And I click on "Add a new Tutoring information" "link" in the "Add an activity or resource" "dialogue"
    And I set the following fields to these values:
      | Name | Tutoring contacts |
      | Student panel title | Support contacts |
    When I press "Save and return to course"
    Then I should see "Tutoring Team" in the "New section" "section"
