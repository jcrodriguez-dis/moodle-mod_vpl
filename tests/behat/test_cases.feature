@mod @mod_vpl @mod_vpl_test_cases
Feature: In a VPL activity, editing teacher changes test cases
  In order to change test cases
  As an editing teacher
  I create and update test cases and verify that the saved contents persist

  Background:
    Given the default editor is set to "textarea"
    And the following "courses" exist:
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
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name | VPL activity name |
      | id_shortdescription | VPL activity short description |
      | id_introeditor | No description |
      | id_duedate_enabled | "" |
      | id_maxfiles | 33 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher creates and updates test cases
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    When I navigate to "Tests configuration" in current page administration
    Then I should see "vpl_evaluate.cases"
    When I drop the file "vpl_evaluate.cases" containing "Case = first test\ninput = 1\noutput = 2\n" on "#vpl_tabs" in VPL
    Then I should see "Case = first test"
    When I click on "#vpl_ide_save" in VPL
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should see "vpl_evaluate.cases"
    And I should see "Case = first test"
    When I navigate to "Tests configuration" in current page administration
    Then I should see "Case = first test"
    And I should see "input = 1"
    And I should see "output = 2"
    When I drop the file "vpl_evaluate.cases" containing "Case = updated test\ninput = 3\noutput = 6\n" on "#vpl_tabs" in VPL
    Then I should see "Case = updated test"
    And I should not see "Case = first test"
    When I click on "#vpl_ide_save" in VPL
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    And I navigate to "Tests configuration" in current page administration
    Then I should see "Case = updated test"
    And I should see "input = 3"
    And I should see "output = 6"
    And I should not see "Case = first test"
