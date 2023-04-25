@mod @mod_vpl
Feature: List submissions of students
  In order to list submissions of students
  As an editing teacher or non-editing teacher
  I go to submissions list and see no error
  I go to Submissions repor and see no error
  I go to Download submissions and see no error
  I go to Download all submissions and see no error

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | teacher2 | Teacher2 | 1 | teacher2@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 1 | student2@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | teacher2 | C1 | teacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity 1 |
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity 2 |
    And I log out

  @javascript
  Scenario: An editing teacher sees Submissions list
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity 1" "link" in the "region-main" "region"
    When I follow "Submissions list"
    Then I should see "VPL activity 1"
    And I should see "Submitted on"
    But I should not see "Error"
    And I should not see " is deprecated"

  @javascript
  Scenario: An non-editing teacher sees Submissions list
    Given I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity 1" "link" in the "region-main" "region"
    When I follow "Submissions list"
    Then I should see "VPL activity 1"
    And I should see "Submitted on"
    But I should not see "Error"
    And I should not see " is deprecated"

  @javascript
  Scenario: An editing teacher download submissions
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity 1" "link" in the "region-main" "region"
    And I follow "Submissions list"
    And I click on "th a.dropdown-toggle" in VPL
    When I follow "Download submissions"
    Then I should not see "Error"

  @javascript
  Scenario: A non-editing teacher download submissions
    Given I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity 1" "link" in the "region-main" "region"
    And I follow "Submissions list"
    And I click on "th a.dropdown-toggle" in VPL
    When I follow "Download submissions"
    Then I should not see "Error"

  @javascript
  Scenario: An editing teacher download all submissions
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity 1" "link" in the "region-main" "region"
    And I follow "Submissions list"
    And I click on "th a.dropdown-toggle" in VPL
    When I follow "Download all submissions"
    Then I should not see "Error"

  @javascript
  Scenario: A non-editing teacher download all submissions
    Given I log in as "teacher2"
    And I am on "Course 1" course homepage
    And I click on "VPL activity 1" "link" in the "region-main" "region"
    And I follow "Submissions list"
    And I click on "th a.dropdown-toggle" in VPL
    When I follow "Download all submissions"
    Then I should not see "Error"
