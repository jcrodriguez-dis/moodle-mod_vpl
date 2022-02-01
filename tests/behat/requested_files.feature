@mod @mod_vpl
Feature: In a VPL activity, an editing teacher sets "requested files" and a student used it
  In order to manage "requested files"
  As an editing teacher
  I access a VPL activity and create, rename and delete requested files

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
      | id_name | VPL activity testing |
      | id_shortdescription | VPL activity short description |
      | id_duedate_enabled | "" |
      | id_maxfiles | 33 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher sets requested files
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Requested files" in current page administration
    And I set the following fields to these values:
      | vpl_ide_input_newfilename | new_file_name.c |
    And I click on "#vpl_ide_dialog_new + div button" in VPL
    Then I should see "new_file_name.c"
    When I click on "#vpl_ide_more" in VPL
    And I click on "#vpl_ide_new" in VPL
    And I set the following fields to these values:
      | vpl_ide_input_newfilename | subdirectory/other new file.c |
    And I click on "#vpl_ide_dialog_new + div button" in VPL
    Then I should see "other new file.c"
    When I click on "#vpl_ide_save" in VPL
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I should see "Requested files"
    Then I should see "new_file_name.c"
    And I should see "subdirectory/other new file.c"
    Then I log out
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I click on "VPL activity testing" "link" in the "region-main" "region"
    Then I should see "Requested files"
    And I should see "new_file_name.c"
    And I should see "subdirectory/other new file.c"
    When I follow "Submission view"
    Then I should see "No submission available"
    When I follow "Edit"
    Then I should see "new_file_name.c"
    And I should see "other new file.c"
    When I click on "#vpl_ide_save" in VPL
    And I follow "Submission view"
    Then I should see "Submitted"
    And I should see "new_file_name.c"
    And I should see "subdirectory/other new file.c"
