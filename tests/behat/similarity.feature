@mod @mod_vpl
Feature: In a VPL activity, similarity feature
  In order to search file similarity
  Students submit files and teachers search similarity
  and see report

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1 | 0 | 1 |
    And the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 1 | student2@example.com |
      | student3 | Student | 1 | student3@example.com |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "Virtual programming lab" to section "1" and I fill the form with:
      | id_name | VPL activity testing |
      | id_shortdescription | VPL activity short description |
      | id_duedate_enabled | "" |
      | id_maxfiles | 33 |
      | id_grade_modgrade_type | None |
    And I follow "VPL activity testing"
    And I navigate to "Requested files" in current page administration
    And I set the following fields to these values:
      | vpl_ide_input_newfilename | hello.c |
    And I click on "#vpl_ide_dialog_new + div button" in VPL
    And I click on "#vpl_ide_save" in VPL
    And I log out

  @javascript
  Scenario: A teacher search similarity
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "VPL activity testing"
    And I follow "Edit"
    # Drops a new file
    And I drop the files "hello.c" on "#vpl_tabs" in VPL
    # Saves files
    And I click on "#vpl_ide_save" in VPL
    And I log out
    And I log in as "student2"
    And I am on "Course 1" course homepage
    And I follow "VPL activity testing"
    And I follow "Edit"
    # Drops a new file
    And I drop the files "hello.c" on "#vpl_tabs" in VPL
    # Saves files
    And I click on "#vpl_ide_save" in VPL
    And I log out
    And I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "VPL activity testing"
    And I follow "Edit"
    # Drops a new file
    And I drop the files "hello.c" on "#vpl_tabs" in VPL
    # Saves files
    And I click on "#vpl_ide_save" in VPL
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "VPL activity testing"
    And I follow "Similarity"
    And I should see "hello.c"
    And I press "Search"
    Then I should see "hello.c"
    And I should see "Student1"
    And I should see "Student2"
    And I should see "Student3"
    And I should see "100/100/100"
