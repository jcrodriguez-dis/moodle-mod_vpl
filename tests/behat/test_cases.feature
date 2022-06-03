@mod @mod_vpl
Feature: In a VPL activity, editing teacher changes test cases
  In order to change test cases
  As an editing teacher
  I access a VPL activity variation page and create, modify, and delete variations

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity name |
      | id_shortdescription | VPL activity short description |
      | id_duedate_enabled | "" |
      | id_maxfiles | 33 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher access to the test cases editor
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    When I navigate to "Test cases" in current page administration
    Then I should see "vpl_evaluate.cases"
    When I drop the file "vpl_evaluate.cases" contening "Case = test\n" on "#vpl_tabs" in VPL
    Then I should see "Case = test"
    When I click on "#vpl_ide_save" in VPL
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should see "vpl_evaluate.cases"
    And I should see "Case = test"
