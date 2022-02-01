@mod @mod_vpl
Feature: In a VPL activity feature Local execution servers
  In order to use local execution servers
  As a teacher set URLs in "Local execution servers"
  And see URLs

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity testing |
    And I log out

  @javascript
  Scenario: A teacher set and see URLs in Local execution servers
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Local execution servers" in current page administration
    And I set the following fields to these values:
      | id_jailservers | "https://demojail.dis.ulpgc.es" |
    And I press "Save changes"
    Then I should see "Saved"
    And I should see "https://demojail.dis.ulpgc.es"
    And I set the following fields to these values:
      | id_jailservers | "https://nojail.com" |
    And I press "Save changes"
    Then I should see "Saved"
    And I should see "https://nojail.com"
