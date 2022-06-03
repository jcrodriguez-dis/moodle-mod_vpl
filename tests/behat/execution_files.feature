@mod @mod_vpl
Feature: In an VPL activity, editing teacher manage execution files
  In order to manages activity requested files
  As an editing teacher
  I access to a VPL activity and create, rename and delete requested files

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
      | id_shortdescription | VPL activity short description |
      | id_duedate_enabled | "" |
      | id_maxfiles | 33 |
      | id_grade_modgrade_type | None |
    And I log out

  @javascript
  Scenario: A teacher sets execution files by adding, renaming, deleting, and seeing files
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    # See default files
    When I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Execution files" in current page administration
    Then I should see "vpl_run.sh"
    And I should see "vpl_debug.sh"
    And I should see "vpl_evaluate.sh"
    And I should see "vpl_evaluate.cases"
    # Add new file
    When I click on "#vpl_ide_more" in VPL
    And I click on "#vpl_ide_new" in VPL
    And I set the following fields to these values:
      | vpl_ide_input_newfilename | new_file_name.c |
    And I click on "#vpl_ide_dialog_new + div button" in VPL
    Then I should see "new_file_name.c"
    # Add other new file
    And I click on "#vpl_ide_new" in VPL
    And I set the following fields to these values:
      | vpl_ide_input_newfilename | suddirectory/other file_name.c |
    And I click on "#vpl_ide_dialog_new + div button" in VPL
    Then I should see "new_file_name.c"
    And  I should see "other file_name.c"
    # Save files
    When I click on "#vpl_ide_save" in VPL
    # Reload files
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Execution files" in current page administration
    Then I should see "vpl_run.sh"
    And I should see "vpl_debug.sh"
    And I should see "vpl_evaluate.sh"
    And I should see "vpl_evaluate.cases"
    And I should see "new_file_name.c"
    And I should see "other file_name.c"
    # Rename file
    When I follow "new_file_name.c"
    When I click on "#vpl_ide_more" in VPL
    And I click on "#vpl_ide_rename" in VPL
    And I set the following fields to these values:
      | vpl_ide_input_renamefilename | changed file.c |
    And I click on "#vpl_ide_dialog_rename + div button" in VPL
    Then I should see "changed file.c"
    Then I click on "#vpl_ide_save" in VPL
    # Reload files
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Execution files" in current page administration
    Then I should see "vpl_run.sh"
    And I should see "vpl_debug.sh"
    And I should see "vpl_evaluate.sh"
    And I should see "vpl_evaluate.cases"
    And I should see "changed file.c"
    And I should see "other file_name.c"
    # Remove file
    When I follow "other file_name.c"
    When I click on "#vpl_ide_more" in VPL
    And I click on "#vpl_ide_delete" in VPL
    Then I should see "Delete file?"
    And I click on "div.vpl_ide_dialog:last + div button" in VPL
    Then I should not see "other file_name.c"
    Then I click on "#vpl_ide_save" in VPL
    # Reload files
    And I am on "Course 1" course homepage
    And I click on "VPL activity testing" "link" in the "region-main" "region"
    And I navigate to "Execution files" in current page administration
    Then I should see "vpl_run.sh"
    And I should see "vpl_debug.sh"
    And I should see "vpl_evaluate.sh"
    And I should see "vpl_evaluate.cases"
    And I should see "changed file.c"
    And I should not see "other file_name.c"
