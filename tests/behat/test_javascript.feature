@mod @mod_vpl @mod_vpl_javascript_test
Feature: Runs JavaScript tests on browser
  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | manager1 | Manager | Manager | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | manager1 | C1 | manager |
    And I log in as "manager1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | JavaScript test |
    And I click on "JavaScript test" "link" in the "region-main" "region"
    # "Edit setting" for Moodle < 4 and "Setting" for Moodle >= 4
    And I navigate to "ettings" in current page administration
    And I set the following fields to these values:
      | id_introeditor | "<a href="/mod/vpl/tests/test_javascript.php">Run test</a>" |
    And I press "Save and display"

  @javascript
  Scenario: A manager runs JavaScript tests
    When I follow "Run test"
    Then I should see "Test passed"
    And I should not see "Error:"
