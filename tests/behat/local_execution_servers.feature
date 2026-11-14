@mod @mod_vpl @mod_vpl_local_execution_servers
Feature: In a VPL activity feature Local execution servers
  In order to use local execution servers
  As a teacher set URLs in "Local execution servers"
  And see URLs

  Background:
    Given the default editor is set to "textarea"
    And the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name | VPL activity testing |
      | id_introeditor | No description |
    And I log out

  Scenario: A teacher set and see URLs in Local execution servers
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Local execution servers" in current page administration
    And I set the following fields to these values:
      | id_jailservers | https://demojail.dis.ulpgc.es |
    And I press "Save changes"
    Then I should see "Saved"
    And I should see "https://demojail.dis.ulpgc.es"
    And I set the following fields to these values:
      | id_jailservers | https://nojail.com |
    And I press "Save changes"
    Then I should see "Saved"
    And I should see "https://nojail.com"

  Scenario Outline: Invalid server settings preserve the previously saved configuration
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Local execution servers" in current page administration
    And I set the field "jailservers" to "https://8.8.8.8/secret"
    And I press "Save changes"
    And I should see "Saved"
    When I set the field "jailservers" to multiline:
      """
      https://1.1.1.1/another
      <server>
      """
    And I press "Save changes"
    Then I should see "Options have not been saved"
    And I should see "Bad URL format for execution server:"
    When I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Local execution servers" in current page administration
    Then the field "jailservers" matches value "https://8.8.8.8/secret"

    Examples:
      | server                         |
      | ftp://8.8.8.8/secret            |
      | https://8.8.8.8/secret?token=1  |
      | https://8.8.8.8/secret#status   |
      | https://8.8.8.8/directory/file |
      | https://8.8.8.8/secret%20file   |

  Scenario: Cancelling server changes preserves the previously saved configuration
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Local execution servers" in current page administration
    And I set the field "jailservers" to "https://8.8.8.8/secret"
    And I press "Save changes"
    When I set the field "jailservers" to "https://1.1.1.1/another"
    And I press "Cancel"
    Then I should see "Cancelled"
    When I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Local execution servers" in current page administration
    Then the field "jailservers" matches value "https://8.8.8.8/secret"
