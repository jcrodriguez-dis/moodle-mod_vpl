@mod @mod_vpl @mod_vpl_activity_modes
Feature: In an VPL activity, editing teacher change activity modes
  In order to modify activity behaviour
  As an editing teacher
  I need to change activity mode and check if the behaviour of activities change for students

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
      | id_name | VPL simple |
      | id_shortdescription | VPL activity short description |
      | id_introeditor | Full description simple|
      | id_duedate_enabled | "" |
      | id_maxfiles | 33 |
      | id_grade_modgrade_type | None |
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name | VPL base activity |
      | id_shortdescription | VPL activity short description |
      | id_introeditor | Full description based on |
      | id_duedate_enabled | "" |
      | id_maxfiles | 100 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: Normal mode - students can access and submit
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | Normal |
    And I press "Save and display"
    Then I should see "VPL simple"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "VPL simple"
    When I click on "VPL simple" "link" in the "region-main" "region"
    Then I should see "Full description simple"
    And I should see "Submission view"
    And I should see "Edit"
    And I should not see "Not available"

  @javascript
  Scenario: No students mode - students cannot access the activity
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | No students |
    And I press "Save and display"
    Then I should see "VPL simple"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "VPL simple"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I open "VPL simple" actions menu
    And I choose "Show" in the open action menu
    And I turn editing mode off
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "VPL simple"
    When I click on "VPL simple" "link" in the "region-main" "region"
    Then I should see "Not available"

  @javascript
  Scenario: Students read-only mode - students can view but not modify
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | Students read-only |
    And I press "Save and display"
    Then I should see "VPL simple"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "VPL simple"
    When I click on "VPL simple" "link" in the "region-main" "region"
    Then I should see "Full description simple"
    And I should see "Submission view"
    And I should not see "Edit"

  @javascript
  Scenario: Based on mode - activity is not visible to students
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | Based on |
    And I press "Save and display"
    Then I should see "VPL simple"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "VPL simple"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I open "VPL simple" actions menu
    And I choose "Show" in the open action menu
    And I turn editing mode off
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "VPL simple"
    When I click on "VPL simple" "link" in the "region-main" "region"
    Then I should see "Not available"

  @javascript
  Scenario: Example mode - students can view the activity as example
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | Example |
    And I press "Save and display"
    And I navigate to "Execution options" in current page administration
    And I set the following fields to these values:
      | id_run | 1 |
      | id_debug | 1 |
    And I press "Save options"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "VPL simple"
    When I click on "VPL simple" "link" in the "region-main" "region"
    Then I should see "Full description simple"
    And I should see "Run"

  @javascript
  Scenario: VPL question mode - students cannot access the activity
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | VPL question |
    And I press "Save and display"
    Then I should see "VPL simple"
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should not see "VPL simple"
    And I log out
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I open "VPL simple" actions menu
    And I choose "Show" in the open action menu
    And I turn editing mode off
    And I log out
    When I log in as "student1"
    And I am on "Course 1" course homepage
    Then I should see "VPL simple"
    When I click on "VPL simple" "link" in the "region-main" "region"
    Then I should see "Not available"

  @javascript
  Scenario: Teacher can always access activities in any mode
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL simple" "link" in the "region-main" "region"
    And I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | No students |
    And I press "Save and display"
    Then I should see "VPL simple"
    When I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | Based on |
    And I press "Save and display"
    Then I should see "VPL simple"
    When I navigate to "Settings" in current page administration
    And I set the following fields to these values:
      | id_activity_mode | VPL question |
    And I press "Save and display"
    Then I should see "VPL simple"
