@mod @mod_vpl
Feature: In an VPL activity, editing teacher change options of execution
  In order to modify activity behaviour
  As an editing teacher
  I need to change options and check the description change and student can see changes

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
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL base activity |
      | id_shortdescription | VPL activity short description |
      | id_duedate_enabled | "" |
      | id_maxfiles | 100 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher sees the execution options default values
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should see "Maximum number of files: 33"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should see "Grade settings: No grade"
    And I should see "Run: No"
    And I should not see "Debug:"
    And I should see "Evaluate: No"
    And I should not see "Evaluate just on submission:"
    And I should not see "Automatic grade:"

  @javascript
  Scenario: A student sees the execution options default values
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should see "Maximum number of files: 33"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should not see "Grade settings: No grade"
    And I should not see "Run:"
    And I should not see "Debug:"
    And I should not see "Evaluate:"
    And I should not see "Evaluate just on submission:"
    And I should not see "Automatic grade:"

  @javascript
  Scenario: A teacher changes the execution options => A teacher see values
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    And I navigate to "Execution options" in current page administration
    And I set the following fields to these values:
      | id_basedon | VPL base activity |
      | id_run | 1 |
      | id_debug | 1 |
      | id_evaluate | 1 |
      | id_evaluateonsubmission | 1 |
      | id_automaticgrading  | 1 |
    And I press "save options"
    And I should see "Options have been saved"
    When I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should see "Based on: VPL base activity"
    And I should see "Maximum number of files: 33"
    And I should see "Run: Yes"
    And I should see "Debug: Yes"
    And I should see "Evaluate: Yes"
    And I should see "Grade settings: No grade"
    And I should see "Evaluate just on submission: Yes"
    And I should see "Automatic grade: Yes"

  @javascript
  Scenario: A teacher changes the execution options => A student see values
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    And I navigate to "Execution options" in current page administration
    And I set the following fields to these values:
      | id_basedon | VPL base activity |
      | id_run | 1 |
      | id_debug | 1 |
      | id_evaluate | 1 |
      | id_evaluateonsubmission | 1 |
      | id_automaticgrading  | 1 |
    And I press "save options"
    And I should see "Options have been saved"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should see "Maximum number of files: 33"
    And I should see "Type of work:"
    And I should see "Individual work"
    And I should not see "Grade settings:"
    And I should not see "Run:"
    And I should not see "Debug:"
    And I should not see "Evaluate:"
    And I should not see "Evaluate just on submission:"
    And I should not see "Automatic grade:"
