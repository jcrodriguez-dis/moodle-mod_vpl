@mod @mod_vpl
Feature: In an VPL activity, editing teacher change variations
  In order to define/modify/delete activity variations
  As an editing teacher
  I access to a VPL activity variation page and create modify delete varitions

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
  Scenario: A teacher set variation title and activate variations
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity name" "link" in the "region-main" "region"
    And I navigate to "Variations" in current page administration
    And I set the following fields to these values:
      | id_usevariations | 1 |
      | id_variationtitle | My variation title text |
    And I press "Save"
    Then I should see "Updated My variation title text"
    Then I press "Continue"
    Then I am on "Course 1" course homepage
    Then I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should not see "My variation title text"

  @javascript
  Scenario: A teacher creates a variation and deletes a variation
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity name" "link" in the "region-main" "region"
    And I navigate to "Variations" in current page administration
    And I set the following fields to these values:
      | id_usevariations | 1 |
      | id_variationtitle | My variation title text |
    And I press "Save"
    Then I press "Continue"
    And I set the following fields to these values:
      | id_identification | variation-code |
      | id_description0 | This is a variation description |
    And I click on "form + form input[value='Save']" in VPL
    Then I should see "Saved"
    Then I press "Continue"
    Then I am on "Course 1" course homepage
    Then I click on "VPL activity name" "link" in the "region-main" "region"
    Then I should see "Variations"
    And I click on "#sht0" in VPL
    And I should see "variation-code"
    And I should see "My variation title text"
    And I should see "This is a variation description"
    Then I navigate to "Variations" in current page administration
    And I accept confirm in VPL
    And I click on "form + form input[value='Delete']" in VPL
    Then I should see "Deleted"
    Then I am on "Course 1" course homepage
    Then I click on "VPL activity name" "link" in the "region-main" "region"
    And I should not see "Variations"
