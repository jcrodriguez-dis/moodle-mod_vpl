@mod @mod_vpl @mod_vpl_list_activities
Feature: In a VPL activity get the list of VPL activities in the course
  In order to get the list of VPL activities in the course
  As a teacher, choose the "Virtual programming Labs" option in the administration
  menu of a VPL activity.
  And see list of VPL activities

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
      | id_name | VPL activity one |
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity two |
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity three |
    And I log out

  @javascript
  Scenario: A teacher see the list of VPL activities
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity one" "link" in the "region-main" "region"
    And I navigate to "Virtual programming labs" in current page administration
    Then I should see "VPL activity one"
    And I should see "VPL activity two"
    And I should see "VPL activity three"
    And I should not see " is deprecated"
