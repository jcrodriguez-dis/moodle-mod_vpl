@mod @mod_vpl @mod_vpl_files_to_keep
Feature: In a VPL activity feature files to keep when running
  In order to use files to keep when running
  As a teacher save files in "execution files"
  And set and see files in files to keep when running

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
    And the following "user preferences" exist:
      | user     | preference | value    |
      | teacher1 | htmleditor | textarea |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "vpl" activity to course "Course 1" section "1" and I fill the form with:
      | id_name | VPL activity testing |
      | id_introeditor | No description |
    And I log out

  @javascript
  Scenario: A teacher set and see files to keep when running
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Execution files" in current page administration
    And I drop the files "hello.c|hello.py|hello.adb" on "#vpl_tabs" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Files to keep when running" in current page administration
    And I should see "hello.c"
    And I should see "hello.py"
    And I should see "hello.adb"
    And I click on "#id_keepfile1" in VPL
    And I click on "#id_keepfile4" in VPL
    And I press "Save options"
    Then I should see "Options have been saved"
    And "input[id=id_keepfile0]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile1][checked]" "css_element" should exist
    And "input[id=id_keepfile2]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile3]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile4][checked]" "css_element" should exist
    And "input[id=id_keepfile5]:not([checked])" "css_element" should exist
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Files to keep when running" in current page administration
    Then I should not see "Options have been saved"
    And "input[id=id_keepfile0]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile1][checked]" "css_element" should exist
    And "input[id=id_keepfile2]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile3]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile4][checked]" "css_element" should exist
    And "input[id=id_keepfile5]:not([checked])" "css_element" should exist
    And I click on "#id_keepfile1" in VPL
    And I press "Save options"
    Then I should see "Options have been saved"
    And "input[id=id_keepfile0]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile1]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile2]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile3]:not([checked])" "css_element" should exist
    And "input[id=id_keepfile4][checked]" "css_element" should exist
    And "input[id=id_keepfile5]:not([checked])" "css_element" should exist
